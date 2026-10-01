<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\PresensiHarian;
use App\Models\RolePermission;
use App\Models\User;

class DutyDashboardController extends Controller
{
    /**
     * Tampilkan Dashboard Khusus Tugas Tambahan
     */
    public function show(Request $request, string $kode)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $role = is_array($sessionUser) ? ($sessionUser['role'] ?? 'guru') : ($sessionUser->role ?? 'guru');
        $userId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
        $ptkId = is_array($sessionUser) ? ($sessionUser['ptk_id'] ?? null) : ($sessionUser->ptk_id ?? null);

        // Cari master tugas tambahan berdasarkan kode slug, nama, atau alias
        $normalizedSlug = Str::slug($kode);
        $upperCode = strtoupper(str_replace('-', '_', $kode));

        $allRefDuties = DB::table('ref_tugas_tambahan')->where('is_active', true)->get();
        $duty = $allRefDuties->first(function ($item) use ($normalizedSlug, $upperCode) {
            $itemSlug = Str::slug($item->kode);
            $nameSlug = Str::slug($item->nama);
            return $itemSlug === $normalizedSlug
                || $nameSlug === $normalizedSlug
                || $item->kode === $upperCode
                || Str::contains($item->kode, $upperCode)
                || Str::contains($itemSlug, $normalizedSlug)
                || Str::contains($nameSlug, $normalizedSlug);
        });

        if (!$duty) {
            return redirect()->route("dashboard.{$role}")->with('error', "Dashboard tugas tambahan '{$kode}' tidak ditemukan.");
        }

        // Cek Hak Akses Penugasan Pengguna
        $isAdmin = ($role === 'admin');
        $isAssigned = false;
        $userAssignment = null;

