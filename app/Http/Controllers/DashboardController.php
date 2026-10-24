<?php

namespace App\Http\Controllers;

use App\Models\AdminAktivitas;
use App\Services\AdminDashboardStatsService;
use App\Services\GuruDashboardStatsService;
use App\Services\TendikDashboardStatsService;
use App\Services\UpdateService;
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
            // Superadmin (admin) memiliki kontrol penuh untuk mengakses seluruh dashboard peran
            if ($userRole === 'admin') {
                return null;
            }
            return redirect()->route('dashboard.' . $userRole);
        }
        return null;
    }

    public function admin(?Request $request = null)
    {
        $request = $request ?: request();
        if ($res = $this->checkAuth('admin')) return $res;
        if (!\App\Models\RolePermission::canAccess('admin', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Administrator', 'role' => 'admin']);
        }

        $viewData = AdminDashboardStatsService::getStats($request);

        return view('dashboard.admin', $viewData);
    }

    public function guru(?Request $request = null)
    {
        $request = $request ?: request();
        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        // Izinkan peran guru, admin (superadmin), atau tendik
        if ($userRole !== 'admin' && $userRole !== 'tendik') {
            if ($res = $this->checkAuth('guru')) return $res;
        } else {
            if ($res = $this->checkAuth()) return $res;
        }

        if ($userRole === 'guru' && !\App\Models\RolePermission::canAccess($user ?: 'guru', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Guru & Pendidik', 'role' => 'guru']);
        }

        $viewData = GuruDashboardStatsService::getStats($user, $request);

        return view('dashboard.guru', $viewData);
    }

    public function tendik(?Request $request = null)
    {
        $request = $request ?: request();

        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        $hasTendikDuty = TendikDashboardStatsService::hasTendikDuty($userId, $ptkId);

        if (!$hasTendikDuty && $userRole !== 'admin') {
            if ($res = $this->checkAuth('tendik')) return $res;
        } else {
            if ($res = $this->checkAuth()) return $res;
        }

        $viewData = TendikDashboardStatsService::getStats($user, $request);

        return view('dashboard.tendik', $viewData);
    }

    public function pesertaDidik(?Request $request = null)
    {
        $request = $request ?: request();
        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        // Izinkan peran peserta didik atau admin (superadmin full control)
        if ($userRole !== 'admin') {
            if ($res = $this->checkAuth('peserta_didik')) return $res;
        } else {
            if ($res = $this->checkAuth()) return $res;
        }

        if ($userRole === 'peserta_didik' && !\App\Models\RolePermission::canAccess($user ?: 'peserta_didik', 'menu_dashboard')) {
            return view('errors.dashboard-disabled', ['roleName' => 'Peserta Didik', 'role' => 'peserta_didik']);
        }

        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));

        // Dukungan pemilihan siswa untuk Superadmin
        $allPdList = collect();
        if ($userRole === 'admin' && Schema::hasTable('peserta_didik')) {
            $allPdList = DB::table('peserta_didik')
                ->select('peserta_didik_id', 'nama', 'nisn', 'nama_rombel')
                ->orderBy('nama')
                ->limit(50)
                ->get();
        }

        $reqPdId = $request->query('peserta_didik_id');
        if ($reqPdId && $userRole === 'admin') {
            $selectedPd = DB::table('peserta_didik')->where('peserta_didik_id', $reqPdId)->first();
            if ($selectedPd) {
                $pdId = $selectedPd->peserta_didik_id;
                $userName = $selectedPd->nama;
            }
        } elseif (!$pdId && $userRole === 'admin' && $allPdList->isNotEmpty()) {
            $firstPd = $allPdList->first();
            $pdId = $firstPd->peserta_didik_id;
            $userName = $firstPd->nama;
        }

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
            $alpaCount = $riwayatBulanIni->where('status', 'A')->count();
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
        $isJadwalDiberlakukan = \App\Models\JadwalPengaturan::isDiberlakukan();
        $modeAktif = \App\Models\JadwalPengaturan::getModeAktif();
        $pengaturanJadwal = \App\Models\JadwalPengaturan::getSettings();
        $hariAktifSekolah = $pengaturanJadwal->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $hariIni = match (now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
        $statusHariPd = \App\Models\KalenderPendidikan::getStatusHari(now()->toDateString(), 'pd');
        $isLiburHariIni = ($statusHariPd['is_libur'] ?? false) || !\App\Models\JadwalPengaturan::isHariAktif(now());

        if ($isJadwalDiberlakukan && $modeAktif && !$isLiburHariIni && !empty($pd->rombongan_belajar_id) && Schema::hasTable('jadwal_kbm')) {
            $jadwalSiswa = DB::table('jadwal_kbm as j')
                ->leftJoin('gtk as g', 'j.ptk_id', '=', 'g.ptk_id')
                ->where('j.rombongan_belajar_id', $pd->rombongan_belajar_id)
                ->where('j.sumber', $modeAktif)
                ->where('j.hari', $hariIni)
                ->where('j.is_active', true)
                ->orderBy('j.jam_ke_mulai', 'asc')
                ->orderBy('j.jam_mulai', 'asc')
                ->select('j.*', 'g.nama as nama_guru')
                ->get();

            $nowTime = now()->format('H:i');

            foreach ($jadwalSiswa as $js) {
                $jamMulai = $js->jam_mulai ? substr($js->jam_mulai, 0, 5) : null;
                $jamSelesai = $js->jam_selesai ? substr($js->jam_selesai, 0, 5) : null;
                $jamStr = ($jamMulai && $jamSelesai)
                    ? "{$jamMulai} - {$jamSelesai}"
                    : sprintf('Jam ke-%d s/d %d', $js->jam_ke_mulai, $js->jam_ke_selesai);

                $status = 'Mendatang';
                if ($jamMulai && $jamSelesai) {
                    if ($nowTime >= $jamMulai && $nowTime <= $jamSelesai) {
                        $status = 'Berlangsung';
                    } elseif ($nowTime > $jamSelesai) {
                        $status = 'Selesai';
                    }
                }

                $jadwal_pelajaran[] = [
                    'id'     => $js->id,
                    'jam'    => $jamStr,
                    'mapel'  => $js->nama_mata_pelajaran ?: 'Mata Pelajaran',
                    'guru'   => $js->nama_guru ?: 'Guru Pengampu',
                    'ruang'  => $js->ruangan ?: ($pd->nama_rombel ?? 'Ruang Kelas'),
                    'status' => $status,
                ];
            }
        }

        // HANYA jika jadwal KBM belum diberlakukan (masih draft) dan bukan libur, tampilkan estimasi sementara dari pembelajaran jika ada
        if (!$isJadwalDiberlakukan && !$isLiburHariIni && empty($jadwal_pelajaran) && $pembelajaran->isNotEmpty()) {
            foreach ($pembelajaran->take(4) as $idx => $pem) {
                $jadwal_pelajaran[] = [
                    'id'     => null,
                    'jam'    => sprintf('%02d:30 - %02d:00', 7 + ($idx * 2), 9 + ($idx * 2)),
                    'mapel'  => $pem->nama_mata_pelajaran ?: 'Mata Pelajaran',
                    'guru'   => $pem->guru_pengampu ?: 'Guru Pengampu',
                    'ruang'  => $pd->nama_rombel ?? 'Ruang Kelas',
                    'status' => 'Draft',
                ];
            }
        }

        // Ambil data koordinator jika peserta didik bertugas sebagai Koordinator Kelas (1 Unified Dashboard)
        $koordinatorInfo = \App\Models\RolePermission::isKoordinator($user) ? \App\Models\RolePermission::getWaliKelasRombelInfo($user) : null;
        $koordinatorStats = null;
        if ($koordinatorInfo && Schema::hasTable('peserta_didik')) {
            $rombelId = $koordinatorInfo->rombongan_belajar_id ?? null;
            if ($rombelId) {
                $totalSiswa = DB::table('peserta_didik')->where('rombongan_belajar_id', $rombelId)->count();
                $koordinatorStats = [
                    'rombel_nama' => $koordinatorInfo->nama,
                    'rombel_id'   => $rombelId,
                    'total_siswa' => $totalSiswa,
                ];
            }
        }

        return view('dashboard.peserta-didik', compact(
            'stats',
            'presensi_terakhir',
            'jadwal_pelajaran',
            'pd',
            'allPdList',
            'userRole',
            'userName',
            'koordinatorStats',
            'isJadwalDiberlakukan',
            'modeAktif',
            'hariIni'
        ));
    }

    /**
     * Helper resolusi data siswa & orang tua secara terpadu
     */
    private function resolveStudentContext(Request $request): array
    {
        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $parentName = 'Orang Tua / Wali';

        // Dukungan pemilihan siswa jika dibuka oleh Administrator
        $allPdList = collect();
        if ($userRole === 'admin' && Schema::hasTable('peserta_didik')) {
            $allPdList = DB::table('peserta_didik')
                ->select('peserta_didik_id', 'nama', 'nisn', 'nama_rombel')
                ->orderBy('nama')
                ->limit(60)
                ->get();

            $reqPdId = $request->query('peserta_didik_id');
            if ($reqPdId) {
                $pdId = $reqPdId;
            } elseif (!$pdId && $allPdList->isNotEmpty()) {
                $pdId = $allPdList->first()->peserta_didik_id;
            }
        }

        $pd = null;
        $waliKelas = null;

        if ($pdId && Schema::hasTable('peserta_didik')) {
            $pd = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
            if ($pd && Schema::hasTable('peserta_didik_meta')) {
                $meta = \App\Models\PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
                $pd->foto_url = $meta?->foto_url;
            }

            // Tentukan sapaan orang tua / wali: periksa status pekerjaan apakah 'Sudah Meninggal'
            if ($pd) {
                $ayahAlive = !empty($pd->nama_ayah) && !str_contains(strtolower($pd->pekerjaan_ayah_id_str ?? ''), 'meninggal');
                $ibuAlive = !empty($pd->nama_ibu) && !str_contains(strtolower($pd->pekerjaan_ibu_id_str ?? ''), 'meninggal');
                $waliAlive = !empty($pd->nama_wali) && !str_contains(strtolower($pd->pekerjaan_wali_id_str ?? ''), 'meninggal');

                if ($ayahAlive && $ibuAlive) {
                    $parentName = 'Bpk. ' . $pd->nama_ayah . ' & Ibu ' . $pd->nama_ibu;
                } elseif ($ayahAlive) {
                    $parentName = 'Bpk. ' . $pd->nama_ayah;
                } elseif ($ibuAlive) {
                    $parentName = 'Ibu ' . $pd->nama_ibu;
                } elseif ($waliAlive) {
                    $parentName = 'Bpk/Ibu ' . $pd->nama_wali;
                }
            }

            // Informasi Wali Kelas & Jurusan
            if ($pd && !empty($pd->rombongan_belajar_id) && Schema::hasTable('rombongan_belajar')) {
                $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id)->first();
                if ($rombel) {
                    $waliKelas = [
                        'nama'    => $rombel->ptk_id_str ?: 'Wali Kelas',
                        'rombel'  => $rombel->nama,
                        'jurusan' => $rombel->jurusan_id_str ?: ($pd->kurikulum_id_str ?? 'Reguler'),
                    ];
                }
            }
        }

        return compact('pd', 'pdId', 'parentName', 'waliKelas', 'userRole', 'allPdList');
    }

    /**
     * Portal & Dashboard Khusus Orang Tua / Wali Murid
     */
    public function orangTua(?Request $request = null)
    {
        $request = $request ?: request();
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $ctx = $this->resolveStudentContext($request);
        extract($ctx);

        if ($userRole !== 'admin' && $userRole !== 'orang_tua') {
            return redirect()->route('dashboard.' . ($userRole ?: 'peserta-didik'));
        }

        // Stats Ringkasan
        $currentMonth = now()->format('Y-m');
        $today = now()->toDateString();
        $statsHarian = [
            'persen'              => 100,
            'hari_efektif'        => 0,
            'total_sesi'          => 0,
            'hadir'               => 0,
            'terlambat'           => 0,
            'izin'                => 0,
            'sakit'               => 0,
            'dispen'              => 0,
            'alpha'               => 0,
            'status_hari_ini'     => 'Belum Ada Data',
            'jam_masuk_hari_ini'  => '--:--',
            'jam_pulang_hari_ini' => '--:--',
        ];

        if ($pd && Schema::hasTable('presensi_harian')) {
            $bulanIniLogs = \App\Models\PresensiHarian::where('peserta_didik_id', $pd->peserta_didik_id)
                ->where('tanggal', 'like', "{$currentMonth}%")
                ->get();

            $hadirCount = $bulanIniLogs->where('status', 'H')->count();
            $terlambatCount = $bulanIniLogs->where('status', 'T')->count();
            $izinCount = $bulanIniLogs->where('status', 'I')->count();
            $sakitCount = $bulanIniLogs->where('status', 'S')->count();
            $dispenCount = $bulanIniLogs->where('status', 'D')->count();
            $alphaCount = $bulanIniLogs->where('status', 'A')->count();
            $totalSesi = $bulanIniLogs->count();
            $totalKehadiran = $hadirCount + $terlambatCount + $dispenCount;

            $heb = \App\Models\KalenderPendidikan::hitungHariEfektifBerjalan(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
                now()->toDateString(),
                'pd'
            );
            $denominator = $heb > 0 ? $heb : ($totalSesi ?: 1);
            $persen = round(($totalKehadiran / $denominator) * 100, 1);

            $statsHarian['persen'] = min(100, $persen);
            $statsHarian['hari_efektif'] = $heb;
            $statsHarian['total_sesi'] = $totalSesi;
            $statsHarian['hadir'] = $hadirCount;
            $statsHarian['terlambat'] = $terlambatCount;
            $statsHarian['izin'] = $izinCount;
            $statsHarian['sakit'] = $sakitCount;
            $statsHarian['dispen'] = $dispenCount;
            $statsHarian['alpha'] = $alphaCount;

            $todayLog = $bulanIniLogs->firstWhere('tanggal', $today);
            if ($todayLog) {
                $statsHarian['jam_masuk_hari_ini'] = $todayLog->jam_masuk ? substr($todayLog->jam_masuk, 0, 5) . ' WIB' : '--:--';
                $statsHarian['jam_pulang_hari_ini'] = $todayLog->jam_pulang ? substr($todayLog->jam_pulang, 0, 5) . ' WIB' : '--:--';
                $statsHarian['status_hari_ini'] = match ($todayLog->status) {
                    'H' => 'Hadir Tepat Waktu',
                    'T' => 'Terlambat (' . ($todayLog->menit_terlambat ?: 0) . ' mnt)',
                    'I' => 'Izin Resmi',
                    'S' => 'Sakit',
                    'D' => 'Dispensasi',
                    'A' => 'Alpha / Tanpa Keterangan',
                    default => 'Hadir'
                };
            }
        }

        $totalMapelSesi = 0;
        if ($pd && Schema::hasTable('presensi_mapel')) {
            $totalMapelSesi = DB::table('presensi_mapel')
                ->where(function ($q) use ($pd) {
                    $q->where('peserta_didik_id', $pd->peserta_didik_id);
                    if (!empty($pd->nisn)) {
                        $q->orWhere('nisn', $pd->nisn);
                    }
                })
                ->count();
        }

        $totalIzinSiswa = 0;
        if ($pd && Schema::hasTable('peserta_didik_izin_keluar')) {
            $totalIzinSiswa += DB::table('peserta_didik_izin_keluar')->where('peserta_didik_id', $pd->peserta_didik_id)->count();
        }
        if ($pd && Schema::hasTable('presensi_izin')) {
            $totalIzinSiswa += DB::table('presensi_izin')->where('peserta_didik_id', $pd->peserta_didik_id)->count();
        }

        // KUMPULKAN SEMUA TRANSAKSI AKTIVITAS SISWA (Point 2)
        $transaksi = collect();

        // 1. Transaksi Presensi Harian
        if ($pd && Schema::hasTable('presensi_harian')) {
            $harianList = DB::table('presensi_harian')->where('peserta_didik_id', $pd->peserta_didik_id)->orderBy('tanggal', 'desc')->limit(20)->get();
            foreach ($harianList as $h) {
                $statusStr = match ($h->status) {
                    'H' => 'Hadir',
                    'T' => 'Terlambat (' . ($h->menit_terlambat ?: 0) . ' mnt)',
                    'I' => 'Izin',
                    'S' => 'Sakit',
                    'D' => 'Dispensasi',
                    'A' => 'Alpha',
                    default => $h->status,
                };
                $badgeBg = match ($h->status) {
                    'H' => 'rgba(16,185,129,0.15)',
                    'T' => 'rgba(245,158,11,0.15)',
                    'A' => 'rgba(239,68,68,0.15)',
                    default => 'rgba(59,130,246,0.15)',
                };
                $badgeColor = match ($h->status) {
                    'H' => '#10b981',
                    'T' => '#f59e0b',
                    'A' => '#ef4444',
                    default => '#3b82f6',
                };
                $waktuDisplay = \Carbon\Carbon::parse($h->tanggal)->translatedFormat('d M Y');
                if ($h->jam_masuk) {
                    $waktuDisplay .= ' &bull; ' . substr($h->jam_masuk, 0, 5) . ' WIB';
                }

                $transaksi->push([
                    'waktu_sort'    => $h->tanggal . ' ' . ($h->jam_masuk ?: '00:00:00'),
                    'waktu_display' => $waktuDisplay,
                    'kategori'      => 'Presensi Gerbang (RFID)',
                    'kategori_icon' => 'fa-id-card-clip text-primary',
                    'judul'         => $h->status === 'T' ? "Presensi Masuk Terlambat ({$h->menit_terlambat} mnt)" : ($h->status === 'H' ? 'Presensi Masuk Tepat Waktu' : "Presensi Harian: {$statusStr}"),
                    'deskripsi'     => 'Metode: ' . strtoupper($h->metode_masuk ?: 'RFID') . ($h->jam_pulang ? ' &bull; Pulang: ' . substr($h->jam_pulang, 0, 5) . ' WIB' : ''),
                    'status'        => $statusStr,
                    'badge_bg'      => $badgeBg,
                    'badge_color'   => $badgeColor,
                    'petugas'       => $h->verified_by ?: 'Terminal Gerbang',
                ]);
            }
        }

        // 2. Transaksi Presensi Mapel KBM
        if ($pd && Schema::hasTable('presensi_mapel')) {
            $mapelList = DB::table('presensi_mapel as pm')
                ->leftJoin('gtk as g', 'pm.ptk_id', '=', 'g.ptk_id')
                ->where(function ($q) use ($pd) {
                    $q->where('pm.peserta_didik_id', $pd->peserta_didik_id);
                    if (!empty($pd->nisn)) {
                        $q->orWhere('pm.nisn', $pd->nisn);
                    }
                })
                ->select('pm.*', 'g.nama as nama_guru')
                ->orderBy('pm.tanggal', 'desc')
                ->orderBy('pm.jam_ke', 'desc')
                ->limit(20)
                ->get();

            foreach ($mapelList as $m) {
                $statusMapelStr = match ($m->status) {
                    'H' => 'Hadir KBM',
                    'T' => 'Terlambat KBM',
                    'I' => 'Izin',
                    'S' => 'Sakit',
                    'A' => 'Alpha',
                    default => $m->status,
                };
                $badgeBg = match ($m->status) {
                    'H' => 'rgba(16,185,129,0.15)',
                    'T' => 'rgba(245,158,11,0.15)',
                    'A' => 'rgba(239,68,68,0.15)',
                    default => 'rgba(59,130,246,0.15)',
                };
                $badgeColor = match ($m->status) {
                    'H' => '#10b981',
                    'T' => '#f59e0b',
                    'A' => '#ef4444',
                    default => '#3b82f6',
                };
                $waktuDisplay = \Carbon\Carbon::parse($m->tanggal)->translatedFormat('d M Y') . ' &bull; Jam ke-' . $m->jam_ke;

                $transaksi->push([
                    'waktu_sort'    => $m->tanggal . ' ' . sprintf('%02d:00:00', 7 + ((int) $m->jam_ke)),
                    'waktu_display' => $waktuDisplay,
                    'kategori'      => 'Presensi Mapel (KBM)',
                    'kategori_icon' => 'fa-book-open text-success',
                    'judul'         => 'KBM: ' . $m->nama_mata_pelajaran,
                    'deskripsi'     => 'Guru: ' . ($m->nama_guru ?: 'Guru Pengampu') . ($m->keterangan ? ' &bull; ' . $m->keterangan : ''),
                    'status'        => $statusMapelStr,
                    'badge_bg'      => $badgeBg,
                    'badge_color'   => $badgeColor,
                    'petugas'       => $m->nama_guru ?: 'Guru Pengampu',
                ]);
            }
        }

        // 3. Transaksi e-Izin Keluar-Masuk
        if ($pd && Schema::hasTable('peserta_didik_izin_keluar')) {
            $izinList = DB::table('peserta_didik_izin_keluar as iz')
                ->leftJoin('gtk as p', 'iz.petugas_piket_ptk_id', '=', 'p.ptk_id')
                ->where('iz.peserta_didik_id', $pd->peserta_didik_id)
                ->select('iz.*', 'p.nama as nama_piket')
                ->orderBy('iz.tanggal', 'desc')
                ->limit(15)
                ->get();

            foreach ($izinList as $iz) {
                $waktuDisplay = \Carbon\Carbon::parse($iz->tanggal)->translatedFormat('d M Y') . ($iz->jam_izin_keluar ? ' &bull; ' . substr($iz->jam_izin_keluar, 0, 5) . ' WIB' : '');
                $statusIzinStr = match ($iz->status) {
                    'disetujui'          => 'Disetujui Piket',
                    'di_luar'            => 'Di Luar Sekolah',
                    'kembali', 'selesai' => 'Kembali Masuk',
                    'ditolak'            => 'Ditolak',
                    default              => ucfirst(str_replace('_', ' ', $iz->status)),
                };

                $transaksi->push([
                    'waktu_sort'    => $iz->tanggal . ' ' . ($iz->jam_izin_keluar ?: '08:00:00'),
                    'waktu_display' => $waktuDisplay,
                    'kategori'      => 'e-Izin Gerbang',
                    'kategori_icon' => 'fa-ticket-alt text-warning',
                    'judul'         => 'e-Izin Keluar: ' . $iz->alasan,
                    'deskripsi'     => 'Tiket: ' . $iz->nomor_tiket . ' &bull; Tujuan: ' . ($iz->tujuan_lokasi ?: '-') . ($iz->jam_keluar_aktual ? ' &bull; Keluar: ' . substr($iz->jam_keluar_aktual, 0, 5) : ''),
                    'status'        => $statusIzinStr,
                    'badge_bg'      => 'rgba(245,158,11,0.15)',
                    'badge_color'   => '#f59e0b',
                    'petugas'       => $iz->nama_piket ?: 'Petugas Piket',
                ]);
            }
        }

        // 4. Transaksi Surat Izin Sakit
        if ($pd && Schema::hasTable('presensi_izin')) {
            $suratList = DB::table('presensi_izin')->where('peserta_didik_id', $pd->peserta_didik_id)->orderBy('tanggal_mulai', 'desc')->limit(10)->get();
            foreach ($suratList as $si) {
                $waktuDisplay = \Carbon\Carbon::parse($si->tanggal_mulai)->translatedFormat('d M Y');
                $statusSuratStr = match (strtolower($si->status ?? 'menunggu')) {
                    'disetujui' => 'Disetujui Wali Kelas',
                    'ditolak'   => 'Ditolak',
                    default     => 'Menunggu Verifikasi',
                };

                $transaksi->push([
                    'waktu_sort'    => $si->tanggal_mulai . ' 00:00:00',
                    'waktu_display' => $waktuDisplay,
                    'kategori'      => 'Surat Permohonan Izin',
                    'kategori_icon' => 'fa-envelope-open-text text-info',
                    'judul'         => 'Surat Izin: ' . ucfirst(strtolower($si->jenis ?? 'Izin')),
                    'deskripsi'     => 'Alasan: ' . ($si->alasan ?? ($si->keterangan ?? '-')),
                    'status'        => $statusSuratStr,
                    'badge_bg'      => strtolower($si->status ?? '') === 'disetujui' ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)',
                    'badge_color'   => strtolower($si->status ?? '') === 'disetujui' ? '#10b981' : '#f59e0b',
                    'petugas'       => $si->disetujui_oleh ?: 'Wali Kelas',
                ]);
            }
        }

        $semuaTransaksi = $transaksi->sortByDesc('waktu_sort')->values();

        return view('dashboard.orang-tua', compact(
            'pd',
            'parentName',
            'waliKelas',
            'userRole',
            'allPdList',
            'statsHarian',
            'totalMapelSesi',
            'totalIzinSiswa',
            'semuaTransaksi'
        ));
    }

    /**
     * Halaman Khusus Riwayat Kehadiran Siswa untuk Orang Tua
     */
    public function orangTuaKehadiran(?Request $request = null)
    {
        $request = $request ?: request();
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $ctx = $this->resolveStudentContext($request);
        extract($ctx);

        if ($userRole !== 'admin' && $userRole !== 'orang_tua') {
            return redirect()->route('dashboard.' . ($userRole ?: 'peserta-didik'));
        }

        $currentMonth = now()->format('Y-m');
        $today = now()->toDateString();
        $statsHarian = [
            'persen'              => 100,
            'hari_efektif'        => 0,
            'total_sesi'          => 0,
            'hadir'               => 0,
            'terlambat'           => 0,
            'izin'                => 0,
            'sakit'               => 0,
            'dispen'              => 0,
            'alpha'               => 0,
            'status_hari_ini'     => 'Belum Ada Data',
            'jam_masuk_hari_ini'  => '--:--',
            'jam_pulang_hari_ini' => '--:--',
        ];
        $riwayatHarian = [];

        if ($pd && Schema::hasTable('presensi_harian')) {
            $bulanIniLogs = \App\Models\PresensiHarian::where('peserta_didik_id', $pd->peserta_didik_id)
                ->where('tanggal', 'like', "{$currentMonth}%")
                ->get();

            $hadirCount = $bulanIniLogs->where('status', 'H')->count();
            $terlambatCount = $bulanIniLogs->where('status', 'T')->count();
            $izinCount = $bulanIniLogs->where('status', 'I')->count();
            $sakitCount = $bulanIniLogs->where('status', 'S')->count();
            $dispenCount = $bulanIniLogs->where('status', 'D')->count();
            $alphaCount = $bulanIniLogs->where('status', 'A')->count();
            $totalSesi = $bulanIniLogs->count();
            $totalKehadiran = $hadirCount + $terlambatCount + $dispenCount;

            $heb = \App\Models\KalenderPendidikan::hitungHariEfektifBerjalan(
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
                now()->toDateString(),
                'pd'
            );
            $denominator = $heb > 0 ? $heb : ($totalSesi ?: 1);
            $persen = round(($totalKehadiran / $denominator) * 100, 1);

            $statsHarian['persen'] = min(100, $persen);
            $statsHarian['hari_efektif'] = $heb;
            $statsHarian['total_sesi'] = $totalSesi;
            $statsHarian['hadir'] = $hadirCount;
            $statsHarian['terlambat'] = $terlambatCount;
            $statsHarian['izin'] = $izinCount;
            $statsHarian['sakit'] = $sakitCount;
            $statsHarian['dispen'] = $dispenCount;
            $statsHarian['alpha'] = $alphaCount;

            $todayLog = $bulanIniLogs->firstWhere('tanggal', $today);
            if ($todayLog) {
                $statsHarian['jam_masuk_hari_ini'] = $todayLog->jam_masuk ? substr($todayLog->jam_masuk, 0, 5) . ' WIB' : '--:--';
                $statsHarian['jam_pulang_hari_ini'] = $todayLog->jam_pulang ? substr($todayLog->jam_pulang, 0, 5) . ' WIB' : '--:--';
                $statsHarian['status_hari_ini'] = match ($todayLog->status) {
                    'H' => 'Hadir Tepat Waktu',
                    'T' => 'Terlambat (' . ($todayLog->menit_terlambat ?: 0) . ' mnt)',
                    'I' => 'Izin Resmi',
                    'S' => 'Sakit',
                    'D' => 'Dispensasi',
                    'A' => 'Alpha / Tanpa Keterangan',
                    default => 'Hadir'
                };
            }

            $latestHarian = \App\Models\PresensiHarian::where('peserta_didik_id', $pd->peserta_didik_id)
                ->orderBy('tanggal', 'desc')
                ->limit(30)
                ->get();

            foreach ($latestHarian as $lh) {
                $statusLabel = 'Hadir';
                $badgeBg = 'rgba(16,185,129,0.15)';
                $badgeColor = '#10b981';

                if ($lh->status === 'T') {
                    $statusLabel = 'Terlambat' . ($lh->menit_terlambat ? " (+{$lh->menit_terlambat}m)" : '');
                    $badgeBg = 'rgba(245,158,11,0.15)';
                    $badgeColor = '#f59e0b';
                } elseif ($lh->status === 'I') {
                    $statusLabel = 'Izin';
                    $badgeBg = 'rgba(59,130,246,0.15)';
                    $badgeColor = '#3b82f6';
                } elseif ($lh->status === 'S') {
                    $statusLabel = 'Sakit';
                    $badgeBg = 'rgba(139,92,246,0.15)';
                    $badgeColor = '#8b5cf6';
                } elseif ($lh->status === 'D') {
                    $statusLabel = 'Dispensasi';
                    $badgeBg = 'rgba(6,182,212,0.15)';
                    $badgeColor = '#06b6d4';
                } elseif ($lh->status === 'A') {
                    $statusLabel = 'Alpha';
                    $badgeBg = 'rgba(239,68,68,0.15)';
                    $badgeColor = '#ef4444';
                }

                $riwayatHarian[] = [
                    'tanggal'     => \Carbon\Carbon::parse($lh->tanggal)->translatedFormat('l, d M Y'),
                    'jam_masuk'   => $lh->jam_masuk ? substr($lh->jam_masuk, 0, 5) . ' WIB' : '--:--',
                    'jam_pulang'  => $lh->jam_pulang ? substr($lh->jam_pulang, 0, 5) . ' WIB' : '--:--',
                    'status'      => $statusLabel,
                    'badge_bg'    => $badgeBg,
                    'badge_color' => $badgeColor,
                    'metode'      => strtoupper($lh->metode_masuk ?: 'Manual/RFID'),
                ];
            }
        }

        // Stats & Riwayat Presensi Mapel
        $riwayatMapel = collect();
        $statsMapel = [
            'total' => 0,
            'hadir' => 0,
            'izin'  => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        if ($pd && Schema::hasTable('presensi_mapel')) {
            $mapelLogs = DB::table('presensi_mapel as pm')
                ->leftJoin('gtk as g', 'pm.ptk_id', '=', 'g.ptk_id')
                ->where(function ($q) use ($pd) {
                    $q->where('pm.peserta_didik_id', $pd->peserta_didik_id);
                    if (!empty($pd->nisn)) {
                        $q->orWhere('pm.nisn', $pd->nisn);
                    }
                })
                ->orderBy('pm.tanggal', 'desc')
                ->orderBy('pm.jam_ke', 'asc')
                ->select('pm.*', 'g.nama as nama_guru')
                ->limit(50)
                ->get();

            $statsMapel['total'] = $mapelLogs->count();
            $statsMapel['hadir'] = $mapelLogs->whereIn('status', ['H', 'T', 'D'])->count();
            $statsMapel['izin']  = $mapelLogs->where('status', 'I')->count();
            $statsMapel['sakit'] = $mapelLogs->where('status', 'S')->count();
            $statsMapel['alpha'] = $mapelLogs->where('status', 'A')->count();
            $riwayatMapel = $mapelLogs;
        }

        return view('dashboard.orang-tua-kehadiran', compact(
            'pd',
            'parentName',
            'waliKelas',
            'userRole',
            'allPdList',
            'statsHarian',
            'riwayatHarian',
            'statsMapel',
            'riwayatMapel'
        ));
    }

    /**
     * Halaman Khusus e-Izin & Surat Sakit Siswa untuk Orang Tua
     */
    public function orangTuaIzin(?Request $request = null)
    {
        $request = $request ?: request();
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $ctx = $this->resolveStudentContext($request);
        extract($ctx);

        if ($userRole !== 'admin' && $userRole !== 'orang_tua') {
            return redirect()->route('dashboard.' . ($userRole ?: 'peserta-didik'));
        }

        $izinKeluarList = collect();
        $suratIzinList = collect();

        if ($pd && Schema::hasTable('peserta_didik_izin_keluar')) {
            $izinKeluarList = DB::table('peserta_didik_izin_keluar as iz')
                ->leftJoin('gtk as p', 'iz.petugas_piket_ptk_id', '=', 'p.ptk_id')
                ->where('iz.peserta_didik_id', $pd->peserta_didik_id)
                ->orderBy('iz.tanggal', 'desc')
                ->orderBy('iz.created_at', 'desc')
                ->select('iz.*', 'p.nama as nama_piket')
                ->limit(40)
                ->get();
        }

        if ($pd && Schema::hasTable('presensi_izin')) {
            $suratIzinList = DB::table('presensi_izin')
                ->where('peserta_didik_id', $pd->peserta_didik_id)
                ->orderBy('tanggal_mulai', 'desc')
                ->limit(30)
                ->get();
        }

        return view('dashboard.orang-tua-izin', compact(
            'pd',
            'parentName',
            'waliKelas',
            'userRole',
            'allPdList',
            'izinKeluarList',
            'suratIzinList'
        ));
    }

    /**
     * Halaman Formulir Identitas Lengkap Peserta Didik (Untuk Peserta Didik & Orang Tua)
     */
    public function identitasSiswa(?Request $request = null)
    {
        $request = $request ?: request();
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $ctx = $this->resolveStudentContext($request);
        extract($ctx);

        $allowedRoles = ['admin', 'peserta_didik', 'orang_tua', 'guru', 'tendik'];
        if (!in_array($userRole, $allowedRoles, true)) {
            return redirect()->route('dashboard.' . ($userRole ?: 'peserta-didik'));
        }

        return view('dashboard.peserta-didik-identitas', compact(
            'pd',
            'parentName',
            'waliKelas',
            'userRole',
            'allPdList'
        ));
    }
}
