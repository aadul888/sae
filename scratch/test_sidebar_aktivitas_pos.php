<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\View;

echo "=== TESTING SIDEBAR POSISI AKTIVITAS & LAPORAN ===\n";

// 1. Test Superadmin
$adminUser = User::where('username', 'admin')->first() ?: (object)[
    'id' => 'admin-id',
    'pengguna_id' => 'admin-id',
    'name' => 'Super Admin',
    'nama' => 'Super Admin',
    'role' => 'admin',
    'peran_id_str' => 'admin',
    'ptk_id' => null,
    'foto_url' => null,
];

session(['user' => $adminUser]);

$html = View::make('partials.dash-sidebar', ['appVersion' => '1.0.0'])->render();

// Check if each bidang has Aktivitas Harian and Laporan Kinerja at the bottom of its nested menu
$bidangList = [
    'persuratan', 'kesiswaan', 'kepegawaian', 'sarpras', 'laboran',
    'perpustakaan', 'teknisi', 'keamanan', 'penjaga', 'piket'
];

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Find Tendik group
$tendikGroup = null;
$groups = $xpath->query("//div[contains(@class, 'dash-nav-group')]");
foreach ($groups as $g) {
    $labelNode = $xpath->query(".//span[contains(@class, 'nav-label')]", $g)->item(0);
    if ($labelNode && trim($labelNode->textContent) === 'Tendik') {
        $tendikGroup = $g;
        break;
    }
}

if (!$tendikGroup) {
    echo "[-] Tendik group not found for admin!\n";
    exit(1);
}
echo "[+] Found 'Tendik' menu group for admin\n";

// Check 10 nested groups
$nestedGroups = $xpath->query(".//div[contains(@class, 'dash-nav-nested-group')]", $tendikGroup);
echo "[+] Found " . $nestedGroups->length . " nested groups in Tendik menu (Expected 10)\n";

foreach ($nestedGroups as $ng) {
    $titleNode = $xpath->query(".//button[contains(@class, 'dash-nav-nested-toggle')]//span[contains(@class, 'nav-label')]", $ng)->item(0);
    $title = $titleNode ? trim($titleNode->textContent) : 'Unknown';
    
    $links = $xpath->query(".//div[contains(@class, 'dash-nav-nested-menu')]/a", $ng);
    $totalLinks = $links->length;
    
    if ($totalLinks < 2) {
        echo "[-] {$title}: Not enough links ({$totalLinks})\n";
        continue;
    }
    
    $secondToLast = $links->item($totalLinks - 2);
    $last = $links->item($totalLinks - 1);
    
    $secondToLastText = trim($xpath->query(".//span[contains(@class, 'nav-label')]", $secondToLast)->item(0)->textContent ?? '');
    $lastText = trim($xpath->query(".//span[contains(@class, 'nav-label')]", $last)->item(0)->textContent ?? '');
    
    $secondToLastHref = $secondToLast->getAttribute('href');
    $lastHref = $last->getAttribute('href');
    
    $ok1 = ($secondToLastText === 'Aktivitas Harian' && strpos($secondToLastHref, 'aktivitas') !== false);
    $ok2 = ($lastText === 'Laporan Kinerja' && strpos($lastHref, 'laporan') !== false);
    
    if ($ok1 && $ok2) {
        echo "  [OK] {$title}: Last 2 items are '{$secondToLastText}' and '{$lastText}'\n";
    } else {
        echo "  [FAIL] {$title}: Found '{$secondToLastText}' and '{$lastText}' at bottom!\n";
    }
}

// Check universal items at the bottom of Tendik submenu
$submenu = $xpath->query(".//div[contains(@class, 'dash-nav-submenu')]", $tendikGroup)->item(0);
// Direct child <a> of submenu
$directLinks = [];
foreach ($submenu->childNodes as $child) {
    if ($child->nodeName === 'a') {
        $directLinks[] = $child;
    }
}
$totalDirect = count($directLinks);
echo "[+] Found {$totalDirect} direct links at root of Tendik submenu\n";

if ($totalDirect >= 3) {
    $firstDirect = $directLinks[0];
    $firstText = trim($xpath->query(".//span[contains(@class, 'nav-label')]", $firstDirect)->item(0)->textContent ?? '');
    echo "  First item: '{$firstText}' (Expected: Dashboard)\n";
    
    $subLast1 = $directLinks[$totalDirect - 2];
    $subLast2 = $directLinks[$totalDirect - 1];
    
    $subLast1Text = trim($xpath->query(".//span[contains(@class, 'nav-label')]", $subLast1)->item(0)->textContent ?? '');
    $subLast2Text = trim($xpath->query(".//span[contains(@class, 'nav-label')]", $subLast2)->item(0)->textContent ?? '');
    
    echo "  Second to last item: '{$subLast1Text}' (Expected: Aktivitas Harian)\n";
    echo "  Last item: '{$subLast2Text}' (Expected: Laporan Kinerja)\n";
    
    if ($subLast1Text === 'Aktivitas Harian' && $subLast2Text === 'Laporan Kinerja') {
        echo "[SUCCESS] Superadmin Tendik menu structure is PERFECT!\n";
    } else {
        echo "[FAIL] Superadmin Tendik bottom items mismatch!\n";
    }
}

// 2. Test Regular Tendik Staff
$tendikUser = (object)[
    'id' => 'tendik-id',
    'pengguna_id' => 'tendik-id',
    'name' => 'Staff TU',
    'nama' => 'Staff TU',
    'role' => 'tendik',
    'peran_id_str' => 'tendik',
    'ptk_id' => null,
    'foto_url' => null,
];
session(['user' => $tendikUser]);
$htmlTendik = View::make('partials.dash-sidebar', ['appVersion' => '1.0.0'])->render();

$domTendik = new DOMDocument();
libxml_use_internal_errors(true);
$domTendik->loadHTML('<?xml encoding="utf-8" ?>' . $htmlTendik);
libxml_clear_errors();
$xpathTendik = new DOMXPath($domTendik);

$tendikGroup2 = null;
foreach ($xpathTendik->query("//div[contains(@class, 'dash-nav-group')]") as $g) {
    $labelNode = $xpathTendik->query(".//span[contains(@class, 'nav-label')]", $g)->item(0);
    if ($labelNode && trim($labelNode->textContent) === 'Tendik') {
        $tendikGroup2 = $g;
        break;
    }
}

if ($tendikGroup2) {
    $links = $xpathTendik->query(".//div[contains(@class, 'dash-nav-submenu')]/a", $tendikGroup2);
    $cnt = $links->length;
    if ($cnt >= 2) {
        $l1 = trim($xpathTendik->query(".//span[contains(@class, 'nav-label')]", $links->item($cnt - 2))->item(0)->textContent ?? '');
        $l2 = trim($xpathTendik->query(".//span[contains(@class, 'nav-label')]", $links->item($cnt - 1))->item(0)->textContent ?? '');
        echo "[+] Regular Tendik bottom items: '{$l1}' and '{$l2}' (Expected: Input Aktivitas & Laporan Kinerja)\n";
    }
}

echo "=== TEST COMPLETED ===\n";
