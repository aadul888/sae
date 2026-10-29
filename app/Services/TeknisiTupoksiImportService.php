<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import & template Excel generik untuk 6 modul TUPOKSI Teknisi (Indikator #1–#5).
 *
 * Satu berkas memuat satu tabel: satu baris = satu catatan. Spesifikasi tiap
 * modul (kolom, tipe, default, contoh) dipusatkan di SPECS sehingga controller
 * tetap tipis dan perilaku antar-modul konsisten.
 */
class TeknisiTupoksiImportService
{
    /** @var array<int, string> */
    public const MODULES = [
        'area-tugas',
        'kebersihan-ruang',
        'kebersihan-rapat',
        'sarana-rapat',
        'jurnal-perbaikan',
        'supervisi',
    ];

    private const SPECS = [
        'area-tugas' => [
            'table'      => 'teknisi_area_tugas',
            'nomor'      => ['nomor_jadwal', 'AT'],
            'title'      => 'Format Import Jadwal Area Tugas Kebersihan (Indikator #1)',
            'sheet'      => 'Area Tugas',
            'cetakTitle' => 'REKAP JADWAL AREA TUGAS KEBERSIHAN',
            'columns'    => [
                'area' => 'string', 'zona' => 'string', 'periode_jadwal' => 'string',
                'petugas_ptk_id' => 'string', 'petugas_nama' => 'string', 'shift' => 'string',
                'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'jenis_tugas' => 'string',
                'laporan_siswa' => 'text', 'sumber_laporan' => 'string', 'uraian' => 'text',
                'catatan' => 'text', 'status' => 'string',
            ],
            'required'   => ['area', 'petugas_nama'],
            'defaults'   => ['periode_jadwal' => 'harian', 'shift' => 'pagi', 'jenis_tugas' => 'tukang_kebersihan', 'status' => 'aktif'],
            'cetak'      => [
                ['Area Tugas', 'area'], ['Zona', 'zona'], ['Jenis Tugas', 'jenis_tugas'],
                ['Periode', 'periode_jadwal'], ['Shift', 'shift'], ['Petugas', 'petugas_nama'],
                ['Mulai', 'tanggal_mulai'], ['Selesai', 'tanggal_selesai'],
                ['Uraian', 'uraian'], ['Catatan', 'catatan'], ['Status', 'status'],
            ],
            'contoh'     => [
                'area' => 'Ruang Kepala Sekolah', 'zona' => 'Gedung A', 'periode_jadwal' => 'harian',
                'petugas_ptk_id' => '', 'petugas_nama' => 'Budi Santoso', 'shift' => 'pagi',
                'tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-10-31',
                'jenis_tugas' => 'tukang_kebersihan', 'laporan_siswa' => '',
                'sumber_laporan' => '', 'uraian' => 'Menyapu & mengepel ruang', 'catatan' => '', 'status' => 'aktif',
            ],
        ],
        'kebersihan-ruang' => [
            'table'      => 'teknisi_kebersihan_ruang',
            'nomor'      => ['nomor_kegiatan', 'KBN'],
            'title'      => 'Format Import Kebersihan Lingkungan & Ruang Kerja (Indikator #2)',
            'sheet'      => 'Kebersihan Ruang',
            'cetakTitle' => 'REKAP KEBERSIHAN LINGKUNGAN & RUANG KERJA',
            'columns'    => [
                'tanggal_kegiatan' => 'date', 'ruang_nama' => 'string', 'kategori_ruang' => 'string',
                'kondisi' => 'string', 'uraian_kegiatan' => 'text', 'peralatan_digunakan' => 'text',
                'temuan' => 'text', 'tindak_lanjut' => 'text', 'foto_sebelum' => 'string',
                'foto_sesudah' => 'string', 'dokumentasi_paths' => 'paths',
                'petugas_nama' => 'string', 'status' => 'string',
            ],
            'required'   => ['ruang_nama'],
            'defaults'   => ['kategori_ruang' => 'perkantoran', 'kondisi' => 'bersih', 'status' => 'selesai'],
            'cetak'      => [
                ['Tanggal', 'tanggal_kegiatan'], ['Ruang', 'ruang_nama'], ['Kategori', 'kategori_ruang'],
                ['Kondisi', 'kondisi'], ['Uraian', 'uraian_kegiatan'], ['Peralatan', 'peralatan_digunakan'],
                ['Temuan', 'temuan'], ['Tindak Lanjut', 'tindak_lanjut'], ['Status', 'status'],
            ],
            'contoh'     => [
                'tanggal_kegiatan' => '2026-10-01', 'ruang_nama' => 'Ruang TU', 'kategori_ruang' => 'administrasi',
                'kondisi' => 'bersih', 'uraian_kegiatan' => 'Menyapu, mengepel, membersihkan meja',
                'peralatan_digunakan' => 'Sapu, pel, kemoceng', 'temuan' => '-', 'tindak_lanjut' => '-',
                'foto_sebelum' => '', 'foto_sesudah' => '', 'dokumentasi_paths' => '',
                'petugas_nama' => 'Budi Santoso', 'status' => 'selesai',
            ],
        ],
        'kebersihan-rapat' => [
            'table'      => 'teknisi_kebersihan_rapat',
            'nomor'      => ['nomor_kegiatan', 'KRT'],
            'title'      => 'Format Import Kebersihan Lingkungan Ruang Rapat (Indikator #3)',
            'sheet'      => 'Kebersihan Rapat',
            'cetakTitle' => 'REKAP KEBERSIHAN LINGKUNGAN RUANG RAPAT',
            'columns'    => [
                'tanggal_kegiatan' => 'date', 'lokasi' => 'string', 'nama_rapat' => 'string',
                'area' => 'string', 'tahap' => 'string', 'kondisi' => 'string',
                'uraian_kegiatan' => 'text', 'peralatan_digunakan' => 'text', 'temuan' => 'text',
                'tindak_lanjut' => 'text', 'foto_sebelum' => 'string', 'foto_sesudah' => 'string',
                'dokumentasi_paths' => 'paths', 'petugas_nama' => 'string', 'status' => 'string',
            ],
            'required'   => [],
            'defaults'   => ['lokasi' => 'Ruang Rapat', 'area' => 'ruang_rapat', 'tahap' => 'sebelum_acara', 'kondisi' => 'bersih', 'status' => 'selesai'],
            'cetak'      => [
                ['Tanggal', 'tanggal_kegiatan'], ['Lokasi', 'lokasi'], ['Area', 'area'],
                ['Nama Rapat', 'nama_rapat'], ['Tahap', 'tahap'], ['Kondisi', 'kondisi'],
                ['Uraian', 'uraian_kegiatan'], ['Temuan', 'temuan'], ['Tindak Lanjut', 'tindak_lanjut'],
                ['Status', 'status'],
            ],
            'contoh'     => [
                'tanggal_kegiatan' => '2026-10-01', 'lokasi' => 'Ruang Rapat Utama', 'nama_rapat' => 'Rapat Dinas',
                'area' => 'ruang_rapat', 'tahap' => 'sebelum_acara', 'kondisi' => 'bersih',
                'uraian_kegiatan' => 'Menyiapkan & membersihkan ruang rapat', 'peralatan_digunakan' => 'Sapu, pel',
                'temuan' => '-', 'tindak_lanjut' => '-', 'foto_sebelum' => '', 'foto_sesudah' => '',
                'dokumentasi_paths' => '', 'petugas_nama' => 'Budi Santoso', 'status' => 'selesai',
            ],
        ],
        'sarana-rapat' => [
            'table'      => 'teknisi_sarana_rapat',
            'nomor'      => ['nomor_laporan', 'SRP'],
            'title'      => 'Format Import Laporan Sarana Kegiatan Rapat (Indikator #4)',
            'sheet'      => 'Sarana Rapat',
            'cetakTitle' => 'REKAP LAPORAN SARANA KEGIATAN RAPAT',
            'columns'    => [
                'tanggal_rapat' => 'date', 'nama_rapat' => 'string', 'ruang_nama' => 'string',
                'jumlah_peserta' => 'int', 'daftar_sarana' => 'sarana', 'kelengkapan_sarana' => 'string',
                'catatan_kebutuhan' => 'text', 'catatan' => 'text', 'petugas_nama' => 'string', 'status' => 'string',
            ],
            'required'   => ['nama_rapat'],
            'defaults'   => ['kelengkapan_sarana' => 'lengkap', 'status' => 'selesai'],
            'cetak'      => [
                ['Tanggal', 'tanggal_rapat'], ['Nama Rapat', 'nama_rapat'], ['Ruang', 'ruang_nama'],
                ['Jumlah Peserta', 'jumlah_peserta'], ['Kelengkapan', 'kelengkapan_sarana'],
                ['Daftar Sarana', 'daftar_sarana'], ['Catatan Kebutuhan', 'catatan_kebutuhan'],
                ['Catatan', 'catatan'], ['Status', 'status'],
            ],
            'contoh'     => [
                'tanggal_rapat' => '2026-10-01', 'nama_rapat' => 'Rapat Dinas', 'ruang_nama' => 'Ruang Rapat Utama',
                'jumlah_peserta' => '25', 'daftar_sarana' => 'Proyektor | 1 | baik', 'kelengkapan_sarana' => 'lengkap',
                'catatan_kebutuhan' => '-', 'catatan' => '-', 'petugas_nama' => 'Budi Santoso', 'status' => 'selesai',
            ],
        ],
        'jurnal-perbaikan' => [
            'table'      => 'teknisi_jurnal_perbaikan',
            'nomor'      => ['nomor_jurnal', 'JRN'],
            'title'      => 'Format Import Jurnal Perbaikan Sarana Prasarana (Indikator #5)',
            'sheet'      => 'Jurnal Perbaikan',
            'cetakTitle' => 'REKAP JURNAL PERBAIKAN SARANA PRASARANA',
            'columns'    => [
                'tanggal' => 'date', 'lokasi_unit' => 'string', 'jenis_pekerjaan' => 'string',
                'kategori_sarana' => 'string', 'uraian_kegiatan' => 'text', 'hasil' => 'text',
                'kebutuhan_sarana' => 'text', 'kendala' => 'text', 'tindak_lanjut' => 'text',
                'estimasi_biaya' => 'decimal', 'realisasi_biaya' => 'decimal',
                'dokumentasi_paths' => 'paths', 'petugas_nama' => 'string', 'status' => 'string',
            ],
            'required'   => ['lokasi_unit', 'uraian_kegiatan'],
            'defaults'   => ['jenis_pekerjaan' => 'perbaikan', 'status' => 'selesai'],
            'cetak'      => [
                ['Tanggal', 'tanggal'], ['Lokasi/Unit', 'lokasi_unit'], ['Jenis Pekerjaan', 'jenis_pekerjaan'],
                ['Kategori Sarana', 'kategori_sarana'], ['Uraian', 'uraian_kegiatan'], ['Hasil', 'hasil'],
                ['Kebutuhan Sarana', 'kebutuhan_sarana'], ['Kendala', 'kendala'], ['Tindak Lanjut', 'tindak_lanjut'],
                ['Estimasi Biaya', 'estimasi_biaya'], ['Realisasi Biaya', 'realisasi_biaya'], ['Status', 'status'],
            ],
            'contoh'     => [
                'tanggal' => '2026-10-01', 'lokasi_unit' => 'Lab Komputer 1', 'jenis_pekerjaan' => 'perbaikan',
                'kategori_sarana' => 'jaringan_it', 'uraian_kegiatan' => 'Ganti kabel LAN rusak',
                'hasil' => 'Koneksi normal kembali', 'kebutuhan_sarana' => '-', 'kendala' => '-',
                'tindak_lanjut' => '-', 'estimasi_biaya' => '150000', 'realisasi_biaya' => '150000',
                'dokumentasi_paths' => '', 'petugas_nama' => 'Budi Santoso', 'status' => 'selesai',
            ],
        ],
        'supervisi' => [
            'table'      => 'teknisi_supervisi',
            'nomor'      => ['nomor_supervisi', 'SPV'],
            'title'      => 'Format Import Lembar Supervisi Teknisi (Skor 1–4)',
            'sheet'      => 'Supervisi',
            'cetakTitle' => 'REKAP SUPERVISI TEKNISI & IT',
            'columns'    => [
                'tanggal_supervisi' => 'date', 'periode_label' => 'string', 'tahun_ajaran' => 'string',
                'semester' => 'string', 'teknisi_ptk_id' => 'string', 'teknisi_nama' => 'string',
                'supervisor_ptk_id' => 'string', 'supervisor_nama' => 'string', 'supervisor_jabatan' => 'string',
                'catatan_umum' => 'text', 'rekomendasi' => 'text', 'status' => 'string',
                'skor_1' => 'skor', 'skor_2' => 'skor', 'skor_3' => 'skor', 'skor_4' => 'skor', 'skor_5' => 'skor',
                'catatan_1' => 'text', 'catatan_2' => 'text', 'catatan_3' => 'text', 'catatan_4' => 'text', 'catatan_5' => 'text',
            ],
            'required'   => ['teknisi_nama', 'supervisor_nama'],
            'defaults'   => ['status' => 'draf'],
            'items'      => true,
            'cetak'      => [
                ['Tanggal', 'tanggal_supervisi'], ['Teknisi', 'teknisi_nama'], ['Supervisor', 'supervisor_nama'],
                ['Jabatan Supervisor', 'supervisor_jabatan'], ['Tahun Ajaran', 'tahun_ajaran'], ['Semester', 'semester'],
                ['Nilai Akhir', 'nilai_akhir'], ['Kualifikasi', 'kualifikasi'],
                ['Catatan Umum', 'catatan_umum'], ['Rekomendasi', 'rekomendasi'], ['Status', 'status'],
            ],
            'contoh'     => [
                'tanggal_supervisi' => '2026-10-01', 'periode_label' => 'Semester Ganjil 2026/2027',
                'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'teknisi_ptk_id' => '',
                'teknisi_nama' => 'Budi Santoso', 'supervisor_ptk_id' => '', 'supervisor_nama' => 'Kepala Sekolah',
                'supervisor_jabatan' => 'Kepala Sekolah', 'catatan_umum' => 'Sangat baik.', 'rekomendasi' => 'Pertahankan.',
                'status' => 'final',
                'skor_1' => '4', 'skor_2' => '3', 'skor_3' => '4', 'skor_4' => '3', 'skor_5' => '4',
                'catatan_1' => 'Lengkap', 'catatan_2' => 'Baik', 'catatan_3' => 'Baik', 'catatan_4' => 'Cukup', 'catatan_5' => 'Baik',
            ],
        ],
    ];

    public function spec(string $modul): array
    {
        if (!isset(self::SPECS[$modul])) {
            throw new \InvalidArgumentException("Modul tidak dikenal: {$modul}");
        }

        return self::SPECS[$modul];
    }

    /** @return array<int, string> */
    public function headers(string $modul): array
    {
        return array_keys($this->userColumns($modul));
    }

    /**
     * Kolom yang diisi otomatis dari akun pengunggah — tidak diminta di berkas.
     *
     * @return array<int, string>
     */
    public const AUTO_OWNER_COLUMNS = ['petugas_nama', 'petugas_ptk_id'];

    /**
     * Kolom modul tanpa identitas petugas (untuk header template impor).
     *
     * @return array<string,string>
     */
    public function userColumns(string $modul): array
    {
        return array_diff_key($this->spec($modul)['columns'], array_flip(self::AUTO_OWNER_COLUMNS));
    }

    public function title(string $modul): string
    {
        return $this->spec($modul)['title'];
    }

    public function cetakTitle(string $modul): string
    {
        return $this->spec($modul)['cetakTitle'];
    }

    /** @return array<int, array{0:string,1:string}> */
    public function cetakFields(string $modul): array
    {
        return $this->spec($modul)['cetak'];
    }

    /** Kolom tanggal pertama pada modul (untuk filter periode cetak). */
    public function dateColumn(string $modul): ?string
    {
        foreach ($this->spec($modul)['columns'] as $col => $type) {
            if ($type === 'date') {
                return $col;
            }
        }

        return null;
    }

    /** Peta tipe kolom modul. @return array<string,string> */
    public function columnTypes(string $modul): array
    {
        return $this->spec($modul)['columns'];
    }

    public function fileName(string $modul): string
    {
        return 'Format_Import_' . str_replace('-', '_', ucwords(str_replace('-', ' ', $modul))) . '.xlsx';
    }

    public function generateTemplateFile(string $modul): string
    {
        $spec = $this->spec($modul);
        $headers = $this->headers($modul);
        $path = storage_path('app/temp_teknisi_' . str_replace('-', '_', $modul) . '_' . uniqid() . '.xlsx');

        // Baris contoh harus terurut sesuai header (indeks numerik) agar eksportir
        // dapat menghitung lebar kolom dengan benar.
        $contoh = array_map(fn ($h) => $spec['contoh'][$h] ?? '', $headers);

        SimpleXlsxExporter::export($path, [
            'title'    => $spec['title'],
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Nomor Dokumen' => 'Boleh dikosongkan — sistem membuat otomatis (' . $spec['nomor'][1] . '/YYYYMM/NNNN)',
                'Tanggal'       => 'Format YYYY-MM-DD; baris tanpa tanggal memakai tanggal impor',
                'Dokumentasi'   => 'Pisahkan beberapa path foto dengan tanda | (pipa)',
                'Daftar Sarana' => 'Satu sarana per baris: Nama | Jumlah | Kondisi',
            ],
        ], $headers, [$contoh], [
            'sheetName' => $spec['sheet'],
        ]);

        return $path;
    }

    /**
     * Impor berkas menjadi baris tabel modul.
     *
     * @return array{imported:int, updated:int}
     */
    public function import(UploadedFile $file, string $modul, array $context): array
    {
        $spec = $this->spec($modul);

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

        $imported = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);

            $payload = $this->buildPayload($n, $spec, $context);
            if ($payload === null) {
                continue;
            }

            if (!empty($spec['items'])) {
                $this->simpanSupervisi($payload, $n, $context);
                $imported++;
                continue;
            }

            [$nomorCol, $prefix] = $spec['nomor'];
            $nomor = trim((string) ($payload[$nomorCol] ?? ''));
            unset($payload[$nomorCol]);

            $existing = null;
            if ($nomor !== '') {
                $existing = DB::table($spec['table'])->where($nomorCol, $nomor)->first();
            }

            if ($existing && !empty($nomor)) {
                DB::table($spec['table'])->where('id', $existing->id)->update($payload + ['updated_at' => now()]);
                $updated++;
            } else {
                $payload[$nomorCol] = $nomor !== '' ? $nomor : $this->generateNomor($spec['table'], $nomorCol, $prefix);
                $payload['created_at'] = now();
                $payload['updated_at'] = now();
                DB::table($spec['table'])->insert($payload);
                $imported++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated];
    }

    /**
     * Ubah satu baris mentah menjadi payload kolom DB (tanpa nomor).
     * Kembalikan null bila seluruh kolom bernilai kosong.
     */
    private function buildPayload(array $n, array $spec, array $context): ?array
    {
        $payload = [];
        $adaIsi = false;

        foreach ($spec['columns'] as $col => $type) {
            if ($type === 'skor' || preg_match('/^catatan_\d$/', $col)) {
                continue; // item supervisi ditangani terpisah
            }
            if (in_array($col, self::AUTO_OWNER_COLUMNS, true)) {
                continue; // diisi otomatis dari akun pengunggah
            }

            $raw = $n[$col] ?? null;
            if (is_string($raw)) {
                $raw = trim($raw);
            }

            $val = $this->coerce($raw, $type);
            if ($val !== null && $val !== '' && $val !== []) {
                $adaIsi = true;
            }

            if ($val === null && isset($spec['defaults'][$col])) {
                $val = $spec['defaults'][$col];
            }

            if ($val === null || $val === '' || $val === []) {
                continue; // biarkan default DB
            }

            $payload[$col] = is_array($val) ? json_encode($val) : $val;
        }

        if (!$adaIsi) {
            return null;
        }

        // Kolom wajib — beri nilai fallback agar insert tidak gagal.
        $fallback = [
            'area' => 'Area Tugas', 'petugas_nama' => $context['nama'] ?? '-', 'ruang_nama' => 'Ruang',
            'nama_rapat' => 'Kegiatan Rapat', 'lokasi_unit' => 'Unit', 'uraian_kegiatan' => '-',
            'jenis_pekerjaan' => 'perbaikan', 'tanggal_kegiatan' => now()->toDateString(),
            'tanggal_rapat' => now()->toDateString(), 'tanggal' => now()->toDateString(),
            'tanggal_supervisi' => now()->toDateString(), 'teknisi_nama' => '-', 'supervisor_nama' => '-',
        ];
        foreach ($spec['required'] as $col) {
            if (empty($payload[$col]) && isset($fallback[$col])) {
                $payload[$col] = $fallback[$col];
            }
        }

        // Jejak akun pengunggah/pembuat baris (untuk semua modul termasuk supervisi).
        $payload['user_id'] = $context['user_id'] ?? null;

        if (empty($spec['items'])) {
            $payload['petugas_ptk_id'] = $context['ptk_id'] ?? null;
            $payload['petugas_nama'] = $payload['petugas_nama'] ?? ($context['nama'] ?? null);
        }

        return $payload;
    }

    /**
     * Simpan lembar supervisi + 5 item indikator.
     */
    private function simpanSupervisi(array $payload, array $n, array $context): void
    {
        $indikator = [
            1 => ['Administrasi & Jadwal Pembagian Area Tugas Kebersihan', "1. Jadwal Area Tugas Kebersihan dan Pengolahan Sampah, Dokumentasi Kegiatan.\n2. Menerima Laporan Siswa Tidak Disiplin dari Tim Keamanan."],
            2 => ['Kebersihan Lingkungan & Ruang Kerja', 'Kebersihan seluruh ruang kerja, teras halaman, jamban, lapangan, gor, aula, dan sarpras.'],
            3 => ['Kebersihan Lingkungan Ruang Rapat', 'Kebersihan Teras, Halaman, Ruang Rapat.'],
            4 => ['Laporan Sarana Kegiatan Rapat', 'Dokumen Rekapitulasi dan Laporan Kelengkapan Sarana Rapat.'],
            5 => ['Pengarsipan & Laporan Perbaikan Sarana Prasarana', 'Jurnal Kegiatan Perbaikan dan Kebutuhan Sarana Prasarana.'],
        ];

        $skor = [];
        for ($i = 1; $i <= 5; $i++) {
            $s = $n['skor_' . $i] ?? null;
            $s = $s !== null && $s !== '' ? (int) $s : null;
            if ($s !== null && ($s < 1 || $s > 4)) {
                $s = null;
            }
            $skor[$i] = $s;
        }

        $terisi = array_filter($skor, fn ($s) => $s !== null);
        $rata = empty($terisi) ? null : round(array_sum($terisi) / count($terisi), 2);
        $kualifikasi = match (true) {
            $rata === null => null,
            $rata >= 3.5 => 'Sangat Baik',
            $rata >= 2.75 => 'Baik',
            $rata >= 2.0 => 'Cukup',
            default => 'Perlu Pembinaan',
        };

        [$nomorCol, $prefix] = ['nomor_supervisi', 'SPV'];
        $nomor = trim((string) ($payload[$nomorCol] ?? ''));
        unset($payload[$nomorCol]);

        DB::transaction(function () use ($payload, $skor, $n, $rata, $kualifikasi, $nomorCol, $prefix, $nomor, $indikator, $context) {
            $existing = $nomor !== '' ? DB::table('teknisi_supervisi')->where($nomorCol, $nomor)->first() : null;

            $data = $payload + [
                'nilai_rata'  => $rata,
                'nilai_akhir' => $rata,
                'kualifikasi' => $kualifikasi,
                'updated_at'  => now(),
            ];

            if ($existing) {
                DB::table('teknisi_supervisi')->where('id', $existing->id)->update($data);
                DB::table('teknisi_supervisi_item')->where('supervisi_id', $existing->id)->delete();
                $id = $existing->id;
            } else {
                $data[$nomorCol] = $nomor !== '' ? $nomor : $this->generateNomor('teknisi_supervisi', $nomorCol, $prefix);
                $data['created_at'] = now();
                $id = DB::table('teknisi_supervisi')->insertGetId($data);
            }

            foreach ($indikator as $no => $def) {
                DB::table('teknisi_supervisi_item')->insert([
                    'supervisi_id'       => $id,
                    'no_indikator'       => $no,
                    'indikator_tupoksi'  => $def[0],
                    'bukti_fisik'        => $def[1],
                    'skor'               => $skor[$no],
                    'catatan_supervisor' => $n['catatan_' . $no] ?? null,
                    'urutan'             => $no,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        });
    }

    private function coerce(mixed $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'date'    => $this->parseTanggal($raw),
            'int'     => is_numeric($raw) ? (int) $raw : null,
            'decimal' => is_numeric($raw) ? (float) $raw : null,
            'paths'   => $this->parsePaths($raw),
            'sarana'  => $this->parseSarana($raw),
            default   => trim((string) $raw) === '' ? null : trim((string) $raw),
        };
    }

    /** @return array<int, string> */
    private function parsePaths(mixed $value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/[|;\r\n]+/', $raw) ?: [];
        $parts = array_map('trim', $parts);

        return array_values(array_filter($parts, fn ($p) => $p !== '' && $p !== '-'));
    }

    /** @return array<int, array{nama:string,jumlah:int,kondisi:string}> */
    private function parseSarana(mixed $value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/[\r\n]+/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line === '-') {
                continue;
            }
            $p = array_map('trim', explode('|', $line));
            if ($p[0] === '') {
                continue;
            }
            $out[] = [
                'nama'    => $p[0],
                'jumlah'  => (int) ($p[1] ?? 1),
                'kondisi' => $p[2] ?? 'baik',
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function normalisasiKunci(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', (string) $k))));
            $out[$key] = is_string($v) ? trim($v) : $v;
        }

        return $out;
    }

    private function generateNomor(string $table, string $column, string $prefix): string
    {
        $periode = now()->format('Ym');
        $like = "{$prefix}/{$periode}/%";
        $last = DB::table($table)->where($column, 'like', $like)->orderByDesc('id')->value($column);

        $urut = 1;
        if ($last) {
            $parts = explode('/', $last);
            $urut = ((int) end($parts)) + 1;
        }

        $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);
        $guard = 0;
        while (DB::table($table)->where($column, $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);
            $guard++;
        }

        return $nomor;
    }

    private function parseTanggal(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

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
