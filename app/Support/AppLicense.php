<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Lisensi bertanda-tangan dari sae-core (sisi klien).
 *
 * Alur:
 *  1. Minta lisensi ke pusat (POST /api/license/issue) — memakai kunci instalasi.
 *  2. Verifikasi tanda-tangan Ed25519 dengan KUNCI PUBLIK tertanam.
 *  3. Simpan token + payload ke settings, sediakan state untuk UI/gate.
 *
 * Bila pusat tak terjangkau, lisensi sah terakhir tetap berlaku sampai
 * kedaluwarsa + masa tenggang.
 */
final class AppLicense
{
    private static ?array $state = null;

    /** Buang state dalam memori (dipakai setelah token/kolom berubah). */
    public static function flush(): void
    {
        self::$state = null;
    }

    /** Ambil/segarkan lisensi dari pusat. Aman gagal (kembalikan state lama). */
    public static function refresh(bool $force = false): array
    {
        $coreUrl = CoreApiKey::coreUrl();
        $apiKey = CoreApiKey::resolve();
        $npsn = self::npsn();

        if ($coreUrl === '' || $apiKey === '' || $npsn === '') {
            return self::state();
        }

        // Throttle: hindari panggilan pusat tiap muat halaman. Tombol manual
        // memakai $force = true.
        if (!$force && self::syncedRecently(120)) {
            return self::state();
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $apiKey, 'Accept' => 'application/json'])
                ->post($coreUrl . '/api/license/issue', [
                    'npsn' => $npsn,
                    'app_code' => 'SAE-SMK',
                ]);

            if ($response->status() === 401) {
                CoreApiKey::forgetEnrollment();
            }

            if (!$response->successful()) {
                return self::state();
            }

            $data = $response->json() ?? [];
            $token = (string) ($data['license'] ?? '');
            $publicKey = self::resolvePublicKey((string) ($data['public_key'] ?? ''));

            if ($token === '' || $publicKey === '') {
                return self::state();
            }

            if (!self::verify($token, $publicKey)) {
                return self::state();
            }

            self::persist($token, $publicKey, (int) ($data['grace_days'] ?? 14));
            self::$state = null;

