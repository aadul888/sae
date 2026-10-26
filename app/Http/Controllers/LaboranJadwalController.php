<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;

class LaboranJadwalController extends Controller
{
    use ChecksAuthenticatedSession;

    /** Pilihan jenis lokasi praktik yang didukung. */
    public const JENIS_LOKASI = [
        'lab'      => 'Laboratorium',
        'bengkel'  => 'Bengkel Praktik',
        'lahan'    => 'Lahan / Kebun Praktik',
        'lapangan' => 'Lapangan / Area Terbuka',
        'lainnya'  => 'Lokasi Lainnya',
    ];

    /** Status sesi yang didukung. */
    public const STATUS_LIST = [
        'dijadwalkan' => 'Dijadwalkan',
        'berlangsung' => 'Berlangsung',
        'selesai'     => 'Selesai',
        'batal'       => 'Dibatalkan',
    ];

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

        // Master ruang dari Sarpras & Aset (termasuk lahan/lapangan praktik)
        $daftarRuangSarpras = DB::table('sarpras_ruang')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get(['id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai', 'kondisi'])
            ->map(function ($r) {
                $r->jenis_lokasi = self::detectJenisLokasi($r->nama_ruang, $r->gedung);
                return $r;
            });

        // Lokasi yang sudah pernah dipakai tetapi tidak ada di master Sarpras (dalam cakupan)
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

        // Daftar lokasi dikelompokkan per jenis (untuk dropdown bertingkat)
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

        // Kelas reguler saja agar mata pelajaran sinkron dengan data Dapodik
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

        $jenisLokasiLabels = self::JENIS_LOKASI;
        $statusLabels      = self::STATUS_LIST;

