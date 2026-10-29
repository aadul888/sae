<?php

namespace App\Http\Controllers\Teknisi;

use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\PeriodeLaporan;
use App\Support\TeknisiModuleRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Controller generik modul TEKNISI LAPANGAN.
 *
 * Satu controller melayani seluruh modul yang terdaftar di
 * {@see TeknisiModuleRegistry}: daftar, tambah/ubah/hapus, unduh template,
 * impor Excel, dan cetak laporan (satu catatan per halaman).
 *
 * Semua data dibatasi per akun pembuat (lihat TeknisiBaseController::scopeOwned):
 * teknisi lapangan hanya melihat & mengubah catatannya sendiri, sedangkan
 * koordinator (Kepala TAS / Waka Sarpras / admin) melihat seluruh data.
 */
class TeknisiModuleController extends TeknisiBaseController
{
    public function index(Request $request, string $modul)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $spec = TeknisiModuleRegistry::get($modul);

        $search  = trim((string) $request->query('q', ''));
        $dari    = trim((string) $request->query('dari', ''));
        $sampai  = trim((string) $request->query('sampai', ''));
        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $query = DB::table($spec['table'] . ' as t')->select('t.*');
        $this->applyFixed($query, $spec, 't');
        $this->scopeOwned($query, $context, 't', 'petugas_ptk_id');

        if ($search !== '') {
            $query->where(function ($q) use ($spec) {
                foreach ($spec['list'] as $col) {
                    $q->orWhere('t.' . $col, 'like', '%' . request('q') . '%');
                }
            });
        }
        if ($spec['dateCol'] && $dari !== '')   $query->where('t.' . $spec['dateCol'], '>=', $dari);
        if ($spec['dateCol'] && $sampai !== '') $query->where('t.' . $spec['dateCol'], '<=', $sampai);

        $statQuery = DB::table($spec['table']);
        $this->applyFixed($statQuery, $spec);
        $this->scopeOwned($statQuery, $context);

        $statCards = [];
        foreach ($spec['stats'] as $stat) {
            $q = (clone $statQuery);
            if (isset($stat['where']))        $q->where($stat['where'][0], $stat['where'][1]);
            if (isset($stat['whereIn']))      $q->whereIn($stat['whereIn'][0], $stat['whereIn'][1]);
            if (isset($stat['whereNull']))    $q->whereNull($stat['whereNull']);
            if (isset($stat['notNull']))      $q->whereNotNull($stat['notNull']);
            if (isset($stat['month'])) {
                $q->whereYear($stat['month'], now()->year)->whereMonth($stat['month'], now()->month);
            }

            $value = isset($stat['distinct'])
                ? $q->distinct()->count($stat['distinct'])
                : $q->count();

            $statCards[] = [
                'icon'  => $stat['icon'],
                'bg'    => 'rgba(59,130,246,0.12)',
                'color' => $stat['color'],
                'value' => number_format((int) $value, 0, ',', '.'),
                'label' => $stat['label'],
            ];
        }

