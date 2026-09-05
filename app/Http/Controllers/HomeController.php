<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Data dummy statistik sekolah
        $stats = [
            'total_students' => 1248,
            'total_classes'  => 36,
            'total_majors'   => 5,
            'male_count'     => 680,
            'female_count'   => 568,
            'grade_x'        => 430,
            'grade_xi'       => 412,
            'grade_xii'      => 406,
        ];

        $running_info = [
            'Pendaftaran Ujian Sekolah Tahun Ajaran 2026/2027 telah dibuka.',
            'Sinkronisasi Absensi RFID & Mobile berjalan normal.',
            'Sosialisasi Portal Kelulusan Online dijadwalkan Jumat ini.',
        ];

        $major_data = [
            ['nama_jurusan' => 'Teknik Komputer & Jaringan', 'total_siswa' => 320, 'code' => 'TKJ'],
            ['nama_jurusan' => 'Rekayasa Perangkat Lunak', 'total_siswa' => 295, 'code' => 'RPL'],
            ['nama_jurusan' => 'Desain Komunikasi Visual', 'total_siswa' => 240, 'code' => 'DKV'],
            ['nama_jurusan' => 'Teknik Kendaraan Ringan', 'total_siswa' => 210, 'code' => 'TKR'],
            ['nama_jurusan' => 'Akuntansi Keuangan Lembaga', 'total_siswa' => 183, 'code' => 'AKL'],
        ];

        $chart_detail = [
            ['j' => 'Teknik Komputer & Jaringan', 'tg' => 'X', 'k' => 'X TKJ 1', 'L' => 18, 'P' => 17],
            ['j' => 'Teknik Komputer & Jaringan', 'tg' => 'X', 'k' => 'X TKJ 2', 'L' => 20, 'P' => 15],
            ['j' => 'Teknik Komputer & Jaringan', 'tg' => 'XI', 'k' => 'XI TKJ 1', 'L' => 19, 'P' => 16],
            ['j' => 'Teknik Komputer & Jaringan', 'tg' => 'XII', 'k' => 'XII TKJ 1', 'L' => 21, 'P' => 14],
            ['j' => 'Rekayasa Perangkat Lunak', 'tg' => 'X', 'k' => 'X RPL 1', 'L' => 16, 'P' => 19],
            ['j' => 'Rekayasa Perangkat Lunak', 'tg' => 'XI', 'k' => 'XI RPL 1', 'L' => 17, 'P' => 18],
            ['j' => 'Rekayasa Perangkat Lunak', 'tg' => 'XII', 'k' => 'XII RPL 1', 'L' => 15, 'P' => 20],
            ['j' => 'Desain Komunikasi Visual', 'tg' => 'X', 'k' => 'X DKV 1', 'L' => 12, 'P' => 23],
            ['j' => 'Desain Komunikasi Visual', 'tg' => 'XI', 'k' => 'XI DKV 1', 'L' => 14, 'P' => 21],
            ['j' => 'Desain Komunikasi Visual', 'tg' => 'XII', 'k' => 'XII DKV 1', 'L' => 11, 'P' => 24],
            ['j' => 'Teknik Kendaraan Ringan', 'tg' => 'X', 'k' => 'X TKR 1', 'L' => 32, 'P' => 3],
            ['j' => 'Teknik Kendaraan Ringan', 'tg' => 'XI', 'k' => 'XI TKR 1', 'L' => 31, 'P' => 4],
            ['j' => 'Teknik Kendaraan Ringan', 'tg' => 'XII', 'k' => 'XII TKR 1', 'L' => 33, 'P' => 2],
            ['j' => 'Akuntansi Keuangan Lembaga', 'tg' => 'X', 'k' => 'X AKL 1', 'L' => 8, 'P' => 27],
            ['j' => 'Akuntansi Keuangan Lembaga', 'tg' => 'XI', 'k' => 'XI AKL 1', 'L' => 7, 'P' => 28],
            ['j' => 'Akuntansi Keuangan Lembaga', 'tg' => 'XII', 'k' => 'XII AKL 1', 'L' => 6, 'P' => 29],
        ];

        return view('home', compact('stats', 'running_info', 'major_data', 'chart_detail'));
    }

    public function checkNisn(Request $request)
    {
        $nisn = $request->input('nisn');
        
        // Dummy response
        return response()->json([
            'status' => 'success',
            'found' => true,
            'data' => [
                'nisn' => $nisn,
                'nama' => 'Ahmad Fauzi Pratama',
                'kelas' => 'XII RPL 1',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'status' => 'Aktif Terdaftar',
            ]
        ]);
    }
}
