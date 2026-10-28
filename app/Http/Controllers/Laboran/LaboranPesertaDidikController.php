<?php

namespace App\Http\Controllers\Laboran;

use App\Services\PesertaDidikQueryService;
use App\Services\SimpleXlsxExporter;
use App\Support\LaboranScope;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data Peserta Didik — modul baca-saja untuk Laboran.
 *
 * Menampilkan direktori peserta didik yang DIBATASI pada jurusan tempat
 * Laboran ditugaskan (`ptk_tugas_tambahan.jurusan_id`). Cakupan dilakukan
 * lewat relasi `peserta_didik.rombongan_belajar_id` →
 * `rombongan_belajar.jurusan_id`, karena tabel peserta didik tidak menyimpan
 * `jurusan_id` secara langsung.
 *
 * Dua tab tersedia:
 *  - `aktif`        → tabel `peserta_didik`
 *  - `tidak_aktif`  → tabel `peserta_didik_tidak_aktif` (arsip keluar/alumni)
 *
 * Seluruh aksi CRUD/ubah data SENGAJA tidak disediakan. Satu-satunya aksi
 * yang diizinkan adalah melihat detail biodata peserta didik, serta ekspor
 * Excel (.xlsx) dengan filter per kelas (rombel).
 */
class LaboranPesertaDidikController extends LaboranBaseController
{
    /** Tab yang didukung halaman. */
    public const TABS = ['aktif', 'tidak_aktif'];

    /** Kolom yang boleh dipakai sebagai kunci pengurutan pada tab aktif. */
    public const SORTABLE_AKTIF = [
        'nama',
        'nisn',
        'nipd',
        'jenis_kelamin',
        'nama_rombel',
        'tingkat_pendidikan_id',
        'tempat_lahir',
        'tanggal_lahir',
    ];

    /** Kolom yang boleh dipakai sebagai kunci pengurutan pada tab tidak aktif. */
    public const SORTABLE_TIDAK_AKTIF = [
        'nama',
        'nisn',
        'nipd',
        'jenis_kelamin',
        'nama_rombel_terakhir',
        'tingkat_pendidikan_terakhir',
        'tahun_lulus',
        'status_keluar',
        'tanggal_keluar',
    ];

    public function __construct(protected PesertaDidikQueryService $queryService)
    {
    }

    // =========================================================================
    // HALAMAN UTAMA
    // =========================================================================

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        $tab = $this->resolveTab($request);

        $q       = trim((string) $request->query('q', ''));
        $rombel  = trim((string) $request->query('rombel', ''));
        $gender  = strtoupper(trim((string) $request->query('gender', '')));
        $perPage = $this->resolvePerPage($request);

        if (!in_array($gender, ['L', 'P'], true)) {
            $gender = '';
        }

        $sortable = $tab === 'aktif' ? self::SORTABLE_AKTIF : self::SORTABLE_TIDAK_AKTIF;
        $sort     = in_array($request->query('sort'), $sortable, true) ? (string) $request->query('sort') : 'nama';
        $sortDir  = $request->query('sort_dir') === 'desc' ? 'desc' : 'asc';

        // --- Hitungan lintas tab (selalu mengikuti cakupan jurusan) ---------
        $aktifBase      = $this->aktifBase($context);
        $tidakAktifBase = $this->tidakAktifBase($context);

        $aktifCount      = $aktifBase ? (clone $aktifBase)->count('pd.peserta_didik_id') : 0;
        $tidakAktifCount = $tidakAktifBase ? (clone $tidakAktifBase)->count('pta.id') : 0;

        // --- Ringkasan kartu statistik sesuai tab aktif ---------------------
        $stats = $tab === 'aktif'
            ? $this->statsAktif($aktifBase)
            : $this->statsTidakAktif($tidakAktifBase);

        // --- Daftar pilihan kelas (rombel) untuk filter & ekspor ------------
        $filterRombel = $tab === 'aktif'
            ? $this->rombelOptionsAktif($aktifBase)
            : $this->rombelOptionsTidakAktif($tidakAktifBase);

