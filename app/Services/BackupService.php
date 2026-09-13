<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use ZipArchive;
use PDO;

class BackupService
{
    /**
     * Hasilkan paket backup ZIP komprehensif berisi SQL dump, Excel CSV, berkas fisik, dan JSON.
     *
     * @param string $adminName
     * @return array ['zip_path' => string, 'filename' => string, 'manifest' => array]
     */
    public function createComprehensiveBackup(string $adminName = 'Admin'): array
    {
        $settings = DB::table('settings')->where('id', 1)->first();
        $sekolah  = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $npsn     = $sekolah->npsn ?? 'Data';
        $namaSekolah = $sekolah->nama ?? 'Satuan Pendidikan';

        $timestamp = date('Ymd_His');
        $zipFilename = 'SAE_Arsip_Backup_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $npsn) . '_' . $timestamp . '.zip';

        $tempBase = storage_path('app/archives');
        if (!File::isDirectory($tempBase)) {
            File::makeDirectory($tempBase, 0755, true, true);
        }

        $workDir = $tempBase . '/build_' . $timestamp . '_' . uniqid();
        File::makeDirectory($workDir, 0755, true, true);

        // Buat struktur subdirektori arsip
        $dirSql   = $workDir . '/01_DATABASE_SQL';
        $dirExcel = $workDir . '/02_DATA_EXCEL_CSV';
        $dirMedia = $workDir . '/03_BERKAS_MEDIA';
        $dirJson  = $workDir . '/04_RAW_JSON_BACKUP';

        File::makeDirectory($dirSql, 0755, true, true);
        File::makeDirectory($dirExcel, 0755, true, true);
        File::makeDirectory($dirMedia, 0755, true, true);
        File::makeDirectory($dirJson, 0755, true, true);

        // 1. Ekspor Database SQL Lengkap
        $this->generateSqlDump($dirSql, $namaSekolah, (string)$npsn, $adminName);

        // 2. Ekspor Spreadsheet Excel (CSV dengan UTF-8 BOM)
        $this->generateExcelCsvs($dirExcel);

        // 3. Salin Seluruh Aset Fisik & Berkas Media
        $mediaCounts = $this->copyMediaFiles($dirMedia);

        // 4. Ekspor JSON & Manifest
        $manifest = $this->generateJsonBackupAndManifest($dirJson, $namaSekolah, (string)$npsn, $adminName, $mediaCounts);

        // 5. Buat File Panduan README
        $this->createReadmeGuide($workDir, $namaSekolah, (string)$npsn, $adminName, $manifest);

        // 6. Kemas seluruh isi folder menjadi file .ZIP
        $zipPath = $tempBase . '/' . $zipFilename;
        $this->compressFolderToZip($workDir, $zipPath);

        // Bersihkan folder kerja sementara
        File::deleteDirectory($workDir);

        return [
            'zip_path' => $zipPath,
            'filename' => $zipFilename,
            'manifest' => $manifest,
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
        $schemaFile = $outputDir . '/database_sae_struktur_schema.sql';

        $handleFull = fopen($sqlFile, 'w');
        $handleSchema = fopen($schemaFile, 'w');

        $header = "-- ==========================================================\n"
            . "-- SISTEM APLIKASI EDUKASI (SAE) - BACKUP DATABASE LENGKAP\n"
            . "-- Satuan Pendidikan : {$namaSekolah} (NPSN: {$npsn})\n"
            . "-- Waktu Ekspor      : " . date('Y-m-d H:i:s') . "\n"
            . "-- Operator Ekspor   : {$adminName}\n"
            . "-- Database Asal     : {$dbName}\n"
            . "-- Kompatibilitas    : MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+\n"
            . "-- ==========================================================\n\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n"
            . "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n"
            . "SET NAMES utf8mb4;\n"
            . "SET time_zone = '+07:00';\n\n";

        fwrite($handleFull, $header);
        fwrite($handleSchema, $header);

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
            fwrite($handleSchema, $tableSection);

            // DML Insert Data
            $count = DB::table($table)->count();
            if ($count > 0) {
                fwrite($handleFull, "-- Dumping data untuk tabel `{$table}` (Total: {$count} baris)\n");
                fwrite($handleFull, "LOCK TABLES `{$table}` WRITE;\n");
                fwrite($handleFull, "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n");

                $cols = DB::getSchemaBuilder()->getColumnListing($table);
                $colNamesEscaped = '`' . implode('`, `', $cols) . '`';

                DB::table($table)->orderBy($cols[0])->chunk(250, function ($rows) use ($handleFull, $table, $cols, $colNamesEscaped, $pdo) {
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
                });

                fwrite($handleFull, "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n");
                fwrite($handleFull, "UNLOCK TABLES;\n\n");
            }
        }

        $footer = "SET FOREIGN_KEY_CHECKS=1;\n"
            . "-- Selesai diekspor oleh SAE Backup Engine pada " . date('Y-m-d H:i:s') . "\n";

        fwrite($handleFull, $footer);
        fwrite($handleSchema, $footer);

        fclose($handleFull);
        fclose($handleSchema);
    }

