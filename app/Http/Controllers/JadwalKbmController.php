<?php

namespace App\Http\Controllers;

use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\RolePermission;
use App\Services\AutoSchedulerService;
use App\Services\JadwalBulananService;
use App\Services\JadwalMutasiService;
use App\Services\JadwalPengaturanService;
use App\Services\JadwalPekanService;
use App\Services\JadwalPrintService;
use App\Services\RealtimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalKbmController extends Controller
{
    private const SORTABLE = ['hari', 'nama_rombel', 'nama_guru', 'nama_mata_pelajaran', 'jam_mulai', 'jam_ke_mulai'];

    public function __construct(
        protected JadwalPrintService $printService,
        protected JadwalMutasiService $mutasiService,
        protected JadwalPengaturanService $pengaturanService,
        protected JadwalPekanService $pekanService,
        protected JadwalBulananService $bulananService
    ) {}

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_jadwal_kbm', 'read')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Jadwal KBM dinonaktifkan.');
        }

        $canCreate = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'create');
        $canRead   = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'read');
        $canUpdate = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update');
        $canDelete = RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'delete');

        $viewMode   = $request->get('view_mode', 'grid');
        $selectedHari = $request->get('hari') ?: 'Senin';
        $tingkat    = trim($request->get('tingkat', ''));
        $jurusan    = trim((string) $request->get('jurusan', ''));
        $hari       = trim($request->get('hari', ''));
        $rombelId   = trim($request->get('rombel_id', ''));
        $guruId     = trim($request->get('ptk_id', ''));
        $q          = trim($request->get('q', ''));
        $perPage    = (int) $request->get('perPage', 15);
        $sort       = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'hari';
        $sortDir    = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Auto-select rombel untuk peserta didik jika belum memilih filter
        if ($role === 'peserta_didik' && $rombelId === '') {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if ($pdId) {
                $pdRow = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
                if ($pdRow && !empty($pdRow->rombongan_belajar_id)) {
                    $rombelId = $pdRow->rombongan_belajar_id;
                    if ($tingkat === '' && !empty($pdRow->tingkat_pendidikan_id)) {
                        $tingkat = (string) $pdRow->tingkat_pendidikan_id;
                    }
                }
            }
        }

        // Query Utama untuk Mode Tabel
        $query = DB::table('jadwal_kbm as j')
            ->leftJoin('rombongan_belajar as r', 'j.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.sumber', 'otomatis')
            ->select(
                'j.*',
                'r.nama as nama_rombel',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            );

        if ($hari !== '') {
            $query->where('j.hari', $hari);
        }

        if ($rombelId !== '') {
            $query->where('j.rombongan_belajar_id', $rombelId);
        }

        if ($guruId !== '') {
            $query->where('j.ptk_id', $guruId);
        }

        // Saring mapel PKL jika role guru (presensi & KBM PKL via ePKL terpisah)
        if ($role === 'guru') {
            $query->where(function ($w) {
                $w->whereNull('j.nama_mata_pelajaran')
                  ->orWhere(function ($sub) {
                      $sub->where('j.nama_mata_pelajaran', 'NOT LIKE', 'PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '% PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTIK KERJA%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTEK KERJA%');
                  });
            });
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('j.nama_mata_pelajaran', 'LIKE', "%{$q}%")
                    ->orWhere('r.nama', 'LIKE', "%{$q}%")
                    ->orWhere('g.nama', 'LIKE', "%{$q}%")
                    ->orWhere('j.ruangan', 'LIKE', "%{$q}%");
            });
        }

        // Custom Order By Hari
        if ($sort === 'hari') {
            $query->orderByRaw("FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') {$sortDir}")
                ->orderBy('j.jam_mulai', 'asc');
        } elseif ($sort === 'nama_rombel') {
            $query->orderBy('r.nama', $sortDir)->orderBy('j.jam_mulai', 'asc');
        } elseif ($sort === 'nama_guru') {
            $query->orderBy('g.nama', $sortDir)->orderBy('j.jam_mulai', 'asc');
        } else {
            $query->orderBy("j.{$sort}", $sortDir);
        }

        $list = $query->paginate($perPage)->appends($request->query());

        // Master Filter Lists: Hanya Kelas Reguler
        $rombelQuery = DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                    ->orWhere('jenis_rombel_str', 'Kelas')
                    ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc');

        $rombelList = $rombelQuery->get();

        // Rombel Khusus Tampilan Grid (Hanya Kelas Reguler, bisa difilter per tingkat & jurusan)
        $gridRombelsQuery = \App\Support\RombelScope::reguler(
            DB::table('rombongan_belajar as rb')
                ->leftJoin('jurusan_meta as jm', 'rb.jurusan_id', '=', 'jm.jurusan_id'),
            'rb.'
        )
            ->select(
                'rb.rombongan_belajar_id',
                'rb.nama',
                'rb.tingkat_pendidikan_id',
                'rb.jurusan_id',
                'rb.jurusan_id_str',
                'jm.singkatan as jurusan_singkatan'
            )
            ->orderBy('rb.jurusan_id_str', 'asc')
            ->orderBy('rb.tingkat_pendidikan_id', 'asc')
            ->orderBy('rb.nama', 'asc');
        if ($tingkat !== '') {
            $gridRombelsQuery->where('rb.tingkat_pendidikan_id', $tingkat);
        }
        if ($jurusan !== '') {
            $gridRombelsQuery->where('rb.jurusan_id', $jurusan);
        }
        $gridRombels = $gridRombelsQuery->get();

        // Label grup kolom per jurusan: singkatan bila ada, jika tidak nama jurusan Dapodik.
        $labelJurusan = function ($r): string {
            $singkatan = trim((string) ($r->jurusan_singkatan ?? ''));
            if ($singkatan !== '') {
                return $singkatan;
            }
            $nama = trim((string) ($r->jurusan_id_str ?? ''));

            return $nama !== '' ? $nama : 'Tanpa Jurusan';
        };

        // Kelompokkan kolom rombel per jurusan agar kelas sejurusan berdampingan.
        $gridGroups = [];
        foreach ($gridRombels as $r) {
            $key = (string) ($r->jurusan_id ?? '');
            if (!isset($gridGroups[$key])) {
                $gridGroups[$key] = [
                    'label'  => $labelJurusan($r),
                    'nama'   => trim((string) ($r->jurusan_id_str ?? '')) ?: 'Tanpa Jurusan',
                    'rombel' => [],
                ];
            }
            $gridGroups[$key]['rombel'][] = $r;
        }

        // Daftar jurusan untuk filter (selalu dari seluruh rombel, bukan hasil filter,
        // agar pilihan tidak hilang setelah memilih salah satu jurusan).
        $jurusanFilterQuery = DB::table('rombongan_belajar as rb')
            ->leftJoin('jurusan_meta as jm', 'rb.jurusan_id', '=', 'jm.jurusan_id')
            ->where(function ($w) {
                $w->where('rb.jenis_rombel', 1)
                    ->orWhere('rb.jenis_rombel_str', 'Kelas')
                    ->orWhere('rb.jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->whereNotNull('rb.jurusan_id')
            ->select(
                'rb.jurusan_id',
                'rb.jurusan_id_str',
                'jm.singkatan',
                DB::raw('COUNT(*) as total_rombel')
            )
            ->groupBy('rb.jurusan_id', 'rb.jurusan_id_str', 'jm.singkatan')
            ->orderBy('rb.jurusan_id_str', 'asc');
        if ($tingkat !== '') {
            $jurusanFilterQuery->where('rb.tingkat_pendidikan_id', $tingkat);
        }
        $jurusanList = $jurusanFilterQuery->get();

        $guruList = DB::table('gtk')
            ->select('ptk_id', 'nama', 'nip')
            ->whereNotNull('nama')
            ->orderBy('nama', 'asc')
            ->get();

        // Data Matriks Grid untuk Hari Terpilih
        $daySchedulesQuery = DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->where('j.sumber', 'otomatis')
            ->where('j.hari', $selectedHari)
            ->where('j.is_active', true)
            ->select(
                'j.*',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            );

        if ($role === 'guru') {
            $daySchedulesQuery->where(function ($w) {
                $w->whereNull('j.nama_mata_pelajaran')
                  ->orWhere(function ($sub) {
                      $sub->where('j.nama_mata_pelajaran', 'NOT LIKE', 'PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '% PKL%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTIK KERJA%')
                          ->where('j.nama_mata_pelajaran', 'NOT LIKE', '%PRAKTEK KERJA%');
                  });
            });
        }

        $daySchedules = $daySchedulesQuery->get();

        // Matriks grid: satu sel dapat memuat LEBIH DARI SATU jadwal (mis. team teaching
        // atau dua guru pada jam sama). Jika hanya disimpan satu item per sel, jadwal
        // berikutnya akan saling menimpa dan hilang dari tampilan.
        $gridMatrix = [];
        $gridOccupied = [];
        foreach ($daySchedules as $item) {
            $rId = $item->rombongan_belajar_id;
            $startK = (int) $item->jam_ke_mulai;
            $endK = (int) $item->jam_ke_selesai;

            $gridMatrix[$rId][$startK][] = $item;
            for ($k = $startK + 1; $k <= $endK; $k++) {
                $gridOccupied[$rId][$k] = true;
            }
        }

        // Hitung total jadwal aktif per hari untuk badge di Tab Hari
        $countPerHari = DB::table('jadwal_kbm')
            ->where('sumber', 'otomatis')
            ->where('is_active', true)
            ->select('hari', DB::raw('COUNT(*) as total'))
            ->groupBy('hari')
            ->pluck('total', 'hari')
            ->toArray();

        // Integrasi Kalender Pendidikan & Hari Efektif Belajar
        $currentYear = (int) date('Y');
        $tahunAjaranAktif = (date('n') >= 7) ? "{$currentYear}/" . ($currentYear + 1) : ($currentYear - 1) . "/{$currentYear}";
        $semesterAktif = (date('n') >= 7) ? '1' : '2';
        $periodeSem = KalenderPendidikan::resolvePeriodeDates($tahunAjaranAktif, $semesterAktif);
        $hariEfektifSemester = KalenderPendidikan::hitungHariEfektif($periodeSem['start'], $periodeSem['end'], 'pd');
        $hariEfektifBerjalan = KalenderPendidikan::hitungHariEfektifBerjalan($periodeSem['start'], $periodeSem['end'], now()->toDateString(), 'pd');

        $tanggalHariIni = now()->toDateString();
        $statusHariIni = KalenderPendidikan::getStatusHari($tanggalHariIni, 'all');
        $agendaHariIni = KalenderPendidikan::whereDate('tanggal_mulai', '<=', $tanggalHariIni)
            ->whereDate('tanggal_selesai', '>=', $tanggalHariIni)
            ->first();

        // Statistik Ringkasan (Khusus Jadwal Otomatis)
        $summary = [
            'total_jadwal'          => DB::table('jadwal_kbm')->where('sumber', 'otomatis')->count(),
            'total_rombel'          => DB::table('jadwal_kbm')->where('sumber', 'otomatis')->distinct('rombongan_belajar_id')->count('rombongan_belajar_id'),
            'total_guru'            => DB::table('jadwal_kbm')->where('sumber', 'otomatis')->whereNotNull('ptk_id')->distinct('ptk_id')->count('ptk_id'),
            'total_jp'              => DB::table('jadwal_kbm')->where('sumber', 'otomatis')->sum(DB::raw('GREATEST(1, jam_ke_selesai - jam_ke_mulai + 1)')),
            'hari_efektif_semester' => $hariEfektifSemester,
            'hari_efektif_berjalan' => $hariEfektifBerjalan,
        ];

        // Ambil konfigurasi slot dinamis (menyesuaikan hari yang sedang dibuka)
        $pengaturan = JadwalPengaturan::getSettings();
        $dailySlotCounts = JadwalPengaturan::getDailySlotCounts();
        $tingkatDailySlots = JadwalPengaturan::getTingkatDailySlotCounts();
        $timeSlots      = JadwalPengaturan::getSlots($selectedHari);

        // Evaluasi Mode Pemberlakuan Eksklusif
        $modePemberlakuan = JadwalPengaturan::getModePemberlakuan();
        $isOtomatisAktif = JadwalPengaturan::isDiberlakukan('otomatis');
        $isManualAktif   = JadwalPengaturan::isDiberlakukan('manual');
        $isDiberlakukan  = $isOtomatisAktif;
        $statusJadwal    = $isOtomatisAktif ? 'aktif' : 'draft';

        // Lapis 2: pemberlakuan per pekan (opsional, tidak mengubah kontrak di atas)
        $pekanList = $this->pekanService->daftarPekan($tahunAjaranAktif, $semesterAktif);
        $ringkasanPekan = $this->pekanService->ringkasan($tahunAjaranAktif, $semesterAktif);
        $periodePekan = $this->pekanService->resolvePeriode($tahunAjaranAktif, $semesterAktif);

        return view('dashboard.jadwal-kbm', compact(
            'list',
            'summary',
            'rombelList',
            'gridRombels',
            'gridGroups',
            'jurusanList',
            'guruList',
            'pengaturan',
            'dailySlotCounts',
            'tingkatDailySlots',
            'hari',
            'selectedHari',
            'tingkat',
            'jurusan',
            'viewMode',
            'timeSlots',
            'gridMatrix',
            'gridOccupied',
            'countPerHari',
            'rombelId',
            'guruId',
            'q',
            'perPage',
            'sort',
            'sortDir',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'statusHariIni',
            'agendaHariIni',
            'tahunAjaranAktif',
            'semesterAktif',
            'isDiberlakukan',
            'statusJadwal',
            'isOtomatisAktif',
            'isManualAktif',
            'modePemberlakuan',
            'pekanList',
            'ringkasanPekan',
            'periodePekan'
        ));
    }

    public function togglePemberlakuan(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki wewenang mengubah status pemberlakuan jadwal.'], 403);
        }

        $res = $this->pengaturanService->togglePemberlakuan($request, $user);

        return response()->json($res);
    }

    public function simpanPengaturan(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah pengaturan jadwal.'], 403);
        }

        $res = $this->pengaturanService->simpanPengaturan($request);

        return response()->json($res);
    }

    public function autoGenerate(Request $request, AutoSchedulerService $scheduler): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'create')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin auto-generate jadwal.'], 403);
        }

        if ($request->has('jam_mulai_kbm') && $request->has('durasi_per_jp')) {
            $this->pengaturanService->simpanPengaturan($request);
        }

        $pengaturan = JadwalPengaturan::getSettings();
        $skemaHari = $request->input('skema_hari', $pengaturan->skema_hari ?? '5_hari');
        $hariAktif = ($skemaHari === '6_hari')
            ? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
            : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $maxJpPerSesi = $request->filled('max_jp_per_sesi')
            ? max(2, min(9, (int) $request->input('max_jp_per_sesi')))
            : (int) ($pengaturan->max_jp_per_sesi ?? 3);

        if ($pengaturan && $pengaturan->max_jp_per_sesi !== $maxJpPerSesi) {
            $pengaturan->update(['max_jp_per_sesi' => $maxJpPerSesi]);
        }

        $options = [
            'clear_existing'        => $request->boolean('clear_existing', true),
            'tingkat'               => $request->input('tingkat') ?: null,
            'tingkat_aktif'         => $request->input('tingkat_aktif', []),
            'rombongan_belajar_ids' => $request->input('rombongan_belajar_ids', []),
            'hari_aktif'            => $hariAktif,
            'max_jp_per_sesi'       => $maxJpPerSesi,
            'strict_validation'     => $request->boolean('strict_validation', true),
        ];

        $result = $scheduler->generate($options);

        if (!empty($result['success'])) {
            RealtimeService::trigger('jadwal.changed', [
                'action'          => 'auto_generated',
                'total_generated' => $result['total_generated'] ?? 0,
            ]);
            return response()->json($result);
        }

        return response()->json($result, 422);
    }

    public function getPembelajaranByRombel(Request $request): JsonResponse
    {
        return response()->json($this->pengaturanService->getPembelajaranByRombel($request));
    }

    public function checkConflictApi(Request $request): JsonResponse
    {
        return response()->json($this->mutasiService->checkConflict($request));
    }

    public function getGuruPreferensi(Request $request): JsonResponse
    {
        $res = $this->pengaturanService->getGuruPreferensi($request);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function simpanGuruPreferensi(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah preferensi guru.'], 403);
        }

        return response()->json($this->pengaturanService->simpanGuruPreferensi($request));
    }

    public function cetakInduk(Request $request)
    {
        $data = $this->printService->getCetakIndukData($request);

        return view('dashboard.jadwal-kbm-cetak-induk', $data);
    }

    public function cetakGuru(Request $request)
    {
        $data = $this->printService->getCetakGuruData($request);

        return view('dashboard.jadwal-kbm-cetak-guru', $data);
    }

    public function cetakRombel(Request $request)
    {
        $data = $this->printService->getCetakRombelData($request);

        return view('dashboard.jadwal-kbm-cetak-rombel', $data);
    }

    public function store(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'create')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin menambah jadwal.'], 403);
        }

        $validated = $request->validate([
            'rombongan_belajar_id' => 'required|string|max:50',
            'pembelajaran_id'      => 'required|string|max:50',
            'hari'                 => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke_mulai'         => 'required|integer|min:1|max:20',
            'jam_ke_selesai'       => 'required|integer|min:1|max:20|gte:jam_ke_mulai',
            'jam_mulai'            => 'required|string',
            'jam_selesai'          => 'required|string',
            'ruangan'              => 'nullable|string|max:50',
            'keterangan'           => 'nullable|string|max:255',
        ]);

        $res = $this->mutasiService->store($validated, $request->boolean('overwrite', false));

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah jadwal.'], 403);
        }

        $validated = $request->validate([
            'rombongan_belajar_id' => 'required|string|max:50',
            'pembelajaran_id'      => 'required|string|max:50',
            'hari'                 => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke_mulai'         => 'required|integer|min:1|max:20',
            'jam_ke_selesai'       => 'required|integer|min:1|max:20|gte:jam_ke_mulai',
            'jam_mulai'            => 'required|string',
            'jam_selesai'          => 'required|string',
            'ruangan'              => 'nullable|string|max:50',
            'keterangan'           => 'nullable|string|max:255',
        ]);

        $res = $this->mutasiService->update($id, $validated, $request->boolean('overwrite', false));

        return response()->json($res, $res['status_code'] ?? 200);
    }

    public function destroy($id): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'delete')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin menghapus jadwal.'], 403);
        }

        $res = $this->mutasiService->destroy($id);

        return response()->json($res, $res['status_code'] ?? 200);
    }

    // =========================================================================
    // Lapis 2: Pemberlakuan Jadwal per Pekan
    // =========================================================================

    /**
     * Sinkronkan pekan periode aktif dengan Kalender Pendidikan.
     */
    public function generatePekan(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengelola pekan jadwal.'], 403);
        }

        $validated = $request->validate([
            'tahun_ajaran' => 'nullable|string|max:10',
            'semester'     => 'nullable|in:1,2',
            'perbarui_draft' => 'nullable|boolean',
        ]);

        $periode = $this->pekanService->resolvePeriode(
            $validated['tahun_ajaran'] ?? null,
            $validated['semester'] ?? null
        );

        $result = $this->pekanService->generatePekan(
            $periode['tahun_ajaran'],
            $periode['semester'],
            $request->boolean('perbarui_draft', true)
        );

        RealtimeService::trigger('jadwal.changed', [
            'action' => 'pekan_generated',
            'total'  => $result['total'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pekan disinkronkan: {$result['created']} dibuat, {$result['updated']} diperbarui.",
            'data'    => $result,
        ]);
    }

    /**
     * Perbarui satu pekan (rentang tanggal, sumber, status, catatan).
     */
    public function updatePekan(Request $request, $id): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin mengubah pekan jadwal.'], 403);
        }

        $validated = $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'sumber'          => 'nullable|in:otomatis,manual',
            'status'          => 'required|in:draft,aktif,libur,ujian',
            'is_efektif'      => 'nullable|boolean',
            'catatan'         => 'nullable|string|max:500',
        ]);

        try {
            $pekan = $this->pekanService->updatePekan(
                (int) $id,
                $validated,
                $this->identifierPengguna($user)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        RealtimeService::trigger('jadwal.changed', [
            'action'   => 'pekan_updated',
            'pekan_id' => $pekan->id,
            'status'   => $pekan->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pekan ke-{$pekan->pekan_ke} diperbarui.",
            'data'    => [
                'id'      => $pekan->id,
                'status'  => $pekan->status,
                'rentang' => $pekan->rentang_label,
            ],
        ]);
    }

    /**
     * Ubah status pemberlakuan pekan (aktif / draft / libur / ujian).
     */
    public function togglePekan(Request $request, $id): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::can($user ?: $role, 'menu_jadwal_kbm', 'update')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki izin memberlakukan pekan jadwal.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,aktif,libur,ujian',
        ]);

        try {
            $pekan = $this->pekanService->ubahStatus(
                (int) $id,
                $validated['status'],
                $this->identifierPengguna($user)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        RealtimeService::trigger('jadwal.changed', [
            'action'   => 'pekan_status',
            'pekan_id' => $pekan->id,
            'status'   => $pekan->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pekan ke-{$pekan->pekan_ke} kini berstatus {$pekan->status_label}.",
            'data'    => [
                'id'     => $pekan->id,
                'status' => $pekan->status,
                'label'  => $pekan->status_label,
                'badge'  => $pekan->status_badge,
            ],
        ]);
    }

    /**
     * Cetak jadwal bulanan (ekspansi pola x kalender x pekan).
     */
    public function cetakBulanan(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_jadwal_kbm', 'read')) {
            abort(403, 'Akses ke cetak jadwal bulanan dinonaktifkan.');
        }

        return view('dashboard.jadwal-kbm-cetak-bulan', $this->bulananService->build($request));
    }

    /**
     * Identitas pengguna untuk jejak pemberlakuan pekan.
     */
    private function identifierPengguna(mixed $user): ?string
    {
        if (!$user) {
            return null;
        }

        $id = is_array($user)
            ? ($user['pengguna_id'] ?? $user['id'] ?? null)
            : ($user->pengguna_id ?? $user->id ?? null);

        return $id !== null ? (string) $id : null;
    }
}
