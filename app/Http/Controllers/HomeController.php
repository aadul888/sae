<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $hasPd = Schema::hasTable('peserta_didik');
        $hasRb = Schema::hasTable('rombongan_belajar');

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

        $metaMap = Schema::hasTable('jurusan_meta')
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

        return view('home', compact('stats', 'running_info', 'major_data', 'chart_detail'));
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

        if (!Schema::hasTable('peserta_didik')) {
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
