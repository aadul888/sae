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
        'roles' => [],
        'dapodik_enabled' => true,
        'show_ads' => false,
        'is_donor' => false,
        'update_enabled' => true,
        'latest_version' => null,
        'update_available' => false,
    ];

    /**
     * Katalog fitur yang dilaporkan ke sae-core secara otomatis.
     *
     * Diambil dari modul menu sistem (RolePermission::getAllSystemModules) dan
     * dikelompokkan per grup sidebar. sae-core memakai ini untuk menampilkan
     * seluruh fitur yang bisa dibuka-tutup per sekolah — termasuk fitur baru
     * tanpa perlu diperbarui manual di pusat.
     *
     * @return array<int,array{name:string,items:array<int,array{key:string,label:string}>}>
     */
    public static function catalog(): array
    {
        try {
            $modules = \App\Models\RolePermission::getAllSystemModules();
        } catch (\Throwable) {
            return [];
        }

        $groups = [];
        foreach ($modules as $key => $meta) {
            $group = (string) ($meta['group'] ?? 'Lainnya');
            $groups[$group][] = [
                'key' => (string) $key,
                'label' => (string) ($meta['label'] ?? $key),
            ];
        }

        $out = [];
        foreach ($groups as $name => $items) {
            $out[] = ['name' => $name, 'items' => $items];
        }

        return $out;
    }

    /**
     * Peta fitur sae-core → awalan permission_key menu sae-smk.
     * Cadangan lama; gating modern memakai daftar permission key langsung
     * dari katalog yang dilaporkan klien.
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
        $coreUrl = \App\Support\CoreApiKey::coreUrl();
        $apiKey = \App\Support\CoreApiKey::resolve();
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
                    'features_catalog' => self::catalog(),
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
                'roles' => is_array($access['roles'] ?? null) ? $access['roles'] : [],
                'dapodik_enabled' => (bool) ($access['dapodik_enabled'] ?? true),
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
     * Apakah menu boleh diakses menurut kontrol pusat (mode + peran + dapodik).
     *
     * - mode none    → tutup total.
     * - menu_dapodik → ikut saklar dapodik_enabled.
     * - mode limited → bila peran user ada di daftar `roles` (yang dimatikan),
     *                  tutup. Peran yang tak dikenal / menu baru tetap aktif.
     * Fail-open bila sae-core tak terjangkau atau mode full.
     */
    public static function moduleAllowed(string $permissionKey, ?string $role = null): bool
    {
        $state = self::state();

        if (!$state['reachable'] || $state['mode'] === 'full') {
            return true;
        }

        if ($state['mode'] === 'none') {
            return false;
        }

        // Menu Tarik Data Dapodik dikontrol terpisah.
        if ($permissionKey === 'menu_dapodik') {
            return (bool) ($state['dapodik_enabled'] ?? true);
        }

        // Mode limited: cek peran yang dimatikan.
        $role = self::normalizeRole($role);
        if ($role !== null) {
            foreach ((array) $state['roles'] as $blockedRole) {
                if (self::roleMatches($role, (string) $blockedRole)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Apakah peran pengguna diizinkan masuk (dipakai saat login). Mengikuti
     * mode 'none' dan daftar peran yang dimatikan. Fail-open.
     */
    public static function roleAllowed(?string $role): bool
    {
        $state = self::state();

        if (!$state['reachable'] || $state['mode'] === 'full') {
            return true;
        }

        if ($state['mode'] === 'none') {
            return false;
        }

        $role = self::normalizeRole($role);
        if ($role !== null) {
            foreach ((array) $state['roles'] as $blockedRole) {
                if (self::roleMatches($role, (string) $blockedRole)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** Petakan variasi label peran ke kode internal. */
    private static function normalizeRole(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        return match (strtolower(trim($role))) {
            'admin', 'administrator', 'operator', 'superadmin' => 'admin',
            'guru', 'ptk' => 'guru',
            'tendik', 'tenaga kependidikan', 'tata usaha', 'pegawai' => 'tendik',
            'tugas_tambahan', 'tugas tambahan' => 'tugas_tambahan',
            'peserta_didik', 'peserta didik', 'siswa', 'murid' => 'peserta_didik',
            'orang_tua', 'orang tua', 'wali murid', 'wali_murid' => 'orang_tua',
            default => strtolower(trim($role)),
        };
    }

    /** Apakah role termasuk kelompok yang dimatikan (GTK = guru+tendik+tugas_tambahan). */
    private static function roleMatches(string $role, string $blockedRole): bool
    {
        if ($role === $blockedRole) {
            return true;
        }

        // Kompatibilitas bila pusat mengirim grup "gtk".
        if ($blockedRole === 'gtk') {
            return in_array($role, ['guru', 'tendik', 'tugas_tambahan'], true);
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
