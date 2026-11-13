<?php

namespace App\Http\Controllers\Laboran;

use App\Models\PresensiHarian;
use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Log Presensi Harian — modul baca-saja untuk Laboran.
 *
 * Menampilkan log presensi harian (terminal RFID / QR / manual) yang
 * DIBATASI pada jurusan tempat Laboran ditugaskan. Cakupan memakai relasi
 * `presensi_harian.rombongan_belajar_id` → `rombongan_belajar.jurusan_id`,
 * karena tabel presensi tidak menyimpan `jurusan_id` langsung.
 *
 * Tidak ada aksi CRUD: halaman hanya menampilkan data, filter kelas
 * (rombel), status, pencarian, dan pratinjau snapshot foto.
 */
class LaboranLogPresensiController extends LaboranBaseController
{
    /** Kolom yang boleh dipakai sebagai kunci pengurutan. */
    public const SORTABLE = ['nama_siswa', 'nama_rombel', 'jam_masuk', 'jam_pulang', 'status'];

    /** Status presensi yang dikenali. */
    public const STATUSES = ['H', 'T', 'I', 'S', 'D', 'A'];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        // Selaraskan dengan halaman presensi pusat: tandai pulang cepat untuk
        // siswa yang tidak tap pulang pada hari sebelumnya.
        PresensiHarian::autoCloseUncheckedOut();

        $tanggal = $this->resolveTanggal($request);
        $q       = trim((string) $request->query('q', ''));
        $status  = strtoupper(trim((string) $request->query('status', '')));
        $perPage = $this->resolvePerPage($request);

        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $sort    = in_array($request->query('sort'), self::SORTABLE, true) ? (string) $request->query('sort') : 'jam_masuk';
        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        // --- Pilihan kelas (rombel) sesuai cakupan jurusan ----------------
        $rombelList = $this->rombelOptions($context);

        // Filter rombel di luar cakupan diabaikan (bukan error) agar URL
        // hasil salin-tempel tidak membocorkan data jurusan lain.
        $rombelId = trim((string) $request->query('rombel_id', ''));
        if ($rombelId !== '' && !$rombelList->contains('rombongan_belajar_id', $rombelId)) {
            $rombelId = '';
        }

        // --- Statistik tanggal terpilih ----------------------------------
        $stats = $this->stats($context, $tanggal);

        // --- Data log ----------------------------------------------------
        $logs = $this->logQuery($context, $tanggal, $rombelId, $status, $q)
            ->orderBy($this->sortColumn($sort), $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $logs->getCollection()->transform(function ($item) {
            $item->foto_profil_url = $this->fotoUrl($item->foto_path);
            $item->foto_masuk_url  = $this->fotoUrl($item->foto_masuk);
            $item->foto_pulang_url = $this->fotoUrl($item->foto_pulang);

            return $item;
        });

        return view('dashboard.laboran.log-presensi', array_merge(
            compact('tanggal', 'logs', 'stats', 'rombelList', 'rombelId', 'status', 'q', 'perPage', 'sort', 'sortDir'),
            $this->abilities(),
            [
                'context'    => $context,
                'scopeBadge' => LaboranScope::badge($context),
            ]
        ));
    }

    public function siswaOptionsJson(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return response()->json([], 403);
        }

