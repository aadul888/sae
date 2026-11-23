<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Jembatan sae-smk ⇄ sae-core.
 *
 * Ambil status dari pusat sekali per request (di-cache singkat), lalu dipakai:
 *  - show_ads / is_donor        → banner iklan donasi
 *  - update_enabled             → gate distribusi update
 *
 * Modul kontrol akses (peran/menu) sudah dihapus. Koneksi pusat tetap ada
 * sebagai status kesehatan + sumber donasi/iklan & pembaruan.
 *
 * Gagal-terbuka (fail-open): bila sae-core tak terjangkau, aplikasi tetap
 * berjalan normal.
 */
class CoreControlService
{
    public const APP_CODE = 'SAE-SMK';
    private const CACHE_KEY = 'sae_core_control_state';
    private const CACHE_TTL = 300; // detik

    /** Status bawaan (fail-open). */
    private const DEFAULT = [
        'reachable' => false,
        'show_ads' => false,
        'is_donor' => false,
        'saweria_username' => '',
        'update_enabled' => true,
        'latest_version' => null,
        'update_available' => false,
    ];

    private static ?array $state = null;

    /** Status kontrol saat ini (fail-open bila gagal). */
    public static function state(): array
    {
        if (self::$state !== null) {
            return self::$state;
        }

        try {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached)) {
                return self::$state = $cached;
            }
        } catch (\Throwable) {
            // cache tak tersedia → ambil langsung
        }

        return self::$state = self::fetch();
    }

    /** Ambil status dari sae-core (sekaligus ping). */
    public static function fetch(): array
    {
        $coreUrl = \App\Support\CoreApiKey::coreUrl();
        $apiKey = \App\Support\CoreApiKey::resolve();
        $npsn = self::npsn();

        if ($coreUrl === '' || $apiKey === '' || $npsn === '') {
            return self::DEFAULT;
        }

        try {
            $response = Http::timeout(6)
                ->withHeaders(['X-API-Key' => $apiKey, 'Accept' => 'application/json'])
                ->post($coreUrl . '/api/sync/checkin', [
                    'npsn' => $npsn,
                    'nama_sekolah' => self::namaSekolah(),
                    'app_code' => self::APP_CODE,
                    'app_version' => UpdateService::CURRENT_VERSION,
                ]);

            if (!$response->successful()) {
                // 401 = kunci instalasi tak dikenal pusat. Bersihkan penanda
                // enroll agar panggilan berikutnya mendaftar ulang otomatis.
                if ($response->status() === 401) {
                    \App\Support\CoreApiKey::forgetEnrollment();
                }

                return self::DEFAULT;
            }

            $data = $response->json() ?? [];
            $donation = $data['donation'] ?? [];

            $state = [
                'reachable' => true,
                'show_ads' => (bool) ($donation['show_ads'] ?? false),
                'is_donor' => (bool) ($donation['is_donor'] ?? false),
                'saweria_username' => (string) ($donation['saweria_username'] ?? ''),
                'update_enabled' => (bool) ($data['update_enabled'] ?? true),
                'latest_version' => $data['latest_version'] ?? null,
                'update_available' => (bool) ($data['update_available'] ?? false),
            ];

            try {
                Cache::put(self::CACHE_KEY, $state, self::CACHE_TTL);
            } catch (\Throwable) {
                // abaikan
            }

            return $state;
        } catch (\Throwable) {
            return self::DEFAULT;
        }
    }

    /** Ping ke sae-core + segarkan cache. Aman dipanggil terjadwal. */
    public static function refresh(): array
    {
        self::$state = null;
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
        }

        return self::state();
    }

    /** Haruskah banner iklan donasi tampil? */
    public static function showAds(): bool
    {
        return (bool) self::state()['show_ads'];
    }

    /** Username Saweria pusat (untuk tautan donasi). */
    public static function saweriaUsername(): string
    {
        return (string) (self::state()['saweria_username'] ?? '');
    }

    /** Apakah distribusi update diizinkan pusat? */
    public static function updatesAllowed(): bool
    {
        return (bool) self::state()['update_enabled'];
    }

    /**
     * Diagnostik koneksi pusat: kondisi nyata yang diterima klien saat ini.
     * Dipakai halaman Update → Diagnostik.
     */
    public static function diagnostics(): array
    {
        // Paksa ambil ulang dari pusat agar hasil mencerminkan kondisi terkini.
        self::$state = null;
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable) {
        }

        $state = self::state();

        $enrolledAt = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('settings', 'core_enrolled_at')) {
                $enrolledAt = DB::table('settings')->where('id', 1)->value('core_enrolled_at');
            }
        } catch (\Throwable) {
        }

        return [
            'core_url' => \App\Support\CoreApiKey::coreUrl(),
            'npsn' => self::npsn(),
            'app_code' => self::APP_CODE,
            'reachable' => (bool) ($state['reachable'] ?? false),
            'show_ads' => (bool) ($state['show_ads'] ?? false),
            'is_donor' => (bool) ($state['is_donor'] ?? false),
            'saweria_username' => (string) ($state['saweria_username'] ?? ''),
            'update_enabled' => (bool) ($state['update_enabled'] ?? true),
            'install_key_set' => \App\Support\CoreApiKey::stored() !== null,
            'enrolled_at' => $enrolledAt,
        ];
    }

    /** NPSN sekolah ini (untuk ditampilkan di iklan donasi). */
    public static function npsn(): string
    {
        try {
            if (\App\Support\Table::exists('sekolah')) {
                $row = DB::table('sekolah')->first();
                if ($row && !empty($row->npsn)) {
                    return (string) $row->npsn;
                }
            }
            if (\App\Support\Table::exists('settings')) {
                $row = DB::table('settings')->where('id', 1)->first();
                if ($row && !empty($row->npsn)) {
                    return (string) $row->npsn;
                }
            }
        } catch (\Throwable) {
        }

        return '';
    }

    private static function namaSekolah(): string
    {
        try {
            if (\App\Support\Table::exists('sekolah')) {
                $row = DB::table('sekolah')->first();
                if ($row && !empty($row->nama)) {
                    return (string) $row->nama;
                }
            }
            if (\App\Support\Table::exists('settings')) {
                $row = DB::table('settings')->where('id', 1)->first();
                if ($row && !empty($row->app_name)) {
                    return (string) $row->app_name;
                }
            }
        } catch (\Throwable) {
        }

        return 'SAE Instance';
    }
}
