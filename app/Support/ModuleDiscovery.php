<?php

namespace App\Support;

use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ModuleDiscovery
{
    /**
     * Otomatis mendeteksi modul menu & fitur baru yang dibuat di sistem
     * dengan memindai berkas route, controller, blade view, dan rekaman database.
     */
    public static function discoverSystemModules(array $baseConfigs = []): array
    {
        $discovered = [];
        $knownKeys = [];
        foreach ($baseConfigs as $group => $items) {
            foreach (array_keys($items) as $k) {
                $knownKeys[$k] = true;
            }
        }

        // 1. Kumpulkan seluruh berkas rute, controller, dan view secara rekursif
        $dirsToScan = [
            base_path('routes'),
            app_path('Http/Controllers'),
            resource_path('views/partials'),
            resource_path('views/dashboard'),
        ];
        $filesToScan = [];
        foreach ($dirsToScan as $dir) {
            if (!is_dir($dir)) continue;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php'])) {
                    $filesToScan[] = $file->getPathname();
                }
            }
        }

        foreach ($filesToScan as $filePath) {
            $content = @file_get_contents($filePath);
            if (!$content) continue;

            preg_match_all('/can\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\)/', $content, $m1);
            preg_match_all('/permission:([a-zA-Z0-9_]+)/', $content, $m2);
            preg_match_all('/canAccess\([^,]+,\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $content, $m3);

            $rawKeys = array_unique(array_merge($m1[1] ?? [], $m2[1] ?? [], $m3[1] ?? []));
            $foundKeys = [];
            foreach ($rawKeys as $rk) {
                if (str_starts_with($rk, 'menu_') || str_starts_with($rk, 'fitur_')) {
                    $foundKeys[] = $rk;
                }
            }

            foreach ($foundKeys as $key) {
                if (!isset($knownKeys[$key]) && !isset($discovered[$key])) {
                    $cleanName = str_replace(['menu_', 'fitur_', '_'], ['', '', ' '], $key);
                    $label = ucwords($cleanName);
                    $meta = self::resolveModuleGroupAndRoles($key);

                    $discovered[$key] = [
                        'label' => $label,
                        'icon' => $meta['icon'],
                        'group' => $meta['group'],
                        'roles' => $meta['roles'],
                    ];
                }
            }
        }

        // 2. Deteksi modul yang tersimpan di tabel role_permissions database
        if (Schema::hasTable('role_permissions')) {
            $dbKeys = DB::table('role_permissions')->distinct()->pluck('permission_key')->toArray();
            foreach ($dbKeys as $key) {
                if (!isset($knownKeys[$key]) && !isset($discovered[$key])) {
                    $cleanName = str_replace(['menu_', 'fitur_', '_'], ['', '', ' '], $key);
                    $meta = self::resolveModuleGroupAndRoles($key);

                    $discovered[$key] = [
                        'label' => ucwords($cleanName),
                        'icon' => $meta['icon'],
                        'group' => $meta['group'],
                        'roles' => $meta['roles'],
                    ];
                }
            }
        }

        return $discovered;
    }

    /**
     * Resolusi cerdas untuk kelompok dan peran default modul baru berdasarkan nama key.
     */
    public static function resolveModuleGroupAndRoles(string $key): array
    {
        $group = 'Layanan Digital';
        $icon = 'fa-cube';
        $targetRoles = ['admin'];

        if (str_starts_with($key, 'fitur_')) {
            $group = 'Fitur Operasional';
            $icon = 'fa-screwdriver-wrench';
            $targetRoles = ['admin'];
        } elseif (str_contains($key, 'wali_kelas') || str_contains($key, 'wali')) {
            $group = 'Wali Kelas';
            $icon = 'fa-chalkboard-user';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'guru') || str_contains($key, 'mengajar') || str_contains($key, 'kbm') || str_contains($key, 'agenda')) {
            $group = 'Administrasi Guru';
            $icon = 'fa-chalkboard-user';
            $targetRoles = ['guru'];
        } elseif (str_contains($key, 'surat') || str_contains($key, 'persuratan')) {
            $group = 'Tendik: Persuratan';
            $icon = 'fa-envelope-open-text';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'kesiswaan') || str_contains($key, 'klaper') || str_contains($key, 'mutasi')) {
            $group = 'Tendik: Kesiswaan';
            $icon = 'fa-user-graduate';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'kepegawaian') || str_contains($key, 'kgb') || str_contains($key, 'cuti')) {
            $group = 'Tendik: Kepegawaian';
            $icon = 'fa-id-card-alt';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'sarpras') || str_contains($key, 'inventaris') || str_contains($key, 'aset') || str_contains($key, 'ruang')) {
            $group = 'Tendik: Sarpras & Aset';
            $icon = 'fa-building';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'laboran') || str_contains($key, 'lab')) {
            $group = 'Tendik: Laboratorium';
            $icon = 'fa-flask';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'perpustakaan') || str_contains($key, 'buku') || str_contains($key, 'koleksi')) {
            $group = 'Tendik: Perpustakaan';
            $icon = 'fa-book-open';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'teknisi') || str_contains($key, 'hardware') || str_contains($key, 'jaringan')) {
            $group = 'Tendik: Teknisi IT';
            $icon = 'fa-network-wired';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'keamanan') || str_contains($key, 'satpam') || str_contains($key, 'patroli')) {
            $group = 'Tendik: Keamanan & Tamu';
            $icon = 'fa-shield-halved';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'penjaga') || str_contains($key, 'kebersihan') || str_contains($key, 'ronda')) {
            $group = 'Tendik: Fasilitas & Penjaga';
            $icon = 'fa-broom';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'piket')) {
            $group = 'Tendik: Piket Sekolah';
            $icon = 'fa-clipboard-user';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'target') || str_contains($key, 'aktivitas') || str_contains($key, 'laporan_tendik') || str_contains($key, 'capaian')) {
            $group = 'Tendik: Kinerja & Aktivitas';
            $icon = 'fa-bullseye';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'peserta_didik') || str_contains($key, 'siswa') || str_contains($key, 'santri') || str_contains($key, 'rapor') || str_contains($key, 'identitas')) {
            $group = 'Portal Peserta Didik';
            $icon = 'fa-user-graduate';
            $targetRoles = ['peserta_didik', 'orang_tua'];
        } elseif (str_contains($key, 'dapodik')) {
            $group = 'Menu Utama';
            $icon = 'fa-cloud-arrow-down';
            $targetRoles = ['admin'];
        } elseif (str_contains($key, 'keuangan') || str_contains($key, 'spp') || str_contains($key, 'bayar')) {
            $group = 'Tendik: Keuangan';
            $icon = 'fa-wallet';
            $targetRoles = ['tugas_tambahan'];
        } elseif (str_contains($key, 'pengguna') || str_contains($key, 'hak_akses') || str_contains($key, 'maintenance') || str_contains($key, 'update') || str_contains($key, 'pengaturan')) {
            $group = 'Sistem & Pengaturan';
            $icon = 'fa-sliders';
            $targetRoles = ['admin'];
        }

        return ['group' => $group, 'icon' => $icon, 'roles' => $targetRoles];
    }

    /**
     * Otomatis mendaftarkan seluruh modul baru yang ditemukan di sistem ke database.
     */
    public static function autoSyncNewDiscoveredModules(): int
    {
        if (!Schema::hasTable('role_permissions')) {
            return 0;
        }

        $allAvailable = RolePermission::getAvailablePermissions();
        $existingByRole = RolePermission::select('role', 'permission_key')
            ->get()
            ->groupBy('role')
            ->map(function ($items) {
                return $items->pluck('permission_key')->flip()->toArray();
            })
            ->toArray();

        $now = now();
        $newRecords = [];

        foreach ($allAvailable as $group => $items) {
            foreach ($items as $permKey => $config) {
                $targetRoles = $config['roles'] ?? ['admin'];

                foreach ($targetRoles as $role) {
                    if (RolePermission::isForbiddenForRole($role, $permKey)) {
                        continue;
                    }

                    if (!isset($existingByRole[$role][$permKey])) {
                        $crud = RolePermission::getDefaultCrudForRole($role, $permKey);
                        $newRecords[] = [
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
                        $existingByRole[$role][$permKey] = true;
                    }
                }
            }
        }

        if (!empty($newRecords)) {
            foreach (array_chunk($newRecords, 100) as $chunk) {
                RolePermission::insert($chunk);
            }
            RolePermission::clearRuntimeCache();
        }

        RolePermission::syncDutiesGrantedPermissions();

        return count($newRecords);
    }

    /**
     * Otomatis mendaftarkan modul baru secara realtime saat pertama kali dipanggil / diakses.
     */
    public static function autoRegisterNewPermission(string $permissionKey): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $conf = RolePermission::getPermissionConfig($permissionKey) ?: [
            'roles' => ['admin'],
        ];
        $targetRoles = array_unique(array_merge(['admin'], $conf['roles'] ?? []));

        // 1. Daftarkan untuk Admin
        $adminPerm = RolePermission::where('role', 'admin')->where('permission_key', $permissionKey)->first();
        if (!$adminPerm) {
            RolePermission::create([
                'role'           => 'admin',
                'permission_key' => $permissionKey,
                'is_allowed'     => true,
                'can_create'     => true,
                'can_read'       => true,
                'can_update'     => true,
                'can_delete'     => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // 2. Daftarkan untuk target peran default jika ada
        foreach ($targetRoles as $r) {
            if ($r === 'admin') continue;
            $exist = RolePermission::where('role', $r)->where('permission_key', $permissionKey)->exists();
            if (!$exist) {
                $crud = RolePermission::getDefaultCrudForRole($r, $permissionKey);
                RolePermission::create([
                    'role'           => $r,
                    'permission_key' => $permissionKey,
                    'is_allowed'     => $crud['is_allowed'],
                    'can_create'     => $crud['can_create'],
                    'can_read'       => $crud['can_read'],
                    'can_update'     => $crud['can_update'],
                    'can_delete'     => $crud['can_delete'],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        RolePermission::clearRuntimeCache();
    }
}
