<?php

namespace App\Services;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import & template Excel untuk Inventaris Alat/Bahan Laboratorium.
 *
 * Dipisahkan dari LaboranInventarisController agar controller tetap fokus
 * pada HTTP (routing & response) dan logika parsing dapat diuji terpisah.
 */
class LaboranInventarisImportService
{
    /** Kolom header template, berurutan. */
    public const HEADERS = [
        'kode_item',
        'nama_item',
        'jenis',
        'kategori',
        'spesifikasi',
        'stok_total',
        'satuan',
        'kondisi',
        'jumlah_baik',
        'jumlah_rusak_ringan',
        'jumlah_rusak_berat',
        'jumlah_kedaluwarsa',
        'ruang_lab_nama',
        'lokasi_lemari_rak',
        'tgl_kedaluwarsa',
        'tanggal_pengadaan',
        'tahun_pengadaan',
        'sumber_dana',
    ];

    /**
     * Baris contoh pada template (memudahkan pengguna memahami format).
     */
    public function contohBaris(): array
    {
        return [
            [
                'ITM-LAB-001', 'Mikroskop Binokuler Digital', 'alat', 'Optik',
                'Perbesaran 1000x, kamera USB 5MP',
                '5', 'unit', 'baik', '4', '1', '0', '0',
                'Lab Biologi', 'Lemari A-01', '', '2024-07-15', '2024', 'BOS Reguler',
            ],
            [
                '', 'Traktor Tangan Mini', 'alat', 'Mesin Pertanian',
                'Kapasitas 8 PK, roda dua',
                '10', 'unit', 'baik', '5', '3', '2', '0',
                'Lahan Praktik ATPH', 'Gudang Alsintan', '', '2023-02-20', '2023', 'DAK Fisik',
            ],
            [
                'ITM-LAB-002', 'Alkohol 70% 1 Liter', 'bahan_habis_pakai', 'Bahan Kimia',
                'Kemasan botol 1000 ml teknis',
                '20', 'botol', 'baik', '20', '0', '0', '0',
                'Lab Kimia', 'Rak Bahan C-03', '2028-12-31', '2025-01-10', '2025', 'BOS Reguler',
            ],
        ];
    }

    public function contohBarisBhp(): array
    {
        return [
            [
                'BHP-202610-0001', 'Alkohol 70% 1 Liter', 'bahan_habis_pakai', 'Bahan Kimia',
                'Kemasan botol 1000 ml teknis',
                '20', 'botol', 'baik', '20', '0', '0', '0',
                'Lab Kimia', 'Rak Bahan C-03', '2028-12-31', '2025-01-10', '2025', 'BOS Reguler',
            ],
            [
                '', 'Aquades Murni 5 Liter', 'bahan_habis_pakai', 'Bahan Kimia',
                'Air demineralisasi untuk praktikum',
                '10', 'jerigen', 'baik', '10', '0', '0', '0',
                'Lab IPA', 'Rak A-02', '2029-06-30', '2025-02-15', '2025', 'BOS Reguler',
            ],
            [
                '', 'Pupuk NPK Mutiara 16-16-16 1 Kg', 'bahan_habis_pakai', 'Bahan Pertanian',
                'Pupuk majemuk untuk praktik pemupukan',
                '15', 'kantong', 'baik', '15', '0', '0', '0',
                'Lahan Praktik ATPH', 'Gudang Pupuk', '2027-10-20', '2024-11-05', '2024', 'Komite / Yayasan',
            ],
        ];
    }

