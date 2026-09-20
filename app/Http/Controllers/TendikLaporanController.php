<?php

namespace App\Http\Controllers;

use App\Models\TendikAktivitas;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TendikLaporanController extends Controller
{
    /**
     * Hitung rentang tanggal awal & akhir berdasarkan parameter periode
     * Mendukung: 'bulan', 'triwulan', 'semester', 'tahun'
     */
    protected function resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran)
    {
        $startDate = null;
        $endDate = null;
        $labelPeriode = '';

        if ($periodeTipe === 'triwulan') {
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
                $labelPeriode = "Semester Ganjil TA {$y1}/{$y2} (Juli - Desember {$y1})";
            } else {
                $startDate = "{$y2}-01-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Semester Genap TA {$y1}/{$y2} (Januari - Juni {$y2})";
            }
        } elseif ($periodeTipe === 'tahun') {
            if (str_contains($tahunAjaran, '/')) {
                $parts = explode('/', $tahunAjaran);
                $y1 = (int) $parts[0];
                $y2 = (int) ($parts[1] ?? ($y1 + 1));
                $startDate = "{$y1}-07-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Tahun Ajaran {$y1}/{$y2} (1 Juli {$y1} - 30 Juni {$y2})";
            } else {
                $y = (int) ($tahun ?: date('Y'));
                $startDate = "{$y}-01-01";
                $endDate = "{$y}-12-31";
                $labelPeriode = "Tahun Kalender {$y}";
            }
        } else {
            // Mode Bulan (Default)
            $b = str_pad((string) ($bulan ?: date('m')), 2, '0', STR_PAD_LEFT);
            $y = (int) ($tahun ?: date('Y'));
            $startCarbon = Carbon::createFromDate($y, (int) $b, 1)->startOfMonth();
            $endCarbon = Carbon::createFromDate($y, (int) $b, 1)->endOfMonth();

            $startDate = $startCarbon->toDateString();
            $endDate = $endCarbon->toDateString();
            $labelPeriode = "Bulan " . $startCarbon->translatedFormat('F Y');
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'label' => $labelPeriode,
        ];
    }

    /**
     * Resolusi Data Pegawai / Tendik dari Sesi
     */
    protected function resolveTendikProfile()
    {
        $sessionUser = session('user');
        $userId = is_array($sessionUser)
            ? ($sessionUser['id'] ?? ($sessionUser['pengguna_id'] ?? null))
            : ($sessionUser->id ?? ($sessionUser->pengguna_id ?? null));

        $ptkId = is_array($sessionUser) ? ($sessionUser['ptk_id'] ?? null) : ($sessionUser->ptk_id ?? null);
        $role = is_array($sessionUser) ? ($sessionUser['role'] ?? 'tendik') : ($sessionUser->role ?? 'tendik');

        $profile = [
            'nama' => is_array($sessionUser) ? ($sessionUser['nama'] ?? ($sessionUser['name'] ?? 'Pegawai')) : ($sessionUser->nama ?? ($sessionUser->name ?? 'Pegawai')),
            'nip' => '-',
            'nuptk' => '-',
            'nik' => '-',
            'jenis_ptk' => 'Tenaga Kependidikan',
            'tugas_tambahan' => 'Staf Tata Usaha',
            'bidang' => 'Tata Usaha',
            'user_id' => $userId,
            'ptk_id' => $ptkId,
            'role' => $role,
        ];

        // Cari data detail di tabel gtk
        $gtk = null;
        if ($ptkId) {
            $gtk = DB::table('gtk')->where('ptk_id', $ptkId)->first();
        } elseif ($userId) {
            $gtk = DB::table('gtk')->where('pengguna_id', $userId)->first();
        }

        if ($gtk) {
            $profile['nama'] = $gtk->nama ?? $profile['nama'];
            $profile['nip'] = $gtk->nip ?? '-';
            $profile['nuptk'] = $gtk->nuptk ?? '-';
            $profile['nik'] = $gtk->nik ?? '-';
            $profile['jenis_ptk'] = $gtk->jenis_ptk_id_str ?? 'Tenaga Kependidikan';
            $profile['ptk_id'] = $gtk->ptk_id;
        }

        // Ambil penugasan tugas tambahan aktif
        $duties = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('ptt.is_active', true)
            ->where('rtt.is_active', true)
            ->where(function ($q) use ($userId, $ptkId) {
                if ($userId) {
                    $q->where('ptt.user_id', $userId);
                }
                if ($ptkId) {
                    $q->orWhere('ptt.ptk_id', $ptkId);
                }
            })
            ->select('rtt.nama', 'rtt.bidang', 'rtt.kode')
            ->get();

        if ($duties->isNotEmpty()) {
            $firstDuty = $duties->first();
            $profile['tugas_tambahan'] = $firstDuty->nama;
            $profile['bidang'] = $firstDuty->bidang ?: 'Tata Usaha';
            $profile['duties'] = $duties;
        }

        $profile['jabatan'] = $profile['tugas_tambahan'] ?: ($profile['jenis_ptk'] ?: 'Tenaga Kependidikan');
        $profile['status_kepegawaian'] = $gtk->status_kepegawaian_id_str ?? 'Pegawai Tetap';

        return $profile;
    }

    /**
     * Halaman Rekap Laporan Kinerja & Aktivitas Tendik
     */
    public function index(Request $request)
    {
        $profile = $this->resolveTendikProfile();

        $periodeTipe = $request->get('periode', 'bulan'); // bulan, triwulan, semester, tahun
        $bulan = (int) $request->get('bulan', date('n'));
        $tahun = (int) $request->get('tahun', date('Y'));
        $triwulan = (int) $request->get('triwulan', ceil(date('n') / 3));
        $semester = (string) $request->get('semester', (date('n') >= 7 ? '1' : '2'));

        $curYear = (int) date('Y');
        $defaultTA = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";
        $tahunAjaran = (string) $request->get('tahun_ajaran', $defaultTA);

        $range = $this->resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran);

        // Ambil data aktivitas pada rentang periode
        $userId = $profile['user_id'];
        $ptkId = $profile['ptk_id'];

        $aktivitasList = TendikAktivitas::where(function ($q) use ($userId, $ptkId) {
            if ($userId) {
                $q->where('user_id', $userId);
            }
            if ($ptkId) {
                $q->orWhere('ptk_id', $ptkId);
            }
        })
            ->whereBetween('tanggal', [$range['start'], $range['end']])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_mulai', 'desc')
            ->get();

        // Hitung metrik capaian kinerja
        $totalAktivitas = $aktivitasList->count();
        $totalSelesai = $aktivitasList->where('status', 'selesai')->count();
        $totalProses = $aktivitasList->where('status', 'proses')->count();
        $totalTertunda = $aktivitasList->where('status', 'tertunda')->count();
        $totalDurasiMenit = (int) $aktivitasList->sum(fn($a) => $a->durasi_menit);
        $totalJam = floor($totalDurasiMenit / 60);
        $sisaMenit = $totalDurasiMenit % 60;

        $hariAktifBekerja = $aktivitasList->pluck('tanggal')->unique()->count();
        $persentaseSelesai = $totalAktivitas > 0 ? round(($totalSelesai / $totalAktivitas) * 100, 1) : 0;

        // Distribusi aktivitas per bidang
        $distribusiBidang = $aktivitasList->groupBy('bidang')->map->count();

        // Ringkasan bulanan jika periode > 1 bulan (triwulan, semester, tahun)
        $rekapBulanan = [];
        if ($periodeTipe !== 'bulan') {
            $groupedByMonth = $aktivitasList->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m');
            });

            foreach ($groupedByMonth as $ym => $items) {
                $mCarbon = Carbon::createFromFormat('Y-m', $ym);
                $mDurasi = (int) $items->sum(fn($a) => $a->durasi_menit);
                $rekapBulanan[] = [
                    'bulan_nama' => $mCarbon->translatedFormat('F Y'),
                    'total' => $items->count(),
                    'selesai' => $items->where('status', 'selesai')->count(),
                    'proses' => $items->where('status', 'proses')->count(),
                    'durasi_jam' => floor($mDurasi / 60),
                    'durasi_menit' => $mDurasi % 60,
                    'hari_aktif' => $items->pluck('tanggal')->unique()->count(),
                ];
            }
        }

        $sekolah = DB::table('sekolah')->first();

        return view('dashboard.tendik.rekap-laporan', compact(
            'profile',
            'periodeTipe',
            'bulan',
            'tahun',
            'triwulan',
            'semester',
            'tahunAjaran',
            'range',
            'aktivitasList',
            'totalAktivitas',
            'totalSelesai',
            'totalProses',
            'totalTertunda',
            'totalDurasiMenit',
            'totalJam',
            'sisaMenit',
            'hariAktifBekerja',
            'persentaseSelesai',
            'distribusiBidang',
            'rekapBulanan',
            'sekolah'
        ));
    }

    /**
     * Cetak Lembar Resmi Laporan Kinerja & Aktivitas Pekerjaan Tendik
     */
    public function cetak(Request $request)
    {
        $profile = $this->resolveTendikProfile();

        $periodeTipe = $request->get('periode', 'bulan');
        $bulan = (int) $request->get('bulan', date('n'));
        $tahun = (int) $request->get('tahun', date('Y'));
        $triwulan = (int) $request->get('triwulan', ceil(date('n') / 3));
        $semester = (string) $request->get('semester', (date('n') >= 7 ? '1' : '2'));

        $curYear = (int) date('Y');
        $defaultTA = (date('n') >= 7) ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";
        $tahunAjaran = (string) $request->get('tahun_ajaran', $defaultTA);

        $range = $this->resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran);

        $userId = $profile['user_id'];
        $ptkId = $profile['ptk_id'];

        $aktivitasList = TendikAktivitas::where(function ($q) use ($userId, $ptkId) {
            if ($userId) {
                $q->where('user_id', $userId);
            }
            if ($ptkId) {
                $q->orWhere('ptk_id', $ptkId);
            }
        })
            ->whereBetween('tanggal', [$range['start'], $range['end']])
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->get();

        $totalAktivitas = $aktivitasList->count();
        $totalSelesai = $aktivitasList->where('status', 'selesai')->count();
        $totalProses = $aktivitasList->where('status', 'proses')->count();
        $totalTertunda = $aktivitasList->where('status', 'tertunda')->count();
        $totalDurasiMenit = (int) $aktivitasList->sum(fn($a) => $a->durasi_menit);
        $totalJam = floor($totalDurasiMenit / 60);
        $sisaMenit = $totalDurasiMenit % 60;
        $hariAktifBekerja = $aktivitasList->pluck('tanggal')->unique()->count();
        $persentaseSelesai = $totalAktivitas > 0 ? round(($totalSelesai / $totalAktivitas) * 100, 1) : 0;

        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();
        $orientasi = in_array(strtolower($request->get('orientasi', 'portrait')), ['portrait', 'landscape']) 
            ? strtolower($request->get('orientasi', 'portrait')) 
            : 'portrait';

        // Rekap akumulasi bulanan jika periode > 1 bulan
        $rekapBulanan = [];
        if ($periodeTipe !== 'bulan') {
            $groupedByMonth = $aktivitasList->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m');
            });

            foreach ($groupedByMonth as $ym => $items) {
                $mCarbon = Carbon::createFromFormat('Y-m', $ym);
                $mDurasi = (int) $items->sum(fn($a) => $a->durasi_menit);
                $rekapBulanan[] = [
                    'bulan_nama' => $mCarbon->translatedFormat('F Y'),
                    'total' => $items->count(),
                    'selesai' => $items->where('status', 'selesai')->count(),
                    'proses' => $items->where('status', 'proses')->count(),
                    'durasi_jam' => floor($mDurasi / 60),
                    'durasi_menit' => $mDurasi % 60,
                    'hari_aktif' => $items->pluck('tanggal')->unique()->count(),
                ];
            }
        }

        // Cari Kepala Sekolah & Kepala Urusan Tata Usaha
        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $kepalaTas = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'KEPALA_TAS')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip', 'gtk.nuptk')
            ->first();

        // QR Code verifikasi dokumen laporan kinerja
        $qrVerifyUrl = route('dashboard.tendik.laporan.index', [
            'pegawai' => $profile['ptk_id'] ?: $profile['user_id'],
            'periode' => $periodeTipe,
            'tgl_cetak' => date('YmdHis'),
        ]);

        $qrCodeBase64 = null;
        try {
            $qrCodeBase64 = QrCodeService::generateBase64Png($qrVerifyUrl, 120);
        } catch (\Throwable $e) {
            $qrCodeBase64 = null;
        }

        return view('dashboard.tendik.cetak-laporan', compact(
            'profile',
            'periodeTipe',
            'bulan',
            'tahun',
            'triwulan',
            'semester',
            'tahunAjaran',
            'range',
            'aktivitasList',
            'totalAktivitas',
            'totalSelesai',
            'totalProses',
            'totalTertunda',
            'totalDurasiMenit',
            'totalJam',
            'sisaMenit',
            'hariAktifBekerja',
            'persentaseSelesai',
            'sekolah',
            'sekolahMeta',
            'orientasi',
            'rekapBulanan',
            'kepsek',
            'kepalaTas',
            'qrCodeBase64'
        ));
    }
}
