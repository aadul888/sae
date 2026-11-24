<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\AdminAktivitas;
use App\Models\RolePermission;
use App\Models\VervalPdResidu;
use App\Services\KesiswaanBerkasService;
use App\Services\KesiswaanQueryService;
use App\Services\KesiswaanUsulanService;
use App\Services\VervalPdResiduService;
use App\Support\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Modul Verval PD (Verifikasi & Validasi Peserta Didik) — khusus admin.
 * Tiga submodul: Residu (import Excel Dukcapil), Berkas/Dokumen, Usulan Perubahan Data.
 * Setiap halaman memiliki tab Riwayat yang menampilkan log submodul terkait.
 */
class VervalPdController extends Controller
{
    use ChecksAuthenticatedSession;

    /** Nama modul log per submodul (dipakai tab Riwayat & MonitoringMenu). */
    protected const MODUL = [
        'residu' => 'Verval PD Residu',
        'berkas' => 'Verval PD Berkas',
        'perubahan' => 'Verval PD Perubahan',
    ];

    public function __construct(
        protected VervalPdResiduService $residuService,
        protected KesiswaanQueryService $queryService,
        protected KesiswaanUsulanService $usulanService,
        protected KesiswaanBerkasService $berkasService,
    ) {}

    /** Submenu 1: Residu — daftar hasil import + tab Riwayat residu. */
    public function residu(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = $this->sessionUser();
        [$canCreate, $canRead, $canUpdate, $canDelete] = $this->permissions($user);

        $data = $this->pageData($request, 'residu');
        $data['items'] = VervalPdResidu::query()
            ->when(trim($request->get('q', '')) !== '', function ($q) use ($request) {
                $term = trim($request->get('q'));
                $q->where(fn($w) => $w->where('nama', 'like', "%{$term}%")
                    ->orWhere('nisn', 'like', "%{$term}%")
                    ->orWhere('nik', 'like', "%{$term}%")
                    ->orWhere('rombel_sekolah', 'like', "%{$term}%"));
            })
            ->orderBy('nama')
            ->paginate($data['perPage'], ['*'], 'residu_page')
            ->withQueryString();
        $data['stats'] = $this->residuStats();
        $data['page'] = 'residu';

        return view('dashboard.verval-pd', $data + compact('canCreate', 'canRead', 'canUpdate', 'canDelete'));
    }

    /** Submenu 2: Berkas / Dokumen — validasi berkas siswa + tab Riwayat berkas. */
    public function berkas(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = $this->sessionUser();
        [$canCreate, $canRead, $canUpdate, $canDelete] = $this->permissions($user);

        $data = $this->pageData($request, 'berkas');
        $data['berkasList'] = $this->queryService->getBerkasTab($request);
        $data['stats'] = $this->berkasStats();
        $data['filterRombel'] = $this->filterRombel();
        $data['page'] = 'berkas';

        return view('dashboard.verval-pd', $data + compact('canCreate', 'canRead', 'canUpdate', 'canDelete'));
    }

    /** Submenu 3: Usulan Perubahan Data + tab Riwayat perubahan. */
    public function perubahan(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = $this->sessionUser();
        [$canCreate, $canRead, $canUpdate, $canDelete] = $this->permissions($user);

        $data = $this->pageData($request, 'perubahan');
        $data['usulanList'] = $this->queryService->getUsulanTab($request);
        $data['stats'] = $this->perubahanStats();
        $data['filterRombel'] = $this->filterRombel();
        $data['page'] = 'perubahan';

        return view('dashboard.verval-pd', $data + compact('canCreate', 'canRead', 'canUpdate', 'canDelete'));
    }

    // ---------------------------------------------------------------- Residu

