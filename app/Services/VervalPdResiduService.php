<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use App\Models\VervalPdResidu;
use App\Support\Table;
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

    /**
     * Import berkas Residu Excel/CSV.
     *
     * Data lama TIDAK dihapus: baris dicocokkan per NISN lalu diperbarui (upsert),
     * dan setiap perubahan dicatat ke tabel riwayat per siswa sehingga jejak
     * (import + perubahan status/catatan) tetap tersimpan walau diimpor ulang.
     */
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

        // Siapkan data per baris dari berkas (key = NISN, fallback nama).
        $payload = [];
        foreach ($rows as $row) {
            $get = fn(int $i) => trim((string) ($row['__col_' . $i] ?? ''));

            $nisn = $get(0);
            $nama = $get(2);
            if ($nisn === '' && $nama === '') {
                continue;
            }

            $data = ['import_batch' => $batch, 'imported_by' => $userName, 'updated_at' => $now];
            foreach (self::COLUMNS as $i => $col) {
                $data[$col] = $get($i) ?: null;
            }
            $key = $nisn !== '' ? $nisn : 'N:' . $nama;
            $payload[$key] = $data;
        }

        if (empty($payload)) {
            return ['status' => 'error', 'message' => 'Tidak ada baris valid (kolom NISN/NAMA kosong).', 'status_code' => 422];
        }

        // Baris lama dipetakan per NISN agar tidak terhapus — hanya diperbarui.
        $existing = VervalPdResidu::query()
            ->whereNotNull('nisn')->where('nisn', '<>', '')
            ->get()->keyBy('nisn');

        $baru = 0;
        $diperbarui = 0;

        DB::transaction(function () use ($payload, $existing, $now, $userName, &$baru, &$diperbarui) {
            foreach ($payload as $key => $data) {
                $nisn = trim((string) ($data['nisn'] ?? ''));
                $old = ($nisn !== '' && $existing->has($nisn)) ? $existing->get($nisn) : null;

                if ($old) {
                    $changes = $this->ringkasPerubahan($old, $data);
                    $old->fill($data);
                    $old->save();
                    $diperbarui++;
                    $this->catatRiwayat(
                        $old,
                        'import',
                        null,
                        null,
                        $data['rombel_sekolah'] ?? null,
                        $changes === [] ? 'Import ulang: tidak ada perubahan nilai.' : 'Import ulang — ' . implode('; ', $changes),
                        $userName
                    );
                } else {
                    $row = VervalPdResidu::create($data + ['created_at' => $now]);
                    $baru++;
                    $this->catatRiwayat(
                        $row,
                        'import',
                        null,
                        null,
                        $data['rombel_sekolah'] ?? null,
                        'Data pertama masuk dari import Residu Dukcapil.',
                        $userName
                    );
                }
            }
        });

        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::recordCrud(
                'CREATE',
                'Import Residu Verval PD (' . count($payload) . ' baris)',
                'Verval PD Residu',
                "Berkas Residu diimpor oleh {$userName}: {$baru} data baru, {$diperbarui} diperbarui (data lama tetap tersimpan di riwayat).",
                'success'
            );
        }

        return [
            'status' => 'success',
            'message' => 'Import Residu selesai: ' . $baru . ' data baru, ' . $diperbarui . ' data diperbarui. Riwayat lama tetap tersimpan.',
            'imported_count' => count($payload),
            'created_count' => $baru,
            'updated_count' => $diperbarui,
        ];
    }

    /** Bandingkan baris lama vs data import, kembalikan daftar kolom yang berubah (label: lama → baru). */
    protected function ringkasPerubahan(VervalPdResidu $old, array $data): array
    {
        $changes = [];
        foreach (self::EDITABLE_COLUMNS as $col) {
            $lama = $this->displayValue($old->{$col});
            $baru = $this->displayValue($data[$col] ?? null);
            if ($lama !== $baru) {
                $changes[] = $this->columnLabel($col) . ': ' . $lama . ' → ' . $baru;
            }
        }

        return $changes;
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

        $userName = is_array($user) ? ($user['nama'] ?? 'Administrator') : ($user->nama ?? 'Administrator');
        $this->catatRiwayat(
            $item,
            'sel',
            $column,
            $this->displayValue($old),
            $this->displayValue($value),
            'Ubah ' . $this->columnLabel($column) . ': ' . $this->displayValue($old) . ' → ' . $this->displayValue($value) . '.',
            $userName
        );

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

        $userName = is_array($user) ? ($user['nama'] ?? 'Administrator') : ($user->nama ?? 'Administrator');
        $this->catatRiwayat(
            $item,
            'status',
            'status_proses',
            $this->displayValue($old),
            $this->displayValue($status),
            'Status proses: ' . $this->displayValue($old) . ' → ' . $this->displayValue($status) . '.',
            $userName
        );

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

        $userName = is_array($user) ? ($user['nama'] ?? 'Administrator') : ($user->nama ?? 'Administrator');
        $this->catatRiwayat(
            $item,
            'catatan',
            'catatan',
            $this->displayValue($old),
            $this->displayValue($new),
            'Catatan masalah: ' . $this->displayValue($old) . ' → ' . $this->displayValue($new) . '.',
            $userName
        );

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
            'nisn_banding' => 'NISN',
            'nik_dukcapil' => 'NIK Dukcapil',
            'nik_ganda' => 'NIK Ganda',
            'nama_banding' => 'Nama',
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

    /** Kunci identitas baris untuk pengelompokan riwayat per siswa (NISN, fallback nama). */
    protected function kunciSiswa(VervalPdResidu $item): string
    {
        $nisn = trim((string) $item->nisn);

        return $nisn !== '' ? $nisn : 'N:' . trim((string) $item->nama);
    }

    /**
     * Catat satu peristiwa (import / ubah sel / ubah status / catatan) ke riwayat per siswa.
     * Riwayat bersifat append-only: tidak pernah dihapus saat import berikutnya.
     */
    protected function catatRiwayat(
        VervalPdResidu $item,
        string $tipe,
        ?string $kolom,
        ?string $nilaiLama,
        ?string $nilaiBaru,
        ?string $keterangan,
        ?string $adminName
    ): void {
        if (!Table::exists('verval_pd_residu_riwayat')) {
            return;
        }

        DB::table('verval_pd_residu_riwayat')->insert([
            'kunci' => $this->kunciSiswa($item),
            'nisn' => $item->nisn ?: null,
            'nama' => $item->nama ?: null,
            'tipe' => $tipe,
            'kolom' => $kolom,
            'nilai_lama' => $nilaiLama,
            'nilai_baru' => $nilaiBaru,
            'keterangan' => $keterangan,
            'admin_name' => $adminName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Query laporan Residu: filter status proses + pencarian, urut Belum → Proses →
     * Selesai lalu nama. Dipakai halaman cetak laporan.
     */
    public function reportItems(Request $request): \Illuminate\Support\Collection
    {
        $status = trim((string) $request->get('status', ''));
        $q = trim((string) $request->get('q', ''));
        $ids = collect($request->get('ids', []))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values();

        return VervalPdResidu::query()
            ->when($ids->isNotEmpty(), fn($w) => $w->whereIn('id', $ids))
            ->when($ids->isEmpty() && $status === self::STATUS_BELUM, fn($w) => $w->where(fn($x) => $x->where('status_proses', self::STATUS_BELUM)->orWhereNull('status_proses')))
            ->when($ids->isEmpty() && in_array($status, [self::STATUS_PROSES, self::STATUS_SELESAI], true), fn($w) => $w->where('status_proses', $status))
            ->when($ids->isEmpty() && $q !== '', fn($w) => $w->where(fn($x) => $x->where('nama', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
                ->orWhere('nik', 'like', "%{$q}%")
                ->orWhere('rombel_sekolah', 'like', "%{$q}%")))
            ->orderByRaw("CASE status_proses
                WHEN '" . self::STATUS_BELUM . "' THEN 0
                WHEN '" . self::STATUS_PROSES . "' THEN 1
                WHEN '" . self::STATUS_SELESAI . "' THEN 2
                ELSE 3 END ASC")
            ->orderByRaw('LOWER(TRIM(nama)) ASC')
            ->get();
    }

    /** Label "Sesuai/Tidak Sesuai" untuk sel centang (dipakai halaman cetak). */
    public function tampilNilai(?string $value): string
    {
        return $this->displayValue($value);
    }

    /**
     * Riwayat Residu dikelompokkan PER SISWA (satu NISN = satu kartu riwayat),
     * lengkap dengan seluruh peristiwa (import pertama sampai update terakhir).
     * Mendukung pencarian (rq), filter jenis peristiwa (rtipe), dan paginasi.
     */
    public function riwayatPerSiswa(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        if (!Table::exists('verval_pd_residu_riwayat')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        $q = trim((string) $request->get('rq', ''));
        $tipe = trim((string) $request->get('rtipe', ''));
        $perPage = (int) $request->get('rperPage', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $base = DB::table('verval_pd_residu_riwayat')
            ->when($q !== '', fn($w) => $w->where(fn($x) => $x->where('nama', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
                ->orWhere('kunci', 'like', "%{$q}%")))
            ->when($tipe !== '', fn($w) => $w->where('tipe', $tipe));

        $paginator = $base->clone()
            ->select('kunci', DB::raw('MAX(created_at) as terakhir'))
            ->groupBy('kunci')
            ->orderByDesc('terakhir')
            ->paginate($perPage, ['kunci', 'terakhir'], 'riwayat_page')
            ->withQueryString();

        $keys = collect($paginator->items())->pluck('kunci')->all();

        if (empty($keys)) {
            return $paginator;
        }

        $events = DB::table('verval_pd_residu_riwayat')
            ->whereIn('kunci', $keys)
            ->orderBy('created_at')->orderBy('id')
            ->get()->groupBy('kunci');

        // Kondisi terkini dari tabel Residu (status proses + catatan saat ini).
        $current = VervalPdResidu::query()->whereIn('nisn', $keys)->get()->keyBy('nisn');

        foreach ($paginator->items() as $row) {
            $list = $events->get($row->kunci, collect());
            $first = $list->first();
            $last = $list->last();
            $row->nama = $last->nama ?? ($first->nama ?? '-');
            $row->nisn = $last->nisn ?? ($first->nisn ?? null);
            $row->nama_awal = $first->nama ?? null;
            $row->total = $list->count();
            $row->import_pertama = $first->created_at ?? null;
            $row->update_terakhir = $last->created_at ?? null;
            $row->events = $list;
            $cur = $row->nisn ? ($current->get($row->nisn) ?? null) : null;
            $row->status_proses = $cur->status_proses ?? null;
            $row->catatan = $cur->catatan ?? null;
        }

        return $paginator;
    }

    /** Label manusiawi tipe peristiwa riwayat Residu. */
    public function tipeLabel(string $tipe): string
    {
        return [
            'import' => 'Import',
            'sel' => 'Ubah Sel',
            'status' => 'Ubah Status',
            'catatan' => 'Catatan',
        ][$tipe] ?? $tipe;
    }

    /** Pasangan ikon + warna untuk tipe peristiwa riwayat. */
    public function tipeMeta(string $tipe): array
    {
        return match ($tipe) {
            'import' => ['fa-file-import', 'var(--accent, #0284c7)'],
            'sel' => ['fa-check-double', 'var(--primary, #3b82f6)'],
            'status' => ['fa-arrows-rotate', 'var(--warning, #f59e0b)'],
            'catatan' => ['fa-note-sticky', 'var(--purple, #8b5cf6)'],
            default => ['fa-circle-info', 'var(--text-muted)'],
        };
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
