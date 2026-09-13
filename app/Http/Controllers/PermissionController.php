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

        $activeRole = $request->query('role', 'admin');
        if (!in_array($activeRole, ['admin', 'guru', 'tendik', 'peserta_didik', 'tugas_tambahan'])) {
            $activeRole = 'admin';
        }

        // Sinkronisasi otomatis modul baru yang didaftarkan pada kode sistem
        RolePermission::syncAvailablePermissions();

        $permissionsConfig = RolePermission::getAvailablePermissions();

        // Ambil data tugas tambahan jika tab tugas_tambahan aktif
        $tugasTambahanList = [];
        $refTugasList = [];
        $ptkList = [];
        $rombelList = [];
        if ($activeRole === 'tugas_tambahan') {
            $tugasTambahanList = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('pengguna as p', 'ptt.user_id', '=', 'p.pengguna_id')
                ->leftJoin('rombongan_belajar as rb', 'ptt.rombel_id', '=', 'rb.rombongan_belajar_id')
                ->select(
                    'ptt.id',
                    'ptt.user_id',
                    'ptt.ptk_id',
                    'ptt.nomor_sk',
                    'ptt.tmt_tugas',
                    'ptt.tst_tugas',
                    'ptt.keterangan',
                    'ptt.is_active',
                    'rtt.kode as tugas_kode',
                    'rtt.nama as tugas_nama',
                    'rtt.kelompok as tugas_kelompok',
                    'rtt.bidang as tugas_bidang',
                    'rtt.ekuivalensi_jam',
                    'rtt.icon as tugas_icon',
                    DB::raw("COALESCE(gtk.nama, p.nama, 'Belum Terhubung') as ptk_nama"),
                    DB::raw("COALESCE(gtk.nip, '-') as ptk_nip"),
                    'rb.nama as rombel_nama'
                )
                ->orderBy('rtt.kelompok')
                ->orderBy('rtt.bidang')
                ->orderBy('ptk_nama')
                ->get();

            $refTugasList = DB::table('ref_tugas_tambahan')->where('is_active', true)->orderBy('kelompok')->orderBy('nama')->get();
            $ptkList = DB::table('gtk')->select('ptk_id', 'nama', 'nip', 'jenis_ptk_id_str')->orderBy('nama')->get();
            $rombelList = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->select('rombongan_belajar_id', 'nama')->orderBy('nama')->get();
        }

        // Ambil seluruh data izin tersimpan dari database untuk role aktif
        $savedPermissions = RolePermission::where('role', $activeRole)
            ->get()
            ->keyBy('permission_key');
        $hasRoleRecords = $savedPermissions->isNotEmpty();

        // Bentuk data flat per modul untuk Datatable
        $tableModules = [];
        $groups = [];
        $existingKeys = [];

        foreach ($permissionsConfig as $groupName => $items) {
            $groups[] = $groupName;
            foreach ($items as $permKey => $perm) {
                $saved = $savedPermissions->get($permKey);
                $isDefault = in_array($activeRole, $perm['roles'] ?? []);

                // Modul muncul jika tersimpan di database untuk role ini.
                // Jika role belum pernah memiliki konfigurasi di DB, gunakan fallback default.
                $isIncluded = $saved ? true : (!$hasRoleRecords && $isDefault);

                if ($isIncluded) {
                    $existingKeys[$permKey] = true;
                    $tableModules[] = [
                        'key' => $permKey,
                        'label' => $perm['label'],
                        'icon' => $perm['icon'],
                        'group' => $groupName,
                        'can_create' => $saved ? (bool) $saved->can_create : ($activeRole === 'admin'),
                        'can_read' => $saved ? (bool) $saved->can_read : $isDefault,
                        'can_update' => $saved ? (bool) $saved->can_update : ($activeRole === 'admin'),
                        'can_delete' => $saved ? (bool) $saved->can_delete : ($activeRole === 'admin'),
                        'is_locked' => ($activeRole === 'admin' && in_array($permKey, ['menu_hak_akses', 'menu_dashboard'])),
                        'is_custom' => (bool)$saved && !$isDefault,
                    ];
                }
            }
        }

        // Modul sistem yang belum ditambahkan ke role aktif ini (harus tetap berupa associative array dengan key modul)
        $allSystemModules = RolePermission::getAllSystemModules();
        $availableModulesToAdd = [];
        foreach ($allSystemModules as $k => $mod) {
            if (!isset($existingKeys[$k])) {
                $availableModulesToAdd[$k] = $mod;
            }
        }

        // Ringkasan hitungan item aktif yang relevan untuk masing-masing role
        $counts = [];
        foreach (['admin', 'guru', 'tendik', 'peserta_didik'] as $r) {
            $counts[$r] = RolePermission::where('role', $r)
                ->where('is_allowed', true)
                ->where('can_read', true)
                ->count();
        }
        $counts['tugas_tambahan'] = DB::table('ptk_tugas_tambahan')->where('is_active', true)->count();

        return view('dashboard.hak-akses', compact('activeRole', 'permissionsConfig', 'savedPermissions', 'tableModules', 'groups', 'counts', 'tugasTambahanList', 'refTugasList', 'ptkList', 'rombelList', 'availableModulesToAdd'));
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
        $permissionKey = $request->input('permission_key');

        if (!in_array($targetRole, ['admin', 'guru', 'tendik', 'peserta_didik']) || empty($permissionKey)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter tidak valid'], 422);
        }

        $config = RolePermission::getPermissionConfig($permissionKey);
        if (!$config) {
            return response()->json(['status' => 'error', 'message' => 'Modul sistem tidak dikenali'], 404);
        }

        $canCreate = in_array($targetRole, ['admin', 'guru', 'tendik']);
        $canUpdate = in_array($targetRole, ['admin', 'guru', 'tendik']);
        $canDelete = $targetRole === 'admin';

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

        return response()->json([
            'status' => 'success',
            'message' => "Modul '{$config['label']}' berhasil ditambahkan ke peran " . ucfirst(str_replace('_', ' ', $targetRole)) . " dan kini aktif di sidebar.",
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

        RolePermission::where('role', $targetRole)->where('permission_key', $permissionKey)->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Modul '{$label}' berhasil dihapus dari peran " . ucfirst(str_replace('_', ' ', $targetRole)) . " dan disembunyikan dari sidebar.",
        ]);
    }
}
