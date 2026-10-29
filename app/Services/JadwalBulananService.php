<?php

namespace App\Services;

use App\Models\JadwalKbmPekan;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ekspansi jadwal KBM dari POLA mingguan menjadi JADWAL BULANAN.
 *
 * Prinsip desain: `jadwal_kbm` tetap berupa pola (hari + jam ke), tidak
 * dimaterialisasi per tanggal. Ekspansi pola x kalender x pekan dilakukan
 * di PHP saat cetak, sehingga `presensi_mengajar.jadwal_kbm_id` dan
 * `agenda_kbm.jadwal_kbm_id` tetap menunjuk baris pola yang sama.
 */
class JadwalBulananService
{
    public function __construct(
        protected JadwalPekanService $pekanService,
        protected JadwalPrintService $printService
    ) {}

    public const FORMATS = [
        'rombel' => 'Jadwal Bulanan per Rombel',
        'guru'   => 'Jadwal Mengajar Guru Bulanan',
        'rekap'  => 'Rekap KBM Bulanan',
    ];

    public const BULAN_LABELS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Bangun seluruh data yang dibutuhkan view cetak bulanan.
     */
    public function build(Request $request): array
    {
        $bulan = max(1, min(12, (int) ($request->get('bulan') ?: now()->month)));
        $tahun = max(2000, min(2100, (int) ($request->get('tahun') ?: now()->year)));
        $format = array_key_exists((string) $request->get('format'), self::FORMATS)
            ? (string) $request->get('format')
            : 'rombel';

        $periode = $this->resolvePeriode($request, $bulan, $tahun);
        $sumber = $request->get('sumber') ?: (JadwalPengaturan::getModeAktif() ?: 'otomatis');
        $sumber = in_array($sumber, ['otomatis', 'manual'], true) ? $sumber : 'otomatis';

        $awalBulan = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth()->startOfDay();

        $pekanMap = $this->pekanService->petaPekanPerTanggal(
            $awalBulan->toDateString(),
            $akhirBulan->toDateString(),
            $periode['tahun_ajaran'],
            $periode['semester']
        );

        // Fitur pekan bersifat opt-in: bila periode ini belum punya pekan sama
        // sekali, cetak bulanan tetap mengekspansi seluruh hari efektif agar
        // dokumen lama tidak mendadak kosong.
        $pakaiPekan = $this->pekanService
            ->daftarPekan($periode['tahun_ajaran'], $periode['semester'])
            ->isNotEmpty();

        $polaByHari = $this->getPolaByHari($sumber);
        $tanggalList = $this->buildTanggalList($awalBulan, $akhirBulan, $periode, $pekanMap, $polaByHari, $pakaiPekan);

        $maxJp = $this->resolveMaxJp($tanggalList);
        $matriks = $this->buildMatriks($tanggalList, $format);

        // Default cetak = satu entitas agar dokumen tetap ringan (31 tanggal x
        // 13 JP x 35 rombel menghasilkan HTML >5 MB dan lambat dicetak).
        // Pilih "semua" secara eksplisit bila memang butuh seluruh rombel/guru.
        $cetakSemua = $request->boolean('semua');

        $opsiRombel = $this->getRombelList(null, $sumber);
        $opsiGuru = $this->getGuruList($sumber, null);

        $rombels = $format === 'rombel'
            ? ($cetakSemua
                ? $opsiRombel
                : $opsiRombel->filter(fn ($r) => !$request->get('rombel_id') || $r->rombongan_belajar_id === $request->get('rombel_id'))->take(1)->values())
            : collect();

        $guruList = $format === 'guru'
            ? ($cetakSemua
                ? $opsiGuru
                : $opsiGuru->filter(fn ($g) => !$request->get('ptk_id') || $g->ptk_id === $request->get('ptk_id'))->take(1)->values())
            : collect();

        $rekap = $format === 'rekap' ? $this->buildRekap($tanggalList) : [];

        return [
            'sekolah'        => DB::table('sekolah')->first(),
            'kepalaSekolah'  => $this->printService->resolveKepalaSekolah(),
            'wakaKurikulum'  => $this->printService->resolveWakaKurikulum(),
            'pengaturan'     => JadwalPengaturan::getSettings(),
            'slots'          => JadwalPengaturan::getSlots(),
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'bulanLabel'     => self::BULAN_LABELS[$bulan] . ' ' . $tahun,
            'tahunAjaran'    => $periode['tahun_ajaran'],
            'semester'       => $periode['semester'],
            'semesterLabel'  => $periode['semester'] === '1' ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)',
            'periodeStart'   => $periode['start'],
            'periodeEnd'     => $periode['end'],
            'format'         => $format,
            'formatLabel'    => self::FORMATS[$format],
            'sumber'         => $sumber,
            'tanggalList'    => $tanggalList,
            'mingguList'     => $this->buildMingguList($tanggalList),
            'maxJp'          => $maxJp,
            'matriks'        => $matriks,
            'rombels'        => $rombels,
            'guruList'       => $guruList,
            'opsiRombel'     => $opsiRombel,
            'opsiGuru'       => $opsiGuru,
            'cetakSemua'     => $cetakSemua,
            'rekap'          => $rekap,
            'ringkasan'      => $this->buildRingkasan($tanggalList),
            'rombelId'       => $request->get('rombel_id'),
            'ptkId'          => $request->get('ptk_id'),
        ];
    }

    /**
     * Resolusi periode (tahun ajaran + semester) dari bulan cetak.
     *
     * @return array{tahun_ajaran:string, semester:string, start:string, end:string}
     */
    protected function resolvePeriode(Request $request, int $bulan, int $tahun): array
    {
        $tahunAjaran = $request->get('tahun_ajaran');
        $semester = $request->get('semester');

        if (!$tahunAjaran || !$semester) {
            // Juli–Desember = semester 1, Januari–Juni = semester 2.
            if ($bulan >= 7) {
                $tahunAjaran = $tahunAjaran ?: "{$tahun}/" . ($tahun + 1);
                $semester = $semester ?: '1';
            } else {
                $tahunAjaran = $tahunAjaran ?: ($tahun - 1) . "/{$tahun}";
                $semester = $semester ?: '2';
            }
        }

        $periode = KalenderPendidikan::resolvePeriodeDates($tahunAjaran, (string) $semester);

        return [
            'tahun_ajaran' => $periode['tahun_ajaran'],
            'semester'     => (string) ($periode['semester'] === 'semua' ? '1' : $periode['semester']),
            'start'        => $periode['start'],
            'end'          => $periode['end'],
        ];
    }

    /**
     * Pola jadwal aktif per hari (1 query), dikelompokkan berdasarkan hari.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, object>>
     */
    protected function getPolaByHari(string $sumber)
    {
        return DB::table('jadwal_kbm as j')
            ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
            ->leftJoin('rombongan_belajar as r', 'j.rombongan_belajar_id', '=', 'r.rombongan_belajar_id')
            ->where('j.sumber', $sumber)
            ->where('j.is_active', true)
            ->select('j.*', 'g.nama as nama_guru', 'r.nama as rombel_nama')
            ->orderBy('j.jam_ke_mulai')
            ->get()
            ->groupBy('hari');
    }

    /**
     * Susun informasi tiap tanggal pada bulan cetak.
     *
     * @param array<string, JadwalKbmPekan> $pekanMap
     * @param \Illuminate\Support\Collection $polaByHari
     * @return array<int, array<string, mixed>>
     */
    protected function buildTanggalList(
        Carbon $awal,
        Carbon $akhir,
        array $periode,
        $pekanMap,
        $polaByHari,
        bool $pakaiPekan = true
    ): array {
        $list = [];
        $cursor = $awal->copy();

        while ($cursor->lte($akhir)) {
            $tanggal = $cursor->toDateString();
            $namaHari = KalenderPendidikan::getNamaHariIndo($cursor);
            $statusHari = KalenderPendidikan::getStatusHari($tanggal, 'all');
            /** @var JadwalKbmPekan|null $pekan */
            $pekan = $pekanMap[$tanggal] ?? null;

            $dalamSemester = $tanggal >= $periode['start'] && $tanggal <= $periode['end'];
            $statusPekan = $pekan?->status;

            $keterangan = null;
            $isEfektif = false;

            if (!$dalamSemester) {
                $keterangan = 'Di luar semester';
            } elseif ($pakaiPekan && $statusPekan === null) {
                $keterangan = 'Tanggal di luar rentang pekan';
            } elseif ($pakaiPekan && $statusPekan === 'libur') {
                $keterangan = 'Pekan libur';
            } elseif ($pakaiPekan && $statusPekan === 'ujian') {
                $keterangan = 'Pekan ujian / asesmen';
            } elseif ($pakaiPekan && $statusPekan === 'draft') {
                $keterangan = 'Pekan belum diberlakukan';
            } elseif (!empty($statusHari['is_libur'])) {
                $keterangan = $statusHari['nama_agenda'] ?: 'Hari libur';
            } else {
                $isEfektif = true;
                $keterangan = $statusHari['nama_agenda'] ?? null;
            }

            $items = $isEfektif ? ($polaByHari[$namaHari] ?? collect())->values() : collect();

            $list[] = [
                'tanggal'      => $tanggal,
                'hari'         => $namaHari,
                'hari_ke'      => $cursor->dayOfWeekIso,
                'pekan_ke'     => $pekan?->pekan_ke,
                'pekan_status' => $statusPekan,
                'pekan_label'  => $pekan?->rentang_label,
                'is_efektif'   => $isEfektif,
                'is_libur'     => !$isEfektif,
                'mode'         => $statusHari['mode'] ?? 'luring',
                'keterangan'   => $keterangan,
                'items'        => $items,
                'jp'           => $items->sum(fn ($i) => max(1, (int) $i->jam_ke_selesai - (int) $i->jam_ke_mulai + 1)),
            ];

            $cursor->addDay();
        }

        return $list;
    }

    /**
     * Jumlah baris JP maksimum yang perlu digambar pada grid bulanan.
     *
     * @param array<int, array<string, mixed>> $tanggalList
     */
    protected function resolveMaxJp(array $tanggalList): int
    {
        $max = (int) (JadwalPengaturan::getSettings()->total_slot_jp ?? 10);
        $dailyCounts = JadwalPengaturan::getDailySlotCounts();

        foreach ($tanggalList as $t) {
            $max = max($max, (int) ($dailyCounts[$t['hari']] ?? 0));
            foreach ($t['items'] as $item) {
                $max = max($max, (int) $item->jam_ke_selesai);
            }
        }

        return max(1, min(16, $max));
    }

    /**
     * Susun matriks JP per tanggal untuk format rombel atau guru.
     *
     * @param array<int, array<string, mixed>> $tanggalList
     * @return array<string, array<string, array<int, object>>>
     */
    protected function buildMatriks(array $tanggalList, string $format): array
    {
        $matriks = [];

        if ($format === 'rekap') {
            return $matriks;
        }

        foreach ($tanggalList as $t) {
            foreach ($t['items'] as $item) {
                if ($format === 'guru') {
                    if (empty($item->ptk_id)) {
                        continue;
                    }
                    if (str_starts_with((string) $item->pembelajaran_id, 'ROUTINE_')) {
                        continue;
                    }
                    $key = $item->ptk_id;
                } else {
                    $key = $item->rombongan_belajar_id;
                }

                $matriks[$key][$t['tanggal']][(int) $item->jam_ke_mulai] = $item;
            }
        }

        return $matriks;
    }

    /**
     * Kelompokkan tanggal per pekan untuk header tabel cetak.
     *
     * @param array<int, array<string, mixed>> $tanggalList
     * @return array<int, array<string, mixed>>
     */
    protected function buildMingguList(array $tanggalList): array
    {
        $minggu = [];

        foreach ($tanggalList as $t) {
            $key = $t['pekan_ke'] ?? 0;

            if (!isset($minggu[$key])) {
                $minggu[$key] = [
                    'pekan_ke' => $t['pekan_ke'],
                    'label'    => $t['pekan_ke'] ? 'Pekan ke-' . $t['pekan_ke'] : 'Di luar pekan',
                    'status'   => $t['pekan_status'],
                    'rentang'  => $t['pekan_label'],
                    'tanggal'  => [],
                    'hari'     => [],
                ];
            }

            $minggu[$key]['tanggal'][] = $t['tanggal'];
            $minggu[$key]['hari'][] = $t;
        }

        return array_values($minggu);
    }

    /**
     * Rekap JP per rombel: terjadwal, efektif, libur/ujian.
     *
     * @param array<int, array<string, mixed>> $tanggalList
     * @return array<int, array<string, mixed>>
     */
    protected function buildRekap(array $tanggalList): array
    {
        $rekap = [];

        foreach ($tanggalList as $t) {
            foreach ($t['items'] as $item) {
                $rId = $item->rombongan_belajar_id;
                if (!isset($rekap[$rId])) {
                    $rekap[$rId] = [
                        'rombongan_belajar_id' => $rId,
                        'nama_rombel'          => null,
                        'jp_terjadwal'         => 0,
                        'jp_efektif'           => 0,
                        'hari_efektif'         => 0,
                        'hari_libur'           => 0,
                        'hari_ujian'           => 0,
                    ];
                }

                $jp = max(1, (int) $item->jam_ke_selesai - (int) $item->jam_ke_mulai + 1);
                $rekap[$rId]['jp_terjadwal'] += $jp;
                if ($t['is_efektif']) {
                    $rekap[$rId]['jp_efektif'] += $jp;
                }
            }
        }

        // Hitung hari efektif / libur / ujian per rombel (sama untuk semua rombel).
        $hariEfektif = 0;
        $hariLibur = 0;
        $hariUjian = 0;
        foreach ($tanggalList as $t) {
            if ($t['pekan_status'] === 'ujian') {
                $hariUjian++;
            } elseif ($t['is_efektif']) {
                $hariEfektif++;
            } else {
                $hariLibur++;
            }
        }

        if (empty($rekap)) {
            return [];
        }

        $namaRombel = DB::table('rombongan_belajar')
            ->whereIn('rombongan_belajar_id', array_keys($rekap))
            ->pluck('nama', 'rombongan_belajar_id');

        foreach ($rekap as $rId => $row) {
            $rekap[$rId]['nama_rombel'] = $namaRombel[$rId] ?? '-';
            $rekap[$rId]['hari_efektif'] = $hariEfektif;
            $rekap[$rId]['hari_libur'] = $hariLibur;
            $rekap[$rId]['hari_ujian'] = $hariUjian;
        }

        usort($rekap, fn ($a, $b) => strcmp((string) $a['nama_rombel'], (string) $b['nama_rombel']));

        return $rekap;
    }

    /**
     * Ringkasan umum bulan cetak.
     *
     * @param array<int, array<string, mixed>> $tanggalList
     * @return array<string, int>
     */
    protected function buildRingkasan(array $tanggalList): array
    {
        $ringkasan = [
            'total_hari'    => count($tanggalList),
            'hari_efektif'  => 0,
            'hari_libur'    => 0,
            'hari_ujian'    => 0,
            'hari_draft'    => 0,
            'total_jp'      => 0,
        ];

        foreach ($tanggalList as $t) {
            if ($t['pekan_status'] === 'ujian') {
                $ringkasan['hari_ujian']++;
            } elseif ($t['pekan_status'] === 'draft') {
                $ringkasan['hari_draft']++;
            } elseif ($t['is_efektif']) {
                $ringkasan['hari_efektif']++;
            } else {
                $ringkasan['hari_libur']++;
            }

            if ($t['is_efektif']) {
                $ringkasan['total_jp'] += (int) $t['jp'];
            }
        }

        return $ringkasan;
    }

    /**
     * Daftar rombel reguler untuk pilihan cetak.
     */
    protected function getRombelList(?string $rombelId, string $sumber)
    {
        $activeRombelIds = DB::table('jadwal_kbm')
            ->where('sumber', $sumber)
            ->where('is_active', true)
            ->whereNotNull('rombongan_belajar_id')
            ->distinct()
            ->pluck('rombongan_belajar_id');

        $query = \App\Support\RombelScope::reguler(
            DB::table('rombongan_belajar as r')->leftJoin('gtk as w', 'r.ptk_id', '=', 'w.ptk_id'),
            'r.'
        )
            ->select('r.rombongan_belajar_id', 'r.nama', 'r.tingkat_pendidikan_id', 'w.nama as nama_wali_kelas')
            ->orderBy('r.tingkat_pendidikan_id')
            ->orderBy('r.nama');

        if ($rombelId) {
            $query->where('r.rombongan_belajar_id', $rombelId);
        } else {
            $query->whereIn('r.rombongan_belajar_id', $activeRombelIds);
        }

        return $query->get();
    }

    /**
     * Daftar guru yang memiliki pola jadwal aktif.
     */
    protected function getGuruList(string $sumber, ?string $ptkId = null)
    {
        $activePtkIds = DB::table('jadwal_kbm')
            ->where('sumber', $sumber)
            ->where('is_active', true)
            ->whereNotNull('ptk_id')
            ->distinct()
            ->pluck('ptk_id');

        $query = DB::table('gtk')
            ->whereIn('ptk_id', $activePtkIds)
            ->whereNotNull('nama')
            ->select('ptk_id', 'nama', 'nip')
            ->orderBy('nama');

        if ($ptkId) {
            $query->where('ptk_id', $ptkId);
        }

        return $query->get();
    }
}
