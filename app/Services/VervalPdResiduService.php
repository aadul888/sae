<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use App\Models\VervalPdResidu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import & pengelolaan berkas Residu Verval PD.
 *
 * Berkas Residu berasal dari Dukcapil dan sudah memuat hasil perbandingan
 * (centang hijau/merah) apa adanya. Sistem TIDAK membandingkan; hanya
 * menyimpan dan menampilkan isi sel.
 */
class VervalPdResiduService
{
    /** Header template, berurutan sesuai kolom posisi (0-12). */
    public const HEADERS = [
        'NISN',
        'NIK',
        'NAMA',
        'ROMBEL/SEKOLAH',
        'NISN (Dukcapil)',
        'NIK DUKCAPIL',
        'NIK GANDA',
        'NAMA (Dukcapil)',
        'TEMPAT LAHIR',
        'TANGGAL LAHIR',
        'IBU KANDUNG',
        'JENIS KELAMIN',
        'DESA/KELURAHAN',
    ];

    /** Urutan kolom DB yang sejajar dengan HEADERS (kolom 0-3 identitas, 4-12 perbandingan). */
    public const COLUMNS = [
        'nisn',
        'nik',
        'nama',
        'rombel_sekolah',
        'nisn_banding',
        'nik_dukcapil',
        'nik_ganda',
        'nama_banding',
        'tempat_lahir',
        'tanggal_lahir',
        'ibu_kandung',
        'jenis_kelamin',
        'desa_kelurahan',
    ];

    /** Indeks kolom yang berisi centang hijau/merah (hasil Dukcapil). */
    public const CENTANG_COLUMNS = [
        'nisn_banding',
        'nik_dukcapil',
        'nik_ganda',
        'nama_banding',
        'tempat_lahir',
        'tanggal_lahir',
        'ibu_kandung',
        'jenis_kelamin',
        'desa_kelurahan',
    ];

    /** Kolom yang boleh diubah manual (centang) + ROMBEL/SEKOLAH. */
    public const EDITABLE_COLUMNS = [
        'rombel_sekolah',
        'nisn_banding',
        'nik_dukcapil',
        'nik_ganda',
        'nama_banding',
        'tempat_lahir',
        'tanggal_lahir',
        'ibu_kandung',
        'jenis_kelamin',
        'desa_kelurahan',
    ];

    /** Lambang centang yang dipakai berkas Dukcapil. */
    public const MARK_YES = '✓';
    public const MARK_NO = '✗';

    /** Status proses manual per baris Residu. */
    public const STATUS_BELUM = 'belum';
    public const STATUS_PROSES = 'proses';
    public const STATUS_SELESAI = 'selesai';

    /** Label manusiawi status proses. */
    public const STATUS_LABELS = [
        self::STATUS_BELUM => 'Belum Diproses',
        self::STATUS_PROSES => 'Sedang Proses',
        self::STATUS_SELESAI => 'Selesai',
    ];

