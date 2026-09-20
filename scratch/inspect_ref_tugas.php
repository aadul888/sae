<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('ref_tugas_tambahan')->get();
foreach ($rows as $r) {
    echo "ID: {$r->id} | Kode: {$r->kode} | Nama: {$r->nama} | Kelompok: {$r->kelompok} | Bidang: {$r->bidang} | Granted: {$r->granted_permissions}\n";
}
