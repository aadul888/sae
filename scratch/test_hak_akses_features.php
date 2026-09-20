<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

session(['user' => (object)[
    'pengguna_id' => 'test-admin',
    'role' => 'admin',
    'nama' => 'Super Admin'
]]);

use App\Models\RolePermission;
use App\Http\Controllers\PermissionController;
use Illuminate\Http\Request;

echo "=== 1. TEST GROUPS IN getBasePermissions() ===\n";
$base = RolePermission::getBasePermissions();
$groups = [];
foreach ($base as $groupName => $modules) {
    $groups[$groupName] = array_keys($modules);
}
echo "Total groups: " . count($groups) . "\n";
$tendikGroups = array_filter(array_keys($groups), fn($g) => str_contains($g, 'Tendik:'));
echo "Tendik groups found (" . count($tendikGroups) . "):\n";
foreach ($tendikGroups as $tg) {
    echo " - $tg: " . implode(', ', $groups[$tg]) . "\n";
}

echo "\n=== 2. TEST REF TUGAS TAMBAHAN BIDANG ===\n";
$refs = \Illuminate\Support\Facades\DB::table('ref_tugas_tambahan')->get();
$byBidang = [];
foreach ($refs as $r) {
    $byBidang[$r->bidang ?? 'Tanpa Bidang'][] = $r->nama;
}
foreach ($byBidang as $bidang => $namas) {
    echo "Bidang [$bidang] (" . count($namas) . "):\n";
    foreach ($namas as $n) {
        echo "   * $n\n";
    }
}

echo "\n=== 3. TEST REMOVE & ADD MODULE IN CONTROLLER ===\n";
$controller = new PermissionController();

// Test removing a non-core module from tendik: e.g. menu_piket
$testRole = 'tendik';
$testKey = 'menu_piket';

// Ensure it exists first
RolePermission::updateOrCreate(
    ['role' => $testRole, 'permission_key' => $testKey],
    ['module_name' => 'Piket Sekolah', 'module_group' => 'Tendik: Piket Sekolah', 'can_read' => 1]
);

$existsBefore = RolePermission::where('role', $testRole)->where('permission_key', $testKey)->exists();
echo "Before remove ($testRole, $testKey): exists = " . ($existsBefore ? 'YES' : 'NO') . "\n";

// Remove via removeModule
$req = Request::create('/dashboard/hak-akses/remove-module', 'POST', [
    'role' => $testRole,
    'permission_key' => $testKey
]);
$res = $controller->removeModule($req);
$resData = json_decode($res->getContent(), true);
echo "removeModule response: " . json_encode($resData) . "\n";

$existsAfter = RolePermission::where('role', $testRole)->where('permission_key', $testKey)->exists();
echo "After remove ($testRole, $testKey): exists = " . ($existsAfter ? 'YES' : 'NO') . "\n";

// Test if syncAvailablePermissions() revives it (it should NOT revive it)
RolePermission::syncAvailablePermissions();
$existsAfterSync = RolePermission::where('role', $testRole)->where('permission_key', $testKey)->exists();
echo "After syncAvailablePermissions() ($testRole, $testKey): exists = " . ($existsAfterSync ? 'YES (BUG!)' : 'NO (CORRECT!)') . "\n";

// Test re-adding it via addModule
$reqAdd = Request::create('/dashboard/hak-akses/add-module', 'POST', [
    'role' => $testRole,
    'permission_key' => $testKey
]);
$resAdd = $controller->addModule($reqAdd);
$resAddData = json_decode($resAdd->getContent(), true);
echo "addModule response: " . json_encode($resAddData) . "\n";

$existsAfterAdd = RolePermission::where('role', $testRole)->where('permission_key', $testKey)->exists();
echo "After addModule ($testRole, $testKey): exists = " . ($existsAfterAdd ? 'YES' : 'NO') . "\n";

echo "\n=== 4. TEST RENDER VIEW dashboard.hak-akses ===\n";
$reqAdmin = Request::create('/dashboard/hak-akses', 'GET', ['role' => 'admin']);
$resAdmin = $controller->index($reqAdmin);
echo "Render view (admin): " . ($resAdmin->getName() === 'dashboard.hak-akses' ? 'SUCCESS' : 'FAIL') . "\n";

$reqTendik = Request::create('/dashboard/hak-akses', 'GET', ['role' => 'tendik']);
$resTendik = $controller->index($reqTendik);
echo "Render view (tendik): " . ($resTendik->getName() === 'dashboard.hak-akses' ? 'SUCCESS' : 'FAIL') . "\n";

$reqTugas = Request::create('/dashboard/hak-akses', 'GET', ['role' => 'tugas_tambahan']);
$resTugas = $controller->index($reqTugas);
echo "Render view (tugas_tambahan): " . ($resTugas->getName() === 'dashboard.hak-akses' ? 'SUCCESS' : 'FAIL') . "\n";

// Render actual HTML to ensure no Blade runtime errors
$html = $resAdmin->render();
echo "Admin HTML length: " . strlen($html) . " bytes\n";
$htmlTendik = $resTendik->render();
echo "Tendik HTML length: " . strlen($htmlTendik) . " bytes\n";
$htmlTugas = $resTugas->render();
echo "Tugas Tambahan HTML length: " . strlen($htmlTugas) . " bytes\n";

echo "\nALL TESTS COMPLETED SUCCESSFULLY!\n";
