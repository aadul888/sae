<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "pembelajaran cols: " . implode(', ', Illuminate\Support\Facades\Schema::getColumnListing('pembelajaran')) . PHP_EOL;
    echo "rows=" . DB::table('pembelajaran')->count() . PHP_EOL;
    echo "with mapel=" . DB::table('pembelajaran')->whereNotNull('nama_mata_pelajaran')->where('nama_mata_pelajaran', '<>', '')->count() . PHP_EOL;
    echo "jadwal cols: " . implode(', ', Illuminate\Support\Facades\Schema::getColumnListing('laboran_jadwal_penggunaan')) . PHP_EOL;
    $staff = (new App\Http\Controllers\LaboranJadwalController())->guruMapelList(request());
    echo "api: " . substr($staff->getContent(), 0, 500) . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERR ' . $e->getMessage() . PHP_EOL;
}
