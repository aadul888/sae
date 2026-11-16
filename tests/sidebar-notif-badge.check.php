<?php
// Self-check badge notifikasi sidebar (jumlah item belum dibaca per menu).
// Jalankan: php tests/sidebar-notif-badge.check.php  (dari root proyek)

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$app->instance('request', $request);
$kernel->bootstrap();

$user = ['role' => 'admin', 'username' => 'admin', 'id' => 'sidebar-badge-check'];
session(['user' => $user]);

$item = fn(bool $read, string $url, string $id) => (object) [
    'id' => $id, 'judul' => 'T', 'pesan' => 'P', 'kategori' => 'sistem', 'tipe' => 'info',
    'icon' => '', 'url' => url($url), 'created_at' => now(), 'time_diff' => 'Baru',
    'is_read' => $read, 'is_dynamic' => true,
];

$ref = new ReflectionClass(\App\Services\SystemNotificationService::class);
$prop = $ref->getProperty('requestCache');
$prop->setAccessible(true);
$prop->setValue(null, [
    'sidebar-badge-check' => [
        'items' => collect([
            $item(false, '/dashboard/persuratan?status=menunggu_disposisi', '1'),
            $item(false, '/dashboard/presensi-mengajar', '2'),
            $item(true, '/dashboard/kesiswaan?tab=berkas', '3'),
            $item(false, '/dashboard', '4'), // path root tidak boleh membocorkan badge
        ]),
        'unread_count' => 3,
        'footer_url' => url('/dashboard/admin'),
        'footer_text' => 'Buka Dashboard',
    ],
]);

$html = view('partials.dash-sidebar', [
    'user' => $user,
    'role' => 'admin',
    'userName' => 'Admin',
    'waliKelasRombelName' => null,
])->render();

preg_match_all('/<span class="dash-nav-count">(\d+)<\/span>/', $html, $m);
$counts = array_map('intval', $m[1]);

$checks = [
    'badge tampil' => count($counts) >= 2,
    'badge persuratan & presensi' => in_array(1, $counts, true),
    'item sudah dibaca tidak dihitung' => count(array_filter($counts, fn($c) => $c > 2)) === 0,
    'path root /dashboard tidak bocor' => substr_count($html, 'dash-nav-count') === count($counts),
];

$fail = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}

exit($fail ? 1 : 0);
