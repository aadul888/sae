<?php

namespace App\Console\Commands;

use App\Models\PipPenggunaanBukti;
use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * pip:kompres-bukti
 * -------------------------------------------------------------------------
 * Kompres bukti laporan PIP yang sudah tersimpan (gambar jpg/jpeg/png/bmp).
 * PDF dibiarkan apa adanya. Default DRY RUN; tambahkan --apply untuk menulis.
 *
 *   php artisan pip:kompres-bukti
 *   php artisan pip:kompres-bukti --apply
 */
class PipKompresBukti extends Command
{
    protected $signature = 'pip:kompres-bukti
                            {--apply : Tulis perubahan ke disk & database (default: dry-run)}';

    protected $description = 'Kompres gambar bukti laporan PIP yang sudah tersimpan';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $optimizer = app(ImageOptimizerService::class);
        $disk = Storage::disk('public');

        $totalBefore = 0;
        $totalAfter = 0;
        $changed = 0;
        $skipped = 0;

        PipPenggunaanBukti::query()->orderBy('id')->chunkById(100, function ($rows) use (
            $apply,
            $optimizer,
            $disk,
            &$totalBefore,
            &$totalAfter,
            &$changed,
            &$skipped
        ) {
            foreach ($rows as $bukti) {
                $oldRelative = ltrim((string) $bukti->file_path, '/');
                $ext = strtolower(pathinfo($oldRelative, PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'webp'], true) || !$disk->exists($oldRelative)) {
                    $skipped++;
                    continue;
                }

                $full = $disk->path($oldRelative);
                $sizeBefore = (int) (@filesize($full) ?: 0);
                $binary = (string) @file_get_contents($full);
                if ($binary === '') {
                    $skipped++;
                    continue;
                }

                $dir = dirname($full);
                try {
                    $hasil = $optimizer->optimizeAndSaveBinary($binary, $dir, 'bukti', 1600, 78);
                } catch (\Throwable $e) {
                    $this->warn("Gagal #{$bukti->id}: {$e->getMessage()}");
                    $skipped++;
                    continue;
                }

                $sizeAfter = (int) $hasil['size'];
                $totalBefore += $sizeBefore;
                $totalAfter += $sizeAfter;

                $newRelative = 'pip-penggunaan/' . basename($dir) . '/' . $hasil['filename'];
                // Fallback bila struktur direktori berbeda dari asumsi.
                if (!$disk->exists($newRelative)) {
                    $newRelative = dirname($oldRelative) . '/' . $hasil['filename'];
                }

                $this->line(sprintf(
                    '#%d  %s  %s KB -> %s KB',
                    $bukti->id,
                    basename($oldRelative),
                    number_format($sizeBefore / 1024, 0),
                    number_format($sizeAfter / 1024, 0)
                ));

                if ($apply) {
                    if ($newRelative !== $oldRelative && $disk->exists($oldRelative)) {
                        $disk->delete($oldRelative);
                    }
                    $bukti->forceFill(['file_path' => $newRelative])->save();
                }

                $changed++;
            }
        });

        $saved = max(0, $totalBefore - $totalAfter);
        $this->newLine();
        $this->info(sprintf(
            '%s: %d file diproses, %d dilewati. Total %s KB -> %s KB (hemat %s KB / %s%%).',
            $apply ? 'APPLIED' : 'DRY-RUN',
            $changed,
            $skipped,
            number_format($totalBefore / 1024, 0),
            number_format($totalAfter / 1024, 0),
            number_format($saved / 1024, 0),
            $totalBefore > 0 ? round($saved / $totalBefore * 100, 1) : 0
        ));

        if (!$apply) {
            $this->comment('Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }
}
