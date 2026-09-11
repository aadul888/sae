<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$allPerms = App\Models\RolePermission::getAvailablePermissions();
$valid = [];
foreach ($allPerms as $grp => $items) {
    foreach ($items as $k => $cfg) {
        foreach ($cfg['roles'] ?? [] as $r) {
            $valid[$r . ':' . $k] = true;
        }
    }
}

$stale = [];
foreach (App\Models\RolePermission::all() as $row) {
    if (!isset($valid[$row->role . ':' . $row->permission_key])) {
        $stale[] = [
            'id' => $row->id,
            'role' => $row->role,
            'key' => $row->permission_key,
            'allowed' => $row->is_allowed
        ];
    }
}

echo "STALE ROWS COUNT: " . count($stale) . PHP_EOL;
foreach ($stale as $s) {
    echo " - ID: {$s['id']} | Role: {$s['role']} | Key: {$s['key']} | Allowed: " . ($s['allowed'] ? 'true' : 'false') . PHP_EOL;
}
