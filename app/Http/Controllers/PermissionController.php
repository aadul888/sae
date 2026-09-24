<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    /**
     * Tampilkan halaman manajemen hak akses
     */
    public function index(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return redirect()->route($role ? ('dashboard.' . ($role === 'peserta_didik' ? 'peserta-didik' : $role)) : 'login')->with('error', 'Akses dibatasi hanya untuk Administrator.');
        }

        $activeRole = $request->query('role', 'global');
        if (!in_array($activeRole, ['global', 'admin', 'guru', 'tendik', 'peserta_didik'])) {
            $activeRole = 'global';
        }

        // Auto-sinkronisasi modul baru jika ada modul yang terdeteksi di sistem tetapi belum tercatat untuk Admin
        if (\Illuminate\Support\Facades\Schema::hasTable('role_permissions')) {
            RolePermission::autoSyncNewDiscoveredModules();
        }

        $permissionsConfig = RolePermission::getAvailablePermissions();
        $allPermissions = RolePermission::all()->groupBy('role');
        $isSystemConfigured = RolePermission::where('role', 'admin')->exists();

        // Hitungan izin aktif per peran
        $counts = [
            'admin' => 0,
            'guru' => 0,
            'tendik' => 0,
            'peserta_didik' => 0,
            'global' => 0,
        ];
        foreach (['admin', 'guru', 'tendik', 'peserta_didik'] as $r) {
            $counts[$r] = RolePermission::where('role', $r)
                ->where('is_allowed', true)
                ->where('can_read', true)
                ->count();
        }

        $tableModules = [];
        $groups = [];

        if ($activeRole === 'global') {
            // Tampilan Datatable Global: seluruh modul dengan toggle status 4 peran
            foreach ($permissionsConfig as $groupName => $items) {
                if (!in_array($groupName, $groups)) {
                    $groups[] = $groupName;
                }
                foreach ($items as $permKey => $perm) {
                    $rolesData = [];
                    foreach (['admin', 'guru', 'tendik', 'peserta_didik'] as $r) {
                        $saved = $allPermissions->get($r)?->keyBy('permission_key')->get($permKey);
                        $isDefault = in_array($r, $perm['roles'] ?? []);
                        $isAllowed = $saved ? (bool) ($saved->is_allowed && $saved->can_read) : (!$isSystemConfigured && $isDefault);
                        $rolesData[$r] = [
                            'is_allowed' => $isAllowed,
                            'can_create' => $saved ? (bool) $saved->can_create : ($r === 'admin'),
                            'can_read'   => $saved ? (bool) $saved->can_read : $isDefault,
                            'can_update' => $saved ? (bool) $saved->can_update : ($r === 'admin'),
                            'can_delete' => $saved ? (bool) $saved->can_delete : ($r === 'admin'),
                            'is_locked'  => ($r === 'admin' && in_array($permKey, ['menu_hak_akses', 'menu_dashboard'])),
                        ];
                    }

                    $tableModules[] = [
                        'key' => $permKey,
                        'label' => $perm['label'],
                        'icon' => $perm['icon'] ?? 'fa-cube',
                        'group' => $groupName,
                        'roles' => $rolesData,
                    ];
                }
            }
            $counts['global'] = count($tableModules);
        } else {
            // Tampilan Datatable Per-Peran Spesifik: granular CRUD
            $savedPermissions = $allPermissions->get($activeRole)?->keyBy('permission_key') ?? collect();
            foreach ($permissionsConfig as $groupName => $items) {
                if (!in_array($groupName, $groups)) {
                    $groups[] = $groupName;
                }
                foreach ($items as $permKey => $perm) {
                    $saved = $savedPermissions->get($permKey);
                    $isDefault = in_array($activeRole, $perm['roles'] ?? []);
                    $isIncluded = $saved ? true : (!$isSystemConfigured && $isDefault);

                    if ($isIncluded) {
                        $tableModules[] = [
                            'key' => $permKey,
                            'label' => $perm['label'],
                            'icon' => $perm['icon'] ?? 'fa-cube',
                            'group' => $groupName,
                            'can_create' => $saved ? (bool) $saved->can_create : ($activeRole === 'admin'),
                            'can_read' => $saved ? (bool) $saved->can_read : $isDefault,
                            'can_update' => $saved ? (bool) $saved->can_update : ($activeRole === 'admin'),
                            'can_delete' => $saved ? (bool) $saved->can_delete : ($activeRole === 'admin'),
                            'is_locked' => ($activeRole === 'admin' && in_array($permKey, ['menu_hak_akses', 'menu_dashboard'])),
                            'is_custom' => (bool) $saved && !$isDefault,
                        ];
                    }
                }
            }
        }

        // Modul yang tersedia untuk ditambahkan ke peran spesifik
        $availableModulesToAdd = [];
        if ($activeRole !== 'global') {
            $allSystemModules = RolePermission::getAllSystemModules();
            $savedPermissions = $allPermissions->get($activeRole)?->keyBy('permission_key') ?? collect();
            foreach ($allSystemModules as $k => $mod) {
                $saved = $savedPermissions->get($k);
                if (!$saved || !$saved->can_read) {
                    $group = $mod['group'] ?? 'Lainnya';
                    $availableModulesToAdd[$group][] = $mod;
                }
            }
            ksort($availableModulesToAdd);
            foreach ($availableModulesToAdd as $grp => &$items) {
                usort($items, fn($a, $b) => strcasecmp($a['label'] ?? '', $b['label'] ?? ''));
            }
            unset($items);
        }

        $roles = [
            'global' => ['name' => 'Semua Peran (Global)', 'icon' => 'fa-globe', 'color' => 'var(--primary)'],
            'admin' => ['name' => 'Administrator', 'icon' => 'fa-user-shield', 'color' => '#6366f1'],
            'guru' => ['name' => 'Guru', 'icon' => 'fa-chalkboard-user', 'color' => '#10b981'],
            'tendik' => ['name' => 'Tenaga Kependidikan', 'icon' => 'fa-id-badge', 'color' => '#0ea5e9'],
            'peserta_didik' => ['name' => 'Peserta Didik', 'icon' => 'fa-user-graduate', 'color' => '#f59e0b'],
        ];

        return view('dashboard.hak-akses', compact(
            'activeRole', 'roles', 'permissionsConfig', 'tableModules',
            'groups', 'counts', 'availableModulesToAdd'
        ));
    }

    /**
     * Toggle satu hak akses (AJAX)
     */
    public function toggle(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $targetRole = $request->input('role');
        $permissionKey = $request->input('permission_key');
        $isAllowed = filter_var($request->input('is_allowed'), FILTER_VALIDATE_BOOLEAN);
        $action = $request->input('action', 'read'); // 'create', 'read', 'update', 'delete'

        if (!in_array($targetRole, ['admin', 'guru', 'tendik', 'peserta_didik']) || empty($permissionKey)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter tidak valid'], 422);
        }

        // Proteksi agar admin tidak mematikan hak akses menu_hak_akses untuk diri sendiri
        if ($targetRole === 'admin' && $permissionKey === 'menu_hak_akses' && !$isAllowed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Modul Hak Akses untuk Administrator tidak dapat dinonaktifkan demi keselamatan sistem.',
            ], 422);
        }

        // Ambil atau buat record izin
        $perm = RolePermission::firstOrCreate(
            ['role' => $targetRole, 'permission_key' => $permissionKey],
            [
                'is_allowed' => true,
                'can_create' => false,
                'can_read' => true,
                'can_update' => false,
                'can_delete' => false,
            ]
        );

        if ($action && in_array($action, ['create', 'read', 'update', 'delete'])) {
            $column = 'can_' . $action;
            $perm->{$column} = $isAllowed;

            // Jika create/update/delete dinyalakan, otomatis read dan is_allowed juga nyala
            if ($isAllowed && in_array($action, ['create', 'update', 'delete'])) {
                $perm->can_read = true;
                $perm->is_allowed = true;
            }

            // Jika read dimatikan, matikan seluruh aksi operasional terkait
            if ($action === 'read') {
                $perm->is_allowed = $isAllowed;
                if (!$isAllowed) {
                    $perm->can_create = false;
                    $perm->can_update = false;
                    $perm->can_delete = false;
                } else {
                    // Jika modul operasional guru dinyalakan, berikan izin create & update default
                    if ($targetRole === 'guru' && in_array($permissionKey, ['menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_presensi_peserta_didik'], true)) {
                        $perm->can_create = true;
                        $perm->can_update = true;
                    }
                }
            }

            // Jika semua 4 aksi false, set is_allowed = false
            if (!$perm->can_create && !$perm->can_read && !$perm->can_update && !$perm->can_delete) {
                $perm->is_allowed = false;
            }
        }

        $perm->save();

        // Sinkronisasi cascading induk-anak untuk modul hierarkis (Wali Kelas, Persuratan, Kesiswaan)
        if ($action === 'read') {
            if ($permissionKey === 'menu_wali_kelas') {
                RolePermission::where('role', $targetRole)
                    ->whereIn('permission_key', [
                        'menu_wali_kelas_aktif',
                        'menu_wali_kelas_tidak_aktif',
                        'menu_wali_kelas_presensi',
                        'menu_wali_kelas_jadwal',
                    ])
                    ->update([
                        'is_allowed' => $isAllowed,
                        'can_read' => $isAllowed,
                    ]);
            } elseif (in_array($permissionKey, ['menu_wali_kelas_aktif', 'menu_wali_kelas_tidak_aktif', 'menu_wali_kelas_presensi', 'menu_wali_kelas_jadwal'], true) && $isAllowed) {
                RolePermission::where('role', $targetRole)
                    ->where('permission_key', 'menu_wali_kelas')
                    ->update(['is_allowed' => true, 'can_read' => true]);
            }

            if ($permissionKey === 'menu_persuratan') {
                RolePermission::where('role', $targetRole)
                    ->whereIn('permission_key', ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'])
                    ->update([
                        'is_allowed' => $isAllowed,
                        'can_read' => $isAllowed,
                    ]);
            } elseif (in_array($permissionKey, ['menu_surat_masuk', 'menu_surat_keluar', 'menu_pengaturan_persuratan'], true) && $isAllowed) {
                RolePermission::where('role', $targetRole)
                    ->where('permission_key', 'menu_persuratan')
                    ->update(['is_allowed' => true, 'can_read' => true]);
            }

            if ($permissionKey === 'menu_kesiswaan') {
                RolePermission::where('role', $targetRole)
                    ->whereIn('permission_key', ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'])
                    ->update([
                        'is_allowed' => $isAllowed,
                        'can_read' => $isAllowed,
                    ]);
            } elseif (in_array($permissionKey, ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan_administrasi', 'menu_kesiswaan_kedisiplinan', 'menu_kesiswaan_kegiatan', 'menu_kesiswaan_prestasi'], true) && $isAllowed) {
                RolePermission::where('role', $targetRole)
                    ->where('permission_key', 'menu_kesiswaan')
                    ->update(['is_allowed' => true, 'can_read' => true]);
            }
        }

        RolePermission::clearRuntimeCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Izin ' . strtoupper($action) . ' berhasil diperbarui.',
            'data' => [
                'role' => $targetRole,
                'permission_key' => $permissionKey,
                'action' => $action,
                'is_allowed' => (bool) $perm->is_allowed,
                'can_create' => (bool) $perm->can_create,
                'can_read' => (bool) $perm->can_read,
                'can_update' => (bool) $perm->can_update,
                'can_delete' => (bool) $perm->can_delete,
            ],
        ]);
    }

    /**
     * Sinkronkan modul sistem secara manual (AJAX)
     */
    public function sync(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $count = RolePermission::syncAvailablePermissions();

        return response()->json([
            'status' => 'success',
            'message' => $count > 0
                ? "Sinkronisasi berhasil! {$count} modul baru ditambahkan ke database."
                : 'Sinkronisasi berhasil! Seluruh modul sudah mutakhir.',
            'count' => $count,
        ]);
    }

    /**
     * Simpan penugasan tugas tambahan PTK dengan validasi anti-duplikasi
     */
    public function storeTugasTambahan(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'ptk_id' => 'required|string',
            'tugas_tambahan_id' => 'required|integer|exists:ref_tugas_tambahan,id',
            'nomor_sk' => 'nullable|string|max:100',
            'tmt_tugas' => 'nullable|date',
            'tst_tugas' => 'nullable|date',
            'rombel_id' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $ptkId = $request->input('ptk_id');
        $tugasId = (int) $request->input('tugas_tambahan_id');
        $rombelId = $request->input('rombel_id');

        $tugas = DB::table('ref_tugas_tambahan')->where('id', $tugasId)->first();
        if (!$tugas) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tambahan tidak ditemukan.'], 422);
        }

        // 1. Cek apakah PTK ini sudah mengemban tugas tambahan yang sama
        $existingPtkDuty = \App\Models\PtkTugasTambahan::where('ptk_id', $ptkId)
            ->where('tugas_tambahan_id', $tugasId)
            ->where('is_active', true)
            ->first();

        if ($existingPtkDuty) {
            return response()->json([
                'status' => 'error',
                'message' => "Personel ini sudah memiliki tugas tambahan sebagai {$tugas->nama}.",
            ], 422);
        }

        // 2. Khusus Wali Kelas: Validasi kelas tidak boleh memiliki lebih dari 1 wali kelas aktif
        if ($tugas->kode === 'WALI_KELAS') {
            if (empty($rombelId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silakan pilih rombongan belajar (kelas) untuk penugasan Wali Kelas.',
                ], 422);
            }

            $existingClassWali = \App\Models\PtkTugasTambahan::where('tugas_tambahan_id', $tugasId)
                ->where('rombel_id', $rombelId)
                ->where('is_active', true)
                ->first();

            if ($existingClassWali) {
                $ptkName = DB::table('gtk')->where('ptk_id', $existingClassWali->ptk_id)->value('nama') ?? 'Guru lain';
                return response()->json([
                    'status' => 'error',
                    'message' => "Kelas tersebut sudah memiliki Wali Kelas aktif ({$ptkName}).",
                ], 422);
            }
        }

        // 3. Khusus Jabatan Tunggal (Waka, Kepala TAS, Operator): hanya 1 orang aktif di sekolah
        $singleRoles = ['WAKA_KURIKULUM', 'WAKA_KESISWAAN', 'WAKA_HUBIN', 'WAKA_SARPRAS', 'KEPALA_TAS', 'OPERATOR_DAPODIK'];
        if (in_array($tugas->kode, $singleRoles, true)) {
            $existingSingle = \App\Models\PtkTugasTambahan::where('tugas_tambahan_id', $tugasId)
                ->where('is_active', true)
                ->first();

            if ($existingSingle) {
                $occupantName = DB::table('gtk')->where('ptk_id', $existingSingle->ptk_id)->value('nama')
                    ?? (DB::table('pengguna')->where('ptk_id', $existingSingle->ptk_id)->value('nama') ?? 'Personel lain');
                return response()->json([
                    'status' => 'error',
                    'message' => "Jabatan {$tugas->nama} saat ini sudah dijabat oleh {$occupantName}.",
                ], 422);
            }
        }

        $userId = DB::table('pengguna')->where('ptk_id', $ptkId)->value('pengguna_id');

        \App\Models\PtkTugasTambahan::create([
            'user_id' => $userId,
            'ptk_id' => $ptkId,
            'tugas_tambahan_id' => $tugasId,
            'nomor_sk' => $request->input('nomor_sk'),
            'tmt_tugas' => $request->input('tmt_tugas'),
            'tst_tugas' => $request->input('tst_tugas'),
            'rombel_id' => $rombelId,
            'keterangan' => $request->input('keterangan'),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Penugasan tugas tambahan berhasil disimpan.',
        ]);
    }

    /**
     * Hapus penugasan tugas tambahan
     */
    public function destroyTugasTambahan(int $id): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        \App\Models\PtkTugasTambahan::where('id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Penugasan tugas tambahan berhasil dihapus.',
        ]);
    }

    /**
     * Pemicu sinkronisasi manual Wali Kelas dari Dapodik
     */
    public function syncWaliKelas(): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $count = \App\Models\PtkTugasTambahan::syncWaliKelasFromDapodik();

        return response()->json([
            'status' => 'success',
            'message' => "Sinkronisasi Wali Kelas dari Dapodik berhasil ({$count} rombel tersinkron).",
        ]);
    }

    /**
     * Simpan perubahan hak akses modul untuk Tugas Tambahan (AJAX)
     */
    public function updateDutyPermissions(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $dutyId = $request->input('duty_id');
        $permissions = $request->input('permissions', []);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        if (empty($dutyId)) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tambahan wajib dipilih.'], 422);
        }

        $duty = DB::table('ref_tugas_tambahan')->where('id', $dutyId)->orWhere('kode', $dutyId)->first();
        if (!$duty) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tambahan tidak ditemukan.'], 404);
        }

        $success = RolePermission::updateDutyPermissions($duty->id, $permissions);
        if ($success) {
            return response()->json([
                'status' => 'success',
                'message' => "Hak akses modul untuk tugas tambahan '{$duty->nama}' berhasil disimpan.",
                'granted_permissions' => $permissions,
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui hak akses tugas tambahan.'], 500);
    }

    /**
     * Toggle satu hak akses modul untuk Tugas Tambahan (AJAX)
     */
    public function toggleDutyPermission(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $dutyId = $request->input('duty_id');
        $permissionKey = $request->input('permission_key');
        $isAllowed = filter_var($request->input('is_allowed'), FILTER_VALIDATE_BOOLEAN);

        if (empty($dutyId) || empty($permissionKey)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter tidak valid'], 422);
        }

        $duty = \App\Models\RefTugasTambahan::where('id', $dutyId)->orWhere('kode', $dutyId)->first();
        if (!$duty) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tambahan tidak ditemukan.'], 404);
        }

        $updatedPerms = RolePermission::toggleDutyPermission($duty->id, $permissionKey, $isAllowed);

        return response()->json([
            'status' => 'success',
            'message' => "Hak akses modul '{$permissionKey}' untuk {$duty->nama} berhasil " . ($isAllowed ? 'diaktifkan' : 'dinonaktifkan') . ".",
            'duty_id' => $duty->id,
            'duty_kode' => $duty->kode,
            'permission_key' => $permissionKey,
            'is_allowed' => $isAllowed,
            'count' => count($updatedPerms),
        ]);
    }

    /**
     * Reset hak akses:
     * - Jika request membawa parameter role='global', reset tugas tambahan yang dipilih (atau seluruh tugas tambahan).
     * - Jika request membawa parameter role spesifik (admin, guru, tendik, peserta_didik), hanya peran tersebut yang direset.
     * - Jika parameter role kosong, seluruh peran dan tugas tambahan direset secara serentak.
     */
    public function resetDefault(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $targetRole = $request->input('role');
        $validRoles = ['admin', 'guru', 'tendik', 'peserta_didik'];

        if ($targetRole === 'global' || empty($targetRole)) {
            foreach ($validRoles as $r) {
                RolePermission::resetDefaultPermissions($r);
            }
            RolePermission::resetDutyDefaults(null);

            return response()->json([
                'status' => 'success',
                'message' => 'Hak akses seluruh peran berhasil direset ke standar baku default kelompoknya masing-masing.',
            ]);
        }

        if (in_array($targetRole, $validRoles, true)) {
            RolePermission::resetDefaultPermissions($targetRole);

            $roleNames = [
                'admin' => 'Administrator',
                'guru' => 'Guru',
                'tendik' => 'Tenaga Kependidikan',
                'peserta_didik' => 'Peserta Didik',
            ];
            $roleName = $roleNames[$targetRole] ?? ucfirst($targetRole);

            return response()->json([
                'status' => 'success',
                'message' => "Hak akses peran {$roleName} berhasil direset ke standar default kelompoknya.",
            ]);
        }

        RolePermission::resetDefaultPermissions(null);
        RolePermission::resetDutyDefaults(null);

        return response()->json([
            'status' => 'success',
            'message' => 'Hak akses seluruh peran dan tugas tambahan berhasil direset ke standar baku kelompoknya masing-masing.',
        ]);
    }

    /**
     * Tambahkan modul sistem ke peran tertentu (AJAX)
     */
    public function addModule(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $targetRole = $request->input('role');
        $permissionKey = trim($request->input('permission_key', ''));
        $customName = trim($request->input('custom_name', ''));

        // Jika memilih untuk mendaftarkan modul baru / mendatang
        if ($permissionKey === '__NEW_CUSTOM_MODULE__' || (!empty($customName) && empty($permissionKey))) {
            if (empty($customName)) {
                return response()->json(['status' => 'error', 'message' => 'Nama modul baru wajib diisi.'], 422);
            }
            $cleanSlug = \Illuminate\Support\Str::slug($customName, '_');
            $permissionKey = 'menu_' . $cleanSlug;
        }

        if (!in_array($targetRole, ['admin', 'guru', 'tendik', 'peserta_didik']) || empty($permissionKey)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter tidak valid'], 422);
        }

        $config = RolePermission::getPermissionConfig($permissionKey);
        $moduleLabel = !empty($customName) ? $customName : ($config['label'] ?? ucwords(str_replace(['menu_', '_'], ['', ' '], $permissionKey)));

        $canCreate = in_array($targetRole, ['admin', 'guru', 'tendik']);
        $canUpdate = in_array($targetRole, ['admin', 'guru', 'tendik']);
        $canDelete = $targetRole === 'admin';

        // 1. Daftarkan/aktifkan modul untuk peran target
        RolePermission::updateOrCreate(
            ['role' => $targetRole, 'permission_key' => $permissionKey],
            [
                'is_allowed' => true,
                'can_create' => $canCreate,
                'can_read' => true,
                'can_update' => $canUpdate,
                'can_delete' => $canDelete,
                'updated_at' => now(),
            ]
        );

        // 2. Pastikan Administrator selalu memiliki hak kontrol penuh atas modul baru ini
        if ($targetRole !== 'admin') {
            RolePermission::firstOrCreate(
                ['role' => 'admin', 'permission_key' => $permissionKey],
                [
                    'is_allowed' => true,
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'updated_at' => now(),
                ]
            );
        }

        RolePermission::clearRuntimeCache();

        return response()->json([
            'status' => 'success',
            'message' => "Modul '{$moduleLabel}' berhasil didaftarkan ke peran " . ucfirst(str_replace('_', ' ', $targetRole)) . " dan kini aktif di sidebar.",
        ]);
    }

    /**
     * Hapus modul dari peran tertentu (AJAX)
     */
    public function removeModule(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $targetRole = $request->input('role');
        $permissionKey = $request->input('permission_key');

        if ($targetRole === 'admin' && in_array($permissionKey, ['menu_dashboard', 'menu_hak_akses'])) {
            return response()->json(['status' => 'error', 'message' => 'Modul inti Administrator tidak dapat dihapus demi keamanan sistem.'], 422);
        }

        $config = RolePermission::getPermissionConfig($permissionKey);
        $label = $config['label'] ?? $permissionKey;

        // Hapus modul dari peran target
        RolePermission::where('role', $targetRole)->where('permission_key', $permissionKey)->delete();

        // Jika modul kustom (bukan bawaan sistem) dan dihapus dari admin, hapus juga dari seluruh peran lain
        $basePerms = RolePermission::getBasePermissions();
        $isBase = false;
        foreach ($basePerms as $grp => $items) {
            if (isset($items[$permissionKey])) {
                $isBase = true;
                break;
            }
        }
        if (!$isBase && $targetRole === 'admin') {
            RolePermission::where('permission_key', $permissionKey)->delete();
        }

        RolePermission::clearRuntimeCache();

        return response()->json([
            'status' => 'success',
            'message' => "Modul '{$label}' berhasil dihapus dari peran " . ucfirst(str_replace('_', ' ', $targetRole)) . " dan disembunyikan dari sidebar.",
            'permission_key' => $permissionKey,
        ]);
    }
}