    /** Unduh template Excel (.xlsx) untuk import berkas Residu. */
    public function downloadTemplate(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_residu_') . '.xlsx';

        $sampleRows = [
            ['0081234561', '3203010101080001', 'Ahmad Rizki Pratama', '✓', '✓', '✓', '✗', '✓', '✓', '✓', '✓', '✓', '✓'],
            ['0081234562', '3203014101080002', 'Siti Nur Aisyah', '✗', '✓', '✗', '✓', '✓', '✓', '✗', '✓', '✓', '✗'],
        ];

        SimpleXlsxExporter::export($tempPath, [], self::HEADERS, $sampleRows, [
            'sheetName' => 'Residu Verval PD',
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, 'Template_Import_Residu_Verval_PD.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Import berkas Residu Excel/CSV. Data lama diganti (satu berkas = kondisi terkini). */
    public function importExcel(Request $request, mixed $user): array
    {
        $file = $request->file('file_residu');
        if (!$file) {
            return ['status' => 'error', 'message' => 'Pilih berkas Residu yang ingin diimpor.', 'status_code' => 422];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return ['status' => 'error', 'message' => 'Format berkas tidak didukung. Gunakan Excel (.xlsx/.xls) atau CSV.', 'status_code' => 422];
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Gagal membaca berkas: ' . $e->getMessage(), 'status_code' => 422];
        }

        if (empty($rows)) {
            return ['status' => 'error', 'message' => 'Berkas kosong atau baris data tidak terdeteksi.', 'status_code' => 422];
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Administrator') : ($user->nama ?? 'Administrator');
        $batch = (string) Str::uuid();
        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $get = fn(int $i) => trim((string) ($row['__col_' . $i] ?? ''));

            $nisn = $get(0);
            $nama = $get(2);
            if ($nisn === '' && $nama === '') {
                continue;
            }

            $data = ['import_batch' => $batch, 'imported_by' => $userName, 'created_at' => $now, 'updated_at' => $now];
            foreach (self::COLUMNS as $i => $col) {
                $data[$col] = $get($i) ?: null;
            }
            $payload[] = $data;
        }

        if (empty($payload)) {
            return ['status' => 'error', 'message' => 'Tidak ada baris valid (kolom NISN/NAMA kosong).', 'status_code' => 422];
        }

        DB::transaction(function () use ($payload) {
            VervalPdResidu::query()->delete();
            foreach (array_chunk($payload, 200) as $chunk) {
                VervalPdResidu::insert($chunk);
            }
        });

        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::recordCrud(
                'CREATE',
                'Import Residu Verval PD (' . count($payload) . ' baris)',
                'Verval PD Residu',
                "Berkas Residu diimpor oleh {$userName}.",
                'success'
            );
        }

        return [
            'status' => 'success',
            'message' => 'Import Residu berhasil: ' . count($payload) . ' baris tersimpan.',
            'imported_count' => count($payload),
        ];
    }

    /**
     * Ubah satu sel centang (✓/✗) atau Rombel/Sekolah pada satu baris Residu.
     *
     * Setiap perubahan dicatat ke admin_aktivitas (modul "Verval PD Residu")
     * sebagai riwayat audit: nilai lama -> nilai baru, sehingga perubahan
     * berkala (bulanan/semester) tetap terekam walau data berbalik arah.
     */
    public function updateCell(int $id, string $column, ?string $value, mixed $user): array
    {
        $item = VervalPdResidu::find($id);
        if (!$item) {
            return ['status' => 'error', 'message' => 'Baris Residu tidak ditemukan.', 'status_code' => 404];
        }

        if (!in_array($column, self::EDITABLE_COLUMNS, true)) {
            return ['status' => 'error', 'message' => 'Kolom tidak dapat diubah.', 'status_code' => 422];
        }

        // Normalisasi: kosong/tanda strip → null, sisanya simpan apa adanya.
        $value = $value === null ? null : trim($value);
        if ($value === '' || $value === '-') {
            $value = null;
        }

        $old = $item->{$column};
        if ($old === $value) {
            return ['status' => 'success', 'message' => 'Tidak ada perubahan.', 'value' => $value];
        }

        $item->{$column} = $value;
        $item->save();

        $this->logPerubahan($item, $column, $old, $value, $user);

        return [
            'status' => 'success',
            'message' => 'Perubahan tersimpan.',
            'value' => $value,
        ];
    }

    /**
     * Ubah status proses baris Residu: belum | proses | selesai.
     * Tercatat di riwayat (admin_aktivitas) sebagai perubahan status.
     */
    public function updateStatus(int $id, string $status, mixed $user): array
    {
        $item = VervalPdResidu::find($id);
        if (!$item) {
            return ['status' => 'error', 'message' => 'Baris Residu tidak ditemukan.', 'status_code' => 404];
        }

        if (!array_key_exists($status, self::STATUS_LABELS)) {
            return ['status' => 'error', 'message' => 'Status proses tidak valid.', 'status_code' => 422];
        }

        $old = $item->status_proses ?: self::STATUS_BELUM;
        if ($old === $status) {
            return ['status' => 'success', 'message' => 'Tidak ada perubahan.', 'status' => $status];
        }

        $item->status_proses = $status;
        $item->save();

        $this->logPerubahan($item, 'status_proses', $old, $status, $user);

        return [
            'status' => 'success',
            'message' => 'Status proses diperbarui.',
            'status_proses' => $status,
        ];
    }

    /**
     * Simpan catatan masalah baris Residu (bebas teks, boleh dikosongkan).
     * Tercatat di riwayat (admin_aktivitas).
     */
    public function updateCatatan(int $id, ?string $catatan, mixed $user): array
    {
        $item = VervalPdResidu::find($id);
        if (!$item) {
            return ['status' => 'error', 'message' => 'Baris Residu tidak ditemukan.', 'status_code' => 404];
        }

        $new = trim((string) $catatan);
        if ($new === '') {
            $new = null;
        }

        $old = $item->catatan;
        if ($old === $new) {
            return ['status' => 'success', 'message' => 'Tidak ada perubahan.', 'catatan' => $new];
        }

        $item->catatan = $new;
        $item->save();

        $this->logPerubahan($item, 'catatan', $old, $new, $user);

        return [
            'status' => 'success',
            'message' => 'Catatan tersimpan.',
            'catatan' => $new,
        ];
    }

    /** Label manusiawi untuk tiap kolom Residu yang dapat diubah. */
    public function columnLabel(string $column): string
    {
        return [
            'rombel_sekolah' => 'Rombel/Sekolah',
            'nisn_banding' => 'NISN (Dukcapil)',
            'nik_dukcapil' => 'NIK Dukcapil',
            'nik_ganda' => 'NIK Ganda',
            'nama_banding' => 'Nama (Dukcapil)',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'ibu_kandung' => 'Ibu Kandung',
            'jenis_kelamin' => 'Jenis Kelamin',
            'desa_kelurahan' => 'Desa/Kelurahan',
            'status_proses' => 'Status Proses',
            'catatan' => 'Catatan Masalah',
        ][$column] ?? $column;
    }

    /** Tampilkan nilai sel agar terbaca di log (centang → Sesuai/Tidak Sesuai). */
    protected function displayValue(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '(kosong)';
        }
        if (array_key_exists($value, self::STATUS_LABELS)) {
            return self::STATUS_LABELS[$value];
        }
        $yes = preg_match('/[✓✔√]/u', $value) === 1;
        $no = preg_match('/[✗✘×]/u', $value) === 1;
        if ($yes && !$no) {
            return 'Sesuai (✓)';
        }
        if ($no && !$yes) {
            return 'Tidak Sesuai (✗)';
        }

        return $value;
    }

    /** Catat satu perubahan sel ke riwayat aktivitas. */
    protected function logPerubahan(VervalPdResidu $item, string $column, ?string $old, ?string $new, mixed $user): void
    {
        if (!class_exists(AdminAktivitas::class)) {
            return;
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Administrator') : ($user->nama ?? 'Administrator');
        $label = $this->columnLabel($column);
        $identitas = trim(($item->nama ?: '-') . ' (NISN: ' . ($item->nisn ?: '-') . ')');

        AdminAktivitas::recordCrud(
            'UPDATE',
            "Ubah {$label}: {$identitas}",
            'Verval PD Residu',
            "{$label} {$identitas}: " . $this->displayValue($old) . ' → ' . $this->displayValue($new) . " oleh {$userName}.",
            'info'
        );
    }

    /**
     * Ekspor laporan Residu ke Excel (.xlsx): seluruh kolom + status proses +
     * status konfirmasi/berkas peserta didik. Bisa difilter status proses & pencarian.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $status = trim((string) $request->get('status', ''));
        $q = trim((string) $request->get('q', ''));

        $query = VervalPdResidu::query();
        if ($status === self::STATUS_BELUM) {
            $query->where(fn($w) => $w->where('status_proses', self::STATUS_BELUM)->orWhereNull('status_proses'));
        } elseif (in_array($status, [self::STATUS_PROSES, self::STATUS_SELESAI], true)) {
            $query->where('status_proses', $status);
        }
        if ($q !== '') {
            $query->where(fn($w) => $w->where('nama', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
                ->orWhere('nik', 'like', "%{$q}%")
                ->orWhere('rombel_sekolah', 'like', "%{$q}%"));
        }

        $query->orderByRaw("CASE status_proses
            WHEN '" . self::STATUS_BELUM . "' THEN 0
            WHEN '" . self::STATUS_PROSES . "' THEN 1
            WHEN '" . self::STATUS_SELESAI . "' THEN 2
            ELSE 3 END ASC")->orderByRaw('LOWER(TRIM(nama)) ASC');

        $rows = [];
        foreach ($query->get() as $i => $r) {
            $rows[] = [
                $i + 1,
                $r->nisn ?: '',
                $r->nik ?: '',
                $r->nama ?: '',
                $r->rombel_sekolah ?: '',
                self::STATUS_LABELS[$r->status_proses ?: self::STATUS_BELUM] ?? 'Belum Diproses',
                $r->nisn_banding ?: '',
                $r->nik_dukcapil ?: '',
                $r->nik_ganda ?: '',
                $r->nama_banding ?: '',
                $r->tempat_lahir ?: '',
                $r->tanggal_lahir ?: '',
                $r->ibu_kandung ?: '',
                $r->jenis_kelamin ?: '',
                $r->desa_kelurahan ?: '',
                $r->catatan ?: '',
            ];
        }

        $headers = [
            'NO', 'NISN', 'NIK', 'NAMA', 'ROMBEL/SEKOLAH', 'STATUS PROSES',
            'NISN (Dukcapil)', 'NIK DUKCAPIL', 'NIK GANDA', 'NAMA (Dukcapil)',
            'TEMPAT LAHIR', 'TANGGAL LAHIR', 'IBU KANDUNG', 'JENIS KELAMIN', 'DESA/KELURAHAN',
            'CATATAN MASALAH',
        ];

        // Ganti lambang centang agar terbaca di Excel (kecuali kolom terakhir: Catatan).
        $lastCol = count($headers) - 1;
        foreach ($rows as &$row) {
            foreach ($row as $ci => &$cell) {
                if ($ci === $lastCol) {
                    continue;
                }
                if (is_string($cell) && $cell !== '') {
                    $cell = $this->displayValue($cell);
                }
            }
        }
        unset($row, $cell);

        $tempPath = tempnam(sys_get_temp_dir(), 'lpr_residu_') . '.xlsx';

        SimpleXlsxExporter::export($tempPath, [
            'title' => 'LAPORAN RESIDU VERVAL PD',
            'subtitle' => 'Hasil penyandingan Dukcapil — Sistem Administrasi Elektronik (SAE)',
            'fields' => [
                'Total Baris' => count($rows),
                'Filter Status' => $status !== '' ? (self::STATUS_LABELS[$status] ?? $status) : 'Semua',
                'Dicetak' => now()->translatedFormat('d M Y, H:i'),
            ],
        ], $headers, $rows, [
            'sheetName' => 'Residu Verval PD',
            'headerBg' => '#1E3A8A',
            'centerCols' => [0, 4, 5, 6, 7, 8, 12, 13],
        ]);

        return response()->download($tempPath, 'Laporan_Residu_Verval_PD_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Hapus seluruh data Residu. */
    public function clear(mixed $user): array
    {
        $count = VervalPdResidu::query()->count();

        DB::transaction(function () {
            VervalPdResidu::query()->delete();
        });

        $userName = is_array($user) ? ($user['nama'] ?? 'Administrator') : ($user->nama ?? 'Administrator');
        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::recordCrud('DELETE', 'Hapus Data Residu Verval PD', 'Verval PD Residu', "Semua baris Residu dihapus oleh {$userName}.", 'warning');
        }

        return ['status' => 'success', 'message' => "Seluruh data Residu ({$count} baris) berhasil dihapus."];
    }
}
