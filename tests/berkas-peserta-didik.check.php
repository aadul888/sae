<?php
// Self-check: unggah & pratinjau berkas peserta didik tidak boleh 500.
// Jalankan: php tests/berkas-peserta-didik.check.php  (dari root proyek)

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\BerkasPesertaDidikController;
use App\Models\PesertaDidikBerkas;
use App\Services\ImageOptimizerService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

session(['user' => ['role' => 'admin', 'name' => 'Check', 'pengguna_id' => 'chk']]);

$checks = [];
$cleanup = [];
$pdId = 'check-' . bin2hex(random_bytes(4));

// --- 1. Pratinjau record tanpa berkas (file_path kosong) harus 404, bukan 500 ---
$kosong = PesertaDidikBerkas::create([
    'peserta_didik_id' => $pdId,
    'jenis_berkas' => 'ktp_orang_tua',
    'nama_berkas' => 'KTP',
    'file_path' => '',
    'file_name' => '(Verifikasi Fisik / Manual)',
    'file_size' => 0,
    'mime_type' => 'application/pdf',
    'status' => 'valid',
]);
$cleanup[] = $kosong;

try {
    app(BerkasPesertaDidikController::class)->preview($kosong->id);
    $checks['pratinjau file_path kosong -> 404'] = false;
} catch (HttpExceptionInterface $e) {
    $checks['pratinjau file_path kosong -> 404'] = ($e->getStatusCode() === 404);
} catch (\Throwable $e) {
    $checks['pratinjau file_path kosong -> 404'] = false;
}

// --- 2. Pratinjau berkas nyata harus mengembalikan respons PDF (bukan 500) ---
$rel = 'berkas_siswa/' . $pdId . '/uji.pdf';
$abs = storage_path('app/public/' . $rel);
@mkdir(dirname($abs), 0777, true);
file_put_contents($abs, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

$ada = PesertaDidikBerkas::create([
    'peserta_didik_id' => $pdId,
    'jenis_berkas' => 'kartu_keluarga',
    'nama_berkas' => 'KK',
    'file_path' => $rel,
    'file_name' => 'KTP "Wali" – é.pdf', // karakter kutip + non-ASCII
    'file_size' => filesize($abs),
    'mime_type' => 'application/pdf',
    'status' => 'menunggu',
]);
$cleanup[] = $ada;

try {
    $resp = app(BerkasPesertaDidikController::class)->preview($ada->id);
    $resp->prepare(Request::create('/'));
    $checks['pratinjau berkas nyata -> 200 PDF'] = ($resp->getStatusCode() === 200);
} catch (\Throwable $e) {
    $checks['pratinjau berkas nyata -> 200 PDF'] = false;
}

// --- 3. Optimizer tetap menolak berkas berbahaya (guard tidak dilonggarkan) ---
$svc = app(ImageOptimizerService::class);
$tmpDir = sys_get_temp_dir() . '/sae-chk-' . uniqid();
@mkdir($tmpDir, 0777, true);

$polyglot = "%PDF-1.4\n1 0 obj\n<?php phpinfo(); ?>\n%%EOF";
try {
    $svc->optimizeAndSaveImage(
        Illuminate\Http\UploadedFile::fake()->createWithContent('x.pdf', $polyglot),
        $tmpDir,
        'polyglot'
    );
    $checks['optimizer tolak PDF berkode'] = false;
} catch (\InvalidArgumentException $e) {
    $checks['optimizer tolak PDF berkode'] = true;
}

foreach (glob($tmpDir . '/*') ?: [] as $f) {
    @unlink($f);
}
@rmdir($tmpDir);

// --- 4. Controller membungkus pemrosesan berkas dengan catch (bukan 500) ---
$src = file_get_contents(__DIR__ . '/../app/Http/Controllers/BerkasPesertaDidikController.php');
$checks['upload dibungkus try/catch'] = str_contains($src, 'catch (\\Throwable $e)')
    && str_contains($src, "with('error', 'Berkas gagal diunggah:");
$checks['preview cek file_path kosong'] = str_contains($src, 'empty($berkas->file_path)');

// --- 5. Berkas pribadi siswa tidak boleh disajikan lewat URL /storage statis ---
$checks['nama berkas unggahan acak (opaque)'] = str_contains($src, 'Str::random(24)');

try {
    app(\App\Http\Controllers\StorageFallbackController::class)->show($rel);
    $checks['fallback storage tolak berkas_siswa -> 404'] = false;
} catch (HttpExceptionInterface $e) {
    $checks['fallback storage tolak berkas_siswa -> 404'] = ($e->getStatusCode() === 404);
} catch (\Throwable $e) {
    $checks['fallback storage tolak berkas_siswa -> 404'] = false;
}

foreach ($cleanup as $row) {
    $row->delete();
}
@unlink($abs);
@rmdir(dirname($abs));

$fail = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}

exit($fail ? 1 : 0);
