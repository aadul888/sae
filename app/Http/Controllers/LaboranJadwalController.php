<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Http\Controllers\Concerns\NormalisasiPayloadJadwalLaboran;
use App\Models\RolePermission;
use App\Services\LaboranJadwalService;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaboranJadwalController extends Controller
{
    use ChecksAuthenticatedSession;
    use NormalisasiPayloadJadwalLaboran;

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

        // Pengaturan pemberlakuan jadwal (2 tab: Mingguan / Semester).
        // Pemberlakuan ketat: klik tab = otomatis berlaku, hanya 1 mode yang diberlakukan.
        $pengaturanJadwal = LaboranJadwalService::getPengaturanJadwal();
        $modePilih = trim((string) $request->query('mode_pemberlakuan', ''));
        if ($modePilih !== '' && array_key_exists($modePilih, LaboranJadwalService::MODE_PEMBERLAKUAN)) {
            DB::table('laboran_jadwal_pengaturan')->where('id', 1)->update([
                'mode_pemberlakuan'        => $modePilih,
                'status'                   => 'aktif',
                'diberlakukan_oleh_ptk_id' => $this->currentPtkId(),
                'updated_at'               => now(),
            ]);
            $pengaturanJadwal = DB::table('laboran_jadwal_pengaturan')->where('id', 1)->first();
        } elseif (($pengaturanJadwal->status ?? '') !== 'aktif') {
            DB::table('laboran_jadwal_pengaturan')->where('id', 1)->update([
                'mode_pemberlakuan'        => $pengaturanJadwal->mode_pemberlakuan ?: 'semester',
                'status'                   => 'aktif',
                'diberlakukan_oleh_ptk_id' => $this->currentPtkId(),
                'updated_at'               => now(),
            ]);
            $pengaturanJadwal = DB::table('laboran_jadwal_pengaturan')->where('id', 1)->first();
        }

        $activeMode = $pengaturanJadwal->mode_pemberlakuan ?? 'semester';
        $modeDiberlakukan = $activeMode;
        $modePemberlakuanLabels = LaboranJadwalService::MODE_PEMBERLAKUAN;

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

        // Filter ketat sesuai mode pemberlakuan aktif (hanya 1 acuan berlaku: mingguan / semester)
        $query->where(function ($w) use ($activeMode) {
            $w->where('ljp.pemberlakuan', $activeMode)
              ->orWhere('ljp.pemberlakuan', 'sekali')
              ->orWhereNull('ljp.pemberlakuan');
        });

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

        // Statistik ringkas (dalam cakupan jurusan & mode pemberlakuan aktif)
        $statBase = DB::table('laboran_jadwal_penggunaan')
            ->where(function ($w) use ($activeMode) {
                $w->where('pemberlakuan', $activeMode)
                  ->orWhere('pemberlakuan', 'sekali')
                  ->orWhereNull('pemberlakuan');
            });
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

        // Kelas (rombel) dibatasi sesuai cakupan jurusan pengguna.
        $allRombelQuery = DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)
                  ->orWhere('jenis_rombel', '1')
                  ->orWhere('jenis_rombel_str', 'Kelas');
            });
        LaboranScope::apply($allRombelQuery, 'jurusan_id', $context);
        $allRombel = $allRombelQuery
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

        // Slot jam pelajaran per hari dari Pengaturan KBM (Jam ke-1..N per hari,
        // termasuk Upacara, Pembiasaan, dan Istirahat) — setiap hari aktif.
        $dailySlotCounts = \App\Models\JadwalPengaturan::getDailySlotCounts();
        $slotsPerHari = [];
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) {
            $slotsPerHari[$h] = \App\Models\JadwalPengaturan::getSlots($h);
        }
        $timeSlots = $slotsPerHari[$selectedHari];

        // Matriks: baris = Jam Pelajaran (maks JP antar hari), KOLOM = HARI.
        // Lokasi praktik tampil di dalam kartu sesi bersama mapel/guru/kelas.
        $gridLokasi = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        // Sesi seluruh hari (status <> batal) — disaring ketat sesuai mode pemberlakuan aktif
        $allQuery = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk as g', 'ljp.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->where('ljp.status', '<>', 'batal')
            ->where(function ($w) use ($activeMode) {
                $w->where('ljp.pemberlakuan', $activeMode)
                  ->orWhere('ljp.pemberlakuan', 'sekali')
                  ->orWhereNull('ljp.pemberlakuan');
            });

        $allQuery->select('ljp.*', 'g.nama as guru_nama', 'rb.nama as rombel_nama');
        LaboranScope::apply($allQuery, 'ljp.jurusan_id', $context);
        $allSchedules = $allQuery->orderBy('ljp.tanggal')->orderBy('ljp.jam_mulai')->get();

        // MySQL DAYNAME() mengembalikan nama Inggris; petakan ke nama Indonesia.
        $dayNameMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];

        // Matriks [hari][slotJP] — setiap hari memuat sesinya sendiri (multi sesi per jam didukung).
        $gridMatrix = [];
        $gridOccupied = [];
        $seenSlotSessions = [];

        foreach ($allSchedules as $item) {
            $hari = $dayNameMap[date('l', strtotime((string) $item->tanggal))] ?? '';
            if (!in_array($hari, $gridLokasi, true)) {
                continue;
            }
            $slots = $slotsPerHari[$hari];

            $mulai = substr((string) $item->jam_mulai, 0, 5);
            $selesai = substr((string) $item->jam_selesai, 0, 5);

            $slotMulai = null;
            $slotSelesai = null;
            foreach ($slots as $ke => $slot) {
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

            // Kunci sesi unik pada matriks mingguan (agar tidak duplikat berkali-kali)
            $sessionKey = $hari . '|' . $slotMulai . '|' . $slotSelesai . '|' . ($item->ruang_lab_nama ?? '') . '|' . ($item->rombel_id ?? '') . '|' . ($item->ptk_id ?? '') . '|' . ($item->mata_pelajaran ?? '');
            if (isset($seenSlotSessions[$sessionKey])) {
                continue;
            }
            $seenSlotSessions[$sessionKey] = true;

            // Simpan kartu di slotMulai dengan informasi durasi/slotAkhir
            $gridMatrix[$hari][$slotMulai][] = [
                'item'      => $item,
                'slotAkhir' => $slotSelesai,
                'isStart'   => true,
            ];

            // Tandai slot berikutnya yang tercakup agar tidak menumpuk sebagai slot kosong
            for ($k = $slotMulai + 1; $k <= $slotSelesai; $k++) {
                $gridOccupied[$hari][$k] = true;
            }
        }

        // Klaster baris kontigu per hari → satu <td rowspan> per klaster (pola Jadwal KBM).
        // Aman untuk multi-sesi per jam: seluruh sesi dalam klaster tampil di satu sel.
        $gridClusters = [];
        foreach ($gridLokasi as $hari) {
            $rowContent = [];
            foreach ($gridMatrix[$hari] ?? [] as $r => $items) {
                $rowContent[(int) $r] = true;
                foreach ($items as $it) {
                    for ($k = (int) $r + 1; $k <= (int) $it['slotAkhir']; $k++) {
                        $rowContent[$k] = true;
                    }
                }
            }
            ksort($rowContent);

            $clusters = [];
            $cur = null;
            foreach (array_keys($rowContent) as $r) {
                if ($cur !== null && $r === $cur['end'] + 1) {
                    $cur['end'] = $r;
                } else {
                    if ($cur !== null) {
                        $clusters[] = $cur;
                    }
                    $cur = ['start' => $r, 'end' => $r];
                }
            }
            if ($cur !== null) {
                $clusters[] = $cur;
            }

            foreach ($clusters as $c) {
                $sesi = [];
                foreach ($gridMatrix[$hari] ?? [] as $r => $items) {
                    if ($r >= $c['start'] && $r <= $c['end']) {
                        foreach ($items as $it) {
                            $it['rowStart'] = (int) $r;
                            $sesi[] = $it;
                        }
                    }
                }
                $gridClusters[$hari][$c['start']] = [
                    'span' => $c['end'] - $c['start'] + 1,
                    'sesi' => $sesi,
                ];
            }
        }

        // Hitung sesi per hari untuk badge header (jumlah sesi unik terjadwal pada hari tersebut)
        $countPerHari = [];
        foreach ($gridMatrix as $h => $slotsMap) {
            $totalCount = 0;
            foreach ($slotsMap as $ke => $items) {
                $totalCount += count($items);
            }
            $countPerHari[$h] = $totalCount;
        }

        // Maks JP antar hari untuk tinggi baris matriks
        $maxSlots = 0;
        foreach ($slotsPerHari as $h => $slots) {
            $maxSlots = max($maxSlots, count($slots));
        }

        // Rutinitas harian (Upacara / Pembiasaan / Istirahat) dari Pengaturan KBM
        $pengaturanKbm = \App\Models\JadwalPengaturan::getSettings();
        $upacaraConfig = $pengaturanKbm->upacara ?? [];
        $pembiasaanConfig = $pengaturanKbm->pembiasaan ?? [];
        $rutinitas = [
            'upacara' => [
                'aktif'  => !empty($upacaraConfig['aktif']),
                'hari'   => $upacaraConfig['hari'] ?? 'Senin',
                'jam_ke' => (int) ($upacaraConfig['jam_ke'] ?? 1),
                'durasi' => (int) ($upacaraConfig['durasi_menit'] ?? 45),
                'nama'   => $upacaraConfig['nama'] ?? 'Upacara Bendera',
            ],
            'pembiasaan' => [
                'aktif'  => !empty($pembiasaanConfig['aktif']),
                'hari'   => $pembiasaanConfig['hari'] ?? 'Jumat',
                'jam_ke' => (int) ($pembiasaanConfig['jam_ke'] ?? 1),
                'durasi' => (int) ($pembiasaanConfig['durasi_menit'] ?? 40),
                'nama'   => $pembiasaanConfig['nama'] ?? 'Pembiasaan',
            ],
            'slotCounts' => $dailySlotCounts,
        ];

        $jenisLokasiLabels = self::JENIS_LOKASI;
        $statusLabels      = self::STATUS_LIST;

        $perluPilihMode = false;

        return view('dashboard.laboran.jadwal', compact(
            'list', 'search', 'labFilter', 'jenisFilter', 'statusFilter', 'tanggalFilter',
            'daftarLab', 'daftarRuangSarpras', 'lokasiTambahan', 'lokasiByJenis',
            'allGtk', 'allRombel', 'statTotal', 'statHariIni', 'statBerlangsung', 'statSelesai',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'perPage',
            'jenisLokasiLabels', 'statusLabels',
            'pengaturanJadwal', 'activeMode', 'modeDiberlakukan', 'modePemberlakuanLabels',
            'viewMode', 'selectedHari', 'timeSlots', 'gridLokasi', 'gridMatrix',
            'gridOccupied', 'gridClusters', 'countPerHari', 'slotsPerHari', 'maxSlots', 'rutinitas',
            'perluPilihMode'
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
     * AJAX: slot jam pelajaran (JP) dari Pengaturan KBM — dipakai dropdown
     * alokasi waktu modal jadwal (Jam ke-1 s/d Jam ke-N).
     */
    public function slotList(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $tanggal = trim((string) $request->query('tanggal', ''));
        $hari = null;
        if ($tanggal !== '') {
            $dayMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
            $hari = $dayMap[date('l', strtotime($tanggal))] ?? null;
            if (!in_array($hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'], true)) {
                $hari = null;
            }
        }

        // Slot per hari (menghormati jumlah JP per hari, Upacara, Pembiasaan,
        // dan Istirahat dari Pengaturan Jadwal KBM).
        $slotsPerHari = [];
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h) {
            $slotsPerHari[$h] = \App\Models\JadwalPengaturan::getSlots($h);
        }

        return response()->json([
            'status' => 'success',
            'hari'   => $hari,
            'slots'  => $slotsPerHari[$hari ?? 'Senin'] ?? [],
            'per_hari' => $slotsPerHari,
        ]);
    }

    /**
     * AJAX: daftar gabungan guru pengampu + mata pelajaran + kelas dari data
     * pembelajaran (seluruh rombel). Dipakai dropdown simpel modal jadwal.
     */
    public function guruMapelList(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $rombelId = trim((string) $request->query('rombel_id', ''));

        $rows = DB::table('pembelajaran as p')
            ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'p.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->whereNotNull('p.nama_mata_pelajaran')
            ->where('p.nama_mata_pelajaran', '<>', '')
            ->when($rombelId !== '', fn ($q) => $q->where('p.rombongan_belajar_id', $rombelId))
            ->select(
                'p.pembelajaran_id',
                'p.mata_pelajaran_id',
                'p.nama_mata_pelajaran',
                'p.mata_pelajaran_id_str',
                'p.rombongan_belajar_id',
                'rb.nama as rombel_nama',
                'p.ptk_id',
                'g.nama as nama_guru'
            )
            ->orderBy('g.nama', 'asc')
            ->orderBy('p.nama_mata_pelajaran', 'asc')
            ->get();

        $list = [];
        $seen = [];
        foreach ($rows as $r) {
            $mapel = trim((string) ($r->nama_mata_pelajaran ?: $r->mata_pelajaran_id_str));
            if ($mapel === '') {
                continue;
            }

            $guruNama = trim((string) $r->nama_guru);
            $key = ($guruNama ?: '-') . '|' . $mapel . '|' . ($r->rombongan_belajar_id ?? '-');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $list[] = [
                'pembelajaran_id'   => $r->pembelajaran_id,
                'mata_pelajaran_id' => (string) ($r->mata_pelajaran_id ?? ''),
                'mata_pelajaran'    => $mapel,
                'rombel_id'         => $r->rombongan_belajar_id,
                'rombel_nama'       => $r->rombel_nama ?: '-',
                'ptk_id'            => $r->ptk_id,
                'nama_guru'         => $guruNama ?: 'Belum Ditugaskan',
                'label'             => $rombelId !== ''
                    ? ($guruNama ?: 'Belum Ditugaskan') . ' — ' . $mapel
                    : ($guruNama ?: 'Belum Ditugaskan') . ' — ' . $mapel . ' (' . ($r->rombel_nama ?: '-') . ')',
            ];
        }

        return response()->json(['status' => 'success', 'guru_mapel' => $list]);
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
        if (empty($hari) && $request->filled('hari_semester')) {
            $hari = [(string) $request->input('hari_semester')];
        }
        $tanggalAkhir = $request->filled('tanggal_akhir') ? (string) $request->input('tanggal_akhir') : null;
        $minggu = null;
        if (in_array($pemberlakuan, ['mingguan', 'semester'], true)) {
            $mingguMulai   = $request->filled('pemberlakuan_minggu') ? max(1, (int) $request->input('pemberlakuan_minggu')) : null;
            $mingguAkhir   = $request->filled('pemberlakuan_minggu_akhir') ? max(1, (int) $request->input('pemberlakuan_minggu_akhir')) : null;
            if ($mingguMulai !== null) {
                // Minggu ke-N s/d ke-M => durasi (M - N + 1) minggu sejak tanggal awal.
                $minggu = $mingguAkhir !== null
                    ? max(1, min(52, $mingguAkhir - $mingguMulai + 1))
                    : 1;
            }
        }

        $targetDates = $this->service->generateDates(
            $pemberlakuan, $baseTanggal, $bulan, $tahun, $semester, $tahunAjaran, $hari, $tanggalAkhir, $minggu
        );

        // Validasi rutinitas & kuota JP per HARI yang benar-benar dihasilkan
        // (mode mingguan/bulanan bisa mencakup beberapa hari berbeda).
        $perHari = [];
        foreach ($targetDates as $tgl) {
            $h = $this->service->hariDariTanggal($tgl);
            if ($h && !isset($perHari[$h])) {
                $perHari[$h] = $tgl;
            }
        }
        if (empty($perHari)) {
            // Tanggal acuan jatuh pada hari tanpa KBM (Sabtu/Minggu): tidak ada
            // slot jadwal yang bisa dipakai, jadi tolak dengan pesan jelas
            // daripada memvalidasi memakai jam hari lain.
            $namaHari = Carbon::parse($baseTanggal)->locale('id')->translatedFormat('l');
            return $this->jsonOrRedirect($request, false,
                'Tanggal ' . Carbon::parse($baseTanggal)->format('d/m/Y') . ' jatuh pada hari '
                . $namaHari . ' yang tidak memiliki jadwal KBM. Pilih tanggal pada hari sekolah.',
                422);
        }

        foreach ($perHari as $h => $tglContoh) {
            if ($pesan = $this->service->findRoutineOverlap($data, $h)) {
                return $this->jsonOrRedirect($request, false, $pesan, 422);
            }
            // Kuota JP hanya divalidasi bila pembelajaran dipilih eksplisit (kontrak modal),
            // agar tebakan mapel otomatis tidak memblokir praktik secara keliru.
            if ($request->filled('pembelajaran_id')
                && ($pesan = $this->service->findKuotaOverflow($data, $h, null, $tglContoh))) {
                return $this->jsonOrRedirect($request, false, $pesan, 422);
            }
        }

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

        $hariEdit = $this->service->hariDariTanggal((string) $data['tanggal']) ?? 'Senin';
        if ($pesan = $this->service->findRoutineOverlap($data, $hariEdit)) {
            return $this->jsonOrRedirect($request, false, $pesan, 422);
        }
        if ($request->filled('pembelajaran_id')
            && ($pesan = $this->service->findKuotaOverflow($data, $hariEdit, (int) $id))) {
            return $this->jsonOrRedirect($request, false, $pesan, 422);
        }

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

    /**
     * Semester yang sedang berjalan (1 = Ganjil: Juli-Desember, 2 = Genap: Januari-Juni).
     */
    public static function semesterAktif(): string
    {
        return (int) date('n') >= 7 ? '1' : '2';
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
