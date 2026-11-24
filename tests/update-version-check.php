<?php
// Self-check deteksi update: baseline commit harus memakai yang BENAR-BENAR terpasang
// (HEAD git atau settings.last_commit_hash bila deploy via ZIP), bukan HEAD mentah.
// Jalankan: php tests/update-version-check.php  (dari root proyek)

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$app->instance('request', $request);
$kernel->bootstrap();

$svc = app(\App\Services\UpdateService::class);
$ref = new ReflectionClass($svc);
$method = $ref->getMethod('resolveDeployedCommit');
$method->setAccessible(true);

$gitHead = trim((string) @shell_exec('git -C ' . escapeshellarg(base_path()) . ' rev-parse HEAD 2>&1'));
$prev = trim((string) @shell_exec('git -C ' . escapeshellarg(base_path()) . ' rev-parse HEAD~5 2>&1'));

$checks = [];

// 1. DB hash lebih baru (ZIP deploy) → dipakai, SETELAH HEAD.
$setting = (object) ['last_commit_hash' => $gitHead];
$checks['DB lebih baru dari HEAD dipilih'] = $method->invoke($svc, $setting, $prev) === $gitHead;

// 2. HEAD lebih baru dari DB → HEAD dipakai (tidak mundur).
$setting = (object) ['last_commit_hash' => $prev];
$checks['HEAD lebih baru dari DB dipakai'] = $method->invoke($svc, $setting, $gitHead) === $gitHead;

// 3. DB kosong → pakai HEAD.
$setting = (object) ['last_commit_hash' => null];
$checks['DB kosong -> HEAD'] = $method->invoke($svc, $setting, $gitHead) === $gitHead;

// 4. Nilai tidak valid → tidak crash, jatuh ke HEAD.
$setting = (object) ['last_commit_hash' => 'bukan-hash'];
$checks['hash invalid diabaikan'] = $method->invoke($svc, $setting, $gitHead) === $gitHead;

// 5. commitMatches: prefix 7 char dianggap sama.
$cm = $ref->getMethod('commitMatches');
$cm->setAccessible(true);
$checks['commitMatches prefix sama'] = $cm->invoke($svc, $gitHead, substr($gitHead, 0, 7)) === true;
$checks['commitMatches beda'] = $cm->invoke($svc, str_repeat('a', 40), $gitHead) === false;

// 6. checkUpdate tidak menandai update saat baseline == remote lokal.
$status = $svc->checkUpdate();
$checks['checkUpdate punya kunci wajib'] = isset($status['updates_available'], $status['git_commit'], $status['behind_count']);

$fail = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $fail++;
    }
}

exit($fail ? 1 : 0);