        return view('dashboard.laboran.jadwal', compact(
            'list',
            'search',
            'labFilter',
            'jenisFilter',
            'statusFilter',
            'tanggalFilter',
            'daftarLab',
            'daftarRuangSarpras',
            'lokasiTambahan',
            'lokasiByJenis',
            'allGtk',
            'allRombel',
            'statTotal',
            'statHariIni',
            'statBerlangsung',
            'statSelesai',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'perPage',
            'jenisLokasiLabels',
            'statusLabels'
        ) + [
            'context'        => $context,
            'scopeBadge'     => LaboranScope::badge($context),
            'jurusanOptions' => LaboranScope::jurusanOptions($context),
        ]);
    }

    /**
     * AJAX: daftar mata pelajaran sebuah kelas (otomatis dari data pembelajaran Dapodik).
     * Dipakai untuk mengisi dropdown Mapel + Guru Pengampu saat kelas dipilih.
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
     * Ambil daftar pembelajaran (mapel + guru) untuk satu rombel kelas.
     * Rombel "Matapelajaran Pilihan" dengan nama sama ikut disertakan karena
     * praktik kejuruan sering diampu pada rombel pilihan.
     */
    protected function resolvePembelajaranKelas(string $rombelId): array
    {
        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();
        if (!$rombel) {
            return [];
        }

        $targetIds = [$rombelId];

        $siblingIds = DB::table('rombongan_belajar')
            ->where('nama', $rombel->nama)
            ->where('rombongan_belajar_id', '<>', $rombelId)
            ->pluck('rombongan_belajar_id')
            ->all();

        if (!empty($siblingIds)) {
            $targetIds = array_merge($targetIds, $siblingIds);
        }

        $rows = DB::table('pembelajaran as p')
            ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
            ->whereIn('p.rombongan_belajar_id', $targetIds)
            ->select(
                'p.pembelajaran_id',
                'p.mata_pelajaran_id',
                'p.nama_mata_pelajaran',
                'p.mata_pelajaran_id_str',
                'p.ptk_id',
                'p.jam_mengajar_per_minggu',
                'g.nama as nama_guru',
                'g.nip as nip_guru'
            )
            ->orderBy('p.nama_mata_pelajaran', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $nama = trim((string) ($row->nama_mata_pelajaran ?: $row->mata_pelajaran_id_str));
            if ($nama === '') {
                continue;
            }

            $key = $nama . '|' . ($row->ptk_id ?? '');
            if (isset($result[$key])) {
                continue;
            }

            $result[$key] = [
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

        if ($conflict = $this->findConflict($data)) {
            return $this->jsonOrRedirect($request, false, $conflict, 422);
        }

        DB::table('laboran_jadwal_penggunaan')->insert([
            'jurusan_id'             => LaboranScope::jurusanForWrite($request->input('jurusan_id'), $context),
            'ruang_lab_nama'         => $data['ruang_lab_nama'],
            'jenis_lokasi'           => $data['jenis_lokasi'],
            'ptk_id'                 => $data['ptk_id'],
            'rombel_id'              => $data['rombel_id'],
            'pembelajaran_id'        => $data['pembelajaran_id'],
            'mata_pelajaran_id'      => $data['mata_pelajaran_id'],
            'mata_pelajaran'         => $data['mata_pelajaran'],
            'topik_praktik'          => $data['topik_praktik'],
            'tanggal'                => $data['tanggal'],
            'jam_mulai'              => $data['jam_mulai'],
            'jam_selesai'            => $data['jam_selesai'],
            'alat_bahan_digunakan'   => $data['alat_bahan_digunakan'],
            'status'                 => $data['status'],
            'laporan_kerusakan'      => $data['laporan_kerusakan'],
            'laboran_petugas_ptk_id' => $this->currentPtkId(),
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return $this->jsonOrRedirect($request, true, 'Jadwal penggunaan lokasi praktik berhasil disimpan.');
    }

    /**
     * Ubah jadwal (form penuh) ATAU ubah status sesi (form status).
     */
    public function update(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_jadwal_penggunaan')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return $this->jsonOrRedirect($request, false,
                'Jadwal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $isStatusOnly = $request->input('form_mode') === 'status'
            || (!$request->has('tanggal') && $request->has('status'));

        if ($isStatusOnly) {
            return $this->updateStatus($request, $id);
        }

        $data = $this->validatedPayload($request);

        if ($conflict = $this->findConflict($data, (int) $id)) {
            return $this->jsonOrRedirect($request, false, $conflict, 422);
        }

        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->update([
            'jurusan_id'           => LaboranScope::jurusanForWrite($request->input('jurusan_id'), $context),
            'ruang_lab_nama'       => $data['ruang_lab_nama'],
            'jenis_lokasi'         => $data['jenis_lokasi'],
            'ptk_id'               => $data['ptk_id'],
            'rombel_id'            => $data['rombel_id'],
            'pembelajaran_id'      => $data['pembelajaran_id'],
            'mata_pelajaran_id'    => $data['mata_pelajaran_id'],
            'mata_pelajaran'       => $data['mata_pelajaran'],
            'topik_praktik'        => $data['topik_praktik'],
            'tanggal'              => $data['tanggal'],
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
            return $this->jsonOrRedirect($request, false,
                'Jadwal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
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
            return $this->jsonOrRedirect($request, false,
                'Jadwal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->delete();

        return $this->jsonOrRedirect($request, true, 'Jadwal praktik berhasil dihapus.');
    }

    // =========================================================================
    // 3. LAPORAN PER PERIODE (Bulan, Triwulan, Semester, Tahun)
    // =========================================================================

    public function laporan(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $data = $this->buildLaporanData($request);

        return view('dashboard.laboran.laporan', $data);
    }

    public function cetakLaporan(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $data = $this->buildLaporanData($request);

        return view('dashboard.laboran.cetak-laporan', $data);
    }

    /**
     * Susun seluruh data laporan untuk satu periode tertentu.
     */
    protected function buildLaporanData(Request $request): array
    {
        $context = LaboranScope::resolve();

        $periodeTipe = (string) $request->query('periode', 'bulan');
        if (!in_array($periodeTipe, ['bulan', 'triwulan', 'semester', 'tahun'], true)) {
            $periodeTipe = 'bulan';
        }

        $bulan    = (int) $request->query('bulan', date('n'));
        $tahun    = (int) $request->query('tahun', date('Y'));
        $triwulan = (int) $request->query('triwulan', (int) ceil(date('n') / 3));
        $semester = (string) $request->query('semester', date('n') >= 7 ? '1' : '2');

        $curYear     = (int) date('Y');
        $defaultTA   = date('n') >= 7 ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";
        $tahunAjaran = (string) $request->query('tahun_ajaran', $defaultTA);

        $range = $this->resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran);

        $jenisFilter  = (string) $request->query('jenis', '');
        $lokasiFilter = (string) $request->query('lab', '');
        $guruFilter   = (string) $request->query('ptk_id', '');

        $query = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk', 'ljp.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->whereBetween('ljp.tanggal', [$range['start'], $range['end']])
            ->select(
                'ljp.*',
                'gtk.nama as guru_nama',
                'gtk.nip as guru_nip',
                'rb.nama as rombel_nama'
            );

        LaboranScope::apply($query, 'ljp.jurusan_id', $context);

        if ($jenisFilter !== '' && array_key_exists($jenisFilter, self::JENIS_LOKASI)) {
            $query->where('ljp.jenis_lokasi', $jenisFilter);
        }

        if ($lokasiFilter !== '') {
            $query->where('ljp.ruang_lab_nama', $lokasiFilter);
        }

        if ($guruFilter !== '') {
            $query->where('ljp.ptk_id', $guruFilter);
        }

        $items = $query->orderBy('ljp.tanggal', 'asc')
            ->orderBy('ljp.jam_mulai', 'asc')
            ->get();

        // ---- Ringkasan ----
        $totalSesi        = $items->count();
        $totalSelesai     = $items->where('status', 'selesai')->count();
        $totalBatal       = $items->where('status', 'batal')->count();
        $totalBerlangsung = $items->where('status', 'berlangsung')->count();
        $totalDijadwalkan = $items->where('status', 'dijadwalkan')->count();

        $totalMenit = 0;
        foreach ($items as $it) {
            $totalMenit += $this->durasiMenit($it->jam_mulai, $it->jam_selesai);
        }
        $totalJam   = intdiv($totalMenit, 60);
        $sisaMenit  = $totalMenit % 60;
        $hariAktif  = $items->pluck('tanggal')->unique()->count();
        $lokasiUnik = $items->pluck('ruang_lab_nama')->unique()->count();
        $guruUnik   = $items->pluck('ptk_id')->filter()->unique()->count();
        $rombelUnik = $items->pluck('rombel_id')->filter()->unique()->count();

        $persentaseSelesai = $totalSesi > 0 ? round(($totalSelesai / $totalSesi) * 100, 1) : 0.0;

        // ---- Rekap per jenis lokasi ----
        $rekapJenis = [];
        foreach (self::JENIS_LOKASI as $key => $label) {
            $subset = $items->where('jenis_lokasi', $key);
            if ($subset->isEmpty()) {
                continue;
            }
            $menit = 0;
            foreach ($subset as $s) {
                $menit += $this->durasiMenit($s->jam_mulai, $s->jam_selesai);
            }
            $rekapJenis[] = [
                'key'     => $key,
                'label'   => $label,
                'sesi'    => $subset->count(),
                'selesai' => $subset->where('status', 'selesai')->count(),
                'jam'     => intdiv($menit, 60),
                'menit'   => $menit % 60,
                'lokasi'  => $subset->pluck('ruang_lab_nama')->unique()->count(),
            ];
        }

        // ---- Rekap per lokasi ----
        $rekapLokasi = $items->groupBy('ruang_lab_nama')->map(function ($group, $nama) {
            $menit = 0;
            foreach ($group as $g) {
                $menit += $this->durasiMenit($g->jam_mulai, $g->jam_selesai);
            }
            $jenis = $group->first()->jenis_lokasi ?: 'lainnya';

            return (object) [
                'nama'    => $nama,
                'jenis'   => $jenis,
                'label'   => self::JENIS_LOKASI[$jenis] ?? 'Lokasi Lainnya',
                'sesi'    => $group->count(),
                'selesai' => $group->where('status', 'selesai')->count(),
                'jam'     => intdiv($menit, 60),
                'menit'   => $menit % 60,
            ];
        })->sortByDesc('sesi')->values();

        // ---- Rekap per guru ----
        $rekapGuru = $items->groupBy(fn($i) => $i->ptk_id ?: '-')->map(function ($group, $ptkId) {
            $menit = 0;
            foreach ($group as $g) {
                $menit += $this->durasiMenit($g->jam_mulai, $g->jam_selesai);
            }
            $first = $group->first();

            return (object) [
                'ptk_id'  => $ptkId,
                'nama'    => $first->guru_nama ?: 'Tanpa Guru',
                'nip'     => $first->guru_nip,
                'sesi'    => $group->count(),
                'selesai' => $group->where('status', 'selesai')->count(),
                'jam'     => intdiv($menit, 60),
                'menit'   => $menit % 60,
                'mapel'   => $group->pluck('mata_pelajaran')->unique()->count(),
            ];
        })->sortByDesc('sesi')->values();

        // ---- Rekap per mata pelajaran ----
        $rekapMapel = $items->groupBy('mata_pelajaran')->map(function ($group, $mapel) {
            $menit = 0;
            foreach ($group as $g) {
                $menit += $this->durasiMenit($g->jam_mulai, $g->jam_selesai);
            }

            return (object) [
                'nama'    => $mapel ?: '-',
                'sesi'    => $group->count(),
                'selesai' => $group->where('status', 'selesai')->count(),
                'jam'     => intdiv($menit, 60),
                'menit'   => $menit % 60,
                'kelas'   => $group->pluck('rombel_id')->filter()->unique()->count(),
            ];
        })->sortByDesc('sesi')->values();

        // ---- Rekap bulanan (bila periode > 1 bulan) ----
        $rekapBulanan = [];
        if ($periodeTipe !== 'bulan') {
            foreach ($items->groupBy(fn($i) => Carbon::parse($i->tanggal)->format('Y-m')) as $ym => $group) {
                $menit = 0;
                foreach ($group as $g) {
                    $menit += $this->durasiMenit($g->jam_mulai, $g->jam_selesai);
                }
                $rekapBulanan[] = [
                    'label'   => Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y'),
                    'sesi'    => $group->count(),
                    'selesai' => $group->where('status', 'selesai')->count(),
                    'batal'   => $group->where('status', 'batal')->count(),
                    'jam'     => intdiv($menit, 60),
                    'menit'   => $menit % 60,
                    'hari'    => $group->pluck('tanggal')->unique()->count(),
                ];
            }
        }

        // ---- Master filter ----
        $daftarLokasi = DB::table('laboran_jadwal_penggunaan')
            ->select('ruang_lab_nama', 'jenis_lokasi')
            ->distinct()
            ->orderBy('ruang_lab_nama')
            ->get();

        $daftarGuru = DB::table('gtk')
            ->whereIn('ptk_id', DB::table('laboran_jadwal_penggunaan')->select('ptk_id')->distinct())
            ->orderBy('nama')
            ->get(['ptk_id', 'nama', 'nip']);

        // ---- Identitas sekolah & pejabat penanda tangan ----
        $sekolah     = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        $kepalaSekolah = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $kepalaLab = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'KEPALA_LAB')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip', 'gtk.nuptk')
            ->first();

        $petugas = $this->resolvePetugasLaboran();

        $orientasi = strtolower((string) $request->query('orientasi', 'landscape'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'landscape';
        }

        return [
            'periodeTipe'       => $periodeTipe,
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'triwulan'          => $triwulan,
            'semester'          => $semester,
            'tahunAjaran'       => $tahunAjaran,
            'range'             => $range,
            'items'             => $items,
            'jenisFilter'       => $jenisFilter,
            'lokasiFilter'      => $lokasiFilter,
            'guruFilter'        => $guruFilter,
            'totalSesi'         => $totalSesi,
            'totalSelesai'      => $totalSelesai,
            'totalBatal'        => $totalBatal,
            'totalBerlangsung'  => $totalBerlangsung,
            'totalDijadwalkan'  => $totalDijadwalkan,
            'totalJam'          => $totalJam,
            'sisaMenit'         => $sisaMenit,
            'hariAktif'         => $hariAktif,
            'lokasiUnik'        => $lokasiUnik,
            'guruUnik'          => $guruUnik,
            'rombelUnik'        => $rombelUnik,
            'persentaseSelesai' => $persentaseSelesai,
            'rekapJenis'        => $rekapJenis,
            'rekapLokasi'       => $rekapLokasi,
            'rekapGuru'         => $rekapGuru,
            'rekapMapel'        => $rekapMapel,
            'rekapBulanan'      => $rekapBulanan,
            'daftarLokasi'      => $daftarLokasi,
            'daftarGuru'        => $daftarGuru,
            'jenisLokasiLabels' => self::JENIS_LOKASI,
            'statusLabels'      => self::STATUS_LIST,
            'sekolah'           => $sekolah,
            'sekolahMeta'       => $sekolahMeta,
            'kepalaSekolah'     => $kepalaSekolah,
            'kepalaLab'         => $kepalaLab,
            'petugas'           => $petugas,
            'orientasi'         => $orientasi,
        ];
    }

    /**
     * Hitung rentang tanggal berdasarkan tipe periode.
     * Mendukung: bulan, triwulan, semester, tahun (tahun ajaran).
     */
    protected function resolveDateRange(string $tipe, int $bulan, int $tahun, int $triwulan, string $semester, string $tahunAjaran): array
    {
        if ($tipe === 'triwulan') {
            $y  = $tahun ?: (int) date('Y');
            $tw = $triwulan ?: (int) ceil(date('n') / 3);

            $map = [
                1 => ['01-01', 3, 'Triwulan I', 'Januari - Maret'],
                2 => ['04-01', 6, 'Triwulan II', 'April - Juni'],
                3 => ['07-01', 9, 'Triwulan III', 'Juli - September'],
                4 => ['10-01', 12, 'Triwulan IV', 'Oktober - Desember'],
            ];
            [$startMd, $endMonth, $labelTw, $bulanLabel] = $map[$tw] ?? $map[4];

            return [
                'start' => "{$y}-{$startMd}",
                'end'   => Carbon::createFromDate($y, $endMonth, 1)->endOfMonth()->toDateString(),
                'label' => "{$labelTw} ({$bulanLabel} {$y})",
            ];
        }

        if ($tipe === 'semester') {
            $parts = explode('/', $tahunAjaran !== '' ? $tahunAjaran : date('Y') . '/' . ((int) date('Y') + 1));
            $y1 = (int) ($parts[0] ?? date('Y'));
            $y2 = (int) ($parts[1] ?? ($y1 + 1));

            if ($semester === '1') {
                return [
                    'start' => "{$y1}-07-01",
                    'end'   => "{$y1}-12-31",
                    'label' => "Semester Ganjil TA {$y1}/{$y2} (Juli - Desember {$y1})",
                ];
            }

            return [
                'start' => "{$y2}-01-01",
                'end'   => "{$y2}-06-30",
                'label' => "Semester Genap TA {$y1}/{$y2} (Januari - Juni {$y2})",
            ];
        }

        if ($tipe === 'tahun') {
            if (str_contains($tahunAjaran, '/')) {
                $parts = explode('/', $tahunAjaran);
                $y1 = (int) $parts[0];
                $y2 = (int) ($parts[1] ?? ($y1 + 1));

                return [
                    'start' => "{$y1}-07-01",
                    'end'   => "{$y2}-06-30",
                    'label' => "Tahun Ajaran {$y1}/{$y2} (1 Juli {$y1} - 30 Juni {$y2})",
                ];
            }

            $y = $tahun ?: (int) date('Y');

            return [
                'start' => "{$y}-01-01",
                'end'   => "{$y}-12-31",
                'label' => "Tahun Kalender {$y}",
            ];
        }

        // Default: bulanan
        $b     = max(1, min(12, $bulan ?: (int) date('n')));
        $y     = $tahun ?: (int) date('Y');
        $start = Carbon::createFromDate($y, $b, 1)->startOfMonth();
        $end   = Carbon::createFromDate($y, $b, 1)->endOfMonth();

        return [
            'start' => $start->toDateString(),
            'end'   => $end->toDateString(),
            'label' => 'Bulan ' . $start->translatedFormat('F Y'),
        ];
    }

    // =========================================================================
    // 4. HELPER
    // =========================================================================

    /**
     * Validasi & normalisasi payload jadwal.
     * Mata pelajaran diambil otomatis dari kelas (pembelajaran) bila tidak diisi manual.
     */
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
            'topik_praktik'        => 'nullable|string|max:255',
            'tanggal'              => 'required|date',
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

        $mapelNama = trim((string) ($validated['mata_pelajaran'] ?? ''));
        $mapelId   = trim((string) ($validated['mata_pelajaran_id'] ?? ''));
        $ptkId     = trim((string) ($validated['ptk_id'] ?? ''));

        // Ambil data pembelajaran sebagai sumber kebenaran (mapel + guru pengampu)
        $pembelajaran = null;
        if ($pembelajaranId !== '') {
            $pembelajaran = DB::table('pembelajaran')->where('pembelajaran_id', $pembelajaranId)->first();
        }

        // Bila hanya kelas yang dipilih (tanpa pembelajaran_id), cocokkan lewat nama mapel
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

        // Terakhir: bila mapel tetap kosong, ambil dari kelas agar penyimpanan tidak gagal
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

        if ($mapelNama === '') {
            $mapelNama = 'Praktik Kejuruan';
        }

        $jenis = $validated['jenis_lokasi'] ?? null;
        if (!$jenis) {
            $jenis = self::detectJenisLokasi($validated['ruang_lab_nama'], null);
        }

        $topik = trim((string) ($validated['topik_praktik'] ?? ''));

        return [
            'ruang_lab_nama'       => trim((string) $validated['ruang_lab_nama']),
            'jenis_lokasi'         => $jenis,
            'rombel_id'            => $rombelId,
            'pembelajaran_id'      => $pembelajaranId !== '' ? $pembelajaranId : null,
            'mata_pelajaran_id'    => $mapelId !== '' ? $mapelId : null,
            'mata_pelajaran'       => $mapelNama,
            'ptk_id'               => $ptkId,
            'topik_praktik'        => $topik !== '' ? $topik : null,
            'tanggal'              => $validated['tanggal'],
            'jam_mulai'            => $validated['jam_mulai'],
            'jam_selesai'          => $validated['jam_selesai'],
            'alat_bahan_digunakan' => $validated['alat_bahan_digunakan'] ?? null,
            'status'               => $validated['status'] ?? 'dijadwalkan',
            'laporan_kerusakan'    => $validated['laporan_kerusakan'] ?? null,
        ];
    }

    /**
     * Deteksi bentrok jadwal pada lokasi yang sama atau guru yang sama.
     * Kembalikan pesan galat, atau null bila tidak ada bentrok.
     */
    protected function findConflict(array $data, ?int $ignoreId = null): ?string
    {
        $base = DB::table('laboran_jadwal_penggunaan')
            ->where('tanggal', $data['tanggal'])
            ->where('status', '<>', 'batal')
            ->where(function ($q) use ($data) {
                $q->where('jam_mulai', '<', $data['jam_selesai'])
                  ->where('jam_selesai', '>', $data['jam_mulai']);
            });

        if ($ignoreId) {
            $base->where('id', '<>', $ignoreId);
        }

        $bentrokLokasi = (clone $base)
            ->where('ruang_lab_nama', $data['ruang_lab_nama'])
            ->first();

        if ($bentrokLokasi) {
            return 'Lokasi "' . $data['ruang_lab_nama'] . '" sudah dipakai pada '
                . substr((string) $bentrokLokasi->jam_mulai, 0, 5) . ' - '
                . substr((string) $bentrokLokasi->jam_selesai, 0, 5)
                . ' (' . $bentrokLokasi->mata_pelajaran . '). Pilih jam atau lokasi lain.';
        }

        if (!empty($data['ptk_id'])) {
            $bentrokGuru = (clone $base)->where('ptk_id', $data['ptk_id'])->first();
            if ($bentrokGuru) {
                $namaGuru = DB::table('gtk')->where('ptk_id', $data['ptk_id'])->value('nama') ?: 'Guru';

                return $namaGuru . ' sudah memiliki jadwal praktik lain pada '
                    . substr((string) $bentrokGuru->jam_mulai, 0, 5) . ' - '
                    . substr((string) $bentrokGuru->jam_selesai, 0, 5)
                    . ' (' . $bentrokGuru->ruang_lab_nama . ').';
            }
        }

        return null;
    }

    /**
     * Tebak jenis lokasi dari nama ruang / gedung.
     */
    public static function detectJenisLokasi(?string $namaRuang, ?string $gedung = null): string
    {
        $nama = strtoupper(trim((string) $namaRuang . ' ' . (string) $gedung));

        foreach (['LAHAN', 'KEBUN', 'GREENHOUSE', 'SAWAH', 'PLOT', 'HIDROPONIK', 'SCREEN HOUSE', 'TANAMAN', 'NURSERY'] as $kw) {
            if (str_contains($nama, $kw)) {
                return 'lahan';
            }
        }

        foreach (['LAPANGAN', 'AREA TERBUKA', 'FIELD', 'GROUND'] as $kw) {
            if (str_contains($nama, $kw)) {
                return 'lapangan';
            }
        }

        foreach (['BENGKEL', 'BENGK', 'WORKSHOP', 'GEDUNG PRAKTIK'] as $kw) {
            if (str_contains($nama, $kw)) {
                return 'bengkel';
            }
        }

        foreach (['LAB', 'LABORATORIUM', 'STUDIO'] as $kw) {
            if (str_contains($nama, $kw)) {
                return 'lab';
            }
        }

        return 'lainnya';
    }

    protected function durasiMenit(?string $mulai, ?string $selesai): int
    {
        if (!$mulai || !$selesai) {
            return 0;
        }

        try {
            $a = Carbon::createFromFormat('H:i:s', strlen($mulai) === 5 ? $mulai . ':00' : $mulai);
            $b = Carbon::createFromFormat('H:i:s', strlen($selesai) === 5 ? $selesai . ':00' : $selesai);
        } catch (\Throwable $e) {
            return 0;
        }

        $diff = (int) $a->diffInMinutes($b, false);

        return $diff > 0 ? $diff : 0;
    }

    protected function currentPtkId(): ?string
    {
        $user = session('user');
        if (is_array($user)) {
            return $user['ptk_id'] ?? null;
        }

        return $user->ptk_id ?? null;
    }

    /**
     * Profil petugas laboran yang sedang login (untuk blok tanda tangan laporan).
     */
    protected function resolvePetugasLaboran(): array
    {
        $user = session('user');
        $nama = is_array($user)
            ? ($user['nama'] ?? ($user['name'] ?? 'Petugas Laboran'))
            : ($user->nama ?? ($user->name ?? 'Petugas Laboran'));

        $ptkId = $this->currentPtkId();
        $gtk   = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : null;

        return [
            'nama'   => $gtk->nama ?? $nama,
            'nip'    => $gtk->nip ?? '-',
            'ptk_id' => $ptkId,
        ];
    }

    /**
     * Respons seragam untuk AJAX maupun submit form biasa.
     */
    protected function jsonOrRedirect(Request $request, bool $ok, string $message, int $code = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => $ok ? 'success' : 'error',
                'message' => $message,
            ], $code);
        }

        return redirect()
            ->route('dashboard.laboran.jadwal.index')
            ->with($ok ? 'success' : 'error', $message);
    }
}
