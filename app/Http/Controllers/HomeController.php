<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
use App\Models\Berita;
use App\Models\Pengumuman;

class HomeController extends Controller
{
    public function index()
    {
        $hasPd = Table::exists('peserta_didik');
        $hasRb = Table::exists('rombongan_belajar');

        $totalPd = $hasPd ? DB::table('peserta_didik')->count() : 0;
        $maleCount = $hasPd ? DB::table('peserta_didik')->where('jenis_kelamin', 'L')->count() : 0;
        $femaleCount = $hasPd ? DB::table('peserta_didik')->where('jenis_kelamin', 'P')->count() : 0;

        $gradeX = $hasPd ? DB::table('peserta_didik')->where(function ($q) {
            $q->whereIn('tingkat_pendidikan_id', ['10', 'X'])
                ->orWhere('nama_rombel', 'LIKE', 'X %')
                ->orWhere('nama_rombel', 'LIKE', 'X-%');
        })->count() : 0;

        $gradeXI = $hasPd ? DB::table('peserta_didik')->where(function ($q) {
            $q->whereIn('tingkat_pendidikan_id', ['11', 'XI'])
                ->orWhere('nama_rombel', 'LIKE', 'XI %')
                ->orWhere('nama_rombel', 'LIKE', 'XI-%');
        })->count() : 0;

        $gradeXII = $hasPd ? DB::table('peserta_didik')->where(function ($q) {
            $q->whereIn('tingkat_pendidikan_id', ['12', 'XII'])
                ->orWhere('nama_rombel', 'LIKE', 'XII %')
                ->orWhere('nama_rombel', 'LIKE', 'XII-%');
        })->count() : 0;

        $totalClasses = $hasRb ? DB::table('rombongan_belajar')->where(function ($q) {
            $q->where('jenis_rombel', '1')
                ->orWhere('jenis_rombel_str', 'LIKE', '%Kelas%')
                ->orWhere('jenis_rombel_str', 'LIKE', '%Reguler%')
                ->orWhereNull('jenis_rombel_str')
                ->orWhere('jenis_rombel_str', '');
        })->count() : 0;

        $totalMajors = $hasRb ? DB::table('rombongan_belajar')
            ->whereNotNull('jurusan_id_str')
            ->where('jurusan_id_str', '<>', '')
            ->distinct()
            ->count('jurusan_id_str') : 0;

        $stats = [
            'total_peserta_didik' => $totalPd,
            'total_students'      => $totalPd,
            'total_classes'       => $totalClasses,
            'total_majors'        => $totalMajors,
            'male_count'          => $maleCount,
            'female_count'        => $femaleCount,
            'grade_x'             => $gradeX,
            'grade_xi'            => $gradeXI,
            'grade_xii'           => $gradeXII,
        ];

        // Ambil pengumuman publik (running text) dari database
        try {
            $running_info = \App\Models\Pengumuman::forPublic()->pluck('isi')->toArray();
        } catch (\Throwable $e) {
            $running_info = [];
        }

        if (empty($running_info)) {
            $running_info = [
                'Pendaftaran Ujian Sekolah Tahun Ajaran 2026/2027 telah dibuka.',
                'Sinkronisasi Absensi RFID & Mobile berjalan normal.',
                'Sosialisasi Portal Kelulusan Online dijadwalkan Jumat ini.',
            ];
        }

        $metaMap = Table::exists('jurusan_meta')
            ? DB::table('jurusan_meta')->whereNotNull('singkatan')->pluck('singkatan', 'nama_jurusan')->toArray()
            : [];

        $knownCodes = [
            'Teknik Komputer dan Jaringan' => 'TKJ',
            'Teknik Jaringan Komputer dan Telekomunikasi' => 'TJKT',
            'Teknik Kendaraan Ringan' => 'TKR',
            'Teknik Otomotif' => 'TO',
            'Desain Komunikasi Visual' => 'DKV',
            'Kehutanan' => 'KHT',
            'Agribisnis Tanaman Pangan dan Hortikultura' => 'ATPH',
            'Agribisnis Tanaman' => 'AT',
            'Rekayasa Perangkat Lunak' => 'RPL',
            'Akuntansi Keuangan Lembaga' => 'AKL',
        ];

        $major_data = [];
        if ($hasRb && $hasPd) {
            $major_data = DB::table('rombongan_belajar')
                ->join('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id')
                ->whereNotNull('rombongan_belajar.jurusan_id_str')
                ->where('rombongan_belajar.jurusan_id_str', '<>', '')
                ->select(
                    'rombongan_belajar.jurusan_id_str as nama_jurusan',
                    DB::raw('COUNT(peserta_didik.peserta_didik_id) as total_peserta_didik')
                )
                ->groupBy('rombongan_belajar.jurusan_id_str')
                ->orderByDesc('total_peserta_didik')
                ->get()
                ->map(function ($item) use ($metaMap, $knownCodes) {
                    $name = $item->nama_jurusan;
                    $code = $metaMap[$name] ?? ($knownCodes[$name] ?? null);
                    if (!$code) {
                        preg_match_all('/\b\w/u', $name, $matches);
                        $code = strtoupper(implode('', $matches[0] ?? []));
                    }
                    return [
                        'nama_jurusan'        => $name,
                        'total_peserta_didik' => (int) $item->total_peserta_didik,
                        'code'                => $code,
                    ];
                })
                ->values()
                ->toArray();
        }

        $chart_detail = [];
        if ($hasRb && $hasPd) {
            $chart_detail = DB::table('rombongan_belajar')
                ->join('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id')
                ->whereNotNull('rombongan_belajar.jurusan_id_str')
                ->where('rombongan_belajar.jurusan_id_str', '<>', '')
                ->select(
                    'rombongan_belajar.jurusan_id_str as j',
                    'rombongan_belajar.tingkat_pendidikan_id as tg',
                    'rombongan_belajar.nama as k',
                    DB::raw('SUM(CASE WHEN peserta_didik.jenis_kelamin = "L" THEN 1 ELSE 0 END) as L'),
                    DB::raw('SUM(CASE WHEN peserta_didik.jenis_kelamin = "P" THEN 1 ELSE 0 END) as P')
                )
                ->groupBy(
                    'rombongan_belajar.rombongan_belajar_id',
                    'rombongan_belajar.nama',
                    'rombongan_belajar.jurusan_id_str',
                    'rombongan_belajar.tingkat_pendidikan_id'
                )
                ->orderBy('rombongan_belajar.nama')
                ->get()
                ->map(function ($row) {
                    $tg = (string) $row->tg;
                    if ($tg === '10') $tg = 'X';
                    elseif ($tg === '11') $tg = 'XI';
                    elseif ($tg === '12') $tg = 'XII';

                    return [
                        'j'  => $row->j,
                        'tg' => $tg,
                        'k'  => $row->k,
                        'L'  => (int) $row->L,
                        'P'  => (int) $row->P,
                    ];
                })
                ->values()
                ->toArray();
        }

        // Data Progres Pengiriman & Kualitas Data per Rombel
        $progres_rombel = [];
        if (Table::exists('rombongan_belajar') && Table::exists('peserta_didik')) {
            $hasGtk = Table::exists('gtk');
            $hasIdentitas = Table::exists('peserta_didik_identitas');
            $hasBerkas = Table::exists('peserta_didik_berkas');

            $progresQuery = DB::table('rombongan_belajar as rb')
                ->join('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id');

            if ($hasGtk) {
                $progresQuery->leftJoin('gtk', 'rb.ptk_id', '=', 'gtk.ptk_id');
            }

            if ($hasIdentitas) {
                $progresQuery->leftJoin('peserta_didik_identitas as pdi', 'pd.peserta_didik_id', '=', 'pdi.peserta_didik_id');
            }

            $selects = [
                'rb.rombongan_belajar_id',
                'rb.nama as nama_rombel',
                'rb.tingkat_pendidikan_id as tingkat',
                'rb.jurusan_id_str as jurusan',
                DB::raw('COUNT(DISTINCT pd.peserta_didik_id) as total_siswa'),
            ];

            if ($hasGtk) {
                $selects[] = 'gtk.nama as wali_kelas';
            } else {
                $selects[] = DB::raw('NULL as wali_kelas');
            }

            if ($hasIdentitas) {
                $selects[] = DB::raw("COUNT(DISTINCT CASE WHEN pdi.status_konfirmasi IN ('sesuai', 'diverifikasi', 'disinkronkan_dapodik') THEN pd.peserta_didik_id END) as konfirm_sesuai");
                $selects[] = DB::raw("COUNT(DISTINCT CASE WHEN pdi.status_konfirmasi = 'perlu_perbaikan' THEN pd.peserta_didik_id END) as konfirm_tidak_sesuai");
                $selects[] = DB::raw("COUNT(DISTINCT CASE WHEN pdi.status_konfirmasi IS NULL OR pdi.status_konfirmasi = 'belum_konfirmasi' THEN pd.peserta_didik_id END) as belum_konfirmasi");
            } else {
                $selects[] = DB::raw('0 as konfirm_sesuai');
                $selects[] = DB::raw('0 as konfirm_tidak_sesuai');
                $selects[] = DB::raw('COUNT(DISTINCT pd.peserta_didik_id) as belum_konfirmasi');
            }

            $groupBy = ['rb.rombongan_belajar_id', 'rb.nama', 'rb.tingkat_pendidikan_id', 'rb.jurusan_id_str'];
            if ($hasGtk) {
                $groupBy[] = 'gtk.nama';
            }

            $rows = $progresQuery
                ->select($selects)
                ->where('rb.jenis_rombel', '1') // rombel reguler
                ->groupBy($groupBy)
                ->orderBy('rb.nama')
                ->get();

            // Hitung siswa dengan berkas valid jika tabel berkas ada
            $berkasMap = collect();
            if ($hasBerkas) {
                $berkasMap = DB::table('peserta_didik_berkas as pdb')
                    ->join('peserta_didik as pd', 'pdb.peserta_didik_id', '=', 'pd.peserta_didik_id')
                    ->where('pdb.status', 'valid')
                    ->select('pd.rombongan_belajar_id', DB::raw('COUNT(DISTINCT pd.peserta_didik_id) as siswa_berkas_valid'))
                    ->groupBy('pd.rombongan_belajar_id')
                    ->pluck('siswa_berkas_valid', 'pd.rombongan_belajar_id');
            }

            $progres_rombel = $rows->map(function ($r) use ($berkasMap) {
                $total = (int) $r->total_siswa;
                $sesuai = (int) $r->konfirm_sesuai;
                $tidakSesuai = (int) $r->konfirm_tidak_sesuai;
                $belum = (int) $r->belum_konfirmasi;
                $bValid = (int) ($berkasMap[$r->rombongan_belajar_id] ?? 0);

                $persenSesuai = $total > 0 ? round(($sesuai / $total) * 100, 1) : 0;
                $persenTidakSesuai = $total > 0 ? round(($tidakSesuai / $total) * 100, 1) : 0;
                $persenBelum = $total > 0 ? round(($belum / $total) * 100, 1) : 0;
                $persenBerkas = $total > 0 ? round(($bValid / $total) * 100, 1) : 0;

                // Skor kualitas data komposit: rata-rata presentase sesuai + berkas valid
                $skorKualitas = round(($persenSesuai + $persenBerkas) / 2, 1);

                $tg = (string) $r->tingkat;
                if ($tg === '10') $tg = 'X';
                elseif ($tg === '11') $tg = 'XI';
                elseif ($tg === '12') $tg = 'XII';

                return [
                    'id'                  => $r->rombongan_belajar_id,
                    'rombel'              => $r->nama_rombel,
                    'tingkat'             => $tg,
                    'jurusan'             => $r->jurusan ?? '-',
                    'wali_kelas'          => $r->wali_kelas ?? 'Belum Ditentukan',
                    'total_siswa'         => $total,
                    'sesuai'              => $sesuai,
                    'persen_sesuai'       => $persenSesuai,
                    'tidak_sesuai'        => $tidakSesuai,
                    'persen_tidak_sesuai' => $persenTidakSesuai,
                    'belum_konfirmasi'    => $belum,
                    'persen_belum'        => $persenBelum,
                    'berkas_valid'        => $bValid,
                    'persen_berkas'       => $persenBerkas,
                    'kualitas'            => $skorKualitas,
                ];
            })
            // Default sort: kualitas data tertinggi
            ->sortByDesc('kualitas')
            ->values()
            ->toArray();
        }

        // Berita untuk Tampilan Halaman Utama (1 berita utama diperbesar, sisanya lebih kecil)
        try {
            $beritaUtama = Berita::active()->first();
            $beritaLainnya = Berita::active()
                ->when($beritaUtama, fn($q) => $q->where('id', '!=', $beritaUtama->id))
                ->take(4)
                ->get();
        } catch (\Throwable $e) {
            $beritaUtama = null;
            $beritaLainnya = collect();
        }

        return view('home', compact('stats', 'running_info', 'major_data', 'chart_detail', 'progres_rombel', 'beritaUtama', 'beritaLainnya'));
    }

