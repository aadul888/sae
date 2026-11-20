<?php
// Self-check: grup Teknisi & IT (dan Sarpras/Laboran) harus membuka grup yang benar
// pada halaman bersama (Ruang, Aset, BHP) sehingga posisi menu konsisten.
// Jalankan: php tests/sidebar-teknisi-active.check.php  (dari root proyek)

/**
 * Replikasi logika $isSarprasActive / $isLaboranActive / $isTeknisiActive
 * (lihat resources/views/partials/dash-sidebar.blade.php).
 */
function sidebarActiveGroups(array $duties, string $route, array $canMenus = []): array
{
    $isSarprasDuty = in_array('STAF_SARPRAS', $duties, true);
    $isLaboranDuty = in_array('LABORAN', $duties, true);
    $isTeknisiDuty = in_array('TEKNISI_IT', $duties, true);

    $ri = fn(string $p) => (bool) preg_match('#^' . str_replace('\*', '.*', preg_quote($p, '#')) . '$#', $route);

    $isBhpPage = $ri('dashboard.laboran.bhp*');
    $isAsetPage = $ri('dashboard.sarpras.aset*');
    $isPeminjamanPage = $ri('dashboard.sarpras.peminjaman*');
    $isSarprasMasterPage = $ri('dashboard.sarpras.ruang*') || $isAsetPage;

    $teknisiOwnsPage = $isTeknisiDuty && !$isSarprasDuty && !$isLaboranDuty && ($isSarprasMasterPage || $isBhpPage);
    $laboranOwnsPage = $isLaboranDuty && !$isSarprasDuty && !$isTeknisiDuty && ($isAsetPage || $isPeminjamanPage || $isBhpPage);

    $canSarpras = in_array('menu_sarpras', $canMenus, true) || $isSarprasDuty;
    $canLaboran = in_array('menu_laboran', $canMenus, true) || $isLaboranDuty;
    $canTeknisi = in_array('menu_teknisi', $canMenus, true) || $isTeknisiDuty;

    $isSarprasActive =
        $ri('dashboard.sarpras.*') && !$teknisiOwnsPage && !$laboranOwnsPage ||
        ($isBhpPage && $canSarpras && !$canLaboran && !$isTeknisiDuty) ||
        ($canTeknisi && !$isTeknisiDuty && $ri('dashboard.teknisi.work-order*'));

    $isLaboranActive = ($ri('dashboard.laboran.*') && !$isBhpPage) || $laboranOwnsPage;
    $isTeknisiActive = $ri('dashboard.teknisi.*') || $teknisiOwnsPage;

    $out = [];
    if ($isSarprasActive) $out[] = 'Sarpras';
    if ($isLaboranActive) $out[] = 'Laboran';
    if ($isTeknisiActive) $out[] = 'Teknisi';
    sort($out);
    return $out;
}

$cases = [
    // duties, route, expected grup aktif
    [['TEKNISI_IT'], 'dashboard.sarpras.ruang.index', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.sarpras.aset.index', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.sarpras.aset.export', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.laboran.bhp.index', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.teknisi.work-order', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.teknisi.modul.index', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.teknisi.pemeliharaan', ['Teknisi']],
    [['TEKNISI_IT'], 'dashboard.teknisi.jurnal-perbaikan.index', ['Teknisi']],

    [['STAF_SARPRAS'], 'dashboard.sarpras.ruang.index', ['Sarpras']],
    [['STAF_SARPRAS'], 'dashboard.sarpras.aset.index', ['Sarpras']],
    [['STAF_SARPRAS'], 'dashboard.sarpras.kir.index', ['Sarpras']],
    [['STAF_SARPRAS'], 'dashboard.sarpras.persediaan.index', ['Sarpras']],
    [['STAF_SARPRAS'], 'dashboard.sarpras.laporan.index', ['Sarpras']],
    [['STAF_SARPRAS'], 'dashboard.sarpras.peminjaman.index', ['Sarpras']],

    [['LABORAN'], 'dashboard.laboran.bhp.index', ['Laboran']],
    [['LABORAN'], 'dashboard.laboran.dashboard', ['Laboran']],
    [['LABORAN'], 'dashboard.sarpras.aset.index', ['Laboran']],
    [['LABORAN'], 'dashboard.sarpras.peminjaman.index', ['Laboran']],

    // Prioritas: Sarpras menang atas Teknisi pada halaman aset
    [['STAF_SARPRAS', 'TEKNISI_IT'], 'dashboard.sarpras.aset.index', ['Sarpras']],
    [['STAF_SARPRAS', 'TEKNISI_IT'], 'dashboard.teknisi.work-order', ['Teknisi']],
];

$fail = 0;
foreach ($cases as [$duties, $route, $expected]) {
    $actual = sidebarActiveGroups($duties, $route);
    $exp = $expected; sort($exp);
    $ok = $actual === $exp;
    echo ($ok ? 'OK   ' : 'FAIL ') . implode('+', $duties) . " @ {$route} => [" . implode(',', $actual) . ']' . PHP_EOL;
    if (!$ok) {
        $fail++;
        echo '     expected [' . implode(',', $exp) . ']' . PHP_EOL;
    }
}

exit($fail ? 1 : 0);
