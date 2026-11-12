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
}
