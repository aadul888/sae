<?php
// Self-check: grup Sarpras & Aset harus terbuka di semua halaman turunannya.
// Jalankan: php tests/sidebar-sarpras-active.check.php  (dari root proyek)

$root = dirname(__DIR__);
$files = [
    $root . '/resources/views/partials/dash-sidebar.blade.php',
    $root . '/resources/views/partials/dash-sidebar-sarpras.blade.php',
];

// Kondisi aktif grup Sarpras (harus sejalan dengan $isSarprasActive di sidebar)
$patterns = [
    'dashboard.tendik',
    'dashboard.sarpras.*',
    'dashboard.laboran.bhp*',
    'dashboard.teknisi.work-order*',
    'dashboard.penjaga.kebersihan*',
];

$match = function (string $pattern, string $name): bool {
    $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
    return (bool) preg_match($regex, $name);
};

$links = [];
foreach ($files as $file) {
    $src = file_get_contents($file);
    preg_match_all("/route\('(dashboard\.[a-z0-9._\-]+)'/i", $src, $m);
    foreach ($m[1] as $name) {
        $links[$name] = true;
    }
}
$links = array_keys($links);

$missing = [];
foreach ($links as $name) {
    // Hanya nilai yang relevan dengan grup Sarpras
    if (!str_starts_with($name, 'dashboard.sarpras.') &&
        !in_array($name, ['dashboard.laboran.bhp.index', 'dashboard.teknisi.work-order', 'dashboard.penjaga.kebersihan.index'], true)) {
        continue;
    }
    $ok = false;
    foreach ($patterns as $p) {
        if ($match($p, $name)) {
            $ok = true;
            break;
        }
    }
    if (!$ok) {
        $missing[] = $name;
    }
}

$checks = [
    'semua tautan Sarpras tercakup kondisi aktif' => $missing === [],
    'halaman KIR aktif' => $match('dashboard.sarpras.*', 'dashboard.sarpras.kir.index'),
    'halaman Persediaan aktif' => $match('dashboard.sarpras.*', 'dashboard.sarpras.persediaan.index'),
    'halaman Laporan aktif' => $match('dashboard.sarpras.*', 'dashboard.sarpras.laporan.index'),
];

$fail = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}
if ($missing) {
    echo 'Belum tercakup: ' . implode(', ', $missing) . PHP_EOL;
}

exit($fail ? 1 : 0);
