<?php

namespace App\Http\Controllers;

use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\RolePermission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JadwalPelajaranController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    private function getIndoDayName($date = null): string
    {
        $c = $date ? Carbon::parse($date) : Carbon::now();
        return match ($c->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
    }

    /**
     * Tampilan Jadwal Pelajaran & Mengajar (Read-Only)
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $isAdmin = in_array($role, ['admin', 'kepala_sekolah', 'waka_kurikulum'], true);
        $isGuru = ($role === 'guru');
        $isSiswa = ($role === 'peserta_didik');

        $isJadwalDiberlakukan = JadwalPengaturan::isDiberlakukan();
        $modeAktif = JadwalPengaturan::getModeAktif();

        $hariIni = $this->getIndoDayName();
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        // Default hari aktif: hari ini jika Senin-Sabtu, atau Senin jika Minggu
        $defaultHari = in_array($hariIni, $hariList, true) ? $hariIni : 'Senin';
        $selectedHari = $request->get('hari', $defaultHari);
        if ($selectedHari !== 'semua' && !in_array($selectedHari, $hariList, true)) {
            $selectedHari = $defaultHari;
        }

        $nowHi = Carbon::now()->format('H:i');

        // 1. Context Khusus Siswa
        $siswaPd = null;
        $studentRombelId = null;
        $studentRombel = null;

        if ($isSiswa && $pdId) {
            $siswaPd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
            $studentRombelId = $siswaPd?->rombongan_belajar_id;
            if ($studentRombelId) {
                $studentRombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $studentRombelId)->first();
            }
        }

        // 2. Query Jadwal Pokok
        $query = JadwalKbm::query()
            ->where('is_active', true);

        if ($modeAktif) {
            $query->where('sumber', $modeAktif);
        }

        // Filter Role Guru
        if ($isGuru) {
            $query->excludePkl();
            if ($ptkId) {
                $query->where('ptk_id', $ptkId);
            }
        }

        // Filter Role Siswa
        if ($isSiswa) {
            if ($studentRombelId) {
                $query->where('rombongan_belajar_id', $studentRombelId);
            } else {
                $query->whereRaw('1 = 0'); // Belum terdaftar di rombel
            }
        }

        // Filter Tambahan Rombel (Admin atau Guru jika memilih kelas tertentu)
        if ($request->filled('rombel_id')) {
            $query->where('rombongan_belajar_id', $request->rombel_id);
        }

        // Filter Tambahan Guru (Admin)
        if ($isAdmin && $request->filled('ptk_id')) {
            $query->where('ptk_id', $request->ptk_id);
        }

        // Filter Search Kata Kunci
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nama_mata_pelajaran', 'like', "%{$q}%")
                  ->orWhere('ruangan', 'like', "%{$q}%");
            });
        }

        // Ambil semua jadwal mingguan guru/siswa ini untuk kalkulasi statistik
        $allWeeklySchedules = (clone $query)->orderBy('jam_ke_mulai', 'asc')->get();

        // Filter Hari untuk Tampilan Tab Aktif
        if ($selectedHari !== 'semua') {
            $query->where('hari', $selectedHari);
        }

        $schedules = $query
            ->orderBy('hari', 'asc')
            ->orderBy('jam_ke_mulai', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->get();

        // Enrich setiap jadwal item dengan nama rombel, guru, dan status live KBM
        $schedules = $schedules->map(function ($j) use ($hariIni, $nowHi) {
            $rombelNama = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->value('nama') ?? $j->rombongan_belajar_id;
            $guruNama = DB::table('gtk')->where('ptk_id', $j->ptk_id)->value('nama') ?? ($j->ptk_id ?: 'Guru Pengampu');

            $status = '';
            if ($j->hari === $hariIni && !empty($j->jam_mulai) && !empty($j->jam_selesai)) {
                $jm = substr($j->jam_mulai, 0, 5);
                $js = substr($j->jam_selesai, 0, 5);
                if ($nowHi >= $jm && $nowHi <= $js) {
                    $status = 'Berlangsung';
                } elseif ($nowHi > $js) {
                    $status = 'Selesai';
                } else {
                    $status = 'Mendatang';
                }
            }

            $j->nama_rombel = $rombelNama;
            $j->nama_guru = $guruNama;
            $j->status_kbm = $status;
            return $j;
        });

        // Daftar Rombel Pilihan (untuk Guru: rombel yang diajar; untuk Admin: semua rombel)
        $rombelOptions = collect();
        if ($isGuru && $ptkId) {
            $rombelIds = DB::table('pembelajaran')->where('ptk_id', $ptkId)->pluck('rombongan_belajar_id')
                ->merge(DB::table('jadwal_kbm')->where('ptk_id', $ptkId)->pluck('rombongan_belajar_id'))
                ->filter()->unique()->toArray();
            $rombelOptions = DB::table('rombongan_belajar')->whereIn('rombongan_belajar_id', $rombelIds)->orderBy('nama')->get();
        } elseif ($isAdmin) {
            $rombelOptions = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->orderBy('nama')->get();
        }

        // Hitung Ringkasan Statistik
        $todaySchedules = $allWeeklySchedules->where('hari', $hariIni);
        $totalSesiHariIni = $todaySchedules->count();
        $totalSesiMingguan = $allWeeklySchedules->count();
        $totalJpMingguan = $allWeeklySchedules->sum(fn($j) => (int) ($j->durasi_jp ?: max(1, $j->jam_ke_selesai - $j->jam_ke_mulai + 1)));

        $uniqueKelasCount = $allWeeklySchedules->pluck('rombongan_belajar_id')->filter()->unique()->count();
        $uniqueMapelCount = $allWeeklySchedules->pluck('nama_mata_pelajaran')->filter()->unique()->count();

        $stats = [
            'total_sesi_hari_ini' => $totalSesiHariIni,
            'total_sesi_mingguan' => $totalSesiMingguan,
            'total_jp_mingguan'   => $totalJpMingguan,
            'total_kelas'         => $uniqueKelasCount,
            'total_mapel'         => $uniqueMapelCount,
        ];

        return view('dashboard.jadwal-pelajaran', compact(
            'schedules',
            'stats',
            'hariList',
            'selectedHari',
            'hariIni',
            'isJadwalDiberlakukan',
            'modeAktif',
            'isGuru',
            'isSiswa',
            'isAdmin',
            'rombelOptions',
            'studentRombel',
            'siswaPd'
        ));
    }
}
