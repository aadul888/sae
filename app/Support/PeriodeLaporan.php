<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Resolusi rentang tanggal untuk laporan berkala modul Tendik (Teknisi, dll.).
 *
 * Mendukung lima tipe periode yang diminta pemilik modul:
 *   minggu | bulan | triwulan | semester | tahun
 *
 * Dipusatkan di sini agar modul Teknisi tidak menduplikasi logika rentang yang
 * sudah ada di `LaboranJadwalService::resolveDateRange` (yang khusus jadwal
 * praktik) dan supaya seluruh modul Tendik memakai definisi yang identik.
 */
final class PeriodeLaporan
{
    /** Tipe periode yang didukung. */
    public const TIPE = ['minggu', 'bulan', 'triwulan', 'semester', 'tahun'];

    /**
     * Rentang tanggal dari parameter request.
     *
     * @return array{tipe:string, start:string, end:string, label:string, singkat:string}
     */
    public static function resolve(
        ?string $tipe,
        int $bulan,
        int $tahun,
        int $triwulan,
        string $semester,
        string $tahunAjaran,
        ?string $tanggal = null
    ): array {
        $tipe = strtolower((string) $tipe);
        if (!in_array($tipe, self::TIPE, true)) {
            $tipe = 'bulan';
        }

        $y = $tahun ?: (int) date('Y');

        if ($tipe === 'minggu') {
            $acuan = $tanggal ? Carbon::parse($tanggal) : Carbon::now();
            $start = $acuan->copy()->startOfWeek(Carbon::MONDAY);
            $end   = $start->copy()->addDays(6);

            return [
                'tipe'    => $tipe,
                'start'   => $start->toDateString(),
                'end'     => $end->toDateString(),
                'label'   => 'Minggu ' . $start->translatedFormat('d M Y') . ' s.d. ' . $end->translatedFormat('d M Y'),
                'singkat' => $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y'),
            ];
        }

        if ($tipe === 'triwulan') {
            $tw = $triwulan >= 1 && $triwulan <= 4 ? $triwulan : (int) ceil(date('n') / 3);
            $map = [
                1 => ['01-01', 3, 'Triwulan I', 'Januari - Maret'],
                2 => ['04-01', 6, 'Triwulan II', 'April - Juni'],
                3 => ['07-01', 9, 'Triwulan III', 'Juli - September'],
                4 => ['10-01', 12, 'Triwulan IV', 'Oktober - Desember'],
            ];
            [$startMd, $endMonth, $labelTw, $bulanLabel] = $map[$tw];

            return [
                'tipe'    => $tipe,
                'start'   => "{$y}-{$startMd}",
                'end'     => Carbon::createFromDate($y, $endMonth, 1)->endOfMonth()->toDateString(),
                'label'   => "{$labelTw} ({$bulanLabel} {$y})",
                'singkat' => "TW {$tw} {$y}",
            ];
        }

        if ($tipe === 'semester') {
            [$y1, $y2] = self::tahunAjaranParts($tahunAjaran, $y);

            if ($semester === '2') {
                return [
                    'tipe'    => $tipe,
                    'start'   => "{$y2}-01-01",
                    'end'     => "{$y2}-06-30",
                    'label'   => "Semester Genap TA {$y1}/{$y2} (Januari - Juni {$y2})",
                    'singkat' => "Semester Genap {$y1}/{$y2}",
                ];
            }

            return [
                'tipe'    => $tipe,
                'start'   => "{$y1}-07-01",
                'end'     => "{$y1}-12-31",
                'label'   => "Semester Ganjil TA {$y1}/{$y2} (Juli - Desember {$y1})",
                'singkat' => "Semester Ganjil {$y1}/{$y2}",
            ];
        }

        if ($tipe === 'tahun') {
            // "per tahun" = tahun kalender (Jan - Des) sesuai pilihan tahun.
            return [
                'tipe'    => $tipe,
                'start'   => "{$y}-01-01",
                'end'     => "{$y}-12-31",
                'label'   => "Tahun {$y} (Januari - Desember)",
                'singkat' => "Tahun {$y}",
            ];
        }

        // Default: bulanan
        $b     = max(1, min(12, $bulan ?: (int) date('n')));
        $start = Carbon::createFromDate($y, $b, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return [
            'tipe'    => 'bulan',
            'start'   => $start->toDateString(),
            'end'     => $end->toDateString(),
            'label'   => 'Bulan ' . $start->translatedFormat('F Y'),
            'singkat' => $start->translatedFormat('M Y'),
        ];
    }

    /**
     * Pecah tahun ajaran "2026/2027" menjadi [2026, 2027].
     *
     * @return array{0:int,1:int}
     */
    public static function tahunAjaranParts(string $tahunAjaran, ?int $fallbackYear = null): array
    {
        $y = $fallbackYear ?: (int) date('Y');

        if (preg_match('#^(\d{4})\s*/\s*(\d{4})$#', trim($tahunAjaran), $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        // Tanpa tahun ajaran valid: turunkan dari bulan berjalan (Juli = awal TA).
        $y1 = (int) date('n') >= 7 ? $y : $y - 1;

        return [$y1, $y1 + 1];
    }

    /**
     * Label tombol/tab periode.
     *
     * @return array<string,string>
     */
    public static function labelTipe(): array
    {
        return [
            'minggu'   => 'Mingguan',
            'bulan'    => 'Bulanan',
            'triwulan' => 'Triwulan',
            'semester' => 'Semester',
            'tahun'    => 'Tahunan',
        ];
    }
}
