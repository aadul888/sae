<?php

namespace App\Services;

use App\Support\Table;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Layanan Import & Template Excel untuk Inventaris & Aset Sarpras Sekolah.
 *
 * Mengadopsi arsitektur yang sama dengan LaboranInventarisImportService:
 *  - Kode aset dibuat otomatis jika dikosongkan (format AST-YYYYMM-NNNN).
 *  - Rincian kondisi per jumlah unit (jumlah_baik, jumlah_rusak_ringan, jumlah_rusak_berat).
 *  - Resolusi ruang penempatan dan alokasi jurusan.
 *  - Penanganan tanggal perolehan & sinkronisasi tahun perolehan.
 */
class SarprasAsetImportService
{
    /** Kolom header template import aset sarpras. */
    public const HEADERS = [
        'kode_aset',
        'nama_barang',
        'kategori',
        'merk_tipe',
        'no_seri_pabrik',
        'tahun_perolehan',
        'tanggal_perolehan',
        'sumber_dana',
        'harga_perolehan',
        'kondisi',
        'jumlah_baik',
        'jumlah_rusak_ringan',
        'jumlah_rusak_berat',
        'nama_ruang',
        'jurusan',
        'jumlah',
        'satuan',
        'status_ketersediaan',
    ];

    /**
     * Contoh baris data untuk template Excel.
     */
    public function contohBaris(): array
    {
        return [
            [
                'AST-202610-0001', 'PC All-in-One Core i5 RAM 16GB', 'Elektronik',
                'Lenovo V50a-24IMB', 'SN-LNV-2026-001', '2024', '2024-03-15',
                'BOS Reguler', '12500000', 'baik', '36', '0', '0',
                'Lab Komputer 1', 'TKJ', '36', 'unit', 'tersedia',
            ],
            [
                '', 'Proyektor LCD 3600 Lumens', 'Elektronik',
                'Epson EB-X500', 'SN-EPS-2026-042', '2023', '2023-08-20',
                'BOS Reguler', '6800000', 'baik', '2', '0', '0',
                'Ruang Guru', '', '2', 'unit', 'tersedia',
            ],
            [
                '', 'Traktor Tangan Roda Dua 8.5 HP', 'Mesin',
                'Quick G1000 Boxer', 'SN-QCK-2022-108', '2022', '2022-05-10',
                'DAK Fisik', '28500000', 'rusak_ringan', '1', '1', '0',
                'Gudang Alat', 'ATPH', '2', 'unit', 'tersedia',
            ],
            [
                '', 'Kursi Siswa Kayu Jati', 'Mebel/Furnitur',
                'Standar Kemdikbud', '', '2023', '2023-01-15',
                'Komite / Yayasan', '250000', 'baik', '36', '0', '0',
                'Kelas X-1', '', '36', 'unit', 'tersedia',
            ],
        ];
    }

