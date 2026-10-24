<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\ImageOptimizerService;
use App\Services\PesertaDidikFotoService;
use App\Services\PesertaDidikImportService;
use App\Services\PesertaDidikQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesertaDidikAktifController extends Controller
{
    public const SORTABLE = PesertaDidikQueryService::SORTABLE;

    public function __construct(
        protected PesertaDidikQueryService $queryService,
        protected PesertaDidikFotoService $fotoService,
        protected PesertaDidikImportService $importService
    ) {}

    /**
     * Tampilan Utama Manajemen Peserta Didik Aktif.
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $isKoordinator = ($role === 'peserta_didik') && RolePermission::isKoordinator($user);
        $hasMenuAccess = RolePermission::canAccess($user, 'menu_peserta_didik_aktif') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif');

        if (($role === 'peserta_didik' && !$isKoordinator) || !$hasMenuAccess) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Peserta Didik Aktif dinonaktifkan.');
        }

        $data = $this->queryService->getIndexData($request, $user);

        return view('dashboard.peserta-didik-aktif', $data);
    }

    /**
     * Detail peserta didik via JSON untuk modal.
     */
    public function show(Request $request, string|int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Hanya Administrator yang memiliki wewenang melihat data lengkap peserta didik.'], 403);
        }

        $data = $this->queryService->getShowData($id);
        if (!$data) {
            return response()->json(['status' => 'error', 'message' => 'Peserta Didik tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    /**
     * Unggah dan auto-kompresi pasfoto peserta didik (khusus format PNG).
     */
    public function uploadFoto(Request $request, ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);

        if ((!$canUpdate && !$canCreate) && !$isAdmin && !$isWaliOrAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunggah pasfoto peserta didik.'
            ], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string|max:50',
            'foto' => 'required|file|mimes:png|max:5120',
        ], [
            'peserta_didik_id.required' => 'ID peserta didik wajib disertakan.',
            'foto.required' => 'File foto wajib diunggah.',
            'foto.mimes' => 'Format foto harus berupa PNG (.png) untuk kebutuhan kartu pelajar digital.',
            'foto.max' => 'Ukuran file foto maksimal adalah 5 MB.',
        ]);

        $res = $this->fotoService->uploadFoto($request, $optimizer, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Hapus pasfoto peserta didik.
     */
    public function deleteFoto(Request $request, string|int $id, ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canDelete = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'delete') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'delete');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);

        if (!$canDelete && !$isAdmin && !$isWaliOrAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menghapus pasfoto peserta didik.'
            ], 403);
        }

        $res = $this->fotoService->deleteFoto($id, $optimizer, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Dapatkan daftar anggota rombel untuk modal batch photo.
     */
    public function getRombelMembers(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Hanya Administrator yang diizinkan mengakses data rombel kelas di menu Manajemen Data.'
            ], 403);
        }

        $rombelName = trim($request->get('rombel', ''));
        if (!$rombelName) {
            return response()->json(['status' => 'error', 'message' => 'Rombel kelas wajib dipilih.'], 422);
        }

        $res = $this->queryService->getRombelMembers($rombelName);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Menerima upload foto masal atau satu per satu secara batch dari antrean async klien.
     */
    public function bulkUploadFoto(Request $request, ImageOptimizerService $optimizer)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create') || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);

        if ((!$canUpdate && !$canCreate) && !$isAdmin && !$isWaliOrAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunggah pasfoto masal.'
            ], 403);
        }

        $res = $this->fotoService->bulkUploadFoto($request, $optimizer, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Reset password akun peserta didik ke default (NISN).
     */
    public function resetPassword(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $canAccessUpdate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update')
            || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccessUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mereset password akun peserta didik.',
            ], 403);
        }

        $pdId = $request->input('peserta_didik_id');
        if (!$pdId) {
            return response()->json(['status' => 'error', 'message' => 'ID Peserta Didik wajib disertakan.'], 400);
        }

        $res = $this->queryService->resetPassword($pdId);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Tunjuk atau cabut wewenang peserta didik sebagai Koordinator Kelas.
     */
    public function toggleKoordinator(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $canAccessUpdate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'update')
            || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccessUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menunjuk Koordinator Kelas.',
            ], 403);
        }

        $pdId = $request->input('peserta_didik_id');
        if (!$pdId) {
            return response()->json(['status' => 'error', 'message' => 'ID Peserta Didik wajib disertakan.'], 400);
        }

        $res = $this->queryService->toggleKoordinator($pdId, $request->input('jabatan'));

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Cetak Data Peserta Didik per Rombongan Belajar (Kelas).
     */
    public function printRombel(Request $request, string $rombelId)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role === 'peserta_didik' || !RolePermission::canAccess($user, 'menu_peserta_didik_aktif')) {
            abort(403, 'Akses ke menu Peserta Didik Aktif dinonaktifkan.');
        }

        $data = $this->queryService->getPrintRombelData($rombelId, $request);
        if (isset($data['error'])) {
            abort(404, 'Data rombongan belajar atau peserta didik tidak ditemukan.');
        }

        return view('dashboard.peserta-didik-aktif-cetak', $data);
    }

    /**
     * Unduh berkas template Microsoft Excel (.xlsx) untuk import peserta didik aktif sementara.
     */
    public function downloadTemplate(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $canRead = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'read')
            || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'read');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!$canRead && $role !== 'admin') {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang mengunduh berkas template.');
        }

        return $this->importService->downloadTemplate();
    }

    /**
     * Import berkas Excel / CSV untuk penambahan peserta didik aktif sementara.
     */
    public function importExcel(Request $request): JsonResponse
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_aktif', 'create')
            || RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $isAdmin = ($role === 'admin');

        if (!$canCreate && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menambah / mengimpor data peserta didik.'
            ], 403);
        }

        $request->validate([
            'file_excel' => 'required|file|max:20480',
        ], [
            'file_excel.required' => 'Berkas Excel wajib dipilih.',
            'file_excel.max' => 'Ukuran berkas maksimal adalah 20 MB.',
        ]);

        $res = $this->importService->importExcel($request);

        return response()->json($res, $res['status_code'] ?? 200);
    }
}
