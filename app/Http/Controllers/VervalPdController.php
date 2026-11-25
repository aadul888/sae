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
        $sortable = ['status_proses', 'rombel_sekolah', 'nisn_banding', 'nik_dukcapil', 'nik_ganda', 'nama_banding', 'tempat_lahir', 'tanggal_lahir', 'ibu_kandung', 'jenis_kelamin', 'desa_kelurahan'];
        $sort = in_array($data['sort'], $sortable, true) ? $data['sort'] : null;
        $data['items'] = VervalPdResidu::query()
            ->when(trim($request->get('q', '')) !== '', function ($q) use ($request) {
                $term = trim($request->get('q'));
                $q->where(fn($w) => $w->where('nama', 'like', "%{$term}%")
                    ->orWhere('nisn', 'like', "%{$term}%")
                    ->orWhere('nik', 'like', "%{$term}%")
                    ->orWhere('rombel_sekolah', 'like', "%{$term}%"));
            })
            ->when($data['status'] !== '', function ($q) use ($data) {
                $st = $data['status'];
                if ($st === VervalPdResiduService::STATUS_BELUM) {
                    $q->where(fn($w) => $w->where('status_proses', $st)->orWhereNull('status_proses'));
                } elseif (in_array($st, [VervalPdResiduService::STATUS_PROSES, VervalPdResiduService::STATUS_SELESAI], true)) {
                    $q->where('status_proses', $st);
                }
            })
            ->when($sort, fn($q) => $q->orderBy($sort, $data['sortDir']), function ($q) {
                // Default: urutkan status Belum → Sedang Proses → Selesai (selesai paling bawah).
                $q->orderByRaw("CASE status_proses
                    WHEN '" . VervalPdResiduService::STATUS_BELUM . "' THEN 0
                    WHEN '" . VervalPdResiduService::STATUS_PROSES . "' THEN 1
                    WHEN '" . VervalPdResiduService::STATUS_SELESAI . "' THEN 2
                    ELSE 3 END ASC")
                    ->orderByRaw("LOWER(TRIM(nama)) ASC");
            })
            ->paginate($data['perPage'], ['*'], 'residu_page')
            ->withQueryString();

        // Kolom "Kelas" & "Status": info peserta didik asli (dipetakan via NISN).
        $this->attachPesertaInfo($data['items']);

        $data['stats'] = $this->residuStats();
        $data['page'] = 'residu';
        $data['isAdmin'] = $this->isAdmin($user);
        $data['filterRombel'] = $this->filterRombel();

        // Tab Riwayat Residu = riwayat PER SISWA (satu NISN satu kartu), bukan log mentah.
        $data['riwayatSiswa'] = $this->residuService->riwayatPerSiswa($request);
        $data['riwayatQ'] = trim((string) $request->get('rq', ''));
        $data['riwayatTipe'] = trim((string) $request->get('rtipe', ''));
        $data['riwayatPerPage'] = (int) $request->get('rperPage', 15);

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
        $data['isAdmin'] = $this->isAdmin($user);

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

    /** Halaman cetak laporan Residu (A4, langsung print/PDF) — ikut filter status & pencarian. */
    public function cetakResidu(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $items = $this->residuService->reportItems($request);
        $this->attachPesertaInfo($items);

        $status = trim((string) $request->get('status', ''));
        $q = trim((string) $request->get('q', ''));
        $orientasi = $request->get('orientasi') === 'portrait' ? 'portrait' : 'landscape';

        return view('dashboard.verval-pd.residu-cetak', [
            'items' => $items,
            'status' => $status,
            'q' => $q,
            'orientasi' => $orientasi,
            'selectedCount' => count((array) $request->get('ids', [])),
            'sekolah' => \App\Models\Sekolah::first(),
            'kepsek' => $this->kepalahSekolah(),
            'stats' => $this->residuStats(),
        ]);
    }

    /** Kepala sekolah untuk tanda tangan dokumen cetak. */
    protected function kepalahSekolah()
    {
        if (!Table::exists('gtk')) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table('gtk')
            ->where(fn($w) => $w->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%'))
            ->select('nama', 'nip')
            ->first();
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

    /** Ubah satu sel centang / Rombel pada satu baris Residu (tercatat di riwayat). */
    public function updateResiduCell(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'column' => 'required|string',
            'value' => 'nullable|string|max:150',
        ]);

        $res = $this->residuService->updateCell((int) $id, $validated['column'], $validated['value'] ?? null, $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /** Ubah status proses (Belum/Sedang Proses/Selesai) satu baris Residu — tercatat di riwayat. */
    public function updateResiduStatus(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:belum,proses,selesai',
        ]);

        $res = $this->residuService->updateStatus((int) $id, $validated['status'], $user);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /** Simpan catatan masalah satu baris Residu — tercatat di riwayat. */
    public function updateResiduCatatan(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->can($user, 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'catatan' => 'nullable|string|max:1000',
        ]);

        $res = $this->residuService->updateCatatan((int) $id, $validated['catatan'] ?? null, $user);

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

    /** Ubah kolom Konfirmasi identitas (sesuai / perlu_perbaikan) langsung dari datatable — khusus admin. */
    public function updateKonfirmasiBerkas(Request $request, $id): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->isAdmin($user)) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Administrator yang dapat mengubah status konfirmasi.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:sesuai,perlu_perbaikan',
            'catatan' => 'nullable|string|max:500',
        ]);

        $res = $this->berkasService->updateKonfirmasiIdentitas(
            (string) $id,
            $validated['status'],
            $validated['catatan'] ?? null,
            $user,
            self::MODUL['berkas']
        );

        return response()->json($res, $res['status_code'] ?? 200);
    }

    /** Reset status konfirmasi identitas ke 'belum_konfirmasi' — khusus admin.
     *  Body opsional `rombel`: bila diisi, hanya kelas itu; kosong = semua kelas. */
    public function resetAllKonfirmasiBerkas(Request $request): JsonResponse
    {
        $user = $this->sessionUser();
        if (!$this->isAdmin($user)) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Administrator yang dapat mereset status konfirmasi.'], 403);
        }

        $validated = $request->validate([
            'rombel' => 'nullable|string|max:100',
        ]);

        $res = $this->berkasService->resetAllKonfirmasiIdentitas(
            $user,
            self::MODUL['berkas'],
            $validated['rombel'] ?? null
        );

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

    /**
     * Tempelkan info peserta didik asli ke tiap baris Residu (dipetakan lewat NISN):
     * nama kelas (rombel), status konfirmasi identitas, dan status berkas wajib
     * (KK + Ijazah) beserta id berkas untuk pratinjau. Terpisah dari kolom centang
     * Dukcapil yang berasal dari berkas import.
     */
    protected function attachPesertaInfo(iterable $items): void
    {
        $rows = $items instanceof \Illuminate\Contracts\Pagination\Paginator
            ? collect($items->items())
            : collect($items);
        foreach ($rows as $it) {
            $it->kelas = null;
            $it->status_konfirmasi = null;
            $it->sudah_konfirmasi = false;
            $it->berkas_valid = false;
            $it->berkas_map = [];
        }

        if (!Table::exists('peserta_didik')) {
            return;
        }

        $nisns = $rows->pluck('nisn')->filter()->unique()->values()->all();
        if (empty($nisns)) {
            return;
        }

        $pdMap = \Illuminate\Support\Facades\DB::table('peserta_didik')
            ->whereIn('nisn', $nisns)
            ->get(['peserta_didik_id', 'nisn', 'nama_rombel'])
            ->keyBy('nisn');
        $pdIds = $pdMap->pluck('peserta_didik_id')->all();

        $konfMap = Table::exists('peserta_didik_identitas')
            ? \Illuminate\Support\Facades\DB::table('peserta_didik_identitas')
                ->whereIn('peserta_didik_id', $pdIds)->pluck('status_konfirmasi', 'peserta_didik_id')
            : collect();

        $wajibKeys = ['kartu_keluarga', 'ijazah_smp'];

        // Status + id berkas terbaru per jenis (hanya yang wajib), untuk ikon & pratinjau.
        $berkasMap = [];
        if (Table::exists('peserta_didik_berkas')) {
            foreach (\Illuminate\Support\Facades\DB::table('peserta_didik_berkas')
                ->whereIn('peserta_didik_id', $pdIds)
                ->whereIn('jenis_berkas', $wajibKeys)
                ->orderByDesc('id')
                ->get(['id', 'peserta_didik_id', 'jenis_berkas', 'status', 'file_path']) as $r) {
                if (isset($berkasMap[$r->peserta_didik_id][$r->jenis_berkas])) {
                    continue; // ambil record terbaru saja
                }
                $berkasMap[$r->peserta_didik_id][$r->jenis_berkas] = [
                    'status' => strtolower(trim((string) $r->status)),
                    'file_id' => !empty($r->file_path) ? $r->id : null,
                ];
            }
        }

        // Verifikasi fisik (checklist walas) → valid walau tanpa berkas digital.
        if (Table::exists('kesiswaan_berkas_verifikasi')) {
            foreach ($wajibKeys as $wk) {
                foreach (\Illuminate\Support\Facades\DB::table('kesiswaan_berkas_verifikasi')
                    ->whereIn('peserta_didik_id', $pdIds)->where($wk, 1)->pluck('peserta_didik_id') as $pid) {
                    $existing = $berkasMap[$pid][$wk]['file_id'] ?? null;
                    $berkasMap[$pid][$wk] = ['status' => 'valid', 'file_id' => $existing];
                }
            }
        }

        foreach ($rows as $it) {
            $pd = $it->nisn ? ($pdMap[$it->nisn] ?? null) : null;
            if (!$pd) {
                continue;
            }
            $it->kelas = $pd->nama_rombel ?: null;
            $stKonf = strtolower(trim((string) ($konfMap[$pd->peserta_didik_id] ?? 'belum_konfirmasi')));
            $it->status_konfirmasi = $stKonf;
            $it->sudah_konfirmasi = in_array($stKonf, ['sesuai', 'perlu_perbaikan', 'diverifikasi', 'disinkronkan_dapodik'], true);

            $map = $berkasMap[$pd->peserta_didik_id] ?? [];
            $it->berkas_map = $map;
            $validCount = 0;
            foreach ($wajibKeys as $wk) {
                if (($map[$wk]['status'] ?? null) === 'valid') {
                    $validCount++;
                }
            }
            $it->berkas_valid = $validCount >= count($wajibKeys);
        }
    }

    protected function residuStats(): array
    {
        $base = VervalPdResidu::query();
        $total = (clone $base)->count();
        $belum = (clone $base)->where(fn($q) => $q->where('status_proses', VervalPdResiduService::STATUS_BELUM)
            ->orWhereNull('status_proses'))->count();
        $proses = (clone $base)->where('status_proses', VervalPdResiduService::STATUS_PROSES)->count();
        $selesai = (clone $base)->where('status_proses', VervalPdResiduService::STATUS_SELESAI)->count();
        $importedBy = (clone $base)->whereNotNull('imported_by')->latest('updated_at')->value('imported_by');

        return [
            'total' => $total,
            'belum' => $belum,
            'proses' => $proses,
            'selesai' => $selesai,
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

    /** Apakah pengguna berperan admin (satu-satunya yang boleh ubah status konfirmasi Verval PD). */
    protected function isAdmin(mixed $user): bool
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return $role === 'admin';
    }
}
