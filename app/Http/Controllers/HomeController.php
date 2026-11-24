<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
use App\Support\RombelScope;
use App\Support\ProgramKeahlian;
use App\Models\Berita;
use App\Models\Pengumuman;

class HomeController extends Controller
{
    public function index()
    {
        // PWA: saat aplikasi terpasang dibuka (start_url `/?source=pwa`), pengguna
        // yang sesinya sudah dipulihkan langsung diarahkan ke beranda perannya,
        // bukan halaman publik. Browser biasa tetap melihat halaman utama.
        if (request('source') === 'pwa') {
            $user = session('user');
            $role = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
            if ($role && \Illuminate\Support\Facades\Route::has('dashboard.' . $role)) {
                return redirect()->route('dashboard.' . $role);
            }
        }

        $hasPd = Table::exists('peserta_didik');
        $hasRb = Table::exists('rombongan_belajar');

        $totalPd = $hasPd ? DB::table('peserta_didik')->count() : 0;
        $maleCount = $hasPd ? DB::table('peserta_didik')->where('jenis_kelamin', 'L')->count() : 0;
        $femaleCount = $hasPd ? DB::table('peserta_didik')->where('jenis_kelamin', 'P')->count() : 0;

        // Distribusi tingkat hanya dari rombel reguler agar mapil/ekskul tidak
        // menghitung siswa yang sama lebih dari sekali.
        $gradeCounts = ['X' => 0, 'XI' => 0, 'XII' => 0];
        if ($hasPd && $hasRb) {
            $rows = RombelScope::reguler(
                DB::table('rombongan_belajar as rb')
                    ->join('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id'),
                'rb.'
            )
                ->select('rb.tingkat_pendidikan_id as tingkat', DB::raw('COUNT(DISTINCT pd.peserta_didik_id) as total'))
                ->groupBy('rb.tingkat_pendidikan_id')
                ->get();

            foreach ($rows as $row) {
                $tg = (string) $row->tingkat;
                if ($tg === '10') $tg = 'X';
                elseif ($tg === '11') $tg = 'XI';
                elseif ($tg === '12') $tg = 'XII';
                if (isset($gradeCounts[$tg])) $gradeCounts[$tg] = (int) $row->total;
            }
        }

        $gradeX = $gradeCounts['X'];
        $gradeXI = $gradeCounts['XI'];
        $gradeXII = $gradeCounts['XII'];

        $totalClasses = $hasRb ? DB::table('rombongan_belajar')->where(function ($q) {
            $q->where('jenis_rombel', '1')
                ->orWhere('jenis_rombel_str', 'LIKE', '%Kelas%')
                ->orWhere('jenis_rombel_str', 'LIKE', '%Reguler%')
                ->orWhereNull('jenis_rombel_str')
                ->orWhere('jenis_rombel_str', '');
        })->count() : 0;

        // Jurusan nyata = program keahlian, bukan tiap jurusan_id Dapodik
        // (kelas X dan XI/XII punya id berbeda untuk jurusan yang sama).
        $totalMajors = $hasRb
            ? count(\App\Support\ProgramKeahlian::mergeRows(
                DB::table('rombongan_belajar')
                    ->whereNotNull('jurusan_id_str')
                    ->where('jurusan_id_str', '<>', '')
                    ->select('jurusan_id as kode', 'jurusan_id_str as nama')
                    ->distinct()
                    ->get()
            ))
            : 0;

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

        // Spektrum keahlian: rombel kelas X memakai nama program keahlian,
        // kelas XI/XII memakai nama konsentrasi keahlian. Semua rekap
        // dipetakan ke program induk (lihat App\Support\ProgramKeahlian).
        $toProgram = fn($nama) => ProgramKeahlian::resolve($nama);

        $major_data = [];
        if ($hasRb && $hasPd) {
            $perJurusan = RombelScope::reguler(
                DB::table('rombongan_belajar')
                    ->join('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id'),
                'rombongan_belajar.'
            )
                ->whereNotNull('rombongan_belajar.jurusan_id_str')
                ->where('rombongan_belajar.jurusan_id_str', '<>', '')
                ->select(
                    'rombongan_belajar.jurusan_id_str as nama_jurusan',
                    DB::raw('COUNT(peserta_didik.peserta_didik_id) as total_peserta_didik'),
                    DB::raw('SUM(CASE WHEN peserta_didik.jenis_kelamin = "L" THEN 1 ELSE 0 END) as total_l'),
                    DB::raw('SUM(CASE WHEN peserta_didik.jenis_kelamin = "P" THEN 1 ELSE 0 END) as total_p'),
                    DB::raw('COUNT(DISTINCT rombongan_belajar.rombongan_belajar_id) as total_rombel')
                )
                ->groupBy('rombongan_belajar.jurusan_id_str')
                ->get();

            $agg = [];
            foreach ($perJurusan as $item) {
                $program = $toProgram($item->nama_jurusan);
                $agg[$program] ??= ['pd' => 0, 'l' => 0, 'p' => 0, 'rombel' => 0];
                $agg[$program]['pd']     += (int) $item->total_peserta_didik;
                $agg[$program]['l']      += (int) $item->total_l;
                $agg[$program]['p']      += (int) $item->total_p;
                $agg[$program]['rombel'] += (int) $item->total_rombel;
            }
            uasort($agg, fn($a, $b) => $b['pd'] <=> $a['pd']);

            foreach ($agg as $program => $row) {
                $code = $metaMap[$program] ?? ($knownCodes[$program] ?? null);
                if (!$code) {
                    preg_match_all('/\b\w/u', $program, $matches);
                    $code = strtoupper(implode('', $matches[0] ?? []));
                }
                $major_data[] = [
                    'nama_jurusan'        => $program,
                    'total_peserta_didik' => $row['pd'],
                    'total_l'             => $row['l'],
                    'total_p'             => $row['p'],
                    'total_rombel'        => $row['rombel'],
                    'code'                => $code,
                ];
            }
        }

        $chart_detail = [];
        if ($hasRb && $hasPd) {
            $chart_detail = RombelScope::reguler(
                DB::table('rombongan_belajar')
                    ->join('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id'),
                'rombongan_belajar.'
            )
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
                ->map(function ($row) use ($toProgram) {
                    $tg = (string) $row->tg;
                    if ($tg === '10') $tg = 'X';
                    elseif ($tg === '11') $tg = 'XI';
                    elseif ($tg === '12') $tg = 'XII';

                    return [
                        'j'  => $toProgram($row->j),
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

            // Berkas wajib = KK + Ijazah; Akta & KTP opsional. Kualitas Data memakai
            // rumus yang sama dengan kartu Wali Kelas — Validasi Data: berkas wajib
            // lengkap & valid (tanpa syarat konfirmasi).
            $wajibKeys = ['kartu_keluarga', 'ijazah_smp'];
            $berkasMap = collect();

            $pdRombelMap = DB::table('peserta_didik')
                ->select('peserta_didik_id', 'rombongan_belajar_id')
                ->pluck('rombongan_belajar_id', 'peserta_didik_id');

            $validKeysMap = [];
            if ($hasBerkas) {
                foreach (DB::table('peserta_didik_berkas')
                    ->where('status', 'valid')
                    ->whereIn('jenis_berkas', $wajibKeys)
                    ->select('peserta_didik_id', 'jenis_berkas')
                    ->get() as $r) {
                    $validKeysMap[$r->peserta_didik_id][$r->jenis_berkas] = true;
                }
            }
            if (Table::exists('kesiswaan_berkas_verifikasi')) {
                foreach ($wajibKeys as $wk) {
                    foreach (DB::table('kesiswaan_berkas_verifikasi')->where($wk, 1)->pluck('peserta_didik_id') as $pid) {
                        $validKeysMap[$pid][$wk] = true;
                    }
                }
            }

            foreach ($validKeysMap as $pid => $keys) {
                if (count($keys) >= count($wajibKeys)) {
                    $rid = $pdRombelMap[$pid] ?? null;
                    if ($rid) $berkasMap[$rid] = ($berkasMap[$rid] ?? 0) + 1;
                }
            }

            $progres_rombel = $rows->map(function ($r) use ($berkasMap) {
                $total = (int) $r->total_siswa;
                $sesuai = (int) $r->konfirm_sesuai;
                $tidakSesuai = (int) $r->konfirm_tidak_sesuai;
                $belum = (int) $r->belum_konfirmasi;
                $bValid = (int) ($berkasMap[$r->rombongan_belajar_id] ?? 0);
                $kualitasCount = $bValid;

                $persenSesuai = $total > 0 ? round(($sesuai / $total) * 100, 1) : 0;
                $persenTidakSesuai = $total > 0 ? round(($tidakSesuai / $total) * 100, 1) : 0;
                $persenBelum = $total > 0 ? round(($belum / $total) * 100, 1) : 0;
                $persenBerkas = $total > 0 ? round(($bValid / $total) * 100, 1) : 0;

                // Kualitas data = berkas wajib (KK + Ijazah) lengkap valid.
                // Rumus identik dengan halaman Wali Kelas — Validasi Data.
                $skorKualitas = $total > 0 ? round(($kualitasCount / $total) * 100, 1) : 0;

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
        $kategoriList = Berita::active()->reorder()->whereNotNull('kategori')->distinct()->pluck('kategori')->toArray();

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
