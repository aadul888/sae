<?php

namespace Tests\Unit;

use App\Services\PipService;
use PHPUnit\Framework\TestCase;

class PipDetectJenisSkTest extends TestCase
{
    public function test_detects_sk_pemberian_from_pencairan_columns(): void
    {
        $rows = [
            ['nisn' => '001', 'nama_pd' => 'A', 'nominal' => '450000', 'tanggal_cair' => '2026-01-05', 'virtual_acc' => '123'],
            ['nisn' => '002', 'nama_pd' => 'B', 'nominal' => '750000', 'tanggal_cair' => '2026-01-05', 'virtual_acc' => '456'],
        ];

        $this->assertSame('pemberian', PipService::detectJenisSk($rows));
    }

    public function test_detects_sk_nominasi_from_aktivasi_rekening_columns(): void
    {
        $rows = [
            ['nisn' => '001', 'nama_pd' => 'A', 'tanggal_aktivasi' => '2026-01-05', 'status_cair' => 'Sudah Aktivasi Rekening'],
            ['nisn' => '002', 'nama_pd' => 'B', 'tanggal_aktivasi' => '', 'status_cair' => 'Belum Aktivasi Rekening'],
        ];

        $this->assertSame('nominasi', PipService::detectJenisSk($rows));
    }

    public function test_returns_null_when_ambiguous(): void
    {
        $this->assertNull(PipService::detectJenisSk([
            ['nisn' => '001', 'nama_pd' => 'A'],
        ]));
    }

    public function test_detects_using_spaced_sipintar_headers(): void
    {
        $pemberian = [
            ['NISN' => '001', 'Nama PD' => 'A', 'Tanggal Cair' => '2026-01-05', 'Virtual Acc' => '123'],
        ];
        $nominasi = [
            ['NISN' => '001', 'Nama PD' => 'A', 'Tanggal Aktivasi Rekening' => '2026-01-05'],
        ];

        $this->assertSame('pemberian', PipService::detectJenisSk($pemberian));
        $this->assertSame('nominasi', PipService::detectJenisSk($nominasi));
    }

    public function test_guard_rejects_nominasi_file_when_pemberian_selected(): void
    {
        $rows = [
            ['nisn' => '001', 'nama_pd' => 'A', 'tanggal_aktivasi' => '2026-01-05', 'status_cair' => 'Sudah Aktivasi'],
        ];

        $this->assertNotNull(PipService::jenisSkKolomError($rows, 'pemberian'));
        $this->assertNull(PipService::jenisSkKolomError($rows, 'nominasi'));
    }

    public function test_guard_rejects_pemberian_file_when_nominasi_selected(): void
    {
        $rows = [
            ['nisn' => '001', 'nama_pd' => 'A', 'nominal' => '450000', 'tanggal_cair' => '2026-01-05'],
        ];

        $this->assertNotNull(PipService::jenisSkKolomError($rows, 'nominasi'));
        $this->assertNull(PipService::jenisSkKolomError($rows, 'pemberian'));
    }

    public function test_guard_rejects_nominasi_file_with_generic_columns_if_status_indicates_aktivasi(): void
    {
        // Berkas tanpa header eksplisit tanggal_aktivasi tapi status_cair = Belum Aktivasi Rekening
        $rows = [
            ['nisn' => '001', 'nama_pd' => 'A', 'status_cair' => 'Belum Aktivasi Rekening', 'nominal' => '450000'],
        ];

        $this->assertNotNull(PipService::jenisSkKolomError($rows, 'pemberian'));
        $this->assertSame('nominasi', PipService::detectJenisSk($rows));
    }

    public function test_normalize_import_rows_maps_sipintar_headers_to_canonical_keys(): void
    {
        $rows = PipService::normalizeImportRows([
            ['Nama PD' => 'A', 'Tanggal Cair' => '2026-01-05', 'Virtual Acc' => '123', 'Nama Rekening' => 'A'],
        ]);

        $this->assertSame('A', $rows[0]['nama_pd']);
        $this->assertSame('2026-01-05', $rows[0]['tanggal_cair']);
        $this->assertSame('123', $rows[0]['virtual_acc']);
        $this->assertSame('A', $rows[0]['nama_rekening']);
    }
}
