<?php

namespace App\Http\Controllers\Teknisi;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Services\TeknisiTupoksiImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Laporan & Import Excel generik untuk modul TUPOKSI Teknisi (Indikator #1–#5).
 *
 * Menangani tiga aksi yang seragam untuk semua modul: unduh template, impor
 * Excel, dan cetak laporan (satu catatan per halaman). Spesifikasi per modul
 * dipusatkan di {@see TeknisiTupoksiImportService}.
 */
class TeknisiTupoksiReportController extends TeknisiBaseController
{
    public function template(Request $request, string $modul, TeknisiTupoksiImportService $service)
    {
        [$res] = $this->boot($request);
        if ($res) return $res;

        $path = $service->generateTemplateFile($modul);

        return response()
            ->download($path, $service->fileName($modul), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function import(Request $request, string $modul, TeknisiTupoksiImportService $service)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate(['file' => 'required|file|max:10240']);

        try {
            $hasil = $service->import($request->file('file'), $modul, $context);
        } catch (\RuntimeException | \InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$hasil['imported']} baris baru, {$hasil['updated']} diperbarui.",
            'count'   => $hasil['imported'] + $hasil['updated'],
        ]);
    }

    public function cetak(Request $request, string $modul, TeknisiTupoksiImportService $service)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $spec = $service->spec($modul);
        $table = $spec['table'];
        $dateCol = $service->dateColumn($modul);

        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));

        $query = DB::table($table);
        if ($dateCol && $dari !== '')   $query->where($dateCol, '>=', $dari);
        if ($dateCol && $sampai !== '') $query->where($dateCol, '<=', $sampai);

        $urutan = $dateCol ?: 'id';
        $rows = $query->orderByDesc($urutan)->orderByDesc('id')->get();

        $types = $service->columnTypes($modul);
        $fields = $service->cetakFields($modul);

        $items = $rows->map(function ($row) use ($fields, $types) {
            $out = ['nomor' => $row->{$this->nomorColumn($row, $types)} ?? null, 'fields' => []];
            foreach ($fields as [$label, $key]) {
                $out['fields'][] = ['label' => $label, 'value' => $this->format($row->{$key} ?? null, $types[$key] ?? 'string')];
            }

            return (object) $out;
        });

        $range = [
            'label' => ($dari !== '' || $sampai !== '')
                ? Carbon::parse($dari ?: '1900-01-01')->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($sampai ?: now()->toDateString())->translatedFormat('d F Y')
                : 'Seluruh Periode',
            'dari' => $dari,
            'sampai' => $sampai,
        ];

        return view('dashboard.teknisi.laporan-cetak', [
            'modul'      => $modul,
            'title'      => $service->cetakTitle($modul),
            'items'      => $items,
            'range'      => $range,
            'sekolah'    => Sekolah::first(),
            'sekolahMeta' => SekolahMeta::first(),
            'dicetakPada' => now(),
        ]);
    }

    private function nomorColumn(object $row, array $types): string
    {
        foreach (array_keys($types) as $col) {
            if (str_starts_with($col, 'nomor_')) {
                return $col;
            }
        }

        return 'id';
    }

    private function format(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($type) {
            'date'    => Carbon::parse($value)->translatedFormat('d F Y'),
            'decimal' => number_format((float) $value, 2, ',', '.'),
            'paths'   => $this->formatPaths($value),
            'sarana'  => $this->formatSarana($value),
            default   => (string) $value,
        };
    }

    private function formatPaths(mixed $value): string
    {
        $arr = is_string($value) ? (json_decode($value, true) ?: []) : (array) $value;

        return empty($arr) ? '—' : implode(', ', $arr);
    }

    private function formatSarana(mixed $value): string
    {
        $arr = is_string($value) ? (json_decode($value, true) ?: []) : (array) $value;
        if (empty($arr)) {
            return '—';
        }

        return collect($arr)->map(fn ($s) => trim(($s['nama'] ?? '-') . ' (' . ($s['jumlah'] ?? 0) . ' ' . ($s['kondisi'] ?? '') . ')'))->implode('; ');
    }
}