    /**
     * Menghasilkan file Excel CSV dengan UTF-8 BOM untuk 8 entitas utama sekolah.
     */
    private function generateExcelCsvs(string $outputDir): void
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 Byte Order Mark agar Microsoft Excel langsung membaca aksen & kolom rapi

        // 1. Peserta Didik Aktif
        if (Schema::hasTable('peserta_didik')) {
            $fp = fopen($outputDir . '/01_Data_Peserta_Didik_Aktif.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, [
                'No', 'Nama Lengkap', 'NISN', 'NIPD', 'NIK', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
                'Agama', 'Tingkat', 'Rombel', 'Kurikulum', 'Nama Ayah', 'Nama Ibu', 'Nama Wali',
                'No HP / Telepon', 'Email', 'Alamat Lengkap'
            ]);

            $no = 1;
            DB::table('peserta_didik')->orderBy('nama_rombel')->orderBy('nama')->chunk(200, function ($rows) use ($fp, &$no) {
                foreach ($rows as $r) {
                    fputcsv($fp, [
                        $no++,
                        $r->nama ?? '-',
                        $r->nisn ? "'" . $r->nisn : '-',
                        $r->nipd ? "'" . $r->nipd : '-',
                        $r->nik ? "'" . $r->nik : '-',
                        $r->jenis_kelamin === 'L' ? 'Laki-laki' : ($r->jenis_kelamin === 'P' ? 'Perempuan' : ($r->jenis_kelamin ?? '-')),
                        $r->tempat_lahir ?? '-',
                        $r->tanggal_lahir ?? '-',
                        $r->agama_id_str ?? '-',
                        $r->tingkat_pendidikan_id ?? '-',
                        $r->nama_rombel ?? '-',
                        $r->kurikulum_id_str ?? '-',
                        $r->nama_ayah ?? '-',
                        $r->nama_ibu ?? '-',
                        $r->nama_wali ?? '-',
                        $r->nomor_telepon_seluler ?: ($r->nomor_telepon_rumah ?? '-'),
                        $r->email ?? '-',
                        $r->alamat_jalan ?? '-'
                    ]);
                }
            });
            fclose($fp);
        }

        // 2. Peserta Didik Tidak Aktif / Alumni
        if (Schema::hasTable('peserta_didik_tidak_aktif')) {
            $fp = fopen($outputDir . '/02_Data_Alumni_Peserta_Didik_Tidak_Aktif.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, [
                'No', 'Status', 'Tahun Kelulusan / Keluar', 'Tanggal Keluar', 'Nama Lengkap', 'NISN', 'NIPD', 'NIK',
                'Jenis Kelamin', 'Rombel Terakhir', 'Tingkat Terakhir', 'Alasan Keluar', 'No HP', 'Alamat'
            ]);

            $no = 1;
            DB::table('peserta_didik_tidak_aktif')->orderByDesc('tahun_lulus')->orderBy('nama')->chunk(200, function ($rows) use ($fp, &$no) {
                foreach ($rows as $r) {
                    fputcsv($fp, [
                        $no++,
                        $r->status_keluar ?? 'Alumni',
                        $r->tahun_lulus ?? '-',
                        $r->tanggal_keluar ?? '-',
                        $r->nama ?? '-',
                        $r->nisn ? "'" . $r->nisn : '-',
                        $r->nipd ? "'" . $r->nipd : '-',
                        $r->nik ? "'" . $r->nik : '-',
                        $r->jenis_kelamin === 'L' ? 'Laki-laki' : ($r->jenis_kelamin === 'P' ? 'Perempuan' : ($r->jenis_kelamin ?? '-')),
                        $r->nama_rombel_terakhir ?? '-',
                        $r->tingkat_pendidikan_terakhir ?? '-',
                        $r->alasan_keluar ?? '-',
                        $r->nomor_telepon_seluler ?? '-',
                        $r->alamat_jalan ?? '-'
                    ]);
                }
            });
            fclose($fp);
        }

        // 3. Data GTK (Guru & Tenaga Kependidikan)
        if (Schema::hasTable('gtk')) {
            $fp = fopen($outputDir . '/03_Data_GTK_Guru_Tendik.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, [
                'No', 'Nama Lengkap', 'Gelar Depan', 'Gelar Belakang', 'Jenis PTK', 'Status Kepegawaian',
                'NUPTK', 'NIP', 'NIK', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'No HP', 'Email'
            ]);

            $no = 1;
            DB::table('gtk')->orderBy('nama')->chunk(200, function ($rows) use ($fp, &$no) {
                foreach ($rows as $r) {
                    fputcsv($fp, [
                        $no++,
                        $r->nama ?? '-',
                        $r->gelar_depan ?? '',
                        $r->gelar_belakang ?? '',
                        $r->jenis_ptk_id_str ?? '-',
                        $r->status_kepegawaian_id_str ?? '-',
                        $r->nuptk ? "'" . $r->nuptk : '-',
                        $r->nip ? "'" . $r->nip : '-',
                        $r->nik ? "'" . $r->nik : '-',
                        $r->jenis_kelamin === 'L' ? 'Laki-laki' : ($r->jenis_kelamin === 'P' ? 'Perempuan' : ($r->jenis_kelamin ?? '-')),
                        $r->tempat_lahir ?? '-',
                        $r->tanggal_lahir ?? '-',
                        $r->no_hp ?: ($r->no_telepon_rumah ?? '-'),
                        $r->email ?? '-'
                    ]);
                }
            });
            fclose($fp);
        }

        // 4. Rombongan Belajar (Rombel)
        if (Schema::hasTable('rombongan_belajar')) {
            $fp = fopen($outputDir . '/04_Data_Rombongan_Belajar.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, ['No', 'Nama Rombel', 'Tingkat', 'Jurusan / Peminatan', 'Kurikulum', 'ID PTK Wali Kelas']);

            $no = 1;
            $rombels = DB::table('rombongan_belajar')->orderBy('nama')->get();
            foreach ($rombels as $r) {
                fputcsv($fp, [
                    $no++,
                    $r->nama ?? '-',
                    $r->tingkat_pendidikan_id ?? '-',
                    $r->jurusan_id_str ?? '-',
                    $r->kurikulum_id_str ?? '-',
                    $r->ptk_id ?? '-'
                ]);
            }
            fclose($fp);
        }

        // 5. Anggota Rombel
        if (Schema::hasTable('anggota_rombel')) {
            $fp = fopen($outputDir . '/05_Data_Anggota_Rombel.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, ['No', 'ID Rombel', 'ID Siswa', 'Jenis Pendaftaran']);

            $no = 1;
            DB::table('anggota_rombel')->orderBy('rombongan_belajar_id')->chunk(300, function ($rows) use ($fp, &$no) {
                foreach ($rows as $r) {
                    fputcsv($fp, [
                        $no++,
                        $r->rombongan_belajar_id ?? '-',
                        $r->peserta_didik_id ?? '-',
                        $r->jenis_pendaftaran_id_str ?? '-'
                    ]);
                }
            });
            fclose($fp);
        }

        // 6. Pembelajaran & Jadwal
        if (Schema::hasTable('pembelajaran')) {
            $fp = fopen($outputDir . '/06_Data_Pembelajaran_Jadwal.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, ['No', 'ID Rombel', 'Mata Pelajaran', 'Nama PTK Guru', 'SK Mengajar', 'Tanggal SK']);

            $no = 1;
            DB::table('pembelajaran')->orderBy('rombongan_belajar_id')->chunk(200, function ($rows) use ($fp, &$no) {
                foreach ($rows as $r) {
                    fputcsv($fp, [
                        $no++,
                        $r->rombongan_belajar_id ?? '-',
                        $r->mata_pelajaran_id_str ?? ($r->nama_mata_pelajaran ?? '-'),
                        $r->nama_ptk ?? ($r->ptk_id ?? '-'),
                        $r->sk_mengajar ?? '-',
                        $r->tanggal_sk_mengajar ?? '-'
                    ]);
                }
            });
            fclose($fp);
        }

        // 7. Akun Pengguna
        if (Schema::hasTable('pengguna')) {
            $fp = fopen($outputDir . '/07_Data_Akun_Pengguna.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, ['No', 'Nama Pengguna', 'Username', 'Peran Akun', 'No HP', 'Alamat', 'Terdaftar Pada']);

            $no = 1;
            DB::table('pengguna')->orderBy('peran_id_str')->orderBy('nama')->chunk(200, function ($rows) use ($fp, &$no) {
                foreach ($rows as $r) {
                    fputcsv($fp, [
                        $no++,
                        $r->nama ?? '-',
                        $r->username ? "'" . $r->username : '-',
                        $r->peran_id_str ?? '-',
                        $r->no_hp ?? '-',
                        $r->alamat ?? '-',
                        $r->created_at ?? '-'
                    ]);
                }
            });
            fclose($fp);
        }

        // 8. Identitas Sekolah
        if (Schema::hasTable('sekolah')) {
            $fp = fopen($outputDir . '/08_Data_Identitas_Sekolah.csv', 'w');
            fwrite($fp, $bom);
            fputcsv($fp, ['Atribut', 'Nilai Informasi']);

            $sekolah = DB::table('sekolah')->first();
            if ($sekolah) {
                foreach ((array)$sekolah as $key => $val) {
                    if ($key === 'raw_data') continue;
                    fputcsv($fp, [strtoupper(str_replace('_', ' ', $key)), (string)($val ?? '-')]);
                }
            }
            fclose($fp);
        }
    }

    /**
     * Salin seluruh berkas media fisik (foto siswa, logo sekolah, kop surat, logo jurusan).
     */
    private function copyMediaFiles(string $outputDir): array
    {
        $counts = [
            'foto_peserta_didik' => 0,
            'sekolah' => 0,
            'jurusan' => 0,
            'dokumen' => 0,
        ];

        // 1. Foto Peserta Didik
        $fotoDir = storage_path('app/public/assets/peserta-didik/foto');
        $targetFoto = $outputDir . '/foto_peserta_didik';
        if (File::isDirectory($fotoDir)) {
            File::makeDirectory($targetFoto, 0755, true, true);
            $files = File::files($fotoDir);
            foreach ($files as $f) {
                File::copy($f->getRealPath(), $targetFoto . '/' . $f->getFilename());
                $counts['foto_peserta_didik']++;
            }
        }

        // 2. Berkas & Identitas Sekolah (Logo, Kop Surat, Stempel)
        $sekolahDir = storage_path('app/public/assets/sekolah');
        $targetSekolah = $outputDir . '/sekolah';
        if (File::isDirectory($sekolahDir)) {
            File::makeDirectory($targetSekolah, 0755, true, true);
            $files = File::files($sekolahDir);
            foreach ($files as $f) {
                File::copy($f->getRealPath(), $targetSekolah . '/' . $f->getFilename());
                $counts['sekolah']++;
            }
        }

        // 3. Logo Jurusan
        $jurusanDir = storage_path('app/public/assets/jurusan');
        $targetJurusan = $outputDir . '/logo_jurusan';
        if (File::isDirectory($jurusanDir)) {
            File::makeDirectory($targetJurusan, 0755, true, true);
            $files = File::files($jurusanDir);
            foreach ($files as $f) {
                File::copy($f->getRealPath(), $targetJurusan . '/' . $f->getFilename());
                $counts['jurusan']++;
            }
        }

        // 4. Dokumen Lainnya jika ada di storage/app/public/dokumen
        $docDir = storage_path('app/public/dokumen');
        $targetDoc = $outputDir . '/dokumen_berkas';
        if (File::isDirectory($docDir)) {
            File::makeDirectory($targetDoc, 0755, true, true);
            $files = File::allFiles($docDir);
            foreach ($files as $f) {
                File::copy($f->getRealPath(), $targetDoc . '/' . $f->getFilename());
                $counts['dokumen']++;
            }
        }

        return $counts;
    }

    /**
     * Ekspor seluruh tabel ke format JSON dan hasilkan manifest.json.
     */
    private function generateJsonBackupAndManifest(string $outputDir, string $namaSekolah, string $npsn, string $adminName, array $mediaCounts): array
    {
        $dbName = DB::connection()->getDatabaseName();
        $tables = array_column(DB::select('SHOW TABLES'), 'Tables_in_' . $dbName);
        $summaryCounts = [];

        foreach ($tables as $table) {
            $rows = DB::table($table)->get()->toArray();
            $summaryCounts[$table] = count($rows);
            File::put($outputDir . '/' . $table . '.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $manifest = [
            'app_name' => 'SAE - Sistem Aplikasi Edukasi',
            'app_version' => '1.0.0',
            'sekolah' => [
                'nama' => $namaSekolah,
                'npsn' => $npsn,
            ],
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => $adminName,
            'summary_database' => $summaryCounts,
            'summary_media' => $mediaCounts,
            'format_version' => '2.0_comprehensive',
            'keterangan' => 'Paket arsip terpadu data sekolah: Database SQL, Spreadsheet Excel CSV, Aset Foto/Berkas Fisik, dan Raw JSON.',
        ];

        File::put($outputDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $manifest;
    }

    /**
     * Tulis panduan lengkap berbahasa Indonesia README_PETUNJUK_PENGGUNAAN_ARSIP.txt.
     */
    private function createReadmeGuide(string $workDir, string $namaSekolah, string $npsn, string $adminName, array $manifest): void
    {
        $readme = "================================================================================\n"
            . "PETUNJUK PENGGUNAAN PAKET ARSIP DATA SISTEM APLIKASI EDUKASI (SAE)\n"
            . "================================================================================\n\n"
            . "Satuan Pendidikan : {$namaSekolah}\n"
            . "NPSN              : {$npsn}\n"
            . "Waktu Pengarsipan : " . ($manifest['exported_at'] ?? date('Y-m-d H:i:s')) . "\n"
            . "Operator Pengarsip: {$adminName}\n\n"
            . "Arsip ini dibuat sebagai rekam jejak digital satuan pendidikan sebelum pergantian\n"
            . "tahun pelajaran, sinkronisasi Dapodik baru, atau pemeliharaan sistem berkala.\n\n"
            . "--------------------------------------------------------------------------------\n"
            . "STRUKTUR FOLDER & CARA MEMBUKA DATA:\n"
            . "--------------------------------------------------------------------------------\n\n"
            . "[1] 01_DATABASE_SQL/\n"
            . "    Berisi file dump database MySQL lengkap:\n"
            . "    - database_sae_lengkap.sql : Berisi seluruh tabel, relasi, dan isi data.\n"
            . "    - database_sae_struktur_schema.sql : Berisi struktur tabel kosong (DDL).\n\n"
            . "    CARA MEMULIHKAN DATABASE (RESTORE):\n"
            . "    - Buka phpMyAdmin atau Laragon Database Manager (HeidiSQL/MySQL Workbench).\n"
            . "    - Buat database baru atau pilih database target.\n"
            . "    - Klik menu 'Import', pilih file 'database_sae_lengkap.sql', lalu klik 'Kirim / Go'.\n"
            . "    - Seluruh tabel dan transaksi akan pulih seperti sedia kala.\n\n"
            . "[2] 02_DATA_EXCEL_CSV/\n"
            . "    Berisi file spreadsheet yang DAPAT LANGSUNG DIBUKA DI MICROSOFT EXCEL:\n"
            . "    - 01_Data_Peserta_Didik_Aktif.csv : Daftar siswa aktif lengkap dengan rombel & kontak.\n"
            . "    - 02_Data_Alumni_Peserta_Didik_Tidak_Aktif.csv : Daftar siswa lulus (alumni) & mutasi.\n"
            . "    - 03_Data_GTK_Guru_Tendik.csv : Data guru dan tenaga kependidikan.\n"
            . "    - 04_Data_Rombongan_Belajar.csv : Daftar rombel dan wali kelas.\n"
            . "    - 05_Data_Anggota_Rombel.csv : Pemetaan siswa ke rombel masing-masing.\n"
            . "    - 06_Data_Pembelajaran_Jadwal.csv : Daftar mapel dan SK mengajar guru.\n"
            . "    - 07_Data_Akun_Pengguna.csv : Daftar akun login pengguna sistem.\n"
            . "    - 08_Data_Identitas_Sekolah.csv : Data profil satuan pendidikan.\n\n"
            . "    CATATAN: File sudah dienkode dengan UTF-8 BOM, cukup klik 2x pada file untuk\n"
            . "    langsung membuka di Microsoft Excel dengan kolom yang rapi.\n\n"
            . "[3] 03_BERKAS_MEDIA/\n"
            . "    Menyimpan seluruh aset fisik asli:\n"
            . "    - foto_peserta_didik/ : Pasfoto siswa aktif dan alumni untuk kartu pelajar.\n"
            . "    - sekolah/ : Logo sekolah, stempel digital, dan kop surat resmi.\n"
            . "    - logo_jurusan/ : Logo kompetensi/program keahlian SMK.\n\n"
            . "[4] 04_RAW_JSON_BACKUP/\n"
            . "    Berisi data mentah JSON per tabel dan manifest.json untuk keperluan otomasi\n"
            . "    atau integrasi antar-sistem developer.\n\n"
            . "================================================================================\n"
            . "Dihasilkan secara otomatis oleh SAE Backup & Archival Engine v2.0\n"
            . "================================================================================\n";

        File::put($workDir . '/README_PETUNJUK_PENGGUNAAN_ARSIP.txt', $readme);
    }

    /**
     * Kompresi direktori kerja ke dalam file ZIP.
     */
    private function compressFolderToZip(string $folderPath, string $zipPath): bool
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($files as $file) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath) + 1);
                    $relativePath = str_replace('\\', '/', $relativePath);

                    if ($file->isDir()) {
                        $zip->addEmptyDir($relativePath);
                    } elseif ($file->isFile()) {
                        $zip->addFile($filePath, $relativePath);
                    }
                }

                $zip->close();
                return true;
            }
        }

        // Fallback jika ekstensi ZipArchive tidak aktif
        $fileList = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getRealPath(), strlen($folderPath) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                $fileList[$relativePath] = file_get_contents($file->getRealPath());
            }
        }

        return $this->createPurePhpZip($zipPath, $fileList);
    }

    /**
     * Generator ZIP murni PHP 100% fail-safe.
     */
    private function createPurePhpZip(string $zipPath, array $files): bool
    {
        $zipData = '';
        $centralDir = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $uncompressedSize = strlen($content);
            $crc = crc32($content);
            $hasGz = function_exists('gzdeflate');
            $compressed = $hasGz ? gzdeflate($content) : $content;
            $compressedSize = strlen($compressed);
            $method = $hasGz ? "\x08\x00" : "\x00\x00";

            $time = time();
            $dtime = dechex((date('Y', $time) - 1980) << 25 | date('m', $time) << 21 | date('d', $time) << 16 |
                date('H', $time) << 11 | date('i', $time) << 5 | date('s', $time) >> 1);
            $dtime = str_pad($dtime, 8, '0', STR_PAD_LEFT);
            $hexdtime = chr(hexdec(substr($dtime, 6, 2))) . chr(hexdec(substr($dtime, 4, 2))) .
                chr(hexdec(substr($dtime, 2, 2))) . chr(hexdec(substr($dtime, 0, 2)));

            $fr = "\x50\x4b\x03\x04\x14\x00\x00\x00" . $method . $hexdtime
                . pack('V', $crc)
                . pack('V', $compressedSize)
                . pack('V', $uncompressedSize)
                . pack('v', strlen($name))
                . pack('v', 0)
                . $name
                . $compressed;

            $zipData .= $fr;

            $cdrec = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00" . $method . $hexdtime
                . pack('V', $crc)
                . pack('V', $compressedSize)
                . pack('V', $uncompressedSize)
                . pack('v', strlen($name))
                . pack('v', 0)
                . pack('v', 0)
                . pack('v', 0)
                . pack('v', 0)
                . pack('V', 32)
                . pack('V', $offset)
                . $name;

            $centralDir .= $cdrec;
            $offset = strlen($zipData);
        }

        $endOfCentral = "\x50\x4b\x05\x06\x00\x00\x00\x00"
            . pack('v', count($files))
            . pack('v', count($files))
            . pack('V', strlen($centralDir))
            . pack('V', $offset)
            . "\x00\x00";

        return (bool) file_put_contents($zipPath, $zipData . $centralDir . $endOfCentral);
    }
}
