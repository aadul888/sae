<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service agregasi data metrik, visualisasi statistik, dan log aktivitas dashboard Admin.
 * ponytail: static-calculator; upgrade ke background cache jika query tren 7 hari memakan > 150ms.
 */
class AdminDashboardStatsService
{
    /**
     * Kumpulkan seluruh dataset untuk tampilan dashboard administrator.
     */
    public static function getStats(Request $request): array
    {
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
        $setting = Schema::hasTable('settings') ? DB::table('settings')->first() : null;
        $lastSync = $setting->last_sync ?? null;
        $appVersion = $setting->app_version ?? UpdateService::CURRENT_VERSION;

        // Otomatis lapor monitoring ke sae-core (throttled 3 jam agar realtime tanpa membebani)
        try {
            $lastPing = Cache::get('sae_last_monitoring_ping');
            if (!$lastPing || Carbon::parse($lastPing)->isBefore(now()->subHours(3))) {
                Cache::put('sae_last_monitoring_ping', now()->toIso8601String(), 86400);
                MonitoringReporterService::reportAsync();
            }
        } catch (\Throwable $e) {}

        // Presensi Masuk Hari Ini (Data Real)
        $today = date('Y-m-d');
        $todayPresensi = Schema::hasTable('presensi_harian')
            ? DB::table('presensi_harian')->whereDate('tanggal', $today)->get()
            : collect();

        if ($todayPresensi->isNotEmpty()) {
            $hadirHariIni = $todayPresensi->whereIn('status', ['H', 'T'])->count();
            $presensiTodayPct = $totalPd > 0 ? min(100.0, round(($hadirHariIni / $totalPd) * 100, 1)) : 0;
            $rfidTapsToday = $todayPresensi->where('metode_masuk', 'rfid')->count() ?: $hadirHariIni;
        } else {
            $latestTgl = Schema::hasTable('presensi_harian') ? DB::table('presensi_harian')->max('tanggal') : null;
            if ($latestTgl) {
                $latestRecords = DB::table('presensi_harian')->whereDate('tanggal', $latestTgl)->get();
                $hadirHariIni = $latestRecords->whereIn('status', ['H', 'T'])->count();
                $presensiTodayPct = $totalPd > 0
                    ? min(100.0, round(($hadirHariIni / $totalPd) * 100, 1))
                    : 0;
                $rfidTapsToday = $latestRecords->where('metode_masuk', 'rfid')->count() ?: $hadirHariIni;
            } else {
                $presensiTodayPct = 0;
                $rfidTapsToday = 0;
            }
        }

        $stats = [
            'total_peserta_didik' => $totalPd ?: 0,
            'total_guru'          => $totalGuru ?: 0,
            'total_tendik'        => $totalTendik ?: 0,
            'total_kelas'         => $totalKelas ?: 0,
            'total_pembelajaran'  => $totalPembelajaran ?: 0,
            'total_pengguna'      => $totalPengguna ?: 0,
            'presensi_today'      => $presensiTodayPct,
            'rfid_taps'           => $rfidTapsToday,
            'sync_dapodik'        => $lastSync ? Carbon::parse($lastSync)->format('d M Y, H:i') . ' WIB' : 'Belum Sinkron',
            'app_version'         => $appVersion,
        ];

        // 1. Status Update Sistem
        $updateStatus = [
            'updates_available' => false,
            'behind_count' => 0,
            'current_version' => $appVersion,
            'changes' => [],
        ];
        try {
            $updateService = new UpdateService();
            $updateStatus = $updateService->checkUpdate();
        } catch (\Throwable $e) {}

        // 2. Data Visualisasi Charts Interaktif
        $datesPd = Schema::hasTable('presensi_harian')
            ? DB::table('presensi_harian')->select('tanggal')->distinct()->orderByDesc('tanggal')->limit(7)->pluck('tanggal')
            : collect();
        $datesPm = Schema::hasTable('presensi_mengajar')
            ? DB::table('presensi_mengajar')->select('tanggal')->distinct()->orderByDesc('tanggal')->limit(7)->pluck('tanggal')
            : collect();

        $trendDates = $datesPd->merge($datesPm)->unique()->sort()->take(-7)->values();

        if ($trendDates->isEmpty()) {
            for ($i = 6; $i >= 0; $i--) {
                $trendDates->push(now()->subDays($i)->format('Y-m-d'));
            }
        }

        $trendLabels = [];
        $trendSiswa = [];
        $trendGuru = [];

        foreach ($trendDates as $tgl) {
            $trendLabels[] = Carbon::parse($tgl)->translatedFormat('d M');

            $recordsPd = Schema::hasTable('presensi_harian')
                ? DB::table('presensi_harian')->whereDate('tanggal', $tgl)->get()
                : collect();

            if ($recordsPd->isNotEmpty() && $totalPd > 0) {
                $hadirPd = $recordsPd->whereIn('status', ['H', 'T'])->count();
                $pctPd = min(100.0, round(($hadirPd / $totalPd) * 100, 1));
            } else {
                $pctPd = 0.0;
            }
            $trendSiswa[] = $pctPd;

            $recordsGuru = Schema::hasTable('presensi_mengajar')
                ? DB::table('presensi_mengajar')->whereDate('tanggal', $tgl)->get()
                : collect();

            if ($recordsGuru->isNotEmpty()) {
                $guruHadirUnik = $recordsGuru->filter(function ($item) {
                    $st = strtoupper($item->status ?? '');
                    return in_array($st, ['H', 'T', 'HADIR'], true);
                })->pluck('ptk_id')->filter()->unique()->count();

                $hariNama = Carbon::parse($tgl)->translatedFormat('l');
                $scheduledGuruCount = Schema::hasTable('jadwal_kbm')
                    ? DB::table('jadwal_kbm')
                        ->where('hari', $hariNama)
                        ->where('is_active', true)
                        ->whereNotNull('ptk_id')
                        ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                        ->distinct('ptk_id')
                        ->count('ptk_id')
                    : 0;

                $targetGuru = $scheduledGuruCount > 0 ? $scheduledGuruCount : $totalGuru;
                $pctGuru = $targetGuru > 0 ? min(100.0, round(($guruHadirUnik / $targetGuru) * 100, 1)) : 0.0;
            } else {
                $pctGuru = 0.0;
            }
            $trendGuru[] = $pctGuru;
        }

        $allValidRates = array_filter(array_merge($trendSiswa, $trendGuru), fn($v) => $v > 0);
        $avgKehadiran = !empty($allValidRates)
            ? round(array_sum($allValidRates) / count($allValidRates), 1)
            : $presensiTodayPct;

        $chartTrend = [
            'labels'  => $trendLabels,
            'siswa'   => $trendSiswa,
            'guru'    => $trendGuru,
            'gtk'     => $trendGuru,
            'average' => $avgKehadiran,
        ];

        // Chart Bar: Sebaran Siswa per Jurusan
        $jurusanStats = collect();
        if (Schema::hasTable('rombongan_belajar') && Schema::hasTable('peserta_didik')) {
            $rawJurusan = DB::table('rombongan_belajar as rb')
                ->join('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id')
                ->select(
                    'rb.jurusan_id',
                    DB::raw('COALESCE(rb.jurusan_id_str, "Umum") as jurusan'),
                    DB::raw('count(pd.peserta_didik_id) as total')
                )
                ->groupBy('rb.jurusan_id', 'rb.jurusan_id_str')
                ->orderByDesc('total')
                ->limit(6)
                ->get();

            $singkatanMap = Schema::hasTable('jurusan_meta')
                ? DB::table('jurusan_meta')->whereNotNull('singkatan')->where('singkatan', '<>', '')->pluck('singkatan', 'jurusan_id')
                : collect();

            $jurusanStats = $rawJurusan->map(function ($item) use ($singkatanMap) {
                $singkatan = $singkatanMap->get($item->jurusan_id);
                return [
                    'jurusan'      => !empty($singkatan) ? $singkatan : $item->jurusan,
                    'nama_lengkap' => $item->jurusan,
                    'total'        => (int) $item->total,
                ];
            });
        }

        // Chart Doughnut: Komposisi GTK Pendidik vs Tendik
        $gtkComposition = [
            'guru' => $totalGuru,
            'tendik' => $totalTendik,
        ];

        // Chart Polar Area: Siswa per Tingkat
        $tingkatStats = Schema::hasTable('rombongan_belajar') && Schema::hasTable('peserta_didik')
            ? DB::table('rombongan_belajar as rb')
                ->join('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id')
                ->select('rb.tingkat_pendidikan_id as tingkat', DB::raw('count(pd.peserta_didik_id) as total'))
                ->groupBy('rb.tingkat_pendidikan_id')
                ->orderBy('rb.tingkat_pendidikan_id')
                ->get()
            : collect();

        // Chart Garis Full Width: Siswa per Bulan dalam 1 TP
        $now = now();
        $startYear = $now->month >= 7 ? (int) $now->year : (int) $now->year - 1;
        $endYear = $startYear + 1;
        $taLabel = "{$startYear}/{$endYear}";

        $bulanList = [
            ['m' => 7,  'y' => $startYear, 'label' => 'Jul ' . $startYear],
            ['m' => 8,  'y' => $startYear, 'label' => 'Ags ' . $startYear],
            ['m' => 9,  'y' => $startYear, 'label' => 'Sep ' . $startYear],
            ['m' => 10, 'y' => $startYear, 'label' => 'Okt ' . $startYear],
            ['m' => 11, 'y' => $startYear, 'label' => 'Nov ' . $startYear],
            ['m' => 12, 'y' => $startYear, 'label' => 'Des ' . $startYear],
            ['m' => 1,  'y' => $endYear,   'label' => 'Jan ' . $endYear],
            ['m' => 2,  'y' => $endYear,   'label' => 'Feb ' . $endYear],
            ['m' => 3,  'y' => $endYear,   'label' => 'Mar ' . $endYear],
            ['m' => 4,  'y' => $endYear,   'label' => 'Apr ' . $endYear],
            ['m' => 5,  'y' => $endYear,   'label' => 'Mei ' . $endYear],
            ['m' => 6,  'y' => $endYear,   'label' => 'Jun ' . $endYear],
        ];

        $chartSiswaBulanan = [
            'tahun_ajaran' => $taLabel,
            'labels'       => [],
            'data'         => [],
        ];

        if (Schema::hasTable('peserta_didik')) {
            foreach ($bulanList as $b) {
                $chartSiswaBulanan['labels'][] = $b['label'];
                $endOfMonth = Carbon::create($b['y'], $b['m'], 1)->endOfMonth()->toDateString();

                $count = DB::table('peserta_didik')
                    ->where(function ($q) use ($endOfMonth) {
                        $q->whereNull('tanggal_masuk_sekolah')
                          ->orWhere('tanggal_masuk_sekolah', '<=', $endOfMonth);
                    })
                    ->count();

                if (Schema::hasTable('peserta_didik_tidak_aktif')) {
                    $countMutasiSetelah = DB::table('peserta_didik_tidak_aktif')
                        ->whereNotNull('tanggal_keluar')
                        ->where('tanggal_keluar', '>', $endOfMonth)
                        ->count();
                    $count += $countMutasiSetelah;
                }

                if (Carbon::create($b['y'], $b['m'], 1)->startOfMonth()->isAfter($now)) {
                    $count = $totalPd;
                }

                $chartSiswaBulanan['data'][] = $count;
            }
        }

        // 3. Datatable Aktivitas Administrator
        $queryLogs = AdminAktivitas::query()->latest();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $queryLogs->where(function ($q) use ($search) {
                $q->where('aktivitas', 'LIKE', "%{$search}%")
                  ->orWhere('keterangan', 'LIKE', "%{$search}%")
                  ->orWhere('modul', 'LIKE', "%{$search}%")
                  ->orWhere('admin_name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('modul')) {
            $queryLogs->where('modul', $request->input('modul'));
        }

        $perPage = (int) $request->input('perPage', 5);
        if (!in_array($perPage, [5, 10, 25, 50], true)) {
            $perPage = 5;
        }

        $aktivitasLogs = $queryLogs->paginate($perPage)->withQueryString();
        $availableModules = Schema::hasTable('admin_aktivitas') 
            ? DB::table('admin_aktivitas')->distinct()->pluck('modul')->filter()->values()
            : collect(['Dapodik', 'Formulir', 'Sistem', 'Backup', 'Pengumuman', 'Hak Akses', 'Presensi']);

        return compact(
            'stats',
            'sekolah',
            'updateStatus',
            'chartTrend',
            'jurusanStats',
            'gtkComposition',
            'tingkatStats',
            'chartSiswaBulanan',
            'aktivitasLogs',
            'availableModules',
            'perPage'
        );
    }
}
