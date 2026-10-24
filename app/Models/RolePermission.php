<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Support\PermissionDictionary;
use App\Support\DutyPermissionDefaults;
use App\Support\DutyPermissionManager;
use App\Support\ModuleDiscovery;
use App\Support\WaliKelasAccessHelper;
use App\Support\RolePermissionRestrictions;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'permission_key',
        'is_allowed',
        'can_create',
        'can_read',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'can_create' => 'boolean',
        'can_read' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    /**
     * In-memory static cache per HTTP request untuk optimasi performa RBAC
     */
    protected static array $runtimeRolePermissionsCache = [];
    protected static array $runtimeDutyPermissionsCache = [];
    protected static ?bool $runtimeAdminExistsCache = null;
    protected static ?bool $runtimeHasTableRolePermissions = null;
    protected static ?bool $runtimeHasTableDuties = null;

    /**
     * Bersihkan static runtime cache (berguna saat pengujian atau setelah mutasi hak akses)
     */
    public static function clearRuntimeCache(): void
    {
        self::$runtimeRolePermissionsCache = [];
        self::$runtimeDutyPermissionsCache = [];
        self::$runtimeAdminExistsCache = null;
        self::$runtimeHasTableRolePermissions = null;
        self::$runtimeHasTableDuties = null;
    }

    /**
     * Cek apakah nama kelompok modul merupakan kelompok tugas tambahan / bidang
     */
    public static function isDutyGroup(string $groupName): bool
    {
        if ($groupName === 'Tendik: Kinerja & Aktivitas' || $groupName === 'Kinerja & Aktivitas Tendik') {
            return false;
        }
        return $groupName === 'Wali Kelas' || str_starts_with($groupName, 'Tendik:');
    }

    /**
     * Cek apakah modul merupakan bagian dari bidang / tugas tambahan
     */
    public static function isDutyModule(string $key, ?string $groupName = null): bool
    {
        if (in_array($key, [
            'menu_target_capaian',
            'menu_aktivitas_tendik',
            'menu_laporan_tendik',
        ], true)) {
            return false;
        }

        if ($groupName && self::isDutyGroup($groupName)) {
            return true;
        }
        return self::isDutySpecificPermission($key);
    }

    /**
     * Dapatkan semua key modul tugas tambahan
     */
    public static function getDutyModuleKeys(): array
    {
        $all = self::getAvailablePermissions();
        $keys = [];
        foreach ($all as $group => $items) {
            foreach ($items as $k => $conf) {
                if (self::isDutyModule($k, $group)) {
                    $keys[] = $k;
                }
            }
        }
        return array_unique($keys);
    }

    /**
     * Modul yang tidak relevan untuk suatu peran (beda pengelolaan); disembunyikan dan dibersihkan saat sinkronisasi.
     */
    public const FORBIDDEN_MODULES = RolePermissionRestrictions::FORBIDDEN_MODULES;

    public static function isForbiddenForRole(string $role, string $permissionKey): bool
    {
        if ($permissionKey === 'menu_rapor') {
            return true;
        }

        if ($role === 'tugas_tambahan') {
            return !self::isDutyModule($permissionKey);
        }

        // Modul tugas tambahan tidak muncul di tab peran dasar (admin, guru, tendik, peserta_didik, orang_tua)
        if (self::isDutyModule($permissionKey)) {
            return true;
        }

        // Administrator memiliki akses ke seluruh modul sistem tanpa batasan
        if ($role === 'admin') {
            return false;
        }

        // Kelompok Manajemen Data, Master Data, Sistem & Pengaturan, dan Fitur Operasional tidak boleh ada di peran non-admin
        if (in_array($role, ['guru', 'tendik', 'peserta_didik', 'orang_tua'], true)) {
            $conf = self::getPermissionConfig($permissionKey);
            $group = $conf['group'] ?? '';
            if (in_array($group, ['Manajemen Data', 'Master Data', 'Sistem & Pengaturan', 'Fitur Operasional'], true)) {
                return true;
            }
            if (in_array($permissionKey, ['menu_dapodik', 'menu_buku_tamu', 'menu_agenda', 'menu_kelulusan', 'menu_poin', 'menu_rfid', 'menu_formulir', 'menu_e_izin', 'menu_pengumuman'], true)) {
                return true;
            }
        }

        return in_array($permissionKey, self::FORBIDDEN_MODULES[$role] ?? [], true);
    }

    /**
     * Definisi dasar modul & fitur bawaan sistem yang dikelompokkan berdasarkan cluster modul.
     */
    public static function getBasePermissions(): array
    {
        return PermissionDictionary::base();
    }

    /**
     * Compatibility source retained while the dictionary is migrated incrementally.
     */
    public static function getLegacyBasePermissions(): array
    {
        return PermissionDictionary::base();
    }

    /**
     * Otomatis mendeteksi modul menu & fitur baru yang dibuat di sistem
     * dengan memindai berkas route, controller, blade view, dan rekaman database.
     */
    public static function discoverSystemModules(array $baseConfigs = []): array
    {
        return ModuleDiscovery::discoverSystemModules($baseConfigs);
    }

    /**
     * Resolusi cerdas untuk kelompok dan peran default modul baru berdasarkan nama key
     */
    public static function resolveModuleGroupAndRoles(string $key): array
    {
        return ModuleDiscovery::resolveModuleGroupAndRoles($key);
    }

    /**
     * Otomatis mendaftarkan seluruh modul baru yang ditemukan di sistem ke database.
     */
    public static function autoSyncNewDiscoveredModules(): int
    {
        return ModuleDiscovery::autoSyncNewDiscoveredModules();
    }

    /**
     * Otomatis mendaftarkan modul baru secara realtime saat pertama kali dipanggil / diakses.
     */
    public static function autoRegisterNewPermission(string $permissionKey): void
    {
        ModuleDiscovery::autoRegisterNewPermission($permissionKey);
    }

    /**
     * Dapatkan seluruh permission sistem (Base + Otomatis Terdeteksi)
     */
    public static function getAvailablePermissions(): array
    {
        $configs = self::getBasePermissions();
        $discovered = self::discoverSystemModules($configs);

        foreach ($discovered as $key => $meta) {
            $grp = $meta['group'] ?? 'Layanan Digital';
            $configs[$grp][$key] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'roles' => $meta['roles'] ?? ['admin'],
            ];
        }

        return $configs;
    }

    /**
     * Dapatkan semua modul sistem yang valid (khusus menu_)
     */
    public static function getAllSystemModules(): array
    {
        $all = self::getAvailablePermissions();
        $modules = [];
        foreach ($all as $group => $items) {
            foreach ($items as $key => $conf) {
                if (str_starts_with($key, 'menu_')) {
                    $modules[$key] = [
                        'key' => $key,
                        'label' => $conf['label'],
                        'icon' => $conf['icon'],
                        'group' => $group,
                    ];
                }
            }
        }
        return $modules;
    }

    /**
     * Dapatkan definisi konfigurasi untuk permission tertentu (Mendukung fallback dinamis masa depan)
     */
    public static function getPermissionConfig(string $key): ?array
    {
        $all = self::getAvailablePermissions();
        foreach ($all as $group => $items) {
            if (isset($items[$key])) {
                $item = $items[$key];
                $item['group'] = $group;
                return $item;
            }
        }

        // Fallback dinamis jika modul baru belum terdaftar secara statis
        if (!empty($key)) {
            $isFitur = str_starts_with($key, 'fitur_');
            $cleanName = str_replace(['menu_', 'fitur_', '_'], ['', '', ' '], $key);
            return [
                'label' => ucwords($cleanName),
                'icon' => $isFitur ? 'fa-screwdriver-wrench' : 'fa-cube',
                'group' => $isFitur ? 'Fitur Operasional' : 'Layanan Digital',
                'roles' => ['admin'],
            ];
        }

        return null;
    }

    /**
     * Cek apakah pengguna saat ini adalah Peserta Didik yang ditunjuk sebagai Koordinator Kelas
     */
    public static function isKoordinator(mixed $userOrRole = null): bool
    {
        return WaliKelasAccessHelper::isKoordinator($userOrRole);
    }

    /**
     * Cek apakah pengguna saat ini adalah Wali Kelas atau Administrator (atau Koordinator Kelas)
     */
    public static function isWaliKelasOrAdmin(mixed $userOrRole = null, ?string $rombelNameOrId = null): bool
    {
        return WaliKelasAccessHelper::isWaliKelasOrAdmin($userOrRole, $rombelNameOrId);
    }

    /**
     * Dapatkan nama rombel yang diampu oleh Wali Kelas atau Koordinator Kelas saat ini
     */
    public static function getWaliKelasRombel(mixed $userOrRole = null): ?string
    {
        return WaliKelasAccessHelper::getWaliKelasRombel($userOrRole);
    }

    /**
     * Dapatkan data rombel lengkap yang diampu oleh Wali Kelas atau Koordinator Kelas saat ini
     */
    public static function getWaliKelasRombelInfo(mixed $userOrRole = null): ?object
    {
        return WaliKelasAccessHelper::getWaliKelasRombelInfo($userOrRole);
    }

    /**
     * Cek apakah suatu modul diizinkan untuk peran tertentu di tabel role_permissions (tanpa tugas tambahan)
     */
    public static function canRoleAccess(string $role, string $permissionKey, string $action = 'read'): bool
    {
        if ($role === 'admin') return true;
        if (self::$runtimeHasTableRolePermissions === null) {
            self::$runtimeHasTableRolePermissions = Schema::hasTable('role_permissions');
        }
        if (!self::$runtimeHasTableRolePermissions) return false;

        if (!isset(self::$runtimeRolePermissionsCache[$role])) {
            self::$runtimeRolePermissionsCache[$role] = self::where('role', $role)->get()->keyBy('permission_key');
        }
        $row = self::$runtimeRolePermissionsCache[$role]->get($permissionKey);
        if (!$row && in_array($permissionKey, ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_persuratan');
        }
        if (!$row && in_array($permissionKey, ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_kesiswaan');
        }
        if (!$row && in_array($permissionKey, ['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_wali_kelas');
        }
        if (!$row && $permissionKey === 'menu_kesiswaan') {
            foreach (['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row && $permissionKey === 'menu_persuratan') {
            foreach (['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row && $permissionKey === 'menu_wali_kelas') {
            foreach (['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row || !$row->is_allowed || !$row->can_read) {
            return false;
        }

        $action = strtolower(trim($action));
        $actionCol = 'can_' . $action;
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            $actionCol = 'can_read';
        }

        if ($actionCol === 'can_read') {
            return (bool) $row->can_read;
        }

        return (bool) ($row->can_read && $row->{$actionCol});
    }

    /**
     * Cek apakah permission key merupakan modul spesifik penugasan tugas tambahan
     */
    public static function isDutySpecificPermission(string $key): bool
    {
        $dutyPrefixes = [
            'menu_wali_kelas',
            'menu_kepala_tas',
            'menu_persuratan',
            'menu_surat_masuk',
            'menu_surat_keluar',
            'menu_pengaturan_persuratan',
            'menu_kesiswaan',
            'menu_kepegawaian',
            'menu_keuangan',
            'menu_sarpras',
            'menu_inventaris',
            'menu_laboran',
            'menu_perpustakaan',
            'menu_teknisi',
            'menu_keamanan',
            'menu_penjaga',
            'menu_piket',
            'menu_berkas_peserta_didik',
            'menu_perubahan_data',
            'menu_e_izin',
        ];

        foreach ($dutyPrefixes as $prefix) {
            if ($key === $prefix || str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dapatkan status izin (termasuk CRUD) untuk Wali Kelas pada suatu permission key.
     * Digunakan oleh Wali Kelas dan Koordinator Kelas untuk memperoleh hak akses Wali Kelas secara otomatis.
     */
    public static function getWaliKelasPermission(string $permissionKey, string $action = 'read'): bool
    {
        $actionCol = 'can_' . strtolower(trim($action));
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            $actionCol = 'can_read';
        }

        // 1. Cek di role_permissions untuk peran 'tugas_tambahan'
        if (!isset(self::$runtimeRolePermissionsCache['tugas_tambahan'])) {
            self::$runtimeRolePermissionsCache['tugas_tambahan'] = self::where('role', 'tugas_tambahan')->get()->keyBy('permission_key');
        }
        $dutyRow = self::$runtimeRolePermissionsCache['tugas_tambahan']->get($permissionKey);
        if (!$dutyRow && in_array($permissionKey, ['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda'], true)) {
            $dutyRow = self::$runtimeRolePermissionsCache['tugas_tambahan']->get('menu_wali_kelas');
        }
        if ($dutyRow && $dutyRow->is_allowed && $dutyRow->can_read) {
            return $actionCol === 'can_read' ? true : (bool) ($dutyRow->{$actionCol} ?? true);
        }

        // 2. Cek di role_permissions untuk peran 'admin'
        if (isset(self::$runtimeRolePermissionsCache['admin'])) {
            $adminRow = self::$runtimeRolePermissionsCache['admin']->get($permissionKey);
            if ($adminRow && $adminRow->is_allowed && $adminRow->can_read) {
                return $actionCol === 'can_read' ? true : (bool) $adminRow->{$actionCol};
            }
        }

        // 3. Fallback default untuk modul wali kelas & peserta didik binaan: read, create, update, delete diizinkan
        if (str_starts_with($permissionKey, 'menu_wali_kelas') || in_array($permissionKey, ['menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_berkas_peserta_didik'], true)) {
            return in_array($actionCol, ['can_read', 'can_create', 'can_update', 'can_delete'], true);
        }

        return false;
    }

    /**
     * Cek apakah user atau role memiliki izin terhadap suatu permission_key
     */
    public static function canAccess($userOrRole, string $permissionKey, string $action = 'read'): bool
    {
        // Jika tabel belum ada (sebelum migrasi selesai), fallback allow default admin
        if (self::$runtimeHasTableRolePermissions === null) {
            self::$runtimeHasTableRolePermissions = Schema::hasTable('role_permissions');
        }
        if (!self::$runtimeHasTableRolePermissions) {
            $r = is_string($userOrRole) ? $userOrRole : (is_array($userOrRole) ? ($userOrRole['role'] ?? '') : ($userOrRole->role ?? ''));
            return $r === 'admin';
        }

        $user = $userOrRole ?: session('user');
        $role = is_string($user) ? $user : (is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik'));

        $action = strtolower(trim($action));
        $actionCol = 'can_' . $action;
        if (!in_array($actionCol, ['can_create', 'can_read', 'can_update', 'can_delete'])) {
            $actionCol = 'can_read';
        }

        // Khusus Administrator: Superadmin memiliki wewenang penuh tanpa batasan terhadap seluruh modul dan aksi CRUD
        if ($role === 'admin') {
            return true;
        }

        // Cache keberadaan tabel tugas tambahan
        if (self::$runtimeHasTableDuties === null) {
            self::$runtimeHasTableDuties = Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan');
        }
        $hasDutyTables = self::$runtimeHasTableDuties;

        // Khusus Wali Kelas (Guru) dan Koordinator Kelas (Peserta Didik):
        // Menu + CRUD otomatis mewarisi hak akses Wali Kelas
        if (($role === 'guru' && self::isWaliKelasOrAdmin($user)) || ($role === 'peserta_didik' && self::isKoordinator($user))) {
            if (str_starts_with($permissionKey, 'menu_wali_kelas') || in_array($permissionKey, ['menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_berkas_peserta_didik'], true)) {
                return self::getWaliKelasPermission($permissionKey, $action);
            }
        }

        // 1. Periksa izin peran di database (role_permissions)
        if (!isset(self::$runtimeRolePermissionsCache[$role])) {
            self::$runtimeRolePermissionsCache[$role] = self::where('role', $role)->get()->keyBy('permission_key');
        }
        $row = self::$runtimeRolePermissionsCache[$role]->get($permissionKey);
        if (!$row && in_array($permissionKey, ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_persuratan');
        }
        if (!$row && in_array($permissionKey, ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_kesiswaan');
        }
        if (!$row && in_array($permissionKey, ['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda'], true)) {
            $row = self::$runtimeRolePermissionsCache[$role]->get('menu_wali_kelas');
        }
        if (!$row && $permissionKey === 'menu_kesiswaan') {
            foreach (['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row && $permissionKey === 'menu_persuratan') {
            foreach (['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }
        if (!$row && $permissionKey === 'menu_wali_kelas') {
            foreach (['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda'] as $sk) {
                $sr = self::$runtimeRolePermissionsCache[$role]->get($sk);
                if ($sr && $sr->is_allowed && $sr->can_read) {
                    $row = $sr;
                    break;
                }
            }
        }

        if ($row) {
            // Matriks Hak Akses Peran adalah Gerbang Utama (Primary Gate):
            // Jika modul tidak diizinkan atau hak baca dimatikan, tolak mutlak (tugas tambahan tidak boleh bypass)
            if (!$row->is_allowed || !$row->can_read) {
                return false;
            }

            // Jika modul spesifik tugas tambahan (misal laboran, persuratan, wali kelas, dsb.),
            // hanya personel yang memegang tugas tambahan tersebut yang boleh mengakses (Secondary Gate)
            if (in_array($role, ['guru', 'tendik', 'peserta_didik', 'orang_tua']) && $hasDutyTables && self::isDutySpecificPermission($permissionKey)) {
                // Kecuali Koordinator Kelas untuk modul wali kelas (sudah diizinkan mewarisi Wali Kelas di atas)
                if ($role === 'peserta_didik' && self::isKoordinator($user) && (str_starts_with($permissionKey, 'menu_wali_kelas') || in_array($permissionKey, ['menu_peserta_didik_aktif', 'menu_presensi_peserta_didik']))) {
                    // Koordinator lolos gate
                } else {
                    $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
                    $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
                    if (!self::hasDutyPermission($userId, $ptkId, $permissionKey, $action)) {
                        return false;
                    }
                }
            }

            if ($actionCol === 'can_read') {
                return (bool) $row->can_read;
            }

            return (bool) ($row->can_read && $row->{$actionCol});
        }

        // 2. Jika modul TIDAK ADA di role_permissions untuk peran dasar user:
        // Periksa apakah user memiliki tugas tambahan aktif yang membuka akses ke modul ini (Duty Gate)
        if (in_array($role, ['guru', 'tendik', 'peserta_didik', 'orang_tua']) && $hasDutyTables) {
            $userId = is_array($user) ? ($user['id'] ?? ($user['pengguna_id'] ?? null)) : ($user->id ?? ($user->pengguna_id ?? null));
            $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            if (self::hasDutyPermission($userId, $ptkId, $permissionKey, $action)) {
                if (!isset(self::$runtimeRolePermissionsCache['tugas_tambahan'])) {
                    self::$runtimeRolePermissionsCache['tugas_tambahan'] = self::where('role', 'tugas_tambahan')->get()->keyBy('permission_key');
                }
                $dutyRow = self::$runtimeRolePermissionsCache['tugas_tambahan']->get($permissionKey);
                if ($dutyRow) {
                    if (!$dutyRow->is_allowed || !$dutyRow->can_read) {
                        return false;
                    }
                    if ($actionCol === 'can_read') {
                        return true;
                    }
                    return (bool) ($dutyRow->can_read && ($dutyRow->{$actionCol} ?? true));
                }
                return true;
            }
        }

        // 3. Fallback: Jika modul terdaftar secara default untuk role ini (misal sebelum sinkronisasi/migrasi DB selesai)
        if (self::isDefaultAllowed($role, $permissionKey)) {
            $defaultCrud = self::getDefaultCrudForRole($role, $permissionKey);
            $actionCol = 'can_' . $action;
            if ($actionCol === 'can_read') {
                return (bool) $defaultCrud['can_read'];
            }
            return (bool) ($defaultCrud['can_read'] && ($defaultCrud[$actionCol] ?? false));
        }

        return false;
    }

    /**
     * Periksa apakah pengguna memiliki tugas tambahan aktif yang membuka modul tertentu
     */
    public static function hasDutyPermission(mixed $userId, ?string $ptkId, string $permissionKey, string $action = 'read'): bool
    {
        try {
            $cacheKey = ($userId ?: 'null') . '|' . ($ptkId ?: 'null');

            if (!isset(self::$runtimeDutyPermissionsCache[$cacheKey])) {
                $query = DB::table('ptk_tugas_tambahan as ptt')
                    ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                    ->where('ptt.is_active', true)
                    ->where('rtt.is_active', true);

                if ($userId && $ptkId) {
                    $query->where(function ($q) use ($userId, $ptkId) {
                        $q->where('ptt.user_id', (string) $userId)->orWhere('ptt.ptk_id', $ptkId);
                    });
                } elseif ($userId) {
                    $query->where('ptt.user_id', (string) $userId);
                } elseif ($ptkId) {
                    $query->where('ptt.ptk_id', $ptkId);
                } else {
                    self::$runtimeDutyPermissionsCache[$cacheKey] = [];
                    return false;
                }

                $allowedKeys = [];

                // 1. Cek granted_permissions JSON dari tabel ref_tugas_tambahan
                $records = $query->select('rtt.kode', 'rtt.kelompok', 'rtt.bidang', 'rtt.granted_permissions', 'ptt.rombel_id')->get();
                foreach ($records as $rec) {
                    $raw = $rec->granted_permissions;
                    $perms = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (is_array($perms)) {
                        foreach ($perms as $p) {
                            $allowedKeys[$p] = true;
                        }
                    }

                    // 2. Pemetaan bawaan berdasarkan kode tugas tambahan
                    $kode = $rec->kode;
                    if ($kode === 'WALI_KELAS') {
                        foreach (['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal', 'menu_wali_kelas_pip', 'menu_wali_kelas_validasi', 'menu_wali_kelas_agenda', 'menu_peserta_didik_aktif', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_berkas_peserta_didik'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['KEPALA_SEKOLAH', 'WAKA_KURIKULUM'], true)) {
                        foreach (['menu_rombel', 'menu_pembelajaran', 'menu_jadwal_kbm', 'menu_kompetensi_keahlian', 'menu_presensi_mengajar', 'menu_agenda_kbm'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'WAKA_KESISWAAN') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_e_izin'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'PEMBINA_OSIS') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_pengumuman', 'menu_kesiswaan_kedisiplinan'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'PEMBINA_EKSKUL') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_pengumuman'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['WAKA_SARPRAS', 'KEPALA_BENGKEL'], true)) {
                        foreach (['menu_sarpras', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'KEPALA_LAB') {
                        foreach (['menu_laboran', 'menu_sarpras', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['KEPALA_PERPUSTAKAAN', 'PUSTAKAWAN'], true)) {
                        foreach (['menu_perpustakaan', 'menu_perpustakaan_koleksi', 'menu_perpustakaan_sirkulasi', 'menu_perpustakaan_kunjungan', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'LABORAN') {
                        foreach (['menu_laboran', 'menu_laboran_inventaris', 'menu_laboran_jadwal', 'menu_laboran_aset', 'menu_laboran_peminjaman', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['TEKNISI_IT', 'TEKNISI_GEDUNG', 'TEKNISI_LAPANGAN'], true)) {
                        foreach (['menu_teknisi', 'menu_teknisi_wo', 'menu_teknisi_pemeliharaan', 'menu_inventaris'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'PETUGAS_KEAMANAN' || $kode === 'SATPAM') {
                        foreach (['menu_keamanan', 'menu_keamanan_izin', 'menu_keamanan_presensi', 'menu_keamanan_buku_tamu', 'menu_buku_tamu', 'menu_keamanan_patroli', 'menu_keamanan_scan', 'menu_rfid'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif (in_array($kode, ['PENJAGA_SEKOLAH', 'PESURUH'], true)) {
                        foreach (['menu_penjaga', 'menu_penjaga_kebersihan', 'menu_penjaga_ronda', 'menu_buku_tamu'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'GURU_PIKET') {
                        foreach (['menu_piket', 'menu_piket_presensi_guru', 'menu_piket_presensi_kelas', 'menu_piket_agenda', 'menu_piket_izin', 'menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_presensi_peserta_didik', 'menu_e_izin', 'menu_piket_jurnal', 'menu_riwayat_rfid'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'OPERATOR_DAPODIK') {
                        foreach (['menu_dapodik', 'menu_peserta_didik_aktif', 'menu_guru_aktif', 'menu_tendik_aktif', 'menu_rombel', 'menu_pembelajaran'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'KEPALA_TAS') {
                        foreach ([
                            'menu_kepala_tas', 'menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan',
                            'menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi',
                            'menu_kepegawaian', 'menu_kepegawaian_guru', 'menu_kepegawaian_tendik', 'menu_kepegawaian_kgb', 'menu_kepegawaian_cuti',
                            'menu_keuangan', 'menu_sarpras', 'menu_sarpras_ruang', 'menu_inventaris', 'menu_sarpras_peminjaman',
                            'menu_target_capaian', 'menu_aktivitas_tendik', 'menu_laporan_tendik',
                            'menu_laboran', 'menu_laboran_inventaris', 'menu_laboran_jadwal', 'menu_laboran_aset', 'menu_laboran_peminjaman',
                            'menu_perpustakaan', 'menu_perpustakaan_koleksi', 'menu_perpustakaan_sirkulasi', 'menu_perpustakaan_kunjungan',
                            'menu_teknisi', 'menu_teknisi_wo', 'menu_teknisi_pemeliharaan',
                            'menu_keamanan', 'menu_keamanan_izin', 'menu_keamanan_presensi', 'menu_keamanan_buku_tamu', 'menu_buku_tamu', 'menu_keamanan_patroli', 'menu_keamanan_scan',
                            'menu_penjaga', 'menu_penjaga_kebersihan', 'menu_penjaga_ronda',
                            'menu_piket', 'menu_piket_presensi_guru', 'menu_piket_presensi_kelas', 'menu_piket_agenda', 'menu_piket_izin', 'menu_presensi_mengajar', 'menu_presensi_peserta_didik', 'menu_agenda_kbm', 'menu_e_izin', 'menu_piket_jurnal'
                        ] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_PERSURATAN') {
                        foreach (['menu_persuratan', 'menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_KEPEGAWAIAN') {
                        foreach (['menu_kepegawaian', 'menu_kepegawaian_guru', 'menu_kepegawaian_tendik', 'menu_kepegawaian_kgb', 'menu_kepegawaian_cuti', 'menu_tendik_aktif', 'menu_guru_aktif', 'menu_guru_tidak_aktif', 'menu_tendik_tidak_aktif', 'menu_rfid'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_KESISWAAN') {
                        foreach (['menu_kesiswaan', 'menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi', 'menu_peserta_didik_aktif', 'menu_peserta_didik_tidak_aktif', 'menu_berkas_peserta_didik', 'menu_perubahan_data'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'STAF_SARPRAS') {
                        foreach (['menu_sarpras', 'menu_sarpras_ruang', 'menu_inventaris', 'menu_sarpras_peminjaman', 'menu_rombel'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    } elseif ($kode === 'KOORDINATOR_KELAS') {
                        foreach (['menu_wali_kelas_aktif', 'menu_peserta_didik_aktif', 'menu_wali_kelas_presensi', 'menu_presensi_peserta_didik', 'menu_wali_kelas_agenda', 'menu_agenda_kbm'] as $k) {
                            $allowedKeys[$k] = true;
                        }
                    }

                    // Tendik umum: Aktivitas & Laporan Kinerja
                    if (in_array($kode, [
                        'KEPALA_TAS', 'STAF_PERSURATAN', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN',
                        'STAF_SARPRAS', 'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH'
                    ], true)) {
                        $allowedKeys['menu_target_capaian'] = true;
                        $allowedKeys['menu_aktivitas_tendik'] = true;
                        $allowedKeys['menu_laporan_tendik'] = true;
                    }
                }

                // Wariskan izin menu_persuratan ke sub-modul persuratan jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_persuratan'])) {
                    if (!isset($allowedKeys['menu_surat_masuk'])) $allowedKeys['menu_surat_masuk'] = true;
                    if (!isset($allowedKeys['menu_surat_keluar'])) $allowedKeys['menu_surat_keluar'] = true;
                    if (!isset($allowedKeys['menu_pengaturan_persuratan'])) $allowedKeys['menu_pengaturan_persuratan'] = true;
                }

                // Wariskan izin menu_kesiswaan ke sub-modul kesiswaan jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_kesiswaan'])) {
                    if (!isset($allowedKeys['menu_kesiswaan_peserta_didik'])) $allowedKeys['menu_kesiswaan_peserta_didik'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_administrasi'])) $allowedKeys['menu_kesiswaan_administrasi'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_kedisiplinan'])) $allowedKeys['menu_kesiswaan_kedisiplinan'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_kegiatan'])) $allowedKeys['menu_kesiswaan_kegiatan'] = true;
                    if (!isset($allowedKeys['menu_kesiswaan_prestasi'])) $allowedKeys['menu_kesiswaan_prestasi'] = true;
                }

                // Wariskan izin menu_kepegawaian ke sub-modul kepegawaian jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_kepegawaian'])) {
                    if (!isset($allowedKeys['menu_kepegawaian_guru'])) $allowedKeys['menu_kepegawaian_guru'] = true;
                    if (!isset($allowedKeys['menu_kepegawaian_tendik'])) $allowedKeys['menu_kepegawaian_tendik'] = true;
                    if (!isset($allowedKeys['menu_kepegawaian_kgb'])) $allowedKeys['menu_kepegawaian_kgb'] = true;
                    if (!isset($allowedKeys['menu_kepegawaian_cuti'])) $allowedKeys['menu_kepegawaian_cuti'] = true;
                }

                // Wariskan izin menu_perpustakaan ke sub-modul perpustakaan jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_perpustakaan'])) {
                    if (!isset($allowedKeys['menu_perpustakaan_koleksi'])) $allowedKeys['menu_perpustakaan_koleksi'] = true;
                    if (!isset($allowedKeys['menu_perpustakaan_sirkulasi'])) $allowedKeys['menu_perpustakaan_sirkulasi'] = true;
                    if (!isset($allowedKeys['menu_perpustakaan_kunjungan'])) $allowedKeys['menu_perpustakaan_kunjungan'] = true;
                }

                // Wariskan izin menu_sarpras ke sub-modul sarpras jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_sarpras'])) {
                    if (!isset($allowedKeys['menu_sarpras_ruang'])) $allowedKeys['menu_sarpras_ruang'] = true;
                    if (!isset($allowedKeys['menu_inventaris'])) $allowedKeys['menu_inventaris'] = true;
                    if (!isset($allowedKeys['menu_sarpras_peminjaman'])) $allowedKeys['menu_sarpras_peminjaman'] = true;
                }

                // Wariskan izin menu_laboran ke sub-modul laboran jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_laboran'])) {
                    if (!isset($allowedKeys['menu_laboran_inventaris'])) $allowedKeys['menu_laboran_inventaris'] = true;
                    if (!isset($allowedKeys['menu_laboran_jadwal'])) $allowedKeys['menu_laboran_jadwal'] = true;
                    if (!isset($allowedKeys['menu_laboran_aset'])) $allowedKeys['menu_laboran_aset'] = true;
                    if (!isset($allowedKeys['menu_laboran_peminjaman'])) $allowedKeys['menu_laboran_peminjaman'] = true;
                }

                // Wariskan izin menu_teknisi ke sub-modul teknisi jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_teknisi'])) {
                    if (!isset($allowedKeys['menu_teknisi_wo'])) $allowedKeys['menu_teknisi_wo'] = true;
                    if (!isset($allowedKeys['menu_teknisi_pemeliharaan'])) $allowedKeys['menu_teknisi_pemeliharaan'] = true;
                }

                // Wariskan izin menu_keamanan ke sub-modul keamanan jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_keamanan'])) {
                    if (!isset($allowedKeys['menu_keamanan_izin'])) $allowedKeys['menu_keamanan_izin'] = true;
                    if (!isset($allowedKeys['menu_keamanan_presensi'])) $allowedKeys['menu_keamanan_presensi'] = true;
                    if (!isset($allowedKeys['menu_buku_tamu'])) $allowedKeys['menu_buku_tamu'] = true;
                    if (!isset($allowedKeys['menu_keamanan_patroli'])) $allowedKeys['menu_keamanan_patroli'] = true;
                    if (!isset($allowedKeys['menu_keamanan_scan'])) $allowedKeys['menu_keamanan_scan'] = true;
                }

                // Wariskan izin menu_penjaga ke sub-modul penjaga jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_penjaga'])) {
                    if (!isset($allowedKeys['menu_penjaga_kebersihan'])) $allowedKeys['menu_penjaga_kebersihan'] = true;
                    if (!isset($allowedKeys['menu_penjaga_ronda'])) $allowedKeys['menu_penjaga_ronda'] = true;
                }

                // Wariskan izin menu_piket ke sub-modul piket jika belum terdefinisi secara terpisah
                if (!empty($allowedKeys['menu_piket'])) {
                    if (!isset($allowedKeys['menu_piket_presensi_guru'])) $allowedKeys['menu_piket_presensi_guru'] = true;
                    if (!isset($allowedKeys['menu_piket_presensi_kelas'])) $allowedKeys['menu_piket_presensi_kelas'] = true;
                    if (!isset($allowedKeys['menu_piket_agenda'])) $allowedKeys['menu_piket_agenda'] = true;
                    if (!isset($allowedKeys['menu_piket_izin'])) $allowedKeys['menu_piket_izin'] = true;
                    if (!isset($allowedKeys['menu_presensi_mengajar'])) $allowedKeys['menu_presensi_mengajar'] = true;
                    if (!isset($allowedKeys['menu_presensi_peserta_didik'])) $allowedKeys['menu_presensi_peserta_didik'] = true;
                    if (!isset($allowedKeys['menu_agenda_kbm'])) $allowedKeys['menu_agenda_kbm'] = true;
                    if (!isset($allowedKeys['menu_e_izin'])) $allowedKeys['menu_e_izin'] = true;
                    if (!isset($allowedKeys['menu_piket_jurnal'])) $allowedKeys['menu_piket_jurnal'] = true;
                }

                self::$runtimeDutyPermissionsCache[$cacheKey] = $allowedKeys;
            }

            $action = strtolower(trim($action));
            if (isset(self::$runtimeDutyPermissionsCache[$cacheKey][$permissionKey])) {
                return in_array($action, ['read', 'create', 'update', 'delete']);
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Perbarui daftar izin hak akses yang dibuka untuk tugas tambahan tertentu
     */
    public static function updateDutyPermissions(string|int $dutyIdOrKode, array $grantedPermissions): bool
    {
        return DutyPermissionManager::updateDutyPermissions($dutyIdOrKode, $grantedPermissions);
    }

    /**
     * Dapatkan daftar granted_permissions default bawaan sistem untuk kode tugas tambahan tertentu
     */
    public static function getDefaultGrantedPermissionsForDuty(string $kode): array
    {
        return DutyPermissionDefaults::forDuty($kode);
    }

    /**
     * Reset daftar modul yang diizinkan untuk tugas tambahan ke pengaturan default baku
     */
    public static function resetDutyDefaults(string|int|null $dutyIdOrKode = null): void
    {
        DutyPermissionManager::resetDutyDefaults($dutyIdOrKode);
    }

    /**
     * Toggle satu izin modul untuk tugas tambahan tertentu
     */
    public static function toggleDutyPermission(string|int $dutyIdOrKode, string $permissionKey, bool $isAllowed): array
    {
        return DutyPermissionManager::toggleDutyPermission($dutyIdOrKode, $permissionKey, $isAllowed);
    }

    /**
     * Dapatkan konfigurasi hak aksi CRUD default untuk role dan modul tertentu
     */
    public static function getDefaultCrudForRole(string $role, string $permKey): array
    {
        if ($role === 'admin') {
            return [
                'is_allowed' => true,
                'can_create' => true,
                'can_read'   => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        if (self::isForbiddenForRole($role, $permKey)) {
            return [
                'is_allowed' => false,
                'can_create' => false,
                'can_read'   => false,
                'can_update' => false,
                'can_delete' => false,
            ];
        }

        if ($role === 'tendik' || $role === 'tugas_tambahan') {
            return [
                'is_allowed' => true,
                'can_create' => true,
                'can_read'   => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        // Standar baku setiap peran non-admin lainnya: memunculkan semua menu dengan batas Read saja
        return [
            'is_allowed' => true,
            'can_create' => false,
            'can_read'   => true,
            'can_update' => false,
            'can_delete' => false,
        ];
    }

    /**
     * Sinkronkan referensi tugas tambahan di tabel ref_tugas_tambahan
     */
    public static function syncDutiesGrantedPermissions(): void
    {
        DutyPermissionManager::syncDutiesGrantedPermissions();
    }

    /**
     * Reset hak akses peran ke standar baku default yang bersih dan terisolasi sesuai kelompoknya.
     * Jika $targetRole diberikan (misal 'guru', 'tendik', 'peserta_didik', 'admin', 'tugas_tambahan'), hanya peran tersebut yang direset.
     * Jika null, seluruh peran direset secara serentak.
     */
    public static function resetDefaultPermissions(?string $targetRole = null): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $validRoles = ['admin', 'guru', 'tendik', 'peserta_didik', 'orang_tua', 'tugas_tambahan'];
        $isSpecific = $targetRole && in_array($targetRole, $validRoles, true);

        if ($isSpecific) {
            $rolesToReset = [$targetRole];
            self::where('role', $targetRole)->delete();
        } else {
            $rolesToReset = $validRoles;
            self::truncate();
        }

        $allPermissions = self::getAvailablePermissions();
        $records = [];
        $now = now();

        foreach ($allPermissions as $groupName => $items) {
            foreach ($items as $permKey => $config) {
                foreach ($rolesToReset as $role) {
                    $isRoleSupported = in_array($role, $config['roles'] ?? [], true);
                    if ($role === 'admin' && !self::isDutyModule($permKey, $groupName) && !in_array($permKey, self::FORBIDDEN_MODULES['admin'] ?? [], true)) {
                        $isRoleSupported = true;
                    }
                    if ($role === 'tugas_tambahan' && self::isDutyModule($permKey, $groupName)) {
                        $isRoleSupported = true;
                    }

                    if ($isRoleSupported && !self::isForbiddenForRole($role, $permKey)) {
                        $crud = self::getDefaultCrudForRole($role, $permKey);
                        $records[] = [
                            'role'           => $role,
                            'permission_key' => $permKey,
                            'is_allowed'     => true,
                            'can_create'     => (bool) $crud['can_create'],
                            'can_read'       => true,
                            'can_update'     => (bool) $crud['can_update'],
                            'can_delete'     => (bool) $crud['can_delete'],
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                    }
                }
            }
        }

        if (!empty($records)) {
            foreach (array_chunk($records, 100) as $chunk) {
                self::insert($chunk);
            }
        }

        self::syncDutiesGrantedPermissions();
        self::clearRuntimeCache();
    }

    /**
     * Sinkronisasi modul & fitur ke tabel role_permissions.
     * Otomatis mendaftarkan modul baru ke database, membersihkan kontaminasi silang antar peran,
     * dan menjaga kustomisasi hak akses admin.
     */
    public static function syncAvailablePermissions(): int
    {
        if (!Schema::hasTable('role_permissions')) {
            return 0;
        }

        $allPermissions = self::getAvailablePermissions();
        $allowedKeysPerRole = [
            'admin'          => [],
            'guru'           => [],
            'tendik'         => [],
            'peserta_didik'  => [],
            'orang_tua'      => [],
            'tugas_tambahan' => [],
        ];

        foreach ($allPermissions as $groupName => $items) {
            foreach ($items as $permKey => $config) {
                $targetRoles = $config['roles'] ?? ['admin'];
                foreach ($targetRoles as $r) {
                    if (isset($allowedKeysPerRole[$r])) {
                        $allowedKeysPerRole[$r][] = $permKey;
                    }
                }
            }
        }

        // 1. Bersihkan modul terlarang dan baris nonaktif di luar default peran (kustomisasi aktif admin dijaga)
        foreach (['admin', 'guru', 'tendik', 'peserta_didik', 'orang_tua', 'tugas_tambahan'] as $role) {
            $forbidden = self::FORBIDDEN_MODULES[$role] ?? [];
            if ($role !== 'tugas_tambahan') {
                $forbidden = array_unique(array_merge($forbidden, self::getDutyModuleKeys()));
            }
            if (!empty($forbidden)) {
                self::where('role', $role)->whereIn('permission_key', $forbidden)->delete();
            }
            self::where('role', $role)
                ->whereNotIn('permission_key', $allowedKeysPerRole[$role])
                ->where(function ($q) {
                    $q->where('is_allowed', false)->orWhere('can_read', false);
                })
                ->delete();
        }

        $addedCount = 0;
        $now = now();

        // 2. Kumpulkan permission_key yang sudah ada per role
        $existingByRole = self::select('role', 'permission_key')
            ->get()
            ->groupBy('role')
            ->map(function ($items) {
                return $items->pluck('permission_key')->flip()->toArray();
            })
            ->toArray();

        // 3. Daftarkan modul baru yang belum tercatat di database role_permissions
        foreach ($allPermissions as $groupName => $items) {
            foreach ($items as $permKey => $config) {
                if ($permKey === 'menu_rapor') continue;
                $targetRoles = $config['roles'] ?? ['admin'];

                foreach ($targetRoles as $role) {
                    if (self::isForbiddenForRole($role, $permKey)) {
                        continue;
                    }

                    if (!isset($existingByRole[$role][$permKey])) {
                        $crud = self::getDefaultCrudForRole($role, $permKey);
                        self::create([
                            'role'           => $role,
                            'permission_key' => $permKey,
                            'is_allowed'     => true,
                            'can_create'     => (bool) $crud['can_create'],
                            'can_read'       => true,
                            'can_update'     => (bool) $crud['can_update'],
                            'can_delete'     => (bool) $crud['can_delete'],
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]);
                        $existingByRole[$role][$permKey] = true;
                        $addedCount++;
                    }
                }
            }
        }

        self::syncDutiesGrantedPermissions();
        self::clearRuntimeCache();

        return $addedCount;
    }

    /**
     * Daftar izin default bawaan sistem untuk tiap role jika belum dikonfigurasi.
     */
    public static function isDefaultAllowed(string $role, string $permissionKey): bool
    {
        if (self::isForbiddenForRole($role, $permissionKey)) {
            return false;
        }

        if ($role === 'admin') {
            return !self::isDutyModule($permissionKey) && !in_array($permissionKey, self::FORBIDDEN_MODULES['admin'] ?? [], true);
        }

        if ($role === 'tugas_tambahan') {
            return self::isDutyModule($permissionKey);
        }

        $conf = self::getPermissionConfig($permissionKey);
        if ($conf && in_array($role, $conf['roles'] ?? [], true)) {
            return true;
        }

        return false;
    }

    /**
     * Cek izin aksi CRUD spesifik (create, read, update, delete) untuk menu tertentu
     */
    public static function can(mixed $userOrRole, string $permissionKey, string $action): bool
    {
        return self::canAccess($userOrRole, $permissionKey, $action);
    }
}
