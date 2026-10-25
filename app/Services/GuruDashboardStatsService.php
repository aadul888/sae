<?php

namespace App\Services;

use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\PipPengaturan;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
/**
 * Service agregasi data metrik, jadwal harian, dan ringkasan perwalian guru.
 * ponytail: static-calculator; upgrade ke caching per-guru jika query jadwal per menit melebihi 200 req/sec.
 */
class GuruDashboardStatsService
{
    /**
     * Kumpulkan seluruh dataset untuk tampilan dashboard Guru.
     */
    public static function getStats(mixed $user, Request $request): array
    {
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));

        // Dukungan pemilihan guru untuk Superadmin / Tendik
        $allGtkList = collect();
        if (($userRole === 'admin' || $userRole === 'tendik') && Table::exists('gtk')) {
            $allGtkList = DB::table('gtk')
                ->where(function ($q) {
                    $q->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                        ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                        ->orWhereNull('jenis_ptk_id_str');
                })
                ->select('ptk_id', 'nama', 'nip', 'jenis_ptk_id_str')
                ->orderBy('nama')
                ->get();
        }

        $reqPtkId = $request->query('ptk_id');
        if ($reqPtkId && ($userRole === 'admin' || $userRole === 'tendik')) {
            $selectedGtk = DB::table('gtk')->where('ptk_id', $reqPtkId)->first();
            if ($selectedGtk) {
                $ptkId = $selectedGtk->ptk_id;
                $userName = $selectedGtk->nama;
            }
        } elseif (!$ptkId && ($userRole === 'admin' || $userRole === 'tendik') && $allGtkList->isNotEmpty()) {
            $firstGuru = $allGtkList->first();
            $ptkId = $firstGuru->ptk_id;
            $userName = $firstGuru->nama;
        }

        $gtk = null;
        if (Table::exists('gtk')) {
            $gtk = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : DB::table('gtk')->where('nama', $userName)->first();
        }

        $fotoUrl = is_array($user) ? ($user['foto_url'] ?? null) : ($user->foto_url ?? null);
        if (!$fotoUrl && $userId) {
            $fotoUrl = User::where('pengguna_id', $userId)->first()?->foto_url;
        }
        if (!$fotoUrl && $ptkId) {
            $fotoUrl = User::where('ptk_id', $ptkId)->whereNotNull('foto_path')->first()?->foto_url;
        }
        if (!$fotoUrl && $gtk?->ptk_id) {
            $fotoUrl = User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
        }

        $pembelajaran = collect();
        if ($gtk && Table::exists('pembelajaran')) {
            $pembelajaran = DB::table('pembelajaran')
                ->leftJoin('rombongan_belajar', 'pembelajaran.rombongan_belajar_id', '=', 'rombongan_belajar.rombongan_belajar_id')
                ->where('pembelajaran.ptk_id', $gtk->ptk_id)
                ->select(
                    'pembelajaran.nama_mata_pelajaran',
                    'pembelajaran.jam_mengajar_per_minggu',
                    'rombongan_belajar.nama as nama_rombel',
                    'rombongan_belajar.id_ruang_str as ruang',
                    'rombongan_belajar.rombongan_belajar_id'
                )
                ->get();
        }

        $totalJamAjar = $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0));
        $kelasDiampu = $pembelajaran->pluck('rombongan_belajar_id')->filter()->unique()->count();
        $rombelIds = $pembelajaran->pluck('rombongan_belajar_id')->filter()->unique()->toArray();
        $totalPdDiampu = (!empty($rombelIds) && Table::exists('peserta_didik')) ? DB::table('peserta_didik')->whereIn('rombongan_belajar_id', $rombelIds)->count() : 0;

        $tanggalHariIni = now()->toDateString();
        $statusHariIni = KalenderPendidikan::getStatusHari($tanggalHariIni, 'gtk');
        $agendaHariIni = KalenderPendidikan::whereDate('tanggal_mulai', '<=', $tanggalHariIni)
            ->whereDate('tanggal_selesai', '>=', $tanggalHariIni)
            ->first();

        $hebBulanIni = KalenderPendidikan::hitungHariEfektif(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), 'gtk');
        $hebBulanBerjalan = KalenderPendidikan::hitungHariEfektifBerjalan(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), $tanggalHariIni, 'gtk');

        $stats = [
            'total_jam_ajar'        => $totalJamAjar ?: 24,
            'kelas_diampu'          => $kelasDiampu ?: 5,
            'total_peserta_didik'   => $totalPdDiampu ?: 175,
            'presensi_masuk'        => '06:45 WIB',
            'status_presensi'       => 'Hadir Tepat Waktu',
            'hari_efektif_bulan_ini' => $hebBulanIni,
            'hari_efektif_berjalan' => $hebBulanBerjalan,
        ];

        $hariIni = match (now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };

        $isJadwalDiberlakukan = JadwalPengaturan::isDiberlakukan();
        $modeAktif = JadwalPengaturan::getModeAktif();
        $isLiburHariIni = ($statusHariIni['is_libur'] ?? false) || !JadwalPengaturan::isHariAktif(now());
        $jadwal_hari_ini = [];

        if ($isJadwalDiberlakukan && $modeAktif && !$isLiburHariIni && $gtk && !empty($gtk->ptk_id) && Table::exists('jadwal_kbm')) {
            $jadwalRiil = DB::table('jadwal_kbm as j')
                ->leftJoin('rombongan_belajar as rb', 'j.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('j.sumber', $modeAktif)
                ->where('j.is_active', true)
                ->where('j.ptk_id', $gtk->ptk_id)
                ->where('j.hari', $hariIni)
                ->orderBy('j.jam_ke_mulai', 'asc')
                ->orderBy('j.jam_mulai', 'asc')
                ->select('j.*', 'rb.nama as nama_rombel')
                ->get();

            $nowTime = now()->format('H:i');

            foreach ($jadwalRiil as $j) {
                $jamMulai = $j->jam_mulai ? substr($j->jam_mulai, 0, 5) : null;
                $jamSelesai = $j->jam_selesai ? substr($j->jam_selesai, 0, 5) : null;
                $jamStr = ($jamMulai && $jamSelesai)
                    ? "{$jamMulai} - {$jamSelesai}"
                    : (($j->jam_ke_mulai == $j->jam_ke_selesai) ? "Jam ke-{$j->jam_ke_mulai}" : "Jam ke-{$j->jam_ke_mulai} s/d {$j->jam_ke_selesai}");

                $status = 'Mendatang';
                if ($jamMulai && $jamSelesai) {
                    if ($nowTime >= $jamMulai && $nowTime <= $jamSelesai) {
                        $status = 'Berlangsung';
                    } elseif ($nowTime > $jamSelesai) {
                        $status = 'Selesai';
                    }
                }

                $jadwal_hari_ini[] = [
                    'id'      => $j->id,
                    'jam'     => $jamStr,
                    'kelas'   => $j->nama_rombel ?: 'Rombel',
                    'mapel'   => $j->nama_mata_pelajaran ?: 'Mata Pelajaran',
                    'ruang'   => $j->ruangan ?: 'Ruang Kelas',
                    'status'  => $status,
                ];
            }
        }

        if (!$isJadwalDiberlakukan && !$isLiburHariIni && empty($jadwal_hari_ini) && $pembelajaran->isNotEmpty()) {
            foreach ($pembelajaran->take(4) as $idx => $pem) {
                $jadwal_hari_ini[] = [
                    'id'     => null,
                    'jam'    => sprintf('%02d:30 - %02d:00', 7 + ($idx * 2), 9 + ($idx * 2)),
                    'kelas'  => $pem->nama_rombel ?: 'Rombel',
                    'mapel'  => $pem->nama_mata_pelajaran ?: 'Mata Pelajaran',
                    'ruang'  => $pem->ruang ?: 'Ruang Kelas',
                    'status' => 'Draft',
                ];
            }
        }

        $mapelUtama = null;
        if ($pembelajaran->isNotEmpty()) {
            $mapelUtama = $pembelajaran->groupBy('nama_mata_pelajaran')
                ->map(fn($group) => $group->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)))
                ->sortDesc()
                ->keys()
                ->first();
        }
        if (!$mapelUtama && $gtk) {
            $mapelUtama = $gtk->bidang_studi_terakhir ?? ($gtk->jabatan_ptk_id_str ?? 'Guru Mata Pelajaran');
        }

        $waliInfo = RolePermission::getWaliKelasRombelInfo($user);
        $waliStats = null;
        if ($waliInfo && Table::exists('peserta_didik')) {
            $rombelId = $waliInfo->rombongan_belajar_id ?? null;
            if ($rombelId) {
                $totalSiswaWali = DB::table('peserta_didik')->where('rombongan_belajar_id', $rombelId)->count();
                $todayDate = now()->toDateString();
                $hadirToday = 0;
                $izinToday = 0;
                $sakitToday = 0;
                $alpaToday = 0;
                if (Table::exists('presensi_harian')) {
                    $presensiKelas = DB::table('presensi_harian')
                        ->where('tanggal', $todayDate)
                        ->whereIn('peserta_didik_id', function ($q) use ($rombelId) {
                            $q->select('peserta_didik_id')->from('peserta_didik')->where('rombongan_belajar_id', $rombelId);
                        })
                        ->get();
                    $hadirToday = $presensiKelas->whereIn('status', ['H', 'T'])->count();
                    $izinToday = $presensiKelas->where('status', 'I')->count();
                    $sakitToday = $presensiKelas->where('status', 'S')->count();
                    $alpaToday = $presensiKelas->where('status', 'A')->count();
                }
                $waliStats = [
                    'rombel_nama' => $waliInfo->nama,
                    'rombel_id'   => $rombelId,
                    'total_siswa' => $totalSiswaWali,
                    'hadir'       => $hadirToday,
                    'izin'        => $izinToday,
                    'sakit'       => $sakitToday,
                    'alpa'        => $alpaToday,
                ];
            }
        }

        $pipCountdownWali = null;
        if ($waliInfo && class_exists(PipPengaturan::class)) {
            $pipCountdownWali = PipPengaturan::getStatusInfo();
        }

        return compact(
            'stats',
            'jadwal_hari_ini',
            'gtk',
            'statusHariIni',
            'agendaHariIni',
            'hariIni',
            'fotoUrl',
            'mapelUtama',
            'allGtkList',
            'userRole',
            'ptkId',
            'userName',
            'waliStats',
            'isJadwalDiberlakukan',
            'waliInfo',
            'pipCountdownWali'
        );
    }
}
