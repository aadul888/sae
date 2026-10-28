<?php

namespace App\Models\Concerns;

use App\Support\Table;

/**
 * Sinkronisasi & reset matriks `role_permissions` untuk `RolePermission`.
 *
 * Diekstrak dari `App\Models\RolePermission` agar model tetap di bawah batas
 * 1.000 baris. Isi method dipindahkan APA ADANYA sehingga hasil sinkronisasi
 * tidak berubah.
 *
 * Pemakai wajib berupa Eloquent Model dengan tabel `role_permissions` dan
 * menyediakan `getAvailablePermissions()`, `getDefaultCrudForRole()`,
 * `isForbiddenForRole()`, `isDutyModule()`, `getDutyModuleKeys()`,
 * `syncDutiesGrantedPermissions()`, dan `clearRuntimeCache()`.
 */
trait RolePermissionSynchronizer
{
    /**
     * Reset hak akses peran ke standar baku default yang bersih dan terisolasi sesuai kelompoknya.
     * Jika $targetRole diberikan (misal 'guru', 'tendik', 'peserta_didik', 'admin', 'tugas_tambahan'), hanya peran tersebut yang direset.
     * Jika null, seluruh peran direset secara serentak.
     */
    protected static function resetDefaultPermissionsMatrix(?string $targetRole = null): void
    {
        if (!Table::exists('role_permissions')) {
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
    protected static function syncAvailablePermissionsMatrix(): int
    {
        if (!Table::exists('role_permissions')) {
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
}
