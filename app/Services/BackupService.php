<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;

use App\Support\Table;

class BackupService
{
    /**
     * Hasilkan dump SQL LENGKAP (seluruh tabel database) saja.
     * Fokus database: ringkas, ringan, dan cepat tanpa media/CSV/JSON.
     *
     * @return array ['path' => string, 'filename' => string]
     */
    public function createSqlBackup(string $adminName = 'Admin'): array
    {
        [$npsn, $namaSekolah] = $this->schoolIdentity();

        $tempBase = storage_path('app/archives');
        if (!File::isDirectory($tempBase)) {
            File::makeDirectory($tempBase, 0755, true, true);
        }

        $slug = preg_replace('/[^A-Za-z0-9_\-]/', '', $npsn);
        $filename = 'SAE_Database_' . $slug . '_' . now()->format('Ymd_His') . '.sql';

        $workDir = $tempBase . '/sql_' . uniqid();
        File::makeDirectory($workDir, 0755, true, true);

        $this->generateSqlDump($workDir, $namaSekolah, $npsn, $adminName);

        $target = $tempBase . '/' . $filename;
        File::move($workDir . '/database_sae_lengkap.sql', $target);
        File::deleteDirectory($workDir);

        return ['path' => $target, 'filename' => $filename];
    }

    /**
     * Identitas sekolah (NPSN, nama) untuk penamaan berkas arsip.
     *
     * @return array{0:string,1:string} [$npsn, $namaSekolah]
     */
    private function schoolIdentity(): array
    {
        $sekolah = Table::exists('sekolah') ? DB::table('sekolah')->first() : null;

        return [
            (string) ($sekolah->npsn ?? 'Data'),
            (string) ($sekolah->nama ?? 'Satuan Pendidikan'),
        ];
    }

    /**
     * Menghasilkan Dump Database SQL standar MySQL murni (CREATE TABLE + INSERT INTO).
     */
    private function generateSqlDump(string $outputDir, string $namaSekolah, string $npsn, string $adminName): void
    {
        $pdo = DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();
        $tables = array_column(DB::select('SHOW TABLES'), 'Tables_in_' . $dbName);

        $sqlFile = $outputDir . '/database_sae_lengkap.sql';
        $handleFull = fopen($sqlFile, 'w');

        $header = "-- ==========================================================\n"
            . "-- SISTEM APLIKASI EDUKASI (SAE) - BACKUP DATABASE LENGKAP\n"
            . "-- Satuan Pendidikan : {$namaSekolah} (NPSN: {$npsn})\n"
            . "-- Waktu Ekspor      : " . now()->format('Y-m-d H:i:s') . " (WIB)\n"
            . "-- Operator Ekspor   : {$adminName}\n"
            . "-- Database Asal     : {$dbName}\n"
            . "-- Kompatibilitas    : MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+\n"
            . "-- ==========================================================\n\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n"
            . "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n"
            . "SET NAMES utf8mb4;\n"
            . "SET time_zone = '+07:00';\n\n";

        fwrite($handleFull, $header);

        foreach ($tables as $table) {
            // DDL Create Table
            $createRes = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql = $createRes[0]->{'Create Table'} ?? '';

            $tableSection = "-- --------------------------------------------------------\n"
                . "-- Struktur Tabel untuk `{$table}`\n"
                . "-- --------------------------------------------------------\n\n"
                . "DROP TABLE IF EXISTS `{$table}`;\n"
                . $createSql . ";\n\n";

            fwrite($handleFull, $tableSection);

            // DML Insert Data
            $count = DB::table($table)->count();
            if ($count > 0) {
                fwrite($handleFull, "-- Dumping data untuk tabel `{$table}` (Total: {$count} baris)\n");
                fwrite($handleFull, "LOCK TABLES `{$table}` WRITE;\n");
                fwrite($handleFull, "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n");

                $cols = DB::getSchemaBuilder()->getColumnListing($table);
                $colNamesEscaped = '`' . implode('`, `', $cols) . '`';

                // chunkById memakai kolom pertama (umumnya PRIMARY KEY) sebagai kursor,
                // menghindari risiko baris terlewat/duplikat akibat offset pagination.
                DB::table($table)->chunkById(250, function ($rows) use ($handleFull, $table, $cols, $colNamesEscaped, $pdo) {
                    $insertSql = "INSERT INTO `{$table}` ({$colNamesEscaped}) VALUES\n";
                    $valueRows = [];

                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($cols as $col) {
                            $val = $row->$col ?? null;
                            if ($val === null) {
                                $values[] = 'NULL';
                            } elseif (is_numeric($val) && !str_starts_with((string)$val, '0')) {
                                $values[] = $val;
                            } else {
                                $values[] = $pdo->quote((string)$val);
                            }
                        }
                        $valueRows[] = '(' . implode(', ', $values) . ')';
                    }

                    $insertSql .= implode(",\n", $valueRows) . ";\n";
                    fwrite($handleFull, $insertSql);
                }, $cols[0]);

                fwrite($handleFull, "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n");
                fwrite($handleFull, "UNLOCK TABLES;\n\n");
            }
        }

        $footer = "SET FOREIGN_KEY_CHECKS=1;\n"
            . "-- Selesai diekspor oleh SAE Backup Engine pada " . now()->format('Y-m-d H:i:s') . " (WIB)\n";

        fwrite($handleFull, $footer);
        fclose($handleFull);
    }
}