        if (!$isAdmin) {
            $userAssignment = DB::table('ptk_tugas_tambahan')
                ->where('tugas_tambahan_id', $duty->id)
                ->where('is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                    if ($ptkId) {
                        $q->orWhere('ptk_id', $ptkId);
                    }
                })
                ->first();

            // Khusus Wali Kelas: periksa juga kepemilikan rombel langsung dari Dapodik
            if (!$userAssignment && in_array($duty->kode, ['WALI_KELAS', 'WALI-KELAS']) && $ptkId) {
                $rombelWali = DB::table('rombongan_belajar')
                    ->where('ptk_id', $ptkId)
                    ->where('jenis_rombel', '1')
                    ->first();
                if ($rombelWali) {
                    $isAssigned = true;
                }
            }

            if ($userAssignment) {
                $isAssigned = true;
            }

            if (!$isAssigned) {
                return redirect()->route("dashboard.{$role}")->with('error', "Anda tidak memiliki penugasan aktif untuk tugas '{$duty->nama}'.");
            }

            // Dual-Key RBAC: Validasi bahwa modul tugas tambahan ini diizinkan di matriks hak akses peran
            $dutyPermMap = [
                'WALI_KELAS' => 'menu_wali_kelas',
                'WALI-KELAS' => 'menu_wali_kelas',
                'GURU_PIKET' => 'menu_piket',
                'KEPALA_TAS' => 'menu_kepala_tas',
                'STAF_PERSURATAN' => 'menu_persuratan',
                'WAKA_KESISWAAN' => 'menu_kesiswaan',
                'STAF_KESISWAAN' => 'menu_kesiswaan',
                'PEMBINA_OSIS' => 'menu_kesiswaan',
                'PEMBINA_EKSKUL' => 'menu_kesiswaan',
                'STAF_KEPEGAWAIAN' => 'menu_kepegawaian',
                'STAF_SARPRAS' => 'menu_sarpras',
                'LABORAN' => 'menu_laboran',
                'PUSTAKAWAN' => 'menu_perpustakaan',
                'TEKNISI_IT' => 'menu_teknisi',
                'SATPAM' => 'menu_keamanan',
                'PENJAGA_SEKOLAH' => 'menu_penjaga',
            ];
            $targetPerm = $dutyPermMap[$duty->kode] ?? ('menu_' . strtolower(str_replace('-', '_', $duty->kode)));

            if (!RolePermission::canAccess($sessionUser, $targetPerm)) {
                return redirect()->route("dashboard.{$role}")->with('error', "Hak akses ke modul '{$duty->nama}' dinonaktifkan oleh Administrator.");
            }
        }

        // Dekode izin yang diberikan untuk tugas ini
        $grantedPermKeys = is_string($duty->granted_permissions)
            ? (json_decode($duty->granted_permissions, true) ?: [])
            : ($duty->granted_permissions ?: []);

        // Ambil konfigurasi modul yang terbuka untuk tugas ini
        $allSystemModules = RolePermission::getAllSystemModules();
        $grantedModules = [];
        foreach ($grantedPermKeys as $permKey) {
            if (isset($allSystemModules[$permKey])) {
                $mod = $allSystemModules[$permKey];
                $mod['key'] = $permKey;
                $grantedModules[] = $mod;
            }
        }

        // Ambil rekan satu tim / personel yang juga mengemban tugas ini
        $teamMembers = DB::table('ptk_tugas_tambahan as ptt')
            ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('pengguna as p', 'ptt.user_id', '=', 'p.pengguna_id')
            ->leftJoin('rombongan_belajar as rb', 'ptt.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->where('ptt.tugas_tambahan_id', $duty->id)
            ->where('ptt.is_active', true)
            ->select(
                'ptt.id',
                'ptt.nomor_sk',
                'ptt.tmt_tugas',
                'ptt.tst_tugas',
                'ptt.keterangan',
                DB::raw("COALESCE(gtk.nama, p.nama, 'Personel') as nama"),
                DB::raw("COALESCE(gtk.nip, '-') as nip"),
                'rb.nama as rombel_nama'
            )
            ->orderBy('nama')
            ->get();

        // Data spesifik per jenis tugas
        $specificData = [];
        $viewSlug = Str::slug($duty->kode);

        if ($duty->kode === 'WALI_KELAS') {
            $viewSlug = 'wali-kelas';
            $specificData = $this->prepareWaliKelasData($request, $ptkId, $userAssignment);
        } elseif ($duty->kode === 'GURU_PIKET') {
            $viewSlug = 'piket';
            $specificData = $this->preparePiketData();
        } elseif ($duty->kode === 'KEPALA_TAS') {
            $viewSlug = 'kepala-tas';
            $specificData = $this->prepareKepalaTasData();
        } elseif (in_array($duty->kode, ['LABORAN', 'KEPALA_LAB'])) {
            $viewSlug = 'laboran';
            $specificData = $this->prepareLaboranData();
        }

        $viewName = view()->exists("dashboard.tugas-tambahan.{$viewSlug}")
            ? "dashboard.tugas-tambahan.{$viewSlug}"
            : "dashboard.tugas-tambahan.default";

        return view($viewName, array_merge([
            'duty' => $duty,
            'userAssignment' => $userAssignment,
            'grantedModules' => $grantedModules,
            'teamMembers' => $teamMembers,
            'isAdmin' => $isAdmin,
            'sessionUser' => $sessionUser,
        ], $specificData));
    }

    /**
     * Siapkan metrik, kombinasi chart analitik, dan 2 datatable khusus Wali Kelas
     */
    private function prepareWaliKelasData(Request $request, ?string $ptkId, $userAssignment): array
    {
        $rombelId = $request->get('rombel_id') ?: ($userAssignment?->rombel_id ?? null);

        if (!$rombelId && $ptkId) {
            $rombelId = DB::table('rombongan_belajar')
                ->where('ptk_id', $ptkId)
                ->where('jenis_rombel', '1')
                ->value('rombongan_belajar_id');
        }

        if (!$rombelId) {
            // Cek penugasan wali kelas lain di ptk_tugas_tambahan
            $rombelId = DB::table('ptk_tugas_tambahan')
                ->where('is_active', true)
                ->whereNotNull('rombel_id')
                ->value('rombel_id');
        }

        if (!$rombelId) {
            // Fallback ke rombel reguler aktif pertama yang memiliki siswa (misal XII TKJ 2)
            $rombelId = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '1')
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('anggota_rombel')
                        ->whereColumn('anggota_rombel.rombongan_belajar_id', 'rombongan_belajar.rombongan_belajar_id');
                })
                ->orderBy('nama')
                ->value('rombongan_belajar_id');
        }

        $allRombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('nama')
            ->get(['rombongan_belajar_id', 'nama']);

        $rombel = null;
        $totalSiswa = 0;
        $siswaList = collect();
        $presensiHariIni = [
            'H' => 0,
            'H_tepat' => 0,
            'T' => 0,
            'S' => 0,
            'I' => 0,
            'A' => 0,
            'total' => 0,
            'belum' => 0,
            'persen' => 0,
        ];
        $kualitasData = [
            'total' => 0,
            'valid' => 0,
            'persen' => 0,
            'konfirmasi' => 0,
            'kk' => 0,
            'ijazah' => 0,
        ];
        $chartPayload = [
            'trend' => ['labels' => [], 'hadir_pct' => [], 'hadir' => [], 'terlambat' => [], 'izin_sakit' => [], 'alpha' => []],
            'komposisi' => ['labels' => [], 'data' => [], 'persen' => 0],
            'gender' => ['labels' => ['Laki-laki', 'Perempuan'], 'data' => [0, 0], 'persen_L' => 0, 'persen_P' => 0],
            'disiplin' => ['labels' => ['Tepat Waktu (< 07:00)', 'Terlambat Ringan (< 15 Mnt)', 'Terlambat Sedang (> 15 Mnt)', 'Izin / Sakit'], 'data' => [0, 0, 0, 0]],
        ];
        $logAktivitas = collect();
        $logPresensi = collect();

        if ($rombelId) {
            $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();

            $siswaList = DB::table('anggota_rombel as ar')
                ->join('peserta_didik as pd', 'ar.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->where('ar.rombongan_belajar_id', $rombelId)
                ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.jenis_kelamin')
                ->orderBy('pd.nama')
                ->get();

            $totalSiswa = $siswaList->count();
            $pdIds = $siswaList->pluck('peserta_didik_id')->all();

            // Peta foto siswa
            $metaMap = collect();
            if (!empty($pdIds) && Schema::hasTable('peserta_didik_meta')) {
                $metaMap = DB::table('peserta_didik_meta')->whereIn('peserta_didik_id', $pdIds)->get()->keyBy('peserta_didik_id');
            }

            foreach ($siswaList as $s) {
                $m = $metaMap->get($s->peserta_didik_id);
                $s->foto_url = !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null);
            }

            // Hitung Presensi Hari Ini dari tabel presensi_harian (Data Real Database)
            $today = date('Y-m-d');
            if (Schema::hasTable('presensi_harian')) {
                $presensiToday = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $rombelId)
                    ->whereDate('tanggal', $today)
                    ->get();

                $countH_tepat = $presensiToday->where('status', 'H')->count();
                $countT       = $presensiToday->where('status', 'T')->count();
                $countS       = $presensiToday->where('status', 'S')->count();
                $countI       = $presensiToday->whereIn('status', ['I', 'D'])->count();
                $countA       = $presensiToday->where('status', 'A')->count();
                $countH_total = $countH_tepat + $countT; // Total hadir fisik

                $presensiHariIni['H'] = $countH_total;
                $presensiHariIni['H_tepat'] = $countH_tepat;
                $presensiHariIni['T'] = $countT;
                $presensiHariIni['S'] = $countS;
                $presensiHariIni['I'] = $countI;
                $presensiHariIni['A'] = $countA;
                $presensiHariIni['total'] = $presensiToday->count();
                $presensiHariIni['belum'] = max(0, $totalSiswa - $presensiHariIni['total']);
                $presensiHariIni['persen'] = $totalSiswa > 0 ? round(($countH_total / $totalSiswa) * 100, 1) : 0;

                // Komposisi Chart Hari Ini
                $chartPayload['komposisi'] = [
                    'labels' => ['Hadir Tepat Waktu', 'Terlambat', 'Sakit', 'Izin', 'Alpha', 'Belum Tercatat'],
                    'data' => [$countH_tepat, $countT, $countS, $countI, $countA, $presensiHariIni['belum']],
                    'persen' => $presensiHariIni['persen'],
                ];

                // Tren Presensi 7 Hari Terakhir
                $trendDays = DB::table('presensi_harian')
                    ->where('rombongan_belajar_id', $rombelId)
                    ->distinct()
                    ->orderBy('tanggal', 'desc')
                    ->limit(7)
                    ->pluck('tanggal')
                    ->sort()
                    ->values();

                $trendLabels = [];
                $trendHadirPct = [];
                $trendHadir = [];
                $trendTerlambat = [];
                $trendIzinSakit = [];
                $trendAlpha = [];

                foreach ($trendDays as $tgl) {
                    $dayRecords = DB::table('presensi_harian')
                        ->where('rombongan_belajar_id', $rombelId)
                        ->whereDate('tanggal', $tgl)
                        ->get();

                    $dH = $dayRecords->whereIn('status', ['H', 'T'])->count();
                    $dT = $dayRecords->where('status', 'T')->count();
                    $dIS = $dayRecords->whereIn('status', ['S', 'I', 'D'])->count();
                    $dA = $dayRecords->where('status', 'A')->count();
                    $dPct = $totalSiswa > 0 ? round(($dH / $totalSiswa) * 100, 1) : 0;

                    $trendLabels[] = Carbon::parse($tgl)->translatedFormat('D, d M');
                    $trendHadirPct[] = $dPct;
                    $trendHadir[] = $dH;
                    $trendTerlambat[] = $dT;
                    $trendIzinSakit[] = $dIS;
                    $trendAlpha[] = $dA;
                }

                $chartPayload['trend'] = [
                    'labels' => $trendLabels,
                    'hadir_pct' => $trendHadirPct,
                    'hadir' => $trendHadir,
                    'terlambat' => $trendTerlambat,
                    'izin_sakit' => $trendIzinSakit,
                    'alpha' => $trendAlpha,
                ];

                // Evaluasi Disiplin Ketepatan Waktu Masuk
                $tepatCount = $presensiToday->where('status', 'H')->count();
                $terlambatRingan = $presensiToday->where('status', 'T')->where('menit_terlambat', '<=', 15)->count();
                $terlambatSedang = $presensiToday->where('status', 'T')->where('menit_terlambat', '>', 15)->count();
                $izinSakitCount = $presensiToday->whereIn('status', ['S', 'I', 'D'])->count();

                $chartPayload['disiplin'] = [
                    'labels' => ['Tepat Waktu (< 07:00)', 'Terlambat 1-15 Mnt', 'Terlambat > 15 Mnt', 'Izin / Sakit'],
                    'data' => [$tepatCount, $terlambatRingan, $terlambatSedang, $izinSakitCount],
                ];
            }

            // Komposisi Gender Siswa
            $countL = $siswaList->where('jenis_kelamin', 'L')->count();
            $countP = $siswaList->where('jenis_kelamin', 'P')->count();
            $chartPayload['gender'] = [
                'labels' => ['Laki-laki', 'Perempuan'],
                'data' => [$countL, $countP],
                'persen_L' => $totalSiswa > 0 ? round(($countL / $totalSiswa) * 100, 1) : 0,
                'persen_P' => $totalSiswa > 0 ? round(($countP / $totalSiswa) * 100, 1) : 0,
            ];

            // Datatable 2: Log Khusus Presensi Harian Siswa Perwalian
            $logPresensi = DB::table('presensi_harian as ph')
                ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->where('ph.rombongan_belajar_id', $rombelId)
                ->select(
                    'ph.id',
                    'ph.peserta_didik_id',
                    'pd.nama as siswa_nama',
                    'pd.nisn as siswa_nisn',
                    'pd.nipd as siswa_nipd',
                    'pd.jenis_kelamin',
                    'ph.tanggal',
                    'ph.jam_masuk',
                    'ph.jam_pulang',
                    'ph.status',
                    'ph.menit_terlambat',
                    'ph.status_ketepatan_masuk',
                    'ph.metode_masuk',
                    'ph.keterangan',
                    'ph.verified_by'
                )
                ->orderBy('ph.tanggal', 'desc')
                ->orderBy('ph.jam_masuk', 'desc')
                ->limit(60)
                ->get();

            foreach ($logPresensi as $lp) {
                $m = $metaMap->get($lp->peserta_didik_id);
                $lp->foto_url = !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null);
            }

            // Datatable 1: Log Aktivitas Siswa Perwalian (Login, e-Izin, Berkas, Usulan, Konfirmasi)
            $logAktivitas = $this->collectStudentActivities($rombelId, $siswaList, $metaMap);

            // Hitung Kualitas Data Siswa (Konfirmasi Sesuai + Berkas KK & Ijazah Valid)
            $kualitasData['total'] = $totalSiswa;
            if (!empty($pdIds)) {
                $identitasMap = Schema::hasTable('peserta_didik_identitas')
                    ? DB::table('peserta_didik_identitas')->whereIn('peserta_didik_id', $pdIds)->get()->keyBy('peserta_didik_id')
                    : collect();

                $berkasMap = Schema::hasTable('peserta_didik_berkas')
                    ? DB::table('peserta_didik_berkas')->whereIn('peserta_didik_id', $pdIds)->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp'])->get()->groupBy('peserta_didik_id')
                    : collect();

                $fisikMap = Schema::hasTable('kesiswaan_berkas_verifikasi')
                    ? DB::table('kesiswaan_berkas_verifikasi')->whereIn('peserta_didik_id', $pdIds)->get()->keyBy('peserta_didik_id')
                    : collect();

                $validCount = 0;
                $konfirmasiCount = 0;
                $kkCount = 0;
                $ijzCount = 0;

                foreach ($siswaList as $s) {
                    $idRec = $identitasMap->get($s->peserta_didik_id);
                    $isConfirmed = $idRec && in_array($idRec->status_konfirmasi, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true);
                    if ($isConfirmed) $konfirmasiCount++;

                    $sBerkas = $berkasMap->get($s->peserta_didik_id) ?? collect();
                    $sFisik  = $fisikMap->get($s->peserta_didik_id);

                    $kkValid  = ($sBerkas->firstWhere('jenis_berkas', 'kartu_keluarga')?->status === 'valid') || !empty($sFisik?->kartu_keluarga);
                    $ijzValid = ($sBerkas->firstWhere('jenis_berkas', 'ijazah_smp')?->status === 'valid') || !empty($sFisik?->ijazah_smp);

                    if ($kkValid) $kkCount++;
                    if ($ijzValid) $ijzCount++;

                    if ($isConfirmed && $kkValid && $ijzValid) {
                        $validCount++;
                    }
                }

                $kualitasData['valid'] = $validCount;
                $kualitasData['konfirmasi'] = $konfirmasiCount;
                $kualitasData['kk'] = $kkCount;
                $kualitasData['ijazah'] = $ijzCount;
                $kualitasData['persen'] = $totalSiswa > 0 ? round(($validCount / $totalSiswa) * 100, 1) : 0;
            }
        }

        return [
            'rombel' => $rombel,
            'allRombels' => $allRombels ?? collect(),
            'totalSiswa' => $totalSiswa,
            'siswaList' => $siswaList,
            'presensiHariIni' => $presensiHariIni,
            'kualitasData' => $kualitasData,
            'chartPayload' => $chartPayload,
            'logAktivitas' => $logAktivitas,
            'logPresensi' => $logPresensi,
        ];
    }

    /**
     * Kumpulkan Log Aktivitas Siswa Perwalian (Login, e-Izin, Berkas, Usulan Data, Identitas)
     */
    private function collectStudentActivities(string $rombelId, $siswaList, $metaMap)
    {
        $activities = collect();
        $nisnMap = $siswaList->keyBy('nisn');
        $idMap   = $siswaList->keyBy('peserta_didik_id');
        $nisns   = $siswaList->pluck('nisn')->filter()->all();
        $pdIds   = $siswaList->pluck('peserta_didik_id')->all();

        // 1. Aktivitas Login Siswa (dari admin_aktivitas)
        if (Schema::hasTable('admin_aktivitas') && !empty($nisns)) {
            $loginLogs = DB::table('admin_aktivitas')
                ->whereIn('admin_username', $nisns)
                ->orderBy('created_at', 'desc')
                ->limit(35)
                ->get();

            foreach ($loginLogs as $log) {
                $siswa = $nisnMap->get($log->admin_username);
                if ($siswa) {
                    $m = $metaMap->get($siswa->peserta_didik_id);
                    $activities->push((object)[
                        'waktu' => Carbon::parse($log->created_at),
                        'siswa_nama' => $siswa->nama,
                        'siswa_nisn' => $siswa->nisn,
                        'siswa_gender' => $siswa->jenis_kelamin,
                        'foto_url' => !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null),
                        'kategori' => 'login',
                        'kategori_label' => 'Autentikasi Login',
                        'tipe_badge' => '<span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6;"><i class="fas fa-right-to-bracket me-1"></i> Login Portal</span>',
                        'aktivitas' => $log->aktivitas,
                        'keterangan' => $log->keterangan ?: 'Berhasil login ke aplikasi SAE',
                        'status_badge' => '<span class="badge badge-success" style="font-size: 0.72rem;"><i class="fas fa-circle-check me-1"></i> Berhasil</span>',
                    ]);
                }
            }
        }

        // 2. Pengajuan e-Izin / Surat Sakit
        if (Schema::hasTable('presensi_izin') && !empty($pdIds)) {
            $izinLogs = DB::table('presensi_izin')
                ->whereIn('peserta_didik_id', $pdIds)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            foreach ($izinLogs as $izin) {
                $siswa = $idMap->get($izin->peserta_didik_id);
                if ($siswa) {
                    $m = $metaMap->get($siswa->peserta_didik_id);
                    $stBadge = match ($izin->status) {
                        'disetujui' => '<span class="badge badge-success" style="font-size: 0.72rem;"><i class="fas fa-check me-1"></i> Disetujui</span>',
                        'ditolak'   => '<span class="badge badge-danger" style="font-size: 0.72rem;"><i class="fas fa-times me-1"></i> Ditolak</span>',
                        default     => '<span class="badge badge-warning" style="font-size: 0.72rem;"><i class="fas fa-clock me-1"></i> Menunggu</span>',
                    };

                    $activities->push((object)[
                        'waktu' => Carbon::parse($izin->created_at),
                        'siswa_nama' => $siswa->nama,
                        'siswa_nisn' => $siswa->nisn,
                        'siswa_gender' => $siswa->jenis_kelamin,
                        'foto_url' => !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null),
                        'kategori' => 'izin',
                        'kategori_label' => 'e-Izin / Sakit',
                        'tipe_badge' => '<span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b;"><i class="fas fa-envelope-open-text me-1"></i> ' . ucfirst($izin->jenis) . '</span>',
                        'aktivitas' => 'Pengajuan Surat ' . ucfirst($izin->jenis),
                        'keterangan' => ($izin->alasan ?: 'Izin ketidakhadiran') . ' (' . Carbon::parse($izin->tanggal_mulai)->format('d/m') . ' s.d ' . Carbon::parse($izin->tanggal_selesai)->format('d/m/Y') . ')',
                        'status_badge' => $stBadge,
                    ]);
                }
            }
        }

        // 3. Izin Keluar Lingkungan Sekolah
        if (Schema::hasTable('peserta_didik_izin_keluar') && !empty($pdIds)) {
            $keluarLogs = DB::table('peserta_didik_izin_keluar')
                ->whereIn('peserta_didik_id', $pdIds)
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();

            foreach ($keluarLogs as $kl) {
                $siswa = $idMap->get($kl->peserta_didik_id);
                if ($siswa) {
                    $m = $metaMap->get($siswa->peserta_didik_id);
                    $activities->push((object)[
                        'waktu' => Carbon::parse($kl->created_at),
                        'siswa_nama' => $siswa->nama,
                        'siswa_nisn' => $siswa->nisn,
                        'siswa_gender' => $siswa->jenis_kelamin,
                        'foto_url' => !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null),
                        'kategori' => 'izin_keluar',
                        'kategori_label' => 'Izin Keluar Kampus',
                        'tipe_badge' => '<span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6;"><i class="fas fa-door-open me-1"></i> Keluar Gerbang</span>',
                        'aktivitas' => 'Izin Meninggalkan Sekolah',
                        'keterangan' => ($kl->alasan ?: 'Keperluan di luar') . ' | Pukul: ' . substr($kl->jam_izin_keluar, 0, 5),
                        'status_badge' => '<span class="badge badge-success" style="font-size: 0.72rem;">' . strtoupper($kl->status) . '</span>',
                    ]);
                }
            }
        }

        // 4. Usulan Perubahan Data Siswa
        if (Schema::hasTable('siswa_usulan_perubahan') && !empty($pdIds)) {
            $usulanLogs = DB::table('siswa_usulan_perubahan')
                ->whereIn('peserta_didik_id', $pdIds)
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();

            foreach ($usulanLogs as $u) {
                $siswa = $idMap->get($u->peserta_didik_id);
                if ($siswa) {
                    $m = $metaMap->get($siswa->peserta_didik_id);
                    $activities->push((object)[
                        'waktu' => Carbon::parse($u->created_at),
                        'siswa_nama' => $siswa->nama,
                        'siswa_nisn' => $siswa->nisn,
                        'siswa_gender' => $siswa->jenis_kelamin,
                        'foto_url' => !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null),
                        'kategori' => 'usulan',
                        'kategori_label' => 'Usulan Data',
                        'tipe_badge' => '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981;"><i class="fas fa-file-pen me-1"></i> Revisi Data</span>',
                        'aktivitas' => 'Usulan Perubahan ' . ucwords(str_replace('_', ' ', $u->kolom_perubahan)),
                        'keterangan' => 'Nilai Baru: "' . $u->nilai_baru . '" | ' . ($u->alasan ?: 'Penyesuaian dokumen'),
                        'status_badge' => '<span class="badge badge-outline" style="font-size: 0.72rem;">' . strtoupper($u->status) . '</span>',
                    ]);
                }
            }
        }

        // 5. Unggah Berkas Dokumen PDF
        if (Schema::hasTable('peserta_didik_berkas') && !empty($pdIds)) {
            $berkasLogs = DB::table('peserta_didik_berkas')
                ->whereIn('peserta_didik_id', $pdIds)
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();

            foreach ($berkasLogs as $b) {
                $siswa = $idMap->get($b->peserta_didik_id);
                if ($siswa) {
                    $m = $metaMap->get($siswa->peserta_didik_id);
                    $activities->push((object)[
                        'waktu' => Carbon::parse($b->created_at),
                        'siswa_nama' => $siswa->nama,
                        'siswa_nisn' => $siswa->nisn,
                        'siswa_gender' => $siswa->jenis_kelamin,
                        'foto_url' => !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null),
                        'kategori' => 'berkas',
                        'kategori_label' => 'Berkas Dokumen',
                        'tipe_badge' => '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444;"><i class="fas fa-file-pdf me-1"></i> Unggah PDF</span>',
                        'aktivitas' => 'Unggah ' . $b->nama_berkas,
                        'keterangan' => $b->file_name . ' (' . number_format($b->file_size / 1024, 1) . ' KB)',
                        'status_badge' => $b->status === 'valid'
                            ? '<span class="badge badge-success" style="font-size: 0.72rem;">Valid</span>'
                            : ($b->status === 'tidak_valid' ? '<span class="badge badge-danger" style="font-size: 0.72rem;">Ditolak</span>' : '<span class="badge badge-warning" style="font-size: 0.72rem;">Menunggu</span>'),
                    ]);
                }
            }
        }

        // 6. Konfirmasi Identitas Siswa Mandiri
        if (Schema::hasTable('peserta_didik_identitas') && !empty($pdIds)) {
            $idLogs = DB::table('peserta_didik_identitas')
                ->whereIn('peserta_didik_id', $pdIds)
                ->whereNotNull('dikonfirmasi_pada')
                ->orderBy('dikonfirmasi_pada', 'desc')
                ->limit(20)
                ->get();

            foreach ($idLogs as $idl) {
                $siswa = $idMap->get($idl->peserta_didik_id);
                if ($siswa) {
                    $m = $metaMap->get($siswa->peserta_didik_id);
                    $isSesuai = in_array($idl->status_konfirmasi, ['sesuai', 'diverifikasi', 'disinkronkan_dapodik'], true);
                    $activities->push((object)[
                        'waktu' => Carbon::parse($idl->dikonfirmasi_pada),
                        'siswa_nama' => $siswa->nama,
                        'siswa_nisn' => $siswa->nisn,
                        'siswa_gender' => $siswa->jenis_kelamin,
                        'foto_url' => !empty($m?->foto_path) ? asset('storage/' . ltrim($m->foto_path, '/')) : ($m?->foto_url ?? null),
                        'kategori' => 'identitas',
                        'kategori_label' => 'Konfirmasi Data',
                        'tipe_badge' => '<span class="badge" style="background: rgba(6,182,212,0.15); color: #06b6d4;"><i class="fas fa-clipboard-check me-1"></i> Konfirmasi Data</span>',
                        'aktivitas' => 'Konfirmasi Identitas Siswa',
                        'keterangan' => ($idl->catatan_siswa ?: ($isSesuai ? 'Siswa menyatakan data identitas telah sesuai' : 'Siswa menyatakan data perlu perbaikan')),
                        'status_badge' => $isSesuai
                            ? '<span class="badge badge-success" style="font-size: 0.72rem;"><i class="fas fa-circle-check me-1"></i> Sesuai</span>'
                            : '<span class="badge badge-warning" style="font-size: 0.72rem;"><i class="fas fa-pen me-1"></i> Perbaikan</span>',
                    ]);
                }
            }
        }

        return $activities->sortByDesc('waktu')->values()->take(50);
    }

    /**
     * Siapkan metrik & data khusus Guru Piket
     */
    private function preparePiketData(): array
    {
        $today = date('Y-m-d');
        $dayOfWeek = date('N'); // 1 = Senin, 7 = Minggu

        $jadwalHariIni = DB::table('pembelajaran as p')
            ->join('rombongan_belajar as rb', 'p.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
            ->where('rb.jenis_rombel', '1')
            ->select('p.nama_mata_pelajaran', 'rb.nama as rombel_nama', 'gtk.nama as guru_nama', 'p.jam_mengajar_per_minggu')
            ->limit(10)
            ->get();

        return [
            'todayDate' => $today,
            'jadwalHariIni' => $jadwalHariIni,
            'totalJadwal' => DB::table('pembelajaran')->count(),
        ];
    }

    /**
     * Siapkan metrik & data khusus Kepala TAS
     */
    private function prepareKepalaTasData(): array
    {
        $totalTendik = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'LIKE', '%Tenaga Kependidikan%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Tata Usaha%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Laboran%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Pustakawan%');
            })
            ->count();

        $totalSuratMasuk = \Illuminate\Support\Facades\Schema::hasTable('surat_masuk') ? DB::table('surat_masuk')->count() : 0;
        $totalSuratKeluar = \Illuminate\Support\Facades\Schema::hasTable('surat_keluar') ? DB::table('surat_keluar')->count() : 0;

        return [
            'totalTendik' => $totalTendik,
            'totalSuratMasuk' => $totalSuratMasuk,
            'totalSuratKeluar' => $totalSuratKeluar,
        ];
    }

    /**
     * Siapkan metrik & data khusus Laboratorium
     */
    private function prepareLaboranData(): array
    {
        return [
            'totalLabRuang' => DB::table('rombongan_belajar')->where('jenis_rombel', '!=', '1')->count() ?: 4,
            'kondisiAlatBaik' => 96,
        ];
    }
}
