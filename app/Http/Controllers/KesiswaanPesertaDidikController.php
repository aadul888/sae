<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\KesiswaanBerkasService;
use App\Services\KesiswaanQueryService;
use App\Services\KesiswaanUsulanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;class KesiswaanPesertaDidikController extends Controller
{
    use ChecksAuthenticatedSession;

    public function __construct(
        protected KesiswaanQueryService $queryService,
        protected KesiswaanUsulanService $usulanService,
        protected KesiswaanBerkasService $berkasService
    ) {}

    

    /**
     * Tampilkan Halaman Kluster Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $data = $this->queryService->getIndexData($request, $user);

        return view('dashboard.kesiswaan.peserta-didik', $data);
    }

    /**
     * Simpan Usulan Perubahan Data Siswa.
     */
    public function storeUsulan(Request $request): JsonResponse
    {
        $user = session('user');
        $res = $this->usulanService->storeUsulan($request, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Ambil data detail lengkap usulan perubahan siswa (JSON) untuk Modal Formulir Pengelolaan Usulan.
     */
    public function getUsulanDetail($id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canAccess = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'read')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccess) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->usulanService->getUsulanDetail($id);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Verifikasi Usulan Perubahan Data Siswa (Setujui / Tolak).
     */
    public function verifikasiUsulan(Request $request, $id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->usulanService->verifikasiUsulan($request, $id, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Tandai Usulan Perubahan Data telah Di-update / Disinkronkan ke Aplikasi Dapodik.
     */
    public function markDapodikUpdated(Request $request, $id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = in_array($role, ['admin', 'tendik'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->usulanService->markDapodikUpdated($id, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Update Checklist Verifikasi Berkas Fisik Siswa Baru.
     */
    public function updateBerkas(Request $request, $id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->berkasService->updateBerkas($request, $id, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Ambil data berkas digital lengkap per siswa (JSON) untuk Modal Verifikasi Berkas Kesiswaan.
     */
    public function getBerkasDetail($id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canAccess = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'read')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'read')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canAccess) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->berkasService->getBerkasDetail($id);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Verifikasi Kebenaran Berkas Siswa (Hanya 2 Status: valid atau tidak_valid).
     */
    public function verifikasiBerkasItem(Request $request, $id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'update')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->berkasService->verifikasiBerkasItem($request, $id, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Upload berkas siswa langsung oleh Tendik / Staf Kesiswaan (otomatis berstatus 'valid').
     */
    public function uploadBerkasTendik(Request $request, $id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canUpdate = in_array($role, ['admin', 'tendik', 'guru'], true)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas_validasi', 'update')
            || RolePermission::canAccess($user ?: $role, 'menu_wali_kelas', 'update')
            || RolePermission::isWaliKelasOrAdmin($user);

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->berkasService->uploadBerkasTendik($request, $id, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /**
     * Detail lengkap biodata peserta didik (JSON) untuk Modal Biodata.
     */
    public function show($id): JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read') && !RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_aktif', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $data = $this->queryService->getShowData($id);
        if (!$data) {
            return response()->json(['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        return response()->json($data);
    }
}
