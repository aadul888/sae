<?php

namespace App\Http\Controllers;

use App\Models\KalenderPendidikan;
use App\Models\RolePermission;
use App\Models\TendikAktivitas;
use App\Models\TendikIndikatorKinerja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TendikTargetCapaianController extends Controller
{
    /**
     * Hitung rentang tanggal berdasarkan parameter periode
     */
    protected function resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran, $tanggal = null)
    {
        $startDate = null;
        $endDate = null;
        $labelPeriode = '';

        if ($periodeTipe === 'hari') {
            $tgl = $tanggal ?: date('Y-m-d');
            $startDate = $tgl;
            $endDate = $tgl;
            $labelPeriode = 'Hari ' . Carbon::parse($tgl)->translatedFormat('l, d F Y');
        } elseif ($periodeTipe === 'minggu') {
            $tgl = $tanggal ?: date('Y-m-d');
            $c = Carbon::parse($tgl);
            $startDate = $c->copy()->startOfWeek()->toDateString();
            $endDate = $c->copy()->endOfWeek()->toDateString();
            $labelPeriode = 'Minggu Ini (' . Carbon::parse($startDate)->translatedFormat('d M') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y') . ')';
        } elseif ($periodeTipe === 'triwulan') {
            $y = (int) ($tahun ?: date('Y'));
            $tw = (int) ($triwulan ?: ceil(date('n') / 3));

            switch ($tw) {
                case 1:
                    $startDate = "{$y}-01-01";
                    $endDate = Carbon::createFromDate($y, 3, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan I (Januari - Maret {$y})";
                    break;
                case 2:
                    $startDate = "{$y}-04-01";
                    $endDate = Carbon::createFromDate($y, 6, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan II (April - Juni {$y})";
                    break;
                case 3:
                    $startDate = "{$y}-07-01";
                    $endDate = Carbon::createFromDate($y, 9, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan III (Juli - September {$y})";
                    break;
                case 4:
                default:
                    $startDate = "{$y}-10-01";
                    $endDate = Carbon::createFromDate($y, 12, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan IV (Oktober - Desember {$y})";
                    break;
            }
        } elseif ($periodeTipe === 'semester') {
            $parts = explode('/', $tahunAjaran ?: date('Y') . '/' . (date('Y') + 1));
            $y1 = (int) ($parts[0] ?? date('Y'));
            $y2 = (int) ($parts[1] ?? ($y1 + 1));

            if ($semester == '1') {
                $startDate = "{$y1}-07-01";
                $endDate = "{$y1}-12-31";
                $labelPeriode = "Semester Ganjil TA {$y1}/{$y2}";
            } else {
                $startDate = "{$y2}-01-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Semester Genap TA {$y1}/{$y2}";
            }
        } elseif ($periodeTipe === 'tahun') {
            $y = (int) ($tahun ?: date('Y'));
            $startDate = "{$y}-01-01";
            $endDate = "{$y}-12-31";
            $labelPeriode = "Tahun Kalender {$y}";
        } elseif ($periodeTipe === 'tahun_ajaran') {
            $parts = explode('/', $tahunAjaran ?: (date('n') >= 7 ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y')));
            $y1 = (int) $parts[0];
            $y2 = (int) ($parts[1] ?? ($y1 + 1));
            $startDate = "{$y1}-07-01";
            $endDate = "{$y2}-06-30";
            $labelPeriode = "Tahun Ajaran {$y1}/{$y2}";
        } else {
            // Mode Bulan (Default)
            $b = str_pad((string) ($bulan ?: date('m')), 2, '0', STR_PAD_LEFT);
            $y = (int) ($tahun ?: date('Y'));
            $startCarbon = Carbon::createFromDate($y, (int) $b, 1)->startOfMonth();
            $endCarbon = Carbon::createFromDate($y, (int) $b, 1)->endOfMonth();

            $startDate = $startCarbon->toDateString();
            $endDate = $endCarbon->toDateString();
            $labelPeriode = 'Bulan ' . $startCarbon->translatedFormat('F Y');
        }

        return [
            'start' => $startDate,
            'end'   => $endDate,
            'label' => $labelPeriode,
        ];
    }

    /**
     * Halaman Utama Target & Capaian Pekerjaan Tendik (Indikator Input Harian)
     */
    public function index(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $userId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
        $ptkId = is_array($sessionUser) ? ($sessionUser['ptk_id'] ?? null) : ($sessionUser->ptk_id ?? null);
        $role = is_array($sessionUser) ? ($sessionUser['role'] ?? '') : ($sessionUser->role ?? '');
        $userName = is_array($sessionUser) ? ($sessionUser['nama'] ?? ($sessionUser['name'] ?? 'Tenaga Kependidikan')) : ($sessionUser->nama ?? ($sessionUser->name ?? 'Tenaga Kependidikan'));

        // Cek wewenang Kepala TAS & Bidang Tugas Pengguna
        $isKepalaTas = str_contains(strtolower($role), 'admin');
        $isKepegawaian = false;
        $activeBidang = 'umum';

        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $duties = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->pluck('rtt.kode');

            if ($duties->contains('KEPALA_TAS')) {
                $isKepalaTas = true;
            }
            if ($duties->contains('STAF_KEPEGAWAIAN') || $isKepalaTas) {
                $isKepegawaian = true;
            }

            $codeMap = [
                'KEPALA_TAS' => 'kepala_tas',
                'STAF_KESISWAAN' => 'kesiswaan',
                'STAF_KEPEGAWAIAN' => 'kepegawaian',
                'STAF_SARPRAS' => 'sarpras',
                'LABORAN' => 'laboran',
                'PUSTAKAWAN' => 'perpustakaan',
                'TEKNISI_IT' => 'teknisi',
                'SATPAM' => 'keamanan',
                'PENJAGA_SEKOLAH' => 'penjaga',
                'STAF_PERSURATAN' => 'persuratan',
            ];

            foreach ($duties as $d) {
                if (isset($codeMap[$d])) {
                    $activeBidang = $codeMap[$d];
                    break;
                }
            }
        }

        $canViewMonitoringPegawai = $isKepalaTas || $isKepegawaian;

        // 1. Parameter Filter Periode
        $periodeTipe = $request->input('periode', 'bulan'); // hari, minggu, bulan, triwulan, semester, tahun, tahun_ajaran
        $tanggalPilihan = $request->input('tanggal', date('Y-m-d'));
        $bulan = (int) $request->input('bulan', date('n'));
        $tahun = (int) $request->input('tahun', date('Y'));
        $triwulan = (int) $request->input('triwulan', ceil(date('n') / 3));
        $semester = (string) $request->input('semester', (date('n') >= 7 ? '1' : '2'));

        $curYear = (int) date('Y');
        $defaultTA = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";
        $tahunAjaran = (string) $request->input('tahun_ajaran', $defaultTA);

        $range = $this->resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran, $tanggalPilihan);

        // Filter Bidang: Kepala TAS dapat memilih semua atau per bidang; Tendik default bidang aktif atau sesuai pilihan bidang
        $filterBidang = $request->input('bidang', ($isKepalaTas ? '' : $activeBidang));

        // 2. Data Master Tendik
        $tendikQuery = DB::table('gtk')
            ->where(function ($sub) {
                $sub->where('jenis_ptk_id_str', 'LIKE', '%Tenaga Kependidikan%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Tendik%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Tata Usaha%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Laboran%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Pustakawan%')
                    ->orWhere('jenis_ptk_id_str', 'NOT LIKE', '%Guru%');
            })
            ->where('jenis_ptk_id_str', 'NOT LIKE', '%Guru%')
            ->select('ptk_id', 'nama', 'nip', 'jenis_ptk_id_str', 'jenis_kelamin', 'status_kepegawaian_id_str')
            ->orderBy('nama', 'asc');

        $tendikStaff = $tendikQuery->get();
        $totalTendik = $tendikStaff->count();

        // 3. Indikator Kepatuhan Input Harian (Hari Ini: Realtime)
        $today = date('Y-m-d');
        $todayActivities = TendikAktivitas::whereDate('tanggal', $today)->get();
        $todayGroupedByPtk = $todayActivities->groupBy('ptk_id');

        $kepatuhanHarianList = $tendikStaff->map(function ($pegawai) use ($todayGroupedByPtk) {
            $acts = $todayGroupedByPtk->get($pegawai->ptk_id, collect());
            $sudahInput = $acts->isNotEmpty();
            $selesaiCount = $acts->where('status', 'selesai')->count();
            $jamPertama = $acts->sortBy('jam_mulai')->first()?->jam_mulai;

            return (object) [
                'ptk_id'        => $pegawai->ptk_id,
                'nama'          => $pegawai->nama,
                'nip'           => $pegawai->nip,
                'jabatan'       => $pegawai->jenis_ptk_id_str ?: 'Tendik',
                'sudah_input'   => $sudahInput,
                'total_input'   => $acts->count(),
                'selesai_count' => $selesaiCount,
                'jam_input'     => $jamPertama ? substr($jamPertama, 0, 5) : null,
                'status_kpi'    => $sudahInput ? ($selesaiCount > 0 ? 'tercapai' : 'proses') : 'belum',
            ];
        });

        $totalSudahInputHariIni = $kepatuhanHarianList->where('sudah_input', true)->count();
        $totalBelumInputHariIni = $totalTendik - $totalSudahInputHariIni;
        $persenKepatuhanHariIni = $totalTendik > 0 ? round(($totalSudahInputHariIni / $totalTendik) * 100, 1) : 0;

        // Status Input Pribadi untuk Tendik
        $userTodayActs = $todayActivities->filter(function ($a) use ($userId, $ptkId) {
            if ($userId && $a->user_id == $userId) return true;
            if ($ptkId && $a->ptk_id == $ptkId) return true;
            return false;
        });
        $userSudahInputHariIni = $userTodayActs->isNotEmpty();
        $userTodayCount = $userTodayActs->count();
        $userTodaySelesai = $userTodayActs->where('status', 'selesai')->count();

        // 4. Perhitungan Target & Capaian Periode Terpilih
        $targetAktivitasPerHari = 1; // Default standar: 1 aktivitas/pekerjaan per hari per tendik
        $hariEfektif = KalenderPendidikan::hitungHariEfektif($range['start'], $range['end'], 'gtk');
        if ($hariEfektif <= 0) {
            $hariEfektif = max(1, Carbon::parse($range['start'])->diffInDaysFiltered(fn(Carbon $date) => !$date->isSunday(), Carbon::parse($range['end'])) + 1);
        }

        $targetTotalPeriode = $totalTendik * $hariEfektif * $targetAktivitasPerHari;

        // Query Aktivitas Pada Rentang Periode
        $actQuery = TendikAktivitas::whereBetween('tanggal', [$range['start'], $range['end']]);

        // Jika bukan Kepala TAS/Admin, hanya aktivitas milik sendiri dan bidang yang bersesuaian
        if (!$isKepalaTas) {
            $actQuery->where(function ($sq) use ($userId, $ptkId) {
                if ($userId) $sq->where('user_id', $userId);
                if ($ptkId) $sq->orWhere('ptk_id', $ptkId);
            });
            if ($filterBidang && $filterBidang !== 'all') {
                $actQuery->where('bidang', $filterBidang);
            }
            $targetTotalPeriode = $hariEfektif * $targetAktivitasPerHari;
        } elseif ($filterBidang && $filterBidang !== 'all') {
            $actQuery->where('bidang', $filterBidang);
        }

        $allActivities = $actQuery->get();
        $realisasiTotal = $allActivities->count();
        $realisasiSelesai = $allActivities->where('status', 'selesai')->count();
        $realisasiProses = $allActivities->where('status', 'proses')->count();
        $realisasiTertunda = $allActivities->where('status', 'tertunda')->count();

        $persenCapaian = $targetTotalPeriode > 0 ? round(($realisasiSelesai / $targetTotalPeriode) * 100, 1) : 0;

        // 5. Data Serial Time-Series untuk Chart Target vs Realisasi
        $chartTimeSeries = $this->buildTimeSeriesData($periodeTipe, $range, $allActivities, $totalTendik, $isKepalaTas);

        // 6. Distribusi Capaian Berdasarkan Bidang
        $bidangLabels = TendikAktivitas::BIDANG_LABELS;
        $chartBidang = [
            'labels' => [],
            'data'   => [],
        ];
        $actByBidang = $allActivities->groupBy('bidang');
        foreach ($bidangLabels as $bKey => $bName) {
            $cnt = $actByBidang->has($bKey) ? $actByBidang[$bKey]->count() : 0;
            if ($cnt > 0 || in_array($bKey, ['umum', 'persuratan', 'kepegawaian', 'kesiswaan', 'sarpras'])) {
                $chartBidang['labels'][] = $bName;
                $chartBidang['data'][] = $cnt;
            }
        }

        // 7. Distribusi Status (Doughnut Chart)
        $chartStatus = [
            'labels' => ['Selesai (Tuntas)', 'Sedang Proses', 'Tertunda / Kendala'],
            'data'   => [$realisasiSelesai, $realisasiProses, $realisasiTertunda],
        ];

        // 8. Peringkat Capaian per Tendik
        $actByPtk = $allActivities->groupBy('ptk_id');
        $capaianTendikList = $tendikStaff->map(function ($staf) use ($actByPtk, $hariEfektif, $targetAktivitasPerHari) {
            $acts = $actByPtk->get($staf->ptk_id, collect());
            $target = $hariEfektif * $targetAktivitasPerHari;
            $selesai = $acts->where('status', 'selesai')->count();
            $proses = $acts->where('status', 'proses')->count();
            $tertunda = $acts->where('status', 'tertunda')->count();
            $persen = $target > 0 ? round(($selesai / $target) * 100, 1) : 0;

            return (object) [
                'ptk_id'    => $staf->ptk_id,
                'nama'      => $staf->nama,
                'nip'       => $staf->nip,
                'target'    => $target,
                'total'     => $acts->count(),
                'selesai'   => $selesai,
                'proses'    => $proses,
                'tertunda'  => $tertunda,
                'persen'    => $persen,
                'kategori'  => $persen >= 100 ? 'Sangat Baik' : ($persen >= 75 ? 'Baik' : ($persen >= 50 ? 'Cukup' : 'Kurang')),
            ];
        })->sortByDesc('persen')->values();

        // 9. Matriks Sasaran & Indikator Kinerja per Bidang
        $matriksIndikatorList = collect();
        if (Schema::hasTable('tendik_indikator_kinerja')) {
            $indQuery = TendikIndikatorKinerja::where('is_active', true);
            if ($filterBidang && $filterBidang !== 'all') {
                $indQuery->where('bidang', $filterBidang);
            }
            $matriksIndikatorList = $indQuery->orderBy('bidang')->orderBy('urutan')->get()->map(function ($ind) use ($allActivities) {
                $acts = $allActivities->filter(function ($a) use ($ind) {
                    return (!empty($a->indikator_id) && $a->indikator_id == $ind->id) ||
                           ($a->bidang === $ind->bidang && stripos($a->judul_aktivitas, substr($ind->sasaran, 0, 16)) !== false);
                });
                $selesai = $acts->where('status', 'selesai')->count();
                $proses = $acts->where('status', 'proses')->count();
                $ind->realisasi_count = $selesai;
                $ind->realisasi_label = $selesai > 0 ? "{$selesai} {$ind->satuan}" : "0 {$ind->satuan}";
                $ind->capaian_persen = $ind->target_kuantitas > 0 ? min(100, round(($selesai / $ind->target_kuantitas) * 100)) : 100;
                $ind->status_kpi = $selesai >= $ind->target_kuantitas ? 'Tercapai' : ($selesai > 0 || $proses > 0 ? 'Sedang Berjalan' : 'Dalam Proses');
                return $ind;
            });
        }

        $canCreate = RolePermission::canAccess($sessionUser ?: $role, 'menu_aktivitas_tendik', 'create');

        return view('dashboard.tendik.target-capaian', compact(
            'periodeTipe',
            'range',
            'bulan',
            'tahun',
            'triwulan',
            'semester',
            'tahunAjaran',
            'tanggalPilihan',
            'today',
            'totalTendik',
            'totalSudahInputHariIni',
            'totalBelumInputHariIni',
            'persenKepatuhanHariIni',
            'kepatuhanHarianList',
            'userSudahInputHariIni',
            'userTodayCount',
            'userTodaySelesai',
            'hariEfektif',
            'targetTotalPeriode',
            'realisasiTotal',
            'realisasiSelesai',
            'realisasiProses',
            'realisasiTertunda',
            'persenCapaian',
            'chartTimeSeries',
            'chartBidang',
            'chartStatus',
            'capaianTendikList',
            'isKepalaTas',
            'isKepegawaian',
            'canViewMonitoringPegawai',
            'activeBidang',
            'userName',
            'canCreate',
            'filterBidang',
            'bidangLabels',
            'matriksIndikatorList'
        ));
    }

    /**
     * Simpan Target & Indikator Kinerja Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bidang'            => 'required|string|max:64',
            'sasaran'           => 'required|string|max:255',
            'indikator_kinerja' => 'required|string',
            'target_kuantitas'  => 'required|integer|min:1',
            'satuan'            => 'required|string|max:50',
        ]);

        $satuan = trim($validated['satuan']);
        $targetLabel = "{$validated['target_kuantitas']} {$satuan}";
        $maxUrutan = TendikIndikatorKinerja::where('bidang', $validated['bidang'])->max('urutan') ?: 0;

        TendikIndikatorKinerja::create([
            'bidang'            => $validated['bidang'],
            'sasaran'           => $validated['sasaran'],
            'indikator_kinerja' => $validated['indikator_kinerja'],
            'target_kuantitas'  => $validated['target_kuantitas'],
            'satuan'            => $satuan,
            'target_label'      => $targetLabel,
            'urutan'            => $maxUrutan + 1,
            'is_active'         => true,
        ]);

        return redirect()->route('dashboard.tendik.target.index', ['bidang' => $validated['bidang']])
            ->with('success', 'Sasaran & Target Indikator Kinerja berhasil ditambahkan.');
    }

    /**
     * Perbarui Target & Indikator Kinerja
     */
    public function update(Request $request, $id)
    {
        $ind = TendikIndikatorKinerja::findOrFail($id);

        $validated = $request->validate([
            'bidang'            => 'required|string|max:64',
            'sasaran'           => 'required|string|max:255',
            'indikator_kinerja' => 'required|string',
            'target_kuantitas'  => 'required|integer|min:1',
            'satuan'            => 'required|string|max:50',
        ]);

        $satuan = trim($validated['satuan']);
        $targetLabel = "{$validated['target_kuantitas']} {$satuan}";

        $ind->update([
            'bidang'            => $validated['bidang'],
            'sasaran'           => $validated['sasaran'],
            'indikator_kinerja' => $validated['indikator_kinerja'],
            'target_kuantitas'  => $validated['target_kuantitas'],
            'satuan'            => $satuan,
            'target_label'      => $targetLabel,
        ]);

        return redirect()->route('dashboard.tendik.target.index', ['bidang' => $validated['bidang']])
            ->with('success', 'Sasaran & Target Indikator Kinerja berhasil diperbarui.');
    }

    /**
     * Hapus Target & Indikator Kinerja
     */
    public function destroy(Request $request, $id)
    {
        $ind = TendikIndikatorKinerja::findOrFail($id);
        $bidang = $ind->bidang;
        $ind->delete();

        return redirect()->route('dashboard.tendik.target.index', ['bidang' => $bidang])
            ->with('success', 'Sasaran & Target Indikator Kinerja berhasil dihapus.');
    }

    /**
     * Bangun time series array untuk Chart.js (Target vs Realisasi Capaian)
     */
    protected function buildTimeSeriesData($periodeTipe, $range, $activities, $totalTendik, $isKepalaTas)
    {
        $labels = [];
        $targetData = [];
        $capaianData = [];

        $targetFactor = $isKepalaTas ? $totalTendik : 1;

        if ($periodeTipe === 'hari') {
            // Jam per jam (07:00 s.d. 17:00)
            for ($h = 7; $h <= 17; $h++) {
                $hourStr = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
                $labels[] = "{$hourStr}:00";
                $targetData[] = round($targetFactor * 0.1, 1);
                $capaian = $activities->filter(function ($a) use ($hourStr) {
                    return substr($a->jam_mulai, 0, 2) === $hourStr;
                })->count();
                $capaianData[] = $capaian;
            }
        } elseif ($periodeTipe === 'minggu') {
            // 7 Hari dalam seminggu
            $start = Carbon::parse($range['start']);
            for ($i = 0; $i < 7; $i++) {
                $cur = $start->copy()->addDays($i);
                $dateStr = $cur->toDateString();
                $labels[] = $cur->translatedFormat('D, d M');
                $targetData[] = $cur->isSunday() ? 0 : $targetFactor;
                $capaian = $activities->where('tanggal', $dateStr)->count();
                $capaianData[] = $capaian;
            }
        } elseif ($periodeTipe === 'triwulan' || $periodeTipe === 'semester' || $periodeTipe === 'tahun' || $periodeTipe === 'tahun_ajaran') {
            // Per Bulan
            $start = Carbon::parse($range['start'])->startOfMonth();
            $end = Carbon::parse($range['end'])->endOfMonth();
            $cur = $start->copy();

            while ($cur <= $end) {
                $ym = $cur->format('Y-m');
                $labels[] = $cur->translatedFormat('M Y');
                $daysInM = KalenderPendidikan::hitungHariEfektif($cur->startOfMonth()->toDateString(), $cur->endOfMonth()->toDateString(), 'gtk') ?: 22;
                $targetData[] = $daysInM * $targetFactor;

                $capaian = $activities->filter(function ($a) use ($ym) {
                    return Carbon::parse($a->tanggal)->format('Y-m') === $ym;
                })->count();
                $capaianData[] = $capaian;

                $cur->addMonth();
            }
        } else {
            // Default Mode Bulan: Dikelompokkan per minggu (Minggu 1, 2, 3, 4, 5)
            $start = Carbon::parse($range['start']);
            $end = Carbon::parse($range['end']);
            $cur = $start->copy();
            $w = 1;

            while ($cur <= $end) {
                $wEnd = $cur->copy()->endOfWeek();
                if ($wEnd > $end) $wEnd = $end->copy();

                $labels[] = "Mgg {$w} (" . $cur->format('d/m') . '-' . $wEnd->format('d/m') . ')';
                $workDays = $cur->diffInDaysFiltered(fn(Carbon $d) => !$d->isSunday(), $wEnd) + 1;
                $targetData[] = $workDays * $targetFactor;

                $capaian = $activities->filter(function ($a) use ($cur, $wEnd) {
                    $d = Carbon::parse($a->tanggal);
                    return $d >= $cur && $d <= $wEnd;
                })->count();
                $capaianData[] = $capaian;

                $cur = $wEnd->copy()->addDay();
                $w++;
            }
        }

        return [
            'labels'     => $labels,
            'target'     => $targetData,
            'realisasi'  => $capaianData,
        ];
    }
}
