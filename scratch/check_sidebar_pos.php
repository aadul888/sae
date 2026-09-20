<?php
$content = file_get_contents('c:/laragon/www/sae/resources/views/partials/dash-sidebar.blade.php');
$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (stripos($line, 'aktivitas') !== false || stripos($line, 'laporan') !== false) {
        echo ($idx + 1) . ': ' . trim($line) . "\n";
    }
}
