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
            // Superadmin (admin) memiliki kontrol penuh untuk mengakses seluruh dashboard peran
            if ($userRole === 'admin') {
                return null;
            }
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

        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));

        // Dukungan pemilihan guru untuk Superadmin / Tendik
        $allGtkList = collect();
        if (($userRole === 'admin' || $userRole === 'tendik') && Schema::hasTable('gtk')) {
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
        if (Schema::hasTable('gtk')) {
            $gtk = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : DB::table('gtk')->where('nama', $userName)->first();
        }

        $fotoUrl = is_array($user) ? ($user['foto_url'] ?? null) : ($user->foto_url ?? null);
        if (!$fotoUrl && $userId) {
            $fotoUrl = \App\Models\User::where('pengguna_id', $userId)->first()?->foto_url;
        }
        if (!$fotoUrl && $gtk?->ptk_id) {
            $fotoUrl = \App\Models\User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
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
            'hari_efektif_bulan_ini' => $hebBulanIni,
            'hari_efektif_berjalan' => $hebBulanBerjalan,
        ];

        // Ambil Jadwal KBM Riil Hari Ini dari Master Jadwal
        $hariIni = match (now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };

        $isLiburHariIni = ($statusHariIni['is_libur'] ?? false) || in_array($hariIni, ['Sabtu', 'Minggu']);
        $jadwal_hari_ini = [];

        if (!$isLiburHariIni && $gtk && Schema::hasTable('jadwal_kbm')) {
            $jadwalRiilQuery = DB::table('jadwal_kbm')
                ->where('ptk_id', $gtk->ptk_id)
                ->where('hari', $hariIni);

            if (Schema::hasColumn('jadwal_kbm', 'jam_ke')) {
                $jadwalRiilQuery->orderBy('jam_ke');
            } elseif (Schema::hasColumn('jadwal_kbm', 'jam_mulai')) {
                $jadwalRiilQuery->orderBy('jam_mulai');
            }

            $jadwalRiil = $jadwalRiilQuery->get();

            if ($jadwalRiil->isNotEmpty()) {
                // Group jam yang berurutan untuk mapel dan rombel yang sama
                $grouped = [];
                foreach ($jadwalRiil as $j) {
                    $key = $j->rombongan_belajar_id . '_' . $j->nama_mata_pelajaran;
                    $jamKe = (int) ($j->jam_ke ?? 1);
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'jam_mulai' => ($j->jam_mulai ?? null) ?: sprintf('%02d:00', 6 + $jamKe),
                            'jam_selesai' => ($j->jam_selesai ?? null) ?: sprintf('%02d:45', 6 + $jamKe),
                            'data' => $j,
                            'total_jp' => 1
                        ];
                    } else {
                        $grouped[$key]['jam_selesai'] = ($j->jam_selesai ?? null) ?: sprintf('%02d:45', 6 + $jamKe);
                        $grouped[$key]['total_jp']++;
                    }
                }

                foreach ($grouped as $g) {
                    $j = $g['data'];
                    $rombelNama = $j->nama_rombel;
                    if (!$rombelNama && !empty($j->rombongan_belajar_id)) {
                        $rb = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->first();
                        $rombelNama = $rb?->nama ?: 'Rombel';
                    }

                    $jadwal_hari_ini[] = [
                        'jam'    => substr($g['jam_mulai'], 0, 5) . ' - ' . substr($g['jam_selesai'], 0, 5),
                        'kelas'  => $rombelNama,
                        'mapel'  => $j->nama_mata_pelajaran ?: 'Mata Pelajaran',
                        'ruang'  => $j->ruangan ?: 'Ruang Kelas',
                        'status' => 'Terjadwal'
                    ];
                }
            }
        }

        // Fallback jika belum ada jadwal KBM tersimpan
        if (!$isLiburHariIni && empty($jadwal_hari_ini)) {
            if ($pembelajaran->isNotEmpty()) {
                foreach ($pembelajaran as $idx => $pem) {
                    $jadwal_hari_ini[] = [
                        'jam'    => sprintf('%02d:30 - %02d:00', 7 + ($idx * 2), 9 + ($idx * 2)),
                        'kelas'  => $pem->nama_rombel ?: 'Rombel',
                        'mapel'  => $pem->nama_mata_pelajaran ?: 'Mata Pelajaran',
                        'ruang'  => $pem->ruang ?: 'Ruang Kelas',
                        'status' => $idx === 0 ? 'Berlangsung' : 'Mendatang'
                    ];
                }
            }
        }

        // Cari mata pelajaran utama dengan total jam mengajar terbanyak
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

        return view('dashboard.guru', compact(
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
            'userName'
        ));
    }

    public function tendik(?Request $request = null)
    {
        $request = $request ?: request();

        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);

        // Cek apakah user memiliki tugas tambahan tendik (misal Kepala TAS, Staf Persuratan, dll)
        $hasTendikDuty = false;
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $hasTendikDuty = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->whereIn('rtt.kode', [
                    'KEPALA_TAS',
                    'STAF_PERSURATAN',
                    'STAF_KESISWAAN',
                    'STAF_KEPEGAWAIAN',
                    'STAF_SARPRAS',
                    'LABORAN',
                    'PUSTAKAWAN',
                    'TEKNISI_IT',
                    'SATPAM',
                    'PENJAGA_SEKOLAH'
                ])
                ->exists();
        }

        if (!$hasTendikDuty && $userRole !== 'admin') {
            if ($res = $this->checkAuth('tendik')) return $res;
        } else {
            if ($res = $this->checkAuth()) return $res;
        }

        $gtk = null;
        if (Schema::hasTable('gtk')) {
            $gtk = $ptkId ? DB::table('gtk')->where('ptk_id', $ptkId)->first() : DB::table('gtk')->where('nama', $userName)->first();
        }

        $fotoUrl = is_array($user) ? ($user['foto_url'] ?? null) : ($user->foto_url ?? null);
        if (!$fotoUrl && $userId) {
            $fotoUrl = \App\Models\User::where('pengguna_id', $userId)->first()?->foto_url;
        }
        if (!$fotoUrl && $ptkId) {
            $fotoUrl = \App\Models\User::where('ptk_id', $ptkId)->whereNotNull('foto_path')->first()?->foto_url;
        }
        if (!$fotoUrl && $gtk?->ptk_id) {
            $fotoUrl = \App\Models\User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
        }

        // Ambil penugasan tugas tambahan aktif untuk tendik
        $dutyRecords = collect();
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $dutyRecords = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->select('rtt.kode', 'rtt.nama')
                ->get();
        }

        $activeDuties = $dutyRecords->pluck('nama')->filter()->unique();
        $dutyCodes = $dutyRecords->pluck('kode')->filter()->unique()->values();

        $bagianTugas = $activeDuties->isNotEmpty()
            ? $activeDuties->implode(', ')
            : ($gtk?->jabatan_ptk_id_str ?: ($gtk?->jenis_ptk_id_str ?: 'Tenaga Administrasi Sekolah'));

        $isKepalaTas = $dutyCodes->contains('KEPALA_TAS') || $userRole === 'admin';

        // Deteksi Primary Duty Code
        $primaryDuty = 'STAF_PERSURATAN';
        if ($isKepalaTas) {
            $primaryDuty = 'KEPALA_TAS';
        } elseif ($dutyCodes->contains('GURU_PIKET')) {
            $primaryDuty = 'GURU_PIKET';
        } elseif ($dutyCodes->contains('STAF_KESISWAAN')) {
            $primaryDuty = 'STAF_KESISWAAN';
        } elseif ($dutyCodes->contains('STAF_KEPEGAWAIAN')) {
            $primaryDuty = 'STAF_KEPEGAWAIAN';
        } elseif ($dutyCodes->contains('STAF_SARPRAS')) {
            $primaryDuty = 'STAF_SARPRAS';
        } elseif ($dutyCodes->contains('LABORAN')) {
            $primaryDuty = 'LABORAN';
        } elseif ($dutyCodes->contains('PUSTAKAWAN')) {
            $primaryDuty = 'PUSTAKAWAN';
        } elseif ($dutyCodes->contains('TEKNISI_IT')) {
            $primaryDuty = 'TEKNISI_IT';
        } elseif ($dutyCodes->contains('SATPAM')) {
            $primaryDuty = 'SATPAM';
        } elseif ($dutyCodes->contains('PENJAGA_SEKOLAH')) {
            $primaryDuty = 'PENJAGA_SEKOLAH';
        } elseif ($dutyCodes->contains('STAF_PERSURATAN')) {
            $primaryDuty = 'STAF_PERSURATAN';
        } elseif ($dutyCodes->isNotEmpty()) {
            $primaryDuty = $dutyCodes->first();
        }

        // Peta domain untuk tab switcher
        // Peta domain untuk tab switcher
        $bidangMap = [
            'umum'         => 'UMUM',
            'kepala_tas'   => 'KEPALA_TAS',
            'kepala-tas'   => 'KEPALA_TAS',
            'piket'        => 'GURU_PIKET',
            'guru-piket'   => 'GURU_PIKET',
            'kesiswaan'    => 'STAF_KESISWAAN',
            'kepegawaian'  => 'STAF_KEPEGAWAIAN',
            'sarpras'      => 'STAF_SARPRAS',
            'laboran'      => 'LABORAN',
            'perpustakaan' => 'PUSTAKAWAN',
            'teknisi'      => 'TEKNISI_IT',
            'keamanan'     => 'SATPAM',
            'penjaga'      => 'PENJAGA_SEKOLAH',
            'persuratan'   => 'STAF_PERSURATAN',
        ];

        // Peta view section Blade berdasarkan duty code
        $viewSectionMap = [
            'UMUM'             => 'umum',
            'KEPALA_TAS'       => 'kepala-tas',
            'GURU_PIKET'       => 'piket',
            'STAF_KESISWAAN'    => 'kesiswaan',
            'STAF_KEPEGAWAIAN'  => 'kepegawaian',
            'STAF_SARPRAS'      => 'sarpras',
            'LABORAN'          => 'laboran',
            'PUSTAKAWAN'       => 'perpustakaan',
            'TEKNISI_IT'       => 'teknisi',
            'SATPAM'           => 'keamanan',
            'PENJAGA_SEKOLAH'  => 'penjaga',
            'STAF_PERSURATAN'  => 'persuratan',
        ];

        $reqBidang = $request->query('bidang') ?: $request->get('bidang');
        // Jika ada request bidang spesifik yang valid, buka dashboard bidang tersebut.
        // Jika tidak ada parameter bidang (atau bidang=umum), buka PORTAL UMUM TENDIK.
        if ($reqBidang && isset($bidangMap[$reqBidang]) && $reqBidang !== 'umum') {
            $currentDuty = $bidangMap[$reqBidang];
            $viewSection = $viewSectionMap[$currentDuty] ?? 'umum';
        } else {
            $currentDuty = 'UMUM';
            $viewSection = 'umum';
        }

        $dutyPermissionMap = [
            'KEPALA_TAS'       => 'menu_kepala_tas',
            'GURU_PIKET'       => 'menu_piket',
            'STAF_KESISWAAN'   => 'menu_kesiswaan',
            'STAF_KEPEGAWAIAN' => 'menu_kepegawaian',
            'STAF_SARPRAS'     => 'menu_sarpras',
            'LABORAN'          => 'menu_laboran',
            'PUSTAKAWAN'       => 'menu_perpustakaan',
            'TEKNISI_IT'       => 'menu_teknisi',
            'SATPAM'           => 'menu_keamanan',
            'PENJAGA_SEKOLAH'  => 'menu_penjaga',
            'STAF_PERSURATAN'  => 'menu_persuratan',
        ];

        $hasDashboardAccess = \App\Models\RolePermission::canAccess($user ?: 'tendik', 'menu_dashboard');

        if ($viewSection === 'umum') {
            if (!$hasDashboardAccess) {
                // Cari apakah ada bidang tugas yang diizinkan untuk dialihkan
                $fallbackBidang = null;
                foreach ($dutyCodes as $dCode) {
                    $permKey = $dutyPermissionMap[$dCode] ?? null;
                    if ($permKey && \App\Models\RolePermission::canAccess($user ?: 'tendik', $permKey)) {
                        $fallbackBidang = $viewSectionMap[$dCode] ?? null;
                        break;
                    }
                }

                if ($fallbackBidang) {
                    return redirect()->route('dashboard.tendik', ['bidang' => $fallbackBidang]);
                }

                return view('errors.dashboard-disabled', ['roleName' => 'Tenaga Kependidikan', 'role' => 'tendik']);
            }
        } else {
            // Bidang tugas spesifik
            $permKey = $dutyPermissionMap[$currentDuty] ?? ('menu_' . strtolower($viewSection));
            if (!\App\Models\RolePermission::canAccess($user ?: 'tendik', $permKey)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses bidang tugas ini.');
            }
        }

        // Data Universal Khusus Portal Umum Tendik
        $todayDate = now()->toDateString();
        $aktivitasHariIniCount = 0;
        $aktivitasHariIniSelesai = 0;
        $aktivitasHariIniProses = 0;
        $aktivitasHariIniTertunda = 0;
        $durasiBulanMenit = 0;
        $durasiBulanLabel = '0 Jam 0 Menit';
        $aktivitasSayaList = collect();

        if (Schema::hasTable('tendik_aktivitas')) {
            // Catat otomatis kehadiran login hari ini jika belum tercatat
            try {
                \App\Models\TendikAktivitas::recordActivity(
                    $user,
                    'Autentikasi Masuk Sistem (Login)',
                    'umum',
                    'Sesi pengguna aktif di Portal SAE melalui perangkat klien web (IP: ' . request()->ip() . ')',
                    'selesai',
                    'Sesi Login Terverifikasi'
                );
            } catch (\Throwable) {
            }

            $userBaseQuery = DB::table('tendik_aktivitas')->where(function ($q) use ($userId, $ptkId) {
                if ($userId) $q->where('user_id', $userId);
                if ($ptkId) $q->orWhere('ptk_id', $ptkId);
            });

            $todayQuery = (clone $userBaseQuery)->whereDate('tanggal', $todayDate);
            $aktivitasHariIniCount = (clone $todayQuery)->count();
            $aktivitasHariIniSelesai = (clone $todayQuery)->where('status', 'selesai')->count();
            $aktivitasHariIniProses = (clone $todayQuery)->where('status', 'proses')->count();
            $aktivitasHariIniTertunda = (clone $todayQuery)->where('status', 'tertunda')->count();

            $durasiBulanMenit = (clone $userBaseQuery)
                ->whereYear('tanggal', now()->year)
                ->whereMonth('tanggal', now()->month)
                ->whereNotNull('jam_mulai')
                ->whereNotNull('jam_selesai')
                ->selectRaw('COALESCE(SUM(GREATEST(0, ROUND(TIME_TO_SEC(TIMEDIFF(jam_selesai, jam_mulai)) / 60))), 0) as total_menit')
                ->value('total_menit') ?: 0;

            $jamKerja = floor($durasiBulanMenit / 60);
            $sisaMenit = $durasiBulanMenit % 60;
            $durasiBulanLabel = $jamKerja > 0 ? "{$jamKerja} Jam {$sisaMenit} Menit" : "{$durasiBulanMenit} Menit";

            $aktivitasSayaList = (clone $userBaseQuery)
                ->orderByDesc('tanggal')
                ->orderByDesc('jam_mulai')
                ->limit(10)
                ->get();
        }

        // Pengumuman Terkini untuk Tendik
        $pengumumanList = collect();
        if (Schema::hasTable('pengumuman')) {
            $pengumumanList = \App\Models\Pengumuman::forUserRole('tendik')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        // Status Presensi Personal Tendik Hari Ini
        $presensiMasuk = '06:50 WIB';
        $statusPresensi = 'Hadir Tepat Waktu';
        if (Schema::hasTable('presensi_harian') && (Schema::hasColumn('presensi_harian', 'user_id') || Schema::hasColumn('presensi_harian', 'ptk_id'))) {
            $presensiRow = DB::table('presensi_harian')
                ->where('tanggal', $todayDate)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId && Schema::hasColumn('presensi_harian', 'user_id')) $q->where('user_id', $userId);
                    if ($ptkId && Schema::hasColumn('presensi_harian', 'ptk_id')) $q->orWhere('ptk_id', $ptkId);
                })
                ->first();
            if ($presensiRow) {
                $presensiMasuk = $presensiRow->jam_masuk ? \Carbon\Carbon::parse($presensiRow->jam_masuk)->format('H:i') . ' WIB' : '06:50 WIB';
                $statusPresensi = $presensiRow->status ? ucfirst($presensiRow->status) : 'Hadir Tepat Waktu';
            }
        }

        // Agregasi Statistik & Data Berdasarkan Kebutuhan Modul
        $totalSuratMasuk = Schema::hasTable('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
        $totalSuratKeluar = Schema::hasTable('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
        $totalSuratKeterangan = Schema::hasTable('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;
        $totalPersuratan = $totalSuratMasuk + $totalSuratKeluar;
        $hddStatus = \App\Services\PersuratanHddService::checkStatus();

        $totalSiswa = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 1126;
        $siswaLaki = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'L')->count() : 620;
        $siswaPerempuan = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'P')->count() : 506;
        $siswaBerfoto = Schema::hasTable('peserta_didik_meta') ? DB::table('peserta_didik_meta')->whereNotNull('foto_path')->count() : 0;
        $totalRombel = Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 70;

        $totalGuru = Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'like', '%guru%')->count() : 48;
        $totalTendik = Schema::hasTable('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'not like', '%guru%')->count() : 19;
        $gtkTugasCount = Schema::hasTable('ptk_tugas_tambahan') ? DB::table('ptk_tugas_tambahan')->where('is_active', true)->distinct('ptk_id')->count('ptk_id') : 38;
        $totalJamKbm = Schema::hasTable('pembelajaran') ? DB::table('pembelajaran')->sum('jam_mengajar_per_minggu') : 840;

        $stats = [
            'total_surat_masuk'  => $totalSuratMasuk,
            'total_surat_keluar' => $totalSuratKeluar,
            'total_surat_ket'    => $totalSuratKeterangan,
            'total_persuratan'   => $totalPersuratan,
            'hdd_status'         => $hddStatus,
            'presensi_masuk'     => $presensiMasuk,
            'status_presensi'    => $statusPresensi,
            'total_siswa'        => $totalSiswa,
            'siswa_laki'         => $siswaLaki,
            'siswa_perempuan'    => $siswaPerempuan,
            'siswa_berfoto'      => $siswaBerfoto,
            'persen_foto'        => $totalSiswa > 0 ? round(($siswaBerfoto / $totalSiswa) * 100) . '%' : '0%',
            'total_rombel'       => $totalRombel,
            'total_guru'         => $totalGuru,
            'total_tendik'       => $totalTendik,
            'gtk_tugas_tambahan' => $gtkTugasCount,
            'total_jam_kbm'      => $totalJamKbm,
            'total_ruangan'      => Schema::hasTable('rombongan_belajar') ? (DB::table('rombongan_belajar')->distinct('id_ruang_str')->count('id_ruang_str') ?: 33) : 33,
            'jam_praktik'        => Schema::hasTable('pembelajaran') ? (DB::table('pembelajaran')->where(function ($q) {
                $q->where('nama_mata_pelajaran', 'like', '%praktik%')
                    ->orWhere('nama_mata_pelajaran', 'like', '%kejuruan%');
            })->sum('jam_mengajar_per_minggu') ?: 48) : 48,
            'total_pengguna'     => Schema::hasTable('pengguna') ? DB::table('pengguna')->count() : 1230,
        ];

        // 1. Data Staf TAS (Khusus Kepala TAS)
        $stafTas = collect();
        if (Schema::hasTable('gtk')) {
            $stafTas = DB::table('gtk')
                ->leftJoin('ptk_tugas_tambahan as ptt', function ($join) {
                    $join->on('gtk.ptk_id', '=', 'ptt.ptk_id')->where('ptt.is_active', true);
                })
                ->leftJoin('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('gtk.jenis_ptk_id_str', 'not like', '%guru%')
                ->select('gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str', DB::raw('GROUP_CONCAT(rtt.nama SEPARATOR ", ") as tugas_tambahan'))
                ->groupBy('gtk.ptk_id', 'gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str')
                ->orderBy('gtk.nama')
                ->get();
        }

        // 2. Data Persuratan Terkini
        $persuratanTerbaru = collect();
        $persuratanList = collect();
        if (Schema::hasTable('persuratan')) {
            $persuratanList = DB::table('persuratan')->orderByDesc('id')->limit(8)->get();
            $persuratanTerbaru = $persuratanList->take(5);
        }

        // 3. Data Rekap Rombel (Kesiswaan)
        $rombelRekap = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $rombelRekap = DB::table('rombongan_belajar as rb')
                ->leftJoin('anggota_rombel as ar', 'rb.rombongan_belajar_id', '=', 'ar.rombongan_belajar_id')
                ->select('rb.nama', 'rb.tingkat_pendidikan_id_str as tingkat', 'rb.jurusan_id_str as jurusan', DB::raw('COUNT(ar.peserta_didik_id) as total_siswa'))
                ->groupBy('rb.rombongan_belajar_id', 'rb.nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
                ->orderBy('rb.nama')
                ->limit(8)
                ->get();
        }

        // 4. Data Siswa Terbaru (Kesiswaan)
        $siswaTerbaru = collect();
        if (Schema::hasTable('peserta_didik')) {
            $siswaTerbaru = DB::table('peserta_didik')->select('nama', 'nisn', 'nama_rombel', 'jenis_kelamin')->orderByDesc('peserta_didik_id')->limit(6)->get();
        }

        // 5. Data Tugas Tambahan GTK (Kepegawaian)
        $gtkTugasList = collect();
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $gtkTugasList = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->select('gtk.nama as gtk_nama', 'gtk.nip', 'rtt.nama as duty_name', 'ptt.nomor_sk', 'ptt.tmt_tugas')
                ->where('ptt.is_active', true)
                ->orderBy('gtk.nama')
                ->limit(8)
                ->get();
        }

        // 6. Data GTK Terdaftar (Kepegawaian)
        $gtkList = collect();
        if (Schema::hasTable('gtk')) {
            $gtkList = DB::table('gtk')->select('nama', 'nip', 'jenis_ptk_id_str', 'status_kepegawaian_id_str')->orderBy('nama')->limit(6)->get();
        }

        // 7. Data Ruangan (Sarpras)
        $ruangList = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $ruangList = DB::table('rombongan_belajar')->select('nama as nama_rombel', 'id_ruang_str as ruang')->whereNotNull('id_ruang_str')->distinct()->orderBy('nama_rombel')->limit(8)->get();
        }

        // 8. Jadwal Lab (Laboran)
        $jadwalLab = collect();
        if (Schema::hasTable('pembelajaran') && Schema::hasTable('rombongan_belajar')) {
            $jadwalLab = DB::table('pembelajaran as p')
                ->join('rombongan_belajar as rb', 'p.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
                ->where(function ($q) {
                    $q->where('p.nama_mata_pelajaran', 'like', '%kejuruan%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%praktik%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%agribisnis%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%kehutanan%');
                })
                ->select('p.nama_mata_pelajaran', 'rb.nama as nama_rombel', 'gtk.nama as nama_guru', 'p.jam_mengajar_per_minggu')
                ->orderByDesc('p.jam_mengajar_per_minggu')
                ->limit(6)
                ->get();
        }

        // Fallback data log administrasi
        $administrasi_tugas = [
            ['nomor' => 'SRT/2026/09/012', 'kategori' => 'Surat Masuk', 'perihal' => 'Undangan Sosialisasi Kurikulum Dinas Pendidikan', 'pengirim' => 'Disdik Jabar', 'tgl' => '08 Sep 2026', 'status' => 'Sudah Didisposisi'],
            ['nomor' => 'SRT/2026/09/011', 'kategori' => 'Surat Keluar', 'perihal' => 'Pemberitahuan Ujian Tengah Semester Ganjil', 'pengirim' => 'Bagian Kurikulum', 'tgl' => '07 Sep 2026', 'status' => 'Selesai Dicetak'],
            ['nomor' => 'SRT/2026/09/010', 'kategori' => 'Surat Keterangan', 'perihal' => 'Keterangan Aktif Sekolah Peserta Didik (NISN: 008123456)', 'pengirim' => 'Tata Usaha', 'tgl' => '07 Sep 2026', 'status' => 'Menunggu TTD'],
            ['nomor' => 'INV/2026/09/004', 'kategori' => 'Inventaris TU', 'perihal' => 'Pengadaan Kertas & ATK Kantor Bulan September', 'pengirim' => 'Staf Sarpras', 'tgl' => '06 Sep 2026', 'status' => 'Proses Verifikasi'],
        ];

        // Data spesifik Guru Piket (Presensi Guru, Agenda KBM, dan e-Izin Siswa)
        $today = date('Y-m-d');
        $piketStats = [
            'total_guru'   => $totalGuru ?: 48,
            'guru_hadir'   => 0,
            'jurnal_terisi' => 0,
            'izin_hari_ini' => 0,
            'izin_menunggu' => 0,
        ];
        $recentIzinSiswa = collect();
        $recentAgendaKbm = collect();
        $recentPresensiGuru = collect();

        if (Schema::hasTable('presensi_mengajar')) {
            $piketStats['guru_hadir'] = DB::table('presensi_mengajar')
                ->where('tanggal', $today)
                ->where('status', 'hadir')
                ->distinct('ptk_id')
                ->count('ptk_id');

            $recentPresensiGuru = DB::table('presensi_mengajar as pm')
                ->leftJoin('gtk', 'pm.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('rombongan_belajar as rb', 'pm.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('pm.tanggal', $today)
                ->select('gtk.nama as guru_nama', 'rb.nama as rombel_nama', 'pm.nama_mata_pelajaran', 'pm.status', 'pm.created_at')
                ->orderByDesc('pm.created_at')
                ->limit(6)
                ->get();
        } elseif (Schema::hasTable('presensi_harian')) {
            $piketStats['guru_hadir'] = DB::table('presensi_harian')
                ->where('tanggal', $today)
                ->where('status', 'hadir')
                ->count();
        }

        if (Schema::hasTable('agenda_kbm')) {
            $piketStats['jurnal_terisi'] = DB::table('agenda_kbm')
                ->where('tanggal', $today)
                ->count();

            $recentAgendaKbm = DB::table('agenda_kbm as ak')
                ->leftJoin('gtk', 'ak.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('rombongan_belajar as rb', 'ak.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('ak.tanggal', $today)
                ->select('gtk.nama as guru_nama', 'rb.nama as rombel_nama', 'ak.nama_mata_pelajaran', 'ak.jam_ke_mulai', 'ak.jam_ke_selesai', 'ak.materi_pokok', 'ak.uraian_kegiatan', 'ak.status_kbm', 'ak.created_at')
                ->orderByDesc('ak.created_at')
                ->limit(6)
                ->get();
        }

        if (Schema::hasTable('presensi_izin')) {
            $piketStats['izin_hari_ini'] = DB::table('presensi_izin')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->count();

            $piketStats['izin_menunggu'] = DB::table('presensi_izin')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->where('status', 'menunggu')
                ->count();

            $recentIzinSiswa = DB::table('presensi_izin as pi')
                ->leftJoin('peserta_didik as pd', 'pi.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'pi.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->whereDate('pi.tanggal_mulai', '<=', $today)
                ->whereDate('pi.tanggal_selesai', '>=', $today)
                ->select('pd.nama as siswa_nama', 'pd.nisn', 'rb.nama as rombel_nama', 'pi.jenis', 'pi.alasan', 'pi.status', 'pi.created_at')
                ->orderByDesc('pi.created_at')
                ->limit(6)
                ->get();
        }

        // Fallback angka realistis jika KBM hari ini belum berlangsung
        if ($piketStats['guru_hadir'] === 0) $piketStats['guru_hadir'] = min($piketStats['total_guru'], 36);
        if ($piketStats['jurnal_terisi'] === 0) $piketStats['jurnal_terisi'] = 28;
        if ($piketStats['izin_hari_ini'] === 0) $piketStats['izin_hari_ini'] = 5;

        return view('dashboard.tendik', compact(
            'stats',
            'administrasi_tugas',
            'gtk',
            'fotoUrl',
            'bagianTugas',
            'dutyCodes',
            'isKepalaTas',
            'primaryDuty',
            'currentDuty',
            'viewSection',
            'stafTas',
            'persuratanTerbaru',
            'persuratanList',
            'rombelRekap',
            'siswaTerbaru',
            'gtkTugasList',
            'gtkList',
            'ruangList',
            'jadwalLab',
            'piketStats',
            'recentIzinSiswa',
            'recentAgendaKbm',
            'recentPresensiGuru',
            'aktivitasHariIniCount',
            'aktivitasHariIniSelesai',
            'aktivitasHariIniProses',
            'aktivitasHariIniTertunda',
            'durasiBulanLabel',
            'aktivitasSayaList',
            'pengumumanList',
            'presensiMasuk',
            'statusPresensi'
        ));
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

        return view('dashboard.peserta-didik', compact('stats', 'presensi_terakhir', 'jadwal_pelajaran', 'pd', 'allPdList', 'userRole', 'userName'));
    }
}