    /**
     * Halaman Publik: Kumpulan Berita & Warta Sekolah
     */
    public function beritaIndex(Request $request)
    {
        $q = trim($request->get('q', ''));
        $kategori = trim($request->get('kategori', ''));

        $query = Berita::active();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('judul', 'like', "%{$q}%")
                    ->orWhere('ringkasan', 'like', "%{$q}%")
                    ->orWhere('isi', 'like', "%{$q}%")
                    ->orWhere('penulis_nama', 'like', "%{$q}%");
            });
        }

        if ($kategori !== '' && $kategori !== 'Semua') {
            $query->where('kategori', $kategori);
        }

        $beritaList = $query->paginate(9)->appends($request->query());
        $kategoriList = Berita::active()->whereNotNull('kategori')->distinct()->pluck('kategori')->toArray();

        return view('berita.index', compact('beritaList', 'kategoriList', 'q', 'kategori'));
    }

    /**
     * Halaman Publik: Detail Baca Berita
     */
    public function beritaShow(string $slug)
    {
        $berita = Berita::where('slug', $slug)->firstOrFail();

        // Tambah jumlah tayangan
        try {
            $berita->increment('views');
        } catch (\Throwable $e) {
        }

        $beritaTerkait = Berita::active()
            ->where('id', '!=', $berita->id)
            ->take(4)
            ->get();

        return view('berita.show', compact('berita', 'beritaTerkait'));
    }

    public function about()
    {
        $sekolah = Table::exists('sekolah')
            ? DB::table('sekolah')
                ->select('nama', 'npsn', 'alamat_jalan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'email', 'website', 'nomor_telepon')
                ->first()
            : null;

        return view('tentang', compact('sekolah'));
    }

    public function checkNisn(Request $request)
    {
        $nisn = trim($request->input('nisn', ''));
        if (!$nisn) {
            return response()->json([
                'status' => 'error',
                'found' => false,
                'message' => 'NISN wajib diisi.'
            ], 422);
        }

        if (!Table::exists('peserta_didik')) {
            return response()->json([
                'status' => 'error',
                'found' => false,
                'message' => 'Tabel peserta didik tidak ditemukan.'
            ], 500);
        }

        $pd = DB::table('peserta_didik')
            ->leftJoin('rombongan_belajar', 'peserta_didik.rombongan_belajar_id', '=', 'rombongan_belajar.rombongan_belajar_id')
            ->where('peserta_didik.nisn', $nisn)
            ->select(
                'peserta_didik.nisn',
                'peserta_didik.nama',
                'peserta_didik.nama_rombel',
                'rombongan_belajar.nama as kelas_nama',
                'rombongan_belajar.jurusan_id_str as jurusan_nama'
            )
            ->first();

        if (!$pd) {
            return response()->json([
                'status' => 'error',
                'found' => false,
                'message' => 'Peserta didik dengan NISN ' . $nisn . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'found' => true,
            'data' => [
                'nisn' => $pd->nisn,
                'nama' => $pd->nama,
                'kelas' => $pd->kelas_nama ?: ($pd->nama_rombel ?: '-'),
                'jurusan' => $pd->jurusan_nama ?: '-',
                'status' => 'Aktif Terdaftar',
            ]
        ]);
    }
}
