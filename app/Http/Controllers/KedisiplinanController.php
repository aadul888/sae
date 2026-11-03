<?php

namespace App\Http\Controllers;

use App\Models\KedisiplinanPelanggaran;
use App\Models\KedisiplinanPembinaan;
use App\Models\KedisiplinanPemanggilanWali;
use App\Models\KedisiplinanTataTertib;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\ImageOptimizerService;
use App\Services\QrCodeService;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;

class KedisiplinanController extends Controller
{
    use ChecksAuthenticatedSession;

    
    /**
     * Tampilkan Halaman Kluster Kedisiplinan Siswa (7 Tab).
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'create') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'create') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'read') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'read') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'update') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'update') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_kesiswaan_kedisiplinan', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_kesiswaan', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_poin', 'delete');

        $activeTab = $request->get('tab', 'rekap'); // rekap, tata_tertib, poin, pembinaan, pemanggilan, tindak_lanjut, riwayat
        $q = trim($request->get('q', ''));
        $kategori = trim($request->get('kategori', ''));
        $tingkat = trim($request->get('tingkat', ''));
        $status = trim($request->get('status', ''));

        // Batas Waktu SOP Kedisiplinan: 7x24 Jam (Sanggah) & 2x24 Jam (Kehadiran Wali)
        $tglBatasSanggah7Hari = now()->subDays(7)->toDateString();
        $tglBatasHadir2Hari   = now()->subDays(2)->toDateString();

        // 1. Statistik Ringkas Kedisiplinan
        $totalAturan = KedisiplinanTataTertib::where('is_active', true)->count();
        $totalRingan = KedisiplinanTataTertib::where('tingkat', 'ringan')->count();
        $totalSedang = KedisiplinanTataTertib::where('tingkat', 'sedang')->count();
        $totalBerat  = KedisiplinanTataTertib::where('tingkat', 'berat')->count();
        $totalPelanggaran = KedisiplinanPelanggaran::count();
        $totalPembinaan = KedisiplinanPembinaan::count();
        $totalPanggilan = KedisiplinanPemanggilanWali::count();
        $totalDisanggah = KedisiplinanPelanggaran::where('sanggah_status', 'selesai')->count();
        $totalPoinDisanggah = (int) KedisiplinanPelanggaran::where('sanggah_status', 'selesai')->sum('sanggah_poin_potong');

        // Kasus Menunggu / Dalam Proses
        $totalPending = KedisiplinanPelanggaran::whereIn('status_tindak_lanjut', ['pending', 'proses'])
            ->where(function ($s) {
                $s->whereNull('sanggah_status')->orWhere('sanggah_status', 'proses');
            })->count();

        // Kasus Eskalasi SOP:
        // A. Siswa tidak menyanggah > 7 hari (7x24 jam)
        $totalLewatSanggah = KedisiplinanPelanggaran::where('status_tindak_lanjut', 'pending')
            ->whereNull('sanggah_status')
            ->whereDate('tanggal_kejadian', '<=', $tglBatasSanggah7Hari)
            ->count();

        // B. Wali murid tidak hadir setelah 2 hari (2x24 jam)
        $totalWaliAbsen = KedisiplinanPemanggilanWali::whereIn('status', ['diterbitkan', 'belum_hadir'])
            ->whereDate('tanggal_hadir', '<=', $tglBatasHadir2Hari)
            ->count();

        $stats = [
            'total_aturan'         => $totalAturan,
            'total_ringan'         => $totalRingan,
            'total_sedang'         => $totalSedang,
            'total_berat'          => $totalBerat,
            'total_pelanggaran'    => $totalPelanggaran,
            'total_pembinaan'      => $totalPembinaan,
            'total_panggilan'      => $totalPanggilan,
            'total_pending'        => $totalPending,
            'total_disanggah'      => $totalDisanggah,
            'total_poin_sanggah'   => $totalPoinDisanggah,
            'total_lewat_sanggah'  => $totalLewatSanggah,
            'total_wali_absen'     => $totalWaliAbsen,
            'total_tindak_lanjut'  => $totalLewatSanggah + $totalWaliAbsen,
        ];

        // 2. Tab: Tata Tertib
        $tataTertibQuery = KedisiplinanTataTertib::orderByRaw("FIELD(tingkat, 'ringan', 'sedang', 'berat')")
            ->orderBy('kategori')
            ->orderBy('kode');
        if ($q !== '' && $activeTab === 'tata_tertib') {
            $tataTertibQuery->where(function ($w) use ($q) {
                $w->where('nama_aturan', 'like', "%{$q}%")
                  ->orWhere('kode', 'like', "%{$q}%");
            });
        }
        if ($kategori !== '' && $activeTab === 'tata_tertib') {
            $tataTertibQuery->where('kategori', $kategori);
        }
        if ($tingkat !== '' && $activeTab === 'tata_tertib') {
            $tataTertibQuery->where('tingkat', $tingkat);
        }
        $tataTertibList = $tataTertibQuery->get();

        $perPageVal = $request->input('perPage', $request->input('per_page', 25));
        $perPage = in_array((int)$perPageVal, [10, 15, 25, 50, 100], true) ? (int)$perPageVal : 25;

        // 3. Tab: Poin & Sanggah Siswa Aktif (Hanya yang berstatus Pending / Proses)
        $poinQuery = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where(function ($w) {
                $w->whereIn('status_tindak_lanjut', ['pending', 'proses'])
                  ->where(function ($sub) {
                      $sub->whereNull('sanggah_status')->orWhere('sanggah_status', 'proses');
                  });
            })
            ->orderBy('tanggal_kejadian', 'desc');

        if ($q !== '' && $activeTab === 'poin') {
            $poinQuery->where(function ($w) use ($q) {
                $w->whereHas('siswa', function ($b) use ($q) {
                    $b->where('nama', 'like', "%{$q}%")
                      ->orWhere('nisn', 'like', "%{$q}%");
                })->orWhere('keterangan', 'like', "%{$q}%");
            });
        }
        if ($status !== '' && $activeTab === 'poin') {
            $poinQuery->where('status_tindak_lanjut', $status);
        }
        $poinList = $poinQuery->paginate($perPage, ['*'], 'poin_page')->withQueryString();

        // 4. Tab: Riwayat Pelanggaran (Data yang telah Berhasil Disanggah / Selesai)
        $riwayatQuery = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where(function ($w) {
                $w->where('status_tindak_lanjut', 'selesai')
                  ->orWhere('sanggah_status', 'selesai');
            })
            ->orderBy('tanggal_kejadian', 'desc');

        if ($q !== '' && $activeTab === 'riwayat') {
            $riwayatQuery->where(function ($w) use ($q) {
                $w->whereHas('siswa', function ($b) use ($q) {
                    $b->where('nama', 'like', "%{$q}%")
                      ->orWhere('nisn', 'like', "%{$q}%");
                })->orWhere('keterangan', 'like', "%{$q}%");
            });
        }
        $riwayatList = $riwayatQuery->paginate($perPage, ['*'], 'riwayat_page')->withQueryString();

        // 5. Tab: Sesi Pembinaan Konseling
        $pembinaanQuery = KedisiplinanPembinaan::with(['siswa', 'pelanggaran.aturan', 'guruBk', 'waliKelas'])
            ->orderBy('tanggal_pembinaan', 'desc');
        if ($q !== '' && $activeTab === 'pembinaan') {
            $pembinaanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%");
            })->orWhere('hasil_pembinaan', 'like', "%{$q}%");
        }
        $pembinaanList = $pembinaanQuery->paginate($perPage, ['*'], 'pembinaan_page')->withQueryString();

        // 6. Tab: Pemanggilan Wali Murid
        $pemanggilanQuery = KedisiplinanPemanggilanWali::with('siswa')
            ->orderBy('tanggal_surat', 'desc');
        if ($q !== '' && $activeTab === 'pemanggilan') {
            $pemanggilanQuery->whereHas('siswa', function ($b) use ($q) {
                $b->where('nama', 'like', "%{$q}%");
            })->orWhere('nomor_surat', 'like', "%{$q}%");
        }
        $pemanggilanList = $pemanggilanQuery->paginate($perPage, ['*'], 'pemanggilan_page')->withQueryString();

        // Antrean Pemanggilan Wali (Pelanggaran belum disanggah > 7 hari yang butuh diterbitkan surat)
        $antreanPemanggilan = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where('status_tindak_lanjut', 'pending')
            ->whereNull('sanggah_status')
            ->whereDate('tanggal_kejadian', '<=', $tglBatasSanggah7Hari)
            ->orderBy('tanggal_kejadian', 'asc')
            ->limit(30)
            ->get();

        // 7. Tab: Tindak Lanjut Terpadu (SOP Eskalasi: Tak Disanggah > 7 Hari & Wali Absen > 2 Hari)
        $kasusLewatSanggah = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where('status_tindak_lanjut', 'pending')
            ->whereNull('sanggah_status')
            ->whereDate('tanggal_kejadian', '<=', $tglBatasSanggah7Hari)
            ->orderBy('tanggal_kejadian', 'asc')
            ->get()
            ->map(function ($item) {
                $item->tipe_eskalasi = 'lewat_sanggah';
                $item->pesan_eskalasi = 'Tidak disanggah > 7x24 jam sejak kejadian. Wajib diterbitkan Surat Pemanggilan Wali.';
                return $item;
            });

        $kasusWaliAbsen = KedisiplinanPemanggilanWali::with('siswa')
            ->whereIn('status', ['diterbitkan', 'belum_hadir'])
            ->whereDate('tanggal_hadir', '<=', $tglBatasHadir2Hari)
            ->orderBy('tanggal_hadir', 'asc')
            ->get()
            ->map(function ($item) {
                $item->tipe_eskalasi = 'wali_absen';
                $item->pesan_eskalasi = 'Wali murid tidak hadir setelah 2x24 jam dari jadwal kehadiran. Butuh Panggilan Ke-2 / Skorsing.';
                return $item;
            });

        $kasusProses = KedisiplinanPelanggaran::with(['siswa', 'aturan'])
            ->where('status_tindak_lanjut', 'proses')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->tipe_eskalasi = 'dalam_proses';
                $item->pesan_eskalasi = 'Sedang proses pembinaan / pengerjaan bakti kompensasi.';
                return $item;
            });

        $tindakLanjutList = [
            'kasus_lewat_sanggah' => $kasusLewatSanggah,
            'kasus_wali_absen'    => $kasusWaliAbsen,
            'kasus_proses'        => $kasusProses,
        ];

        // Daftar Siswa dari Kasus Poin & Sanggah untuk rujukan form Pembinaan & Pemanggilan
        $siswaPoinList = DB::table('kedisiplinan_pelanggaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel_nama',
                'tt.nama_aturan',
                'kp.id as pelanggaran_id',
                'kp.poin',
                'kp.status_tindak_lanjut',
                'kp.sanggah_status',
                'kp.tanggal_kejadian'
            )
            ->orderBy('kp.tanggal_kejadian', 'desc')
            ->get()
            ->unique('peserta_didik_id')
            ->values();

        // 6. Tab: Rekapitulasi Poin Siswa (Total akumulasi poin & kompensasi per siswa)
        $rekapSiswa = DB::table('kedisiplinan_pelanggaran as kp')
            ->join('peserta_didik as pd', 'kp.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel_nama',
                DB::raw('SUM(kp.poin) as total_poin_kotor'),
                DB::raw('SUM(COALESCE(kp.sanggah_poin_potong, 0)) as total_disanggah'),
                DB::raw('SUM(GREATEST(0, kp.poin - COALESCE(kp.sanggah_poin_potong, 0))) as total_poin'),
                DB::raw('COUNT(kp.id) as total_kasus')
            )
            ->groupBy('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'rb.nama')
            ->orderByDesc('total_poin')
            ->limit(100)
            ->get();

        // 7. Data Chart Informatif Rekapitulasi Kedisiplinan
        $chartTren = ['labels' => [], 'data' => []];
        for ($i = 5; $i >= 0; $i--) {
            $dt = now()->subMonths($i);
            $monthKey = $dt->format('Y-m');
            $chartTren['labels'][] = $dt->translatedFormat('M Y');
            $chartTren['data'][] = DB::table('kedisiplinan_pelanggaran')
                ->whereRaw("DATE_FORMAT(tanggal_kejadian, '%Y-%m') = ?", [$monthKey])
                ->count();
        }

        $chartTingkat = [
            'labels' => ['Ringan', 'Sedang', 'Berat'],
            'data'   => [
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.tingkat', 'ringan')->count(),
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.tingkat', 'sedang')->count(),
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.tingkat', 'berat')->count(),
            ],
        ];

        $chartKategori = [
            'labels' => ['Kerapian', 'Kehadiran', 'Perilaku', 'Larangan Berat'],
            'data'   => [
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.kategori', 'kerapian')->count(),
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.kategori', 'kehadiran')->count(),
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.kategori', 'perilaku')->count(),
                DB::table('kedisiplinan_pelanggaran as kp')
                    ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
                    ->where('tt.kategori', 'larangan_berat')->count(),
            ],
        ];

        $totalSelesaiBiasa = DB::table('kedisiplinan_pelanggaran')
            ->where('status_tindak_lanjut', 'selesai')
            ->whereNull('sanggah_status')
            ->count();
        $chartStatus = [
            'labels' => ['Menunggu (Pending)', 'Dalam Proses', 'Selesai Biasa', 'Disanggah / Bakti Sekolah'],
            'data'   => [
                DB::table('kedisiplinan_pelanggaran')->where('status_tindak_lanjut', 'pending')->count(),
                DB::table('kedisiplinan_pelanggaran')->where('status_tindak_lanjut', 'proses')->count(),
                $totalSelesaiBiasa,
                DB::table('kedisiplinan_pelanggaran')->whereNotNull('sanggah_status')->count(),
            ],
        ];

        $topAturan = DB::table('kedisiplinan_pelanggaran as kp')
            ->join('kedisiplinan_tata_tertib as tt', 'kp.tata_tertib_id', '=', 'tt.id')
            ->select('tt.kode', 'tt.nama_aturan', 'tt.tingkat', DB::raw('COUNT(kp.id) as total_pelanggaran'))
            ->groupBy('tt.id', 'tt.kode', 'tt.nama_aturan', 'tt.tingkat')
            ->orderByDesc('total_pelanggaran')
            ->limit(5)
            ->get();

        return view('dashboard.kesiswaan.kedisiplinan', compact(
            'stats',
            'activeTab',
            'q',
            'kategori',
            'tingkat',
            'status',
            'perPage',
            'tataTertibList',
            'poinList',
            'riwayatList',
            'pembinaanList',
            'pemanggilanList',
            'antreanPemanggilan',
            'tindakLanjutList',
            'siswaPoinList',
            'rekapSiswa',
            'chartTren',
            'chartTingkat',
            'chartKategori',
            'chartStatus',
            'topAturan',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Unduh Template Excel untuk Import Tata Tertib.
     */
    public function downloadTemplateTataTertib()
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_tatib_') . '.xlsx';

