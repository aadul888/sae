<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dokumen cetak per-halaman modul Keamanan.
 *
 * Dipakai controller modul (buku tamu, pelanggaran, izin, patroli/insiden)
 * untuk merender satu halaman cetak berisi rincian data yang sedang difilter
 * pengguna di halaman modul tersebut.
 */
trait CetakKeamanan
{
    /**
     * Render halaman cetak untuk satu modul.
     *
     * @param string               $modul      kode modul: tamu|pelanggaran|izin|patroli|insiden
     * @param string               $judul      judul dokumen
     * @param string|null          $filterInfo ringkas filter aktif (opsional)
     * @param \Illuminate\Support\Collection $rows
     */
    protected function renderCetak(string $modul, string $judul, ?string $filterInfo, $rows)
    {
        return view('dashboard.keamanan.cetak', [
            'modul' => $modul,
            'judul' => $judul,
            'filterInfo' => $filterInfo,
            'rows' => $rows,
            'kepalaSekolah' => $this->kepalaSekolah(),
            'koordinator' => $this->koordinatorKeamanan(),
        ]);
    }

    /**
     * Rentang tanggal awal & akhir berdasarkan tipe periode.
     * Mendukung: minggu, bulan, triwulan, semester, tahun ajaran.
     */
    protected function resolveDateRange($periodeTipe, $bulan, $tahun, $triwulan, $semester, $tahunAjaran, $minggu = null): array
    {
        $startDate = null;
        $endDate = null;
        $labelPeriode = '';

        if ($periodeTipe === 'minggu') {
            try {
                $anchor = Carbon::parse($minggu ?: date('Y-m-d'));
            } catch (\Throwable $e) {
                $anchor = Carbon::now();
            }
            $startCarbon = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $endCarbon = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
            $startDate = $startCarbon->toDateString();
            $endDate = $endCarbon->toDateString();
            $labelPeriode = 'Minggu, ' . $startCarbon->translatedFormat('d M Y') . ' — ' . $endCarbon->translatedFormat('d M Y');
        } elseif ($periodeTipe === 'triwulan') {
            $y = (int) ($tahun ?: date('Y'));
            $tw = (int) ($triwulan ?: ceil(date('n') / 3));
            switch ($tw) {
                case 1:
                    $startDate = "{$y}-01-01";
                    $endDate = Carbon::createFromDate($y, 3, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan I (Januari - Maret {$y})";
                    break;
                case 2:
                    $startDate = "{$y}-04-01";
                    $endDate = Carbon::createFromDate($y, 6, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan II (April - Juni {$y})";
                    break;
                case 3:
                    $startDate = "{$y}-07-01";
                    $endDate = Carbon::createFromDate($y, 9, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan III (Juli - September {$y})";
                    break;
                default:
                    $startDate = "{$y}-10-01";
                    $endDate = Carbon::createFromDate($y, 12, 1)->endOfMonth()->toDateString();
                    $labelPeriode = "Triwulan IV (Oktober - Desember {$y})";
                    break;
            }
        } elseif ($periodeTipe === 'semester') {
            $parts = explode('/', $tahunAjaran ?: date('Y') . '/' . (date('Y') + 1));
            $y1 = (int) ($parts[0] ?? date('Y'));
            $y2 = (int) ($parts[1] ?? ($y1 + 1));
            if ($semester == '1') {
                $startDate = "{$y1}-07-01";
                $endDate = "{$y1}-12-31";
                $labelPeriode = "Semester Ganjil TA {$y1}/{$y2} (Juli - Desember {$y1})";
            } else {
                $startDate = "{$y2}-01-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Semester Genap TA {$y1}/{$y2} (Januari - Juni {$y2})";
            }
        } elseif ($periodeTipe === 'tahun' || $periodeTipe === 'tahun_ajaran') {
            if (str_contains((string) $tahunAjaran, '/')) {
                $parts = explode('/', $tahunAjaran);
                $y1 = (int) $parts[0];
                $y2 = (int) ($parts[1] ?? ($y1 + 1));
                $startDate = "{$y1}-07-01";
                $endDate = "{$y2}-06-30";
                $labelPeriode = "Tahun Ajaran {$y1}/{$y2} (1 Juli {$y1} - 30 Juni {$y2})";
            } else {
                $y = (int) ($tahun ?: date('Y'));
                $startDate = "{$y}-01-01";
                $endDate = "{$y}-12-31";
                $labelPeriode = "Tahun Kalender {$y}";
            }
        } else {
            // Mode Bulan (default)
            $b = (int) ($bulan ?: date('n'));
            $y = (int) ($tahun ?: date('Y'));
            $startCarbon = Carbon::createFromDate($y, $b, 1)->startOfMonth();
            $endCarbon = Carbon::createFromDate($y, $b, 1)->endOfMonth();
            $startDate = $startCarbon->toDateString();
            $endDate = $endCarbon->toDateString();
            $labelPeriode = 'Bulan ' . $startCarbon->translatedFormat('F Y');
        }

        return ['start' => $startDate, 'end' => $endDate, 'label' => $labelPeriode];
    }

    /**
     * Baca parameter periode dari request & kembalikan rentang tanggal,
     * atau null bila pengguna tidak memilih periode (mode tanggal tunggal).
     */
    protected function periodeDariRequest($request): ?array
    {
        if (!$request->filled('periode')) {
            return null;
        }

        $curYear = (int) date('Y');
        $defaultTA = date('n') >= 7 ? "{$curYear}/" . ($curYear + 1) : ($curYear - 1) . "/{$curYear}";

        return $this->resolveDateRange(
            (string) $request->query('periode', 'bulan'),
            (int) $request->query('bulan', date('n')),
            (int) $request->query('tahun', $curYear),
            (int) $request->query('triwulan', (int) ceil(date('n') / 3)),
            (string) $request->query('semester', date('n') >= 7 ? '1' : '2'),
            (string) $request->query('tahun_ajaran', $defaultTA),
            (string) $request->query('minggu', date('Y-m-d'))
        );
    }

    private function kepalaSekolah()
    {
        if (!Table::exists('gtk')) {
            return null;
        }

        return DB::table('gtk')
            ->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->first();
    }

    private function koordinatorKeamanan()
    {
        if (!Table::exists('ptk_tugas_tambahan') || !Table::exists('ref_tugas_tambahan')) {
            return null;
        }

        return DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('ptt.is_active', true)
            ->where(function ($q) {
                $q->where('rtt.kode', 'like', '%KEAMANAN%')
                    ->orWhere('rtt.nama', 'like', '%Keamanan%');
            })
            ->select('gtk.nama', 'gtk.nip')
            ->first();
    }
}
