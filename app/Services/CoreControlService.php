<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Jembatan kontrol sae-smk ⇄ sae-core.
 *
 * Ambil status kontrol sekali per request (di-cache singkat), lalu dipakai di
 * seluruh aplikasi:
 *  - mode akses (full/limited/none) + daftar fitur  → gate menu (RolePermission)
 *  - show_ads                                       → banner iklan donasi
 *  - update_enabled + latest_version                → gate distribusi update
 *
 * Gagal-terbuka (fail-open): bila sae-core tak terjangkau, aplikasi tetap
 * berjalan penuh tanpa iklan. Kontrol pusat opsional, bukan penghalang.
 */
class CoreControlService
{
    public const APP_CODE = 'SAE-SMK';
    private const CACHE_KEY = 'sae_core_control_state';
    private const CACHE_TTL = 300; // detik

    /** Status penuh bawaan (fail-open). */
    private const DEFAULT = [
        'reachable' => false,
        'mode' => 'full',
        'features' => [],
        'show_ads' => false,
        'is_donor' => false,
        'update_enabled' => true,
        'latest_version' => null,
        'update_available' => false,
    ];

    /**
     * Peta fitur sae-core → awalan permission_key menu sae-smk.
     * Dipakai saat mode = limited: menu yang tidak tercakup fitur terpilih ditutup.
     */
    public const FEATURE_MENUS = [
        'Data Induk' => [
            'menu_kompetensi_keahlian', 'menu_rombel', 'menu_pembelajaran',
            'menu_jadwal_kbm', 'menu_kalender_pendidikan', 'menu_peserta_didik_aktif',
            'menu_peserta_didik_tidak_aktif', 'menu_guru_aktif', 'menu_guru_tidak_aktif',
            'menu_tendik_aktif', 'menu_tendik_tidak_aktif', 'menu_gtk_non_asn',
            'menu_buku_induk', 'menu_berkas_peserta_didik', 'menu_identitas_siswa',
            'menu_dapodik',
        ],
        'Absensi' => [
            'menu_presensi_peserta_didik', 'menu_presensi_mengajar',
            'menu_agenda_kbm', 'menu_rfid', 'menu_riwayat_rfid',
        ],
        'Tata Tertib' => [
            'menu_poin', 'menu_kesiswaan', 'menu_e_izin', 'menu_agenda',
        ],
        'Inventaris' => [
            'menu_inventaris', 'menu_sarpras',
        ],
        'Kartu Pelajar' => [
            'menu_kartu_pelajar',
        ],
        'Laporan' => [
            'menu_aktivitas_database', 'menu_laporan_tendik', 'menu_aktivitas_tendik',
        ],
        'Sinkronisasi Dapodik' => [
            'menu_dapodik',
        ],
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
            // cache tak tersedia → lanjut cek langsung
        }

        return self::$state = self::fetch();
    }

    /** Ambil status dari sae-core (dipakai juga untuk ping sekaligus). */
    public static function fetch(): array
    {
        $coreUrl = rtrim((string) (config('services.sae_core.url') ?: env('SAE_CORE_URL', '')), '/');
        $apiKey = (string) config('services.sae_core.api_key');
        $npsn = self::npsn();
        $version = UpdateService::CURRENT_VERSION;

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
                    'app_version' => $version,
                ]);

            if (!$response->successful()) {
                return self::DEFAULT;
            }

            $data = $response->json() ?? [];
            $access = $data['access'] ?? [];
            $donation = $data['donation'] ?? [];

            $state = [
                'reachable' => true,
                'mode' => in_array($access['mode'] ?? 'full', ['full', 'limited', 'none'], true)
                    ? $access['mode']
                    : 'full',
                'features' => is_array($access['features'] ?? null) ? $access['features'] : [],
                'show_ads' => (bool) ($donation['show_ads'] ?? false),
                'is_donor' => (bool) ($donation['is_donor'] ?? false),
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

    /**
     * Apakah menu boleh diakses menurut kontrol pusat.
     * Fail-open: true kecuali kontrol menyatakan tertutup/terbatas.
     */
    public static function moduleAllowed(string $permissionKey): bool
    {
        $state = self::state();

        if (!$state['reachable'] || $state['mode'] === 'full') {
            return true;
        }

        if ($state['mode'] === 'none') {
            return false;
        }

        // mode = limited: hanya menu yang tercakup fitur terpilih.
        $allowedPrefixes = [];
        foreach ((array) $state['features'] as $feature) {
            foreach ((self::FEATURE_MENUS[$feature] ?? []) as $prefix) {
                $allowedPrefixes[] = $prefix;
            }
        }

        if (empty($allowedPrefixes)) {
            return false;
        }

        foreach ($allowedPrefixes as $prefix) {
            if ($permissionKey === $prefix || str_starts_with($permissionKey, $prefix . '_')
                || str_starts_with($permissionKey, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Haruskah banner iklan donasi tampil? */
    public static function showAds(): bool
    {
        return (bool) self::state()['show_ads'];
    }

    /** Apakah distribusi update diizinkan pusat? */
    public static function updatesAllowed(): bool
    {
        return (bool) self::state()['update_enabled'];
    }

    private static function npsn(): string
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
