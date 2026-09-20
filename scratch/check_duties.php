<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;

$anis = User::where('username', 'like', '%anis%')->first();
echo "=== ANIS ===\n";
dump([
    'pengguna_id' => $anis->pengguna_id,
    'ptk_id' => $anis->ptk_id,
    'role' => $anis->role,
    'peran_id_str' => $anis->peran_id_str
]);

$duties = DB::table('ptk_tugas_tambahan')->where('user_id', $anis->pengguna_id)->orWhere('ptk_id', $anis->ptk_id)->get();
echo "Duties count: " . count($duties) . "\n";
foreach ($duties as $d) {
    $ref = DB::table('ref_tugas_tambahan')->where('id', $d->tugas_tambahan_id)->first();
    dump($ref);
}

echo "RolePermission canAccess kesiswaan: " . (RolePermission::canAccess($anis, 'menu_kesiswaan') ? 'YES' : 'NO') . "\n";

$jalaludin = User::where('username', 'like', '%jalaludin%')->first();
echo "\n=== JALALUDIN ===\n";
dump([
    'pengguna_id' => $jalaludin->pengguna_id,
    'ptk_id' => $jalaludin->ptk_id,
    'role' => $jalaludin->role,
    'peran_id_str' => $jalaludin->peran_id_str
]);
$dutiesJ = DB::table('ptk_tugas_tambahan')->where('user_id', $jalaludin->pengguna_id)->orWhere('ptk_id', $jalaludin->ptk_id)->get();
echo "Duties count: " . count($dutiesJ) . "\n";
foreach ($dutiesJ as $d) {
    $ref = DB::table('ref_tugas_tambahan')->where('id', $d->tugas_tambahan_id)->first();
    dump($ref);
}

echo "RolePermission canAccess kepegawaian: " . (RolePermission::canAccess($jalaludin, 'menu_kepegawaian') ? 'YES' : 'NO') . "\n";
