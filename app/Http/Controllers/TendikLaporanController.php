<?php

namespace App\Http\Controllers;

use App\Models\TendikAktivitas;
use App\Models\TendikIndikatorKinerja;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            $u = DB::table('pengguna')->where('pengguna_id', $userId)->first();
            if ($u && !empty($u->ptk_id)) {
                $ptkId = $u->ptk_id;
                $profile['ptk_id'] = $ptkId;
                $gtk = DB::table('gtk')->where('ptk_id', $ptkId)->first();
            }
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

        $filterBidang = $request->get('bidang');
        $aktivitasQuery = TendikAktivitas::whereBetween('tanggal', [$range['start'], $range['end']]);

        if ($profile['role'] !== 'admin' && !str_contains(strtolower($profile['role']), 'admin')) {
            $aktivitasQuery->where(function ($q) use ($userId, $ptkId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
                if ($ptkId) {
                    $q->orWhere('ptk_id', $ptkId);
                }
            });
        }

        if ($filterBidang && $filterBidang !== 'all') {
            $aktivitasQuery->where('bidang', $filterBidang);
        }

        $aktivitasList = $aktivitasQuery->orderBy('tanggal', 'desc')->orderBy('jam_mulai', 'desc')->get();

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

        // Matriks Sasaran & Indikator Kinerja yang terelasi dengan aktivitas periode ini
        $bidangTarget = $filterBidang && $filterBidang !== 'all' ? $filterBidang : $this->mapProfileToBidang($profile['bidang'] ?? '');
        $indikatorKinerjaList = $this->resolveIndikatorKinerjaWithCapaian($bidangTarget, $aktivitasList);

        // Riwayat & Log Cetak Laporan Kinerja
        $logQuery = DB::table('tendik_laporan_log');
        if ($profile['role'] !== 'admin' && !str_contains(strtolower($profile['role']), 'admin')) {
            $logQuery->where(function ($q) use ($userId, $ptkId) {
                if ($userId) $q->where('user_id', $userId);
                if ($ptkId) $q->orWhere('ptk_id', $ptkId);
            });
        }
        $cetakLogs = $logQuery->orderByDesc('tanggal_cetak')->paginate(15);

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
            'sekolah',
            'indikatorKinerjaList',
            'cetakLogs'
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

        // Matriks Sasaran & Indikator Kinerja yang terelasi dengan aktivitas periode ini
        $bidangTarget = $request->get('bidang');
        if (!$bidangTarget || $bidangTarget === 'all') {
            $bidangTarget = $this->mapProfileToBidang($profile['bidang'] ?? '');
        }
        $indikatorKinerjaList = $this->resolveIndikatorKinerjaWithCapaian($bidangTarget, $aktivitasList);

        // QR Code verifikasi dokumen laporan kinerja
        $verifCode = 'LAP-' . strtoupper(substr(md5(($profile['ptk_id'] ?: $userId) . $range['start'] . $range['end'] . now()->timestamp), 0, 10));

        if (Schema::hasTable('tendik_laporan_log')) {
            try {
                DB::table('tendik_laporan_log')->insert([
                    'user_id'            => $userId,
                    'ptk_id'             => $ptkId,
                    'nama_pegawai'       => $profile['nama'],
                    'bidang'             => $bidangTarget ?: ($profile['bidang'] ?? 'kepegawaian'),
                    'periode_tipe'       => $periodeTipe,
                    'periode_label'      => $range['label'],
                    'tanggal_cetak'      => now(),
                    'total_aktivitas'    => $totalAktivitas,
                    'total_selesai'      => $totalSelesai,
                    'persentase_selesai' => $persentaseSelesai,
                    'orientasi'          => $orientasi,
                    'kode_verifikasi'    => $verifCode,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            } catch (\Throwable $e) {
                // Jangan gagalkan cetak jika log duplikat atau gagal
            }
        }

        $qrVerifyUrl = route('dashboard.tendik.laporan.index', [
            'pegawai' => $profile['ptk_id'] ?: $profile['user_id'],
            'periode' => $periodeTipe,
            'kode'    => $verifCode,
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
            'qrCodeBase64',
            'indikatorKinerjaList'
        ));
    }

    /**
     * Petakan bidang dari profil ke kode bidang indikator kinerja
     */
    protected function mapProfileToBidang(string $str): string
    {
        $low = strtolower($str);
        if (str_contains($low, 'pegawai') || str_contains($low, 'kepegawaian')) return 'kepegawaian';
        if (str_contains($low, 'surat') || str_contains($low, 'arsip')) return 'persuratan';
        if (str_contains($low, 'siswa')) return 'kesiswaan';
        if (str_contains($low, 'sarpras') || str_contains($low, 'aset')) return 'sarpras';
        if (str_contains($low, 'lab')) return 'laboran';
        if (str_contains($low, 'pustaka')) return 'perpustakaan';
        if (str_contains($low, 'it') || str_contains($low, 'teknisi')) return 'teknisi';
        if (str_contains($low, 'aman') || str_contains($low, 'satpam')) return 'keamanan';
        if (str_contains($low, 'jaga') || str_contains($low, 'bersih')) return 'penjaga';
        if (str_contains($low, 'tas') || str_contains($low, 'ktu') || str_contains($low, 'kepala')) return 'kepala_tas';
        return 'kepegawaian'; // fallback ke kepegawaian default TU
    }

    /**
     * Hitung capaian realisasi indikator kinerja dari daftar aktivitas
     */
    protected function resolveIndikatorKinerjaWithCapaian(?string $bidang, $aktivitasList)
    {
        if (!Schema::hasTable('tendik_indikator_kinerja')) {
            return collect();
        }

        $query = TendikIndikatorKinerja::where('is_active', true);
        if ($bidang && $bidang !== 'all') {
            $query->where('bidang', $bidang);
        }

        return $query->orderBy('bidang')->orderBy('urutan')->get()->map(function ($ind) use ($aktivitasList) {
            $acts = $aktivitasList->filter(function ($a) use ($ind) {
                return (!empty($a->indikator_id) && $a->indikator_id == $ind->id) ||
                       ($a->bidang === $ind->bidang && stripos($a->judul_aktivitas, substr($ind->sasaran, 0, 16)) !== false);
            });
            $selesai = $acts->where('status', 'selesai')->count();
            $proses = $acts->where('status', 'proses')->count();
            $ind->realisasi_count = $selesai;
            $ind->realisasi_label = $selesai > 0 ? "{$selesai} {$ind->satuan}" : "0 {$ind->satuan}";
            $ind->capaian_persen = $ind->target_kuantitas > 0 ? min(100, round(($selesai / $ind->target_kuantitas) * 100)) : 100;
            $ind->status_label = $selesai >= $ind->target_kuantitas ? 'Tercapai' : ($selesai > 0 || $proses > 0 ? 'Sedang Berjalan' : 'Dalam Proses');
            return $ind;
        });
    }
}
