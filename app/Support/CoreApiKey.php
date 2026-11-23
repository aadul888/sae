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
    private static bool $enrollTried = false;

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

    /**
     * URL pusat sae-core. Urutan: pengaturan per-instalasi (DB) → .env → bawaan.
     *
     * Alamat pusat berbeda antar server sekolah, jadi tidak ditanam di kode:
     * admin dapat mengubahnya dari halaman Pengaturan instalasi masing-masing.
     */
    public static function coreUrl(): string
    {
        $fromDb = self::storedCoreUrl();
        if ($fromDb !== null) {
            return $fromDb;
        }

        return rtrim((string) (config('services.sae_core.url') ?: 'https://appsae.com'), '/');
    }

    /** URL pusat tersimpan per instalasi (settings.core_url), null bila kosong. */
    public static function storedCoreUrl(): ?string
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'core_url')) {
                return null;
            }

            $url = DB::table('settings')->where('id', 1)->value('core_url');

            return is_string($url) && trim($url) !== '' ? rtrim(trim($url), '/') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Simpan URL pusat per instalasi (dipanggil dari halaman Pengaturan). */
    public static function saveCoreUrl(?string $url): void
    {
        if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'core_url')) {
            return;
        }

        DB::table('settings')->where('id', 1)->update([
            'core_url' => ($url !== null && trim($url) !== '') ? rtrim(trim($url), '/') : null,
            'updated_at' => now(),
        ]);

        // Pusat baru → kunci lama tak dikenal di sana. Bersihkan penanda enroll
        // agar instalasi mendaftar ulang otomatis ke pusat yang baru.
        self::forgetEnrollment();
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
            // Kunci sudah ada tapi belum pernah sukses enroll (mis. core sempat
            // mati saat pembuatan) → coba daftarkan lagi, maksimal sekali per 10
            // menit agar page-load tidak tertahan saat pusat tak terjangkau.
            if (self::storedEnrolledAt() === null) {
                self::enrollThrottled($existing);
            }

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

        // Daftarkan ke pusat (best-effort). Kegagalan tidak menghalangi operasi;
        // percobaan ulang terjadi di pemanggilan berikutnya bila belum terdaftar.
        self::$enrollTried = true;
        self::enroll($key);

        return $key;
    }

    private static function storedEnrolledAt(): ?string
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'core_enrolled_at')) {
                return null;
            }

            $val = DB::table('settings')->where('id', 1)->value('core_enrolled_at');

            return is_string($val) && $val !== '' ? $val : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Coba enroll ulang, maksimal sekali per 10 menit (hemat saat pusat mati). */
    private static function enrollThrottled(string $key): void
    {
        try {
            if (\Illuminate\Support\Facades\Cache::get('sae_core_enroll_tried')) {
                return;
            }
            \Illuminate\Support\Facades\Cache::put('sae_core_enroll_tried', true, 600);
        } catch (\Throwable) {
            // Cache tak tersedia → batasi lewat flag proses.
            if (self::$enrollTried) {
                return;
            }
            self::$enrollTried = true;
        }

        self::enroll($key);
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

    /**
     * Bersihkan penanda "sudah enroll" agar kunci lokal didaftarkan ulang ke
     * pusat pada panggilan berikutnya. Dipakai saat pusat menolak kunci (401) —
     * mis. hash tersimpan direset admin — sehingga klien memulihkan diri.
     */
    public static function forgetEnrollment(): void
    {
        try {
            if (Schema::hasColumn('settings', 'core_enrolled_at')) {
                DB::table('settings')->where('id', 1)->update(['core_enrolled_at' => null]);
            }
        } catch (\Throwable) {
            // abaikan
        }

        // Buang juga throttle enroll, agar pemulihan (mis. setelah admin pusat
        // mereset kunci) terjadi pada panggilan berikutnya, bukan menunggu 10 menit.
        try {
            \Illuminate\Support\Facades\Cache::forget('sae_core_enroll_tried');
        } catch (\Throwable) {
            // abaikan
        }
        self::$enrollTried = false;
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
