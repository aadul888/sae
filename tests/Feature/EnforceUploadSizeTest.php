<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceUploadSize;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Pagar ukuran unggahan global (EnforceUploadSize): berkas di atas batas
 * `upload.absolute_max_kb` ditolak 413 sebelum menyentuh controller; berkas
 * wajar dan field yang dikecualikan tetap lolos.
 */
class EnforceUploadSizeTest extends TestCase
{
    private function runMiddleware(array $files, string $field = 'file'): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create('/uji-unggah', 'POST', [], [], $files);
        $request->headers->set('Accept', 'application/json');

        return (new EnforceUploadSize())->handle($request, fn () => response('lolos', 200));
    }

    public function test_berkas_di_bawah_batas_lolos(): void
    {
        $res = $this->runMiddleware([
            'file' => UploadedFile::fake()->create('kecil.pdf', 1024, 'application/pdf'), // 1 MB
        ]);

        $this->assertSame(200, $res->getStatusCode());
    }

    public function test_berkas_di_atas_batas_ditolak_413(): void
    {
        $batasKb = (int) config('upload.absolute_max_kb');
        $res = $this->runMiddleware([
            'file' => UploadedFile::fake()->create('besar.pdf', $batasKb + 1024, 'application/pdf'),
        ]);

        $this->assertSame(413, $res->getStatusCode());
        $this->assertStringContainsString('terlalu besar', (string) $res->getContent());
    }

    public function test_field_dikecualikan_tidak_dibatasi(): void
    {
        $batasKb = (int) config('upload.absolute_max_kb');
        $res = $this->runMiddleware([
            'restore_file' => UploadedFile::fake()->create('backup.zip', $batasKb * 3, 'application/zip'),
        ], 'restore_file');

        $this->assertSame(200, $res->getStatusCode());
    }
}
