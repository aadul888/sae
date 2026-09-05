<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->integer('id')->default(1)->primary();
            $table->string('app_name', 255)->default('SAE - Sistem Aplikasi Edukasi');
            $table->string('site_name', 255)->default('SAE - Sistem Aplikasi Edukasi');
            $table->string('api_key', 191)->default('sae_secret_live_key_2026');
            $table->string('dapodik_url', 255)->default('http://localhost:5774');
            $table->dateTime('last_sync')->nullable();
            $table->boolean('maintenance_status')->default(false);
            $table->timestamps();
        });

        // 1. Sekolah
        Schema::create('sekolah', function (Blueprint $table) {
            $table->string('sekolah_id', 50)->primary();
            $table->string('nama', 200)->nullable();
            $table->string('nss', 50)->nullable();
            $table->string('npsn', 20)->nullable()->index();
            $table->string('bentuk_pendidikan_id', 10)->nullable();
            $table->string('bentuk_pendidikan_id_str', 100)->nullable();
            $table->string('status_sekolah', 10)->nullable();
            $table->string('status_sekolah_str', 100)->nullable();
            $table->text('alamat_jalan')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('dusun', 100)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kode_wilayah', 20)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('lintang', 50)->nullable();
            $table->string('bujur', 50)->nullable();
            $table->string('nomor_telepon', 50)->nullable();
            $table->string('nomor_fax', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('is_sks', 10)->default('0');
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten_kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_sekolah', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('sekolah_id', 50)->index();
            $table->string('nama', 200)->nullable();
            $table->string('nss', 50)->nullable();
            $table->string('npsn', 20)->nullable();
            $table->string('bentuk_pendidikan_id', 10)->nullable();
            $table->string('bentuk_pendidikan_id_str', 100)->nullable();
            $table->string('status_sekolah', 10)->nullable();
            $table->string('status_sekolah_str', 100)->nullable();
            $table->text('alamat_jalan')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('dusun', 100)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kode_wilayah', 20)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('lintang', 50)->nullable();
            $table->string('bujur', 50)->nullable();
            $table->string('nomor_telepon', 50)->nullable();
            $table->string('nomor_fax', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('is_sks', 10)->default('0');
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten_kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        // 2. GTK
        Schema::create('gtk', function (Blueprint $table) {
            $table->string('ptk_id', 50)->primary();
            $table->string('tahun_ajaran_id', 10)->nullable();
            $table->string('ptk_terdaftar_id', 50)->nullable()->index();
            $table->string('ptk_induk', 10)->nullable();
            $table->string('tanggal_surat_tugas', 30)->nullable();
            $table->string('nama', 200)->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->integer('agama_id')->nullable();
            $table->string('agama_id_str', 50)->nullable();
            $table->string('nuptk', 30)->nullable()->index();
            $table->string('nik', 30)->nullable()->index();
            $table->string('jenis_ptk_id', 10)->nullable();
            $table->string('jenis_ptk_id_str', 100)->nullable();
            $table->string('jabatan_ptk_id', 20)->nullable();
            $table->string('jabatan_ptk_id_str', 200)->nullable();
            $table->string('status_kepegawaian_id', 20)->nullable();
            $table->string('status_kepegawaian_id_str', 100)->nullable();
            $table->string('nip', 30)->nullable()->index();
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('bidang_studi_terakhir', 200)->nullable();
            $table->string('pangkat_golongan_terakhir', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat_jalan')->nullable();
            $table->longText('rwy_pend_formal')->nullable();
            $table->longText('rwy_kepangkatan')->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_gtk', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('ptk_id', 50)->index();
            $table->string('tahun_ajaran_id', 10)->nullable();
            $table->string('ptk_terdaftar_id', 50)->nullable();
            $table->string('ptk_induk', 10)->nullable();
            $table->string('tanggal_surat_tugas', 30)->nullable();
            $table->string('nama', 200)->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->integer('agama_id')->nullable();
            $table->string('agama_id_str', 50)->nullable();
            $table->string('nuptk', 30)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('jenis_ptk_id', 10)->nullable();
            $table->string('jenis_ptk_id_str', 100)->nullable();
            $table->string('jabatan_ptk_id', 20)->nullable();
            $table->string('jabatan_ptk_id_str', 200)->nullable();
            $table->string('status_kepegawaian_id', 20)->nullable();
            $table->string('status_kepegawaian_id_str', 100)->nullable();
            $table->string('nip', 30)->nullable();
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('bidang_studi_terakhir', 200)->nullable();
            $table->string('pangkat_golongan_terakhir', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat_jalan')->nullable();
            $table->longText('rwy_pend_formal')->nullable();
            $table->longText('rwy_kepangkatan')->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        // 3. Rombongan Belajar
        Schema::create('rombongan_belajar', function (Blueprint $table) {
            $table->string('rombongan_belajar_id', 50)->primary();
            $table->string('nama', 100);
            $table->string('tingkat_pendidikan_id', 10)->nullable()->index();
            $table->string('tingkat_pendidikan_id_str', 50)->nullable();
            $table->string('semester_id', 10)->nullable()->index();
            $table->string('jenis_rombel', 10)->nullable();
            $table->string('jenis_rombel_str', 50)->nullable();
            $table->string('kurikulum_id', 20)->nullable();
            $table->string('kurikulum_id_str', 200)->nullable();
            $table->string('id_ruang', 50)->nullable();
            $table->string('id_ruang_str', 100)->nullable();
            $table->string('moving_class', 20)->nullable();
            $table->string('ptk_id', 50)->nullable();
            $table->string('ptk_id_str', 200)->nullable();
            $table->string('jurusan_id', 20)->nullable();
            $table->string('jurusan_id_str', 200)->nullable();
            $table->longText('anggota_rombel')->nullable();
            $table->longText('pembelajaran')->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_rombongan_belajar', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('rombongan_belajar_id', 50)->index();
            $table->string('nama', 100);
            $table->string('tingkat_pendidikan_id', 10)->nullable();
            $table->string('tingkat_pendidikan_id_str', 50)->nullable();
            $table->string('semester_id', 10)->nullable();
            $table->string('jenis_rombel', 10)->nullable();
            $table->string('jenis_rombel_str', 50)->nullable();
            $table->string('kurikulum_id', 20)->nullable();
            $table->string('kurikulum_id_str', 200)->nullable();
            $table->string('id_ruang', 50)->nullable();
            $table->string('id_ruang_str', 100)->nullable();
            $table->string('moving_class', 20)->nullable();
            $table->string('ptk_id', 50)->nullable();
            $table->string('ptk_id_str', 200)->nullable();
            $table->string('jurusan_id', 20)->nullable();
            $table->string('jurusan_id_str', 200)->nullable();
            $table->longText('anggota_rombel')->nullable();
            $table->longText('pembelajaran')->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        // 4. Peserta Didik
        Schema::create('peserta_didik', function (Blueprint $table) {
            $table->string('peserta_didik_id', 50)->primary();
            $table->string('registrasi_id', 50)->nullable()->index();
            $table->string('jenis_pendaftaran_id', 10)->nullable();
            $table->string('jenis_pendaftaran_id_str', 100)->nullable();
            $table->string('nipd', 50)->nullable()->index();
            $table->string('tanggal_masuk_sekolah', 30)->nullable();
            $table->string('sekolah_asal', 200)->nullable();
            $table->string('nama', 200)->nullable();
            $table->string('nisn', 20)->nullable()->index();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('nik', 30)->nullable()->index();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->integer('agama_id')->nullable();
            $table->string('agama_id_str', 50)->nullable();
            $table->string('nomor_telepon_rumah', 30)->nullable();
            $table->string('nomor_telepon_seluler', 30)->nullable();
            $table->string('nama_ayah', 200)->nullable();
            $table->integer('pekerjaan_ayah_id')->nullable();
            $table->string('pekerjaan_ayah_id_str', 100)->nullable();
            $table->string('nama_ibu', 200)->nullable();
            $table->integer('pekerjaan_ibu_id')->nullable();
            $table->string('pekerjaan_ibu_id_str', 100)->nullable();
            $table->string('nama_wali', 200)->nullable();
            $table->integer('pekerjaan_wali_id')->nullable();
            $table->string('pekerjaan_wali_id_str', 100)->nullable();
            $table->string('anak_keberapa', 10)->nullable();
            $table->string('tinggi_badan', 10)->nullable();
            $table->string('berat_badan', 10)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('semester_id', 10)->nullable();
            $table->string('anggota_rombel_id', 50)->nullable();
            $table->string('rombongan_belajar_id', 50)->nullable()->index();
            $table->string('tingkat_pendidikan_id', 10)->nullable();
            $table->string('nama_rombel', 100)->nullable();
            $table->string('kurikulum_id', 20)->nullable();
            $table->string('kurikulum_id_str', 200)->nullable();
            $table->string('kebutuhan_khusus', 100)->nullable();
            $table->text('alamat_jalan')->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_peserta_didik', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('peserta_didik_id', 50)->index();
            $table->string('registrasi_id', 50)->nullable();
            $table->string('jenis_pendaftaran_id', 10)->nullable();
            $table->string('jenis_pendaftaran_id_str', 100)->nullable();
            $table->string('nipd', 50)->nullable();
            $table->string('tanggal_masuk_sekolah', 30)->nullable();
            $table->string('sekolah_asal', 200)->nullable();
            $table->string('nama', 200)->nullable();
            $table->string('nisn', 20)->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->integer('agama_id')->nullable();
            $table->string('agama_id_str', 50)->nullable();
            $table->string('nomor_telepon_rumah', 30)->nullable();
            $table->string('nomor_telepon_seluler', 30)->nullable();
            $table->string('nama_ayah', 200)->nullable();
            $table->integer('pekerjaan_ayah_id')->nullable();
            $table->string('pekerjaan_ayah_id_str', 100)->nullable();
            $table->string('nama_ibu', 200)->nullable();
            $table->integer('pekerjaan_ibu_id')->nullable();
            $table->string('pekerjaan_ibu_id_str', 100)->nullable();
            $table->string('nama_wali', 200)->nullable();
            $table->integer('pekerjaan_wali_id')->nullable();
            $table->string('pekerjaan_wali_id_str', 100)->nullable();
            $table->string('anak_keberapa', 10)->nullable();
            $table->string('tinggi_badan', 10)->nullable();
            $table->string('berat_badan', 10)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('semester_id', 10)->nullable();
            $table->string('anggota_rombel_id', 50)->nullable();
            $table->string('rombongan_belajar_id', 50)->nullable();
            $table->string('tingkat_pendidikan_id', 10)->nullable();
            $table->string('nama_rombel', 100)->nullable();
            $table->string('kurikulum_id', 20)->nullable();
            $table->string('kurikulum_id_str', 200)->nullable();
            $table->string('kebutuhan_khusus', 100)->nullable();
            $table->text('alamat_jalan')->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        // 5. Relasi Anggota Rombel & Pembelajaran
        Schema::create('anggota_rombel', function (Blueprint $table) {
            $table->string('anggota_rombel_id', 50)->primary();
            $table->string('rombongan_belajar_id', 50)->nullable()->index();
            $table->string('peserta_didik_id', 50)->nullable()->index();
            $table->string('jenis_pendaftaran_id', 10)->nullable();
            $table->string('jenis_pendaftaran_id_str', 100)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_anggota_rombel', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('anggota_rombel_id', 50)->index();
            $table->string('rombongan_belajar_id', 50)->nullable();
            $table->string('peserta_didik_id', 50)->nullable();
            $table->string('jenis_pendaftaran_id', 10)->nullable();
            $table->string('jenis_pendaftaran_id_str', 100)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });

        Schema::create('pembelajaran', function (Blueprint $table) {
            $table->string('pembelajaran_id', 50)->primary();
            $table->string('rombongan_belajar_id', 50)->nullable()->index();
            $table->string('mata_pelajaran_id', 50)->nullable();
            $table->string('mata_pelajaran_id_str', 200)->nullable();
            $table->string('ptk_terdaftar_id', 50)->nullable();
            $table->string('ptk_id', 50)->nullable()->index();
            $table->string('nama_mata_pelajaran', 200)->nullable();
            $table->string('induk_pembelajaran_id', 50)->nullable();
            $table->string('jam_mengajar_per_minggu', 10)->nullable();
            $table->string('status_di_kurikulum', 20)->nullable();
            $table->string('status_di_kurikulum_str', 100)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_pembelajaran', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('pembelajaran_id', 50)->index();
            $table->string('rombongan_belajar_id', 50)->nullable();
            $table->string('mata_pelajaran_id', 50)->nullable();
            $table->string('mata_pelajaran_id_str', 200)->nullable();
            $table->string('ptk_terdaftar_id', 50)->nullable();
            $table->string('ptk_id', 50)->nullable();
            $table->string('nama_mata_pelajaran', 200)->nullable();
            $table->string('induk_pembelajaran_id', 50)->nullable();
            $table->string('jam_mengajar_per_minggu', 10)->nullable();
            $table->string('status_di_kurikulum', 20)->nullable();
            $table->string('status_di_kurikulum_str', 100)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_pembelajaran');
        Schema::dropIfExists('pembelajaran');
        Schema::dropIfExists('backup_anggota_rombel');
        Schema::dropIfExists('anggota_rombel');
        Schema::dropIfExists('backup_peserta_didik');
        Schema::dropIfExists('peserta_didik');
        Schema::dropIfExists('backup_rombongan_belajar');
        Schema::dropIfExists('rombongan_belajar');
        Schema::dropIfExists('backup_gtk');
        Schema::dropIfExists('gtk');
        Schema::dropIfExists('backup_sekolah');
        Schema::dropIfExists('sekolah');
        Schema::dropIfExists('settings');
    }
};

