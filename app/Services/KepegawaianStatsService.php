<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\Table;
/**
 * Service kalkulasi dataset multi-periode untuk aktivitas GTK / Tendik dan indikator kepegawaian.
 * ponytail: static-calculator; upgrade ke agregasi cron batch jika record tendik_aktivitas melebihi 100.000 baris.
 */
class KepegawaianStatsService
{
    /**
     * Bangun dataset aktivitas multi-periode untuk dashboard kepegawaian maupun bidang tendik lainnya
     */
    public static function buildMultiPeriodeData(int $totalTendik = 19, ?string $bidangFilter = null): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $factor = max(1, $totalTendik);

        $hasTable = Table::exists('tendik_aktivitas');
        $allActsQuery = $hasTable ? DB::table('tendik_aktivitas') : null;
        if ($allActsQuery && $bidangFilter) {
            $allActsQuery->where('bidang', $bidangFilter);
        }
        $allActs = $allActsQuery ? $allActsQuery->get() : collect();

        // Integrasi transaksi persuratan riil sebagai aktivitas jika log manual belum diisi
        if ($bidangFilter === 'persuratan' && $allActs->isEmpty() && Table::exists('persuratan')) {
            $realSurat = DB::table('persuratan')->get()->map(function ($s) {
                $tgl = $s->tanggal_surat ?: substr($s->created_at, 0, 10);
                $jam = substr($s->created_at ?? '08:00:00', 11, 8) ?: '08:00:00';
                $st = in_array($s->status, ['selesai', 'diarsipkan'], true) ? 'selesai' : 'proses';
                return (object) [
                    'tanggal'   => $tgl,
                    'jam_mulai' => $jam,
                    'status'    => $st,
                ];
            });
            $allActs = $allActs->concat($realSurat);
            $factor = 2;
        }

        // 1. HARI
        $hariLabels = [];
        $hariTarget = [];
        $hariSelesai = [];
        $hariProses = [];
        $todayActs = $allActs->filter(fn($a) => $a->tanggal === $today);
        for ($h = 7; $h <= 16; $h++) {
            $hStr = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
            $hariLabels[] = "{$hStr}:00";
            $hariTarget[] = (int) ceil($factor * 0.1);
            $matched = $todayActs->filter(fn($a) => substr($a->jam_mulai, 0, 2) === $hStr);
            $hariSelesai[] = $matched->where('status', 'selesai')->count();
            $hariProses[] = $matched->where('status', 'proses')->count();
        }

        // 2. MINGGU
        $mingguLabels = [];
        $mingguTarget = [];
        $mingguSelesai = [];
        $mingguProses = [];
        $startOfWeek = $now->copy()->startOfWeek();
        for ($d = 0; $d < 6; $d++) {
            $curDay = $startOfWeek->copy()->addDays($d);
            $curDateStr = $curDay->toDateString();
            $mingguLabels[] = $curDay->translatedFormat('D');
            $mingguTarget[] = (int) ceil($factor * 1.0);
            $matched = $allActs->filter(fn($a) => $a->tanggal === $curDateStr);
            $mingguSelesai[] = $matched->where('status', 'selesai')->count();
            $mingguProses[] = $matched->where('status', 'proses')->count();
        }

        // 3. BULAN
        $bulanLabels = [];
        $bulanTarget = [];
        $bulanSelesai = [];
        $bulanProses = [];
        $daysInMonth = $now->daysInMonth;
        for ($m = 1; $m <= $daysInMonth; $m++) {
            $curDateStr = $now->copy()->day($m)->toDateString();
            $bulanLabels[] = (string)$m;
            $bulanTarget[] = (int) ceil($factor * 0.9);
            $matched = $allActs->filter(fn($a) => $a->tanggal === $curDateStr);
            $bulanSelesai[] = $matched->where('status', 'selesai')->count();
            $bulanProses[] = $matched->where('status', 'proses')->count();
        }

        // 4. TRIWULAN
        $triwulanLabels = [];
        $triwulanTarget = [];
        $triwulanSelesai = [];
        $triwulanProses = [];
        $twNum = ceil($now->month / 3);
        $twStartMonth = ($twNum - 1) * 3 + 1;
        for ($tm = $twStartMonth; $tm < $twStartMonth + 3; $tm++) {
            $mCarbon = Carbon::create($now->year, $tm, 1);
            $triwulanLabels[] = $mCarbon->translatedFormat('M');
            $triwulanTarget[] = (int) ceil($factor * 20);
            $mPrefix = $mCarbon->format('Y-m');
            $matched = $allActs->filter(fn($a) => str_starts_with($a->tanggal ?? '', $mPrefix));
            $triwulanSelesai[] = $matched->where('status', 'selesai')->count();
            $triwulanProses[] = $matched->where('status', 'proses')->count();
        }

