<?php

namespace App\Services;

use App\Models\JadwalPengaturan;
use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Services\Concerns\LaboranJadwalKbmKuota;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboranJadwalService
{
    use LaboranJadwalKbmKuota;

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
     * Catatan aturan bentrok (berlaku untuk SEMUA jenis lokasi: lab, bengkel,
     * lahan, lapangan):
     *
     *  - LOKASI / tempat praktik BOLEH dipakai beberapa sesi pada jam yang sama,
     *    termasuk oleh guru lain dan rombel lain (permintaan pemilik modul:
     *    "tempat praktik bisa juga digunakan oleh guru lain"). Tidak ada lagi
     *    eksklusivitas kapasitas ruang.
     *  - GURU tetap eksklusif: satu guru tidak boleh mengajar dua sesi praktik
     *    yang jamnya beririsan, termasuk bila lokasinya sama (duplikat sesi).
     *
     * Lihat `findConflictSingleDate()`.
     */

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
            ->where('jk.sumber', $this->sumberKbmAktif())
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
        ?string $tanggalAkhir = null,
        ?int $minggu = null
    ): array {
        $base = Carbon::parse($baseTanggal);

        if ($pemberlakuan === 'sekali' || $pemberlakuan === '') {
            return [$base->toDateString()];
        }

        // Hari yang dipilih -> daftar dayOfWeek Carbon (0=Minggu..6=Sabtu)
        $targetDays = $this->resolveTargetDaysOfWeek($hari, $base);

        // Rentang tanggal
        [$start, $end] = $this->resolveDateWindow(
            $pemberlakuan, $base, $bulan, $tahun, $semester, $tahunAjaran, $tanggalAkhir, $minggu
        );

        $dates = [];
        $curr = $start->copy();
        while ($curr->lte($end)) {
            if (in_array($curr->dayOfWeek, $targetDays, true)) {
                $dates[] = $curr->toDateString();
            }
            $curr->addDay();
        }

        // Mode 1 sesi (mis. semester/template): bila hari terpilih tidak jatuh pada tanggal
        // acuan, geser ke kemunculan terdekat hari tersebut agar sesi tidak salah hari.
        if (empty($dates) && $start->equalTo($end)) {
            $curr = $start->copy();
            for ($i = 0; $i < 7; $i++) {
                if (in_array($curr->dayOfWeek, $targetDays, true)) {
                    $dates[] = $curr->toDateString();
                    break;
                }
                $curr->addDay();
            }
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
        ?string $tanggalAkhir,
        ?int $minggu = null
    ): array {
        // Rentang eksplisit menang atas perhitungan otomatis.
        if (!empty($tanggalAkhir)) {
            $end = Carbon::parse($tanggalAkhir);
            $start = $base->copy();

            return $end->lt($start) ? [$end->copy(), $start] : [$start, $end];
        }

        // Mingguan dan Semester dengan rentang minggu eksplisit (Minggu ke-N s/d ke-M):
        // mulai tanggal awal (minggu N) hingga akhir minggu M.
        if (in_array($pemberlakuan, ['mingguan', 'semester'], true) && $minggu && $minggu > 1) {
            $start = $base->copy();
            $end = $base->copy()->addWeeks($minggu)->subDay();

            return [$start, $end];
        }

        if ($pemberlakuan === 'bulanan') {
            $b = $bulan ?: $base->month;
            $y = $tahun ?: $base->year;

            return [
                Carbon::createFromDate($y, $b, 1)->startOfMonth(),
                Carbon::createFromDate($y, $b, 1)->endOfMonth(),
            ];
        }

        if ($pemberlakuan === 'mingguan') {
            // `mingguan` tanpa tanggal akhir: sebulan penuh sejak tanggal awal.
            return [$base->copy(), $base->copy()->addMonthNoOverflow()->subDay()];
        }

        // Sesuai jam dan tanggal yang dipilih (1 sesi jadwal)
        return [$base->copy(), $base->copy()];
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
     * Nama hari Indonesia dari tanggal (Y-m-d), atau null bila bukan hari sekolah.
     */
    public function hariDariTanggal(?string $tanggal): ?string
    {
        if (empty($tanggal)) {
            return null;
        }
        $ts = strtotime($tanggal);
        if ($ts === false) {
            return null;
        }
        $map = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];

        return $map[date('l', $ts)] ?? null;
    }

    /**
     * Tolak rentang jam yang menimpa slot rutinitas Pengaturan KBM
     * (Upacara, Pembiasaan, Istirahat).
     *
     * @return string|null Pesan kesalahan, atau null bila aman.
     */
    public function findRoutineOverlap(array $data, string $hari): ?string
    {
        $mulai = substr((string) ($data['jam_mulai'] ?? ''), 0, 5);
        $selesai = substr((string) ($data['jam_selesai'] ?? ''), 0, 5);
        if ($mulai === '' || $selesai === '') {
            return null;
        }

        foreach (JadwalPengaturan::getSlots($hari) as $slot) {
            if (!empty($slot['schedulable'])) {
                continue;
            }
            if ($mulai < $slot['selesai'] && $selesai > $slot['mulai']) {
                return 'Jam yang dipilih menimpa ' . trim((string) ($slot['label'] ?? 'kegiatan rutin'))
                    . ' (' . $slot['mulai'] . ' - ' . $slot['selesai']
                    . ') yang sudah diatur pada Jadwal KBM. Pilih jam praktik lain.';
            }
        }

        return null;
    }


    /**
     * Deteksi bentrok jadwal pada tanggal-tanggal tertentu.
     *
     * Hanya bentrok GURU yang diperiksa; lokasi praktik boleh dipakai bersama
     * (termasuk oleh guru lain pada rombel lain).
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

        // Pemberlakuan ketat: bentrok hanya diperiksa dalam cakupan mode yang sama
        $pemberlakuan = $data['pemberlakuan'] ?? null;
        if ($pemberlakuan) {
            if ($pemberlakuan === 'mingguan') {
                $base->where(function ($w) {
                    $w->where('pemberlakuan', 'mingguan')
                      ->orWhere('pemberlakuan', 'sekali')
                      ->orWhereNull('pemberlakuan');
                });
            } elseif ($pemberlakuan === 'semester') {
                $base->where('pemberlakuan', 'semester');
            }
        }

        if ($ignoreId) {
            $base->where('id', '<>', $ignoreId);
        }

        // 1. Bentrok LOKASI tidak lagi diperiksa.
        //    Tempat praktik (lab/bengkel/lahan/lapangan) boleh dipakai beberapa
        //    sesi pada jam yang sama, termasuk oleh guru dan rombel lain —
        //    permintaan pemilik modul agar lokasi dapat dipakai guru lain.
        //    Yang tetap eksklusif adalah GURU (lihat langkah 2).

        // 2. Bentrok Guru (memeriksa Guru Utama dan SEMUA Guru Pendamping)
        $bentrokGuru = null;
        $guruBentrokId = null;
        if (!empty($allTeacherIds)) {
            foreach ($allTeacherIds as $tid) {
                $bentrokGuru = (clone $base)
                    ->where(function ($q) use ($tid) {
                        $q->where('ptk_id', $tid)
                          ->orWhere('ptk_ids', 'like', '%"' . $tid . '"%');
                    })
                    ->first();

                if ($bentrokGuru) {
                    $guruBentrokId = $tid;
                    break;
                }
            }
        }

        // 3. Satu-satunya penyebab penolakan yang tersisa: guru bentrok.
        if ($bentrokGuru) {
            return $this->pesanBentrokGuru($guruBentrokId, $bentrokGuru, $tanggal);
        }

        return null;
    }

    /**
     * Pesan penolakan karena guru sudah mengajar praktik lain pada jam tersebut.
     */
    protected function pesanBentrokGuru(?string $ptkId, object $bentrok, string $tanggal): string
    {
        $namaGuru = $ptkId
            ? (DB::table('gtk')->where('ptk_id', $ptkId)->value('nama') ?: 'Guru')
            : 'Guru';
        $tglStr = Carbon::parse($tanggal)->translatedFormat('d/m/Y');

        return "Guru {$namaGuru} sudah memiliki jadwal praktik lain pada {$tglStr} "
            . substr((string) $bentrok->jam_mulai, 0, 5) . ' - '
            . substr((string) $bentrok->jam_selesai, 0, 5)
            . " ({$bentrok->ruang_lab_nama}).";
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
     *
     * Delegasi ke `LaboranJadwalLaporanService` — dipisah agar berkas ini tetap
     * di bawah batas 1.000 baris. API publik (nama & bentuk kembalian) tetap.
     */
    public function buildLaporanData(Request $request): array
    {
        return $this->laporan()->buildLaporanData($request);
    }

    /**
     * Susun jadwal praktik PER GURU untuk satu minggu, satu bulan, atau semester.
     *
     * @param string $mode 'mingguan' | 'bulanan' | 'semester'
     */
    public function buildJadwalPerGuru(Request $request): array
    {
        return $this->laporan()->buildJadwalPerGuru($request);
    }

    /**
     * Service laporan (dibuat malas agar tidak ada siklus konstruktor).
     */
    protected function laporan(): LaboranJadwalLaporanService
    {
        return new LaboranJadwalLaporanService($this);
    }

    // =========================================================================
    // PENGATURAN PEMBERLAKUAN JADWAL (Mingguan / Semester)
    // =========================================================================

    public const MODE_PEMBERLAKUAN = [
        'semester' => 'Semester',
        'mingguan' => 'Mingguan',
    ];

    /**
     * Ambil baris pengaturan pemberlakuan jadwal MILIK SATU LABORAN.
     *
     * Setiap laboran punya barisnya sendiri (kolom `ptk_id`) supaya pilihan
     * Semester/Mingguan tidak saling menimpa. Bila laboran belum pernah
     * memilih, nilainya diambil dari baris bawaan (ptk_id NULL) sebagai
     * fallback; baris itu dibuat otomatis bila belum ada.
     */
    public static function getPengaturanJadwal(?string $ptkId = null): object
    {
        $ptkId = trim((string) ($ptkId ?? ''));

        if ($ptkId !== '') {
            // Satu laboran hanya menyimpan satu mode aktif: ambil baris miliknya,
            // prioritaskan yang berstatus aktif.
            $row = DB::table('laboran_jadwal_pengaturan')
                ->where('ptk_id', $ptkId)
                ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
                ->orderByDesc('updated_at')
                ->first();

            if ($row) {
                return $row;
            }
        }

        // Fallback: baris bawaan/global (ptk_id NULL).
        $row = DB::table('laboran_jadwal_pengaturan')->whereNull('ptk_id')->first();
        if ($row) {
            return $row;
        }

        DB::table('laboran_jadwal_pengaturan')->insert([
            'ptk_id'            => null,
            'mode_pemberlakuan' => 'semester',
            'status'            => 'aktif',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return DB::table('laboran_jadwal_pengaturan')->whereNull('ptk_id')->first();
    }

    /**
     * Mode pemberlakuan yang sedang diberlakukan laboran ('semester' | 'mingguan' | null).
     */
    public static function getModeDiberlakukan(?string $ptkId = null): ?string
    {
        $row = self::getPengaturanJadwal($ptkId);

        return ($row->status === 'aktif' && in_array($row->mode_pemberlakuan, ['semester', 'mingguan'], true))
            ? $row->mode_pemberlakuan
            : null;
    }

    /**
     * Simpan pilihan mode pemberlakuan untuk satu laboran saja.
     *
     * Hanya satu baris per laboran: baris lama diperbarui bila ada, sehingga
     * mode yang dipilih menggantikan pilihan sebelumnya (pemberlakuan eksklusif)
     * tanpa menyentuh pengaturan laboran lain.
     */
    public static function simpanPengaturanLaboran(?string $ptkId, string $mode, string $status): void
    {
        $ptkId = trim((string) ($ptkId ?? ''));

        $payload = [
            'mode_pemberlakuan'        => $mode,
            'status'                   => $status,
            'diberlakukan_oleh_ptk_id' => $status === 'aktif' ? ($ptkId !== '' ? $ptkId : null) : null,
            'updated_at'               => now(),
        ];

        $query = DB::table('laboran_jadwal_pengaturan')->where('ptk_id', $ptkId === '' ? null : $ptkId)
            ->when($ptkId === '', fn ($q) => $q->whereNull('ptk_id'));

        if ($query->clone()->exists()) {
            $query->update($payload);
            return;
        }

        DB::table('laboran_jadwal_pengaturan')->insert($payload + [
            'ptk_id'     => $ptkId !== '' ? $ptkId : null,
            'created_at' => now(),
        ]);
    }

}
