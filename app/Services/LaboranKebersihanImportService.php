<?php

namespace App\Services;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import & template Excel untuk modul Kebersihan Lingkungan Produktif Jurusan.
 *
 * Satu baris berkas = satu catatan kegiatan kebersihan (Tupoksi #9).
 * Mengadopsi arsitektur yang sama dengan LaboranInventarisImportService
 * agar controller tetap fokus pada HTTP dan logika parsing dapat diuji terpisah.
 */
class LaboranKebersihanImportService
{
    public const JENIS = ['harian', 'mingguan', 'bulanan', 'insidental'];

    public const KONDISI = ['bersih', 'cukup', 'kotor', 'perlu_tindakan'];

    public const STATUS = ['draf', 'selesai'];

    /** Kolom header template, berurutan. */
    public const HEADERS = [
        'nomor_kegiatan',
        'tanggal_kegiatan',
        'area',
        'ruang_lab_nama',
        'jenis_kegiatan',
        'uraian_kegiatan',
        'peralatan_digunakan',
        'bahan_digunakan',
        'jumlah_petugas',
        'nama_petugas',
        'kondisi_akhir',
        'temuan',
        'tindak_lanjut',
        'dokumentasi_paths',
        'status',
    ];

    /**
     * Baris contoh pada template.
     */
    public function contohBaris(): array
    {
        return [
            [
                '', '2026-10-01', 'Lahan Praktik ATPH', 'Lahan Praktik ATPH', 'harian',
                'Membersihkan gulma di bedengan praktik',
                'Cangkul, sabit, gerobak', '-',
                '5', 'Budi, Andi, Rina, Sari, Dedi', 'bersih', '-', '-',
                'storage/laboran/kebersihan/foto1.jpg|storage/laboran/kebersihan/foto2.jpg',
                'selesai',
            ],
            [
                '', '2026-10-03', 'Bengkel Otomotif', 'Bengkel Otomotif', 'mingguan',
                'Menyapu lantai, merapikan peralatan, dan membersihkan ceceran oli',
                'Sapu, pel, sekop, majun', 'Pembersih lantai, sabun',
                '4', 'Joko, Rudi, Tono, Bayu', 'cukup',
                'Ceceran oli di area pit masih tersisa', 'Dijadwalkan pembersihan ulang pekan depan',
                'storage/laboran/kebersihan/bengkel-1.jpg',
                'selesai',
            ],
        ];
    }

    public function metaTemplate(): array
    {
        return [
            'title'    => 'Format Import Kegiatan Kebersihan Lingkungan Produktif Jurusan',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => [
                'Nomor Kegiatan' => 'Boleh dikosongkan — sistem membuat otomatis (KBR/YYYYMM/NNNN)',
                'Tanggal'        => 'Format YYYY-MM-DD; baris tanpa tanggal memakai tanggal impor',
                'Jenis Kegiatan' => implode(', ', self::JENIS),
                'Kondisi Akhir'  => implode(', ', self::KONDISI),
                'Dokumentasi'    => 'Pisahkan beberapa path foto dengan tanda | (pipa)',
            ],
        ];
    }

    /**
     * Hasilkan berkas template .xlsx dan kembalikan path sementaranya.
     */
    public function generateTemplateFile(): string
    {
        $path = storage_path('app/temp_template_kebersihan_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export($path, $this->metaTemplate(), self::HEADERS, $this->contohBaris(), [
            'sheetName' => 'Kebersihan Jurusan',
        ]);

        return $path;
    }

    /**
     * Proses berkas unggahan menjadi catatan kegiatan kebersihan.
     *
     * @return array{imported:int, updated:int}
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

        $imported = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $data = $this->normalisasiBaris($row, $context);
            if ($data === null) {
                continue;
            }

            $existing = null;
            if (!empty($data['nomor_kegiatan'])) {
                $existing = DB::table('laboran_kebersihan')
                    ->where('nomor_kegiatan', $data['nomor_kegiatan'])
                    ->first();

                if ($existing && !LaboranScope::allows($existing->jurusan_id ?? null, $context)) {
                    continue;
                }
            }

            if ($existing) {
                $this->perbarui($existing, $data);
                $updated++;
            } else {
                $this->simpanBaru($data, $jurusanId, $context);
                $imported++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated];
    }

    /**
     * Ubah satu baris mentah menjadi payload siap simpan.
     * Kembalikan null bila baris tidak memiliki area (baris kosong).
     */
    protected function normalisasiBaris(array $row, array $context): ?array
    {
        $n = $this->normalisasiKunci($row);

        $area = trim((string) ($n['area'] ?? ($n['lokasi'] ?? ($n['area_lokasi'] ?? ''))));
        $uraian = trim((string) ($n['uraian_kegiatan'] ?? ($n['uraian'] ?? ($n['kegiatan'] ?? ''))));

        if ($area === '' && $uraian === '') {
            return null;
        }
        if ($area === '') {
            $area = 'Area Praktik Jurusan';
        }

        $nomor = strtoupper(trim((string) ($n['nomor_kegiatan'] ?? ($n['nomor'] ?? ''))));

        $tanggal = $this->parseTanggal($n['tanggal_kegiatan'] ?? ($n['tanggal'] ?? ($n['tgl_kegiatan'] ?? '')));
        if ($tanggal === null) {
            $tanggal = now()->toDateString();
        }

        $jenis = strtolower(trim((string) ($n['jenis_kegiatan'] ?? ($n['jenis'] ?? 'harian'))));
        if (!in_array($jenis, self::JENIS, true)) {
            $jenis = 'harian';
        }

        $kondisi = strtolower(trim((string) ($n['kondisi_akhir'] ?? ($n['kondisi'] ?? 'bersih'))));
        if (!in_array($kondisi, self::KONDISI, true)) {
            $kondisi = 'bersih';
        }

        $status = strtolower(trim((string) ($n['status'] ?? 'selesai')));
        if (!in_array($status, self::STATUS, true)) {
            $status = 'selesai';
        }

        $jumlahPetugas = (int) ($n['jumlah_petugas'] ?? ($n['petugas_jml'] ?? 1));
        if ($jumlahPetugas <= 0) {
            $jumlahPetugas = 1;
        }

        $dokumentasi = $this->parseDokumentasi($n['dokumentasi_paths'] ?? ($n['dokumentasi'] ?? ($n['foto'] ?? '')));

        return [
            'nomor_kegiatan'      => $nomor,
            'tanggal_kegiatan'    => $tanggal,
            'area'                => $area,
            'ruang_lab_nama'      => trim((string) ($n['ruang_lab_nama'] ?? ($n['ruang'] ?? ($n['lab'] ?? '')))),
            'jenis_kegiatan'      => $jenis,
            'uraian_kegiatan'     => $uraian !== '' ? $uraian : null,
            'peralatan_digunakan' => trim((string) ($n['peralatan_digunakan'] ?? ($n['peralatan'] ?? ''))) ?: null,
            'bahan_digunakan'     => trim((string) ($n['bahan_digunakan'] ?? ($n['bahan'] ?? ''))) ?: null,
            'jumlah_petugas'      => $jumlahPetugas,
            'nama_petugas'        => trim((string) ($n['nama_petugas'] ?? ($n['petugas'] ?? ''))) ?: null,
            'kondisi_akhir'       => $kondisi,
            'temuan'              => trim((string) ($n['temuan'] ?? '')) ?: null,
            'tindak_lanjut'       => trim((string) ($n['tindak_lanjut'] ?? '')) ?: null,
            'dokumentasi_paths'   => !empty($dokumentasi) ? json_encode($dokumentasi) : null,
            'status'              => $status,
            'petugas_ptk_id'      => $context['ptk_id'] ?? null,
            'petugas_nama'        => $context['nama'] ?? null,
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
     * Pecah daftar path dokumentasi (dipisah pipa, titik koma, atau baris baru).
     *
     * @return array<int, string>
     */
    protected function parseDokumentasi(mixed $value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[|;\r\n]+/', $raw) ?: [];
        $parts = array_map('trim', $parts);

        return array_values(array_filter($parts, fn($p) => $p !== '' && strtolower($p) !== '-'));
    }

    protected function perbarui(object $existing, array $d): void
    {
        DB::table('laboran_kebersihan')->where('id', $existing->id)->update([
            'tanggal_kegiatan'    => $d['tanggal_kegiatan'],
            'area'                => $d['area'],
            'ruang_lab_nama'      => $d['ruang_lab_nama'] !== '' ? $d['ruang_lab_nama'] : $existing->ruang_lab_nama,
            'jenis_kegiatan'      => $d['jenis_kegiatan'],
            'uraian_kegiatan'     => $d['uraian_kegiatan'] ?? $existing->uraian_kegiatan,
            'peralatan_digunakan' => $d['peralatan_digunakan'] ?? $existing->peralatan_digunakan,
            'bahan_digunakan'     => $d['bahan_digunakan'] ?? $existing->bahan_digunakan,
            'jumlah_petugas'      => $d['jumlah_petugas'],
            'nama_petugas'        => $d['nama_petugas'] ?? $existing->nama_petugas,
            'kondisi_akhir'       => $d['kondisi_akhir'],
            'temuan'              => $d['temuan'] ?? $existing->temuan,
            'tindak_lanjut'       => $d['tindak_lanjut'] ?? $existing->tindak_lanjut,
            'dokumentasi_paths'   => $d['dokumentasi_paths'] ?? $existing->dokumentasi_paths,
            'status'              => $d['status'],
            'updated_at'          => now(),
        ]);
    }

    protected function simpanBaru(array $d, ?string $jurusanId, array $context): void
    {
        $nomor = $d['nomor_kegiatan'] !== '' ? $d['nomor_kegiatan'] : $this->generateNomorKegiatan();

        DB::table('laboran_kebersihan')->insert([
            'jurusan_id'          => $jurusanId,
            'nomor_kegiatan'      => $nomor,
            'tanggal_kegiatan'    => $d['tanggal_kegiatan'],
            'area'                => $d['area'],
            'ruang_lab_nama'      => $d['ruang_lab_nama'] !== '' ? $d['ruang_lab_nama'] : null,
            'jenis_kegiatan'      => $d['jenis_kegiatan'],
            'uraian_kegiatan'     => $d['uraian_kegiatan'],
            'peralatan_digunakan' => $d['peralatan_digunakan'],
            'bahan_digunakan'     => $d['bahan_digunakan'],
            'jumlah_petugas'      => $d['jumlah_petugas'],
            'nama_petugas'        => $d['nama_petugas'],
            'kondisi_akhir'       => $d['kondisi_akhir'],
            'temuan'              => $d['temuan'],
            'tindak_lanjut'       => $d['tindak_lanjut'],
            'dokumentasi_paths'   => $d['dokumentasi_paths'],
            'petugas_ptk_id'      => $d['petugas_ptk_id'],
            'petugas_nama'        => $d['petugas_nama'],
            'status'              => $d['status'],
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * Nomor dokumen berurutan: KBR/YYYYMM/NNNN (unik).
     */
    public function generateNomorKegiatan(): string
    {
        $periode = now()->format('Ym');
        $like = "KBR/{$periode}/%";

        $last = DB::table('laboran_kebersihan')
            ->where('nomor_kegiatan', 'like', $like)
            ->orderByDesc('id')
            ->value('nomor_kegiatan');

        $urut = 1;
        if ($last) {
            $parts = explode('/', $last);
            $urut = ((int) end($parts)) + 1;
        }

        $nomor = sprintf('KBR/%s/%04d', $periode, $urut);

        $guard = 0;
        while (DB::table('laboran_kebersihan')->where('nomor_kegiatan', $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('KBR/%s/%04d', $periode, $urut);
            $guard++;
        }

        return $nomor;
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
