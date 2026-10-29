<?php
// Self-check render komponen toolbar tanggal.
// Jalankan: php tests/toolbar-date.check.php  (dari root proyek)

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$app->instance('request', $request);
$kernel->bootstrap();

$html = view('components.sae.toolbar-date-range', [
    'fromValue' => '2026-10-01',
    'toValue' => '2026-10-31',
])->render();

$checks = [
    'wrapper range' => str_contains($html, 'class="toolbar-filter-range"'),
    'input dari' => str_contains($html, 'name="dari"') && str_contains($html, 'value="2026-10-01"'),
    'input sampai' => str_contains($html, 'name="sampai"') && str_contains($html, 'value="2026-10-31"'),
    'dua kontrol tanggal' => substr_count($html, 'class="toolbar-filter-date"') === 2,
    'aria-label' => substr_count($html, 'aria-label=') >= 2,
];

$fail = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}

exit($fail ? 1 : 0);
