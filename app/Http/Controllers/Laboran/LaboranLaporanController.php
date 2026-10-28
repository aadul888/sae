<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Laporan Lengkap Penggunaan & Kerusakan Alat.
 *
 * Mencakup: pemakaian lokasi praktik, peminjaman alat, perawatan,
 * pemeriksaan, kerusakan, kebutuhan, dan pengadaan — per periode.
 */
class LaboranLaporanController extends LaboranBaseController
{
    public const PERIODE = [
        'bulan'    => 'Bulanan',
        'triwulan' => 'Triwulan',
        'semester' => 'Semester',
        'tahun'    => 'Tahun Ajaran',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->buildData($request, $context);

        return view('dashboard.laboran.laporan-lengkap', array_merge($data, $this->abilities(), [
            'context'     => $context,
            'scopeBadge'  => LaboranScope::badge($context),
            'periodeList' => self::PERIODE,
        ]));
    }

    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->buildData($request, $context);

        $orientasi = $this->orientasiCetak($request, 'landscape');
        $mode      = $this->modeCetak($request, 'lengkap');

        return view('dashboard.laboran.laporan-lengkap-cetak', array_merge($data, [
            'orientasi' => $orientasi,
            'mode'      => $mode,
        ], $this->dokumenCetak('laporan-lengkap', $context, [
            'mode'           => $mode,
            'orientasi'      => $orientasi,
            'periode_label'  => $data['range']['label'] ?? null,
            'periode_dari'   => $data['range']['start'] ?? null,
            'periode_sampai' => $data['range']['end'] ?? null,
            'jumlah_baris'   => (int) ($data['totalSesi'] ?? 0),
            'filter'         => [
                'periode' => $data['periodeTipe'] ?? null,
                'bulan'   => $data['bulan'] ?? null,
                'tahun'   => $data['tahun'] ?? null,
                'lab'     => $data['lokasiFilter'] ?? null,
            ],
        ])));
    }

    /**
     * Susun seluruh data laporan untuk periode terpilih.
     */
    protected function buildData(Request $request, array $context): array
    {
        $periodeTipe = (string) $request->query('periode', 'bulan');
        if (!array_key_exists($periodeTipe, self::PERIODE)) {
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

        // ---------------------------------------------------------------
        // 1. Penggunaan lokasi praktik
        // ---------------------------------------------------------------
        $qJadwal = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk', 'ljp.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->whereBetween('ljp.tanggal', [$range['start'], $range['end']])
            ->select('ljp.*', 'gtk.nama as guru_nama', 'rb.nama as rombel_nama');
        LaboranScope::apply($qJadwal, 'ljp.jurusan_id', $context);
        if ($lokasiFilter !== '') $qJadwal->where('ljp.ruang_lab_nama', $lokasiFilter);
        if ($jenisFilter !== '')  $qJadwal->where('ljp.jenis_lokasi', $jenisFilter);
        $jadwal = $qJadwal->orderBy('ljp.tanggal')->get();

        $totalSesi      = $jadwal->count();
        $totalSesiSelesai = $jadwal->where('status', 'selesai')->count();
        $totalMenitPraktik = 0;
        foreach ($jadwal as $j) {
            $totalMenitPraktik += $this->durasiMenit($j->jam_mulai, $j->jam_selesai);
        }
        $hariAktif = $jadwal->pluck('tanggal')->unique()->count();
        $lokasiUnik = $jadwal->pluck('ruang_lab_nama')->filter()->unique()->count();
        $guruUnik   = $jadwal->pluck('ptk_id')->filter()->unique()->count();

        $rekapLokasi = $jadwal->groupBy('ruang_lab_nama')->map(function ($group, $nama) {
            $menit = 0;
            foreach ($group as $g) {
                $menit += $this->durasiMenit($g->jam_mulai, $g->jam_selesai);
            }
            return (object) [
                'nama'    => $nama ?: '—',
                'sesi'    => $group->count(),
                'selesai' => $group->where('status', 'selesai')->count(),
                'jam'     => intdiv($menit, 60),
                'menit'   => $menit % 60,
            ];
        })->sortByDesc('sesi')->values();

        // ---------------------------------------------------------------
        // 2. Peminjaman alat
        // ---------------------------------------------------------------
        $qPinjam = DB::table('laboran_peminjaman')
            ->whereBetween('tanggal_pinjam', [$range['start'], $range['end']]);
        LaboranScope::apply($qPinjam, 'jurusan_id', $context);
        $peminjaman = $qPinjam->orderBy('tanggal_pinjam')->get();

        $totalPeminjaman   = $peminjaman->count();
        $totalDipinjam     = $peminjaman->whereIn('status', ['dipinjam', 'terlambat'])->count();
        $totalDikembalikan = $peminjaman->where('status', 'dikembalikan')->count();
        $totalTerlambat    = $peminjaman->where('status', 'terlambat')->count();

        $rekapPeminjam = $peminjaman->groupBy('peminjam_tipe')->map(fn($g, $k) => (object) [
            'label' => self::labelPeminjam($k),
            'total' => $g->count(),
        ])->values();

        // ---------------------------------------------------------------
        // 3. Kerusakan alat
        // ---------------------------------------------------------------
        $qRusak = DB::table('laboran_berita_acara_kerusakan')
            ->whereBetween('tanggal_kejadian', [$range['start'], $range['end']]);
        LaboranScope::apply($qRusak, 'jurusan_id', $context);
        $kerusakan = $qRusak->orderBy('tanggal_kejadian')->get();

        $totalKerusakan   = $kerusakan->count();
        $totalRusakBerat  = $kerusakan->whereIn('tingkat_kerusakan', ['berat', 'total'])->count();
        $totalEstimasi    = (float) $kerusakan->sum('estimasi_biaya');

        $rekapTingkat = $kerusakan->groupBy('tingkat_kerusakan')->map(fn($g, $k) => (object) [
            'label' => LaboranKerusakanController::TINGKAT[$k] ?? $k,
            'total' => $g->count(),
        ])->values();

        $rekapAlatRusak = $kerusakan->groupBy('nama_alat')->map(fn($g, $nama) => (object) [
            'nama'     => $nama,
            'kejadian' => $g->count(),
            'jumlah'   => $g->sum('jumlah_rusak'),
            'biaya'    => $g->sum('estimasi_biaya'),
        ])->sortByDesc('kejadian')->values()->take(15);

        // ---------------------------------------------------------------
        // 4. Perawatan alat
        // ---------------------------------------------------------------
        $qRawat = DB::table('laboran_perawatan')
            ->whereBetween('tanggal_perawatan', [$range['start'], $range['end']]);
        LaboranScope::apply($qRawat, 'jurusan_id', $context);
        $perawatan = $qRawat->orderBy('tanggal_perawatan')->get();

        $totalPerawatan   = $perawatan->count();
        $perawatanSelesai = $perawatan->where('status', 'selesai')->count();
        $totalBiayaRawat  = (float) $perawatan->sum('biaya');

        $rekapJenisRawat = $perawatan->groupBy('jenis_perawatan')->map(fn($g, $k) => (object) [
            'label' => LaboranPerawatanController::JENIS_PERAWATAN[$k] ?? $k,
            'total' => $g->count(),
            'selesai' => $g->where('status', 'selesai')->count(),
        ])->values();

        // ---------------------------------------------------------------
        // 5. Pemeriksaan (ceklist)
        // ---------------------------------------------------------------
        $qPeriksa = DB::table('laboran_pemeriksaan')
            ->whereBetween('tanggal_pemeriksaan', [$range['start'], $range['end']]);
        LaboranScope::apply($qPeriksa, 'jurusan_id', $context);
        $pemeriksaan = $qPeriksa->orderBy('tanggal_pemeriksaan')->get();

        $totalPemeriksaan = $pemeriksaan->count();
        $totalItemDiperiksa = (int) $pemeriksaan->sum('total_item');
        $totalItemTidakSesuai = (int) $pemeriksaan->sum('total_tidak_sesuai');
        $totalItemTidakAda = (int) $pemeriksaan->sum('total_tidak_ada');

        // ---------------------------------------------------------------
        // 6. Kebutuhan & Pengadaan
        // ---------------------------------------------------------------
        $qKebutuhan = DB::table('laboran_kebutuhan')
            ->whereBetween('tanggal_input', [$range['start'], $range['end']]);
        LaboranScope::apply($qKebutuhan, 'jurusan_id', $context);
        $kebutuhan = $qKebutuhan->get();

        $totalKebutuhan = $kebutuhan->count();
        $totalKebutuhanKritis = $kebutuhan->where('prioritas', 'kritis')->count();

        $qPengadaan = DB::table('laboran_usulan_pengadaan')
            ->whereBetween('tanggal_usulan', [$range['start'], $range['end']]);
        LaboranScope::apply($qPengadaan, 'jurusan_id', $context);
        $pengadaan = $qPengadaan->get();

        $totalPengadaan   = $pengadaan->count();
        $totalPengadaanDisetujui = $pengadaan->whereIn('status', ['disetujui', 'direalisasi'])->count();
        $totalNilaiPengadaan = (float) $pengadaan->sum('total_estimasi_biaya');

        // ---------------------------------------------------------------
        // 7. Kondisi inventaris saat ini
        // ---------------------------------------------------------------
        $qInventaris = DB::table('laboran_bahan_alat');
        LaboranScope::apply($qInventaris, 'jurusan_id', $context);
        $inventaris = $qInventaris->get();

        $rekapKondisi = $inventaris->groupBy('kondisi')->map(fn($g, $k) => (object) [
            'label' => match ($k) {
                'baik' => 'Baik',
                'rusak_ringan' => 'Rusak Ringan',
                'rusak_berat' => 'Rusak Berat',
                'kedaluwarsa' => 'Kedaluwarsa',
                default => ucfirst((string) $k),
            },
            'total' => $g->count(),
            'stok'  => $g->sum('stok_tersedia'),
        ])->values();

        // ---------------------------------------------------------------
        // 8. Rekap bulanan (bila periode > 1 bulan)
        // ---------------------------------------------------------------
        $rekapBulanan = [];
        if ($periodeTipe !== 'bulan') {
            $byMonth = [];
            foreach ($jadwal as $j) {
                $ym = Carbon::parse($j->tanggal)->format('Y-m');
                $byMonth[$ym]['praktik'] = ($byMonth[$ym]['praktik'] ?? 0) + 1;
            }
            foreach ($peminjaman as $p) {
                $ym = Carbon::parse($p->tanggal_pinjam)->format('Y-m');
                $byMonth[$ym]['pinjam'] = ($byMonth[$ym]['pinjam'] ?? 0) + 1;
            }
            foreach ($kerusakan as $k) {
                $ym = Carbon::parse($k->tanggal_kejadian)->format('Y-m');
                $byMonth[$ym]['rusak'] = ($byMonth[$ym]['rusak'] ?? 0) + 1;
            }
            foreach ($perawatan as $pr) {
                $ym = Carbon::parse($pr->tanggal_perawatan)->format('Y-m');
                $byMonth[$ym]['rawat'] = ($byMonth[$ym]['rawat'] ?? 0) + 1;
            }

            ksort($byMonth);
            foreach ($byMonth as $ym => $vals) {
                $rekapBulanan[] = [
                    'label'   => Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y'),
                    'praktik' => $vals['praktik'] ?? 0,
                    'pinjam'  => $vals['pinjam'] ?? 0,
                    'rusak'   => $vals['rusak'] ?? 0,
                    'rawat'   => $vals['rawat'] ?? 0,
                ];
            }
        }

        // ---------------------------------------------------------------
        // Identitas sekolah & pejabat
        // ---------------------------------------------------------------
        $sekolah     = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        // Pejabat penanda tangan terpusat (Kepala Sekolah, Kepala Lab, KAPROG).
        $pejabat       = $this->dokumenService()->pejabat($context);
        $kepalaSekolah = $pejabat['kepalaSekolah'];
        $kepalaLab     = $pejabat['kepalaLab'];
        $kaprog        = $pejabat['kaprog'];
        $petugas       = $pejabat['petugas'];

        $daftarLokasi = DB::table('laboran_jadwal_penggunaan')
            ->select('ruang_lab_nama')
            ->distinct()
            ->orderBy('ruang_lab_nama')
            ->pluck('ruang_lab_nama');

        return compact(
            'periodeTipe', 'bulan', 'tahun', 'triwulan', 'semester', 'tahunAjaran',
            'range', 'jenisFilter', 'lokasiFilter', 'daftarLokasi',
            'jadwal', 'totalSesi', 'totalSesiSelesai', 'totalMenitPraktik', 'hariAktif', 'lokasiUnik', 'guruUnik', 'rekapLokasi',
            'peminjaman', 'totalPeminjaman', 'totalDipinjam', 'totalDikembalikan', 'totalTerlambat', 'rekapPeminjam',
            'kerusakan', 'totalKerusakan', 'totalRusakBerat', 'totalEstimasi', 'rekapTingkat', 'rekapAlatRusak',
            'perawatan', 'totalPerawatan', 'perawatanSelesai', 'totalBiayaRawat', 'rekapJenisRawat',
            'pemeriksaan', 'totalPemeriksaan', 'totalItemDiperiksa', 'totalItemTidakSesuai', 'totalItemTidakAda',
            'kebutuhan', 'totalKebutuhan', 'totalKebutuhanKritis',
            'pengadaan', 'totalPengadaan', 'totalPengadaanDisetujui', 'totalNilaiPengadaan',
            'inventaris', 'rekapKondisi', 'rekapBulanan',
            'sekolah', 'sekolahMeta', 'kepalaSekolah', 'kepalaLab', 'kaprog', 'petugas'
        );
    }

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

    protected static function labelPeminjam(string $tipe): string
    {
        return LaboranPeminjamanController::PEMINJAM_TIPE[$tipe] ?? ucfirst($tipe);
    }
}
