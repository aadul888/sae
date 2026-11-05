<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\KesiswaanBerkasService;
use App\Services\KesiswaanQueryService;
use App\Services\KesiswaanUsulanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


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
     * Upload berkas siswa langsung oleh Wali Kelas / Tendik / Admin (otomatis berstatus 'valid').
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

    /**
     * Tambah data alumni tahun sebelumnya secara manual (tidak menunggu tarikan Dapodik).
     * Tersimpan di `peserta_didik_tidak_aktif` dengan status_keluar = Alumni.
     */
    public function storeAlumni(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) return $res;

        if (!$this->canManageAlumni('create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $data = $this->validateAlumni($request);
        $data['peserta_didik_id'] = (string) Str::uuid();
        $data['status_keluar'] = 'Alumni';
        $data['alasan_keluar'] = $data['alasan_keluar'] ?? null;
        $data['alasan_keluar'] = $data['alasan_keluar'] ?: 'Lulus';
        $data['tingkat_pendidikan_terakhir'] = '12';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('peserta_didik_tidak_aktif')->insert($data);

        return response()->json(['status' => 'success', 'message' => 'Data alumni berhasil ditambahkan.']);
    }

    /**
     * Perbarui data alumni manual (JSON). Data identitas tetap di NISN bila diisi.
     */
    public function updateAlumni(Request $request, $id): JsonResponse
    {
        if ($res = $this->checkAuth()) return $res;

        if (!$this->canManageAlumni('update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $exists = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists();
        if (!$exists) {
            return response()->json(['status' => 'error', 'message' => 'Data alumni tidak ditemukan.'], 404);
        }

        $data = $this->validateAlumni($request);
        $data['updated_at'] = now();

        DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->update($data);

        return response()->json(['status' => 'success', 'message' => 'Data alumni berhasil diperbarui.']);
    }

    /**
     * Detail data alumni manual (JSON) untuk mengisi modal edit.
     */
    public function showAlumni($id): JsonResponse
    {
        if ($res = $this->checkAuth()) return $res;

        if (!$this->canManageAlumni('read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $row = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Data alumni tidak ditemukan.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $row]);
    }

    /** Cek hak kelola alumni (input manual di tab alumni). */
    private function canManageAlumni(string $action): bool
    {
        $user = session('user');
        if (!$user) return false;

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (in_array($role, ['admin', 'tendik'], true)) return true;

        return RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_peserta_didik', $action)
            || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', $action)
            || RolePermission::canAccess($user ?: $role, 'menu_peserta_didik_tidak_aktif', $action);
    }

    /** Validasi input manual alumni. */
    private function validateAlumni(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:150',
            'nisn' => 'nullable|string|max:20',
            'nipd' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:30',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'agama_id_str' => 'nullable|string|max:50',
            'kebutuhan_khusus' => 'nullable|string|max:100',
            'nama_rombel_terakhir' => 'nullable|string|max:100',
            'tahun_lulus' => 'nullable|string|max:20',
            'alasan_keluar' => 'nullable|string|max:100',
            'tanggal_keluar' => 'nullable|date',
            'alamat_jalan' => 'nullable|string',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'dusun' => 'nullable|string|max:100',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'jenis_tinggal' => 'nullable|string|max:100',
            'alat_transportasi' => 'nullable|string|max:100',
            'nomor_telepon_rumah' => 'nullable|string|max:50',
            'nomor_telepon_seluler' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'skhun' => 'nullable|string|max:100',
            'penerima_kps' => 'nullable|string|max:20',
            'no_kps' => 'nullable|string|max:100',
            'nama_ayah' => 'nullable|string|max:150',
            'tahun_lahir_ayah' => 'nullable|string|max:10',
            'jenjang_pendidikan_ayah' => 'nullable|string|max:100',
            'pekerjaan_ayah' => 'nullable|string|max:100',
            'penghasilan_ayah' => 'nullable|string|max:100',
            'kebutuhan_khusus_ayah' => 'nullable|string|max:100',
            'nama_ibu' => 'nullable|string|max:150',
            'tahun_lahir_ibu' => 'nullable|string|max:10',
            'jenjang_pendidikan_ibu' => 'nullable|string|max:100',
            'pekerjaan_ibu' => 'nullable|string|max:100',
            'penghasilan_ibu' => 'nullable|string|max:100',
            'kebutuhan_khusus_ibu' => 'nullable|string|max:100',
            'nama_wali' => 'nullable|string|max:150',
            'tahun_lahir_wali' => 'nullable|string|max:10',
            'jenjang_pendidikan_wali' => 'nullable|string|max:100',
            'pekerjaan_wali' => 'nullable|string|max:100',
            'penghasilan_wali' => 'nullable|string|max:100',
        ]);
    }
}
