<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Penyusunan data LAPORAN & DOKUMEN CETAK modul Laboran.
 *
 * Diekstrak dari `LaboranJadwalService` agar berkas tersebut tetap di bawah
 * batas 1.000 baris. Semua method di sini bersifat "baca saja" (tidak menulis
 * data), sehingga aman dipisahkan dari inti penjadwalan.
 *
 * `LaboranJadwalService` mempertahankan method publik bernama sama sebagai
 * delegasi tipis agar pemanggil lama (controller) tidak perlu diubah.
 */
class LaboranJadwalLaporanService
{
    /** Service inti penjadwalan — dipakai untuk `resolveDateRange()`. */
    protected LaboranJadwalService $jadwal;

    /** Service dokumen cetak (pejabat & pencatatan cetak). */
    protected LaboranDokumenService $dokumen;

    public function __construct(LaboranJadwalService $jadwal, ?LaboranDokumenService $dokumen = null)
    {
        $this->jadwal  = $jadwal;
        $this->dokumen = $dokumen ?: app(LaboranDokumenService::class);
    }

    /**
     * Susun seluruh data laporan penggunaan lokasi.
     *
     * @param  bool  $catat  Catat dokumen ke riwayat cetak & arsip digital.
     *                       Hanya aktif saat halaman CETAK dirender, bukan
     *                       saat halaman pratinjau (`laporan.index`).
     */
    public function buildLaporanData(Request $request, bool $catat = false): array
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