    public function importResidu(Request $request): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'create')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->residuService->importExcel($request, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function downloadTemplateResidu()
    {
        if ($res = $this->checkAuth()) return $res;

        return $this->residuService->downloadTemplate();
    }

    public function clearResidu(Request $request): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'delete')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->residuService->clear($user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    // ---------------------------------------------------------------- Berkas

    public function getBerkasDetail($id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $res = $this->berkasService->getBerkasDetail($id);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function verifikasiBerkasItem(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->berkasService->verifikasiBerkasItem($request, $id, $user, self::MODUL['berkas']);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function uploadBerkasTendik(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->berkasService->uploadBerkasTendik($request, $id, $user, self::MODUL['berkas']);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    // ------------------------------------------------------------- Perubahan

    public function getUsulanDetail($id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $res = $this->usulanService->getUsulanDetail($id);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function verifikasiUsulan(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->usulanService->verifikasiUsulan($request, $id, $user, self::MODUL['perubahan']);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function markDapodikUpdated(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->usulanService->markDapodikUpdated($id, $user, self::MODUL['perubahan']);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    // -------------------------------------------------------------- Pembantu

    /** Parameter halaman bersama (tab aktif, pencarian, paginasi, riwayat). */
    protected function pageData(Request $request, string $sub): array
    {
        $perPageVal = $request->get('perPage', $request->get('per_page', '25'));
        $perPage = in_array($perPageVal, ['10', '15', '25', '50', '100'], true) ? (int) $perPageVal : 25;

        $activeTab = $request->get('tab', 'data') === 'riwayat' ? 'riwayat' : 'data';

        return [
            'activeTab' => $activeTab,
            'q' => trim($request->get('q', '')),
            'status' => trim($request->get('status', '')),
            'rombel' => trim($request->get('rombel', '')),
            'sort' => $request->get('sort', ''),
            'sortDir' => $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc',
            'perPage' => $perPage,
            'riwayatList' => $this->riwayat(self::MODUL[$sub]),
        ];
    }

    /** Riwayat aktivitas satu submodul Verval PD (per tanggal terbaru). */
    protected function riwayat(string $modul)
    {
        if (!Table::exists('admin_aktivitas')) {
            return collect();
        }

        return AdminAktivitas::query()
            ->where('modul', $modul)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    protected function residuStats(): array
    {
        $base = VervalPdResidu::query();
        $total = (clone $base)->count();
        $centangHijau = (clone $base)->where('nama_banding', 'like', '%✓%')->count();
        $perluPerbaikan = (clone $base)->where(fn($q) => $q
            ->where('nik_dukcapil', 'like', '%✗%')
            ->orWhere('nama_banding', 'like', '%✗%')
            ->orWhere('tempat_lahir', 'like', '%✗%')
            ->orWhere('tanggal_lahir', 'like', '%✗%')
            ->orWhere('ibu_kandung', 'like', '%✗%')
            ->orWhere('jenis_kelamin', 'like', '%✗%')
            ->orWhere('desa_kelurahan', 'like', '%✗%'))->count();
        $nikGanda = (clone $base)->where(fn($q) => $q
            ->where('nik_ganda', 'like', '%✗%')
            ->orWhere('nik_ganda', 'like', '%Ganda%'))->count();
        $importedBy = (clone $base)->whereNotNull('imported_by')->latest('updated_at')->value('imported_by');

        return [
            'total' => $total,
            'valid' => $centangHijau,
            'perlu_perbaikan' => $perluPerbaikan,
            'nik_ganda' => $nikGanda,
            'imported_by' => $importedBy,
        ];
    }

    protected function berkasStats(): array
    {
        $totalSiswa = Table::exists('peserta_didik') ? \App\Models\PesertaDidik::count() : 0;
        $menunggu = Table::exists('peserta_didik_berkas')
            ? \Illuminate\Support\Facades\DB::table('peserta_didik_berkas')->where('status', 'menunggu')->distinct()->count('peserta_didik_id')
            : 0;
        $tidakValid = Table::exists('peserta_didik_berkas')
            ? \Illuminate\Support\Facades\DB::table('peserta_didik_berkas')->where('status', 'tidak_valid')->distinct()->count('peserta_didik_id')
            : 0;
        $valid = Table::exists('peserta_didik_berkas')
            ? \Illuminate\Support\Facades\DB::table('peserta_didik_berkas')->where('status', 'valid')->distinct()->count('peserta_didik_id')
            : 0;

        return [
            'total' => $totalSiswa,
            'valid' => $valid,
            'menunggu' => $menunggu,
            'tidak_valid' => $tidakValid,
        ];
    }

    protected function perubahanStats(): array
    {
        $model = \App\Models\SiswaUsulanPerubahan::class;
        if (!Table::exists('siswa_usulan_perubahan')) {
            return ['total' => 0, 'menunggu' => 0, 'disetujui' => 0, 'dapodik' => 0, 'ditolak' => 0];
        }

        return [
            'total' => $model::distinct()->count('peserta_didik_id'),
            'menunggu' => $model::where('status', 'menunggu')->distinct()->count('peserta_didik_id'),
            'disetujui' => $model::where('status', 'disetujui')->distinct()->count('peserta_didik_id'),
            'dapodik' => $model::where('status', 'sudah_ke_dapodik')->distinct()->count('peserta_didik_id'),
            'ditolak' => $model::where('status', 'ditolak')->distinct()->count('peserta_didik_id'),
        ];
    }

    protected function filterRombel(): \Illuminate\Support\Collection
    {
        if (!Table::exists('peserta_didik')) {
            return collect();
        }

        return \Illuminate\Support\Facades\DB::table('peserta_didik')
            ->whereNotNull('nama_rombel')->where('nama_rombel', '<>', '')
            ->distinct()->orderBy('nama_rombel')->pluck('nama_rombel');
    }

    /** Ambil [create, read, update, delete] dari hak akses menu. */
    protected function permissions(mixed $user): array
    {
        return [
            $this->can($user, 'create'),
            $this->can($user, 'read'),
            $this->can($user, 'update'),
            $this->can($user, 'delete'),
        ];
    }

    protected function can(mixed $user, string $verb): bool
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return RolePermission::canAccess($user ?: $role, 'menu_verval_pd', $verb);
    }
}
