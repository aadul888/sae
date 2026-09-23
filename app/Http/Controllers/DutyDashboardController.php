<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            $specificData = $this->prepareWaliKelasData($ptkId, $userAssignment);
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
     * Siapkan metrik & data khusus Wali Kelas
     */
    private function prepareWaliKelasData(?string $ptkId, $userAssignment): array
    {
        $rombelId = $userAssignment?->rombel_id;

        if (!$rombelId && $ptkId) {
            $rombelId = DB::table('rombongan_belajar')
                ->where('ptk_id', $ptkId)
                ->where('jenis_rombel', '1')
                ->value('rombongan_belajar_id');
        }

        $rombel = null;
        $totalSiswa = 0;
        $siswaList = collect();
        $presensiHariIni = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'total' => 0];

        if ($rombelId) {
            $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->first();

            $siswaList = DB::table('anggota_rombel as ar')
                ->join('peserta_didik as pd', 'ar.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->where('ar.rombongan_belajar_id', $rombelId)
                ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.jenis_kelamin')
                ->orderBy('pd.nama')
                ->get();

            $totalSiswa = $siswaList->count();

            // Cek presensi hari ini jika tabel presensi harian siswa tersedia
            $today = date('Y-m-d');
            if (\Illuminate\Support\Facades\Schema::hasTable('presensi_peserta_didik')) {
                $counts = DB::table('presensi_peserta_didik')
                    ->where('rombongan_belajar_id', $rombelId)
                    ->whereDate('tanggal', $today)
                    ->select('status_kehadiran', DB::raw('count(*) as count'))
                    ->groupBy('status_kehadiran')
                    ->pluck('count', 'status_kehadiran')
                    ->toArray();

                $presensiHariIni['H'] = $counts['H'] ?? ($counts['hadir'] ?? 0);
                $presensiHariIni['S'] = $counts['S'] ?? ($counts['sakit'] ?? 0);
                $presensiHariIni['I'] = $counts['I'] ?? ($counts['izin'] ?? 0);
                $presensiHariIni['A'] = $counts['A'] ?? ($counts['alpa'] ?? 0);
                $presensiHariIni['total'] = array_sum($presensiHariIni);
            }
        }

        return [
            'rombel' => $rombel,
            'totalSiswa' => $totalSiswa,
            'siswaList' => $siswaList,
            'presensiHariIni' => $presensiHariIni,
        ];
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
