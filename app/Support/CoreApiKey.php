<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci privat jembatan ke sae-core, dibuat otomatis per instalasi.
 *
 * Instalasi SAE segar tidak punya .env. Saat pertama butuh, sistem membuat kunci
 * acak privat (disimpan di tabel settings), lalu mendaftarkannya (enroll) ke
 * sae-core sekali. Setelah itu kunci ini dipakai untuk seluruh panggilan.
 *
 * Env opsional (SAE_CORE_URL / SAE_CORE_API_KEY) tetap dihormati bila diisi
 * manual, tapi bukan keharusan — dan TIDAK ADA nilai rahasia yang ditanam di
 * repositori publik.
 */
final class CoreApiKey
{
    private static ?string $cached = null;

    /** Kunci efektif: dari .env bila diisi, else dari settings (buat bila belum ada). */
    public static function resolve(): string
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        // .env manual (opsional) — untuk pengujian/pengelolaan manual.
        $envKey = (string) config('services.sae_core.api_key');
        if ($envKey !== '') {
            return self::$cached = $envKey;
        }

        return self::$cached = self::local();
    }

    /** URL pusat sae-core (default aman, bisa ditimpa .env). */
    public static function coreUrl(): string
    {
        return rtrim((string) (config('services.sae_core.url') ?: 'https://appsae.com'), '/');
    }

    /** Kunci lokal tersimpan (tanpa efek samping). */
    public static function stored(): ?string
    {
        if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'core_install_key')) {
            return null;
        }

        $key = DB::table('settings')->where('id', 1)->value('core_install_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /** Ambil kunci lokal; buat + enroll otomatis bila belum ada. */
    public static function local(): string
    {
        $existing = self::stored();
        if ($existing !== null) {
            return $existing;
        }

        if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'core_install_key')) {
            // Skema belum siap (mis. saat instalasi): pakai kunci sesi agar aplikasi
            // tetap jalan. Tidak disimpan; akan dibuat permanen setelah migrasi.
            return self::$cached ?: ('SAE_' . bin2hex(random_bytes(16)));
        }

        $key = 'SAE_' . bin2hex(random_bytes(16));

        try {
            DB::table('settings')->updateOrInsert(
                ['id' => 1],
                ['core_install_key' => $key, 'updated_at' => now()]
            );
        } catch (\Throwable) {
            return $key;
        }

        self::$cached = $key;

        // Daftarkan ke pusat (best-effort). Kegagalan tidak menghalangi operasi.
        self::enroll($key);

        return $key;
    }

    /** Kirim hash kunci ke sae-core. First-wins di sisi pusat. */
    public static function enroll(string $key): bool
    {
        $coreUrl = self::coreUrl();
        if ($coreUrl === '') {
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($coreUrl . '/api/enroll', [
                    'npsn' => self::npsn(),
                    'nama_sekolah' => self::namaSekolah(),
                    'app_code' => 'SAE-SMK',
                    'key_hash' => hash('sha256', $key),
                ]);

            if ($response->successful()) {
                if (Schema::hasColumn('settings', 'core_enrolled_at')) {
                    DB::table('settings')->where('id', 1)->update(['core_enrolled_at' => now()]);
                }
                return true;
            }

            // 409 = sudah terdaftar. Anggap terdaftar.
            return $response->status() === 409;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function npsn(): string
    {
        try {
            if (Table::exists('sekolah')) {
                $row = DB::table('sekolah')->first();
                if ($row && !empty($row->npsn)) {
                    return (string) $row->npsn;
                }
            }
            if (Schema::hasTable('settings')) {
                $row = DB::table('settings')->where('id', 1)->first();
                if ($row && !empty($row->npsn)) {
                    return (string) $row->npsn;
                }
            }
        } catch (\Throwable) {
        }

        return '00000000';
    }

    private static function namaSekolah(): string
    {
        try {
            if (Table::exists('sekolah')) {
                $row = DB::table('sekolah')->first();
                if ($row && !empty($row->nama)) {
                    return (string) $row->nama;
                }
            }
            if (Schema::hasTable('settings')) {
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
