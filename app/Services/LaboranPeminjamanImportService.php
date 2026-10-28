<?php

namespace App\Services;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import & template Excel untuk modul Peminjaman Alat Laboratorium/Jurusan.
 *
 * Mengadopsi arsitektur yang sama dengan LaboranInventarisImportService:
 *  - Controller tetap fokus pada HTTP (routing & response).
 *  - Logika parsing/penyimpanan diuji terpisah di sini.
 *
 * Format berkas: SATU BARIS = SATU ALAT yang dipinjam. Baris dengan
 * `nomor_pinjam` yang sama digabung menjadi satu dokumen peminjaman
 * (multi-item), sehingga surat/kartu peminjaman tetap utuh.
 */
class LaboranPeminjamanImportService
{
    public const STATUS = ['diajukan', 'disetujui', 'dipinjam', 'dikembalikan', 'terlambat', 'ditolak'];

    public const TIPE = ['siswa', 'guru', 'tendik', 'umum'];

    public const KONDISI = ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'];

    /** Kolom header template, berurutan. */
    public const HEADERS = [
        'nomor_pinjam',
        'peminjam_tipe',
        'peminjam_nama',
        'peminjam_kelas',
        'keperluan',
        'ruang_lab_nama',
        'tanggal_pinjam',
        'tanggal_kembali_rencana',
        'tanggal_kembali_aktual',
        'status',
        'kondisi_kembali',
        'catatan',
        'kode_item',
        'nama_item',
        'jumlah',
        'satuan',
    ];

    /**
     * Baris contoh pada template (memudahkan pengguna memahami format).
     */
    public function contohBaris(): array
    {
        return [
            [
                'PJM-2026-0001', 'siswa', 'Budi Santoso', 'XI TKJ 1',
                'Praktik konfigurasi jaringan', 'Lab Komputer 1',
                '2026-10-01', '2026-10-04', '', 'dipinjam', '',
                'Dikembalikan tepat waktu',
                'ALT-202610-0001', 'Switch Cisco Catalyst 2960', '2', 'unit',
            ],
            [
                'PJM-2026-0001', 'siswa', 'Budi Santoso', 'XI TKJ 1',
                'Praktik konfigurasi jaringan', 'Lab Komputer 1',
                '2026-10-01', '2026-10-04', '', 'dipinjam', '',
                '',
                '', 'Kabel UTP Patch 2 Meter', '4', 'buah',
            ],
            [
                '', 'guru', 'Siti Aminah, S.Pd.', 'NIP. 19850101', 
                'Persiapan praktik biologi', 'Lab Biologi',
                '2026-10-02', '2026-10-09', '', 'diajukan', '',
                '',
                'ALT-202610-0002', 'Mikroskop Binokuler', '1', 'unit',
            ],
        ];
    }

