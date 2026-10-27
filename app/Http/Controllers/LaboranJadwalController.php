<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use App\Services\LaboranJadwalService;
use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaboranJadwalController extends Controller
{
    use ChecksAuthenticatedSession;

    public const JENIS_LOKASI = LaboranJadwalService::JENIS_LOKASI;
    public const STATUS_LIST  = LaboranJadwalService::STATUS_LIST;

    protected LaboranJadwalService $service;

    public function __construct(?LaboranJadwalService $service = null)
    {
        $this->service = $service ?? app(LaboranJadwalService::class);
    }

    // =========================================================================
    // 1. HALAMAN PENJADWALAN
    // =========================================================================

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $context = LaboranScope::resolve($user);

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'delete');

        $search        = trim($request->query('q', ''));
        $labFilter     = trim($request->query('lab', ''));
        $jenisFilter   = trim($request->query('jenis', ''));
        $statusFilter  = trim($request->query('status', ''));
        $tanggalFilter = trim($request->query('tanggal', ''));

        $query = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk', 'ljp.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'ljp.*',
                'gtk.nama as guru_nama',
                'gtk.nip as guru_nip',
                'rb.nama as rombel_nama'
            );

        LaboranScope::apply($query, 'ljp.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ljp.mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('ljp.topik_praktik', 'like', "%{$search}%")
                  ->orWhere('ljp.ruang_lab_nama', 'like', "%{$search}%")
                  ->orWhere('ljp.guru_pendamping', 'like', "%{$search}%")
                  ->orWhere('gtk.nama', 'like', "%{$search}%")
                  ->orWhere('rb.nama', 'like', "%{$search}%");
            });
        }

        if ($labFilter !== '') {
            $query->where('ljp.ruang_lab_nama', $labFilter);
        }

        if ($jenisFilter !== '' && array_key_exists($jenisFilter, self::JENIS_LOKASI)) {
            $query->where('ljp.jenis_lokasi', $jenisFilter);
        }

        if ($statusFilter !== '') {
            $query->where('ljp.status', $statusFilter);
        }

        if ($tanggalFilter !== '') {
            $query->where('ljp.tanggal', $tanggalFilter);
        }

        // Statistik ringkas (dalam cakupan jurusan)
        $statBase = DB::table('laboran_jadwal_penggunaan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal       = (clone $statBase)->count();
        $statHariIni     = (clone $statBase)->where('tanggal', now()->toDateString())->count();
        $statBerlangsung = (clone $statBase)->where('status', 'berlangsung')->count();
        $statSelesai     = (clone $statBase)->where('status', 'selesai')->count();

        // Master ruang dari Sarpras & Aset
        $daftarRuangSarpras = DB::table('sarpras_ruang')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get(['id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai', 'kondisi'])
            ->map(function ($r) {
                $r->jenis_lokasi = $this->service->detectJenisLokasi($r->nama_ruang, $r->gedung);
                return $r;
            });

        // Lokasi tambahan yang pernah dipakai
        $existingQuery = DB::table('laboran_jadwal_penggunaan')
            ->select('ruang_lab_nama', 'jenis_lokasi')
            ->distinct();
        LaboranScope::apply($existingQuery, 'jurusan_id', $context);
        $existingLokasi = $existingQuery->get();

        $namaMaster = $daftarRuangSarpras->pluck('nama_ruang')->all();
        $lokasiTambahan = $existingLokasi
            ->filter(fn($r) => !in_array($r->ruang_lab_nama, $namaMaster, true))
            ->map(fn($r) => (object) [
                'id'           => null,
                'kode_ruang'   => null,
                'nama_ruang'   => $r->ruang_lab_nama,
                'gedung'       => null,
                'lantai'       => null,
                'kondisi'      => null,
                'jenis_lokasi' => $r->jenis_lokasi ?: 'lainnya',
            ])
            ->values();

        $lokasiByJenis = [];
        foreach (array_keys(self::JENIS_LOKASI) as $jenis) {
            $lokasiByJenis[$jenis] = $daftarRuangSarpras
                ->filter(fn($r) => $r->jenis_lokasi === $jenis)
                ->values();
        }
        foreach ($lokasiTambahan as $extra) {
            $key = array_key_exists($extra->jenis_lokasi, $lokasiByJenis) ? $extra->jenis_lokasi : 'lainnya';
            $lokasiByJenis[$key]->push($extra);
        }

        $daftarLab = $daftarRuangSarpras->pluck('nama_ruang')
            ->merge($lokasiTambahan->pluck('nama_ruang'))
            ->unique()
            ->values()
            ->all();

        $allGtk = DB::table('gtk')->orderBy('nama', 'asc')->get(['ptk_id', 'nama', 'nip']);

        $allRombel = DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)
                  ->orWhere('jenis_rombel', '1')
                  ->orWhere('jenis_rombel_str', 'Kelas');
            })
            ->orderBy('nama', 'asc')
            ->get(['rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id_str', 'jurusan_id_str']);

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderBy('ljp.tanggal', 'desc')
            ->orderBy('ljp.jam_mulai', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // ==================== Matriks Grid (pola Jadwal KBM) ====================
        $viewMode    = $request->get('view_mode', 'grid');
        $selectedHari = $request->get('hari_grid') ?: 'Senin';
        if (!in_array($selectedHari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'], true)) {
            $selectedHari = 'Senin';
        }

        // Slot jam pelajaran dari pengaturan KBM (Jam ke-1..N dengan jam mulai/selesai)
        $timeSlots = \App\Models\JadwalPengaturan::getSlots($selectedHari);

        // Seluruh lokasi praktik (kolom matriks): master Sarpras + lokasi tercatat
        $gridLokasi = collect($daftarLab)->sort()->values();

        // Sesi jadwal hari terpilih (status <> batal, semua tanggal berkala ditata sama)
        $dayQuery = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk as g', 'ljp.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->where('ljp.status', '<>', 'batal')
            ->select('ljp.*', 'g.nama as guru_nama', 'rb.nama as rombel_nama');
        LaboranScope::apply($dayQuery, 'ljp.jurusan_id', $context);

        // Hari grid cocok dengan nama hari tanggal yang direkam — sesi mingguan
        // berulang pada hari yang sama, sehingga cukup filter nama hari tanggalnya.
        $dayQuery->whereRaw("DAYNAME(ljp.tanggal) = ?", [$selectedHari]);
        $daySchedules = $dayQuery->orderBy('ljp.tanggal')->orderBy('ljp.jam_mulai')->get();

        $gridMatrix = [];
        $gridOccupied = [];
        foreach ($daySchedules as $item) {
            $lokasi = (string) $item->ruang_lab_nama;
            $mulai = substr((string) $item->jam_mulai, 0, 5);
            $selesai = substr((string) $item->jam_selesai, 0, 5);

            $slotMulai = null;
            $slotSelesai = null;
            foreach ($timeSlots as $ke => $slot) {
                if ($slotMulai === null && $mulai >= $slot['mulai'] && $mulai < $slot['selesai']) {
                    $slotMulai = (int) $ke;
                }
                if ($slotSelesai === null && $selesai > $slot['mulai'] && $selesai <= $slot['selesai']) {
                    $slotSelesai = (int) $ke;
                }
            }
            if ($slotMulai === null) {
                continue; // Di luar rentang jam pelajaran KBM (mis. malam) — lewati
            }
            $slotSelesai = $slotSelesai ?? $slotMulai;

            $gridMatrix[$lokasi][$slotMulai] = $item;
            for ($k = $slotMulai + 1; $k <= $slotSelesai; $k++) {
                $gridOccupied[$lokasi][$k] = $item->id;
            }
        }

        // Hitung sesi per hari untuk badge tab hari
        $countPerHari = DB::table('laboran_jadwal_penggunaan')
            ->selectRaw('DAYNAME(tanggal) as hari, COUNT(*) as total')
            ->where('status', '<>', 'batal');
        LaboranScope::apply($countPerHari, 'jurusan_id', $context);
        $countPerHari = $countPerHari->groupBy(DB::raw('DAYNAME(tanggal)'))->pluck('total', 'hari')->toArray();

        $jenisLokasiLabels = self::JENIS_LOKASI;
        $statusLabels      = self::STATUS_LIST;

        // Pengaturan pemberlakuan jadwal (2 tab: Mingguan / Semester).
        // Klik tab = memilih mode aktif (disimpan langsung ke pengaturan).
        $pengaturanJadwal = LaboranJadwalService::getPengaturanJadwal();
        $modePilih = trim((string) $request->query('mode_pemberlakuan', ''));
        if ($modePilih !== '' && array_key_exists($modePilih, LaboranJadwalService::MODE_PEMBERLAKUAN)
            && $modePilih !== $pengaturanJadwal->mode_pemberlakuan) {
            DB::table('laboran_jadwal_pengaturan')->where('id', 1)->update([
                'mode_pemberlakuan' => $modePilih,
                'updated_at'        => now(),
            ]);
            $pengaturanJadwal = LaboranJadwalService::getPengaturanJadwal();
        }
        $modeDiberlakukan = LaboranJadwalService::getModeDiberlakukan();
        $modePemberlakuanLabels = LaboranJadwalService::MODE_PEMBERLAKUAN;

        return view('dashboard.laboran.jadwal', compact(
            'list', 'search', 'labFilter', 'jenisFilter', 'statusFilter', 'tanggalFilter',
            'daftarLab', 'daftarRuangSarpras', 'lokasiTambahan', 'lokasiByJenis',
            'allGtk', 'allRombel', 'statTotal', 'statHariIni', 'statBerlangsung', 'statSelesai',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'perPage',
            'jenisLokasiLabels', 'statusLabels',
            'pengaturanJadwal', 'modeDiberlakukan', 'modePemberlakuanLabels'
        ) + [
            'context'        => $context,
            'scopeBadge'     => LaboranScope::badge($context),
            'jurusanOptions' => LaboranScope::jurusanOptions($context),
        ]);
    }

    /**
     * AJAX: daftar pembelajaran (mapel + guru) dari kelas.
     */
    public function pembelajaranByRombel(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $rombelId = trim((string) $request->query('rombel_id', ''));
        if ($rombelId === '') {
            return response()->json(['status' => 'success', 'pembelajaran' => []]);
        }

        return response()->json([
            'status'       => 'success',
            'pembelajaran' => $this->resolvePembelajaranKelas($rombelId),
        ]);
    }

    /**
     * AJAX: slot jadwal KBM aktif untuk satu rombel (Point 1).
     */
    public function kbmByRombel(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $rombelId = trim((string) $request->query('rombel_id', ''));
        if ($rombelId === '') {
            return response()->json(['status' => 'success', 'kbm' => []]);
        }

        return response()->json([
            'status' => 'success',
            'kbm'    => $this->service->resolveKbmSlots($rombelId),
        ]);
    }

    protected function resolvePembelajaranKelas(string $rombelId): array
    {
        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        if (!$rombel) {
            return [];
        }

        $targetIds = [$rombelId];
        if (!empty($rombel->nama)) {
            $pilihanIds = DB::table('rombongan_belajar')
                ->where('nama', $rombel->nama)
                ->where('rombongan_belajar_id', '<>', $rombelId)
                ->pluck('rombongan_belajar_id')
                ->all();
            $targetIds = array_merge($targetIds, $pilihanIds);
        }

        $rows = DB::table('pembelajaran as p')
            ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
            ->whereIn('p.rombongan_belajar_id', $targetIds)
            ->whereNotNull('p.nama_mata_pelajaran')
            ->where('p.nama_mata_pelajaran', '<>', '')
            ->select(
                'p.pembelajaran_id',
                'p.mata_pelajaran_id',
                'p.nama_mata_pelajaran',
                'p.mata_pelajaran_id_str',
                'p.ptk_id',
                'p.jam_mengajar_per_minggu',
                'gtk.nama as nama_guru',
                'gtk.nip as nip_guru'
            )
            ->orderBy('p.nama_mata_pelajaran', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $nama = trim((string) ($row->nama_mata_pelajaran ?: $row->mata_pelajaran_id_str));
            if ($nama === '' || isset($result[$nama])) {
                continue;
            }
            $result[$nama] = [
                'pembelajaran_id'     => $row->pembelajaran_id,
                'mata_pelajaran_id'   => $row->mata_pelajaran_id,
                'nama_mata_pelajaran' => $nama,
                'ptk_id'              => $row->ptk_id,
                'nama_guru'           => $row->nama_guru,
                'nip_guru'            => $row->nip_guru,
                'jam_per_minggu'      => $row->jam_mengajar_per_minggu,
            ];
        }

        return array_values($result);
    }

    // =========================================================================
    // 2. SIMPAN / UBAH / HAPUS JADWAL
    // =========================================================================

    public function store(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $data = $this->validatedPayload($request);

        $pemberlakuan = $data['pemberlakuan'];
        $baseTanggal  = $data['tanggal'];
        $bulan        = $request->filled('pemberlakuan_bulan') ? (int) $request->input('pemberlakuan_bulan') : null;
        $tahun        = $request->filled('pemberlakuan_tahun') ? (int) $request->input('pemberlakuan_tahun') : null;
        $semester     = $request->filled('pemberlakuan_semester') ? (string) $request->input('pemberlakuan_semester') : null;
        $tahunAjaran  = $request->filled('pemberlakuan_ta') ? (string) $request->input('pemberlakuan_ta') : null;
        $hari         = (array) $request->input('hari', []);
        $tanggalAkhir = $request->filled('tanggal_akhir') ? (string) $request->input('tanggal_akhir') : null;

        $targetDates = $this->service->generateDates(
            $pemberlakuan, $baseTanggal, $bulan, $tahun, $semester, $tahunAjaran, $hari, $tanggalAkhir
        );

        if ($conflict = $this->service->findConflictForDates($data, $targetDates)) {
            return $this->jsonOrRedirect($request, false, $conflict, 422);
        }

        $seriId = count($targetDates) > 1 ? 'SERI-' . strtoupper(Str::random(12)) : null;
        $jurusanId = LaboranScope::jurusanForWrite($request->input('jurusan_id'), $context);
        $currentPetugas = $this->currentPtkId();

        $rowsToInsert = [];
        foreach ($targetDates as $tgl) {
            $rowsToInsert[] = [
                'jurusan_id'             => $jurusanId,
                'ruang_lab_nama'         => $data['ruang_lab_nama'],
                'jenis_lokasi'           => $data['jenis_lokasi'],
                'ptk_id'                 => $data['ptk_id'],
                'ptk_ids'                => $data['ptk_ids'],
                'guru_pendamping'        => $data['guru_pendamping'],
                'rombel_id'              => $data['rombel_id'],
                'pembelajaran_id'        => $data['pembelajaran_id'],
                'mata_pelajaran_id'      => $data['mata_pelajaran_id'],
                'mata_pelajaran'         => $data['mata_pelajaran'],
                'topik_praktik'          => $data['topik_praktik'],
                'tanggal'                => $tgl,
                'pemberlakuan'           => $pemberlakuan,
                'jadwal_seri_id'         => $seriId,
                'jam_mulai'              => $data['jam_mulai'],
                'jam_selesai'            => $data['jam_selesai'],
                'alat_bahan_digunakan'   => $data['alat_bahan_digunakan'],
                'status'                 => $data['status'],
                'laporan_kerusakan'      => $data['laporan_kerusakan'],
                'laboran_petugas_ptk_id' => $currentPetugas,
                'created_at'             => now(),
                'updated_at'             => now(),
            ];
        }

        DB::table('laboran_jadwal_penggunaan')->insert($rowsToInsert);

        $msg = count($rowsToInsert) > 1
            ? 'Jadwal praktik berkala (' . count($rowsToInsert) . ' sesi) berhasil dibuat.'
            : 'Jadwal penggunaan lokasi praktik berhasil disimpan.';

        return $this->jsonOrRedirect($request, true, $msg);
    }

    public function update(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_jadwal_penggunaan')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return $this->jsonOrRedirect($request, false, 'Jadwal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $isStatusOnly = $request->input('form_mode') === 'status'
            || (!$request->has('tanggal') && $request->has('status'));

        if ($isStatusOnly) {
            return $this->updateStatus($request, $id);
        }

        $data = $this->validatedPayload($request);

        if ($conflict = $this->service->findConflictSingleDate($data, $data['tanggal'], $this->service->extractAllPtkIds($data), (int) $id)) {
            return $this->jsonOrRedirect($request, false, $conflict, 422);
        }

        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->update([
            'jurusan_id'           => LaboranScope::jurusanForWrite($request->input('jurusan_id'), $context),
            'ruang_lab_nama'       => $data['ruang_lab_nama'],
            'jenis_lokasi'         => $data['jenis_lokasi'],
            'ptk_id'               => $data['ptk_id'],
            'ptk_ids'              => $data['ptk_ids'],
            'guru_pendamping'      => $data['guru_pendamping'],
            'rombel_id'            => $data['rombel_id'],
            'pembelajaran_id'      => $data['pembelajaran_id'],
            'mata_pelajaran_id'    => $data['mata_pelajaran_id'],
            'mata_pelajaran'       => $data['mata_pelajaran'],
            'topik_praktik'        => $data['topik_praktik'],
            'tanggal'              => $data['tanggal'],
            'pemberlakuan'         => $data['pemberlakuan'],
            'jam_mulai'            => $data['jam_mulai'],
            'jam_selesai'          => $data['jam_selesai'],
            'alat_bahan_digunakan' => $data['alat_bahan_digunakan'],
            'status'               => $data['status'],
            'laporan_kerusakan'    => $data['laporan_kerusakan'],
            'updated_at'           => now(),
        ]);

        return $this->jsonOrRedirect($request, true, 'Jadwal penggunaan lokasi praktik berhasil diperbarui.');
    }

    public function updateStatus(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_jadwal_penggunaan')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return $this->jsonOrRedirect($request, false, 'Jadwal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $request->validate([
            'status'            => 'required|in:' . implode(',', array_keys(self::STATUS_LIST)),
            'laporan_kerusakan' => 'nullable|string|max:1000',
        ]);

        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->update([
            'status'            => $request->status,
            'laporan_kerusakan' => $request->laporan_kerusakan,
            'updated_at'        => now(),
        ]);

        return $this->jsonOrRedirect($request, true, 'Status penggunaan lokasi praktik berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_jadwal_penggunaan')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return $this->jsonOrRedirect($request, false, 'Jadwal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        if ($request->boolean('hapus_seri') && !empty($row->jadwal_seri_id)) {
            DB::table('laboran_jadwal_penggunaan')->where('jadwal_seri_id', $row->jadwal_seri_id)->delete();
            return $this->jsonOrRedirect($request, true, 'Seluruh rangkaian jadwal dalam seri berhasil dihapus.');
        }

        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->delete();

        return $this->jsonOrRedirect($request, true, 'Jadwal praktik berhasil dihapus.');
    }

    // =========================================================================
    // 3b. PEMBERLAKUAN JADWAL (AKTIFKAN MODE MINGGUAN / SEMESTER)
    // =========================================================================

    /**
     * Aktifkan / matikan pemberlakuan salah satu mode jadwal (mingguan / semester).
     * Pola eksklusif seperti pemberlakuan Jadwal KBM: hanya satu mode aktif.
     */
    public function togglePemberlakuan(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!RolePermission::canAccess($user ?: $role, 'menu_laboran', 'update')) {
            return $this->jsonOrRedirect($request, false, 'Anda tidak memiliki hak untuk mengubah pemberlakuan jadwal.', 403);
        }

        $data = $request->validate([
            'mode'   => 'required|in:mingguan,semester',
            'status' => 'required|in:aktif,draft',
        ]);

        $ptkId = $this->currentPtkId();

        DB::table('laboran_jadwal_pengaturan')->where('id', 1)->update([
            'mode_pemberlakuan'       => $data['mode'],
            'status'                  => $data['status'],
            'diberlakukan_oleh_ptk_id' => $data['status'] === 'aktif' ? $ptkId : null,
            'updated_at'              => now(),
        ]);

        $label = LaboranJadwalService::MODE_PEMBERLAKUAN[$data['mode']];
        $msg = $data['status'] === 'aktif'
            ? "Pemberlakuan jadwal <strong>{$label}</strong> berhasil diaktifkan sebagai acuan penentuan jadwal praktik."
            : "Pemberlakuan jadwal {$label} dimatikan (draft).";

        return $this->jsonOrRedirect($request, true, $msg);
    }

    // =========================================================================
    // 3. LAPORAN PER PERIODE
    // =========================================================================

    public function laporan(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $data = $this->service->buildLaporanData($request);

        return view('dashboard.laboran.laporan', $data);
    }

    public function cetakLaporan(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $data = $this->service->buildLaporanData($request);

        return view('dashboard.laboran.cetak-laporan', $data);
    }

    public function cetakJadwalPerGuru(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $data = $this->service->buildJadwalPerGuru($request);

        return view('dashboard.laboran.jadwal-per-guru-cetak', $data);
    }

    // =========================================================================
    // 4. HELPER
    // =========================================================================

    /**
     * Daftar opsi tahun ajaran (±3 tahun sekitar tahun berjalan).
     *
     * @return array<int,string>
     */
    public static function tahunAjaranOptions(): array
    {
        $y = (int) date('Y');
        $ops = [];
        for ($i = $y - 1; $i <= $y + 2; $i++) {
            $ops[] = $i . '/' . ($i + 1);
        }

        return $ops;
    }

    public static function tahunAjaranAktif(): string
    {
        $y = (int) date('Y');

        return date('n') >= 7 ? "{$y}/" . ($y + 1) : ($y - 1) . "/{$y}";
    }

    protected function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'ruang_lab_nama'       => 'required|string|max:100',
            'jenis_lokasi'         => 'nullable|in:' . implode(',', array_keys(self::JENIS_LOKASI)),
            'rombel_id'            => 'required|string|max:100',
            'pembelajaran_id'      => 'nullable|string|max:50',
            'mata_pelajaran_id'    => 'nullable|string|max:50',
            'mata_pelajaran'       => 'nullable|string|max:255',
            'ptk_id'               => 'nullable|string|max:100',
            'ptk_ids'              => 'nullable|array',
            'ptk_ids.*'            => 'nullable|string|max:100',
            'guru_pendamping'      => 'nullable|string|max:500',
            'topik_praktik'        => 'nullable|string|max:255',
            'tanggal'              => 'required|date',
            'pemberlakuan'         => 'nullable|in:sekali,mingguan,bulanan,semester',
            'hari'                 => 'nullable|array',
            'hari.*'               => 'nullable|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'tanggal_akhir'        => 'nullable|date|after_or_equal:tanggal',
            'jam_mulai'            => 'required',
            'jam_selesai'          => 'required|after:jam_mulai',
            'alat_bahan_digunakan' => 'nullable|string|max:1000',
            'status'               => 'nullable|in:' . implode(',', array_keys(self::STATUS_LIST)),
            'laporan_kerusakan'    => 'nullable|string|max:1000',
        ], [
            'ruang_lab_nama.required' => 'Lokasi praktik wajib dipilih.',
            'rombel_id.required'      => 'Kelas / rombel wajib dipilih agar mata pelajaran terisi otomatis.',
            'jam_selesai.after'       => 'Jam selesai harus lebih besar dari jam mulai.',
        ]);

        $rombelId       = trim((string) $validated['rombel_id']);
        $pembelajaranId = trim((string) ($validated['pembelajaran_id'] ?? ''));
        $mapelNama      = trim((string) ($validated['mata_pelajaran'] ?? ''));
        $mapelId        = trim((string) ($validated['mata_pelajaran_id'] ?? ''));
        $ptkId          = trim((string) ($validated['ptk_id'] ?? ''));

        $pembelajaran = null;
        if ($pembelajaranId !== '') {
            $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $pembelajaranId)->first();
        }

        if (!$pembelajaran && $rombelId !== '') {
            $q = DB::table('pembelajaran')->where('rombongan_belajar_id', $rombelId);
            if ($mapelNama !== '') {
                $q->where(function ($w) use ($mapelNama) {
                    $w->where('nama_mata_pelajaran', $mapelNama)
                      ->orWhere('mata_pelajaran_id_str', $mapelNama);
                });
            }
            if ($ptkId !== '') {
                $q->where('ptk_id', $ptkId);
            }
            $pembelajaran = $q->first();
        }

        if ($pembelajaran) {
            $pembelajaranId = $pembelajaranId !== '' ? $pembelajaranId : $pembelajaran->pembelajaran_id;
            $mapelNama      = $mapelNama !== ''
                ? $mapelNama
                : trim((string) ($pembelajaran->nama_mata_pelajaran ?: $pembelajaran->mata_pelajaran_id_str));
            $mapelId        = $mapelId !== '' ? $mapelId : (string) ($pembelajaran->mata_pelajaran_id ?? '');
            $ptkId          = $ptkId !== '' ? $ptkId : (string) ($pembelajaran->ptk_id ?? '');
        }

        if ($mapelNama === '' && $rombelId !== '') {
            $fallback = DB::table('pembelajaran')
                ->where('rombongan_belajar_id', $rombelId)
                ->orderBy('nama_mata_pelajaran')
                ->first();
            if ($fallback) {
                $mapelNama      = trim((string) ($fallback->nama_mata_pelajaran ?: $fallback->mata_pelajaran_id_str));
                $mapelId        = (string) ($fallback->mata_pelajaran_id ?? '');
                $ptkId          = $ptkId !== '' ? $ptkId : (string) ($fallback->ptk_id ?? '');
                $pembelajaranId = $pembelajaranId !== '' ? $pembelajaranId : $fallback->pembelajaran_id;
            }
        }

        // Guru pengampu wajib ada: kolom ptk_id di tabel jadwal bersifat NOT NULL.
        // Bila pembelajaran tidak ditemukan (mapel input bebas), ambil satu guru
        // dari kelas yang sama atau guru siapa pun sebagai penanggung jawab sesi.
        if ($ptkId === '' && $rombelId !== '') {
            $ptkId = (string) (DB::table('pembelajaran as p')
                ->join('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
                ->where('p.rombongan_belajar_id', $rombelId)
                ->orderBy('p.nama_mata_pelajaran')
                ->value('p.ptk_id') ?? '');
        }
        if ($ptkId === '') {
            $ptkId = (string) (DB::table('gtk')->orderBy('nama')->value('ptk_id') ?? '');
        }

        if ($mapelNama === '') {
            $mapelNama = 'Praktik Kejuruan';
        }

        $jenis = $validated['jenis_lokasi'] ?? null;
        if (!$jenis) {
            $jenis = $this->service->detectJenisLokasi($validated['ruang_lab_nama'], null);
        }

        $allTeacherIds = $this->service->extractAllPtkIds([
            'ptk_id'  => $ptkId,
            'ptk_ids' => $validated['ptk_ids'] ?? [],
        ]);

        $primaryPtkId = $allTeacherIds[0] ?? ($ptkId !== '' ? $ptkId : null);
        $companionNames = $this->service->formatGuruPendamping($allTeacherIds, $primaryPtkId)
            ?: (trim((string) ($validated['guru_pendamping'] ?? '')) ?: null);

        $topik = trim((string) ($validated['topik_praktik'] ?? ''));

        return [
            'ruang_lab_nama'       => trim((string) $validated['ruang_lab_nama']),
            'jenis_lokasi'         => $jenis,
            'rombel_id'            => $rombelId,
            'pembelajaran_id'      => $pembelajaranId !== '' ? $pembelajaranId : null,
            'mata_pelajaran_id'    => $mapelId !== '' ? $mapelId : null,
            'mata_pelajaran'       => $mapelNama,
            'ptk_id'               => $primaryPtkId,
            'ptk_ids'              => !empty($allTeacherIds) ? json_encode($allTeacherIds) : null,
            'guru_pendamping'      => $companionNames,
            'topik_praktik'        => $topik !== '' ? $topik : null,
            'tanggal'              => $validated['tanggal'],
            'pemberlakuan'         => $validated['pemberlakuan'] ?? 'mingguan',
            'jam_mulai'            => $validated['jam_mulai'],
            'jam_selesai'          => $validated['jam_selesai'],
            'alat_bahan_digunakan' => $validated['alat_bahan_digunakan'] ?? null,
            'status'               => $validated['status'] ?? 'dijadwalkan',
            'laporan_kerusakan'    => $validated['laporan_kerusakan'] ?? null,
        ];
    }

    protected function resolveDateRange(string $tipe, int $bulan, int $tahun, int $triwulan, string $semester, string $tahunAjaran): array
    {
        return $this->service->resolveDateRange($tipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran);
    }

    public static function detectJenisLokasi(?string $namaRuang, ?string $gedung = null): string
    {
        return (new LaboranJadwalService())->detectJenisLokasi($namaRuang, $gedung);
    }

    protected function findConflict(array $data, ?int $ignoreId = null): ?string
    {
        $allTeacherIds = $this->service->extractAllPtkIds($data);
        return $this->service->findConflictSingleDate($data, $data['tanggal'], $allTeacherIds, $ignoreId);
    }

    protected function jsonOrRedirect(Request $request, bool $ok, string $msg, int $status = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => $ok ? 'success' : 'error',
                'message' => $msg,
            ], $status);
        }

        return redirect()->back()->with($ok ? 'success' : 'error', $msg);
    }

    protected function currentPtkId(): ?string
    {
        $user = session('user');
        return is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
    }
}
