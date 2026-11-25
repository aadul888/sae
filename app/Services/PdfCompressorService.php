<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * Kompres PDF memakai Ghostscript.
 *
 * PHP tidak punya pustaka kompres PDF bawaan; satu-satunya cara praktis adalah
 * memanggil Ghostscript (gs/gswin64c). Bila binari tidak tersedia, atau berkas
 * gagal/berubah aneh, layanan ini mengembalikan null dan pemanggil menyimpan
 * PDF asli — tidak pernah membuat unggahan gagal karena optimasi opsional.
 */
class PdfCompressorService
{
    /** Cache hasil pencarian binari selama satu request. */
    private ?string $binary = null;
    private bool $binaryResolved = false;

    /** Apakah kompresi PDF bisa dijalankan di server ini. */
    public function isAvailable(): bool
    {
        return $this->resolveBinary() !== null;
    }

    /**
     * Kompres berkas PDF di $sourcePath, tulis ke $targetPath (boleh sama).
     * Kembalikan ukuran hasil (byte) bila berhasil & cukup menghemat,
     * atau null bila tidak tersedia / tidak menguntungkan / gagal.
     */
    public function compress(string $sourcePath, ?string $targetPath = null): ?int
    {
        if (!config('pdf.enabled', true)) {
            return null;
        }

        $binary = $this->resolveBinary();
        if (!$binary || !is_file($sourcePath)) {
            return null;
        }

        $targetPath = $targetPath ?: $sourcePath;
        $originalSize = (int) (filesize($sourcePath) ?: 0);
        if ($originalSize <= 0) {
            return null;
        }

        $outFile = $targetPath . '.gscmp_' . bin2hex(random_bytes(4)) . '.pdf';

        try {
            $process = new Process([
                $binary,
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dPDFSETTINGS=' . (string) config('pdf.settings', '/ebook'),
                '-dNOPAUSE',
                '-dQUIET',
                '-dBATCH',
                '-dDetectDuplicateImages=true',
                '-dSAFER',
                '-sOutputFile=' . $outFile,
                $sourcePath,
            ]);
            $process->setTimeout((float) config('pdf.timeout', 60));
            $process->run();
        } catch (\Throwable $e) {
            @unlink($outFile);

            return null;
        }

        if (!$process->isSuccessful() || !is_file($outFile)) {
            @unlink($outFile);

            return null;
        }

        clearstatcache(true, $outFile);
        $newSize = (int) (filesize($outFile) ?: 0);

        $minSavings = (float) config('pdf.min_savings_percent', 5);
        $savedPercent = $originalSize > 0 ? (($originalSize - $newSize) / $originalSize) * 100 : 0;

        // Hasil tidak lebih kecil (atau untungnya tipis) → buang, pakai asli.
        if ($newSize <= 0 || $savedPercent < $minSavings) {
            @unlink($outFile);

            return null;
        }

        if (!@rename($outFile, $targetPath)) {
            // rename lintas-volume bisa gagal → fallback copy.
            if (!@copy($outFile, $targetPath)) {
                @unlink($outFile);

                return null;
            }
            @unlink($outFile);
        }

        return $newSize;
    }

    /**
     * Cari binari Ghostscript: config eksplisit → PATH → lokasi umum Windows.
     */
    private function resolveBinary(): ?string
    {
        if ($this->binaryResolved) {
            return $this->binary;
        }
        $this->binaryResolved = true;

        $configured = trim((string) config('pdf.binary', ''));
        if ($configured !== '' && @is_file($configured)) {
            $this->binary = $configured;

            return $this->binary;
        }

        $candidates = ['gswin64c', 'gswin32c', 'gs'];

        foreach ($candidates as $exe) {
            $found = $this->which($exe);
            if ($found) {
                $this->binary = $found;

                return $this->binary;
            }
        }

        // Lokasi umum di Windows bila PATH belum memuat Ghostscript.
        if (stripos(PHP_OS_FAMILY, 'Windows') === 0) {
            $globs = [
                'C:/Program Files/gs/*/bin/gswin64c.exe',
                'C:/Program Files (x86)/gs/*/bin/gswin32c.exe',
            ];
            foreach ($globs as $pattern) {
                $matches = glob($pattern) ?: [];
                if (!empty($matches)) {
                    $this->binary = end($matches);

                    return $this->binary;
                }
            }
        }

        return null;
    }

    /** Cari eksekutabel di PATH (lintas Windows/non-Windows). */
    private function which(string $exe): ?string
    {
        $cmd = stripos(PHP_OS_FAMILY, 'Windows') === 0 ? 'where' : 'command -v';

        try {
            $process = new Process(explode(' ', $cmd . ' ' . $exe));
            $process->setTimeout(5.0);
            $process->run();
            if ($process->isSuccessful()) {
                $first = trim(strtok($process->getOutput() ?: '', "\r\n"));
                if ($first !== '' && @is_file($first)) {
                    return $first;
                }
            }
        } catch (\Throwable $e) {
            // abaikan — anggap tidak ada
        }

        return null;
    }
}
