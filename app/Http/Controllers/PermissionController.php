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
        if (!in_array($activeRole, ['admin', 'guru', 'peserta_didik'])) {
            $activeRole = 'admin';
        }

        $permissionsConfig = RolePermission::getAvailablePermissions();

        // Ambil seluruh data izin tersimpan dari database untuk role aktif
        $savedPermissions = RolePermission::where('role', $activeRole)
            ->get()
            ->keyBy('permission_key');

        // Ringkasan hitungan untuk masing-masing role
        $counts = [
            'admin' => RolePermission::where('role', 'admin')->where('is_allowed', true)->count(),
            'guru' => RolePermission::where('role', 'guru')->where('is_allowed', true)->count(),
            'peserta_didik' => RolePermission::where('role', 'peserta_didik')->where('is_allowed', true)->count(),
        ];

        return view('dashboard.hak-akses', compact('activeRole', 'permissionsConfig', 'savedPermissions', 'counts'));
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
        $action = $request->input('action'); // null jika toggle akses menu keseluruhan, atau 'create','read','update','delete'

        if (!in_array($targetRole, ['admin', 'guru', 'peserta_didik']) || empty($permissionKey)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter tidak valid'], 422);
        }

        // Proteksi agar admin tidak mematikan hak akses menu_hak_akses untuk diri sendiri
        if ($targetRole === 'admin' && $permissionKey === 'menu_hak_akses' && !$isAllowed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Modul Hak Akses untuk Administrator tidak dapat dinonaktifkan demi keselamatan sistem.',
            ], 422);
        }

        // Jika mengubah aksi CRUD spesifik
        if ($action && in_array($action, ['create', 'read', 'update', 'delete'])) {
            $column = 'can_' . $action;
            $perm = RolePermission::firstOrCreate(
                ['role' => $targetRole, 'permission_key' => $permissionKey],
                ['is_allowed' => true, 'can_create' => false, 'can_read' => true, 'can_update' => false, 'can_delete' => false]
            );
            $perm->{$column} = $isAllowed;
            $perm->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Izin ' . strtoupper($action) . ' berhasil diperbarui.',
                'data' => [
                    'role' => $targetRole,
                    'permission_key' => $permissionKey,
                    'action' => $action,
                    'is_allowed' => $isAllowed,
                ],
            ]);
        }

        // Toggle akses menu keseluruhan
        $perm = RolePermission::firstOrNew(['role' => $targetRole, 'permission_key' => $permissionKey]);
        $perm->is_allowed = $isAllowed;
        if (!$perm->exists) {
            $perm->can_create = ($targetRole === 'admin');
            $perm->can_read = true;
            $perm->can_update = ($targetRole === 'admin');
            $perm->can_delete = ($targetRole === 'admin');
        }
        $perm->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Izin berhasil diperbarui.',
            'data' => [
                'role' => $targetRole,
                'permission_key' => $permissionKey,
                'is_allowed' => $isAllowed,
            ],
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
        if (!in_array($targetRole, ['admin', 'guru', 'peserta_didik'])) {
            return response()->json(['status' => 'error', 'message' => 'Role tidak valid'], 422);
        }

        // Hapus konfigurasi lama role tersebut
        RolePermission::where('role', $targetRole)->delete();

        $defaults = [];
        $now = now();

        if ($targetRole === 'admin') {
            $keys = [
                'menu_dashboard',
                'menu_pengguna',
                'menu_guru',
                'menu_peserta_didik',
                'menu_rfid',
                'menu_dapodik',
                'menu_update',
                'menu_hak_akses',
                'menu_pengumuman',
                'menu_pengaturan',
                'fitur_pengguna_edit',
                'fitur_pengguna_hapus',
                'fitur_pengguna_reset',
                'fitur_dapodik_sync',
                'fitur_system_update'
            ];
            foreach ($keys as $k) {
                $defaults[] = [
                    'role' => 'admin',
                    'permission_key' => $k,
                    'is_allowed' => true,
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        } elseif ($targetRole === 'guru') {
            $keys = ['menu_dashboard', 'menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_penilaian', 'menu_presensi_peserta_didik', 'menu_pengumuman'];
            foreach ($keys as $k) {
                $isCrud = in_array($k, ['menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_penilaian', 'menu_presensi_peserta_didik']);
                $defaults[] = [
                    'role' => 'guru',
                    'permission_key' => $k,
                    'is_allowed' => true,
                    'can_create' => $isCrud,
                    'can_read' => true,
                    'can_update' => $isCrud,
                    'can_delete' => false,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        } else {
            $keys = ['menu_dashboard', 'menu_riwayat_rfid', 'menu_jadwal_pelajaran', 'menu_rapor', 'menu_validasi_berkas', 'menu_pengumuman'];
            foreach ($keys as $k) {
                $defaults[] = [
                    'role' => 'peserta_didik',
                    'permission_key' => $k,
                    'is_allowed' => true,
                    'can_create' => false,
                    'can_read' => true,
                    'can_update' => false,
                    'can_delete' => false,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }

        DB::table('role_permissions')->insert($defaults);

        return response()->json([
            'status' => 'success',
            'message' => "Hak akses peran {$targetRole} telah direset ke bawaan sistem.",
        ]);
    }
}