        // 5. SEMESTER
        $semesterLabels = [];
        $semesterTarget = [];
        $semesterSelesai = [];
        $semesterProses = [];
        $isGanjil = $now->month >= 7 && $now->month <= 12;
        $semStartMonth = $isGanjil ? 7 : 1;
        for ($sm = $semStartMonth; $sm < $semStartMonth + 6; $sm++) {
            $sCarbon = Carbon::create($now->year, $sm, 1);
            $semesterLabels[] = $sCarbon->translatedFormat('M');
            $semesterTarget[] = (int) ceil($factor * 20);
            $sPrefix = $sCarbon->format('Y-m');
            $matched = $allActs->filter(fn($a) => str_starts_with($a->tanggal ?? '', $sPrefix));
            $semesterSelesai[] = $matched->where('status', 'selesai')->count();
            $semesterProses[] = $matched->where('status', 'proses')->count();
        }

        // 6. TAHUN KALENDER
        $tahunLabels = [];
        $tahunTarget = [];
        $tahunSelesai = [];
        $tahunProses = [];
        for ($ym = 1; $ym <= 12; $ym++) {
            $yCarbon = Carbon::create($now->year, $ym, 1);
            $tahunLabels[] = $yCarbon->translatedFormat('M');
            $tahunTarget[] = (int) ceil($factor * 20);
            $yPrefix = $yCarbon->format('Y-m');
            $matched = $allActs->filter(fn($a) => str_starts_with($a->tanggal ?? '', $yPrefix));
            $tahunSelesai[] = $matched->where('status', 'selesai')->count();
            $tahunProses[] = $matched->where('status', 'proses')->count();
        }

        // 7. TAHUN AJARAN
        $taLabels = [];
        $taTarget = [];
        $taSelesai = [];
        $taProses = [];
        $taStartYear = $now->month >= 7 ? $now->year : $now->year - 1;
        for ($tam = 7; $tam <= 18; $tam++) {
            $actualM = $tam > 12 ? $tam - 12 : $tam;
            $actualY = $tam > 12 ? $taStartYear + 1 : $taStartYear;
            $taCarbon = Carbon::create($actualY, $actualM, 1);
            $taLabels[] = $taCarbon->translatedFormat('M y');
            $taTarget[] = (int) ceil($factor * 20);
            $taPrefix = $taCarbon->format('Y-m');
            $matched = $allActs->filter(fn($a) => str_starts_with($a->tanggal ?? '', $taPrefix));
            $taSelesai[] = $matched->where('status', 'selesai')->count();
            $taProses[] = $matched->where('status', 'proses')->count();
        }

        return [
            'hari' => [
                'label' => 'Hari Ini (' . $now->translatedFormat('d M Y') . ')',
                'labels' => $hariLabels,
                'target' => $hariTarget,
                'selesai' => $hariSelesai,
                'proses' => $hariProses,
                'total_target' => array_sum($hariTarget),
                'total_selesai' => array_sum($hariSelesai),
            ],
            'minggu' => [
                'label' => 'Minggu Ini (' . $startOfWeek->translatedFormat('d M') . ' - ' . $startOfWeek->copy()->addDays(5)->translatedFormat('d M Y') . ')',
                'labels' => $mingguLabels,
                'target' => $mingguTarget,
                'selesai' => $mingguSelesai,
                'proses' => $mingguProses,
                'total_target' => array_sum($mingguTarget),
                'total_selesai' => array_sum($mingguSelesai),
            ],
            'bulan' => [
                'label' => 'Bulan Ini (' . $now->translatedFormat('F Y') . ')',
                'labels' => $bulanLabels,
                'target' => $bulanTarget,
                'selesai' => $bulanSelesai,
                'proses' => $bulanProses,
                'total_target' => array_sum($bulanTarget),
                'total_selesai' => array_sum($bulanSelesai),
            ],
            'triwulan' => [
                'label' => 'Triwulan ' . $twNum . ' (' . $now->year . ')',
                'labels' => $triwulanLabels,
                'target' => $triwulanTarget,
                'selesai' => $triwulanSelesai,
                'proses' => $triwulanProses,
                'total_target' => array_sum($triwulanTarget),
                'total_selesai' => array_sum($triwulanSelesai),
            ],
            'semester' => [
                'label' => ($isGanjil ? 'Semester Ganjil' : 'Semester Genap') . ' ' . $now->year,
                'labels' => $semesterLabels,
                'target' => $semesterTarget,
                'selesai' => $semesterSelesai,
                'proses' => $semesterProses,
                'total_target' => array_sum($semesterTarget),
                'total_selesai' => array_sum($semesterSelesai),
            ],
            'tahun' => [
                'label' => 'Tahun Kalender ' . $now->year,
                'labels' => $tahunLabels,
                'target' => $tahunTarget,
                'selesai' => $tahunSelesai,
                'proses' => $tahunProses,
                'total_target' => array_sum($tahunTarget),
                'total_selesai' => array_sum($tahunSelesai),
            ],
            'tahun_ajaran' => [
                'label' => "Tahun Ajaran {$taStartYear}/" . ($taStartYear + 1),
                'labels' => $taLabels,
                'target' => $taTarget,
                'selesai' => $taSelesai,
                'proses' => $taProses,
                'total_target' => array_sum($taTarget),
                'total_selesai' => array_sum($taSelesai),
            ],
        ];
    }
}
