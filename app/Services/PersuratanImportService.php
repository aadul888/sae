<?php

namespace App\Services;

use App\Models\Persuratan;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Import & template Excel untuk Surat Masuk dan Surat Keluar.
 *
 * Satu layanan dipakai kedua jenis (parameter $jenis: 'masuk'|'keluar')
 * agar tidak ada duplikasi logika baca/validasi/simpan.
 */
class PersuratanImportService
{
    public const HEADERS_MASUK = [
        'nomor_surat', 'pengirim_asal', 'perihal', 'tanggal_surat',
        'tanggal_diterima', 'status', 'keterangan',
    ];

    public const HEADERS_KELUAR = [
        'nomor_surat', 'kode_indeks', 'tujuan_penerima', 'perihal',
        'tanggal_surat', 'status', 'keterangan',
    ];

    public function headers(string $jenis): array
    {
        return $jenis === 'keluar' ? self::HEADERS_KELUAR : self::HEADERS_MASUK;
    }

    public function metaTemplate(string $jenis): array
    {
        return [
            'title'    => $jenis === 'keluar' ? 'Format Import Surat Keluar' : 'Format Import Surat Masuk',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Diunduh ' . now()->translatedFormat('d F Y H:i'),
            'fields'   => $jenis === 'keluar'
                ? [
                    'Nomor Surat'   => 'Boleh dikosongkan — sistem membuat nomor otomatis dari kode indeks',
                    'Kode Indeks'   => 'Contoh: KPG.11.01, HM.01.02 (lihat Pengaturan Persuratan)',
                    'Tanggal'       => 'Format YYYY-MM-DD; dikosongkan berarti hari ini',
                    'Status'        => 'draf | selesai | diarsipkan',
                ]
                : [
                    'Nomor Surat'     => 'Nomor resmi dari instansi pengirim (wajib)',
                    'Pengirim Asal'   => 'Nama instansi pengirim',
                    'Tanggal'         => 'Format YYYY-MM-DD; dikosongkan berarti hari ini',
                    'Status'          => 'menunggu_disposisi | diproses | selesai | diarsipkan',
                ],
        ];
    }

    public function contohBaris(string $jenis): array
    {
        return $jenis === 'keluar'
            ? [
                ['', 'KPG.11.01', 'Dinas Pendidikan Prov. Jawa Barat', 'Permohonan Narasumber Workshop Digitalisasi', date('Y-m-d'), 'selesai', ''],
                ['019/002/SMK/2026', 'HM.01.02', 'Kepala BBGP Provinsi', 'Undangan Rapat Koordinasi Kurikulum', date('Y-m-d'), 'selesai', ''],
            ]
            : [
                ['421/100/Disdik/2026', 'Dinas Pendidikan Prov. Jawa Barat', 'Undangan Rapat Koordinasi Kurikulum Merdeka', date('Y-m-d'), date('Y-m-d'), 'menunggu_disposisi', 'Disposisikan ke Waka Kurikulum'],
                ['005/200/SMK/2026', 'Kemdikbudristek', 'Sosialisasi Program Sekolah Penggerak', date('Y-m-d'), date('Y-m-d'), 'diproses', ''],
            ];
    }

    public function generateTemplateFile(string $jenis): string
    {
        $path = storage_path('app/temp_import_persuratan_' . $jenis . '_' . uniqid() . '.xlsx');

        SimpleXlsxExporter::export(
            $path,
            $this->metaTemplate($jenis),
            $this->headers($jenis),
            $this->contohBaris($jenis),
            ['sheetName' => 'Surat ' . ucfirst($jenis)]
        );

        return $path;
    }

    /**
     * Proses import berkas Excel/CSV menjadi record persuratan.
     *
     * @return array{imported:int, updated:int, skipped:int}
     *
     * @throws \RuntimeException bila berkas tidak valid/kosong
     */
    public function import(UploadedFile $file, string $jenis, string $userName): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            throw new \RuntimeException('Format berkas tidak didukung. Gunakan Excel (.xlsx/.xls) atau CSV.');
        }

        try {
            $rows = SimpleXlsxReader::readFile($file->getRealPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal membaca berkas: ' . $e->getMessage());
        }

        if (empty($rows)) {
            throw new \RuntimeException('Berkas kosong atau baris data tidak terdeteksi.');
        }

        $isKeluar = $jenis === 'keluar';
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $n = $this->normalisasiKunci($row);

            $perihal = trim((string) ($n['perihal'] ?? ''));
            if ($perihal === '') {
                $skipped++;
                continue;
            }

            $nomor = trim((string) ($n['nomor_surat'] ?? ''));
            $kodeIndeks = trim((string) ($n['kode_indeks'] ?? ''));

            if ($isKeluar) {
                if ($kodeIndeks === '') {
                    $kodeIndeks = 'KPG.11.01';
                }
                if ($nomor === '') {
                    $nomor = PersuratanHddService::generateNomorSuratKeluar($kodeIndeks);
                }
            } elseif ($nomor === '') {
                $skipped++;
                continue;
            }

            $tanggal = $this->tanggal($n['tanggal_surat'] ?? null) ?: date('Y-m-d');
            $status = trim((string) ($n['status'] ?? ''));
            if (!array_key_exists($status, Persuratan::STATUS_MAP)) {
                $status = $isKeluar ? 'selesai' : 'menunggu_disposisi';
            }

            $data = [
                'kode_indeks'     => $isKeluar ? $kodeIndeks : null,
                'perihal'         => $perihal,
                'pengirim_asal'   => $isKeluar ? 'SMK SAE' : (trim((string) ($n['pengirim_asal'] ?? '')) ?: '-'),
                'tujuan_penerima' => $isKeluar
                    ? (trim((string) ($n['tujuan_penerima'] ?? '')) ?: '-')
                    : 'SMK SAE',
                'tanggal_surat'   => $tanggal,
                'status'          => $status,
                'keterangan'      => trim((string) ($n['keterangan'] ?? '')) ?: null,
            ];

            if ($isKeluar) {
                $data['tanggal_diterima'] = null;
            } else {
                $data['tanggal_diterima'] = $this->tanggal($n['tanggal_diterima'] ?? null) ?: $tanggal;
            }

            $existing = Persuratan::where('nomor_surat', $nomor)->where('jenis_surat', $jenis)->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                Persuratan::create(array_merge($data, [
                    'nomor_surat' => $nomor,
                    'jenis_surat' => $jenis,
                    'created_by'  => $userName,
                ]));

                if ($isKeluar && trim((string) ($n['nomor_surat'] ?? '')) === '') {
                    PersuratanHddService::incrementNomorCounter('keluar');
                }

                $imported++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped];
    }

    private function normalisasiKunci(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            if (str_starts_with((string) $k, '__col_')) {
                continue;
            }
            $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', (string) $k))));
            $out[$key] = is_string($v) ? trim($v) : $v;
        }

        return $out;
    }

    private function tanggal($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