    public function metaTemplate(): array
    {
        return [
            'title'    => 'Format Import Peminjaman Alat Laboratorium & Jurusan',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Nomor Pinjam'   => 'Boleh dikosongkan — sistem membuat otomatis (PJM/YYYYMM/NNNN)',
                'Multi Item'     => 'Baris dengan nomor_pinjam sama digabung menjadi satu dokumen peminjaman',
                'Status'         => implode(', ', self::STATUS),
                'Stok Otomatis'  => 'Status dipinjam/terlambat pada data BARU otomatis mengurangi stok tersedia',
                'Tanggal'        => 'Format YYYY-MM-DD (tanggal_pinjam & tanggal_kembali_rencana wajib)',
            ],
        ];
    }

    /**
     * Hasilkan berkas template .xlsx dan kembalikan path sementaranya.
     */
    public function generateTemplateFile(): string
    {
        $path = storage_path('app/temp_template_peminjaman_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, $this->metaTemplate(), self::HEADERS, $this->contohBaris(), [
            'sheetName' => 'Peminjaman Alat',
        ]);

        return $path;
    }

    /**
     * Proses berkas unggahan menjadi dokumen peminjaman beserta itemnya.
     *
     * @return array{imported:int, updated:int, skipped:int}
     *
     * @throws \RuntimeException bila berkas tidak valid / kosong
     */
    public function import(UploadedFile $file, array $context, ?string $inputJurusanId = null): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            throw new \RuntimeException('Format berkas tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.');
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal membaca berkas: ' . $e->getMessage());
        }

        if (empty($rows)) {
            throw new \RuntimeException('Berkas Excel kosong atau tidak memiliki data baris.');
        }

        $jurusanId = LaboranScope::jurusanForWrite($inputJurusanId, $context);

        $groups = $this->kelompokkanBaris($rows);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($groups as $nomor => $baris) {
            $hasil = $this->simpanKelompok(
                is_string($nomor) && !str_starts_with($nomor, '__ROW_') ? $nomor : null,
                $baris,
                $jurusanId,
                $context
            );

            if ($hasil === 'imported') {
                $imported++;
            } elseif ($hasil === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Kelompokkan baris mentah berdasarkan nomor pinjam.
     * Baris tanpa nomor pinjam berdiri sendiri sebagai satu dokumen.
     *
     * @return array<string, array<int, array>>
     */
    protected function kelompokkanBaris(array $rows): array
    {
        $groups = [];
        $idx = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);

            $namaPeminjam = trim((string) ($n['peminjam_nama'] ?? ($n['nama_peminjam'] ?? ($n['nama'] ?? ''))));
            $namaItem     = trim((string) ($n['nama_item'] ?? ($n['nama_alat'] ?? '')));

            // Lewati baris kosong / baris keterangan yang tidak memuat data.
            if ($namaPeminjam === '' && $namaItem === '') {
                continue;
            }

            $nomor = strtoupper(trim((string) ($n['nomor_pinjam'] ?? ($n['nomor'] ?? ''))));
            $key = $nomor !== '' ? $nomor : ('__ROW_' . $idx);

            $groups[$key][] = $n;
            $idx++;
        }

        return $groups;
    }

    /**
     * Simpan satu dokumen peminjaman (header + item).
     *
     * @param  array<int, array>  $baris
     * @return string imported|updated|skipped
     */
    protected function simpanKelompok(?string $nomor, array $baris, ?string $jurusanId, array $context): string
    {
        $header = $this->normalisasiHeader($baris[0], $context);
        if ($header === null) {
            return 'skipped';
        }

        $items = [];
        foreach ($baris as $n) {
            $item = $this->normalisasiItem($n, $jurusanId, $context);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        if (empty($items)) {
            return 'skipped';
        }

        $existing = null;
        if ($nomor !== null) {
            $existing = DB::table('laboran_peminjaman')->where('nomor_pinjam', $nomor)->first();
            if ($existing && !LaboranScope::allows($existing->jurusan_id ?? null, $context)) {
                return 'skipped';
            }
        }

        if ($existing) {
            return $this->perbaruiDokumen($existing, $header, $items);
        }

        $this->buatDokumen($nomor, $header, $items, $jurusanId);

        return 'imported';
    }

    /**
     * Ubah satu baris mentah menjadi payload header peminjaman.
     * Kembalikan null bila nama peminjam tidak ada.
     */
    protected function normalisasiHeader(array $n, array $context): ?array
    {
        $nama = trim((string) ($n['peminjam_nama'] ?? ($n['nama_peminjam'] ?? ($n['nama'] ?? ''))));
        if ($nama === '') {
            return null;
        }

        $tipe = strtolower(trim((string) ($n['peminjam_tipe'] ?? ($n['tipe'] ?? 'siswa'))));
        if (!in_array($tipe, self::TIPE, true)) {
            $tipe = 'siswa';
        }

        $keperluan = trim((string) ($n['keperluan'] ?? ($n['keterangan'] ?? '')));
        if ($keperluan === '') {
            $keperluan = 'Peminjaman alat praktik (import)';
        }

        $tglPinjam = $this->parseTanggal($n['tanggal_pinjam'] ?? ($n['tgl_pinjam'] ?? ''));
        if ($tglPinjam === null) {
            $tglPinjam = now()->toDateString();
        }

        $tglRencana = $this->parseTanggal($n['tanggal_kembali_rencana'] ?? ($n['tgl_kembali_rencana'] ?? ($n['tanggal_kembali'] ?? '')));
        if ($tglRencana === null || $tglRencana < $tglPinjam) {
            $tglRencana = Carbon::parse($tglPinjam)->addDays(3)->toDateString();
        }

        $status = strtolower(trim((string) ($n['status'] ?? 'diajukan')));
        if (!in_array($status, self::STATUS, true)) {
            $status = 'diajukan';
        }

        $kondisiKembali = strtolower(trim((string) ($n['kondisi_kembali'] ?? '')));
        if (!in_array($kondisiKembali, self::KONDISI, true)) {
            $kondisiKembali = '';
        }

        return [
            'peminjam_tipe'           => $tipe,
            'peminjam_nama'           => $nama,
            'peminjam_kelas'          => trim((string) ($n['peminjam_kelas'] ?? ($n['kelas'] ?? ''))),
            'keperluan'               => $keperluan,
            'ruang_lab_nama'          => trim((string) ($n['ruang_lab_nama'] ?? ($n['lokasi'] ?? ($n['ruang'] ?? '')))),
            'tanggal_pinjam'          => $tglPinjam,
            'tanggal_kembali_rencana' => $tglRencana,
            'tanggal_kembali_aktual'  => $this->parseTanggal($n['tanggal_kembali_aktual'] ?? ($n['tgl_kembali_aktual'] ?? '')),
            'status'                  => $status,
            'kondisi_kembali'         => $kondisiKembali !== '' ? $kondisiKembali : null,
            'catatan'                 => trim((string) ($n['catatan'] ?? ($n['keterangan'] ?? ''))),
            'petugas_ptk_id'          => $context['ptk_id'] ?? null,
        ];
    }

    /**
     * Ubah satu baris mentah menjadi payload item peminjaman.
     */
    protected function normalisasiItem(array $n, ?string $jurusanId, array $context): ?array
    {
        $namaItem = trim((string) ($n['nama_item'] ?? ($n['nama_alat'] ?? '')));
        if ($namaItem === '') {
            return null;
        }

        $kode = strtoupper(trim((string) ($n['kode_item'] ?? ($n['kode_alat'] ?? ''))));

        $inventaris = null;
        if ($kode !== '') {
            $q = DB::table('laboran_bahan_alat')->where('kode_item', $kode);
            LaboranScope::apply($q, 'jurusan_id', $context);
            $inventaris = $q->first();
        }
        if (!$inventaris) {
            $q = DB::table('laboran_bahan_alat')->where('nama_item', $namaItem);
            LaboranScope::apply($q, 'jurusan_id', $context);
            $inventaris = $q->first();
        }

        $jumlah = (float) str_replace(',', '.', (string) ($n['jumlah'] ?? 1));
        if ($jumlah <= 0) {
            $jumlah = 1;
        }

        return [
            'item_id'        => $inventaris->id ?? null,
            'nama_item'      => $inventaris->nama_item ?? $namaItem,
            'kode_item'      => $inventaris->kode_item ?? ($kode !== '' ? $kode : null),
            'jumlah'         => $jumlah,
            'satuan'         => trim((string) ($n['satuan'] ?? ($inventaris->satuan ?? 'unit'))) ?: 'unit',
            'kondisi_pinjam' => $inventaris->kondisi ?? 'baik',
        ];
    }

    /**
     * Buat dokumen peminjaman baru beserta seluruh itemnya.
     */
    protected function buatDokumen(?string $nomor, array $header, array $items, ?string $jurusanId): void
    {
        $nomor = $nomor ?: $this->generateNomorPinjam();

        $statusAktif = in_array($header['status'], ['dipinjam', 'terlambat'], true);

        $id = DB::table('laboran_peminjaman')->insertGetId([
            'jurusan_id'              => $jurusanId,
            'ruang_lab_nama'          => $header['ruang_lab_nama'] !== '' ? $header['ruang_lab_nama'] : null,
            'nomor_pinjam'            => $nomor,
            'peminjam_tipe'           => $header['peminjam_tipe'],
            'peminjam_id'             => null,
            'peminjam_nama'           => $header['peminjam_nama'],
            'peminjam_kelas'          => $header['peminjam_kelas'] !== '' ? $header['peminjam_kelas'] : null,
            'keperluan'               => $header['keperluan'],
            'tanggal_pinjam'          => $header['tanggal_pinjam'],
            'tanggal_kembali_rencana' => $header['tanggal_kembali_rencana'],
            'tanggal_kembali_aktual'  => $header['tanggal_kembali_aktual'],
            'jumlah_item'             => count($items),
            'kondisi_kembali'         => $header['kondisi_kembali'],
            'status'                  => $header['status'],
            'petugas_ptk_id'          => $header['petugas_ptk_id'],
            'catatan'                 => $header['catatan'] !== '' ? $header['catatan'] : null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        foreach ($items as $item) {
            DB::table('laboran_peminjaman_item')->insert([
                'peminjaman_id'  => $id,
                'item_id'        => $item['item_id'],
                'sumber'         => 'inventaris',
                'aset_id'        => null,
                'nama_item'      => $item['nama_item'],
                'kode_item'      => $item['kode_item'],
                'jumlah'         => $item['jumlah'],
                'satuan'         => $item['satuan'],
                'kondisi_pinjam' => $item['kondisi_pinjam'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Status aktif (alat sudah dibawa peminjam) -> stok tersedia berkurang.
            if ($statusAktif && $item['item_id']) {
                $this->sesuaikanStok((int) $item['item_id'], -(float) $item['jumlah']);
            }
        }
    }

    /**
     * Perbarui dokumen peminjaman yang sudah ada & tambahkan item baru.
     */
    protected function perbaruiDokumen(object $existing, array $header, array $items): string
    {
        $update = [
            'peminjam_tipe'           => $header['peminjam_tipe'],
            'peminjam_nama'           => $header['peminjam_nama'],
            'peminjam_kelas'          => $header['peminjam_kelas'] !== '' ? $header['peminjam_kelas'] : $existing->peminjam_kelas,
            'keperluan'               => $header['keperluan'] !== '' ? $header['keperluan'] : $existing->keperluan,
            'ruang_lab_nama'          => $header['ruang_lab_nama'] !== '' ? $header['ruang_lab_nama'] : $existing->ruang_lab_nama,
            'tanggal_pinjam'          => $header['tanggal_pinjam'],
            'tanggal_kembali_rencana' => $header['tanggal_kembali_rencana'],
            'status'                  => $header['status'],
            'updated_at'              => now(),
        ];

        if ($header['tanggal_kembali_aktual'] !== null) {
            $update['tanggal_kembali_aktual'] = $header['tanggal_kembali_aktual'];
        }
        if ($header['kondisi_kembali'] !== null) {
            $update['kondisi_kembali'] = $header['kondisi_kembali'];
        }
        if ($header['catatan'] !== '') {
            $update['catatan'] = $header['catatan'];
        }

        // Hanya tambahkan item yang belum tercatat (hindari duplikasi saat re-import).
        $jumlahBaru = 0;
        foreach ($items as $item) {
            $sudahAda = DB::table('laboran_peminjaman_item')
                ->where('peminjaman_id', $existing->id)
                ->where('nama_item', $item['nama_item'])
                ->where('kode_item', $item['kode_item'])
                ->exists();

            if ($sudahAda) {
                continue;
            }

            DB::table('laboran_peminjaman_item')->insert([
                'peminjaman_id'  => $existing->id,
                'item_id'        => $item['item_id'],
                'sumber'         => 'inventaris',
                'aset_id'        => null,
                'nama_item'      => $item['nama_item'],
                'kode_item'      => $item['kode_item'],
                'jumlah'         => $item['jumlah'],
                'satuan'         => $item['satuan'],
                'kondisi_pinjam' => $item['kondisi_pinjam'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $jumlahBaru++;
        }

        if ($jumlahBaru > 0) {
            $total = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $existing->id)->count();
            $update['jumlah_item'] = $total;
        }

        DB::table('laboran_peminjaman')->where('id', $existing->id)->update($update);

        return 'updated';
    }

    /**
     * Sesuaikan stok tersedia inventaris (tidak pernah negatif).
     */
    protected function sesuaikanStok(int $itemId, float $delta): void
    {
        $stok = (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia');
        $baru = max(0, $stok + $delta);

        DB::table('laboran_bahan_alat')->where('id', $itemId)->update([
            'stok_tersedia' => $baru,
            'updated_at'    => now(),
        ]);
    }

    /**
     * Nomor dokumen berurutan: PJM/YYYYMM/NNNN (unik).
     */
    public function generateNomorPinjam(): string
    {
        $periode = now()->format('Ym');
        $like = "PJM/{$periode}/%";

        $last = DB::table('laboran_peminjaman')
            ->where('nomor_pinjam', 'like', $like)
            ->orderByDesc('id')
            ->value('nomor_pinjam');

        $urut = 1;
        if ($last) {
            $parts = explode('/', $last);
            $urut = ((int) end($parts)) + 1;
        }

        $nomor = sprintf('PJM/%s/%04d', $periode, $urut);

        $guard = 0;
        while (DB::table('laboran_peminjaman')->where('nomor_pinjam', $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('PJM/%s/%04d', $periode, $urut);
            $guard++;
        }

        return $nomor;
    }

    /**
     * Normalisasi kunci kolom: huruf kecil, spasi -> underscore, buang simbol.
     */
    protected function normalisasiKunci(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
            $out[$key] = is_string($v) ? trim($v) : $v;
        }

        return $out;
    }

    /**
     * Parse tanggal dari teks atau serial number Excel.
     */
    protected function parseTanggal(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        // Serial date Excel (mis. 45678)
        if (preg_match('/^\d+(\.\d+)?$/', $raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            try {
                return Carbon::createFromDate(1899, 12, 30)->addDays((int) $raw)->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
