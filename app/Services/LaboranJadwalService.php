<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboranJadwalService
{
    public const JENIS_LOKASI = [
        'lab'      => 'Laboratorium',
        'bengkel'  => 'Bengkel Praktik',
        'lahan'    => 'Lahan / Kebun Praktik',
        'lapangan' => 'Lapangan / Area Terbuka',
        'lainnya'  => 'Lokasi Lainnya',
    ];

    public const STATUS_LIST = [
        'dijadwalkan' => 'Dijadwalkan',
        'berlangsung' => 'Berlangsung',
        'selesai'     => 'Selesai',
        'batal'       => 'Dibatalkan',
    ];

    /**
     * Jenis lokasi yang mengizinkan BEBERAPA sesi bersamaan pada jam yang sama.
     *
     * Lahan/kebun dan lapangan merupakan area terbuka yang dapat menampung
     * beberapa rombel sekaligus (mis. 3 kelas praktik olah tanah di petak
     * berbeda). Laboratorium dan bengkel tetap eksklusif karena keterbatasan
     * daya tampung dan alat.
     *
     * Bentrok GURU tetap diperiksa untuk semua jenis lokasi.
     */
    public const JENIS_LOKASI_BERSAMAAN = ['lahan', 'lapangan'];

    public const HARI_MAP = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * Ambil slot jadwal KBM aktif untuk satu rombel.
     */
    public function resolveKbmSlots(string $rombelId): array
    {
        if ($rombelId === '') {
            return [];
        }

        $kbmRows = DB::table('jadwal_kbm as jk')
            ->leftJoin('gtk', 'jk.ptk_id', '=', 'gtk.ptk_id')
            ->where('jk.rombongan_belajar_id', $rombelId)
            ->where('jk.is_active', true)
            ->where('jk.pembelajaran_id', '<>', 'ROUTINE_ISTIRAHAT')
            ->select(
                'jk.id',
                'jk.hari',
                'jk.jam_ke_mulai',
                'jk.jam_ke_selesai',
                'jk.jam_mulai',
                'jk.jam_selesai',
                'jk.nama_mata_pelajaran',
                'jk.mata_pelajaran_id',
                'jk.pembelajaran_id',
                'jk.ptk_id',
                'jk.ruangan',
                'gtk.nama as guru_nama',
                'gtk.nip as guru_nip'
            )
            ->orderByRaw("FIELD(jk.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('jk.jam_mulai')
            ->get();

        $slots = [];
        foreach ($kbmRows as $r) {
            $mulai = $r->jam_mulai ? substr((string) $r->jam_mulai, 0, 5) : '08:00';
            $selesai = $r->jam_selesai ? substr((string) $r->jam_selesai, 0, 5) : '09:30';
            $guru = $r->guru_nama ? $r->guru_nama : 'Guru Pengampu';

            $slots[] = [
                'id'                  => $r->id,
                'hari'                => $r->hari,
                'jam_mulai'           => $mulai,
                'jam_selesai'         => $selesai,
                'jam_ke'              => "Jam ke-{$r->jam_ke_mulai} s.d {$r->jam_ke_selesai}",
                'nama_mata_pelajaran' => $r->nama_mata_pelajaran,
                'mata_pelajaran_id'   => $r->mata_pelajaran_id,
                'pembelajaran_id'     => $r->pembelajaran_id,
                'ptk_id'              => $r->ptk_id,
                'guru_nama'           => $guru,
                'ruangan'             => $r->ruangan,
                'label'               => "{$r->hari} • {$mulai}-{$selesai} ({$r->nama_mata_pelajaran} — {$guru})",
            ];
        }

        return $slots;
    }

    /**
     * Hitung tanggal-tanggal sesi sesuai pilihan pemberlakuan.
     *
     * Mode yang didukung:
     *  - `mingguan` : hari tertentu (boleh >1) diulang tiap minggu dalam rentang tanggal.
     *  - `bulanan`  : hari tertentu (boleh >1) diulang tiap minggu selama 1 bulan penuh.
     *  - `sekali`   : satu sesi pada `tanggal` saja (kompatibilitas data lama).
     *
     * `semester` masih diterima demi kompatibilitas data lama dan diperlakukan
     * seperti `bulanan` dengan rentang 1 semester.
     *
     * @param array<int,string> $hari Daftar nama hari (Senin..Sabtu). Bila kosong,
     *                                hari diturunkan dari `$baseTanggal`.
     */
    public function generateDates(
        string $pemberlakuan,
        string $baseTanggal,
        ?int $bulan = null,
        ?int $tahun = null,
        ?string $semester = null,
        ?string $tahunAjaran = null,
        array $hari = [],
        ?string $tanggalAkhir = null
    ): array {
        $base = Carbon::parse($baseTanggal);

        if ($pemberlakuan === 'sekali' || $pemberlakuan === '') {
            return [$base->toDateString()];
        }

        // Hari yang dipilih -> daftar dayOfWeek Carbon (0=Minggu..6=Sabtu)
        $targetDays = $this->resolveTargetDaysOfWeek($hari, $base);

        // Rentang tanggal
        [$start, $end] = $this->resolveDateWindow(
            $pemberlakuan, $base, $bulan, $tahun, $semester, $tahunAjaran, $tanggalAkhir
        );

        $dates = [];
        $curr = $start->copy();
        while ($curr->lte($end)) {
            if (in_array($curr->dayOfWeek, $targetDays, true)) {
                $dates[] = $curr->toDateString();
            }
            $curr->addDay();
        }

        return !empty($dates) ? $dates : [$base->toDateString()];
    }

    /**
     * Ubah daftar nama hari menjadi indeks dayOfWeek Carbon.
     *
     * @param array<int,string> $hari
     * @return array<int,int>
     */
    protected function resolveTargetDaysOfWeek(array $hari, Carbon $base): array
    {
        $map = [
            'minggu' => 0, 'senin' => 1, 'selasa' => 2, 'rabu' => 3,
            'kamis' => 4, 'jumat' => 5, 'sabtu' => 6,
        ];

        $days = [];
        foreach ($hari as $h) {
            $key = strtolower(trim((string) $h));
            if (isset($map[$key])) {
                $days[] = $map[$key];
            }
        }

        return !empty($days) ? array_values(array_unique($days)) : [$base->dayOfWeek];
    }

    /**
     * Tentukan rentang tanggal [start, end] untuk pemberlakuan tertentu.
     *
     * @return array{0:Carbon, 1:Carbon}
     */
    protected function resolveDateWindow(
        string $pemberlakuan,
        Carbon $base,
        ?int $bulan,
        ?int $tahun,
        ?string $semester,
        ?string $tahunAjaran,
        ?string $tanggalAkhir
    ): array {
        // Rentang eksplisit menang atas perhitungan otomatis.
        if (!empty($tanggalAkhir)) {
            $end = Carbon::parse($tanggalAkhir);
            $start = $base->copy();

            return $end->lt($start) ? [$end->copy(), $start] : [$start, $end];
        }

        if ($pemberlakuan === 'bulanan') {
            $b = $bulan ?: $base->month;
            $y = $tahun ?: $base->year;

            return [
                Carbon::createFromDate($y, $b, 1)->startOfMonth(),
                Carbon::createFromDate($y, $b, 1)->endOfMonth(),
            ];
        }

        if ($pemberlakuan === 'semester') {
            $parts = explode('/', $tahunAjaran ?: ($base->month >= 7
                ? $base->year . '/' . ($base->year + 1)
                : ($base->year - 1) . '/' . $base->year));
            $y1 = (int) ($parts[0] ?? $base->year);
            $y2 = (int) ($parts[1] ?? ($y1 + 1));
            $sem = $semester ?: ($base->month >= 7 ? '1' : '2');

            return $sem === '1'
                ? [Carbon::createFromDate($y1, 7, 1), Carbon::createFromDate($y1, 12, 31)]
                : [Carbon::createFromDate($y2, 1, 1), Carbon::createFromDate($y2, 6, 30)];
        }

        // `mingguan` tanpa tanggal akhir: sebulan penuh sejak tanggal awal.
        return [$base->copy(), $base->copy()->addMonthNoOverflow()->subDay()];
    }

    /**
     * Ekstrak daftar seluruh PTK ID guru (guru utama + guru pendamping).
     */
    public function extractAllPtkIds(array $data): array
    {
        $ids = [];
        if (!empty($data['ptk_id'])) {
            $ids[] = trim((string) $data['ptk_id']);
        }

        if (!empty($data['ptk_ids'])) {
            if (is_array($data['ptk_ids'])) {
                foreach ($data['ptk_ids'] as $pid) {
                    $p = trim((string) $pid);
                    if ($p !== '') $ids[] = $p;
                }
            } elseif (is_string($data['ptk_ids'])) {
                $dec = json_decode($data['ptk_ids'], true);
                if (is_array($dec)) {
                    foreach ($dec as $pid) {
                        $p = trim((string) $pid);
                        if ($p !== '') $ids[] = $p;
                    }
                }
            }
        }

        return array_values(array_filter(array_unique($ids)));
    }

    /**
     * Format nama-nama guru pendamping untuk disimpan dan ditampilkan.
     */
    public function formatGuruPendamping(array $allPtkIds, ?string $primaryPtkId): ?string
    {
        $companionIds = array_values(array_filter($allPtkIds, fn($id) => $id !== $primaryPtkId));
        if (empty($companionIds)) {
            return null;
        }

        $names = DB::table('gtk')
            ->whereIn('ptk_id', $companionIds)
            ->pluck('nama')
            ->all();

        return !empty($names) ? implode(', ', $names) : null;
    }

    /**
     * Deteksi bentrok jadwal pada tanggal-tanggal tertentu (lokasi atau guru manapun).
     */
    public function findConflictForDates(array $data, array $targetDates, ?int $ignoreId = null): ?string
    {
        $allTeacherIds = $this->extractAllPtkIds($data);

        foreach ($targetDates as $tgl) {
            $conflict = $this->findConflictSingleDate($data, $tgl, $allTeacherIds, $ignoreId);
            if ($conflict) {
                return $conflict;
            }
        }

        return null;
    }

    /**
     * Deteksi bentrok pada satu tanggal.
     */
    public function findConflictSingleDate(array $data, string $tanggal, array $allTeacherIds, ?int $ignoreId = null): ?string
    {
        $base = DB::table('laboran_jadwal_penggunaan')
            ->where('tanggal', $tanggal)
            ->where('status', '<>', 'batal')
            ->where(function ($q) use ($data) {
                $q->where('jam_mulai', '<', $data['jam_selesai'])
                  ->where('jam_selesai', '>', $data['jam_mulai']);
            });

        if ($ignoreId) {
            $base->where('id', '<>', $ignoreId);
        }

        // 1. Bentrok Lokasi — hanya untuk lokasi eksklusif (lab/bengkel).
        //    Lahan & lapangan boleh dipakai beberapa sesi bersamaan.
        $jenisLokasi = $data['jenis_lokasi'] ?? null;
        $bolehBersamaan = in_array($jenisLokasi, self::JENIS_LOKASI_BERSAMAAN, true);

        if (!$bolehBersamaan) {
            $bentrokLokasi = (clone $base)
                ->where('ruang_lab_nama', $data['ruang_lab_nama'])
                ->first();

            if ($bentrokLokasi) {
                $tglStr = Carbon::parse($tanggal)->translatedFormat('d/m/Y');
                return 'Lokasi "' . $data['ruang_lab_nama'] . '" sudah dipakai pada ' . $tglStr . ' '
                    . substr((string) $bentrokLokasi->jam_mulai, 0, 5) . ' - '
                    . substr((string) $bentrokLokasi->jam_selesai, 0, 5)
                    . ' (' . $bentrokLokasi->mata_pelajaran . '). Pilih jam atau lokasi lain.';
            }
        }

        // 2. Bentrok Guru (memeriksa Guru Utama dan SEMUA Guru Pendamping)
        if (!empty($allTeacherIds)) {
            foreach ($allTeacherIds as $tid) {
                $bentrokGuru = (clone $base)
                    ->where(function ($q) use ($tid) {
                        $q->where('ptk_id', $tid)
                          ->orWhere('ptk_ids', 'like', '%"' . $tid . '"%');
                    })
                    ->first();

                if ($bentrokGuru) {
                    $namaGuru = DB::table('gtk')->where('ptk_id', $tid)->value('nama') ?: 'Guru';
                    $tglStr = Carbon::parse($tanggal)->translatedFormat('d/m/Y');

                    return "Guru {$namaGuru} sudah memiliki jadwal praktik lain pada {$tglStr} "
                        . substr((string) $bentrokGuru->jam_mulai, 0, 5) . ' - '
                        . substr((string) $bentrokGuru->jam_selesai, 0, 5)
                        . " ({$bentrokGuru->ruang_lab_nama}).";
                }
            }
        }

        return null;
    }

    /**
     * Hitung durasi sesi dalam menit.
     */
    public function durasiMenit(?string $mulai, ?string $selesai): int
    {
        if (!$mulai || !$selesai) {
            return 0;
        }

        try {
            $t1 = Carbon::createFromFormat('H:i:s', strlen($mulai) === 5 ? "{$mulai}:00" : $mulai);
            $t2 = Carbon::createFromFormat('H:i:s', strlen($selesai) === 5 ? "{$selesai}:00" : $selesai);

            return max(0, $t1->diffInMinutes($t2, false));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Tebak jenis lokasi dari nama ruang / gedung.
     */
    public function detectJenisLokasi(?string $namaRuang, ?string $gedung = null): string
    {
        $haystack = mb_strtolower(trim(($namaRuang ?? '') . ' ' . ($gedung ?? '')));

        if (str_contains($haystack, 'lahan') || str_contains($haystack, 'kebun') || str_contains($haystack, 'greenhouse') || str_contains($haystack, 'green house') || str_contains($haystack, 'hidroponik') || str_contains($haystack, 'kandang') || str_contains($haystack, 'kolam') || str_contains($haystack, 'pembibitan')) {
            return 'lahan';
        }

        if (str_contains($haystack, 'lapangan') || str_contains($haystack, 'outdoor') || str_contains($haystack, 'area terbuka')) {
            return 'lapangan';
        }

        if (str_contains($haystack, 'bengkel') || str_contains($haystack, 'workshop') || str_contains($haystack, 'otomotif') || str_contains($haystack, 'permesinan') || str_contains($haystack, 'las') || str_contains($haystack, 'kayu')) {
            return 'bengkel';
        }

        if (str_contains($haystack, 'lab') || str_contains($haystack, 'laboratorium') || str_contains($haystack, 'komputer') || str_contains($haystack, 'ipa') || str_contains($haystack, 'bahasa') || str_contains($haystack, 'kimia') || str_contains($haystack, 'fisika') || str_contains($haystack, 'biologi') || str_contains($haystack, 'multimedia') || str_contains($haystack, 'rpl') || str_contains($haystack, 'tkj')) {
            return 'lab';
        }

        return 'lainnya';
    }

    /**
     * Hitung rentang tanggal laporan.
     */
    public function resolveDateRange(string $tipe, int $bulan, int $tahun, int $triwulan, string $semester, string $tahunAjaran): array
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

    /**
     * Susun seluruh data laporan penggunaan lokasi.
     */
    public function buildLaporanData(Request $request): array
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
        if (!in_array($mode, ['mingguan', 'bulanan'], true)) {
            $mode = 'mingguan';
        }

        $ptkId = trim((string) $request->query('ptk_id', ''));
        $bulan = max(1, min(12, (int) $request->query('bulan', date('n'))));
        $tahun = (int) $request->query('tahun', date('Y'));

        // Rentang: minggu berjalan (Senin–Sabtu) atau satu bulan penuh.
        if ($mode === 'bulanan') {
            $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $label = 'Bulan ' . $start->translatedFormat('F Y');
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

        // Kelompokkan per tanggal (untuk mode bulanan)
        $perTanggal = $items->groupBy('tanggal')->sortKeys()->toArray();

        $totalMenit = 0;
        foreach ($items as $it) {
            $totalMenit += $this->durasiMenit($it->jam_mulai, $it->jam_selesai);
        }

        $guru = $ptkId !== '' ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : null;

        $daftarGuru = DB::table('gtk')
            ->whereIn('ptk_id', DB::table('laboran_jadwal_penggunaan')->select('ptk_id')->distinct())
            ->orderBy('nama')
            ->get(['ptk_id', 'nama', 'nip']);

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
            'totalSesi'   => $items->count(),
            'totalMenit'  => $totalMenit,
            'hariAktif'   => $items->pluck('tanggal')->unique()->count(),
            'lokasiUnik'  => $items->pluck('ruang_lab_nama')->unique()->count(),
            'daftarGuru'  => $daftarGuru,
            'sekolah'     => Sekolah::first(),
            'petugas'     => $this->resolvePetugasLaboran(),
            'statusLabels' => self::STATUS_LIST,
            'jenisLabels'  => self::JENIS_LOKASI,
        ];
    }

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
