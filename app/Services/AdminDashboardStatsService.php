<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Support\RombelScope;
use App\Support\Table;
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
        $totalPd = Table::exists('peserta_didik') ? DB::table('peserta_didik')->count() : 0;
        $totalGuru = Table::exists('gtk') ? DB::table('gtk')->where(function ($q) {
            $q->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                ->orWhereNull('jenis_ptk_id_str');
        })->count() : 0;

        $totalTendik = Table::exists('gtk') ? DB::table('gtk')->where(function ($q) {
            $q->where('jenis_ptk_id_str', 'LIKE', '%Tenaga Kependidikan%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Tata Usaha%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Laboran%')
                ->orWhere('jenis_ptk_id_str', 'LIKE', '%Pustakawan%');
        })->count() : 0;

        // Hanya rombel reguler (jenis_rombel = 1); mapil & ekskul tidak dihitung sebagai kelas.
        $totalKelas = RombelScope::count();
        $totalPembelajaran = Table::exists('pembelajaran') ? DB::table('pembelajaran')->count() : 0;
        $totalPengguna = Table::exists('pengguna') ? DB::table('pengguna')->count() : 0;
        $sekolah = Table::exists('sekolah') ? DB::table('sekolah')->first() : null;
        $setting = Table::exists('settings') ? DB::table('settings')->first() : null;
        $lastSync = $setting->last_sync ?? null;
        $appVersion = UpdateService::CURRENT_VERSION;

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
        $todayPresensi = Table::exists('presensi_harian')
            ? DB::table('presensi_harian')->whereDate('tanggal', $today)->get()
            : collect();

        if ($todayPresensi->isNotEmpty()) {
            $hadirHariIni = $todayPresensi->whereIn('status', ['H', 'T'])->count();
            $presensiTodayPct = $totalPd > 0 ? min(100.0, round(($hadirHariIni / $totalPd) * 100, 1)) : 0;
            $rfidTapsToday = $todayPresensi->where('metode_masuk', 'rfid')->count() ?: $hadirHariIni;
        } else {
            $latestTgl = Table::exists('presensi_harian') ? DB::table('presensi_harian')->max('tanggal') : null;
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
        $datesPd = Table::exists('presensi_harian')
            ? DB::table('presensi_harian')->select('tanggal')->distinct()->orderByDesc('tanggal')->limit(7)->pluck('tanggal')
            : collect();
        $datesPm = Table::exists('presensi_mengajar')
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

            $recordsPd = Table::exists('presensi_harian')
                ? DB::table('presensi_harian')->whereDate('tanggal', $tgl)->get()
                : collect();

            if ($recordsPd->isNotEmpty() && $totalPd > 0) {
                $hadirPd = $recordsPd->whereIn('status', ['H', 'T'])->count();
                $pctPd = min(100.0, round(($hadirPd / $totalPd) * 100, 1));
            } else {
                $pctPd = 0.0;
            }
            $trendSiswa[] = $pctPd;

            $recordsGuru = Table::exists('presensi_mengajar')
                ? DB::table('presensi_mengajar')->whereDate('tanggal', $tgl)->get()
                : collect();

            if ($recordsGuru->isNotEmpty()) {
                $guruHadirUnik = $recordsGuru->filter(function ($item) {
                    $st = strtoupper($item->status ?? '');
                    return in_array($st, ['H', 'T', 'HADIR'], true);
                })->pluck('ptk_id')->filter()->unique()->count();

                $hariNama = Carbon::parse($tgl)->translatedFormat('l');
                $scheduledGuruCount = Table::exists('jadwal_kbm')
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
        if (Table::exists('rombongan_belajar') && Table::exists('peserta_didik')) {
            // Digabung per program keahlian: kelas X & XI/XII satu jurusan nyata.
            $rawJurusan = DB::table('rombongan_belajar as rb')
                ->join('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id')
                ->select(
                    'rb.jurusan_id as kode',
                    DB::raw('COALESCE(rb.jurusan_id_str, "Umum") as nama'),
                    DB::raw('count(pd.peserta_didik_id) as total')
                )
                ->groupBy('rb.jurusan_id', 'rb.jurusan_id_str')
                ->get();

            $singkatanMap = Table::exists('jurusan_meta')
                ? DB::table('jurusan_meta')->whereNotNull('singkatan')->where('singkatan', '<>', '')->pluck('singkatan', 'jurusan_id')
                : collect();

            $jurusanStats = collect(\App\Support\ProgramKeahlian::mergeRows($rawJurusan))
                ->sortByDesc('total')
                ->take(6)
                ->map(function ($item) use ($singkatanMap) {
                    $singkatan = $singkatanMap->get($item['kode']);
                    foreach ($item['ids'] as $id) {
                        if (empty($singkatan)) $singkatan = $singkatanMap->get($id);
                    }

                    return [
                        'jurusan'      => !empty($singkatan) ? $singkatan : $item['nama'],
                        'nama_lengkap' => $item['nama'],
                        'total'        => (int) $item['total'],
                    ];
                })
                ->values();
        }

        // Chart Doughnut: Komposisi GTK Pendidik vs Tendik
        $gtkComposition = [
            'guru' => $totalGuru,
            'tendik' => $totalTendik,
        ];

        // Chart Polar Area: Siswa per Tingkat (hanya rombel reguler, agar tidak
        // terhitung ganda dari rombel mapil/ekskul yang menampung siswa sama).
        $tingkatStats = Table::exists('rombongan_belajar') && Table::exists('peserta_didik')
            ? \App\Support\RombelScope::reguler(
                DB::table('rombongan_belajar as rb')
                    ->join('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id'),
                'rb.'
            )
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

        if (Table::exists('peserta_didik')) {
            foreach ($bulanList as $b) {
                $chartSiswaBulanan['labels'][] = $b['label'];
                $endOfMonth = Carbon::create($b['y'], $b['m'], 1)->endOfMonth()->toDateString();

                $count = DB::table('peserta_didik')
                    ->where(function ($q) use ($endOfMonth) {
                        $q->whereNull('tanggal_masuk_sekolah')
                          ->orWhere('tanggal_masuk_sekolah', '<=', $endOfMonth);
                    })
                    ->count();

                if (Table::exists('peserta_didik_tidak_aktif')) {
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

        // Chart Bar: Statistik Usia Peserta Didik (< 15, 15, 16, 17, 18, > 18)
        $usiaBuckets = ['< 15' => 0, '15' => 0, '16' => 0, '17' => 0, '18' => 0, '> 18' => 0];
        if (Table::exists('peserta_didik')) {
            DB::table('peserta_didik')
                ->whereNotNull('tanggal_lahir')
                ->where('tanggal_lahir', '<>', '')
                ->pluck('tanggal_lahir')
                ->each(function ($tgl) use (&$usiaBuckets) {
                    try {
                        $umur = Carbon::parse($tgl)->age;
                    } catch (\Throwable $e) {
                        return;
                    }
                    if ($umur < 15) {
                        $usiaBuckets['< 15']++;
                    } elseif ($umur > 18) {
                        $usiaBuckets['> 18']++;
                    } else {
                        $usiaBuckets[(string) $umur]++;
                    }
                });
        }
        $usiaStats = [
            'labels' => array_keys($usiaBuckets),
            'data'   => array_values($usiaBuckets),
            'total'  => array_sum($usiaBuckets),
        ];

        // Chart Bar: Kondisi & Jumlah Ruangan dan Aset Sarpras
        $kondisiKeys = ['baik', 'rusak_ringan', 'rusak_berat'];
        $ruangCounts = array_fill_keys($kondisiKeys, 0);
        if (Table::exists('sarpras_ruang')) {
            DB::table('sarpras_ruang')
                ->select('kondisi', DB::raw('count(*) as total'))
                ->groupBy('kondisi')
                ->get()
                ->each(function ($r) use (&$ruangCounts) {
                    $k = $r->kondisi ?: 'baik';
                    if (array_key_exists($k, $ruangCounts)) $ruangCounts[$k] += (int) $r->total;
                });
        }

        $asetCounts = array_fill_keys($kondisiKeys, 0);
        if (Table::exists('sarpras_aset')) {
            $cols = [
                'baik'         => Table::hasColumn('sarpras_aset', 'jumlah_baik') ? 'jumlah_baik' : null,
                'rusak_ringan' => Table::hasColumn('sarpras_aset', 'jumlah_rusak_ringan') ? 'jumlah_rusak_ringan' : null,
                'rusak_berat'  => Table::hasColumn('sarpras_aset', 'jumlah_rusak_berat') ? 'jumlah_rusak_berat' : null,
            ];
            $row = DB::table('sarpras_aset')->selectRaw(
                collect($cols)->map(fn ($c, $k) => 'COALESCE(SUM(' . ($c ?: '0') . '),0) as ' . $k)->implode(', ')
            )->first();
            if ($row) {
                foreach ($kondisiKeys as $k) {
                    $asetCounts[$k] = (int) ($row->{$k} ?? 0);
                }
            }
        }

        $ruangAsetStats = [
            'labels'        => ['Baik', 'Rusak Ringan', 'Rusak Berat'],
            'ruangan'       => array_values($ruangCounts),
            'aset'          => array_values($asetCounts),
            'total_ruangan' => array_sum($ruangCounts),
            'total_aset'    => array_sum($asetCounts),
        ];

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
        $availableModules = Table::exists('admin_aktivitas') 
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
            'usiaStats',
            'ruangAsetStats',
            'aktivitasLogs',
            'availableModules',
            'perPage'
        );
    }
}
