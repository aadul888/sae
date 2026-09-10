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
            return redirect()->route('dashboard.' . ($role ?: 'login'))->with('error', 'Akses dibatasi hanya untuk Administrator.');
        }

        $activeRole = $request->query('role', 'admin');
        if (!in_array($activeRole, ['admin', 'guru', 'tendik', 'peserta_didik'])) {
            $activeRole = 'admin';
        }

        // Sinkronisasi otomatis modul baru yang didaftarkan pada kode sistem
        RolePermission::syncAvailablePermissions();

        $permissionsConfig = RolePermission::getAvailablePermissions();

        // Ambil seluruh data izin tersimpan dari database untuk role aktif
        $savedPermissions = RolePermission::where('role', $activeRole)
            ->get()
            ->keyBy('permission_key');

        // Bentuk data flat per modul untuk Datatable
        $tableModules = [];
        $groups = [];
        foreach ($permissionsConfig as $groupName => $items) {
            $groups[] = $groupName;
            foreach ($items as $permKey => $perm) {
                if (in_array($activeRole, $perm['roles'] ?? [])) {
                    $saved = $savedPermissions->get($permKey);
                    $tableModules[] = [
                        'key' => $permKey,
                        'label' => $perm['label'],
                        'icon' => $perm['icon'],
                        'group' => $groupName,
                        'can_create' => $saved ? (bool) $saved->can_create : ($activeRole === 'admin'),
                        'can_read' => $saved ? (bool) $saved->can_read : true,
                        'can_update' => $saved ? (bool) $saved->can_update : ($activeRole === 'admin'),
                        'can_delete' => $saved ? (bool) $saved->can_delete : ($activeRole === 'admin'),
                        'is_locked' => ($activeRole === 'admin' && $permKey === 'menu_hak_akses'),
                    ];
                }
            }
        }

        // Ringkasan hitungan item aktif yang relevan untuk masing-masing role
        $counts = [];
        foreach (['admin', 'guru', 'tendik', 'peserta_didik'] as $r) {
            $relevantKeys = [];
            foreach ($permissionsConfig as $items) {
                foreach ($items as $k => $p) {
                    if (in_array($r, $p['roles'] ?? [])) {
                        $relevantKeys[] = $k;
                    }
                }
            }
            $counts[$r] = RolePermission::where('role', $r)
                ->whereIn('permission_key', $relevantKeys)
                ->where('is_allowed', true)
                ->where('can_read', true)
                ->count();
        }

        return view('dashboard.hak-akses', compact('activeRole', 'permissionsConfig', 'savedPermissions', 'tableModules', 'groups', 'counts'));
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
                }
            }

            // Jika semua 4 aksi false, set is_allowed = false
            if (!$perm->can_create && !$perm->can_read && !$perm->can_update && !$perm->can_delete) {
                $perm->is_allowed = false;
            }
        }

        $perm->save();

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
     * Reset hak akses role ke default bawaan sistem
     */
    public function resetDefault(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $targetRole = $request->input('role');
        if (!in_array($targetRole, ['admin', 'guru', 'tendik', 'peserta_didik'])) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak valid'], 422);
        }

        // Hapus konfigurasi lama role tersebut dan sinkronkan ulang bawaan
        RolePermission::where('role', $targetRole)->delete();
        RolePermission::syncAvailablePermissions();

        return response()->json([
            'status' => 'success',
            'message' => "Hak akses peran {$targetRole} telah direset ke bawaan sistem.",
        ]);
    }
}
