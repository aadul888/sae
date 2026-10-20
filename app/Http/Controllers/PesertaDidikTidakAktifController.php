<?php

namespace App\Http\Controllers;

use App\Models\PesertaDidikTidakAktif;
use App\Models\RolePermission;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PesertaDidikTidakAktifController extends Controller
{
    private const SORTABLE = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel_terakhir', 'tingkat_pendidikan_terakhir', 'tahun_lulus', 'status_keluar'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if ($role === 'peserta_didik' || !RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Peserta Didik Tidak Aktif dinonaktifkan.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'create');
        $canRead   = RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'delete');
        $isAdmin   = ($role === 'admin');

        $q       = trim($request->get('q', ''));
        $status  = trim($request->get('status', ''));
        $tahun   = trim($request->get('tahun', ''));
        $perPage = (int) ($request->get('perPage') ?? $request->get('per_page') ?? $request->get('perPageSelect') ?? 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('peserta_didik_tidak_aktif')) {
            return view('dashboard.peserta-didik-tidak-aktif', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['total' => 0, 'alumni' => 0, 'mutasi' => 0, 'berfoto' => 0],
                'filterTahun' => collect(),
                'q' => $q,
                'status' => $status,
                'tahun' => $tahun,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
                'grade12ActiveCount' => 0,
            ]);
        }

        // Jamin kepastian: Siswa yang saat ini aktif di tabel peserta_didik TIDAK BOLEH muncul ganda di tidak aktif
        $activePdIds = Schema::hasTable('peserta_didik')
            ? DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all()
            : [];

        $baseTidakAktif = DB::table('peserta_didik_tidak_aktif');
        if (!empty($activePdIds)) {
            $baseTidakAktif->whereNotIn('peserta_didik_id', $activePdIds);
        }

        // Summary counts
        $totalAll   = (clone $baseTidakAktif)->count();
        $totalAlumni = (clone $baseTidakAktif)->where('status_keluar', 'Alumni')->count();
        $totalMutasi = (clone $baseTidakAktif)->where('status_keluar', '<>', 'Alumni')->count();
        $totalBerfoto = (clone $baseTidakAktif)->whereNotNull('foto_path')->where('foto_path', '<>', '')->count();

        $summary = [
            'total'   => $totalAll,
            'alumni'  => $totalAlumni,
            'mutasi'  => $totalMutasi,
            'berfoto' => $totalBerfoto,
        ];

        // Daftar filter tahun kelulusan
        $filterTahun = (clone $baseTidakAktif)
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '<>', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->sortDesc()
            ->values();

        $grade12ActiveCount = 0;

        $query = clone $baseTidakAktif;

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nisn', 'LIKE', "%{$q}%")
                    ->orWhere('nipd', 'LIKE', "%{$q}%")
                    ->orWhere('nik', 'LIKE', "%{$q}%")
                    ->orWhere('nama_rombel_terakhir', 'LIKE', "%{$q}%");
            });
        }

        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }

        if ($tahun !== '') {
            $query->where('tahun_lulus', $tahun);
        }

        $totalFiltered = $query->count();
        $query->orderBy($sort, $sortDir);

        $currentPage = (int) $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $items = $query->skip($offset)->take($perPage)->get();

        foreach ($items as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
        }

        $list = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $totalFiltered,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.peserta-didik-tidak-aktif', compact(
            'list',
            'summary',
            'filterTahun',
            'q',
            'status',
            'tahun',
            'perPage',
            'sort',
            'sortDir',
            'grade12ActiveCount',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'isAdmin'
        ));
    }

    /**
     * Detail siswa tidak aktif untuk modal (Khusus Admin pada Manajemen Data)
     */
    public function show(Request $request, string|int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Hanya Administrator yang memiliki wewenang melihat data lengkap peserta didik.'], 403);
        }

        $item = DB::table('peserta_didik_tidak_aktif')
            ->where('id', $id)
            ->orWhere('peserta_didik_id', $id)
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Data arsip peserta didik tidak ditemukan.'], 404);
        }

        $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;

        return response()->json([
            'status' => 'success',
            'data' => $item,
        ]);
    }



    /**
     * Ekspor daftar siswa tidak aktif ke file Excel CSV
     */
    public function exportExcel(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $status = trim($request->get('status', ''));
        $tahun  = trim($request->get('tahun', ''));

        $query = DB::table('peserta_didik_tidak_aktif');
        if (Schema::hasTable('peserta_didik')) {
            $activePdIds = DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all();
            if (!empty($activePdIds)) {
                $query->whereNotIn('peserta_didik_id', $activePdIds);
            }
        }
        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }
        if ($tahun !== '') {
            $query->where('tahun_lulus', $tahun);
        }

        $items = $query->orderByDesc('tahun_lulus')->orderBy('nama')->get();

        $filename = 'Data_Alumni_Siswa_Tidak_Aktif_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($items) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'No',
                'Status',
                'NIPD',
                'Nama',
                'JK',
                'NISN',
                'Tempat Lahir',
                'Tanggal Lahir',
                'NIK',
                'Agama',
                'Kebutuhan Khusus',
                'Alamat',
                'RT',
                'RW',
                'Dusun',
                'Kelurahan',
                'Kecamatan',
                'Kode Pos',
                'Jenis Tinggal',
                'Alat Transportasi',
                'Telepon',
                'HP',
                'E-Mail',
                'SKHUN',
                'Penerima KPS',
                'No. KPS',
                'Data Ayah - Nama',
                'Data Ayah - Tahun Lahir',
                'Data Ayah - Jenjang Pendidikan',
                'Data Ayah - Pekerjaan',
                'Data Ayah - Penghasilan',
                'Data Ayah - Kebutuhan Khusus',
                'Data Ibu - Nama',
                'Data Ibu - Tahun Lahir',
                'Data Ibu - Jenjang Pendidikan',
                'Data Ibu - Pekerjaan',
                'Data Ibu - Penghasilan',
                'Data Ibu - Kebutuhan Khusus',
                'Data Wali - Nama',
                'Data Wali - Tahun Lahir',
                'Data Wali - Jenjang Pendidikan',
                'Data Wali - Pekerjaan',
                'Data Wali - Penghasilan',
                'Keluar Karena',
                'Tanggal keluar',
                'Tahun Lulus',
                'Rombel Terakhir',
            ]);

            $no = 1;
            foreach ($items as $r) {
                fputcsv($output, [
                    $no++,
                    $r->status_keluar ?? 'Alumni',
                    $r->nipd ? "'" . $r->nipd : '-',
                    $r->nama ?? '-',
                    $r->jenis_kelamin ?? '-',
                    $r->nisn ? "'" . $r->nisn : '-',
                    $r->tempat_lahir ?? '-',
                    $r->tanggal_lahir ?? '-',
                    $r->nik ? "'" . $r->nik : '-',
                    $r->agama_id_str ?? '-',
                    $r->kebutuhan_khusus ?? 'Tidak ada',
                    $r->alamat_jalan ?? '-',
                    $r->rt ?? '-',
                    $r->rw ?? '-',
                    $r->dusun ?? '-',
                    $r->kelurahan ?? '-',
                    $r->kecamatan ?? '-',
                    $r->kode_pos ?? '-',
                    $r->jenis_tinggal ?? '-',
                    $r->alat_transportasi ?? '-',
                    $r->nomor_telepon_rumah ?? '-',
                    $r->nomor_telepon_seluler ?? '-',
                    $r->email ?? '-',
                    $r->skhun ?? '-',
                    $r->penerima_kps ?? '-',
                    $r->no_kps ?? '-',
                    $r->nama_ayah ?? '-',
                    $r->tahun_lahir_ayah ?? '-',
                    $r->jenjang_pendidikan_ayah ?? '-',
                    $r->pekerjaan_ayah ?? '-',
                    $r->penghasilan_ayah ?? '-',
                    $r->kebutuhan_khusus_ayah ?? '-',
                    $r->nama_ibu ?? '-',
                    $r->tahun_lahir_ibu ?? '-',
                    $r->jenjang_pendidikan_ibu ?? '-',
                    $r->pekerjaan_ibu ?? '-',
                    $r->penghasilan_ibu ?? '-',
                    $r->kebutuhan_khusus_ibu ?? '-',
                    $r->nama_wali ?? '-',
                    $r->tahun_lahir_wali ?? '-',
                    $r->jenjang_pendidikan_wali ?? '-',
                    $r->pekerjaan_wali ?? '-',
                    $r->penghasilan_wali ?? '-',
                    $r->alasan_keluar ?? 'Lulus',
                    $r->tanggal_keluar ?? '-',
                    $r->tahun_lulus ?? '-',
                    $r->nama_rombel_terakhir ?? '-',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Arsipkan siswa kelas XII yang masih aktif
     */
    public function archiveGrade12(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        if (!RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'create') && !RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk mengarsipkan data peserta didik.'], 403);
        }

        $grade12ActiveCount = (int) $request->get('count', 0);

        if ($grade12ActiveCount === 0) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada peserta didik kelas XII yang perlu diarsipkan.'], 400);
        }

        // Ambil siswa kelas XII yang masih aktif
        $grade12Students = DB::table('peserta_didik')
            ->where('tingkat_pendidikan', 12)
            ->where('status', 'Aktif')
            ->pluck('peserta_didik_id')
            ->filter()
            ->all();

        if (empty($grade12Students)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada peserta didik kelas XII yang ditemukan.'], 404);
        }

        // Pindahkan ke tabel tidak aktif
        $movedCount = 0;
        foreach ($grade12Students as $studentId) {
            $existing = DB::table('peserta_didik_tidak_aktif')
                ->where('peserta_didik_id', $studentId)
                ->first();

            if (!$existing) {
                $student = DB::table('peserta_didik')
                    ->where('peserta_didik_id', $studentId)
                    ->first();

                if ($student) {
                    DB::table('peserta_didik_tidak_aktif')->insert([
                        'peserta_didik_id' => $student->peserta_didik_id,
                        'nama' => $student->nama,
                        'nisn' => $student->nisn,
                        'nipd' => $student->nipd,
                        'nik' => $student->nik,
                        'jenis_kelamin' => $student->jenis_kelamin,
                        'nama_rombel_terakhir' => $student->nama_rombel,
                        'tingkat_pendidikan_terakhir' => $student->tingkat_pendidikan,
                        'tahun_lulus' => date('Y'),
                        'tanggal_keluar' => now(),
                        'status_keluar' => 'Alumni',
                        'alasan_keluar' => 'Lulus',
                        'nomor_telepon_seluler' => $student->no_hp,
                        'alamat_jalan' => $student->alamat,
                        'foto_path' => $student->foto_path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $movedCount++;
                }
            }
        }

        // Hapus dari tabel aktif
        DB::table('peserta_didik')
            ->whereIn('peserta_didik_id', $grade12Students)
            ->update(['status' => 'Tidak Aktif']);

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil mengarsipkan {$movedCount} peserta didik kelas XII.",
            'moved_count' => $movedCount,
        ]);
    }

    /**
     * Unduh template Excel (.xlsx) data peserta didik keluar / tidak aktif sesuai format Dapodik
     */
    public function downloadTemplate(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $canRead = RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'read');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!$canRead && $role !== 'admin') {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang mengunduh berkas template.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_pdta_') . '.xlsx';

        $headers = [
            'NIPD',
            'Nama',
            'JK',
            'NISN',
            'Tempat Lahir',
            'Tanggal Lahir',
            'NIK',
            'Agama',
            'Kebutuhan Khusus',
            'Alamat',
            'RT',
            'RW',
            'Dusun',
            'Kelurahan',
            'Kecamatan',
            'Kode Pos',
            'Jenis Tinggal',
            'Alat Transportasi',
            'Telepon',
            'HP',
            'E-Mail',
            'SKHUN',
            'Penerima KPS',
            'No. KPS',
            'Data Ayah - Nama',
            'Data Ayah - Tahun Lahir',
            'Data Ayah - Jenjang Pendidikan',
            'Data Ayah - Pekerjaan',
            'Data Ayah - Penghasilan',
            'Data Ayah - Kebutuhan Khusus',
            'Data Ibu - Nama',
            'Data Ibu - Tahun Lahir',
            'Data Ibu - Jenjang Pendidikan',
            'Data Ibu - Pekerjaan',
            'Data Ibu - Penghasilan',
            'Data Ibu - Kebutuhan Khusus',
            'Data Wali - Nama',
            'Data Wali - Tahun Lahir',
            'Data Wali - Jenjang Pendidikan',
            'Data Wali - Pekerjaan',
            'Data Wali - Penghasilan',
            'Keluar Karena',
            'Tanggal keluar',
        ];

        $sampleRows = [
            [
                '212210032', 'A Jatnika', 'L', '3057658494', 'Cianjur', '2005-01-02',
                '3203180201050003', 'Islam', 'Tidak ada', 'Sukajaya', '3', '5', '',
                'Pasirbaru', 'Pasirbaru', '43265', 'Bersama orang tua', 'Jalan kaki',
                '085794404181', '085794404181', '', '', 'TIDAK', '', 'KOSIH', '1972',
                'SD / sederajat', 'Buruh', '< Rp1.000.000', 'Tidak ada', 'Nurhayati', '1988',
                'SD / sederajat', 'Lainnya', 'Tidak Berpenghasilan', 'Tidak ada', '', '', '',
                'Tidak bekerja', '', 'Lulus', '2024-07-01',
            ],
        ];

        SimpleXlsxExporter::export($tempPath, [], $headers, $sampleRows, [
            'sheetName' => 'Peserta Didik Keluar',
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, 'Template_Dapodik_Peserta_Didik_Keluar.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import berkas Excel (.xlsx / .xls) atau CSV (.csv) data Peserta Didik Keluar / Tidak Aktif (Format Dapodik)
     */
    public function importExcel(Request $request): JsonResponse
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $canCreate = RolePermission::canAccess($user, 'menu_peserta_didik_tidak_aktif', 'create');
        $isAdmin = ($role === 'admin');

        if (!$canCreate && !$isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menambah / mengimpor data peserta didik tidak aktif.'
            ], 403);
        }

        $request->validate([
            'file_excel' => 'required|file|max:30720',
        ], [
            'file_excel.required' => 'Berkas Excel wajib dipilih.',
            'file_excel.max' => 'Ukuran berkas maksimal adalah 30 MB.',
        ]);

        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format berkas tidak didukung. Unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).'
            ], 422);
        }

        $path = $file->getRealPath();

        try {
            $rows = SimpleXlsxReader::readFile($path);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca berkas: ' . $e->getMessage()
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Berkas Excel kosong atau baris data tidak terdeteksi.'
            ], 422);
        }

        if (!Schema::hasTable('peserta_didik_tidak_aktif')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tabel peserta_didik_tidak_aktif belum tersedia di database.'
            ], 500);
        }

        // Helper ekstraksi kolom cerdas (exact -> clean match -> prefix match -> fallback index)
        $extractCol = function (array $row, array $keywords, ?int $fallbackIndex = null): string {
            // 1. Cek index kolom mentah jika tersedia (__col_0, __col_1, dst)
            if ($fallbackIndex !== null && isset($row['__col_' . $fallbackIndex]) && trim((string)$row['__col_' . $fallbackIndex]) !== '') {
                return trim((string)$row['__col_' . $fallbackIndex]);
            }
            // 2. Cek kecocokan exact key
            foreach ($keywords as $kw) {
                if (isset($row[$kw]) && trim((string)$row[$kw]) !== '') {
                    return trim((string)$row[$kw]);
                }
            }
            // 3. Cek kecocokan clean key
            foreach ($row as $k => $v) {
                if (str_starts_with((string)$k, '__col_')) continue;
                $cleanK = preg_replace('/[^a-z0-9]/', '', strtolower((string)$k));
                foreach ($keywords as $kw) {
                    $cleanKw = preg_replace('/[^a-z0-9]/', '', strtolower($kw));
                    if ($cleanK === $cleanKw && trim((string)$v) !== '') {
                        return trim((string)$v);
                    }
                }
            }
            // 4. Cek fallback index dari array_values jika bukan '__col_'
            if ($fallbackIndex !== null) {
                $vals = array_values(array_filter($row, fn($k) => !str_starts_with((string)$k, '__col_'), ARRAY_FILTER_USE_KEY));
                if (isset($vals[$fallbackIndex]) && trim((string)$vals[$fallbackIndex]) !== '') {
                    return trim((string)$vals[$fallbackIndex]);
                }
            }
            return '';
        };

        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;

            // 1. Ekstrak data utama
            $nipd = $extractCol($row, ['nipd', 'no_induk', 'nis', 'nomor_induk'], 0);
            $nama = $extractCol($row, ['nama', 'nama_lengkap', 'nama_pd', 'nama_siswa'], 1);
            $jkRaw = strtoupper($extractCol($row, ['jk', 'jenis_kelamin', 'gender', 'l_p'], 2));
            $jk = str_starts_with($jkRaw, 'P') ? 'P' : 'L';
            $nisnRaw = $extractCol($row, ['nisn', 'nomor_induk_siswa_nasional'], 3);
            $nisn = preg_replace('/[^0-9]/', '', $nisnRaw);

            if ($nama === '' && $nisn === '' && $nipd === '') {
                continue;
            }

            if ($nama === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: Nama tidak boleh kosong (NISN: " . ($nisn ?: '-') . ").";
                continue;
            }

            $tempatLahir = $extractCol($row, ['tempat_lahir', 'tempat lahir', 'tmp_lahir'], 4);
            $tglLahirRaw = $extractCol($row, ['tanggal_lahir', 'tanggal lahir', 'tgl_lahir'], 5);
            $tglLahir = $this->parseTanggal($tglLahirRaw);

            $nikRaw = $extractCol($row, ['nik', 'no_nik', 'nik_ktp'], 6);
            $nik = preg_replace('/[^0-9]/', '', $nikRaw);

            $agama = $extractCol($row, ['agama', 'agama_id_str'], 7);
            $kebutuhanKhusus = $extractCol($row, ['kebutuhan_khusus', 'kebutuhan khusus'], 8);

            // Alamat & Domisili
            $alamat = $extractCol($row, ['alamat', 'alamat_jalan'], 9);
            $rt = $extractCol($row, ['rt'], 10);
            $rw = $extractCol($row, ['rw'], 11);
            $dusun = $extractCol($row, ['dusun'], 12);
            $kelurahan = $extractCol($row, ['kelurahan', 'desa_kelurahan'], 13);
            $kecamatan = $extractCol($row, ['kecamatan'], 14);
            $kodePos = $extractCol($row, ['kode_pos', 'kode pos'], 15);
            $jenisTinggal = $extractCol($row, ['jenis_tinggal', 'jenis tinggal'], 16);
            $alatTransportasi = $extractCol($row, ['alat_transportasi', 'alat transportasi'], 17);
            $teleponRumah = $extractCol($row, ['nomor_telepon_rumah', 'telepon', 'telp'], 18);
            $hp = $extractCol($row, ['hp', 'nomor_telepon_seluler', 'no_hp'], 19);
            $noHp = $hp !== '' ? $hp : ($teleponRumah !== '' ? $teleponRumah : null);

            $email = $extractCol($row, ['e_mail', 'e-mail', 'email'], 20);
            $skhun = $extractCol($row, ['skhun'], 21);
            $penerimaKps = $extractCol($row, ['penerima_kps', 'penerima kps'], 22);
            $noKps = $extractCol($row, ['no_kps', 'no. kps'], 23);

            // Data Ayah
            $namaAyah = $extractCol($row, ['data_ayah_nama', 'data ayah - nama', 'nama_ayah'], 24);
            $tahunLahirAyah = $extractCol($row, ['data_ayah_tahun_lahir', 'data ayah - tahun lahir', 'tahun_lahir_ayah'], 25);
            $pendidikanAyah = $extractCol($row, ['data_ayah_jenjang_pendidikan', 'data ayah - jenjang pendidikan', 'jenjang_pendidikan_ayah'], 26);
            $pekerjaanAyah = $extractCol($row, ['data_ayah_pekerjaan', 'data ayah - pekerjaan', 'pekerjaan_ayah'], 27);
            $penghasilanAyah = $extractCol($row, ['data_ayah_penghasilan', 'data ayah - penghasilan', 'penghasilan_ayah'], 28);
            $kebutuhanKhususAyah = $extractCol($row, ['data_ayah_kebutuhan_khusus', 'data ayah - kebutuhan khusus', 'kebutuhan_khusus_ayah'], 29);

            // Data Ibu
            $namaIbu = $extractCol($row, ['data_ibu_nama', 'data ibu - nama', 'nama_ibu'], 30);
            $tahunLahirIbu = $extractCol($row, ['data_ibu_tahun_lahir', 'data ibu - tahun lahir', 'tahun_lahir_ibu'], 31);
            $pendidikanIbu = $extractCol($row, ['data_ibu_jenjang_pendidikan', 'data ibu - jenjang pendidikan', 'jenjang_pendidikan_ibu'], 32);
            $pekerjaanIbu = $extractCol($row, ['data_ibu_pekerjaan', 'data ibu - pekerjaan', 'pekerjaan_ibu'], 33);
            $penghasilanIbu = $extractCol($row, ['data_ibu_penghasilan', 'data ibu - penghasilan', 'penghasilan_ibu'], 34);
            $kebutuhanKhususIbu = $extractCol($row, ['data_ibu_kebutuhan_khusus', 'data ibu - kebutuhan khusus', 'kebutuhan_khusus_ibu'], 35);

            // Data Wali
            $namaWali = $extractCol($row, ['data_wali_nama', 'data wali - nama', 'nama_wali'], 36);
            $tahunLahirWali = $extractCol($row, ['data_wali_tahun_lahir', 'data wali - tahun lahir', 'tahun_lahir_wali'], 37);
            $pendidikanWali = $extractCol($row, ['data_wali_jenjang_pendidikan', 'data wali - jenjang pendidikan', 'jenjang_pendidikan_wali'], 38);
            $pekerjaanWali = $extractCol($row, ['data_wali_pekerjaan', 'data wali - pekerjaan', 'pekerjaan_wali'], 39);
            $penghasilanWali = $extractCol($row, ['data_wali_penghasilan', 'data wali - penghasilan', 'penghasilan_wali'], 40);

            // Perakitan alamat lengkap
            $alamatParts = array_filter([
                $alamat,
                ($rt !== '' ? "RT {$rt}" : '') . ($rw !== '' ? ($rt !== '' ? ' / ' : '') . "RW {$rw}" : ''),
                $dusun !== '' ? "Dusun {$dusun}" : '',
                $kelurahan !== '' ? "Kel. {$kelurahan}" : '',
                $kecamatan !== '' ? "Kec. {$kecamatan}" : '',
                $kodePos !== '' ? "Kode Pos {$kodePos}" : '',
            ]);
            $alamatLengkap = !empty($alamatParts) ? implode(', ', $alamatParts) : ($alamat ?: null);

            // Kolom keluar
            $keluarKarena = $extractCol($row, ['keluar_karena', 'keluar karena', 'alasan_keluar'], 41);
            $tglKeluarRaw = $extractCol($row, ['tanggal_keluar', 'tanggal keluar', 'tgl_keluar'], 42);
            $tglKeluar = $this->parseTanggal($tglKeluarRaw);

            $alasan = $keluarKarena !== '' ? $keluarKarena : 'Lulus';

            // Klasifikasi status_keluar enum: 'Alumni', 'Mutasi', 'Keluar', 'Dikeluarkan', 'Lainnya'
            if (stripos($alasan, 'lulus') !== false) {
                $statusKeluar = 'Alumni';
            } elseif (stripos($alasan, 'mutasi') !== false || stripos($alasan, 'pindah') !== false) {
                $statusKeluar = 'Mutasi';
            } elseif (stripos($alasan, 'dikeluarkan') !== false) {
                $statusKeluar = 'Dikeluarkan';
            } elseif (stripos($alasan, 'keluar') !== false || stripos($alasan, 'undur') !== false || stripos($alasan, 'berhenti') !== false) {
                $statusKeluar = 'Keluar';
            } else {
                $statusKeluar = 'Lainnya';
            }

            // Tahun kelulusan / keluar
            if ($tglKeluar) {
                $tahunLulus = substr($tglKeluar, 0, 4);
            } else {
                $tahunLulus = date('Y');
            }

            try {
                // 1. Cek apakah siswa ini tercatat di peserta_didik (tabel aktif)
                $activePd = null;
                if (Schema::hasTable('peserta_didik')) {
                    $activePdQuery = DB::table('peserta_didik');
                    if ($nisn !== '') {
                        $activePd = (clone $activePdQuery)->where('nisn', $nisn)->first();
                    }
                    if (!$activePd && $nik !== '') {
                        $activePd = (clone $activePdQuery)->where('nik', $nik)->first();
                    }
                    if (!$activePd && $nipd !== '') {
                        $activePd = (clone $activePdQuery)->where('nipd', $nipd)->first();
                    }
                }

                $pdId = $activePd?->peserta_didik_id;
                $rombelTerakhir = $activePd?->nama_rombel;
                $rombelId = $activePd?->rombongan_belajar_id;
                $tingkatTerakhir = $activePd?->tingkat_pendidikan_id;
                $kurikulum = $activePd?->kurikulum_id_str;

                // Cek foto lama jika ada
                $fotoPath = null;
                if (!empty($pdId) && Schema::hasTable('peserta_didik_meta')) {
                    $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $pdId)->first();
                    $fotoPath = $meta?->foto_path;
                }

                // 2. Cek apakah sudah ada di peserta_didik_tidak_aktif
                $existing = DB::table('peserta_didik_tidak_aktif')
                    ->where(function ($w) use ($pdId, $nisn, $nik, $nipd, $nama, $tglLahir) {
                        if (!empty($pdId)) {
                            $w->where('peserta_didik_id', $pdId);
                        }
                        if ($nisn !== '') {
                            $w->orWhere('nisn', $nisn);
                        }
                        if ($nik !== '') {
                            $w->orWhere('nik', $nik);
                        }
                        if ($nipd !== '') {
                            $w->orWhere('nipd', $nipd);
                        }
                        if ($tglLahir && $nama !== '') {
                            $w->orWhere(function ($sub) use ($nama, $tglLahir) {
                                $sub->where('nama', $nama)->where('tanggal_lahir', $tglLahir);
                            });
                        }
                    })
                    ->first();

                if (!$pdId) {
                    $pdId = $existing?->peserta_didik_id ?: (string) Str::uuid();
                }

                $recordData = [
                    'peserta_didik_id' => $pdId,
                    'nipd' => $nipd ?: ($existing?->nipd ?: null),
                    'nama' => $nama,
                    'nisn' => $nisn ?: ($existing?->nisn ?: null),
                    'nik' => $nik ?: ($existing?->nik ?: null),
                    'jenis_kelamin' => $jk,
                    'tempat_lahir' => $tempatLahir ?: ($existing?->tempat_lahir ?: null),
                    'tanggal_lahir' => $tglLahir ?: ($existing?->tanggal_lahir ?: null),
                    'agama_id_str' => $agama ?: ($existing?->agama_id_str ?: null),
                    'kebutuhan_khusus' => $kebutuhanKhusus ?: ($existing?->kebutuhan_khusus ?: null),
                    'nama_rombel_terakhir' => $rombelTerakhir ?: ($existing?->nama_rombel_terakhir ?: null),
                    'rombongan_belajar_id' => $rombelId ?: ($existing?->rombongan_belajar_id ?: null),
                    'tingkat_pendidikan_terakhir' => $tingkatTerakhir ?: ($existing?->tingkat_pendidikan_terakhir ?: ($statusKeluar === 'Alumni' ? '12' : null)),
                    'kurikulum_id_str' => $kurikulum ?: ($existing?->kurikulum_id_str ?: null),
                    'tahun_lulus' => $tahunLulus,
                    'tanggal_keluar' => $tglKeluar ?: ($existing?->tanggal_keluar ?: now()->toDateString()),
                    'status_keluar' => $statusKeluar,
                    'alasan_keluar' => $alasan,
                    'email' => $email ?: ($existing?->email ?: null),
                    'nomor_telepon_seluler' => $noHp ?: ($existing?->nomor_telepon_seluler ?: null),
                    'nomor_telepon_rumah' => $teleponRumah ?: ($existing?->nomor_telepon_rumah ?: null),
                    'alamat_jalan' => $alamatLengkap ?: ($existing?->alamat_jalan ?: null),
                    'rt' => $rt ?: ($existing?->rt ?: null),
                    'rw' => $rw ?: ($existing?->rw ?: null),
                    'dusun' => $dusun ?: ($existing?->dusun ?: null),
                    'kelurahan' => $kelurahan ?: ($existing?->kelurahan ?: null),
                    'kecamatan' => $kecamatan ?: ($existing?->kecamatan ?: null),
                    'kode_pos' => $kodePos ?: ($existing?->kode_pos ?: null),
                    'jenis_tinggal' => $jenisTinggal ?: ($existing?->jenis_tinggal ?: null),
                    'alat_transportasi' => $alatTransportasi ?: ($existing?->alat_transportasi ?: null),
                    'skhun' => $skhun ?: ($existing?->skhun ?: null),
                    'penerima_kps' => $penerimaKps ?: ($existing?->penerima_kps ?: null),
                    'no_kps' => $noKps ?: ($existing?->no_kps ?: null),
                    'nama_ayah' => $namaAyah ?: ($existing?->nama_ayah ?: null),
                    'tahun_lahir_ayah' => $tahunLahirAyah ?: ($existing?->tahun_lahir_ayah ?: null),
                    'jenjang_pendidikan_ayah' => $pendidikanAyah ?: ($existing?->jenjang_pendidikan_ayah ?: null),
                    'pekerjaan_ayah' => $pekerjaanAyah ?: ($existing?->pekerjaan_ayah ?: null),
                    'penghasilan_ayah' => $penghasilanAyah ?: ($existing?->penghasilan_ayah ?: null),
                    'kebutuhan_khusus_ayah' => $kebutuhanKhususAyah ?: ($existing?->kebutuhan_khusus_ayah ?: null),
                    'nama_ibu' => $namaIbu ?: ($existing?->nama_ibu ?: null),
                    'tahun_lahir_ibu' => $tahunLahirIbu ?: ($existing?->tahun_lahir_ibu ?: null),
                    'jenjang_pendidikan_ibu' => $pendidikanIbu ?: ($existing?->jenjang_pendidikan_ibu ?: null),
                    'pekerjaan_ibu' => $pekerjaanIbu ?: ($existing?->pekerjaan_ibu ?: null),
                    'penghasilan_ibu' => $penghasilanIbu ?: ($existing?->penghasilan_ibu ?: null),
                    'kebutuhan_khusus_ibu' => $kebutuhanKhususIbu ?: ($existing?->kebutuhan_khusus_ibu ?: null),
                    'nama_wali' => $namaWali ?: ($existing?->nama_wali ?: null),
                    'tahun_lahir_wali' => $tahunLahirWali ?: ($existing?->tahun_lahir_wali ?: null),
                    'jenjang_pendidikan_wali' => $pendidikanWali ?: ($existing?->jenjang_pendidikan_wali ?: null),
                    'pekerjaan_wali' => $pekerjaanWali ?: ($existing?->pekerjaan_wali ?: null),
                    'penghasilan_wali' => $penghasilanWali ?: ($existing?->penghasilan_wali ?: null),
                    'raw_data' => json_encode($row, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ];

                if (!empty($fotoPath) && empty($existing?->foto_path)) {
                    $recordData['foto_path'] = $fotoPath;
                }

                if ($existing) {
                    DB::table('peserta_didik_tidak_aktif')
                        ->where('id', $existing->id)
                        ->update($recordData);
                    $updatedCount++;
                } else {
                    $recordData['created_at'] = now();
                    DB::table('peserta_didik_tidak_aktif')->insert($recordData);
                    $importedCount++;
                }

                // Jika siswa ini sebelumnya ada di peserta_didik (tabel aktif),
                // hapus dari tabel aktif karena status resminya sudah keluar/alumni di Dapodik
                if ($activePd) {
                    DB::table('peserta_didik')->where('peserta_didik_id', $activePd->peserta_didik_id)->delete();
                }
            } catch (\Throwable $err) {
                $skippedCount++;
                $errors[] = "Baris {$rowNum} ({$nama}): " . $err->getMessage();
            }
        }

        $totalProcessed = $importedCount + $updatedCount;

        if ($totalProcessed === 0) {
            $errDetail = !empty($errors) ? implode(' ', array_slice($errors, 0, 3)) : 'Pastikan berkas Excel memiliki baris data dengan kolom Nama dan NISN yang terisi.';
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data peserta didik tidak aktif yang berhasil diimpor. ' . $errDetail,
                'imported_count' => 0,
                'updated_count' => 0,
                'skipped_count' => $skippedCount,
                'errors' => array_slice($errors, 0, 10),
            ], 422);
        }

        $message = "Import data peserta didik keluar/tidak aktif berhasil: {$importedCount} data baru ditambahkan, {$updatedCount} data diperbarui.";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} baris dilewati).";
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'imported_count' => $importedCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'total_processed' => $totalProcessed,
            'errors' => array_slice($errors, 0, 10),
        ]);
    }

    /**
     * Parsing tanggal dari Excel (serial number, format Y-m-d, D-M-Y, atau text)
     * Mengembalikan format Y-m-d valid atau null
     */
    private function parseTanggal(mixed $value): ?string
    {
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        // Excel serial date number
        if (is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            return gmdate('Y-m-d', (int) (((float) $s - 25569) * 86400));
        }

        // Format YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }
        }

        // Format DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $m)) {
            if (checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
                return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            }
        }

        $time = strtotime($s);
        if ($time !== false && $time > 0) {
            $year = (int) date('Y', $time);
            if ($year >= 1950 && $year <= 2100) {
                return date('Y-m-d', $time);
            }
        }

        return null;
    }
}