        // --- Data tabel -----------------------------------------------------
        [$list, $totalFiltered] = $tab === 'aktif'
            ? $this->paginateAktif($request, $aktifBase, $q, $rombel, $gender, $sort, $sortDir, $perPage)
            : $this->paginateTidakAktif($request, $tidakAktifBase, $q, $rombel, $gender, $sort, $sortDir, $perPage);

        return view('dashboard.laboran.peserta-didik', array_merge(
            compact(
                'tab',
                'list',
                'totalFiltered',
                'stats',
                'filterRombel',
                'aktifCount',
                'tidakAktifCount',
                'q',
                'rombel',
                'gender',
                'perPage',
                'sort',
                'sortDir',
                'sortable'
            ),
            $this->abilities(),
            [
                'context'    => $context,
                'scopeBadge' => LaboranScope::badge($context),
            ]
        ));
    }

    // =========================================================================
    // DETAIL BIODATA (satu-satunya aksi yang diizinkan)
    // =========================================================================

    public function show(Request $request, string $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir. Silakan masuk kembali.'], 401);
        }

        $context = LaboranScope::resolve($user);
        $tab     = $this->resolveTab($request);

        if ($tab === 'tidak_aktif') {
            return $this->showTidakAktif($id, $context);
        }

        return $this->showAktif($id, $context);
    }

    private function showAktif(string $id, array $context)
    {
        $row = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nama_rombel', 'rb.jurusan_id', 'rb.jurusan_id_str')
            ->where('pd.peserta_didik_id', $id)
            ->orWhere('pd.nisn', $id)
            ->orWhere('pd.nipd', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data peserta didik tidak ditemukan.',
            ], 404);
        }

        if (!LaboranScope::allows($row->jurusan_id, $context)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data peserta didik berada di luar cakupan jurusan Anda.',
            ], 403);
        }

        $data = $this->queryService->getShowData($row->peserta_didik_id);

        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data peserta didik tidak ditemukan.',
            ], 404);
        }

        // Lengkapi dengan konteks jurusan (tidak tersedia pada peserta_didik).
        $data['tab']          = 'aktif';
        $data['jurusan_id']   = $row->jurusan_id;
        $data['jurusan_nama'] = $row->jurusan_id_str;

        return response()->json($data);
    }

    private function showTidakAktif(string $id, array $context)
    {
        $item = DB::table('peserta_didik_tidak_aktif as pta')
            ->leftJoin('rombongan_belajar as rb', 'pta.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pta.*', 'rb.jurusan_id as rb_jurusan_id', 'rb.jurusan_id_str as rb_jurusan_nama')
            ->where(function ($w) use ($id) {
                $w->where('pta.id', $id)->orWhere('pta.peserta_didik_id', $id);
            })
            ->first();

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data arsip peserta didik tidak ditemukan.',
            ], 404);
        }

        if (!LaboranScope::allows($item->rb_jurusan_id ?? null, $context)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data peserta didik berada di luar cakupan jurusan Anda.',
            ], 403);
        }

        $item->foto_url = !empty($item->foto_path)
            ? asset('storage/' . ltrim($item->foto_path, '/'))
            : null;

        return response()->json([
            'status'          => 'success',
            'tab'             => 'tidak_aktif',
            'data'            => $item,
            'rombel_terakhir' => $item->nama_rombel_terakhir,
            'jurusan'         => $item->rb_jurusan_nama ?? null,
        ]);
    }

    // =========================================================================
    // EKSPOR EXCEL (.xlsx) DENGAN FILTER PER KELAS
    // =========================================================================

    public function exportExcel(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        $tab    = $this->resolveTab($request);
        $rombel = trim((string) $request->query('rombel', ''));
        $q      = trim((string) $request->query('q', ''));
        $gender = strtoupper(trim((string) $request->query('gender', '')));
        if (!in_array($gender, ['L', 'P'], true)) {
            $gender = '';
        }

        $rows    = [];
        $headers = [];

        if ($tab === 'aktif') {
            $headers = [
                'No', 'Nama Peserta Didik', 'L/P', 'NISN', 'NIPD', 'NIK',
                'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Rombel Kelas',
                'Tingkat', 'Jurusan', 'Nama Ayah', 'Nama Ibu', 'Nama Wali',
                'No. HP', 'E-Mail', 'Alamat Jalan',
            ];

            $query = $this->aktifBase($context);
            if ($query) {
                $query->select(
                    'pd.nama',
                    'pd.jenis_kelamin',
                    'pd.nisn',
                    'pd.nipd',
                    'pd.nik',
                    'pd.tempat_lahir',
                    'pd.tanggal_lahir',
                    'pd.agama_id_str',
                    'pd.nama_rombel',
                    'pd.tingkat_pendidikan_id',
                    'rb.jurusan_id_str',
                    'pd.nama_ayah',
                    'pd.nama_ibu',
                    'pd.nama_wali',
                    'pd.nomor_telepon_seluler',
                    'pd.email',
                    'pd.alamat_jalan'
                );

                $this->applyCommonFilters($query, 'pd', $q, $rombel, $gender, 'nama_rombel');

                $no = 1;
                foreach ($query->orderByRaw('LOWER(TRIM(pd.nama)) asc')->orderBy('pd.nisn')->get() as $r) {
                    $rows[] = [
                        $no++,
                        $r->nama ?: '-',
                        $r->jenis_kelamin ?: '-',
                        $r->nisn ?: '-',
                        $r->nipd ?: '-',
                        $r->nik ?: '-',
                        $r->tempat_lahir ?: '-',
                        $r->tanggal_lahir ?: '-',
                        $r->agama_id_str ?: '-',
                        $r->nama_rombel ?: '-',
                        $r->tingkat_pendidikan_id ?: '-',
                        $r->jurusan_id_str ?: '-',
                        $r->nama_ayah ?: '-',
                        $r->nama_ibu ?: '-',
                        $r->nama_wali ?: '-',
                        $r->nomor_telepon_seluler ?: '-',
                        $r->email ?: '-',
                        $r->alamat_jalan ?: '-',
                    ];
                }
            }

            $judulTab = 'PESERTA DIDIK AKTIF';
        } else {
            $headers = [
                'No', 'Nama Peserta Didik', 'L/P', 'NISN', 'NIPD', 'NIK',
                'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Rombel Terakhir',
                'Tingkat Terakhir', 'Jurusan', 'Status Keluar', 'Tanggal Keluar',
                'Tahun Lulus', 'Alasan Keluar', 'Nama Ayah', 'Nama Ibu',
                'No. HP', 'Alamat Jalan',
            ];

            $query = $this->tidakAktifBase($context);
            if ($query) {
                $query->select(
                    'pta.nama',
                    'pta.jenis_kelamin',
                    'pta.nisn',
                    'pta.nipd',
                    'pta.nik',
                    'pta.tempat_lahir',
                    'pta.tanggal_lahir',
                    'pta.agama_id_str',
                    'pta.nama_rombel_terakhir',
                    'pta.tingkat_pendidikan_terakhir',
                    'rb.jurusan_id_str',
                    'pta.status_keluar',
                    'pta.tanggal_keluar',
                    'pta.tahun_lulus',
                    'pta.alasan_keluar',
                    'pta.nama_ayah',
                    'pta.nama_ibu',
                    'pta.nomor_telepon_seluler',
                    'pta.alamat_jalan'
                );

                $this->applyCommonFilters($query, 'pta', $q, $rombel, $gender, 'nama_rombel_terakhir');

                $no = 1;
                foreach ($query->orderByRaw('LOWER(TRIM(pta.nama)) asc')->orderBy('pta.nisn')->get() as $r) {
                    $rows[] = [
                        $no++,
                        $r->nama ?: '-',
                        $r->jenis_kelamin ?: '-',
                        $r->nisn ?: '-',
                        $r->nipd ?: '-',
                        $r->nik ?: '-',
                        $r->tempat_lahir ?: '-',
                        $r->tanggal_lahir ?: '-',
                        $r->agama_id_str ?: '-',
                        $r->nama_rombel_terakhir ?: '-',
                        $r->tingkat_pendidikan_terakhir ?: '-',
                        $r->jurusan_id_str ?: '-',
                        $r->status_keluar ?: '-',
                        $r->tanggal_keluar ?: '-',
                        $r->tahun_lulus ?: '-',
                        $r->alasan_keluar ?: '-',
                        $r->nama_ayah ?: '-',
                        $r->nama_ibu ?: '-',
                        $r->nomor_telepon_seluler ?: '-',
                        $r->alamat_jalan ?: '-',
                    ];
                }
            }

            $judulTab = 'PESERTA DIDIK TIDAK AKTIF';
        }

        $namaSekolah = Table::exists('sekolah')
            ? (DB::table('sekolah')->value('nama') ?: 'SAE')
            : 'SAE';

        $jurusanLabel = $context['jurusan_nama'] ?: ($context['jurusan_id'] ?: 'Semua Jurusan');
        $userName     = $context['nama'] ?: 'Laboran';

        $meta = [
            'title'    => 'DATA ' . $judulTab,
            'subtitle' => $namaSekolah . ' — ' . $jurusanLabel,
            'fields'   => [
                'Cakupan Jurusan' => $jurusanLabel,
                'Kelas / Rombel'  => $rombel !== '' ? $rombel : 'Semua Kelas',
                'Pencarian'       => $q !== '' ? $q : '-',
                'Jenis Kelamin'   => $gender === 'L' ? 'Laki-Laki' : ($gender === 'P' ? 'Perempuan' : 'Semua'),
                'Jumlah Data'     => count($rows) . ' Peserta Didik',
                'Waktu Ekspor'    => now()->format('d/m/Y H:i:s') . ' WIB',
                'Diekspor Oleh'   => $userName,
            ],
        ];

        $filename = 'Data_' . Str::slug($judulTab, '_') . '_'
            . Str::slug($rombel !== '' ? $rombel : $jurusanLabel, '_') . '_'
            . date('Ymd_His') . '.xlsx';

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempPath = $tempDir . '/' . Str::random(16) . '.xlsx';

        SimpleXlsxExporter::export(
            $tempPath,
            $meta,
            $headers,
            $rows,
            [
                'sheetName'  => $tab === 'aktif' ? 'Peserta Didik Aktif' : 'Peserta Didik Tidak Aktif',
                'headerBg'   => 'FF1E3A8A',
                'centerCols' => [0, 2, 3, 4, 10],
            ]
        );

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // =========================================================================
    // CETAK DOKUMEN A4 (Ringkasan / Lengkap)
    // =========================================================================

    /**
     * Dokumen cetak A4 Data Peserta Didik Jurusan.
     *
     * Mode `ringkasan` → rekapitulasi jumlah peserta didik per kelas/rombel.
     * Mode `lengkap`   → daftar seluruh peserta didik sesuai filter.
     *
     * Hasil render dicatat ke `laboran_log_cetak` + `laboran_arsip` oleh
     * `LaboranDokumenService::dokumen()` sehingga bukti supervisi terekam.
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        $tab    = $this->resolveTab($request);
        $q      = trim((string) $request->query('q', ''));
        $rombel = trim((string) $request->query('rombel', ''));
        $gender = strtoupper(trim((string) $request->query('gender', '')));
        if (!in_array($gender, ['L', 'P'], true)) {
            $gender = '';
        }

        $mode      = $this->modeCetak($request, 'ringkasan');
        $orientasi = $this->orientasiCetak($request, 'portrait');

        $aktif = $tab === 'aktif';
        $base  = $aktif ? $this->aktifBase($context) : $this->tidakAktifBase($context);

        $items = collect();
        if ($base) {
            $alias        = $aktif ? 'pd' : 'pta';
            $rombelColumn = $aktif ? 'nama_rombel' : 'nama_rombel_terakhir';

            $query = (clone $base)->select(
                "{$alias}.nama",
                "{$alias}.nisn",
                "{$alias}.nipd",
                "{$alias}.jenis_kelamin",
                "{$alias}.tempat_lahir",
                "{$alias}.tanggal_lahir",
                "{$alias}.agama_id_str",
                "{$alias}.{$rombelColumn} as nama_rombel_terakhir",
                "{$alias}.nomor_telepon_seluler",
                "{$alias}.alamat_jalan",
                'rb.jurusan_id_str'
            );

            if (!$aktif) {
                $query->addSelect([
                    'pta.status_keluar',
                    'pta.tahun_lulus',
                    'pta.tanggal_keluar',
                ]);
            }

            $this->applyCommonFilters($query, $alias, $q, $rombel, $gender, $rombelColumn);

            $items = $query->orderByRaw("LOWER(TRIM({$alias}.nama)) asc")
                ->orderBy("{$alias}.nisn")
                ->get();
        }

        $labelTab = $aktif ? 'Peserta Didik Aktif' : 'Peserta Didik Tidak Aktif';

        // Rekap per rombel untuk mode ringkasan.
        $rekap = $items->groupBy(fn ($r) => $r->nama_rombel_terakhir ?: '—')
            ->map(fn ($g, $kelas) => (object) [
                'kelas'     => $kelas,
                'total'     => $g->count(),
                'laki'      => $g->where('jenis_kelamin', 'L')->count(),
                'perempuan' => $g->where('jenis_kelamin', 'P')->count(),
            ])
            ->sortKeys()
            ->values();

        $filterLabel = [];
        if ($rombel !== '') {
            $filterLabel[] = 'Kelas: ' . $rombel;
        }
        if ($gender !== '') {
            $filterLabel[] = 'Jenis Kelamin: ' . ($gender === 'L' ? 'Laki-Laki' : 'Perempuan');
        }
        if ($q !== '') {
            $filterLabel[] = 'Pencarian: "' . $q . '"';
        }

        return view('dashboard.laboran.peserta-didik-cetak', array_merge(
            [
                'tabLabel'    => $labelTab,
                'items'       => $items,
                'rekap'       => $rekap,
                'filterLabel' => $filterLabel,
                'backUrl'     => route('dashboard.laboran.peserta-didik.index', $request->only(['tab'])),
            ],
            $this->dokumenCetak('peserta-didik', $context, [
                'judul'        => 'Data ' . $labelTab . ' Jurusan',
                'mode'         => $mode,
                'orientasi'    => $orientasi,
                'jumlah_baris' => $items->count(),
                'filter'       => [
                    'tab'    => $tab,
                    'q'      => $q,
                    'rombel' => $rombel,
                    'gender' => $gender,
                ],
            ])
        ));
    }

    // =========================================================================
    // QUERY BUILDER
    // =========================================================================

    /**
     * Basis query peserta didik AKTIF dalam cakupan jurusan.
     */
    private function aktifBase(array $context)
    {
        if (!Table::exists('peserta_didik') || !Table::exists('rombongan_belajar')) {
            return null;
        }

        $query = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id');

        LaboranScope::apply($query, 'rb.jurusan_id', $context);

        return $query;
    }

    /**
     * Basis query peserta didik TIDAK AKTIF dalam cakupan jurusan.
     *
     * Peserta didik yang saat ini masih tercatat aktif (ada di tabel
     * `peserta_didik`) dikecualikan agar tidak muncul ganda.
     */
    private function tidakAktifBase(array $context)
    {
        if (!Table::exists('peserta_didik_tidak_aktif')) {
            return null;
        }

        $query = DB::table('peserta_didik_tidak_aktif as pta')
            ->leftJoin('rombongan_belajar as rb', 'pta.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id');

        if (Table::exists('peserta_didik')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('peserta_didik as pdx')
                    ->whereColumn('pdx.peserta_didik_id', 'pta.peserta_didik_id');
            });
        }

        LaboranScope::apply($query, 'rb.jurusan_id', $context);

        return $query;
    }

    /**
     * Terapkan pencarian & filter rombel/gender yang sama untuk index maupun ekspor.
     */
    private function applyCommonFilters($query, string $alias, string $q, string $rombel, string $gender, string $rombelColumn): void
    {
        if ($q !== '') {
            $query->where(function ($sub) use ($q, $alias, $rombelColumn) {
                $sub->where("{$alias}.nama", 'LIKE', "%{$q}%")
                    ->orWhere("{$alias}.nisn", 'LIKE', "%{$q}%")
                    ->orWhere("{$alias}.nipd", 'LIKE', "%{$q}%")
                    ->orWhere("{$alias}.nik", 'LIKE', "%{$q}%")
                    ->orWhere("{$alias}.{$rombelColumn}", 'LIKE', "%{$q}%");
            });
        }

        if ($rombel !== '') {
            $query->where("{$alias}.{$rombelColumn}", $rombel);
        }

        if ($gender !== '') {
            $query->where("{$alias}.jenis_kelamin", $gender);
        }
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: int}
     */
    private function paginateAktif(
        Request $request,
        $base,
        string $q,
        string $rombel,
        string $gender,
        string $sort,
        string $sortDir,
        int $perPage
    ): array {
        if (!$base) {
            return [$this->emptyPaginator($request, $perPage), 0];
        }

        $query = (clone $base)->select(
            'pd.peserta_didik_id',
            'pd.nama',
            'pd.nisn',
            'pd.nipd',
            'pd.nik',
            'pd.jenis_kelamin',
            'pd.tempat_lahir',
            'pd.tanggal_lahir',
            'pd.agama_id_str',
            'pd.nama_rombel',
            'pd.rombongan_belajar_id',
            'pd.tingkat_pendidikan_id',
            'pd.kurikulum_id_str',
            'pd.nama_ayah',
            'pd.nama_ibu',
            'pd.nama_wali',
            'pd.pekerjaan_ayah_id_str',
            'pd.pekerjaan_ibu_id_str',
            'pd.pekerjaan_wali_id_str',
            'pd.kebutuhan_khusus',
            'pd.anak_keberapa',
            'pd.tinggi_badan',
            'pd.berat_badan',
            'pd.sekolah_asal',
            'pd.tanggal_masuk_sekolah',
            'pd.jenis_pendaftaran_id_str',
            'pd.registrasi_id',
            'pd.anggota_rombel_id',
            'pd.email',
            'pd.alamat_jalan',
            'pd.nomor_telepon_seluler',
            'rb.jurusan_id',
            'rb.jurusan_id_str'
        );

        $this->applyCommonFilters($query, 'pd', $q, $rombel, $gender, 'nama_rombel');

        $total = (clone $query)->count('pd.peserta_didik_id');

        $this->applyOrder($query, 'pd', $sort, $sortDir, 'nama_rombel');

        $currentPage = max(1, (int) $request->query('page', 1));
        $items = $query->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

        // Pasfoto dari tabel meta
        $pdIds = $items->pluck('peserta_didik_id')->filter()->values()->all();
        if (!empty($pdIds) && Table::exists('peserta_didik_meta')) {
            $metas = DB::table('peserta_didik_meta')
                ->whereIn('peserta_didik_id', $pdIds)
                ->get()
                ->keyBy('peserta_didik_id');

            foreach ($items as $item) {
                $meta = $metas[$item->peserta_didik_id] ?? null;
                $item->foto_url = !empty($meta?->foto_path)
                    ? asset('storage/' . ltrim($meta->foto_path, '/'))
                    : null;
            }
        } else {
            foreach ($items as $item) {
                $item->foto_url = null;
            }
        }

        $list = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [$list, $total];
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: int}
     */
    private function paginateTidakAktif(
        Request $request,
        $base,
        string $q,
        string $rombel,
        string $gender,
        string $sort,
        string $sortDir,
        int $perPage
    ): array {
        if (!$base) {
            return [$this->emptyPaginator($request, $perPage), 0];
        }

        $query = (clone $base)->select(
            'pta.id',
            'pta.peserta_didik_id',
            'pta.nama',
            'pta.nisn',
            'pta.nipd',
            'pta.nik',
            'pta.jenis_kelamin',
            'pta.tempat_lahir',
            'pta.tanggal_lahir',
            'pta.agama_id_str',
            'pta.nama_rombel_terakhir',
            'pta.rombongan_belajar_id',
            'pta.tingkat_pendidikan_terakhir',
            'pta.kurikulum_id_str',
            'pta.tahun_lulus',
            'pta.tanggal_keluar',
            'pta.status_keluar',
            'pta.alasan_keluar',
            'pta.foto_path',
            'pta.nama_ayah',
            'pta.nama_ibu',
            'pta.nama_wali',
            'pta.pekerjaan_ayah',
            'pta.pekerjaan_ibu',
            'pta.pekerjaan_wali',
            'pta.kebutuhan_khusus',
            'pta.email',
            'pta.alamat_jalan',
            'pta.rt',
            'pta.rw',
            'pta.dusun',
            'pta.kelurahan',
            'pta.kecamatan',
            'pta.kode_pos',
            'pta.jenis_tinggal',
            'pta.alat_transportasi',
            'pta.skhun',
            'pta.penerima_kps',
            'pta.no_kps',
            'pta.nomor_telepon_rumah',
            'pta.nomor_telepon_seluler',
            'rb.jurusan_id',
            'rb.jurusan_id_str'
        );

        $this->applyCommonFilters($query, 'pta', $q, $rombel, $gender, 'nama_rombel_terakhir');

        $total = (clone $query)->count('pta.id');

        $this->applyOrder($query, 'pta', $sort, $sortDir, 'nama_rombel_terakhir');

        $currentPage = max(1, (int) $request->query('page', 1));
        $items = $query->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

        foreach ($items as $item) {
            $item->foto_url = !empty($item->foto_path)
                ? asset('storage/' . ltrim($item->foto_path, '/'))
                : null;
        }

        $list = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [$list, $total];
    }

    private function applyOrder($query, string $alias, string $sort, string $sortDir, string $rombelColumn): void
    {
        if ($sort === 'nama') {
            $query->orderByRaw("LOWER(TRIM({$alias}.nama)) {$sortDir}")->orderBy("{$alias}.nisn", 'asc');
            return;
        }

        if ($sort === 'nama_rombel_terakhir') {
            $query->orderBy("{$alias}.{$rombelColumn}", $sortDir);
            $query->orderByRaw("LOWER(TRIM({$alias}.nama)) asc");
            return;
        }

        $query->orderBy("{$alias}.{$sort}", $sortDir)->orderByRaw("LOWER(TRIM({$alias}.nama)) asc");
    }

    // =========================================================================
    // STATISTIK & OPSI FILTER
    // =========================================================================

    private function statsAktif($base): array
    {
        if (!$base) {
            return ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'rombel' => 0];
        }

        return [
            'total'     => (clone $base)->count('pd.peserta_didik_id'),
            'laki'      => (clone $base)->where('pd.jenis_kelamin', 'L')->count('pd.peserta_didik_id'),
            'perempuan' => (clone $base)->where('pd.jenis_kelamin', 'P')->count('pd.peserta_didik_id'),
            'rombel'    => (clone $base)->distinct()->count('pd.nama_rombel'),
        ];
    }

    private function statsTidakAktif($base): array
    {
        if (!$base) {
            return ['total' => 0, 'alumni' => 0, 'mutasi' => 0, 'rombel' => 0];
        }

        return [
            'total'   => (clone $base)->count('pta.id'),
            'alumni'  => (clone $base)->where('pta.status_keluar', 'Alumni')->count('pta.id'),
            'mutasi'  => (clone $base)->where(function ($w) {
                $w->where('pta.status_keluar', '<>', 'Alumni')->orWhereNull('pta.status_keluar');
            })->count('pta.id'),
            'rombel'  => (clone $base)->distinct()->count('pta.nama_rombel_terakhir'),
        ];
    }

    private function rombelOptionsAktif($base)
    {
        if (!$base) {
            return collect();
        }

        return (clone $base)
            ->whereNotNull('pd.nama_rombel')
            ->where('pd.nama_rombel', '<>', '')
            ->distinct()
            ->orderBy('pd.nama_rombel')
            ->pluck('pd.nama_rombel')
            ->values();
    }

    private function rombelOptionsTidakAktif($base)
    {
        if (!$base) {
            return collect();
        }

        return (clone $base)
            ->whereNotNull('pta.nama_rombel_terakhir')
            ->where('pta.nama_rombel_terakhir', '<>', '')
            ->distinct()
            ->orderBy('pta.nama_rombel_terakhir')
            ->pluck('pta.nama_rombel_terakhir')
            ->values();
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function resolveTab(Request $request): string
    {
        $tab = (string) $request->query('tab', 'aktif');

        return in_array($tab, self::TABS, true) ? $tab : 'aktif';
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));

        return in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;
    }

    private function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            max(1, (int) $request->query('page', 1)),
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
