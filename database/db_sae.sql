-- ========================================================
-- SAE (Sistem Aplikasi Edukasi) - Database Schema
-- Standard: Pure Dapodik UUID Primary Keys & Column Parity
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. Tabel settings (Konfigurasi Aplikasi)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `app_name` varchar(255) DEFAULT 'SAE - Sistem Aplikasi Edukasi',
  `site_name` varchar(255) DEFAULT 'SAE - Sistem Aplikasi Edukasi',
  `api_key` varchar(191) NOT NULL DEFAULT 'sae_secret_live_key_2026',
  `dapodik_url` varchar(255) DEFAULT 'http://localhost:5774',
  `last_sync` datetime DEFAULT NULL,
  `maintenance_status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `app_name`, `site_name`, `api_key`, `dapodik_url`, `last_sync`, `maintenance_status`, `created_at`, `updated_at`) VALUES
(1, 'SAE - Sistem Aplikasi Edukasi', 'SAE - Sistem Aplikasi Edukasi', 'sae_secret_live_key_2026', 'http://localhost:5774', NULL, 0, NOW(), NOW());

-- --------------------------------------------------------
-- 2. Tabel sekolah (Endpoint: getSekolah)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sekolah`;
CREATE TABLE `sekolah` (
  `sekolah_id` varchar(50) NOT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `nss` varchar(50) DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `bentuk_pendidikan_id` varchar(10) DEFAULT NULL,
  `bentuk_pendidikan_id_str` varchar(100) DEFAULT NULL,
  `status_sekolah` varchar(10) DEFAULT NULL,
  `status_sekolah_str` varchar(100) DEFAULT NULL,
  `alamat_jalan` text DEFAULT NULL,
  `rt` varchar(10) DEFAULT NULL,
  `rw` varchar(10) DEFAULT NULL,
  `dusun` varchar(100) DEFAULT NULL,
  `desa_kelurahan` varchar(100) DEFAULT NULL,
  `kode_wilayah` varchar(20) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `lintang` varchar(50) DEFAULT NULL,
  `bujur` varchar(50) DEFAULT NULL,
  `nomor_telepon` varchar(50) DEFAULT NULL,
  `nomor_fax` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `is_sks` varchar(10) DEFAULT '0',
  `kecamatan` varchar(100) DEFAULT NULL,
  `kabupaten_kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sekolah_id`),
  KEY `sekolah_npsn_index` (`npsn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Sekolah
DROP TABLE IF EXISTS `backup_sekolah`;
CREATE TABLE `backup_sekolah` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sekolah_id` varchar(50) NOT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `nss` varchar(50) DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `bentuk_pendidikan_id` varchar(10) DEFAULT NULL,
  `bentuk_pendidikan_id_str` varchar(100) DEFAULT NULL,
  `status_sekolah` varchar(10) DEFAULT NULL,
  `status_sekolah_str` varchar(100) DEFAULT NULL,
  `alamat_jalan` text DEFAULT NULL,
  `rt` varchar(10) DEFAULT NULL,
  `rw` varchar(10) DEFAULT NULL,
  `dusun` varchar(100) DEFAULT NULL,
  `desa_kelurahan` varchar(100) DEFAULT NULL,
  `kode_wilayah` varchar(20) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `lintang` varchar(50) DEFAULT NULL,
  `bujur` varchar(50) DEFAULT NULL,
  `nomor_telepon` varchar(50) DEFAULT NULL,
  `nomor_fax` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `is_sks` varchar(10) DEFAULT '0',
  `kecamatan` varchar(100) DEFAULT NULL,
  `kabupaten_kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_sekolah_sekolah_id_index` (`sekolah_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel gtk (Endpoint: getGtk)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `gtk`;
CREATE TABLE `gtk` (
  `ptk_id` varchar(50) NOT NULL,
  `tahun_ajaran_id` varchar(10) DEFAULT NULL,
  `ptk_terdaftar_id` varchar(50) DEFAULT NULL,
  `ptk_induk` varchar(10) DEFAULT NULL,
  `tanggal_surat_tugas` varchar(30) DEFAULT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` varchar(30) DEFAULT NULL,
  `agama_id` int(11) DEFAULT NULL,
  `agama_id_str` varchar(50) DEFAULT NULL,
  `nuptk` varchar(30) DEFAULT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `jenis_ptk_id` varchar(10) DEFAULT NULL,
  `jenis_ptk_id_str` varchar(100) DEFAULT NULL,
  `jabatan_ptk_id` varchar(20) DEFAULT NULL,
  `jabatan_ptk_id_str` varchar(200) DEFAULT NULL,
  `status_kepegawaian_id` varchar(20) DEFAULT NULL,
  `status_kepegawaian_id_str` varchar(100) DEFAULT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `pendidikan_terakhir` varchar(50) DEFAULT NULL,
  `bidang_studi_terakhir` varchar(200) DEFAULT NULL,
  `pangkat_golongan_terakhir` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `alamat_jalan` text DEFAULT NULL,
  `rwy_pend_formal` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `rwy_kepangkatan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ptk_id`),
  KEY `gtk_ptk_terdaftar_id_index` (`ptk_terdaftar_id`),
  KEY `gtk_nik_index` (`nik`),
  KEY `gtk_nip_index` (`nip`),
  KEY `gtk_nuptk_index` (`nuptk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup GTK
DROP TABLE IF EXISTS `backup_gtk`;
CREATE TABLE `backup_gtk` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ptk_id` varchar(50) NOT NULL,
  `tahun_ajaran_id` varchar(10) DEFAULT NULL,
  `ptk_terdaftar_id` varchar(50) DEFAULT NULL,
  `ptk_induk` varchar(10) DEFAULT NULL,
  `tanggal_surat_tugas` varchar(30) DEFAULT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` varchar(30) DEFAULT NULL,
  `agama_id` int(11) DEFAULT NULL,
  `agama_id_str` varchar(50) DEFAULT NULL,
  `nuptk` varchar(30) DEFAULT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `jenis_ptk_id` varchar(10) DEFAULT NULL,
  `jenis_ptk_id_str` varchar(100) DEFAULT NULL,
  `jabatan_ptk_id` varchar(20) DEFAULT NULL,
  `jabatan_ptk_id_str` varchar(200) DEFAULT NULL,
  `status_kepegawaian_id` varchar(20) DEFAULT NULL,
  `status_kepegawaian_id_str` varchar(100) DEFAULT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `pendidikan_terakhir` varchar(50) DEFAULT NULL,
  `bidang_studi_terakhir` varchar(200) DEFAULT NULL,
  `pangkat_golongan_terakhir` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `alamat_jalan` text DEFAULT NULL,
  `rwy_pend_formal` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `rwy_kepangkatan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_gtk_ptk_id_index` (`ptk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel rombongan_belajar (Endpoint: getRombonganBelajar)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `rombongan_belajar`;
CREATE TABLE `rombongan_belajar` (
  `rombongan_belajar_id` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tingkat_pendidikan_id` varchar(10) DEFAULT NULL,
  `tingkat_pendidikan_id_str` varchar(50) DEFAULT NULL,
  `semester_id` varchar(10) DEFAULT NULL,
  `jenis_rombel` varchar(10) DEFAULT NULL,
  `jenis_rombel_str` varchar(50) DEFAULT NULL,
  `kurikulum_id` varchar(20) DEFAULT NULL,
  `kurikulum_id_str` varchar(200) DEFAULT NULL,
  `id_ruang` varchar(50) DEFAULT NULL,
  `id_ruang_str` varchar(100) DEFAULT NULL,
  `moving_class` varchar(20) DEFAULT NULL,
  `ptk_id` varchar(50) DEFAULT NULL,
  `ptk_id_str` varchar(200) DEFAULT NULL,
  `jurusan_id` varchar(20) DEFAULT NULL,
  `jurusan_id_str` varchar(200) DEFAULT NULL,
  `anggota_rombel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `pembelajaran` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`rombongan_belajar_id`),
  KEY `rombongan_belajar_tingkat_index` (`tingkat_pendidikan_id`),
  KEY `rombongan_belajar_semester_index` (`semester_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Rombongan Belajar
DROP TABLE IF EXISTS `backup_rombongan_belajar`;
CREATE TABLE `backup_rombongan_belajar` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `rombongan_belajar_id` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tingkat_pendidikan_id` varchar(10) DEFAULT NULL,
  `tingkat_pendidikan_id_str` varchar(50) DEFAULT NULL,
  `semester_id` varchar(10) DEFAULT NULL,
  `jenis_rombel` varchar(10) DEFAULT NULL,
  `jenis_rombel_str` varchar(50) DEFAULT NULL,
  `kurikulum_id` varchar(20) DEFAULT NULL,
  `kurikulum_id_str` varchar(200) DEFAULT NULL,
  `id_ruang` varchar(50) DEFAULT NULL,
  `id_ruang_str` varchar(100) DEFAULT NULL,
  `moving_class` varchar(20) DEFAULT NULL,
  `ptk_id` varchar(50) DEFAULT NULL,
  `ptk_id_str` varchar(200) DEFAULT NULL,
  `jurusan_id` varchar(20) DEFAULT NULL,
  `jurusan_id_str` varchar(200) DEFAULT NULL,
  `anggota_rombel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `pembelajaran` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_rombel_id_index` (`rombongan_belajar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel peserta_didik (Endpoint: getPesertaDidik)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `peserta_didik`;
CREATE TABLE `peserta_didik` (
  `peserta_didik_id` varchar(50) NOT NULL,
  `registrasi_id` varchar(50) DEFAULT NULL,
  `jenis_pendaftaran_id` varchar(10) DEFAULT NULL,
  `jenis_pendaftaran_id_str` varchar(100) DEFAULT NULL,
  `nipd` varchar(50) DEFAULT NULL,
  `tanggal_masuk_sekolah` varchar(30) DEFAULT NULL,
  `sekolah_asal` varchar(200) DEFAULT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` varchar(30) DEFAULT NULL,
  `agama_id` int(11) DEFAULT NULL,
  `agama_id_str` varchar(50) DEFAULT NULL,
  `nomor_telepon_rumah` varchar(30) DEFAULT NULL,
  `nomor_telepon_seluler` varchar(30) DEFAULT NULL,
  `nama_ayah` varchar(200) DEFAULT NULL,
  `pekerjaan_ayah_id` int(11) DEFAULT NULL,
  `pekerjaan_ayah_id_str` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(200) DEFAULT NULL,
  `pekerjaan_ibu_id` int(11) DEFAULT NULL,
  `pekerjaan_ibu_id_str` varchar(100) DEFAULT NULL,
  `nama_wali` varchar(200) DEFAULT NULL,
  `pekerjaan_wali_id` int(11) DEFAULT NULL,
  `pekerjaan_wali_id_str` varchar(100) DEFAULT NULL,
  `anak_keberapa` varchar(10) DEFAULT NULL,
  `tinggi_badan` varchar(10) DEFAULT NULL,
  `berat_badan` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `semester_id` varchar(10) DEFAULT NULL,
  `anggota_rombel_id` varchar(50) DEFAULT NULL,
  `rombongan_belajar_id` varchar(50) DEFAULT NULL,
  `tingkat_pendidikan_id` varchar(10) DEFAULT NULL,
  `nama_rombel` varchar(100) DEFAULT NULL,
  `kurikulum_id` varchar(20) DEFAULT NULL,
  `kurikulum_id_str` varchar(200) DEFAULT NULL,
  `kebutuhan_khusus` varchar(100) DEFAULT NULL,
  `alamat_jalan` text DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`peserta_didik_id`),
  KEY `peserta_didik_registrasi_id_index` (`registrasi_id`),
  KEY `peserta_didik_nisn_index` (`nisn`),
  KEY `peserta_didik_nik_index` (`nik`),
  KEY `peserta_didik_nipd_index` (`nipd`),
  KEY `peserta_didik_rombongan_belajar_id_index` (`rombongan_belajar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Peserta Didik
DROP TABLE IF EXISTS `backup_peserta_didik`;
CREATE TABLE `backup_peserta_didik` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `peserta_didik_id` varchar(50) NOT NULL,
  `registrasi_id` varchar(50) DEFAULT NULL,
  `jenis_pendaftaran_id` varchar(10) DEFAULT NULL,
  `jenis_pendaftaran_id_str` varchar(100) DEFAULT NULL,
  `nipd` varchar(50) DEFAULT NULL,
  `tanggal_masuk_sekolah` varchar(30) DEFAULT NULL,
  `sekolah_asal` varchar(200) DEFAULT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` varchar(30) DEFAULT NULL,
  `agama_id` int(11) DEFAULT NULL,
  `agama_id_str` varchar(50) DEFAULT NULL,
  `nomor_telepon_rumah` varchar(30) DEFAULT NULL,
  `nomor_telepon_seluler` varchar(30) DEFAULT NULL,
  `nama_ayah` varchar(200) DEFAULT NULL,
  `pekerjaan_ayah_id` int(11) DEFAULT NULL,
  `pekerjaan_ayah_id_str` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(200) DEFAULT NULL,
  `pekerjaan_ibu_id` int(11) DEFAULT NULL,
  `pekerjaan_ibu_id_str` varchar(100) DEFAULT NULL,
  `nama_wali` varchar(200) DEFAULT NULL,
  `pekerjaan_wali_id` int(11) DEFAULT NULL,
  `pekerjaan_wali_id_str` varchar(100) DEFAULT NULL,
  `anak_keberapa` varchar(10) DEFAULT NULL,
  `tinggi_badan` varchar(10) DEFAULT NULL,
  `berat_badan` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `semester_id` varchar(10) DEFAULT NULL,
  `anggota_rombel_id` varchar(50) DEFAULT NULL,
  `rombongan_belajar_id` varchar(50) DEFAULT NULL,
  `tingkat_pendidikan_id` varchar(10) DEFAULT NULL,
  `nama_rombel` varchar(100) DEFAULT NULL,
  `kurikulum_id` varchar(20) DEFAULT NULL,
  `kurikulum_id_str` varchar(200) DEFAULT NULL,
  `kebutuhan_khusus` varchar(100) DEFAULT NULL,
  `alamat_jalan` text DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_pd_peserta_didik_id_index` (`peserta_didik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel pengguna (Endpoint: getPengguna + Akun Sistem)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
  `pengguna_id` varchar(50) NOT NULL,
  `sekolah_id` varchar(50) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `peran_id_str` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(30) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `ptk_id` varchar(50) DEFAULT NULL,
  `peserta_didik_id` varchar(50) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pengguna_id`),
  UNIQUE KEY `pengguna_username_unique` (`username`),
  KEY `pengguna_ptk_id_index` (`ptk_id`),
  KEY `pengguna_peserta_didik_id_index` (`peserta_didik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Pengguna
DROP TABLE IF EXISTS `backup_pengguna`;
CREATE TABLE `backup_pengguna` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengguna_id` varchar(50) NOT NULL,
  `sekolah_id` varchar(50) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `peran_id_str` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(30) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `ptk_id` varchar(50) DEFAULT NULL,
  `peserta_didik_id` varchar(50) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_pengguna_id_index` (`pengguna_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Akun Default Bawaan (Admin, Guru, Siswa)
--
INSERT INTO `pengguna` (`pengguna_id`, `sekolah_id`, `username`, `nama`, `peran_id_str`, `password`, `alamat`, `no_telepon`, `no_hp`, `ptk_id`, `peserta_didik_id`, `created_at`, `updated_at`) VALUES
('seed-admin-1', NULL, 'admin@sae.id', 'Administrator', 'Administrator', '$2y$10$bJHqW8gj8/sxtvKE6QaapeYgsQ81hKPubLG.DpmQH/OdTMl59Kk.6', NULL, NULL, NULL, NULL, NULL, NOW(), NOW()),
('seed-gtk-1', NULL, 'gtk@sae.id', 'Guru dan Tendik', 'PTK', '$2y$10$ukN4A7SdLXxrTiFFDqDUxukoaFTCbVvH05H4aRwzXaISz5haQiV3G', NULL, NULL, NULL, NULL, NULL, NOW(), NOW()),
('seed-siswa-1', NULL, 'siswa@sae.id', 'Siswa', 'Peserta Didik', '$2y$10$8nvAOuy6tNLf/gv9GwJ5yuqsyNJ7j7d9RDPYfE47Mp506OYUl10kW', NULL, NULL, NULL, NULL, NULL, NOW(), NOW());

-- --------------------------------------------------------
-- 7. Tabel Relasi Ekstra Dapodik (Anggota Rombel & Pembelajaran)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `anggota_rombel`;
CREATE TABLE `anggota_rombel` (
  `anggota_rombel_id` varchar(50) NOT NULL,
  `rombongan_belajar_id` varchar(50) DEFAULT NULL,
  `peserta_didik_id` varchar(50) DEFAULT NULL,
  `jenis_pendaftaran_id` varchar(10) DEFAULT NULL,
  `jenis_pendaftaran_id_str` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`anggota_rombel_id`),
  KEY `ar_rombel_index` (`rombongan_belajar_id`),
  KEY `ar_pd_index` (`peserta_didik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Anggota Rombel
DROP TABLE IF EXISTS `backup_anggota_rombel`;
CREATE TABLE `backup_anggota_rombel` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `anggota_rombel_id` varchar(50) NOT NULL,
  `rombongan_belajar_id` varchar(50) DEFAULT NULL,
  `peserta_didik_id` varchar(50) DEFAULT NULL,
  `jenis_pendaftaran_id` varchar(10) DEFAULT NULL,
  `jenis_pendaftaran_id_str` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_ar_id_index` (`anggota_rombel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pembelajaran`;
CREATE TABLE `pembelajaran` (
  `pembelajaran_id` varchar(50) NOT NULL,
  `rombongan_belajar_id` varchar(50) DEFAULT NULL,
  `mata_pelajaran_id` varchar(50) DEFAULT NULL,
  `mata_pelajaran_id_str` varchar(200) DEFAULT NULL,
  `ptk_terdaftar_id` varchar(50) DEFAULT NULL,
  `ptk_id` varchar(50) DEFAULT NULL,
  `nama_mata_pelajaran` varchar(200) DEFAULT NULL,
  `induk_pembelajaran_id` varchar(50) DEFAULT NULL,
  `jam_mengajar_per_minggu` varchar(10) DEFAULT NULL,
  `status_di_kurikulum` varchar(20) DEFAULT NULL,
  `status_di_kurikulum_str` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pembelajaran_id`),
  KEY `pembelajaran_rombel_index` (`rombongan_belajar_id`),
  KEY `pembelajaran_ptk_index` (`ptk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Pembelajaran
DROP TABLE IF EXISTS `backup_pembelajaran`;
CREATE TABLE `backup_pembelajaran` (
  `backup_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pembelajaran_id` varchar(50) NOT NULL,
  `rombongan_belajar_id` varchar(50) DEFAULT NULL,
  `mata_pelajaran_id` varchar(50) DEFAULT NULL,
  `mata_pelajaran_id_str` varchar(200) DEFAULT NULL,
  `ptk_terdaftar_id` varchar(50) DEFAULT NULL,
  `ptk_id` varchar(50) DEFAULT NULL,
  `nama_mata_pelajaran` varchar(200) DEFAULT NULL,
  `induk_pembelajaran_id` varchar(50) DEFAULT NULL,
  `jam_mengajar_per_minggu` varchar(10) DEFAULT NULL,
  `status_di_kurikulum` varchar(20) DEFAULT NULL,
  `status_di_kurikulum_str` varchar(100) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`),
  KEY `backup_pembelajaran_id_index` (`pembelajaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;