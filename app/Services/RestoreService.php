<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Pemulihan (restore) database dari berkas arsip .sql atau .zip paket SAE.
 *
 * Catatan: Menerima dump SQL lengkap (database_sae_lengkap.sql) maupun ZIP paket
 * arsip (01_DATABASE_SQL/database_sae_lengkap.sql). Statement dieksekusi apa adanya
 * lewat koneksi default sehingga DDL (DROP/CREATE TABLE) langsung berlaku.
 */
class RestoreService
{
    /**
     * Pulihkan database dari berkas yang diunggah.
     *
     * @return array{filename:string, tables:int, snapshot:string}
     */
    public function restore(UploadedFile $file): array
    {
        $tempBase = storage_path('app/restore');
        if (!File::isDirectory($tempBase)) {
            File::makeDirectory($tempBase, 0755, true, true);
        }

        $workDir = $tempBase . '/' . uniqid();
        File::makeDirectory($workDir, 0755, true, true);

        try {
            $sqlPath = $this->extractSql($file, $workDir);
            if (!$sqlPath || !File::exists($sqlPath)) {
                throw new RuntimeException('Berkas arsip tidak memuat file .sql yang dapat dipulihkan.');
            }

            // Snapshot pengaman sebelum menimpa database agar tetap bisa dikembalikan.
            $snapshotName = 'SEBELUM_RESTORE_' . now()->format('Ymd_His') . '.sql';
            $snapshotPath = storage_path('app/archives/' . $snapshotName);
            if (!File::isDirectory(storage_path('app/archives'))) {
                File::makeDirectory(storage_path('app/archives'), 0755, true, true);
            }
            try {
                $pre = (new BackupService())->createSqlBackup('Auto (pra-restore)');
                File::move($pre['path'], $snapshotPath);
            } catch (\Throwable $e) {
                $snapshotName = null; // lanjut tanpa snapshot bila dump pra-restore gagal
            }

            $sql = (string) file_get_contents($sqlPath);
            // Buang UTF-8 BOM bila ada agar statement pertama tidak rusak.
            if (str_starts_with($sql, "\xEF\xBB\xBF")) {
                $sql = substr($sql, 3);
            }

            if (trim($sql) === '') {
                throw new RuntimeException('File SQL kosong.');
            }

            DB::unprepared($sql);

            return [
                'filename' => $file->getClientOriginalName(),
                'tables' => count(DB::select('SHOW TABLES')),
                'snapshot' => $snapshotName,
            ];
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /**
     * Ambil path file .sql dari unggahan. ZIP akan diekstrak terlebih dahulu.
     */
    private function extractSql(UploadedFile $file, string $workDir): ?string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());

        if ($ext === 'zip') {
            if (!class_exists('ZipArchive')) {
                throw new RuntimeException('Ekstensi PHP ZipArchive tidak aktif, tidak dapat membuka arsip ZIP.');
            }

            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                throw new RuntimeException('Gagal membuka berkas ZIP (rusak atau bukan arsip valid).');
            }

            $zip->extractTo($workDir);
            $zip->close();

            return $this->findSqlFile($workDir);
        }

        // SQL / TXT mentah: langsung disalin ke folder kerja.
        $target = $workDir . '/upload.sql';
        if (!copy($file->getRealPath(), $target)) {
            throw new RuntimeException('Gagal membaca berkas yang diunggah.');
        }

        return $target;
    }

    /**
     * Cari file .sql di dalam folder hasil ekstraksi (prioritas nama mengandung "lengkap").
     */
    private function findSqlFile(string $dir): ?string
    {
        $candidates = array_values(array_filter(
            File::allFiles($dir),
            fn ($f) => strtolower($f->getExtension()) === 'sql'
        ));

        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $f) {
            if (stripos($f->getFilename(), 'lengkap') !== false) {
                return $f->getPathname();
            }
        }

        return $candidates[0]->getPathname();
    }
}