        $range = $this->jadwal->resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran);

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

        if ($jenisFilter !== '' && array_key_exists($jenisFilter, LaboranJadwalService::JENIS_LOKASI)) {
            $query->where('ljp.jenis_lokasi', $jenisFilter);
        }

        if ($lokasiFilter !== '') {
            $query->where('ljp.ruang_lab_nama', $lokasiFilter);
        }

        if ($guruFilter !== '') {
            $query->where(function ($q) use ($guruFilter) {
                $q->where('ljp.ptk_id', $guruFilter)
                  ->orWhere('ljp.ptk_ids', 'like', '%"' . $guruFilter . '"%');
            });
        }

        $items = $query->orderBy('ljp.tanggal', 'asc')
            ->orderBy('ljp.jam_mulai', 'asc')
            ->get();

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

        $rekapJenis = [];
        foreach (LaboranJadwalService::JENIS_LOKASI as $key => $label) {
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

        $rekapLokasi = $items->groupBy('ruang_lab_nama')->map(function ($group, $nama) {
            $menit = 0;
            foreach ($group as $g) {
                $menit += $this->durasiMenit($g->jam_mulai, $g->jam_selesai);
            }
            $jenis = $group->first()->jenis_lokasi ?: 'lainnya';

            return (object) [
                'nama'    => $nama,
                'jenis'   => $jenis,
                'label'   => LaboranJadwalService::JENIS_LOKASI[$jenis] ?? 'Lokasi Lainnya',
                'sesi'    => $group->count(),
                'selesai' => $group->where('status', 'selesai')->count(),
                'jam'     => intdiv($menit, 60),
                'menit'   => $menit % 60,
            ];
        })->sortByDesc('sesi')->values();

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

        $daftarLokasi = DB::table('laboran_jadwal_penggunaan')
            ->select('ruang_lab_nama', 'jenis_lokasi')
            ->distinct()
            ->orderBy('ruang_lab_nama')
            ->get();

        $daftarGuru = DB::table('gtk')
            ->whereIn('ptk_id', DB::table('laboran_jadwal_penggunaan')->select('ptk_id')->distinct())
            ->orderBy('nama')
            ->get(['ptk_id', 'nama', 'nip']);

        $sekolah     = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        // Pejabat penanda tangan terpusat (Kepala Sekolah, Kepala Lab, KAPROG).
        $pejabat       = $this->dokumen->pejabat($context);
        $kepalaSekolah = $pejabat['kepalaSekolah'];
        $kepalaLab     = $pejabat['kepalaLab'];
        $kaprog        = $pejabat['kaprog'];

        $petugas = $pejabat['petugas'];

        $orientasi = strtolower((string) $request->query('orientasi', 'landscape'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'landscape';
        }

        $mode = strtolower((string) $request->query('mode', 'lengkap'));
        if (!in_array($mode, LaboranDokumenService::MODE, true)) {
            $mode = 'lengkap';
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
            'jenisLokasiLabels' => LaboranJadwalService::JENIS_LOKASI,
            'statusLabels'      => LaboranJadwalService::STATUS_LIST,
            'sekolah'           => $sekolah,
            'sekolahMeta'       => $sekolahMeta,
            'kepalaSekolah'     => $kepalaSekolah,
            'kepalaLab'         => $kepalaLab,
            'kaprog'            => $kaprog,
            'petugas'           => $petugas,
            'orientasi'         => $orientasi,
            'mode'              => $mode,
            'logCetak'          => $catat ? $this->dokumen->catatCetak('laporan-penggunaan', $context, [
                'mode'           => $mode,
                'orientasi'      => $orientasi,
                'periode_label'  => $range['label'] ?? null,
                'periode_dari'   => $range['start'] ?? null,
                'periode_sampai' => $range['end'] ?? null,
                'jumlah_baris'   => $totalSesi,
                'filter'         => [
                    'periode' => $periodeTipe,
                    'jenis'   => $jenisFilter,
                    'lab'     => $lokasiFilter,
                    'ptk_id'  => $guruFilter,
                ],
            ]) : null,
        ];
    }

    /**
     * Susun jadwal praktik PER GURU untuk satu minggu atau satu bulan.
     *
     * Hasil dikelompokkan per hari (Senin..Sabtu) agar siap dicetak sebagai
     * tabel jadwal mingguan, atau per tanggal untuk tampilan bulanan.
     *
     * @param string $mode 'mingguan' | 'bulanan'
     * @return array{
     *   mode:string, label:string, start:string, end:string,
     *   guru:?object, items:\Illuminate\Support\Collection,
     *   perHari:array<string,\Illuminate\Support\Collection>,
     *   perTanggal:array<string,\Illuminate\Support\Collection>,
     *   totalSesi:int, totalMenit:int, hariAktif:int, lokasiUnik:int,
     *   daftarGuru:\Illuminate\Support\Collection, sekolah:?object, petugas:array
     * }
     */
    public function buildJadwalPerGuru(Request $request): array
    {
        $context = LaboranScope::resolve();

        $mode  = strtolower((string) $request->query('mode', 'mingguan'));
        if (!in_array($mode, ['mingguan', 'bulanan', 'semester'], true)) {
            $mode = 'mingguan';
        }

        $ptkId = trim((string) $request->query('ptk_id', ''));
        $bulan = max(1, min(12, (int) $request->query('bulan', date('n'))));
        $tahun = (int) $request->query('tahun', date('Y'));
        $semester = (string) $request->query('semester', date('n') >= 7 ? '1' : '2');

        // Rentang: minggu berjalan (Senin–Sabtu), satu bulan penuh, atau satu semester.
        if ($mode === 'bulanan') {
            $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $label = 'Bulan ' . $start->translatedFormat('F Y');
        } elseif ($mode === 'semester') {
            $curYear = $tahun ?: (int) date('Y');
            $tahunAjaran = $request->query('tahun_ajaran', $semester === '1'
                ? "{$curYear}/" . ($curYear + 1)
                : ($curYear - 1) . "/{$curYear}");
            $range = $this->jadwal->resolveDateRange('semester', $bulan, $curYear, 1, $semester, $tahunAjaran);
            $start = Carbon::parse($range['start']);
            $end   = Carbon::parse($range['end']);
            $label = $range['label'];
        } else {
            $acuan = $request->filled('tanggal')
                ? Carbon::parse((string) $request->query('tanggal'))
                : Carbon::now();

            // Senin sebagai awal minggu; Sabtu sebagai akhir (sekolah 6 hari).
            $start = $acuan->copy()->startOfWeek(Carbon::MONDAY);
            $end   = $start->copy()->addDays(5);
            $label = 'Minggu ' . $start->translatedFormat('d M Y') . ' s.d. ' . $end->translatedFormat('d M Y');
        }

        $query = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk', 'ljp.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->whereBetween('ljp.tanggal', [$start->toDateString(), $end->toDateString()])
            ->where('ljp.status', '<>', 'batal')
            ->select('ljp.*', 'gtk.nama as guru_nama', 'gtk.nip as guru_nip', 'rb.nama as rombel_nama')
            ->orderBy('ljp.tanggal')
            ->orderBy('ljp.jam_mulai');

        LaboranScope::apply($query, 'ljp.jurusan_id', $context);

        if ($ptkId !== '') {
            $query->where(function ($q) use ($ptkId) {
                $q->where('ljp.ptk_id', $ptkId)
                  ->orWhere('ljp.ptk_ids', 'like', '%"' . $ptkId . '"%');
            });
        }

        $items = $query->get();

        // Kelompokkan per nama hari (untuk mode mingguan)
        $perHari = [];
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari) {
            $perHari[$hari] = $items->filter(
                fn($i) => Carbon::parse($i->tanggal)->translatedFormat('l') === $hari
            )->values();
        }

        // Kelompokkan per tanggal (untuk mode bulanan & semester)
        $perTanggal = $items->groupBy('tanggal')->sortKeys()->toArray();
        // Semester juga dikelompokkan per bulan agar cetak terstruktur
        $perBulan = [];
        if ($mode === 'semester') {
            $perBulan = $items
                ->groupBy(fn($i) => Carbon::parse($i->tanggal)->format('Y-m'))
                ->sortKeys()
                ->map(fn($g, $ym) => [
                    'label' => Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y'),
                    'rows'  => $g->sortBy('jam_mulai')->values(),
                ])
                ->toArray();
        }

        $totalMenit = 0;
        foreach ($items as $it) {
            $totalMenit += $this->durasiMenit($it->jam_mulai, $it->jam_selesai);
        }

        $guru = $ptkId !== '' ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : null;

        $daftarGuru = DB::table('gtk')
            ->whereIn('ptk_id', DB::table('laboran_jadwal_penggunaan')->select('ptk_id')->distinct())
            ->orderBy('nama')
            ->get(['ptk_id', 'nama', 'nip']);

        // Pejabat penanda tangan terpusat (Kepala Sekolah, Kepala Lab, KAPROG).
        $pejabat = $this->dokumen->pejabat($context);
        $petugas = $pejabat['petugas'];

        $orientasi = strtolower((string) $request->query('orientasi', 'portrait'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'portrait';
        }

        return [
            'mode'        => $mode,
            'label'       => $label,
            'start'       => $start->toDateString(),
            'end'         => $end->toDateString(),
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'guru'        => $guru,
            'ptkId'       => $ptkId,
            'items'       => $items,
            'perHari'     => $perHari,
            'perTanggal'  => $perTanggal,
            'perBulan'    => $perBulan,
            'semester'    => $semester,
            'totalSesi'   => $items->count(),
            'totalMenit'  => $totalMenit,
            'hariAktif'   => $items->pluck('tanggal')->unique()->count(),
            'lokasiUnik'  => $items->pluck('ruang_lab_nama')->unique()->count(),
            'daftarGuru'  => $daftarGuru,
            'sekolah'     => Sekolah::first(),
            'sekolahMeta' => SekolahMeta::first(),
            'kepalaSekolah' => $pejabat['kepalaSekolah'],
            'kepalaLab'     => $pejabat['kepalaLab'],
            'kaprog'        => $pejabat['kaprog'],
            'orientasi'   => $orientasi,
            'petugas'     => $petugas,
            'statusLabels' => LaboranJadwalService::STATUS_LIST,
            'jenisLabels'  => LaboranJadwalService::JENIS_LOKASI,
            'logCetak'     => $this->dokumen->catatCetak('jadwal-per-guru', $context, [
                'mode'           => 'lengkap',
                'orientasi'      => $orientasi,
                'judul'          => 'Jadwal Praktik per Guru — ' . $label,
                'periode_label'  => $label,
                'periode_dari'   => $start->toDateString(),
                'periode_sampai' => $end->toDateString(),
                'jumlah_baris'   => $items->count(),
                'filter'         => [
                    'mode'   => $mode,
                    'ptk_id' => $ptkId,
                ],
            ]),
        ];
    }

    /**
     * Selisih menit antara dua jam.
     *
     * Delegasi ke `LaboranJadwalService` agar hanya ada SATU implementasi
     * perhitungan durasi di modul ini.
     */
    protected function durasiMenit(?string $mulai, ?string $selesai): int
    {
        return $this->jadwal->durasiMenit($mulai, $selesai);
    }

    /**
     * Identitas petugas laboratorium untuk blok tanda tangan dokumen.
     */
    protected function resolvePetugasLaboran(): array
    {
        $user = session('user');
        $nama = is_array($user) ? ($user['nama'] ?? $user['name'] ?? null) : ($user->nama ?? $user->name ?? null);
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $nip = null;
        if ($ptkId) {
            $nip = DB::table('gtk')->where('ptk_id', $ptkId)->value('nip');
        }

        if (!$nama) {
            $row = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->where('rtt.kode', 'LABORAN')
                ->where('ptt.is_active', true)
                ->select('gtk.nama', 'gtk.nip')
                ->first();
            if ($row) {
                return ['nama' => $row->nama, 'nip' => $row->nip];
            }
        }

        return [
            'nama' => $nama ?: 'Petugas Laboratorium',
            'nip'  => $nip ?: null,
        ];
    }
}
