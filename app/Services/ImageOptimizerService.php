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
    public const ASSET_DIR_FOTO_GTK = 'assets/gtk/foto';

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
                0,
                0,
                0,
                0,
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
     * Optimasi & simpan unggahan apa pun (gambar dikompres, dokumen apa adanya).
     *
     * Gambar → resize proporsional ke $maxDimension lalu encode ulang:
     *   - punya transparansi → PNG level 9
     *   - foto tanpa transparansi → WebP q{$quality} (fallback JPEG)
     * Dokumen (pdf/doc/xls/...) → disimpan tanpa diubah.
     *
     * Nama file = prefix + hash isi (12 hex) sehingga unggahan identik tidak menumpuk.
     *
     * @param string $targetDir Direktori tujuan ABSOLUT (public_path(...) atau storage_path('app/public/...')).
     */
    public function optimizeAndSaveImage(
        UploadedFile $file,
        string $targetDir,
        ?string $namePrefix = null,
        int $maxDimension = 1600,
        int $quality = 80
    ): array {
        $originalSize = (int) $file->getSize();
        $binary = (string) @file_get_contents($file->getRealPath());
        $encoded = $this->encodeImageBinary($binary, $maxDimension, $quality);

        if ($encoded === null) {
            // Bukan gambar → simpan apa adanya dengan nama aman.
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';

            return $this->persistBinary($binary, $extension, 0, 0, $targetDir, $namePrefix, $originalSize);
        }

        return $this->persistBinary(
            $encoded['binary'],
            $encoded['ext'],
            $encoded['width'],
            $encoded['height'],
            $targetDir,
            $namePrefix,
            $originalSize
        );
    }

    /**
     * Sama seperti optimizeAndSaveImage() tetapi menerima data biner gambar mentah
     * (mis. snapshot webcam base64). Melempar RuntimeException bila bukan gambar valid.
     */
    public function optimizeAndSaveBinary(
        string $binary,
        string $targetDir,
        ?string $namePrefix = null,
        int $maxDimension = 1600,
        int $quality = 80
    ): array {
        $encoded = $this->encodeImageBinary($binary, $maxDimension, $quality);
        if ($encoded === null) {
            throw new RuntimeException('Data gambar tidak valid atau rusak.');
        }

        return $this->persistBinary(
            $encoded['binary'],
            $encoded['ext'],
            $encoded['width'],
            $encoded['height'],
            $targetDir,
            $namePrefix,
            strlen($binary)
        );
    }

    /**
     * @return array{filename:string,path:string,full_path:string,url:string,size:int,original_size:int,width:int,height:int,savings_percent:float,format:string}
     */
    private function persistBinary(
        string $binary,
        string $extension,
        int $width,
        int $height,
        string $targetDir,
        ?string $namePrefix,
        int $originalSize
    ): array {
        $dir = $this->ensureDirectory($targetDir);
        $filename = $this->buildFilename($namePrefix, $binary, $extension);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($fullPath, $binary) === false) {
            throw new RuntimeException('Gagal menyimpan berkas unggahan ke penyimpanan sistem.');
        }

        clearstatcache(true, $fullPath);
        $finalSize = (int) (filesize($fullPath) ?: 0);

        return [
            'filename' => $filename,
            'path' => $fullPath,
            'full_path' => $fullPath,
            'url' => $this->publicUrlFor($fullPath),
            'size' => $finalSize,
            'original_size' => $originalSize,
            'width' => $width,
            'height' => $height,
            'savings_percent' => $originalSize > 0 ? max(0.0, round((($originalSize - $finalSize) / $originalSize) * 100, 1)) : 0.0,
            'format' => $extension,
        ];
    }

    /**
     * Encode ulang gambar menjadi biner optimal, atau null bila bukan gambar.
     *
     * @return array{binary:string,ext:string,width:int,height:int}|null
     */
    private function encodeImageBinary(string $binary, int $maxDimension, int $quality): ?array
    {
        if ($binary === '' || !function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Gambar palette (PNG-8/GIF) → truecolor agar resize & deteksi alpha benar.
        if (!imageistruecolor($source) && function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($source);
        }

        $hasAlpha = $this->imageUsesAlpha($source, $width, $height);

        if ($maxDimension > 0 && ($width > $maxDimension || $height > $maxDimension)) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));

            $destination = imagecreatetruecolor($newWidth, $newHeight);
            if ($hasAlpha) {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, imagecolorallocatealpha($destination, 0, 0, 0, 127));
            }

            imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $destination;
            $width = $newWidth;
            $height = $newHeight;
        } elseif ($hasAlpha) {
            imagealphablending($source, false);
            imagesavealpha($source, true);
        }

        $encoded = $this->encodeGdImage($source, $hasAlpha, $quality);
        imagedestroy($source);

        if ($encoded === null) {
            return null;
        }

        return ['binary' => $encoded['binary'], 'ext' => $encoded['ext'], 'width' => $width, 'height' => $height];
    }

    /**
     * Keluarkan biner dari GdImage ke berkas sementara agar tidak menyentuh output buffer.
     *
     * @return array{binary:string,ext:string}|null
     */
    private function encodeGdImage(\GdImage $image, bool $hasAlpha, int $quality): ?array
    {
        $targets = $hasAlpha
            ? [['png', 'imagepng', 9], ['webp', 'imagewebp', $quality], ['jpg', 'imagejpeg', $quality]]
            : [['webp', 'imagewebp', $quality], ['jpg', 'imagejpeg', $quality], ['png', 'imagepng', 9]];

        foreach ($targets as [$ext, $fn, $arg]) {
            if (!function_exists($fn)) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'sae_img_');
            if ($tmp === false) {
                return null;
            }

            $ok = ($ext === 'png') ? @imagepng($image, $tmp, $arg) : @$fn($image, $tmp, $arg);
            $binary = $ok ? (string) @file_get_contents($tmp) : '';
            @unlink($tmp);

            if ($ok && $binary !== '') {
                return ['binary' => $binary, 'ext' => $ext];
            }
        }

        return null;
    }

    /**
     * Deteksi transparansi dengan sampling grid (gambar sudah truecolor).
     */
    private function imageUsesAlpha(\GdImage $image, int $width, int $height): bool
    {
        $stepX = max(1, intdiv($width, 128));
        $stepY = max(1, intdiv($height, 128));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function buildFilename(?string $prefix, string $binary, string $extension): string
    {
        $base = Str::slug($prefix ?: 'file', '_') ?: 'file';

        return $base . '_' . substr(sha1($binary), 0, 12) . '.' . $extension;
    }

    private function ensureDirectory(string $directory): string
    {
        $directory = rtrim($directory, '/\\');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return $directory;
    }

    /**
     * URL publik untuk berkas, baik di public/ maupun storage/app/public/.
     */
    private function publicUrlFor(string $fullPath): string
    {
        $normalized = str_replace('\\', '/', $fullPath);
        $publicRoot = str_replace('\\', '/', public_path());
        $storageRoot = str_replace('\\', '/', storage_path('app/public'));

        if (str_starts_with($normalized, $publicRoot . '/')) {
            return asset(substr($normalized, strlen($publicRoot) + 1));
        }

        if (str_starts_with($normalized, $storageRoot . '/')) {
            return asset('storage/' . substr($normalized, strlen($storageRoot) + 1));
        }

        return $normalized;
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
