<?php

namespace Tests\Feature;

use App\Services\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Guard jalur unggahan: semua bukti/foto lewat ImageOptimizerService supaya
 * ukurannya diminalisir (WebP untuk foto, PNG bila perlu transparansi).
 */
class ImageOptimizerUploadTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('Ekstensi GD tidak tersedia.');
        }

        $this->dir = sys_get_temp_dir() . '/sae-opt-' . uniqid();
        @mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function service(): ImageOptimizerService
    {
        return app(ImageOptimizerService::class);
    }

    public function test_foto_besar_dikompres_jadi_webp_dan_diperkecil(): void
    {
        $foto = imagecreatetruecolor(3600, 2400);
        for ($i = 0; $i < 150; $i++) {
            imagefilledrectangle(
                $foto,
                random_int(0, 3599),
                random_int(0, 2399),
                random_int(0, 3599),
                random_int(0, 2399),
                imagecolorallocate($foto, random_int(0, 255), random_int(0, 255), random_int(0, 255))
            );
        }
        ob_start();
        imagejpeg($foto, null, 95);
        $jpeg = (string) ob_get_clean();
        imagedestroy($foto);

        $hasil = $this->service()->optimizeAndSaveBinary($jpeg, $this->dir, 'bukti');

        $this->assertSame('webp', $hasil['format']);
        $this->assertLessThanOrEqual(1600, max($hasil['width'], $hasil['height']));
        $this->assertLessThan(strlen($jpeg), $hasil['size'], 'Hasil kompresi harus lebih kecil dari aslinya.');
        $this->assertFileExists($hasil['full_path']);
    }

    public function test_png_transparan_tetap_png_dan_alpha_terjaga(): void
    {
        $png = imagecreatetruecolor(300, 300);
        imagealphablending($png, false);
        imagesavealpha($png, true);
        imagefilledrectangle($png, 0, 0, 299, 299, imagecolorallocatealpha($png, 0, 0, 0, 127));
        imagefilledellipse($png, 150, 150, 200, 200, imagecolorallocatealpha($png, 255, 0, 0, 0));
        ob_start();
        imagepng($png);
        $binary = (string) ob_get_clean();
        imagedestroy($png);

        $hasil = $this->service()->optimizeAndSaveBinary($binary, $this->dir, 'logo');

        $this->assertSame('png', $hasil['format']);

        $kembali = imagecreatefromstring((string) file_get_contents($hasil['full_path']));
        $this->assertGreaterThan(0, (imagecolorat($kembali, 5, 5) >> 24) & 0x7F, 'Piksel transparan harus tetap transparan.');
        imagedestroy($kembali);
    }

    public function test_png_tanpa_alpha_dikonversi_ke_webp(): void
    {
        $flat = imagecreatetruecolor(400, 400);
        imagefilledrectangle($flat, 0, 0, 399, 399, imagecolorallocate($flat, 10, 20, 30));
        ob_start();
        imagepng($flat);
        $binary = (string) ob_get_clean();
        imagedestroy($flat);

        $this->assertSame('webp', $this->service()->optimizeAndSaveBinary($binary, $this->dir, 'qris')['format']);
    }

    public function test_dokumen_pdf_disimpan_apa_adanya(): void
    {
        $pdf = "%PDF-1.4\n" . str_repeat('x', 5000);

        $hasil = $this->service()->optimizeAndSaveImage(
            UploadedFile::fake()->createWithContent('surat.pdf', $pdf),
            $this->dir,
            'surat'
        );

        $this->assertSame('pdf', $hasil['format']);
        $this->assertSame($pdf, file_get_contents($hasil['full_path']));
    }

    public function test_nama_file_aman_dari_path_traversal(): void
    {
        $hasil = $this->service()->optimizeAndSaveImage(
            UploadedFile::fake()->createWithContent('../aneh name!.pdf', 'a'),
            $this->dir,
            '../../lolos ../x'
        );

        $this->assertStringNotContainsString('..', $hasil['filename']);
        $this->assertStringNotContainsString('/', $hasil['filename']);
        $this->assertStringNotContainsString('\\', $hasil['filename']);
    }

    public function test_unggahan_identik_tidak_menumpuk_berkas(): void
    {
        $binary = 'raw-document-bytes';

        $pertama = $this->service()->optimizeAndSaveImage(UploadedFile::fake()->createWithContent('a.txt', $binary), $this->dir, 'dok');
        $kedua = $this->service()->optimizeAndSaveImage(UploadedFile::fake()->createWithContent('b.txt', $binary), $this->dir, 'dok');

        $this->assertSame($pertama['filename'], $kedua['filename']);
        $this->assertCount(1, glob($this->dir . '/dok_*'));
    }

    public function test_ekstensi_eksekutabel_ditolak(): void
    {
        foreach (['evil.php', 'evil.phtml', 'evil.phar', 'evil.exe', 'evil.html', 'evil.svg'] as $nama) {
            try {
                $this->service()->optimizeAndSaveImage(
                    UploadedFile::fake()->createWithContent($nama, 'bukan gambar'),
                    $this->dir,
                    'jahat'
                );
                $this->fail("Berkas {$nama} seharusnya ditolak.");
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        $this->assertCount(0, glob($this->dir . '/*') ?: []);
    }

    public function test_berkas_dokumen_berisi_kode_php_ditolak(): void
    {
        $polyglot = "%PDF-1.4\n1 0 obj\n<?php system(\$_GET['c']); ?>\n%%EOF";

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->optimizeAndSaveImage(
            UploadedFile::fake()->createWithContent('laporan.pdf', $polyglot),
            $this->dir,
            'polyglot'
        );
    }

    public function test_dokumen_tanpa_ekstensi_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()->optimizeAndSaveImage(
            UploadedFile::fake()->createWithContent('tanpa-ekstensi', 'isi'),
            $this->dir,
            'kosong'
        );
    }

    public function test_mime_hasil_kompresi_akurat(): void
    {
        $foto = imagecreatetruecolor(200, 150);
        imagefilledrectangle($foto, 0, 0, 199, 149, imagecolorallocate($foto, 40, 90, 200));
        ob_start();
        imagejpeg($foto);
        $jpeg = (string) ob_get_clean();
        imagedestroy($foto);

        $hasil = $this->service()->optimizeAndSaveBinary($jpeg, $this->dir, 'mime');

        $this->assertSame('image/webp', $hasil['mime']);
    }

    public function test_penjaga_htaccess_dibuat_di_direktori_publik(): void
    {
        $target = public_path('uploads/__guard_test_' . uniqid());

        try {
            $foto = imagecreatetruecolor(60, 60);
            ob_start();
            imagejpeg($foto);
            $jpeg = (string) ob_get_clean();
            imagedestroy($foto);

            $this->service()->optimizeAndSaveBinary($jpeg, $target, 'bukti');

            $this->assertFileExists($target . '/.htaccess');
            $this->assertStringContainsString('Require all denied', (string) file_get_contents($target . '/.htaccess'));
        } finally {
            foreach (glob($target . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($target);
        }
    }
}