    public function metaTemplate(): array
    {
        return [
            'title'    => 'Format Import Sarpras & Aset Sekolah',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Kode Aset'       => 'Boleh dikosongkan — sistem membuat kode otomatis (AST-YYYYMM-NNNN)',
                'Rincian Kondisi' => 'jumlah_baik + jumlah_rusak_ringan + jumlah_rusak_berat = jumlah',
                'Alokasi Jurusan' => 'Boleh dikosongkan untuk aset umum sekolah, atau isi singkatan jurusan (TKJ, TKR, ATPH, dsb)',
                'Tanggal & Tahun' => 'tanggal_perolehan (YYYY-MM-DD); tahun terisi otomatis dari tanggal bila kosong',
            ],
        ];
    }

    /**
     * Hasilkan berkas template .xlsx dan kembalikan path sementaranya.
     */
    public function generateTemplateFile(): string
    {
        $path = storage_path('app/temp_template_aset_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, $this->metaTemplate(), self::HEADERS, $this->contohBaris(), [
            'sheetName' => 'Aset Sarpras',
        ]);

        return $path;
    }

    /**
     * Proses import berkas Excel menjadi record aset sarpras.
     *
     * @return array{imported:int, updated:int}
     *
     * @throws \RuntimeException bila berkas tidak valid atau kosong
     */
    public function import(UploadedFile $file): array
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

        $ruangMap = Table::exists('sarpras_ruang')
            ? DB::table('sarpras_ruang')->pluck('id', 'nama_ruang')->toArray()
            : [];

        $jurusanMap = [];
        if (Table::exists('jurusan_meta')) {
            $jRows = DB::table('jurusan_meta')->get(['jurusan_id', 'nama_jurusan', 'singkatan']);
            foreach ($jRows as $jr) {
                $jurusanMap[strtolower(trim((string) $jr->jurusan_id))] = (string) $jr->jurusan_id;
                if ($jr->singkatan) {
                    $jurusanMap[strtolower(trim((string) $jr->singkatan))] = (string) $jr->jurusan_id;
                }
                if ($jr->nama_jurusan) {
                    $jurusanMap[strtolower(trim((string) $jr->nama_jurusan))] = (string) $jr->jurusan_id;
                }
            }
        }

        $imported = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $data = $this->normalisasiBaris($row, $ruangMap, $jurusanMap);
            if ($data === null) {
                continue;
            }

            $existing = DB::table('sarpras_aset')->where('kode_aset', $data['kode_aset'])->first();

            if ($existing) {
                $this->perbarui($existing, $data);
                $updated++;
            } else {
                $this->simpanBaru($data);
                $imported++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated];
    }

    /**
     * Normalisasi baris mentah import.
     */
    protected function normalisasiBaris(array $row, array $ruangMap, array $jurusanMap): ?array
    {
        $n = $this->normalisasiKunci($row);

        $nama = trim((string) ($n['nama_barang'] ?? ($n['nama_aset'] ?? ($n['nama'] ?? ''))));
        if ($nama === '') {
            return null;
        }

        $kode = strtoupper(trim((string) ($n['kode_aset'] ?? ($n['kode'] ?? ''))));
        if ($kode === '') {
            $kode = $this->generateKodeAset();
        }

        $kategori   = trim((string) ($n['kategori'] ?? 'Lainnya')) ?: 'Lainnya';
        $merkTipe   = trim((string) ($n['merk_tipe'] ?? ($n['merk'] ?? '')));
        $noSeri     = trim((string) ($n['no_seri_pabrik'] ?? ($n['no_seri'] ?? '')));
        $sumberDana = trim((string) ($n['sumber_dana'] ?? 'BOS Reguler')) ?: 'BOS Reguler';
        $harga      = max(0, (float) ($n['harga_perolehan'] ?? ($n['harga'] ?? 0)));
        $jumlah     = max(1, (int) ($n['jumlah'] ?? 1));
        $satuan     = trim((string) ($n['satuan'] ?? 'unit')) ?: 'unit';
        $status     = trim((string) ($n['status_ketersediaan'] ?? ($n['status'] ?? 'tersedia'))) ?: 'tersedia';
        if (!in_array($status, ['tersedia', 'dipinjam', 'perbaikan', 'dihapuskan'], true)) {
            $status = 'tersedia';
        }

        [$kondisi, $rincian] = $this->resolveKondisi($n, $jumlah);

        // Resolusi tanggal & tahun perolehan
        $tglPerolehanRaw = (string) ($n['tanggal_perolehan'] ?? ($n['tgl_perolehan'] ?? ($n['tanggal_pengadaan'] ?? '')));
        $tglPerolehan = null;
        if ($tglPerolehanRaw !== '') {
            try {
                $tglPerolehan = Carbon::parse($tglPerolehanRaw)->toDateString();
            } catch (\Throwable $e) {
                $tglPerolehan = null;
            }
        }

        $tahun = (int) ($n['tahun_perolehan'] ?? ($n['tahun_pengadaan'] ?? ($n['tahun'] ?? 0)));
        if ($tahun <= 0 && $tglPerolehan !== null) {
            $tahun = (int) Carbon::parse($tglPerolehan)->format('Y');
        }
        if ($tahun <= 0) {
            $tahun = (int) date('Y');
        }

        // Resolusi Ruang
        $namaRuang = (string) ($n['nama_ruang'] ?? ($n['lokasi_ruang'] ?? ($n['ruang'] ?? '')));
        $ruangId = null;
        if ($namaRuang !== '') {
            $ruangId = $ruangMap[$namaRuang] ?? null;
            if (!$ruangId) {
                foreach ($ruangMap as $rNama => $rId) {
                    if (stripos($rNama, $namaRuang) !== false || stripos($namaRuang, $rNama) !== false) {
                        $ruangId = $rId;
                        break;
                    }
                }
            }
        }

        // Resolusi Jurusan
        $rawJurusan = strtolower(trim((string) ($n['jurusan'] ?? ($n['jurusan_id'] ?? ($n['program_keahlian'] ?? '')))));
        $jurusanId  = $rawJurusan !== '' ? ($jurusanMap[$rawJurusan] ?? null) : null;

        return [
            'kode_aset'           => $kode,
            'nama_barang'         => $nama,
            'kategori'            => $kategori,
            'merk_tipe'           => $merkTipe ?: null,
            'no_seri_pabrik'      => $noSeri ?: null,
            'tahun_perolehan'     => $tahun,
            'tanggal_perolehan'   => $tglPerolehan,
            'sumber_dana'         => $sumberDana,
            'harga_perolehan'     => $harga,
            'kondisi'             => $kondisi,
            'jumlah_baik'         => $rincian['baik'],
            'jumlah_rusak_ringan' => $rincian['rusak_ringan'],
            'jumlah_rusak_berat'  => $rincian['rusak_berat'],
            'ruang_id'            => $ruangId,
            'jurusan_id'          => $jurusanId,
            'jumlah'              => $jumlah,
            'satuan'              => $satuan,
            'status_ketersediaan' => $status,
        ];
    }

    /**
     * Normalisasi kunci associative array baris Excel.
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
     * Tentukan kondisi dominan + rincian unit per kondisi.
     *
     * @return array{0:string, 1:array{baik:int, rusak_ringan:int, rusak_berat:int}}
     */
    public function resolveKondisi(array $n, int $total): array
    {
        $baik = max(0, (int) ($n['jumlah_baik'] ?? ($n['baik'] ?? 0)));
        $rr   = max(0, (int) ($n['jumlah_rusak_ringan'] ?? ($n['rusak_ringan'] ?? 0)));
        $rb   = max(0, (int) ($n['jumlah_rusak_berat'] ?? ($n['rusak_berat'] ?? 0)));

        // Jika rincian kosong: seluruh unit dialokasikan ke kondisi yang dipilih
        if (($baik + $rr + $rb) <= 0) {
            $raw = strtolower((string) ($n['kondisi'] ?? 'baik'));
            $kondisi = match ($raw) {
                'rusak_ringan', 'ringan' => 'rusak_ringan',
                'rusak_berat', 'berat'   => 'rusak_berat',
                default                  => 'baik',
            };

            $rincian = ['baik' => 0, 'rusak_ringan' => 0, 'rusak_berat' => 0];
            $rincian[$kondisi] = $total;

            return [$kondisi, $rincian];
        }

        // Tentukan kondisi dominan (jumlah terbanyak, rusak berat didahulukan jika seri)
        $kondisi = 'baik';
        $max = $baik;
        if ($rr > $max) {
            $kondisi = 'rusak_ringan';
            $max = $rr;
        }
        if ($rb >= $max && $rb > 0) {
            $kondisi = 'rusak_berat';
        }

        return [$kondisi, [
            'baik'         => $baik,
            'rusak_ringan' => $rr,
            'rusak_berat'  => $rb,
        ]];
    }

    protected function perbarui(object $existing, array $d): void
    {
        DB::table('sarpras_aset')->where('id', $existing->id)->update([
            'nama_barang'         => $d['nama_barang'],
            'kategori'            => $d['kategori'],
            'merk_tipe'           => $d['merk_tipe'] ?: $existing->merk_tipe,
            'no_seri_pabrik'      => $d['no_seri_pabrik'] ?: $existing->no_seri_pabrik,
            'tahun_perolehan'     => $d['tahun_perolehan'] ?: $existing->tahun_perolehan,
            'tanggal_perolehan'   => $d['tanggal_perolehan'] ?: $existing->tanggal_perolehan,
            'sumber_dana'         => $d['sumber_dana'],
            'harga_perolehan'     => $d['harga_perolehan'] ?: $existing->harga_perolehan,
            'kondisi'             => $d['kondisi'],
            'jumlah_baik'         => $d['jumlah_baik'],
            'jumlah_rusak_ringan' => $d['jumlah_rusak_ringan'],
            'jumlah_rusak_berat'  => $d['jumlah_rusak_berat'],
            'ruang_id'            => $d['ruang_id'] ?: $existing->ruang_id,
            'jurusan_id'          => $d['jurusan_id'] ?: $existing->jurusan_id,
            'jumlah'              => $d['jumlah'],
            'satuan'              => $d['satuan'],
            'status_ketersediaan' => $d['status_ketersediaan'],
            'updated_at'          => now(),
        ]);
    }

    protected function simpanBaru(array $d): void
    {
        DB::table('sarpras_aset')->insert([
            'kode_aset'           => $d['kode_aset'],
            'nama_barang'         => $d['nama_barang'],
            'kategori'            => $d['kategori'],
            'merk_tipe'           => $d['merk_tipe'],
            'no_seri_pabrik'      => $d['no_seri_pabrik'],
            'tahun_perolehan'     => $d['tahun_perolehan'],
            'tanggal_perolehan'   => $d['tanggal_perolehan'],
            'sumber_dana'         => $d['sumber_dana'],
            'harga_perolehan'     => $d['harga_perolehan'],
            'kondisi'             => $d['kondisi'],
            'jumlah_baik'         => $d['jumlah_baik'],
            'jumlah_rusak_ringan' => $d['jumlah_rusak_ringan'],
            'jumlah_rusak_berat'  => $d['jumlah_rusak_berat'],
            'ruang_id'            => $d['ruang_id'],
            'jurusan_id'          => $d['jurusan_id'],
            'jumlah'              => $d['jumlah'],
            'satuan'              => $d['satuan'],
            'status_ketersediaan' => $d['status_ketersediaan'],
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * Buat kode aset unik otomatis berformat AST-YYYYMM-NNNN.
     */
    public function generateKodeAset(): string
    {
        $base = 'AST-' . now()->format('Ym') . '-';

        $last = DB::table('sarpras_aset')
            ->where('kode_aset', 'like', $base . '%')
            ->orderByDesc('kode_aset')
            ->value('kode_aset');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        $candidate = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $guard = 0;
        while (DB::table('sarpras_aset')->where('kode_aset', $candidate)->exists() && $guard < 9999) {
            $seq++;
            $candidate = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $guard++;
        }

        return $candidate;
    }
}
