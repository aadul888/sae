<?php

namespace Tests\Unit;

use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SimpleXlsxExporterTest extends TestCase
{
    public function test_export_generates_valid_xlsx_with_default_row_height_and_bold_styles(): void
    {
        $tempPath = sys_get_temp_dir() . '/test_sae_export_' . uniqid() . '.xlsx';

        $meta = [
            'title'    => 'DATA REKAPITULASI USULAN PIP',
            'subtitle' => 'SMK NEGERI 1 PAGELARAN — 2026',
            'fields'   => [
                'Tahun Anggaran' => '2026',
                'Kelas'          => 'XII RPL 1',
            ],
        ];
        $headers = ['No', 'NISN', 'Nama Peserta Didik', 'Kelas'];
        $rows = [
            [1, '0081234567', 'Ahmad Dahlan', 'XII RPL 1'],
            [2, '0087654321', 'Budi Santoso', 'XII RPL 1'],
        ];

        $exported = SimpleXlsxExporter::export($tempPath, $meta, $headers, $rows);
        $this->assertTrue($exported);
        $this->assertFileExists($tempPath);

        // Validasi XML OpenXML internal
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempPath));

        $stylesXml = $zip->getFromName('xl/styles.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        // 1. Validasi styles.xml well-formed
        libxml_use_internal_errors(true);
        $stylesDoc = simplexml_load_string($stylesXml);
        $this->assertNotFalse($stylesDoc, 'xl/styles.xml harus merupakan XML yang valid.');

        // 2. Validasi font bold pada Judul dan Header
        $fonts = $stylesDoc->fonts->font;
        $this->assertTrue(isset($fonts[1]->b), 'Font judul (index 1) harus berhuruf tebal (bold).');
        $this->assertTrue(isset($fonts[4]->b), 'Font table header (index 4) harus berhuruf tebal (bold).');

        // 3. Validasi sheet1.xml well-formed dan tinggi baris default
        $sheetDoc = simplexml_load_string($sheetXml);
        $this->assertNotFalse($sheetDoc, 'xl/worksheets/sheet1.xml harus merupakan XML yang valid.');
        $this->assertTrue(isset($sheetDoc->sheetFormatPr), 'sheetFormatPr harus ada.');
        $this->assertEquals('15', (string) $sheetDoc->sheetFormatPr['defaultRowHeight']);

        // Pastikan tidak ada baris yang memaksa customHeight
        foreach ($sheetDoc->sheetData->row as $row) {
            $this->assertNull($row['customHeight'], 'Tinggi baris harus default tanpa customHeight.');
            $this->assertNull($row['ht'], 'Tinggi baris tidak boleh di-hardcode dengan ht.');
        }

        // 4. Pastikan berkas dapat dibaca kembali
        $readData = SimpleXlsxReader::readFile($tempPath);
        $this->assertCount(2, $readData);
        $this->assertEquals('Ahmad Dahlan', $readData[0]['nama peserta didik']);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }
}