        $rombelId = trim((string) $request->query('rombel_id', ''));
        $q = trim((string) $request->query('q', ''));

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'rb.nama as nama_rombel', 'pd.rombongan_belajar_id')
            ->orderBy('pd.nama');

        LaboranScope::apply($query, 'rb.jurusan_id', $context);

        if ($rombelId !== '') {
            $query->where('pd.rombongan_belajar_id', $rombelId);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('pd.nama', 'like', "%{$q}%")
                  ->orWhere('pd.nisn', 'like', "%{$q}%")
                  ->orWhere('pd.nipd', 'like', "%{$q}%");
            });
        }

        $list = $query->limit(50)->get();

        return response()->json($list);
    }

    /**
     * Cetak dokumen resmi Laporan Presensi Harian (A4).
     *
     * Mendukung tipe laporan 'kelas' (rekap/log rombel) dan 'murid' (kartu riwayat siswa tertentu),
     * serta periode 'hari', 'bulan', 'semester', dan 'tahun'.
     * Dicatat ke `laboran_log_cetak` + `laboran_arsip` via `dokumenCetak()`.
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        $tipeLaporan = $request->query('tipe_laporan') === 'murid' ? 'murid' : 'kelas';
        $periode     = $request->query('periode', 'hari');
        if (!in_array($periode, ['hari', 'bulan', 'semester', 'tahun'], true)) {
            $periode = 'hari';
        }

        $mode      = $this->modeCetak($request, $tipeLaporan === 'murid' ? 'lengkap' : 'ringkasan');
        $orientasi = $this->orientasiCetak($request, ($tipeLaporan === 'kelas' && $mode === 'lengkap') ? 'landscape' : 'portrait');

        $tanggal     = $this->resolveTanggal($request);
        $bulan       = (int) $request->query('bulan', date('n'));
        $tahun       = (int) $request->query('tahun', date('Y'));
        $semester    = (string) $request->query('semester', (int) date('n') >= 7 ? '1' : '2');
        $tahunAjaran = (string) $request->query('tahun_ajaran', (int) date('n') >= 7 ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y'));

        $range = $this->resolveDateRange($periode, $tanggal, $bulan, $tahun, $semester, $tahunAjaran);

        $q      = trim((string) $request->query('q', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $rombelList = $this->rombelOptions($context);
        $rombelId   = trim((string) $request->query('rombel_id', ''));
        $rombelObj  = null;
        if ($rombelId !== '') {
            $rombelObj = $rombelList->firstWhere('rombongan_belajar_id', $rombelId);
            if (!$rombelObj) {
                $rombelId = '';
            }
        }

        // TIPE LAPORAN: PER MURID
        if ($tipeLaporan === 'murid') {
            $pesertaDidikId = trim((string) $request->query('peserta_didik_id', ''));
            $siswaQuery = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
                ->select(
                    'pd.*',
                    'rb.nama as nama_rombel',
                    'rb.tingkat_pendidikan_id',
                    'rb.jurusan_id_str',
                    'pdm.foto_path',
                    'pdm.rfid_uid',
                    'wali.nama as wali_nama',
                    'wali.nip as wali_nip',
                    'wali.nuptk as wali_nuptk'
                );

            LaboranScope::apply($siswaQuery, 'rb.jurusan_id', $context);

            if ($pesertaDidikId !== '') {
                $siswaQuery->where('pd.peserta_didik_id', $pesertaDidikId);
            } elseif ($rombelId !== '') {
                $siswaQuery->where('pd.rombongan_belajar_id', $rombelId);
            }

            $siswa = $siswaQuery->first();
            if (!$siswa) {
                return redirect()->route('dashboard.laboran.log-presensi', $request->query())
                    ->with('error', 'Peserta didik pada jurusan ini tidak ditemukan.');
            }

            $logs = DB::table('presensi_harian')
                ->where('peserta_didik_id', $siswa->peserta_didik_id)
                ->whereBetween('tanggal', [$range['start'], $range['end']])
                ->orderBy('tanggal', 'asc')
                ->get();

            $countHadir     = $logs->where('status', 'H')->count();
            $countTerlambat = $logs->where('status', 'T')->count();
            $countIzin      = $logs->where('status', 'I')->count();
            $countSakit     = $logs->where('status', 'S')->count();
            $countDispen    = $logs->where('status', 'D')->count();
            $countAlpha     = $logs->where('status', 'A')->count();
            $totalRecord    = $logs->count();
            $totalHadir     = $countHadir + $countTerlambat;
            $menitTerlambat = $logs->where('status', 'T')->sum('menit_terlambat');

            $hariEfektifTotal    = \App\Models\KalenderPendidikan::hitungHariEfektif($range['start'], $range['end'], 'pd');
            $hariEfektifBerjalan = \App\Models\KalenderPendidikan::hitungHariEfektifBerjalan($range['start'], $range['end'], now()->toDateString(), 'pd');

            $totalKehadiran = $totalHadir + $countDispen;
            $deno = $hariEfektifBerjalan > 0 ? $hariEfektifBerjalan : $totalRecord;
            $persenKehadiran = $deno > 0
                ? min(100.0, round(($totalKehadiran / $deno) * 100, 1))
                : ($totalRecord > 0 ? round(($totalKehadiran / $totalRecord) * 100, 1) : 100.0);

            $stats = [
                'total'                 => $totalRecord,
                'hadir'                 => $countHadir,
                'terlambat'             => $countTerlambat,
                'izin'                  => $countIzin,
                'sakit'                 => $countSakit,
                'dispen'                => $countDispen,
                'alpha'                 => $countAlpha,
                'total_hadir'           => $totalHadir,
                'menit_terlambat'       => $menitTerlambat,
                'hari_efektif'          => $hariEfektifTotal,
                'hari_efektif_berjalan' => $hariEfektifBerjalan,
                'persen'                => $persenKehadiran,
            ];

            $filterLabel = [
                'Periode: ' . $range['label'],
                'Kelas: ' . ($siswa->nama_rombel ?: '—'),
                'Siswa: ' . $siswa->nama . ($siswa->nisn ? ' (' . $siswa->nisn . ')' : ''),
            ];

            return view('dashboard.laboran.log-presensi-cetak', array_merge(
                compact(
                    'tipeLaporan', 'periode', 'range', 'siswa', 'logs', 'stats',
                    'filterLabel', 'rombelList', 'rombelId', 'rombelObj', 'bulan',
                    'tahun', 'semester', 'tahunAjaran', 'tanggal'
                ),
                [
                    'backUrl' => route('dashboard.laboran.log-presensi', $request->only(['tanggal', 'rombel_id', 'status', 'q'])),
                ],
                $this->dokumenCetak('log-presensi', $context, [
                    'judul'        => 'Laporan Presensi Peserta Didik — ' . $siswa->nama,
                    'mode'         => $mode,
                    'orientasi'    => $orientasi,
                    'jumlah_baris' => $logs->count(),
                    'filter'       => [
                        'tipe_laporan'      => 'murid',
                        'peserta_didik_id'  => $siswa->peserta_didik_id,
                        'periode'           => $periode,
                        'range'             => $range,
                    ],
                ])
            ));
        }

        // TIPE LAPORAN: PER KELAS / JURUSAN
        $isHarian = ($periode === 'hari');

        // Query log presensi pada rentang tanggal terpilih
        $itemsQuery = $this->logBase($context)
            ->whereBetween('ph.tanggal', [$range['start'], $range['end']]);

        if ($rombelId !== '') {
            $itemsQuery->where('ph.rombongan_belajar_id', $rombelId);
        }
        if ($status !== '') {
            $itemsQuery->where('ph.status', $status);
        }
        if ($q !== '') {
            $itemsQuery->where(function ($builder) use ($q) {
                $builder->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%")
                    ->orWhere('pd.nipd', 'like', "%{$q}%")
                    ->orWhere('rb.nama', 'like', "%{$q}%");
            });
        }

        $items = $itemsQuery
            ->select(
                'ph.*',
                'pd.nama as nama_siswa',
                'pd.nisn as nisn_siswa',
                'pd.nipd as nipd_siswa',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            )
            ->orderBy('ph.tanggal', 'desc')
            ->orderBy('rb.nama')
            ->orderBy('pd.nama')
            ->get();

        // Statistik umum
        if ($isHarian) {
            $stats = $this->stats($context, $tanggal);
        } else {
            $countHadir     = $items->where('status', 'H')->count();
            $countTerlambat = $items->where('status', 'T')->count();
            $countIzin      = $items->where('status', 'I')->count();
            $countSakit     = $items->where('status', 'S')->count();
            $countDispen    = $items->where('status', 'D')->count();
            $countAlpha     = $items->where('status', 'A')->count();
            $totalRecord    = $items->count();
            $totalHadir     = $countHadir + $countTerlambat;

            $hariEfektifTotal    = \App\Models\KalenderPendidikan::hitungHariEfektif($range['start'], $range['end'], 'pd');
            $hariEfektifBerjalan = \App\Models\KalenderPendidikan::hitungHariEfektifBerjalan($range['start'], $range['end'], now()->toDateString(), 'pd');

            $siswaQuery = DB::table('peserta_didik as pd')
                ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id');
            LaboranScope::apply($siswaQuery, 'rb.jurusan_id', $context);
            if ($rombelId !== '') {
                $siswaQuery->where('pd.rombongan_belajar_id', $rombelId);
            }
            $totalSiswa = (int) $siswaQuery->count('pd.peserta_didik_id');

            $expectedSlots = ($hariEfektifBerjalan > 0 ? $hariEfektifBerjalan : 1) * ($totalSiswa > 0 ? $totalSiswa : 1);
            $persen = $expectedSlots > 0 ? round((($totalHadir + $countDispen) / $expectedSlots) * 100, 1) : 0.0;

            $stats = [
                'total'                 => $totalRecord,
                'total_siswa'           => $totalSiswa,
                'hadir'                 => $countHadir,
                'terlambat'             => $countTerlambat,
                'izin'                  => $countIzin,
                'sakit'                 => $countSakit,
                'dispen'                => $countDispen,
                'alpha'                 => $countAlpha,
                'hari_efektif'          => $hariEfektifTotal,
                'hari_efektif_berjalan' => $hariEfektifBerjalan,
                'persen'                => min(100.0, $persen),
            ];
        }

        // Rekapitulasi per kelas/rombel untuk mode ringkasan
        $rekap = $items->groupBy(fn ($item) => $item->nama_rombel ?: 'Tanpa Kelas')
            ->map(function ($group, $namaKelas) {
                return (object) [
                    'kelas'     => $namaKelas,
                    'total'     => $group->count(),
                    'hadir'     => $group->where('status', 'H')->count(),
                    'terlambat' => $group->where('status', 'T')->count(),
                    'izin'      => $group->where('status', 'I')->count(),
                    'sakit'     => $group->where('status', 'S')->count(),
                    'dispen'    => $group->where('status', 'D')->count(),
                    'alpha'     => $group->where('status', 'A')->count(),
                ];
            })
            ->sortKeys()
            ->values();

        $statusLabels = [
            'H' => 'Hadir Tepat Waktu',
            'T' => 'Terlambat',
            'I' => 'Izin',
            'S' => 'Sakit',
            'D' => 'Dispensasi',
            'A' => 'Alpha',
        ];

        $filterLabel = [];
        $filterLabel[] = 'Periode: ' . $range['label'];
        if ($rombelObj) {
            $filterLabel[] = 'Kelas: ' . $rombelObj->nama;
        }
        if ($status !== '' && isset($statusLabels[$status])) {
            $filterLabel[] = 'Status: ' . $statusLabels[$status];
        }
        if ($q !== '') {
            $filterLabel[] = 'Pencarian: "' . $q . '"';
        }

        $tanggalFormat = $range['label'];

        return view('dashboard.laboran.log-presensi-cetak', array_merge(
            compact(
                'tipeLaporan', 'periode', 'range', 'tanggal', 'tanggalFormat', 'items', 'rekap', 'stats',
                'filterLabel', 'rombelList', 'rombelId', 'rombelObj', 'status',
                'bulan', 'tahun', 'semester', 'tahunAjaran'
            ),
            [
                'backUrl' => route('dashboard.laboran.log-presensi', $request->only(['tanggal', 'rombel_id', 'status', 'q'])),
            ],
            $this->dokumenCetak('log-presensi', $context, [
                'judul'        => $rombelObj ? 'Laporan Presensi Kelas ' . $rombelObj->nama : 'Laporan Presensi Peserta Didik',
                'mode'         => $mode,
                'orientasi'    => $orientasi,
                'jumlah_baris' => $items->count(),
                'filter'       => [
                    'tipe_laporan' => 'kelas',
                    'periode'      => $periode,
                    'range'        => $range,
                    'rombel_id'    => $rombelId,
                    'status'       => $status,
                    'q'            => $q,
                ],
            ])
        ));
    }

    /**
     * Hitung rentang tanggal awal & akhir berdasarkan parameter periode.
     *
     * @return array{start:string,end:string,label:string}
     */
    protected function resolveDateRange(string $periode, string $tanggal, int $bulan, int $tahun, string $semester, string $tahunAjaran): array
    {
        if ($periode === 'hari') {
            return [
                'start' => $tanggal,
                'end'   => $tanggal,
                'label' => \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'),
            ];
        }

        if ($periode === 'semester') {
            $parts = explode('/', $tahunAjaran ?: date('Y') . '/' . (date('Y') + 1));
            $y1 = (int) ($parts[0] ?? date('Y'));
            $y2 = (int) ($parts[1] ?? ($y1 + 1));

            if ($semester === '1') {
                return [
                    'start' => "{$y1}-07-01",
                    'end'   => "{$y1}-12-31",
                    'label' => "Semester Ganjil {$y1}/{$y2} (Juli - Desember {$y1})",
                ];
            }

            return [
                'start' => "{$y2}-01-01",
                'end'   => "{$y2}-06-30",
                'label' => "Semester Genap {$y1}/{$y2} (Januari - Juni {$y2})",
            ];
        }

        if ($periode === 'tahun') {
            if (str_contains($tahunAjaran, '/')) {
                $parts = explode('/', $tahunAjaran);
                $y1 = (int) $parts[0];
                $y2 = (int) ($parts[1] ?? ($y1 + 1));

                return [
                    'start' => "{$y1}-07-01",
                    'end'   => "{$y2}-06-30",
                    'label' => "Tahun Ajaran {$y1}/{$y2} (Juli {$y1} - Juni {$y2})",
                ];
            }

            $y = $tahun ?: (int) date('Y');
            return [
                'start' => "{$y}-01-01",
                'end'   => "{$y}-12-31",
                'label' => "Tahun Kalender {$y}",
            ];
        }

        // Mode Bulan (Default)
        $b = str_pad((string) ($bulan ?: date('n')), 2, '0', STR_PAD_LEFT);
        $y = (int) ($tahun ?: date('Y'));
        $startCarbon = \Carbon\Carbon::createFromDate($y, (int) $b, 1)->startOfMonth();
        $endCarbon   = \Carbon\Carbon::createFromDate($y, (int) $b, 1)->endOfMonth();

        return [
            'start' => $startCarbon->toDateString(),
            'end'   => $endCarbon->toDateString(),
            'label' => 'Bulan ' . $startCarbon->translatedFormat('F Y'),
        ];
    }

    // =========================================================================
    // QUERY
    // =========================================================================

    /**
     * Basis query log presensi yang sudah dibatasi jurusan Laboran.
     *
     * @param  array  $context
     * @return \Illuminate\Database\Query\Builder
     */
    private function logBase(array $context)
    {
        $query = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

        LaboranScope::apply($query, 'rb.jurusan_id', $context);

        return $query;
    }

    private function logQuery(array $context, string $tanggal, string $rombelId, string $status, string $q)
    {
        $query = $this->logBase($context)
            ->where('ph.tanggal', $tanggal)
            ->select(
                'ph.*',
                'pd.nama as nama_siswa',
                'pd.nisn as nisn_siswa',
                'pd.jenis_kelamin',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            );

        if ($rombelId !== '') {
            $query->where('ph.rombongan_belajar_id', $rombelId);
        }

        if ($status !== '') {
            $query->where('ph.status', $status);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'nama_siswa'  => 'pd.nama',
            'nama_rombel' => 'rb.nama',
            'jam_pulang'  => 'ph.jam_pulang',
            'status'      => 'ph.status',
            default       => 'ph.jam_masuk',
        };
    }

    /**
     * Ringkasan presensi pada tanggal terpilih, dibatasi jurusan.
     *
     * @return array{total:int,hadir:int,terlambat:int,izin:int,sakit:int,dispen:int,alpha:int,belum:int,persen:float}
     */
    private function stats(array $context, string $tanggal): array
    {
        $siswaQuery = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id');

        LaboranScope::apply($siswaQuery, 'rb.jurusan_id', $context);
        $total = (int) $siswaQuery->count('pd.peserta_didik_id');

        $byStatus = $this->logBase($context)
            ->where('ph.tanggal', $tanggal)
            ->select('ph.status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('ph.status')
            ->pluck('jumlah', 'status');

        $count = fn (string $key) => (int) ($byStatus[$key] ?? 0);

        $hadir      = $count('H');
        $terlambat  = $count('T');
        $izin       = $count('I');
        $sakit      = $count('S');
        $dispen     = $count('D');
        $alpha      = $count('A');
        $tercatat   = $hadir + $terlambat + $izin + $sakit + $dispen + $alpha;

        return [
            'total'     => $total,
            'hadir'     => $hadir,
            'terlambat' => $terlambat,
            'izin'      => $izin,
            'sakit'     => $sakit,
            'dispen'    => $dispen,
            'alpha'     => $alpha,
            'belum'     => max(0, $total - $tercatat),
            'persen'    => $total > 0 ? round((($hadir + $terlambat) / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Daftar rombel (kelas reguler) pada jurusan Laboran.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rombelOptions(array $context)
    {
        $query = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->select('rombongan_belajar_id', 'nama')
            ->orderBy('tingkat_pendidikan_id')
            ->orderBy('nama');

        LaboranScope::apply($query, 'jurusan_id', $context);

        return $query->get();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function resolveTanggal(Request $request): string
    {
        $raw = trim((string) $request->query('tanggal', ''));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            [$y, $m, $d] = array_map('intval', explode('-', $raw));
            if (checkdate($m, $d, $y)) {
                return $raw;
            }
        }

        return now()->toDateString();
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('perPage', 15);

        return in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;
    }

    private function fotoUrl(?string $path): ?string
    {
        return !empty($path) ? asset('storage/' . ltrim($path, '/')) : null;
    }
}
