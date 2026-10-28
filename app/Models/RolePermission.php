<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Support\PermissionDictionary;
use App\Support\DutyPermissionDefaults;
use App\Support\DutyPermissionManager;
use App\Support\ModuleDiscovery;
use App\Support\WaliKelasAccessHelper;
use App\Support\RolePermissionRestrictions;


use App\Support\Table;
use App\Models\Concerns\RolePermissionDutyGate;
use App\Models\Concerns\RolePermissionSynchronizer;

class RolePermission extends Model
{
    use HasFactory;
    use RolePermissionDutyGate;
    use RolePermissionSynchronizer;

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
    protected static ?bool $runtimeAdminExistsCache = null;
    protected static ?bool $runtimeHasTableRolePermissions = null;
    protected static ?bool $runtimeHasTableDuties = null;
    protected static ?array $runtimeAvailablePermissionsCache = null;
    protected static ?array $runtimeAllSystemModulesCache = null;

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
        self::$runtimeAvailablePermissionsCache = null;
        self::$runtimeAllSystemModulesCache = null;
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
        if (self::$runtimeAvailablePermissionsCache !== null) {
            return self::$runtimeAvailablePermissionsCache;
        }

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

        return self::$runtimeAvailablePermissionsCache = $configs;
    }

    /**
     * Dapatkan semua modul sistem yang valid (khusus menu_)
     */
    public static function getAllSystemModules(): array
    {
        if (self::$runtimeAllSystemModulesCache !== null) {
            return self::$runtimeAllSystemModulesCache;
        }

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

        return self::$runtimeAllSystemModulesCache = $modules;
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
            self::$runtimeHasTableRolePermissions = Table::exists('role_permissions');
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
            self::$runtimeHasTableRolePermissions = Table::exists('role_permissions');
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
            self::$runtimeHasTableDuties = Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan');
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
     * Reset hak akses peran ke standar baku default (lihat `RolePermissionSynchronizer`).
     * Jika $targetRole diberikan, hanya peran tersebut yang direset; jika null, seluruh peran.
     */
    public static function resetDefaultPermissions(?string $targetRole = null): void
    {
        self::resetDefaultPermissionsMatrix($targetRole);
    }

    /**
     * Sinkronisasi modul & fitur ke tabel role_permissions
     * (lihat `RolePermissionSynchronizer::syncAvailablePermissions`).
     */
    public static function syncAvailablePermissions(): int
    {
        return self::syncAvailablePermissionsMatrix();
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
