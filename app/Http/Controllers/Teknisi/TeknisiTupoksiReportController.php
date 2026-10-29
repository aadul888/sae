<?php

namespace App\Http\Controllers\Teknisi;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Services\TeknisiTupoksiImportService;
use App\Support\PeriodeLaporan;
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

        $this->logImport(
            $context,
            $modul,
            $service->title($modul),
            $request->file('file'),
            $hasil['imported'],
            $hasil['updated']
        );

        $oleh = trim((string) ($context['nama'] ?? '')) ?: 'akun Anda';

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$hasil['imported']} baris baru, {$hasil['updated']} diperbarui. Diunggah oleh {$oleh}.",
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

        $mode = strtolower((string) $request->query('mode', 'ringkasan'));
        if (!in_array($mode, ['ringkasan', 'lengkap'], true)) {
            $mode = 'ringkasan';
        }
        $orientasi = strtolower((string) $request->query('orientasi', 'portrait'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'portrait';
        }

        // Prioritas: tipe periode (minggu/bulan/triwulan/semester/tahun).
        // Tautan lama yang memakai `?dari=&sampai=` tetap didukung.
        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));

        if ($request->filled('periode')) {
            $range = PeriodeLaporan::resolve(
                (string) $request->query('periode'),
                (int) $request->query('bulan', date('n')),
                (int) $request->query('tahun', date('Y')),
                (int) $request->query('triwulan', (int) ceil(date('n') / 3)),
                (string) $request->query('semester', date('n') >= 7 ? '1' : '2'),
                (string) $request->query('tahun_ajaran', ''),
                $request->query('tanggal')
            );
            $dari   = $range['start'];
            $sampai = $range['end'];
        } else {
            $range = [
                'tipe'    => 'kustom',
                'start'   => $dari,
                'end'     => $sampai,
                'label'   => ($dari !== '' || $sampai !== '')
                    ? Carbon::parse($dari ?: '1900-01-01')->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($sampai ?: now()->toDateString())->translatedFormat('d F Y')
                    : 'Seluruh Periode',
                'singkat' => ($dari !== '' || $sampai !== '') ? $dari . ' - ' . $sampai : 'Seluruh Periode',
            ];
        }

        $query = DB::table($table);
        // Cakupan per akun: teknisi hanya mencetak catatannya sendiri.
        $this->scopeOwned($query, $context, null, $this->ownerColumn($table));
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

        return view('dashboard.teknisi.laporan-cetak', [
            'modul'      => $modul,
            'title'      => $service->cetakTitle($modul),
            'items'      => $items,
            'rows'       => $rows,
            'types'      => $types,
            'fieldMap'   => $fields,
            'range'      => $range,
            'mode'       => $mode,
            'orientasi'  => $orientasi,
            'teknisiTtd' => $this->teknisiTtd($context),
            'sekolah'    => Sekolah::first(),
            'sekolahMeta' => SekolahMeta::first(),
            'dicetakPada' => now(),
        ]);
    }

    /**
     * Pihak penanda tangan dokumen Teknisi: Teknisi pelaksana, Kepala TAS, dan
     * Kepala Sekolah. Diresolusi terpusat agar perilakunya seragam antar modul.
     *
     * @return array{petugas:array{nama:string,nip:?string}, kepalaTas:?object, kepalaSekolah:?object}
     */
    private function teknisiTtd(array $context): array
    {
        $dokumen = app(\App\Services\LaboranDokumenService::class);

        $petugas = [
            'nama' => $context['nama'] ?? '',
            'nip'  => null,
        ];

        $ptkId = $context['ptk_id'] ?? null;
        if ($ptkId && \App\Support\Table::exists('gtk')) {
            $petugas['nip'] = DB::table('gtk')->where('ptk_id', $ptkId)->value('nip') ?: null;
        }

        return [
            'petugas'       => $petugas,
            'kepalaTas'     => $dokumen->jabatan('KEPALA_TAS'),
            'kepalaSekolah' => $dokumen->kepalaSekolah(),
        ];
    }

    /**
     * Kolom penanda pemilik baris per tabel TUPOKSI.
     */
    private function ownerColumn(string $table): string
    {
        return 'petugas_ptk_id';
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
