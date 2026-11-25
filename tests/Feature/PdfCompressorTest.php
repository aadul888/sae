<?php

namespace Tests\Feature;

use App\Services\PdfCompressorService;
use Tests\TestCase;

/**
 * Kompresi PDF via Ghostscript harus "aman-gagal": bila binari tidak ada,
 * layanan mengembalikan null (bukan error) sehingga unggahan tetap tersimpan.
 */
class PdfCompressorTest extends TestCase
{
    public function test_tidak_error_saat_ghostscript_tidak_tersedia(): void
    {
        $service = app(PdfCompressorService::class);

        $tmp = tempnam(sys_get_temp_dir(), 'sae_pdf_') . '.pdf';
        file_put_contents($tmp, "%PDF-1.4\n% uji\n%%EOF");

        try {
            $result = $service->compress($tmp);

            if ($service->isAvailable()) {
                // Ada Ghostscript: boleh mengembalikan ukuran int atau null
                // (mis. isi terlalu kecil untuk untung), tetapi berkas tetap ada.
                $this->assertTrue($result === null || is_int($result));
            } else {
                $this->assertNull($result, 'Tanpa Ghostscript, compress() harus null.');
            }
            $this->assertFileExists($tmp, 'Berkas asli tidak boleh hilang.');
        } finally {
            @unlink($tmp);
        }
    }

    public function test_berkas_bukan_pdf_tidak_diproses(): void
    {
        $service = app(PdfCompressorService::class);

        $this->assertNull($service->compress(sys_get_temp_dir() . '/tidak-ada-9999.pdf'));
    }
}