        $headers = [
            'kode',
            'tingkat',
            'kategori',
            'nama_aturan',
            'bobot_poin',
            'sanksi_rekomendasi',
        ];

        $sampleRows = [
            ['KER01', 'ringan', 'kerapian', 'Tidak memakai seragam lengkap sesuai ketentuan hari', 5, 'Teguran lisan & pembinaan'],
            ['KER02', 'ringan', 'kerapian', 'Rambut gondrong/tidak rapi atau diwarnai bagi siswa putra', 10, 'Teguran & pemotongan mandiri'],
            ['HD01', 'ringan', 'kehadiran', 'Terlambat masuk sekolah lebih dari 15 menit', 5, 'Piket & tugas literasi'],
            ['HD02', 'sedang', 'kehadiran', 'Meninggalkan jam pelajaran atau sekolah tanpa izin (membolos)', 20, 'Pemanggilan konseling BK & orang tua'],
            ['PR01', 'ringan', 'perilaku', 'Menggunakan gawai/ponsel saat KBM tanpa izin guru', 10, 'Penyitaan sementara oleh wali kelas/BK'],
            ['PR02', 'sedang', 'perilaku', 'Membawa/bermain game judi online atau kartu di lingkungan sekolah', 30, 'Penyitaan & surat peringatan'],
            ['LB01', 'berat', 'larangan_berat', 'Merokok atau membawa rokok/vape di lingkungan sekolah', 50, 'Surat peringatan 1 & pemanggilan orang tua'],
            ['LB02', 'berat', 'larangan_berat', 'Terlibat perkelahian, tawuran, atau tindak kekerasan fisik', 100, 'Surat peringatan keras & skorsing'],
        ];

