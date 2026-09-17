<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private function checkAuth(?string $allowedRole = null)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        if (!$userRole) {
            // Attempt to resolve role from peran_id_str or default to admin/peserta_didik
            $peran = is_array($user) ? ($user['peran_id_str'] ?? '') : ($user->peran_id_str ?? '');
            $peranLower = strtolower($peran);
            if (str_contains($peranLower, 'admin')) {
                $userRole = 'admin';
            } elseif (str_contains($peranLower, 'tendik') || str_contains($peranLower, 'tenaga kependidikan') || str_contains($peranLower, 'tata usaha')) {
                $userRole = 'tendik';
            } elseif (str_contains($peranLower, 'guru') || str_contains($peranLower, 'pendidik') || str_contains($peranLower, 'ptk')) {
                $userRole = 'guru';
            } else {
                $userRole = 'peserta_didik';
            }

            if (is_array($user)) {
                $user['role'] = $userRole;
                session(['user' => $user]);
            }
        }

        if ($allowedRole && $userRole !== $allowedRole) {
            return redirect()->route('dashboard.' . $userRole);
        }
        return null;
    }

    public function admin()
    {
        if ($res = $this->checkAuth('admin')) return $res;
        if (!\App\Models\RolePermission::canAccess('admin', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Administrator', 'role' => 'admin']);
        }

        $totalPd = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 0;
        $totalGuru = Schema::hasTable('gtk') ? DB::table('gtk')->where(function ($q) {
            $q->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                ->orWhereNull('jenis_ptk_id_str');
        })->count() : 0;

        $totalTendik = Schema::hasTable('gtk') ? DB::table('gtk')->where(function ($q) {
            $q->where('jenis_ptk_id_str', 'LIKE', '%Tenaga Kependidikan%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Tata Usaha%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Laboran%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Pustakawan%');
        })->count() : 0;

        $totalKelas = Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0;
        $totalPembelajaran = Schema::hasTable('pembelajaran') ? DB::table('pembelajaran')->count() : 0;
        $totalPengguna = Schema::hasTable('pengguna') ? DB::table('pengguna')->count() : 0;
        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $lastSync = Schema::hasTable('settings') ? DB::table('settings')->value('last_sync') : null;

        $stats = [
            'total_peserta_didik' => $totalPd ?: 0,
            'total_guru'          => $totalGuru ?: 0,
            'total_tendik'        => $totalTendik ?: 0,
            'total_kelas'         => $totalKelas ?: 0,
            'total_pembelajaran'  => $totalPembelajaran ?: 0,
            'total_pengguna'      => $totalPengguna ?: 0,
            'presensi_today'      => 96.4,
            'rfid_taps'           => $totalPd ? round($totalPd * 0.94) : 0,
            'sync_dapodik'        => $lastSync ? \Carbon\Carbon::parse($lastSync)->format('d M Y, H:i') . ' WIB' : 'Belum Sinkron'
        ];

        $recent_logs = [
            ['time' => now()->format('H:i'), 'user' => 'Sistem Sync', 'action' => 'Data Dapodik: ' . $totalPd . ' Peserta Didik, ' . $totalGuru . ' Guru, ' . $totalKelas . ' Rombel, ' . $totalPembelajaran . ' Mapel', 'status' => 'info'],
            ['time' => now()->subMinutes(15)->format('H:i'), 'user' => 'Gateway RFID #01', 'action' => 'Presensi Masuk Gerbang Utama Aktif', 'status' => 'success'],
            ['time' => now()->subMinutes(45)->format('H:i'), 'user' => $sekolah->nama ?? 'Admin Sekolah', 'action' => 'Monitoring Data Pokok Satuan Pendidikan', 'status' => 'success'],
        ];

        return view('dashboard.admin', compact('stats', 'recent_logs', 'sekolah'));
    }

    public function guru()
    {
        if ($res = $this->checkAuth('guru')) return $res;
        if (!\App\Models\RolePermission::canAccess('guru', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Guru & Pendidik', 'role' => 'guru']);
        }

        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));

        $gtk = null;
        if (Schema::hasTable('gtk')) {
            $gtk = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : DB::table('gtk')->where('nama', $userName)->first();
        }

        $pembelajaran = collect();
        if ($gtk && Schema::hasTable('pembelajaran')) {
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
        $totalPdDiampu = (!empty($rombelIds) && Schema::hasTable('peserta_didik')) ? DB::table('peserta_didik')->whereIn('rombongan_belajar_id', $rombelIds)->count() : 0;

        // Integrasi Kalender Pendidikan Hari Ini & Hari Efektif Belajar
        $tanggalHariIni = now()->toDateString();
        $statusHariIni = \App\Models\KalenderPendidikan::getStatusHari($tanggalHariIni, 'gtk');
        $agendaHariIni = \App\Models\KalenderPendidikan::whereDate('tanggal_mulai', '<=', $tanggalHariIni)
            ->whereDate('tanggal_selesai', '>=', $tanggalHariIni)
            ->first();

        $hebBulanIni = \App\Models\KalenderPendidikan::hitungHariEfektif(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), 'gtk');
        $hebBulanBerjalan = \App\Models\KalenderPendidikan::hitungHariEfektifBerjalan(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), $tanggalHariIni, 'gtk');

        $stats = [
            'total_jam_ajar'        => $totalJamAjar ?: 24,
            'kelas_diampu'          => $kelasDiampu ?: 5,
            'total_peserta_didik'   => $totalPdDiampu ?: 175,
            'presensi_masuk'        => '06:45 WIB',
            'status_presensi'       => 'Hadir Tepat Waktu',
            'hari_efektif_bulan_ini'=> $hebBulanIni,
            'hari_efektif_berjalan' => $hebBulanBerjalan,
        ];

        // Ambil Jadwal KBM Riil Hari Ini dari Master Jadwal
        $dayMap = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $hariIni = $dayMap[now()->format('l')] ?? 'Senin';

        $jadwal_hari_ini = [];
        $isLiburHariIni = ($statusHariIni['is_libur'] ?? false) || ($statusHariIni['libur_gtk'] ?? false);

        if (Schema::hasTable('jadwal_kbm')) {
            $jadwalRiil = \App\Models\JadwalKbm::where('is_active', true)
                ->where('hari', $hariIni)
                ->when($gtk, fn($q) => $q->where('ptk_id', $gtk->ptk_id))
                ->excludePkl()
                ->orderBy('jam_ke_mulai')
                ->get();

            if ($jadwalRiil->isNotEmpty()) {
                foreach ($jadwalRiil as $j) {
                    $rombelNama = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->value('nama') ?? $j->rombongan_belajar_id;
                    $jamRange = (!empty($j->jam_mulai) && !empty($j->jam_selesai))
                        ? substr($j->jam_mulai, 0, 5) . ' - ' . substr($j->jam_selesai, 0, 5)
                        : "Jam Ke {$j->jam_ke_mulai}-{$j->jam_ke_selesai}";

                    $jadwal_hari_ini[] = [
                        'jam'    => $jamRange,
                        'kelas'  => $rombelNama,
                        'mapel'  => $j->nama_mata_pelajaran ?: 'Mata Pelajaran',
                        'ruang'  => $j->ruangan ?: 'Ruang Kelas',
                        'status' => $isLiburHariIni ? 'Libur KBM' : 'Terjadwal'
                    ];
                }
            }
        }

        // Fallback jika belum ada jadwal KBM tersimpan
        if (empty($jadwal_hari_ini)) {
            if ($pembelajaran->isNotEmpty()) {
                foreach ($pembelajaran as $idx => $pem) {
                    $jadwal_hari_ini[] = [
                        'jam'    => sprintf('%02d:30 - %02d:00', 7 + ($idx * 2), 9 + ($idx * 2)),
                        'kelas'  => $pem->nama_rombel ?: 'Rombel',
                        'mapel'  => $pem->nama_mata_pelajaran ?: 'Mata Pelajaran',
                        'ruang'  => $pem->ruang ?: 'Ruang Kelas',
                        'status' => $isLiburHariIni ? 'Libur KBM' : ($idx === 0 ? 'Berlangsung' : 'Mendatang')
                    ];
                }
            }
        }

        return view('dashboard.guru', compact('stats', 'jadwal_hari_ini', 'gtk', 'statusHariIni', 'agendaHariIni', 'hariIni'));
    }

    public function tendik()
    {
        if ($res = $this->checkAuth('tendik')) return $res;
        if (!\App\Models\RolePermission::canAccess('tendik', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Tenaga Kependidikan', 'role' => 'tendik']);
        }

        $stats = [
            'total_surat_masuk'  => 14,
            'total_surat_keluar' => 8,
            'agenda_sekolah'     => 5,
            'buku_tamu_hari_ini' => 12,
            'presensi_masuk'     => '06:50 WIB',
            'status_presensi'    => 'Hadir Tepat Waktu'
        ];

        $administrasi_tugas = [
            ['nomor' => 'SRT/2026/09/012', 'kategori' => 'Surat Masuk', 'perihal' => 'Undangan Sosialisasi Kurikulum Dinas Pendidikan', 'pengirim' => 'Disdik Jabar', 'tgl' => '08 Sep 2026', 'status' => 'Sudah Didisposisi'],
            ['nomor' => 'SRT/2026/09/011', 'kategori' => 'Surat Keluar', 'perihal' => 'Pemberitahuan Ujian Tengah Semester Ganjil', 'pengirim' => 'Bagian Kurikulum', 'tgl' => '07 Sep 2026', 'status' => 'Selesai Dicetak'],
            ['nomor' => 'SRT/2026/09/010', 'kategori' => 'Surat Keterangan', 'perihal' => 'Keterangan Aktif Sekolah Peserta Didik (NISN: 008123456)', 'pengirim' => 'Tata Usaha', 'tgl' => '07 Sep 2026', 'status' => 'Menunggu TTD'],
            ['nomor' => 'INV/2026/09/004', 'kategori' => 'Inventaris TU', 'perihal' => 'Pengadaan Kertas & ATK Kantor Bulan September', 'pengirim' => 'Staf Sarpras', 'tgl' => '06 Sep 2026', 'status' => 'Proses Verifikasi'],
        ];

        return view('dashboard.tendik', compact('stats', 'administrasi_tugas'));
    }

    public function pesertaDidik()
    {
        if ($res = $this->checkAuth('peserta_didik')) return $res;
        if (!\App\Models\RolePermission::canAccess('peserta_didik', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Peserta Didik', 'role' => 'peserta_didik']);
        }

        $user = session('user');
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));

        $pd = null;
        if (Schema::hasTable('peserta_didik')) {
            $pd = $pdId ? DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first() : DB::table('peserta_didik')->where('nama', $userName)->first();
            if ($pd && Schema::hasTable('peserta_didik_meta')) {
                $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
                $pd->foto_url = $meta?->foto_url;
                $pd->foto_size = $meta?->formatted_foto_size;
            }
        }

        $pembelajaran = collect();
        if ($pd && !empty($pd->rombongan_belajar_id) && Schema::hasTable('pembelajaran')) {
            $pembelajaran = DB::table('pembelajaran')
                ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
                ->where('pembelajaran.rombongan_belajar_id', $pd->rombongan_belajar_id)
                ->select(
                    'pembelajaran.nama_mata_pelajaran',
                    'pembelajaran.jam_mengajar_per_minggu',
                    'gtk.nama as guru_pengampu'
                )
                ->get();
        }

        $stats = [
            'presensi_bulan_ini' => 100,
            'hadir_hari'         => 0,
            'izin_hari'          => 0,
            'sakit_hari'         => 0,
            'alpa_hari'          => 0,
            'poin_prestasi'      => 45,
            'poin_pelanggaran'   => 0
        ];

        $presensi_terakhir = [];

        if ($pd && Schema::hasTable('presensi_harian')) {
            $currentMonth = now()->format('Y-m');
            $riwayatBulanIni = \App\Models\PresensiHarian::where('peserta_didik_id', $pd->peserta_didik_id)
                ->where('tanggal', 'like', "{$currentMonth}%")
                ->get();

            $hadirCount = $riwayatBulanIni->whereIn('status', ['H', 'T'])->count();
            $izinCount = $riwayatBulanIni->where('status', 'I')->count();
            $sakitCount = $riwayatBulanIni->where('status', 'S')->count();
            $dispenCount = $riwayatBulanIni->where('status', 'D')->count();
            $totalSesi = $riwayatBulanIni->count();
            $totalKehadiran = $hadirCount + $dispenCount;

            // Hari Efektif Belajar bulan ini s/d hari ini dari Kalender Pendidikan
            $hebBulanIni = \App\Models\KalenderPendidikan::hitungHariEfektifBerjalan(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
                now()->toDateString(),
                'pd'
            );
            $denominator = $hebBulanIni > 0 ? $hebBulanIni : $totalSesi;
            $persen = $denominator > 0 ? min(100.0, round(($totalKehadiran / $denominator) * 100, 1)) : 100;

            $stats['presensi_bulan_ini'] = $persen;
            $stats['hari_efektif_bulan_ini'] = $hebBulanIni;
            $stats['hadir_hari'] = $hadirCount;
            $stats['izin_hari'] = $izinCount;
            $stats['sakit_hari'] = $sakitCount;
            $stats['dispen_hari'] = $dispenCount;
            $stats['alpa_hari'] = $alpaCount;

            // 5 Presensi Terakhir Riil
            $latestLogs = \App\Models\PresensiHarian::where('peserta_didik_id', $pd->peserta_didik_id)
                ->orderBy('tanggal', 'desc')
                ->limit(5)
                ->get();

            foreach ($latestLogs as $log) {
                $statusLabel = 'Hadir';
                $badgeColor = '#10b981';
                $badgeBg = 'rgba(16,185,129,0.15)';

                if ($log->status === 'T') {
                    $statusLabel = 'Terlambat' . ($log->menit_terlambat ? " +{$log->menit_terlambat}m" : '');
                    $badgeColor = '#f59e0b';
                    $badgeBg = 'rgba(245,158,11,0.15)';
                } elseif ($log->status === 'I') {
                    $statusLabel = 'Izin';
                    $badgeColor = 'var(--primary)';
                    $badgeBg = 'rgba(99,102,241,0.15)';
                } elseif ($log->status === 'S') {
                    $statusLabel = 'Sakit';
                    $badgeColor = '#8b5cf6';
                    $badgeBg = 'rgba(139,92,246,0.15)';
                } elseif ($log->status === 'D') {
                    $statusLabel = 'Dispen';
                    $badgeColor = 'var(--accent)';
                    $badgeBg = 'rgba(6,182,212,0.15)';
                } elseif ($log->status === 'A') {
                    $statusLabel = 'Alpha';
                    $badgeColor = '#ef4444';
                    $badgeBg = 'rgba(239,68,68,0.15)';
                } else {
                    $statusLabel = $log->metode_masuk === 'rfid' ? 'Hadir (Tap RFID)' : 'Hadir';
                }

                $jamMasukStr = $log->jam_masuk ? substr($log->jam_masuk, 0, 5) . ' WIB' : '--:--';
                $jamPulangStr = $log->jam_pulang ? substr($log->jam_pulang, 0, 5) . ' WIB' : '--:--';

                $presensi_terakhir[] = [
                    'tanggal'     => \Carbon\Carbon::parse($log->tanggal)->translatedFormat('d M Y'),
                    'jam_masuk'   => $jamMasukStr,
                    'jam_pulang'  => $jamPulangStr,
                    'status'      => $statusLabel,
                    'badge_color' => $badgeColor,
                    'badge_bg'    => $badgeBg,
                ];
            }
        }

        $jadwal_pelajaran = [];
        if ($pembelajaran->isNotEmpty()) {
            foreach ($pembelajaran as $idx => $pem) {
                $jadwal_pelajaran[] = [
                    'jam' => sprintf('%02d:30 - %02d:00', 7 + ($idx * 2), 9 + ($idx * 2)),
                    'mapel' => $pem->nama_mata_pelajaran ?: 'Mata Pelajaran',
                    'guru' => $pem->guru_pengampu ?: 'Guru Pengampu',
                    'ruang' => $pd->nama_rombel ?? 'Ruang Kelas'
                ];
            }
        } else {
            $jadwal_pelajaran = [
                ['jam' => '07:30 - 09:00', 'mapel' => 'Pemrograman Web & Mobile', 'guru' => 'Budi Santoso, S.Pd.', 'ruang' => 'Lab Komputer 2'],
                ['jam' => '09:15 - 10:45', 'mapel' => 'Bahasa Inggris Lanjut', 'guru' => 'Siti Nurhaliza, M.Pd.', 'ruang' => 'Ruang 12'],
                ['jam' => '11:00 - 12:30', 'mapel' => 'Pendidikan Pancasila', 'guru' => 'Drs. Hendro Wibowo', 'ruang' => 'Ruang 12'],
            ];
        }

        return view('dashboard.peserta-didik', compact('stats', 'presensi_terakhir', 'jadwal_pelajaran', 'pd'));
    }
}
