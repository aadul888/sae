<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ImageOptimizerService
{
    /**
     * Direktori dasar aset publik di dalam storage/app/public/
     */
    public const ASSET_DIR_JURUSAN = 'assets/jurusan';
    public const ASSET_DIR_SEKOLAH = 'assets/sekolah';
    public const ASSET_DIR_FOTO_PESERTA_DIDIK = 'assets/peserta-didik/foto';
    public const ASSET_DIR_BERKAS_PESERTA_DIDIK = 'assets/peserta-didik/berkas';

    // Alias kompatibilitas
    public const ASSET_DIR_FOTO_SISWA = self::ASSET_DIR_FOTO_PESERTA_DIDIK;
    public const ASSET_DIR_BERKAS_SISWA = self::ASSET_DIR_BERKAS_PESERTA_DIDIK;

    /**
     * Batas resolusi maksimal standar untuk logo / kartu pelajar (1000px)
     */
    public const DEFAULT_MAX_DIMENSION = 1000;

    /**
     * Optimasi dan simpan file PNG dengan preservasi transparansi (Alpha Channel) 100%
     * serta kompresi cerdas lossless.
     *
     * @param UploadedFile $file File yang diunggah
     * @param string $directory Sub-direktori dalam storage/app/public (contoh: assets/jurusan)
     * @param string|null $customFilename Nama file kustom tanpa ekstensi (opsional)
     * @param int $maxDimension Dimensi maksimum lebar/tinggi gambar (default 1000px)
     * @return array [path, size, width, height, original_size, savings_percent]
     */
    public function optimizeAndSavePng(
        UploadedFile $file,
        string $directory = self::ASSET_DIR_JURUSAN,
        ?string $customFilename = null,
        int $maxDimension = self::DEFAULT_MAX_DIMENSION
    ): array {
        // 1. Validasi ketat format PNG (MIME dan Ekstensi)
        $this->validatePng($file);

        $sourcePath = $file->getRealPath();
        $originalSize = $file->getSize();

        // 2. Baca gambar menggunakan GD
        $srcImage = @imagecreatefrompng($sourcePath);
        if (!$srcImage) {
            throw new RuntimeException('Gagal memproses file PNG. Pastikan file gambar PNG valid dan tidak rusak.');
        }

        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);

        if ($origWidth <= 0 || $origHeight <= 0) {
            imagedestroy($srcImage);
            throw new RuntimeException('Dimensi gambar PNG tidak valid.');
        }

        // 3. Hitung dimensi baru jika melebihi batas maksimal (proportional scaling)
        $needResize = ($origWidth > $maxDimension || $origHeight > $maxDimension);
        if ($needResize) {
            $ratio = min($maxDimension / $origWidth, $maxDimension / $origHeight);
            $newWidth = (int) max(1, round($origWidth * $ratio));
            $newHeight = (int) max(1, round($origHeight * $ratio));

            $destImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preservasi transparansi (Alpha Channel)
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
            imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);

            // Resampling berkualitas tinggi
            imagecopyresampled(
                $destImage,
                $srcImage,
                0, 0, 0, 0,
                $newWidth,
                $newHeight,
                $origWidth,
                $origHeight
            );

            imagedestroy($srcImage);
            $finalImage = $destImage;
            $finalWidth = $newWidth;
            $finalHeight = $newHeight;
        } else {
            // Jika dimensi sudah sesuai, pertahankan gambar asli dan aktifkan alpha channel
            imagealphablending($srcImage, false);
            imagesavealpha($srcImage, true);
            $finalImage = $srcImage;
            $finalWidth = $origWidth;
            $finalHeight = $origHeight;
        }

        // 4. Siapkan folder target di storage/app/public/
        $cleanDirectory = trim($directory, '/\\');
        $targetDir = storage_path('app/public/' . $cleanDirectory);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true, true);
        }

        // 5. Tentukan nama file unik & bersih
        $slug = $customFilename ? Str::slug($customFilename, '_') : 'asset_' . time();
        $filename = $slug . '_' . Str::random(8) . '.png';
        $fullDestinationPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        // 6. Simpan dengan kompresi tingkat 9 (lossless deflate compression maksimal)
        // Level 9 memberikan ukuran byte paling hemat tanpa mengubah 1 piksel pun dari kualitas warna
        $saved = imagepng($finalImage, $fullDestinationPath, 9);
        imagedestroy($finalImage);

        if (!$saved || !file_exists($fullDestinationPath)) {
            throw new RuntimeException('Gagal menyimpan file gambar hasil optimasi ke penyimpanan sistem.');
        }

        clearstatcache(true, $fullDestinationPath);
        $finalSize = filesize($fullDestinationPath);
        $savings = $originalSize > 0 ? round((($originalSize - $finalSize) / $originalSize) * 100, 1) : 0;

        $relativePath = $cleanDirectory . '/' . $filename;

        return [
            'path' => $relativePath,
            'full_path' => $fullDestinationPath,
            'url' => asset('storage/' . $relativePath),
            'filename' => $filename,
            'size' => $finalSize,
            'width' => $finalWidth,
            'height' => $finalHeight,
            'original_size' => $originalSize,
            'savings_percent' => max(0, $savings),
        ];
    }

    /**
     * Validasi ketat bahwa file adalah PNG yang sah
     */
    public function validatePng(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'png') {
            throw new InvalidArgumentException("Format file tidak diizinkan. Wajib menggunakan format PNG (ditemukan: .{$extension}).");
        }

        $mime = $file->getMimeType();
        $validMimes = ['image/png', 'image/x-png'];
        if (!in_array($mime, $validMimes, true)) {
            throw new InvalidArgumentException("Tipe MIME tidak valid ({$mime}). File harus berupa gambar PNG yang sah.");
        }
    }

    /**
     * Hapus file aset lama dari disk publik jika ada
     */
    public function deleteFile(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $cleanPath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $fullPath = storage_path('app/public' . DIRECTORY_SEPARATOR . $cleanPath);

        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }

        return false;
    }
}