        SimpleXlsxExporter::export($tempPath, [
            'title'    => 'TEMPLATE IMPORT ATURAN TATA TERTIB & POIN SISWA',
            'subtitle' => 'KLASIFIKASI: RINGAN, SEDANG, BERAT — SISTEM ADMINISTRASI ELEKTRONIK (SAE)',
        ], $headers, $sampleRows, [
            'sheetName' => 'Tata Tertib',
            'headerBg'  => '#1E3A8A',
        ]);

        return response()->download($tempPath, 'Template_Import_Tata_Tertib_SAE.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import Data Aturan Tata Tertib dari Excel/CSV.
     */
    public function importTataTertib(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|max:20480',
        ], [
            'file_excel.required' => 'Berkas Excel / CSV wajib diunggah.',
            'file_excel.max'      => 'Ukuran berkas maksimal 20 MB.',
        ]);

        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Format berkas tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.',
            ], 422);
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membaca berkas: ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Berkas kosong atau tidak ada baris data yang terdeteksi.',
            ], 422);
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $namaAturan = $this->ambilCell($row, ['nama_aturan', 'uraian', 'aturan', 'nama_pelanggaran', 'pelanggaran', 'deskripsi']);
            if ($namaAturan === '') {
                $skipped++;
                continue;
            }

            $rawKategori = strtolower($this->ambilCell($row, ['kategori', 'jenis', 'kelompok', 'tipe']));
            if (str_contains($rawKategori, 'rapi')) {
                $kategori = 'kerapian';
            } elseif (str_contains($rawKategori, 'hadir') || str_contains($rawKategori, 'bolos') || str_contains($rawKategori, 'lambat')) {
                $kategori = 'kehadiran';
            } elseif (str_contains($rawKategori, 'berat')) {
                $kategori = 'larangan_berat';
            } else {
                $kategori = 'perilaku';
            }

            $rawKode = $this->ambilCell($row, ['kode', 'kode_aturan', 'kode_tatib', 'no_aturan']);
            if ($rawKode === '') {
                $rawKode = KedisiplinanTataTertib::generateKode($kategori);
            }

            $rawPoin = $this->ambilCell($row, ['bobot_poin', 'poin', 'bobot', 'skor']);
            $bobotPoin = is_numeric($rawPoin) ? max(1, (int)$rawPoin) : 5;

            // Klasifikasi tingkat keparahan: ringan, sedang, berat
            $rawTingkat = strtolower($this->ambilCell($row, ['tingkat', 'tingkat_pelanggaran', 'level', 'derajat', 'keparahan']));
            if (str_contains($rawTingkat, 'berat')) {
                $tingkat = 'berat';
            } elseif (str_contains($rawTingkat, 'sedang')) {
                $tingkat = 'sedang';
            } elseif (str_contains($rawTingkat, 'ringan')) {
                $tingkat = 'ringan';
            } else {
                if ($kategori === 'larangan_berat' || $bobotPoin >= 35) {
                    $tingkat = 'berat';
                } elseif ($bobotPoin >= 15) {
                    $tingkat = 'sedang';
                } else {
                    $tingkat = 'ringan';
                }
            }

            $sanksi = $this->ambilCell($row, ['sanksi_rekomendasi', 'sanksi', 'rekomendasi_sanksi', 'tindakan']);

            $existing = KedisiplinanTataTertib::where('kode', $rawKode)->first();
            if ($existing) {
                $existing->update([
                    'tingkat'            => $tingkat,
                    'kategori'           => $kategori,
                    'nama_aturan'        => $namaAturan,
                    'bobot_poin'         => $bobotPoin,
                    'sanksi_rekomendasi' => $sanksi ?: null,
                    'is_active'          => true,
                ]);
                $updated++;
            } else {
                KedisiplinanTataTertib::create([
                    'kode'               => $rawKode,
                    'tingkat'            => $tingkat,
                    'kategori'           => $kategori,
                    'nama_aturan'        => $namaAturan,
                    'bobot_poin'         => $bobotPoin,
                    'sanksi_rekomendasi' => $sanksi ?: null,
                    'is_active'          => true,
                ]);
                $imported++;
            }
        }

        $total = $imported + $updated;
        if ($total === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada baris tata tertib valid yang berhasil diimpor.',
            ], 422);
        }

        $message = "Import selesai: {$imported} aturan baru ditambahkan, {$updated} aturan diperbarui.";
        if ($skipped > 0) {
            $message .= " ({$skipped} baris kosong dilewati).";
        }

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'count'   => $total,
        ]);
    }

    /**
     * Helper ekstrak nilai sel dari baris impor secara fleksibel.
     */
    private function ambilCell(array $row, array $keys): string
    {
        foreach ($keys as $k) {
            $clean = preg_replace('/_+/', '_', trim(preg_replace('/[^a-z0-9_]/', '_', preg_replace('/\s+/', '_', strtolower($k))), '_'));
            foreach ([$k, $clean] as $key) {
                if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                    return trim((string)$row[$key]);
                }
            }
        }
        return '';
    }

    /**
     * Simpan Aturan Tata Tertib Baru (alias storeTatib).
     */
    public function storeTatib(Request $request)
    {
        return $this->storeTataTertib($request);
    }

    /**
     * Update Aturan Tata Tertib.
     */
    public function updateTatib(Request $request, $id)
    {
        $item = KedisiplinanTataTertib::findOrFail($id);

        $validated = $request->validate([
            'kode'               => 'required|string|max:50|unique:kedisiplinan_tata_tertib,kode,' . $item->id,
            'tingkat'            => 'nullable|string|in:ringan,sedang,berat',
            'kategori'           => 'required|string|in:kerapian,kehadiran,perilaku,larangan_berat',
            'nama_aturan'        => 'required|string|max:255',
            'bobot_poin'         => 'required|integer|min:1',
            'sanksi_rekomendasi' => 'nullable|string|max:255',
        ]);

        if (empty($validated['tingkat'])) {
            $validated['tingkat'] = ($validated['kategori'] === 'larangan_berat' || (int)$validated['bobot_poin'] >= 35)
                ? 'berat'
                : ((int)$validated['bobot_poin'] >= 15 ? 'sedang' : 'ringan');
        }

        $item->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => "Aturan tata tertib {$item->kode} berhasil diperbarui.",
            'data'    => $item,
        ]);
    }

    /**
     * Hapus Aturan Tata Tertib.
     */
    public function destroyTatib($id)
    {
        $item = KedisiplinanTataTertib::findOrFail($id);
        $kode = $item->kode;
        $item->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "Aturan tata tertib {$kode} berhasil dihapus.",
        ]);
    }

    /**
     * Ambil kode aturan berikutnya berdasarkan kategori.
     */
    public function getNextKodeTataTertib(Request $request)
    {
        $kategori = $request->query('kategori');
        $kode = KedisiplinanTataTertib::generateKode($kategori);

        return response()->json([
            'status' => 'success',
            'kode'   => $kode,
        ]);
    }

    /**
     * Simpan Aturan Tata Tertib Baru.
     */
    public function storeTataTertib(Request $request)
    {
        $validated = $request->validate([
            'kode'               => 'nullable|string|max:50|unique:kedisiplinan_tata_tertib,kode',
            'tingkat'            => 'nullable|string|in:ringan,sedang,berat',
            'kategori'           => 'required|string|in:kerapian,kehadiran,perilaku,larangan_berat',
            'nama_aturan'        => 'required|string|max:255',
            'bobot_poin'         => 'required|integer|min:1',
            'sanksi_rekomendasi' => 'nullable|string|max:255',
        ]);

        if (empty($validated['kode'])) {
            $validated['kode'] = KedisiplinanTataTertib::generateKode($validated['kategori'] ?? null);
        }

        if (empty($validated['tingkat'])) {
            $validated['tingkat'] = ($validated['kategori'] === 'larangan_berat' || (int)$validated['bobot_poin'] >= 35)
                ? 'berat'
                : ((int)$validated['bobot_poin'] >= 15 ? 'sedang' : 'ringan');
        }

        $item = KedisiplinanTataTertib::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Aturan tata tertib {$item->kode} ({$item->tingkat}) berhasil ditambahkan.",
            'data' => $item,
        ]);
    }

    /**
     * Simpan Entri Pelanggaran Poin Siswa Baru.
     */
    public function storePoin(Request $request)
    {
        $user = session('user');

        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'tata_tertib_id'   => 'required|exists:kedisiplinan_tata_tertib,id',
            'tanggal_kejadian' => 'required|date',
            'tempat_kejadian'  => 'nullable|string|max:190',
            'keterangan'       => 'nullable|string',
            'foto_bukti'       => 'nullable|image|max:3072',
        ]);

        $aturan = KedisiplinanTataTertib::findOrFail($validated['tata_tertib_id']);
        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        $filePath = null;
        if ($request->hasFile('foto_bukti')) {
            $hasil = app(ImageOptimizerService::class)->optimizeAndSaveImage(
                $request->file('foto_bukti'),
                public_path('uploads/kedisiplinan'),
                'pelanggaran'
            );
            $filePath = '/uploads/kedisiplinan/' . $hasil['filename'];
        }

        $pelaporNama = is_array($user) ? ($user['nama'] ?? 'Guru/Staf') : ($user->nama ?? 'Guru/Staf');
        $pelaporPtk = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $pelanggaran = KedisiplinanPelanggaran::create([
            'peserta_didik_id'     => $siswa->peserta_didik_id,
            'tata_tertib_id'       => $aturan->id,
            'tanggal_kejadian'     => $validated['tanggal_kejadian'],
            'tempat_kejadian'      => $validated['tempat_kejadian'] ?? null,
            'poin'                 => $aturan->bobot_poin,
            'keterangan'           => $validated['keterangan'] ?? null,
            'pelapor_ptk_id'       => $pelaporPtk,
            'pelapor_nama'         => $pelaporNama,
            'foto_bukti'           => $filePath,
            'status_tindak_lanjut' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pelanggaran siswa {$siswa->nama} ({$aturan->bobot_poin} poin) berhasil dicatat.",
            'data' => $pelanggaran,
        ]);
    }

    /**
     * Alias untuk storePoin.
     */
    public function storePelanggaran(Request $request)
    {
        return $this->storePoin($request);
    }

    /**
     * Sanggah / Kompensasi Pelanggaran Poin Siswa (Bakti Sekolah, Tugas Literasi, dll).
     */
    public function storeSanggah(Request $request, $id)
    {
        $item = KedisiplinanPelanggaran::with('aturan')->findOrFail($id);

        $validated = $request->validate([
            'sanggah_jenis'        => 'required|string|max:255',
            'sanggah_keterangan'   => 'required|string|max:1000',
            'sanggah_poin_potong'  => 'nullable|integer|min:0',
            'sanggah_validator'    => 'nullable|string|max:100',
            'sanggah_status_opsi'  => 'nullable|string|in:proses,selesai',
            'sanggah_bukti'        => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        $potong = isset($validated['sanggah_poin_potong']) && $validated['sanggah_poin_potong'] !== ''
            ? min((int) $item->poin, max(0, (int) $validated['sanggah_poin_potong']))
            : (int) $item->poin;

        $user = session('user');
        $validator = !empty($validated['sanggah_validator'])
            ? $validated['sanggah_validator']
            : ($user['nama'] ?? 'Tim Kesiswaan / BK');

        $buktiPath = $item->sanggah_bukti;
        if ($request->hasFile('sanggah_bukti')) {
            $hasil = app(ImageOptimizerService::class)->optimizeAndSaveImage(
                $request->file('sanggah_bukti'),
                public_path('uploads/kedisiplinan/sanggah'),
                'sanggah_' . $item->id
            );
            $buktiPath = 'uploads/kedisiplinan/sanggah/' . $hasil['filename'];
        }

        $statusSanggah = $request->input('sanggah_status_opsi', 'selesai');

        $item->update([
            'status_tindak_lanjut' => $statusSanggah,
            'sanggah_status'       => $statusSanggah,
            'sanggah_jenis'        => $validated['sanggah_jenis'],
            'sanggah_keterangan'   => $validated['sanggah_keterangan'],
            'sanggah_poin_potong'  => $potong,
            'sanggah_validator'    => $validator,
            'sanggah_tanggal'      => now(),
            'sanggah_bukti'        => $buktiPath,
        ]);

        // Otomatis masukkan ke sesi pembinaan konseling
        KedisiplinanPembinaan::create([
            'peserta_didik_id'      => $item->peserta_didik_id,
            'pelanggaran_id'        => $item->id,
            'tanggal_pembinaan'     => now()->toDateString(),
            'guru_bk_ptk_id'        => $user['ptk_id'] ?? null,
            'bentuk_pembinaan'      => 'Sanggah / Kompensasi: ' . $validated['sanggah_jenis'],
            'hasil_pembinaan'       => $validated['sanggah_keterangan'] . ' (Validator: ' . $validator . ')',
            'status'                => $statusSanggah,
        ]);

        $pesan = $statusSanggah === 'selesai'
            ? "Sanggahan/kompensasi berhasil diselesaikan. {$potong} poin telah diputihkan dan data dipindahkan ke Riwayat Pelanggaran."
            : "Sanggahan berhasil diajukan dengan status PROSES. Kasus diteruskan ke Sesi Pembinaan/Kompensasi.";

        return response()->json([
            'status'  => 'success',
            'message' => $pesan,
            'data'    => $item,
        ]);
    }

    /**
     * Verifikasi & Selesaikan Sanggah Poin (Memutihkan Poin dan Pindah ke Riwayat).
     */
    public function verifikasiSanggah(Request $request, $id)
    {
        $item = KedisiplinanPelanggaran::findOrFail($id);
        $item->update([
            'status_tindak_lanjut' => 'selesai',
            'sanggah_status'       => 'selesai',
            'sanggah_tanggal'      => now(),
        ]);

        KedisiplinanPembinaan::where('pelanggaran_id', $item->id)->update([
            'status' => 'selesai',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Sanggahan berhasil diverifikasi dan diselesaikan. Poin resmi diputihkan (-{$item->sanggah_poin_potong} poin) dan dipindahkan ke Riwayat Pelanggaran.",
            'data'    => $item,
        ]);
    }

    /**
     * Hapus Catatan Pelanggaran Siswa.
     */
    public function destroyPelanggaran($id)
    {
        $pelanggaran = KedisiplinanPelanggaran::findOrFail($id);
        $pelanggaran->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Catatan pelanggaran berhasil dihapus.',
        ]);
    }

    /**
     * Update Status Tindak Lanjut.
     */
    public function updateTindakLanjut(Request $request, $id)
    {
        $validated = $request->validate([
            'status_tindak_lanjut' => 'required|string|in:pending,proses,selesai',
        ]);

        $pelanggaran = KedisiplinanPelanggaran::findOrFail($id);
        $pelanggaran->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status tindak lanjut pelanggaran berhasil diperbarui.',
            'data'    => $pelanggaran,
        ]);
    }

    /**
     * Simpan Sesi Pembinaan Siswa (Konseling BK / Wali Kelas).
     */
    public function storePembinaan(Request $request)
    {
        $validated = $request->validate([
            'peserta_didik_id'      => 'required|string',
            'pelanggaran_id'        => 'nullable|exists:kedisiplinan_pelanggaran,id',
            'tanggal_pembinaan'     => 'required|date',
            'guru_bk_ptk_id'        => 'nullable|string',
            'wali_kelas_ptk_id'     => 'nullable|string',
            'bentuk_pembinaan'      => 'required|string|max:190',
            'hasil_pembinaan'       => 'required|string',
            'status'                => 'required|string|in:proses,selesai',
            'surat_perjanjian_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('surat_perjanjian_file')) {
            $hasil = app(ImageOptimizerService::class)->optimizeAndSaveImage(
                $request->file('surat_perjanjian_file'),
                public_path('uploads/kedisiplinan'),
                'perjanjian'
            );
            $filePath = '/uploads/kedisiplinan/' . $hasil['filename'];
        }

        $pembinaan = KedisiplinanPembinaan::create([
            'peserta_didik_id'      => $validated['peserta_didik_id'],
            'pelanggaran_id'        => $validated['pelanggaran_id'] ?? null,
            'tanggal_pembinaan'     => $validated['tanggal_pembinaan'],
            'guru_bk_ptk_id'        => $validated['guru_bk_ptk_id'] ?? null,
            'wali_kelas_ptk_id'     => $validated['wali_kelas_ptk_id'] ?? null,
            'bentuk_pembinaan'      => $validated['bentuk_pembinaan'],
            'hasil_pembinaan'       => $validated['hasil_pembinaan'],
            'status'                => $validated['status'],
            'surat_perjanjian_file' => $filePath,
        ]);

        if (!empty($validated['pelanggaran_id'])) {
            KedisiplinanPelanggaran::where('id', $validated['pelanggaran_id'])
                ->update(['status_tindak_lanjut' => $validated['status'] === 'selesai' ? 'selesai' : 'proses']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan sesi pembinaan siswa berhasil disimpan.',
            'data' => $pembinaan,
        ]);
    }

    /**
     * Terbitkan Surat Pemanggilan Orang Tua / Wali Murid.
     */
    public function storePemanggilanWali(Request $request)
    {
        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'tanggal_surat'    => 'required|date',
            'tanggal_hadir'    => 'required|date',
            'jam_hadir'        => 'required',
            'tempat'           => 'required|string|max:190',
            'alasan'           => 'required|string|max:255',
            'menghadap_ke'     => 'required|string|max:190',
        ]);

        $siswa = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->firstOrFail();

        $count = KedisiplinanPemanggilanWali::whereYear('tanggal_surat', date('Y', strtotime($validated['tanggal_surat'])))->count() + 1;
        $nomorSurat = "421.5/" . str_pad($count, 3, '0', STR_PAD_LEFT) . "/SMK-PGL-ORTU/" . date('Y');
        $docId = 'SAE-PGL-' . strtoupper(substr(md5($siswa->peserta_didik_id . time()), 0, 10));

        $panggilan = KedisiplinanPemanggilanWali::create([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'nomor_surat'      => $nomorSurat,
            'tanggal_surat'    => $validated['tanggal_surat'],
            'tanggal_hadir'    => $validated['tanggal_hadir'],
            'jam_hadir'        => $validated['jam_hadir'],
            'tempat'           => $validated['tempat'],
            'alasan'           => $validated['alasan'],
            'menghadap_ke'     => $validated['menghadap_ke'],
            'status'           => 'diterbitkan',
            'doc_id'           => $docId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Surat pemanggilan orang tua a.n {$siswa->nama} berhasil diterbitkan.",
            'data' => $panggilan,
            'cetak_url' => route('dashboard.kesiswaan.kedisiplinan.panggilan.cetak', $panggilan->id),
        ]);
    }

    /**
     * Cetak Surat Pemanggilan Orang Tua / Wali Format Resmi Ber-Kop Sekolah.
     */
    public function cetakSuratPanggilan(Request $request, $id)
    {
        $panggilan = KedisiplinanPemanggilanWali::with('siswa')->findOrFail($id);
        $siswa = $panggilan->siswa;
        $sekolah = DB::table('sekolah')->first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        $anggotaRombel = DB::table('anggota_rombel as ar')
            ->join('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('ar.peserta_didik_id', $siswa->peserta_didik_id)
            ->select('rb.nama as rombel_nama')
            ->first();

        $rombelNama = $anggotaRombel?->rombel_nama ?: 'Kelas X / XI / XII';

        $kepsek = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                    ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $docId = $panggilan->doc_id ?: ('SAE-PGL-' . strtoupper(substr(md5($siswa->peserta_didik_id . $panggilan->id), 0, 10)));
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 130, 1);

        return view('dashboard.kesiswaan.cetak-panggilan-wali', compact(
            'panggilan',
            'siswa',
            'sekolah',
            'sekolahMeta',
            'rombelNama',
            'kepsek',
            'qrUri',
            'docId'
        ));
    }
}
