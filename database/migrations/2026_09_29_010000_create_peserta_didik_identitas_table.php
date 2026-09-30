<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            Schema::create('peserta_didik_identitas', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 36)->unique();
                $table->string('nisn', 10)->index();
                $table->string('nipd', 30)->nullable()->index();

                // Bagian 1: Data Pribadi
                $table->string('nama', 150)->nullable();
                $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
                $table->string('nik', 16)->nullable();
                $table->string('no_kk', 16)->nullable();
                $table->string('no_registrasi_akta_lahir', 80)->nullable();
                $table->string('kewarganegaraan', 10)->default('WNI');
                $table->string('tempat_lahir', 60)->nullable();
                $table->date('tanggal_lahir')->nullable();
                $table->string('agama_id', 10)->nullable();
                $table->string('agama_id_str', 50)->nullable();
                $table->string('kebutuhan_khusus_id', 10)->nullable();
                $table->string('kebutuhan_khusus_str', 80)->nullable();
                $table->unsignedSmallInteger('anak_keberapa')->nullable();
                $table->unsignedSmallInteger('tinggi_badan')->nullable();
                $table->unsignedSmallInteger('berat_badan')->nullable();

                // Bagian 2: Alamat & Domisili
                $table->string('alamat_jalan', 255)->nullable();
                $table->string('rt', 5)->nullable();
                $table->string('rw', 5)->nullable();
                $table->string('nama_dusun', 100)->nullable();
                $table->string('desa_kelurahan', 100)->nullable();
                $table->string('kecamatan', 100)->nullable();
                $table->string('kabupaten_kota', 100)->nullable();
                $table->string('provinsi', 100)->nullable();
                $table->string('kode_pos', 7)->nullable();
                $table->decimal('lintang', 10, 7)->nullable();
                $table->decimal('bujur', 11, 7)->nullable();
                $table->string('tempat_tinggal_id', 10)->nullable();
                $table->string('tempat_tinggal_str', 60)->nullable();
                $table->string('transportasi_id', 10)->nullable();
                $table->string('transportasi_str', 80)->nullable();

                // Bagian 3: Rekening Bank PIP
                $table->string('nama_bank', 50)->nullable();
                $table->string('no_rekening', 40)->nullable();
                $table->string('kcp_bank', 100)->nullable();
                $table->string('rekening_atas_nama', 150)->nullable();

                // Bagian 4: Data Ayah Kandung
                $table->string('status_hidup_ayah', 10)->default('1'); // 1: Masih Hidup, 0: Meninggal
                $table->string('nama_ayah', 150)->nullable();
                $table->string('nik_ayah', 16)->nullable();
                $table->string('tahun_lahir_ayah', 4)->nullable();
                $table->string('pendidikan_ayah_id', 10)->nullable();
                $table->string('pendidikan_ayah_str', 60)->nullable();
                $table->string('pekerjaan_ayah_id', 10)->nullable();
                $table->string('pekerjaan_ayah_str', 80)->nullable();
                $table->string('penghasilan_ayah_id', 10)->nullable();
                $table->string('penghasilan_ayah_str', 60)->nullable();
                $table->string('kebutuhan_khusus_ayah_id', 10)->nullable();
                $table->string('kebutuhan_khusus_ayah_str', 80)->nullable();

                // Bagian 5: Data Ibu Kandung
                $table->string('status_hidup_ibu', 10)->default('1');
                $table->string('nama_ibu', 150)->nullable();
                $table->string('nik_ibu', 16)->nullable();
                $table->string('tahun_lahir_ibu', 4)->nullable();
                $table->string('pendidikan_ibu_id', 10)->nullable();
                $table->string('pendidikan_ibu_str', 60)->nullable();
                $table->string('pekerjaan_ibu_id', 10)->nullable();
                $table->string('pekerjaan_ibu_str', 80)->nullable();
                $table->string('penghasilan_ibu_id', 10)->nullable();
                $table->string('penghasilan_ibu_str', 60)->nullable();
                $table->string('kebutuhan_khusus_ibu_id', 10)->nullable();
                $table->string('kebutuhan_khusus_ibu_str', 80)->nullable();

                // Bagian 6: Data Wali
                $table->boolean('mempunyai_wali')->default(false);
                $table->string('nama_wali', 150)->nullable();
                $table->string('nik_wali', 16)->nullable();
                $table->string('tahun_lahir_wali', 4)->nullable();
                $table->string('pendidikan_wali_id', 10)->nullable();
                $table->string('pendidikan_wali_str', 60)->nullable();
                $table->string('pekerjaan_wali_id', 10)->nullable();
                $table->string('pekerjaan_wali_str', 80)->nullable();
                $table->string('penghasilan_wali_id', 10)->nullable();
                $table->string('penghasilan_wali_str', 60)->nullable();
                $table->string('kebutuhan_khusus_wali_id', 10)->nullable();
                $table->string('kebutuhan_khusus_wali_str', 80)->nullable();

                // Bagian 7: Kontak & Komunikasi
                $table->string('nomor_telepon_rumah', 25)->nullable();
                $table->string('nomor_telepon_seluler', 25)->nullable();
                $table->string('email', 100)->nullable();

                // Bagian 8: Riwayat Prestasi (JSON array)
                $table->json('riwayat_prestasi')->nullable();

                // Bagian 9: Perlindungan Sosial / Kesejahteraan (JSON array)
                $table->json('perlindungan_sosial')->nullable();

                // Bagian 10: Registrasi Masuk
                $table->string('jenis_pendaftaran_id', 10)->nullable();
                $table->string('jenis_pendaftaran_str', 50)->nullable();
                $table->date('tanggal_masuk_sekolah')->nullable();
                $table->string('sekolah_asal', 150)->nullable();
                $table->boolean('pernah_paud_formal')->default(false);
                $table->boolean('pernah_paud_non_formal')->default(false);

                // Bagian 11: Minat & Bakat
                $table->string('hobi_id', 10)->nullable();
                $table->string('hobi_str', 60)->nullable();
                $table->string('cita_cita_id', 10)->nullable();
                $table->string('cita_cita_str', 60)->nullable();

                // Status Konfirmasi & Audit Siswa
                // status_konfirmasi: belum_konfirmasi, sesuai, perlu_perbaikan, diverifikasi
                $table->string('status_konfirmasi', 30)->default('belum_konfirmasi')->index();
                $table->timestamp('dikonfirmasi_pada')->nullable();
                $table->string('dikonfirmasi_oleh', 60)->nullable();
                $table->text('catatan_siswa')->nullable();
                $table->text('catatan_kesiswaan')->nullable();
                $table->string('terakhir_diubah_oleh', 50)->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_didik_identitas');
    }
};