            return self::state();
        } catch (\Throwable) {
            return self::state();
        }
    }

    /**
     * Kunci publik tepercaya untuk verifikasi. Urutan:
     *  1. Kunci tertanam di konfigurasi (.env SAE_CORE_LICENSE_PUBLIC_KEY) — utama.
     *  2. Kunci yang dipatok sebelumnya (first-run).
     *  3. TOFU: patok kunci dari pusat pada pengambilan pertama.
     *  Kunci bercabang dari yang sudah dipatok → ditolak (indikasi MITM).
     */
    public static function resolvePublicKey(string $fromCore): string
    {
        $pinned = self::trustedPublicKey();

        if ($pinned === '') {
            return $fromCore; // first-run: patok belum ada
        }

        // Selalu pakai kunci tepercaya; abaikan yang dikirim pusat.
        return $pinned;
    }

    /** Kunci publik tepercaya yang sudah dipegang klien (env atau tersimpan). */
    public static function trustedPublicKey(): string
    {
        $env = trim((string) config('services.sae_core.license_public_key'));
        if (preg_match('/^[0-9a-f]{64}$/i', $env)) {
            return strtolower($env);
        }

        $stored = (string) (self::row()->license_public_key ?? '');
        if (preg_match('/^[0-9a-f]{64}$/i', $stored)) {
            return strtolower($stored);
        }

        return '';
    }

    /** State lisensi terkini: valid?, exp, entitlements, status, sumber. */
    public static function state(): array
    {
        if (self::$state !== null) {
            return self::$state;
        }

        $row = self::row();
        $token = $row->license_token ?? null;
        $graceDays = (int) ($row->license_grace_days ?? 14);

        $out = [
            'present' => false,
            'verified' => false,
            'entitlements' => [],
            'exp' => null,
            'grace_days' => $graceDays,
            'status' => 'none',
            'days_left' => null,
        ];

        // Verifikasi HANYA dengan kunci tepercaya (tertanam/terpatok), bukan
        // kunci yang kebetulan tersimpan dari respons.
        $publicKey = self::trustedPublicKey();

        if ($token && $publicKey) {
            $out['present'] = true;
            $payload = self::decodePayload($token);
            $out['verified'] = $payload !== null && self::verify($token, $publicKey);

            if (is_array($payload)) {
                $out['entitlements'] = array_values((array) ($payload['entitlements'] ?? []));
                $out['exp'] = $payload['exp'] ?? null;
                $out['status'] = (string) ($payload['status'] ?? 'active');
            }
        }

        if ($out['exp']) {
            try {
                $exp = \Illuminate\Support\Carbon::parse($out['exp']);
                $out['days_left'] = (int) now()->diffInDays($exp, false);
            } catch (\Throwable) {
            }
        }

        return self::$state = $out;
    }

    /**
     * Apakah fitur/modul tertentu berlisensi. Bila belum pernah ada lisensi
     * sama sekali (instalasi baru, pusat belum diatur), anggap berlaku (fail-open
     * terkendali) — penegakan keras baru aktif setelah lisensi diterbitkan.
     */
    public static function allows(string $entitlement): bool
    {
        $state = self::state();

        if (!$state['present']) {
            return true; // belum ada lisensi → jangan kunci instalasi
        }

        // Tanda-tangan tak sah ATAU dibekukan pusat → langsung ditolak.
        if (!$state['verified'] || $state['status'] === 'suspended') {
            return false;
        }

        // Kedaluwarsa → hanya boleh selama masa tenggang.
        if ($state['days_left'] !== null && $state['days_left'] < 0) {
            return self::withinGrace($state);
        }

        return in_array($entitlement, $state['entitlements'], true)
            || in_array('*', $state['entitlements'], true)
            || in_array('core', $state['entitlements'], true);
    }

    /** Apakah lisensi masih dalam masa tenggang (habis tapi belum dikunci). */
    public static function withinGrace(?array $state = null): bool
    {
        $state ??= self::state();

        if ($state['exp'] === null || $state['days_left'] === null) {
            return true;
        }

        return $state['days_left'] >= -1 * (int) $state['grace_days'];
    }

    /** Apakah lisensi perlu perhatian (belum ada / dekat atau lewat masa berlaku). */
    public static function needsAttention(): bool
    {
        $state = self::state();

        // Belum pernah ada lisensi → instalasi baru; jangan dipanggil tiap halaman.
        if (!$state['present']) {
            return false;
        }

        return !$state['verified']
            || $state['status'] !== 'active'
            || ($state['days_left'] !== null && $state['days_left'] <= 30);
    }

    /** Verifikasi tanda-tangan Ed25519 (token "b64url(payload).b64url(sig)"). */
    public static function verify(string $token, string $publicKeyHex): bool
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        $json = self::b64urlDecode($parts[0]);
        $signature = self::b64urlDecode($parts[1]);
        $publicKey = @hex2bin($publicKeyHex);

        if ($json === null || $signature === null || $publicKey === false || strlen($publicKey) !== 32) {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached($signature, $json, $publicKey);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function decodePayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        $json = self::b64urlDecode($parts[0]);
        $data = $json !== null ? json_decode($json, true) : null;

        return is_array($data) ? $data : null;
    }

    private static function persist(string $token, string $publicKeyHex, int $graceDays): void
    {
        self::ensureColumns();

        DB::table('settings')->where('id', 1)->update([
            'license_token' => $token,
            'license_public_key' => $publicKeyHex,
            'license_grace_days' => $graceDays,
            'license_synced_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Apakah lisensi baru disegarkan dalam $seconds detik terakhir. */
    private static function syncedRecently(int $seconds): bool
    {
        $at = self::row()->license_synced_at ?? null;
        if (!$at) {
            return false;
        }

        try {
            return \Illuminate\Support\Carbon::parse($at)->diffInSeconds(now()) < $seconds;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function row(): object
    {
        try {
            if (!Schema::hasTable('settings')) {
                return new \stdClass();
            }

            return DB::table('settings')->where('id', 1)->first() ?? new \stdClass();
        } catch (\Throwable) {
            return new \stdClass();
        }
    }

    private static function ensureColumns(): void
    {
        foreach (['license_token', 'license_public_key', 'license_grace_days', 'license_synced_at'] as $col) {
            if (!Schema::hasColumn('settings', $col)) {
                return;
            }
        }
    }

    private static function npsn(): string
    {
        try {
            if (\App\Support\Table::exists('sekolah')) {
                $npsn = DB::table('sekolah')->value('npsn');
                if ($npsn) {
                    return (string) $npsn;
                }
            }
            if (Schema::hasTable('settings')) {
                $npsn = DB::table('settings')->where('id', 1)->value('npsn');
                if ($npsn) {
                    return (string) $npsn;
                }
            }
        } catch (\Throwable) {
        }

        return '';
    }

    private static function b64urlDecode(string $value): ?string
    {
        $padded = $value . str_repeat('=', (4 - strlen($value) % 4) % 4);
        $decoded = base64_decode(strtr($padded, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
