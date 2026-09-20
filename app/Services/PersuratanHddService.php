<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersuratanHddService
{
    /**
     * Ambil path direktori penyimpanan harddisk (HDD) aktif dari database
     */
    public static function getHddPath(): string
    {
        $setting = DB::table('persuratan_settings')->first();
        $path = $setting?->hdd_path ?: storage_path('app/arsip_persuratan');

        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    /**
     * Cek status konektivitas direktori HDD dan sisa kapasitas penyimpanan
     */
    public static function checkStatus(): array
    {
        $hddPath = self::getHddPath();
        $exists = is_dir($hddPath);
        $writable = false;

        if ($exists) {
            $writable = is_writable($hddPath);
        } else {
            // Coba buat folder jika belum ada
            try {
                @mkdir($hddPath, 0755, true);
                $exists = is_dir($hddPath);
                $writable = $exists && is_writable($hddPath);
            } catch (\Throwable $e) {
                $exists = false;
                $writable = false;
            }
        }

        $freeBytes = 0;
        $totalBytes = 0;

        if ($exists) {
            $freeBytes = @disk_free_space($hddPath) ?: 0;
            $totalBytes = @disk_total_space($hddPath) ?: 0;
        }

        $usedBytes = max(0, $totalBytes - $freeBytes);
        $percentUsed = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0;

        return [
            'path' => $hddPath,
            'exists' => $exists,
            'writable' => $writable,
            'is_ready' => $exists && $writable,
            'free_bytes' => $freeBytes,
            'total_bytes' => $totalBytes,
            'used_bytes' => $usedBytes,
            'free_formatted' => self::formatBytes($freeBytes),
            'total_formatted' => self::formatBytes($totalBytes),
            'used_formatted' => self::formatBytes($usedBytes),
            'percent_used' => $percentUsed,
        ];
    }

    /**
     * Pastikan struktur folder arsip di HDD siap digunakan
     */
    public static function ensureDirectories(): bool
    {
        $root = self::getHddPath();
        $curYear = date('Y');

        $folders = [
            $root,
            $root . DIRECTORY_SEPARATOR . 'Surat_Masuk',
            $root . DIRECTORY_SEPARATOR . 'Surat_Masuk' . DIRECTORY_SEPARATOR . $curYear,
            $root . DIRECTORY_SEPARATOR . 'Surat_Keluar',
            $root . DIRECTORY_SEPARATOR . 'Surat_Keluar' . DIRECTORY_SEPARATOR . $curYear,
            $root . DIRECTORY_SEPARATOR . 'Surat_Keterangan',
            $root . DIRECTORY_SEPARATOR . 'Surat_Keterangan' . DIRECTORY_SEPARATOR . $curYear,
        ];

        foreach ($folders as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Simpan berkas arsip yang diunggah pengguna langsung ke HDD komputer
     */
    public static function storeFile(UploadedFile $file, string $type = 'masuk'): array
    {
        self::ensureDirectories();

        $root = self::getHddPath();
        $year = date('Y');
        $subfolderName = match ($type) {
            'keluar' => 'Surat_Keluar',
            'keterangan' => 'Surat_Keterangan',
            default => 'Surat_Masuk',
        };

        $targetDir = $root . DIRECTORY_SEPARATOR . $subfolderName . DIRECTORY_SEPARATOR . $year;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $prefix = match ($type) {
            'keluar' => 'SK',
            'keterangan' => 'SKET',
            default => 'SM',
        };

        $newFileName = sprintf('%s_%s_%s.%s', $prefix, date('Ymd_His'), substr($safeName, 0, 30), $extension);
        $file->move($targetDir, $newFileName);

        $relativePath = $subfolderName . '/' . $year . '/' . $newFileName;
        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $newFileName;

        return [
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'file_name_original' => $originalName,
            'file_name_stored' => $newFileName,
            'file_size' => @filesize($fullPath) ?: $file->getSize(),
            'file_mime' => @mime_content_type($fullPath) ?: 'application/pdf',
        ];
    }

    /**
     * Dapatkan path absolut fisik di HDD secara aman (Anti Path-Traversal)
     */
    public static function resolveSafePath(string $relativePath): ?string
    {
        $root = self::getHddPath();
        $cleanedRelative = str_replace(['../', '..\\', '..'], '', $relativePath);
        $fullPath = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanedRelative);

        if (!file_exists($fullPath)) {
            // Cek juga kemungkinan relative path yang tersimpan di default storage legacy
            $legacyPath = storage_path('app/' . $cleanedRelative);
            if (file_exists($legacyPath)) {
                return $legacyPath;
            }
            return null;
        }

        return $fullPath;
    }

    /**
     * Render/stream dokumen (PDF / Gambar) langsung dari HDD ke browser
     */
    public static function streamFile(string $relativePath): BinaryFileResponse
    {
        $fullPath = self::resolveSafePath($relativePath);
        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'Berkas arsip fisik tidak ditemukan di direktori harddisk: ' . htmlspecialchars($relativePath));
        }

        $mimeType = @mime_content_type($fullPath) ?: 'application/pdf';
        $fileName = basename($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Unduh dokumen dari HDD
     */
    public static function downloadFile(string $relativePath, ?string $downloadName = null): BinaryFileResponse
    {
        $fullPath = self::resolveSafePath($relativePath);
        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'Berkas arsip fisik tidak ditemukan di direktori harddisk.');
        }

        $name = $downloadName ?: basename($fullPath);
        return response()->download($fullPath, $name);
    }

    /**
     * Hapus berkas fisik di HDD
     */
    public static function deleteFile(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $fullPath = self::resolveSafePath($relativePath);
        if ($fullPath && file_exists($fullPath)) {
            return @unlink($fullPath);
        }

        return false;
    }

    /**
     * Konversi angka bulan ke format Romawi (I - XII)
     */
    public static function getRomawiBulan(int $bulan): string
    {
        $romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romawi[$bulan] ?? 'I';
    }

    /**
     * Generate Nomor Surat Keluar Otomatis Berdasarkan Template Pengaturan
     */
    public static function generateNomorSuratKeluar(string $kodeIndeks, ?string $customFormat = null): string
    {
        $setting = DB::table('persuratan_settings')->first();
        $format = $customFormat ?: ($setting?->format_nomor_surat_keluar ?: '{nomor}/{kode_indeks}/{sekolah_kode}/{romawi_bulan}/{tahun}');
        $sekolahKode = $setting?->sekolah_kode ?: 'SMK-SAE';

        $curYear = (int) date('Y');
        $curMonth = (int) date('n');
        $lastYear = (int) ($setting?->tahun_terakhir ?: $curYear);

        $nextNumber = 1;
        if ($setting) {
            if ($curYear !== $lastYear) {
                // Reset nomor setiap pergantian tahun
                $nextNumber = 1;
            } else {
                $nextNumber = ((int) $setting->nomor_terakhir_surat_keluar) + 1;
            }
        }

        $formattedNumber = str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        $romawiBulan = self::getRomawiBulan($curMonth);

        $nomorSurat = str_replace(
            ['{nomor}', '{kode_indeks}', '{sekolah_singkatan}', '{sekolah_kode}', '{romawi_bulan}', '{bulan}', '{tahun}'],
            [$formattedNumber, $kodeIndeks, $sekolahKode, $sekolahKode, $romawiBulan, str_pad((string)$curMonth, 2, '0', STR_PAD_LEFT), $curYear],
            $format
        );

        return $nomorSurat;
    }

    /**
     * Generate Nomor Surat Keterangan Siswa Otomatis Berdasarkan Template Pengaturan
     */
    public static function generateNomorSuratKeterangan(?string $kodeIndeks = '421.5', ?string $customFormat = null): string
    {
        $setting = DB::table('persuratan_settings')->first();
        $format = $customFormat ?: ($setting?->format_nomor_surat_keterangan ?: '{kode_indeks}/{nomor}/{sekolah_kode}/{romawi_bulan}/{tahun}');
        $sekolahKode = $setting?->sekolah_kode ?: 'SMK-SAE';

        $curYear = (int) date('Y');
        $curMonth = (int) date('n');
        $lastYear = (int) ($setting?->tahun_terakhir ?: $curYear);

        $nextNumber = 1;
        if ($setting) {
            if ($curYear !== $lastYear) {
                $nextNumber = 1;
            } else {
                $nextNumber = ((int) $setting->nomor_terakhir_surat_keterangan) + 1;
            }
        }

        $formattedNumber = str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        $romawiBulan = self::getRomawiBulan($curMonth);
        $actualKodeIndeks = $kodeIndeks ?: '421.5';

        // Jika di setting format masih hardcoded '421.5' dan bukan placeholder {kode_indeks}
        if (!str_contains($format, '{kode_indeks}') && str_contains($format, '421.5')) {
            $format = str_replace('421.5', '{kode_indeks}', $format);
        }

        $nomorSurat = str_replace(
            ['{nomor}', '{kode_indeks}', '{sekolah_singkatan}', '{sekolah_kode}', '{romawi_bulan}', '{bulan}', '{tahun}'],
            [$formattedNumber, $actualKodeIndeks, $sekolahKode, $sekolahKode, $romawiBulan, str_pad((string)$curMonth, 2, '0', STR_PAD_LEFT), $curYear],
            $format
        );

        return $nomorSurat;
    }

    /**
     * Update Counter Nomor Terakhir Setelah Surat Resmi Disimpan
     */
    public static function incrementNomorCounter(string $type = 'keluar'): void
    {
        $setting = DB::table('persuratan_settings')->first();
        if (!$setting) return;

        $curYear = (int) date('Y');
        $field = ($type === 'keterangan') ? 'nomor_terakhir_surat_keterangan' : 'nomor_terakhir_surat_keluar';

        DB::table('persuratan_settings')->where('id', $setting->id)->update([
            $field => DB::raw($field . ' + 1'),
            'tahun_terakhir' => $curYear,
            'updated_at' => now(),
        ]);
    }

    /**
     * Format byte ke satuan yang mudah dibaca (KB, MB, GB, TB)
     */
    public static function formatBytes(float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $power = floor($base);

        return round(pow(1024, $base - $power), $precision) . ' ' . ($units[$power] ?? 'B');
    }
}