        [$orderCol, $orderDir] = $spec['order'];
        $list = $query->orderBy('t.' . $orderCol, $orderDir)->orderByDesc('t.id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.teknisi.modul', array_merge(
            compact('spec', 'modul', 'list', 'search', 'dari', 'sampai', 'perPage', 'statCards'),
            $this->abilities(),
            ['sources' => TeknisiModuleRegistry::sources()]
        ));
    }

    public function store(Request $request, string $modul)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $spec = TeknisiModuleRegistry::get($modul);
        $payload = $this->payload($request, $spec, $context);

        [$nomorCol, $prefix] = $spec['nomor'];
        $payload[$nomorCol] = $this->generateNomor($spec['table'], $nomorCol, $prefix);
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        DB::table($spec['table'])->insert($payload);

        return $this->respond($request, true, $spec['title'] . ' berhasil disimpan.');
    }

    public function update(Request $request, string $modul, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $spec = TeknisiModuleRegistry::get($modul);
        $row = $this->findRow($spec['table'], $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data milik sendiri.', 403);
        }

        $payload = $this->payload($request, $spec, $context);
        $payload['updated_at'] = now();

        DB::table($spec['table'])->where('id', $id)->update($payload);

        return $this->respond($request, true, $spec['title'] . ' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $modul, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $spec = TeknisiModuleRegistry::get($modul);
        $row = $this->findRow($spec['table'], $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus data milik sendiri.', 403);
        }

        DB::table($spec['table'])->where('id', $id)->delete();

        return $this->respond($request, true, $spec['title'] . ' berhasil dihapus.');
    }

    /** Unduh template Excel sesuai kolom modul. */
    public function template(Request $request, string $modul)
    {
        [$res] = $this->boot($request);
        if ($res) return $res;

        $spec = TeknisiModuleRegistry::get($modul);
        // Kolom identitas petugas tidak ikut di template: diisi otomatis
        // dari akun yang mengunggah.
        $columns = array_diff_key($spec['columns'], array_flip(self::AUTO_OWNER_COLUMNS));
        $headers = array_keys($columns);
        $contoh = [];
        foreach ($columns as $col => $conf) {
            $contoh[] = $this->contohNilai($col, $conf);
        }

        $path = storage_path('app/temp_teknisi_mod_' . $modul . '_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, [
            'title'    => 'Format Import — ' . $spec['title'],
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Nomor Dokumen' => 'Boleh dikosongkan — sistem membuat otomatis.',
                'Tanggal'       => 'Format YYYY-MM-DD.',
                'Petugas'       => 'Tidak perlu diisi — otomatis memakai akun yang mengunggah.',
                'Data per Akun' => 'Baris yang diimpor menjadi milik akun pengunggah.',
            ],
        ], $headers, [$contoh], ['sheetName' => mb_substr($spec['title'], 0, 28)]);

        return response()
            ->download($path, 'Format_' . str_replace('-', '_', $modul) . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /** Impor Excel/CSV ke tabel modul; satu baris = satu catatan milik pengunggah. */
    public function import(Request $request, string $modul)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $request->validate(['file' => 'required|file|max:10240']);

        $spec = TeknisiModuleRegistry::get($modul);
        $file = $request->file('file');

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Format berkas tidak didukung.'], 422);
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage()], 422);
        }

        [$nomorCol, $prefix] = $spec['nomor'];
        $imported = 0;

        foreach ($rows as $raw) {
            $row = $this->normalisasi($raw);
            if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $payload = [];
            foreach ($spec['columns'] as $col => $conf) {
                // Identitas petugas tidak dibaca dari berkas: diisi otomatis
                // dari akun yang mengunggah (lihat ownerColumns()).
                if (in_array($col, self::AUTO_OWNER_COLUMNS, true)) {
                    continue;
                }
                $val = $this->coerce($row[$col] ?? null, $conf['type'] ?? 'string');
                if ($val !== null && $val !== '') {
                    $payload[$col] = $val;
                }
            }
            if (empty($payload)) {
                continue;
            }

            // Nilai dari ownerColumns() harus menang atas isi berkas.
            $payload = array_merge($payload, $spec['fixed'] ?? [], $this->ownerColumns($context));
            $payload[$nomorCol] = $this->generateNomor($spec['table'], $nomorCol, $prefix);
            $payload['created_at'] = now();
            $payload['updated_at'] = now();

            DB::table($spec['table'])->insert($payload);
            $imported++;
        }

        $this->logImport($context, $modul, $spec['title'], $file, $imported);

        $oleh = trim((string) ($context['nama'] ?? '')) ?: 'akun Anda';

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$imported} baris masuk. Diunggah oleh {$oleh}.",
            'count'   => $imported,
        ]);
    }

    /** Cetak laporan: satu catatan per halaman A4 (kontrak view sama dengan laporan TUPOKSI). */
    public function cetak(Request $request, string $modul)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $spec = TeknisiModuleRegistry::get($modul);

        $mode = strtolower((string) $request->query('mode', 'ringkasan'));
        if (!in_array($mode, ['ringkasan', 'lengkap'], true)) {
            $mode = 'ringkasan';
        }
        $orientasi = strtolower((string) $request->query('orientasi', 'portrait'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'portrait';
        }

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

        $query = DB::table($spec['table']);
        $this->applyFixed($query, $spec);
        $this->scopeOwned($query, $context);
        if ($spec['dateCol'] && $dari !== '')   $query->where($spec['dateCol'], '>=', $dari);
        if ($spec['dateCol'] && $sampai !== '') $query->where($spec['dateCol'], '<=', $sampai);

        [$orderCol, $orderDir] = $spec['order'];
        $rows = $query->orderBy($orderCol, $orderDir)->orderByDesc('id')->get();

        [$nomorCol] = $spec['nomor'];

        // fieldMap & types dipakai view ringkasan untuk mengelompokkan kolom.
        $fieldMap = [];
        $types = [];
        foreach ($spec['columns'] as $col => $conf) {
            if (($conf['type'] ?? '') === 'hidden') {
                continue;
            }
            $fieldMap[] = [$conf['label'], $col];
            $types[$col] = $conf['type'] ?? 'string';
        }

        $items = $rows->map(function ($row) use ($fieldMap, $types, $spec, $nomorCol) {
            $fields = [];
            foreach ($fieldMap as [$label, $key]) {
                $conf = $spec['columns'][$key] ?? ['type' => 'string'];
                $fields[] = [
                    'label' => $label,
                    'value' => $this->format($row->{$key} ?? null, $conf),
                ];
            }

            return (object) [
                'nomor'  => $row->{$nomorCol} ?? null,
                'fields' => $fields,
            ];
        });

        return view('dashboard.teknisi.laporan-cetak', [
            'modul'       => $modul,
            'title'       => mb_strtoupper($spec['title']),
            'items'       => $items,
            'rows'        => $rows,
            'types'       => $types,
            'fieldMap'    => $fieldMap,
            'range'       => $range,
            'mode'        => $mode,
            'orientasi'   => $orientasi,
            'cetakUrl'    => fn (array $p = []) => route('dashboard.teknisi.modul.cetak', array_merge(['modul' => $modul], $p)),
            'teknisiTtd'  => $this->teknisiTtd($context),
            'sekolah'     => \App\Models\Sekolah::first(),
            'sekolahMeta' => \App\Models\SekolahMeta::first(),
            'dicetakPada' => now(),
        ]);
    }

    /**
     * Pihak penanda tangan dokumen: teknisi pelaksana, Kepala TAS, Kepala Sekolah.
     */
    private function teknisiTtd(array $context): array
    {
        $dokumen = app(\App\Services\LaboranDokumenService::class);

        $petugas = ['nama' => $context['nama'] ?? '', 'nip' => null];

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

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    private function applyFixed($query, array $spec, ?string $alias = null): void
    {
        $p = $alias ? $alias . '.' : '';
        foreach (($spec['fixed'] ?? []) as $col => $val) {
            $query->where($p . $col, $val);
        }
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, array $spec, array $context): array
    {
        $payload = [];

        foreach ($spec['columns'] as $col => $conf) {
            $type = $conf['type'] ?? 'string';

            if ($type === 'hidden') {
                continue;
            }

            $value = $request->input($col);
            if ($type === 'decimal') {
                $value = $value === null || $value === '' ? null : (float) str_replace(',', '.', (string) $value);
            } elseif ($type === 'int') {
                $value = $value === null || $value === '' ? null : (int) $value;
            } elseif (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            }

            if ($value === null && isset($conf['default'])) {
                $value = $conf['default'] === 'today' ? now()->toDateString() : $conf['default'];
            }

            if ($value === null) {
                continue;
            }

            $payload[$col] = $value;

            // Simpan label sumber terpilih agar rekap & cetak tidak perlu join.
            if (!empty($conf['source'])) {
                $label = $this->labelFor($conf['source'], $value);
                if ($label !== null) {
                    foreach (($spec['nameMap'] ?? []) as $srcCol => $labelCol) {
                        if ($srcCol === $col) {
                            $payload[$labelCol] = $label;
                        }
                    }
                    if ($col === 'aset_id') {
                        $payload['aset_nama'] = $payload['aset_nama'] ?? $label;
                    }
                }
            }
        }

        // Kolom turunan mutasi aset.
        if (isset($payload['aset_id']) && $spec['table'] === 'teknisi_aset_mutasi') {
            $aset = DB::table('sarpras_aset')->where('id', $payload['aset_id'])->first();
            $payload['aset_nama'] = $aset->nama_barang ?? $payload['aset_nama'] ?? null;
            $payload['kode_aset'] = $aset->kode_aset ?? null;
            if (empty($payload['dari_ruang']) && !empty($aset->ruang_id)) {
                $payload['dari_ruang_id'] = $aset->ruang_id;
                $payload['dari_ruang'] = DB::table('sarpras_ruang')->where('id', $aset->ruang_id)->value('nama_ruang');
            }
        }

        // Mutasi aset juga memindahkan lokasi aset.
        if ($spec['table'] === 'teknisi_aset_mutasi' && !empty($payload['ke_ruang_id']) && !empty($payload['aset_id'])) {
            DB::table('sarpras_aset')->where('id', $payload['aset_id'])
                ->update(['ruang_id' => $payload['ke_ruang_id'], 'updated_at' => now()]);
        }

        return $payload + ($spec['fixed'] ?? []) + $this->ownerColumns($context);
    }

    /** @return array<string, mixed> */
    private function ownerColumns(array $context): array
    {
        return [
            'petugas_ptk_id' => $context['ptk_id'] ?? null,
            'petugas_nama'   => $context['nama'] ?? null,
            'user_id'        => $context['user_id'] ?? null,
        ];
    }

    private function labelFor(string $source, mixed $id): ?string
    {
        foreach (TeknisiModuleRegistry::sources()[$source] ?? [] as $opt) {
            if ((string) $opt['id'] === (string) $id) {
                return $opt['label'];
            }
        }

        return null;
    }

    private function format(mixed $value, array $conf): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($conf['type'] ?? 'string') {
            'date'    => Carbon::parse($value)->translatedFormat('d F Y'),
            'decimal' => number_format((float) $value, 0, ',', '.'),
            'select'  => $conf['options'][$value] ?? (string) $value,
            default   => (string) $value,
        };
    }

    private function contohNilai(string $col, array $conf): string
    {
        if (!empty($conf['source'])) {
            return (string) (TeknisiModuleRegistry::sources()[$conf['source']][0]['id'] ?? '');
        }

        return match ($conf['type'] ?? 'string') {
            'date'    => now()->toDateString(),
            'decimal' => '0',
            'int'     => '1',
            'select'  => (string) (array_key_first($conf['options'] ?? []) ?? ''),
            default   => $conf['label'],
        };
    }

    /** @return array<string, mixed> */
    private function normalisasi(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
            $out[$key] = is_string($v) ? trim($v) : $v;
        }

        return $out;
    }

    private function coerce(mixed $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }
        $raw = is_string($raw) ? trim($raw) : $raw;
        if ($raw === '' || $raw === '-') {
            return null;
        }

        return match ($type) {
            'date'    => $this->parseTanggal($raw),
            'int'     => is_numeric($raw) ? (int) $raw : null,
            'decimal' => is_numeric($raw) ? (float) $raw : null,
            default   => (string) $raw,
        };
    }

    private function parseTanggal(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            return Carbon::createFromDate(1899, 12, 30)->addDays((int) $raw)->toDateString();
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