    public function metaTemplate(): array
    {
        return [
            'title'    => 'Format Import Inventaris Alat & Bahan Laboratorium',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Kode Item'       => 'Boleh dikosongkan — sistem akan membuat kode otomatis',
                'Rincian Kondisi' => 'jumlah_baik + jumlah_rusak_ringan + jumlah_rusak_berat + jumlah_kedaluwarsa = stok_total',
                'Data Pengadaan'  => 'tanggal_pengadaan (YYYY-MM-DD), tahun_pengadaan & sumber_dana (BOS Reguler, DAK, dll)',
            ],
        ];
    }

    public function metaTemplateBhp(): array
    {
        return [
            'title'    => 'Format Import Bahan Habis Pakai (BHP)',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Kode Item'       => 'Boleh dikosongkan — sistem akan membuat kode otomatis (BHP-YYYYMM-NNNN)',
                'Rincian Kondisi' => 'jumlah_baik + jumlah_rusak_ringan + jumlah_rusak_berat + jumlah_kedaluwarsa = stok_total',
                'Data Pengadaan'  => 'tanggal_pengadaan (YYYY-MM-DD), tahun_pengadaan & sumber_dana (BOS Reguler, DAK, dll)',
            ],
        ];
    }

    /**
     * Hasilkan berkas template .xlsx dan kembalikan path sementaranya.
     */
    public function generateTemplateFile(): string
    {
        $path = storage_path('app/temp_template_inventaris_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, $this->metaTemplate(), self::HEADERS, $this->contohBaris(), [
            'sheetName' => 'Inventaris Lab',
        ]);

        return $path;
    }

    /**
     * Hasilkan berkas template .xlsx khusus Bahan Habis Pakai.
     */
    public function generateTemplateFileBhp(): string
    {
        $path = storage_path('app/temp_template_bhp_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, $this->metaTemplateBhp(), self::HEADERS, $this->contohBarisBhp(), [
            'sheetName' => 'Bahan Habis Pakai',
        ]);

        return $path;
    }

    /**
     * Proses berkas unggahan menjadi baris inventaris.
     *
     * @return array{imported:int, updated:int}
     *
     * @throws \RuntimeException bila berkas tidak valid / kosong
     */
    public function import(UploadedFile $file, array $context, ?string $inputJurusanId = null, ?string $paksaJenis = null): array
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
        $imported = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $data = $this->normalisasiBaris($row, $paksaJenis);
            if ($data === null) {
                continue;
            }

            $existing = DB::table('laboran_bahan_alat')->where('kode_item', $data['kode_item'])->first();

            if ($existing) {
                $this->perbarui($existing, $data);
                $updated++;
            } else {
                $this->simpanBaru($data, $jurusanId);
                $imported++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated];
    }

    /**
     * Ubah satu baris mentah menjadi payload siap simpan.
     * Kembalikan null bila baris tidak memiliki nama item (baris kosong).
     */
    protected function normalisasiBaris(array $row, ?string $paksaJenis = null): ?array
    {
        $n = $this->normalisasiKunci($row);

        $nama = trim((string) ($n['nama_item'] ?? ($n['nama_barang'] ?? ($n['nama'] ?? ''))));
        if ($nama === '') {
            return null;
        }

        $rawJenis = strtolower((string) ($n['jenis'] ?? 'alat'));
        $jenis = in_array($rawJenis, ['bahan', 'bahan_habis_pakai', 'habis_pakai', 'bhp'], true)
            ? 'bahan_habis_pakai'
            : 'alat';

        // Modul BHP memaksa semua baris masuk sebagai bahan habis pakai.
        if ($paksaJenis !== null) {
            $jenis = $paksaJenis;
        }

        $kode = strtoupper(trim((string) ($n['kode_item'] ?? ($n['kode'] ?? ''))));
        if ($kode === '') {
            $kode = $this->generateKodeItem($jenis);
        }

        $total = (float) ($n['stok_total'] ?? ($n['stok'] ?? ($n['jumlah'] ?? 1)));
        $tersedia = isset($n['stok_tersedia']) ? (float) $n['stok_tersedia'] : $total;

        [$kondisi, $rincian] = $this->resolveKondisi($n, $total);

        // Data pengadaan
        $tglPengadaanRaw = (string) ($n['tanggal_pengadaan'] ?? ($n['tgl_pengadaan'] ?? ''));
        $tglPengadaan = null;
        if ($tglPengadaanRaw !== '') {
            try {
                $tglPengadaan = Carbon::parse($tglPengadaanRaw)->toDateString();
            } catch (\Throwable $e) {
                $tglPengadaan = null;
            }
        }

        $tahunPengadaan = (int) ($n['tahun_pengadaan'] ?? 0);
        if ($tahunPengadaan <= 0 && $tglPengadaan !== null) {
            $tahunPengadaan = (int) Carbon::parse($tglPengadaan)->format('Y');
        }

        $kadaluwarsa = (string) ($n['tgl_kedaluwarsa'] ?? ($n['kedaluwarsa'] ?? ''));
        $tglVal = null;
        if ($kadaluwarsa !== '') {
            try {
                $tglVal = Carbon::parse($kadaluwarsa)->toDateString();
            } catch (\Throwable $e) {
                $tglVal = null;
            }
        }

        return [
            'kode_item'           => $kode,
            'nama_item'           => $nama,
            'jenis'               => $jenis,
            'stok_total'          => $total,
            'stok_tersedia'       => $tersedia,
            'satuan'              => (string) ($n['satuan'] ?? 'unit'),
            'kondisi'             => $kondisi,
            'jumlah_baik'         => $rincian['baik'],
            'jumlah_rusak_ringan' => $rincian['rusak_ringan'],
            'jumlah_rusak_berat'  => $rincian['rusak_berat'],
            'jumlah_kedaluwarsa'  => $rincian['kedaluwarsa'],
            'ruang_lab_nama'      => (string) ($n['ruang_lab_nama'] ?? ($n['lokasi'] ?? ($n['ruangan'] ?? 'Lab Utama'))),
            'lokasi_lemari_rak'   => (string) ($n['lokasi_lemari_rak'] ?? ($n['rak'] ?? ($n['lemari'] ?? ''))),
            'spesifikasi'         => (string) ($n['spesifikasi'] ?? ''),
            'tgl_kedaluwarsa'     => $tglVal,
            'tanggal_pengadaan'   => $tglPengadaan,
            'tahun_pengadaan'     => $tahunPengadaan > 0 ? $tahunPengadaan : null,
            'sumber_dana'         => (string) ($n['sumber_dana'] ?? ($n['sumber'] ?? ($n['sumber_anggaran'] ?? 'BOS'))),
        ];
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
     * Tentukan kondisi dominan + rincian jumlah per kondisi.
     *
     * @return array{0:string, 1:array<string,float>}
     */
    protected function resolveKondisi(array $n, float $total): array
    {
        $baik = (float) ($n['jumlah_baik'] ?? ($n['baik'] ?? 0));
        $rr   = (float) ($n['jumlah_rusak_ringan'] ?? ($n['rusak_ringan'] ?? 0));
        $rb   = (float) ($n['jumlah_rusak_berat'] ?? ($n['rusak_berat'] ?? 0));
        $kad  = (float) ($n['jumlah_kedaluwarsa'] ?? ($n['kedaluwarsa_jml'] ?? 0));

        // Tanpa rincian: seluruh stok mengikuti kolom `kondisi`.
        if (($baik + $rr + $rb + $kad) <= 0) {
            $raw = strtolower((string) ($n['kondisi'] ?? 'baik'));
            $kondisi = match ($raw) {
                'rusak_ringan', 'ringan' => 'rusak_ringan',
                'rusak_berat', 'berat'   => 'rusak_berat',
                'kedaluwarsa', 'expired' => 'kedaluwarsa',
                default                  => 'baik',
            };

            $rincian = ['baik' => 0.0, 'rusak_ringan' => 0.0, 'rusak_berat' => 0.0, 'kedaluwarsa' => 0.0];
            $rincian[$kondisi] = $total;

            return [$kondisi, $rincian];
        }

        // Kondisi dominan = jumlah terbanyak; rusak berat diprioritaskan bila seri.
        $kondisi = 'baik';
        $max = $baik;
        if ($rr > $max) { $kondisi = 'rusak_ringan'; $max = $rr; }
        if ($rb >= $max && $rb > 0) { $kondisi = 'rusak_berat'; $max = $rb; }
        if ($kad > $max) { $kondisi = 'kedaluwarsa'; }

        return [$kondisi, [
            'baik'         => $baik,
            'rusak_ringan' => $rr,
            'rusak_berat'  => $rb,
            'kedaluwarsa'  => $kad,
        ]];
    }

    protected function perbarui(object $existing, array $d): void
    {
        DB::table('laboran_bahan_alat')->where('id', $existing->id)->update([
            'nama_item'           => $d['nama_item'],
            'jenis'               => $d['jenis'],
            'stok_total'          => $d['stok_total'],
            'stok_tersedia'       => $d['stok_tersedia'],
            'jumlah_baik'         => $d['jumlah_baik'],
            'jumlah_rusak_ringan' => $d['jumlah_rusak_ringan'],
            'jumlah_rusak_berat'  => $d['jumlah_rusak_berat'],
            'jumlah_kedaluwarsa'  => $d['jumlah_kedaluwarsa'],
            'satuan'              => $d['satuan'],
            'kondisi'             => $d['kondisi'],
            'ruang_lab_nama'      => $d['ruang_lab_nama'],
            'spesifikasi'         => $d['spesifikasi'] ?: $existing->spesifikasi,
            'lokasi_lemari_rak'   => $d['lokasi_lemari_rak'] ?: $existing->lokasi_lemari_rak,
            'tgl_kedaluwarsa'     => $d['tgl_kedaluwarsa'] ?: $existing->tgl_kedaluwarsa,
            'tanggal_pengadaan'   => $d['tanggal_pengadaan'] ?: $existing->tanggal_pengadaan,
            'tahun_pengadaan'     => $d['tahun_pengadaan'] ?: $existing->tahun_pengadaan,
            'sumber_dana'         => $d['sumber_dana'] ?: ($existing->sumber_dana ?? 'BOS'),
            'updated_at'          => now(),
        ]);
    }

    protected function simpanBaru(array $d, ?string $jurusanId): void
    {
        DB::table('laboran_bahan_alat')->insert([
            'jurusan_id'          => $jurusanId,
            'ruang_lab_nama'      => $d['ruang_lab_nama'],
            'kode_item'           => $d['kode_item'],
            'nama_item'           => $d['nama_item'],
            'jenis'               => $d['jenis'],
            'spesifikasi'         => $d['spesifikasi'] ?: null,
            'stok_total'          => max(0.01, $d['stok_total']),
            'stok_tersedia'       => max(0, $d['stok_tersedia']),
            'jumlah_baik'         => $d['jumlah_baik'],
            'jumlah_rusak_ringan' => $d['jumlah_rusak_ringan'],
            'jumlah_rusak_berat'  => $d['jumlah_rusak_berat'],
            'jumlah_kedaluwarsa'  => $d['jumlah_kedaluwarsa'],
            'satuan'              => $d['satuan'],
            'kondisi'             => $d['kondisi'],
            'tgl_kedaluwarsa'     => $d['tgl_kedaluwarsa'],
            'tanggal_pengadaan'   => $d['tanggal_pengadaan'],
            'tahun_pengadaan'     => $d['tahun_pengadaan'],
            'sumber_dana'         => $d['sumber_dana'] ?: 'BOS',
            'lokasi_lemari_rak'   => $d['lokasi_lemari_rak'] ?: null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * Buat kode item otomatis yang unik.
     * Format: {PREFIX}-{YYYYMM}-{NNNN}, mis. ALT-202610-0001.
     */
    public function generateKodeItem(?string $jenis): string
    {
        $prefix = $jenis === 'bahan_habis_pakai' ? 'BHP' : 'ALT';
        $base = $prefix . '-' . now()->format('Ym') . '-';

        $last = DB::table('laboran_bahan_alat')
            ->where('kode_item', 'like', $base . '%')
            ->orderByDesc('kode_item')
            ->value('kode_item');

        $seq = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        $candidate = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $guard = 0;
        while (DB::table('laboran_bahan_alat')->where('kode_item', $candidate)->exists() && $guard < 9999) {
            $seq++;
            $candidate = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $guard++;
        }

        return $candidate;
    }
}
