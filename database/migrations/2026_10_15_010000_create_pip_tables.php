<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Kuota Usulan PIP per Rombongan Belajar
        if (!Schema::hasTable('pip_kuota_rombel')) {
            Schema::create('pip_kuota_rombel', function (Blueprint $table) {
                $table->id();
                $table->string('rombongan_belajar_id', 50)->index();
                $table->string('tahun', 20)->default('2026')->index();
                $table->unsignedInteger('kuota')->default(5);
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->unique(['rombongan_belajar_id', 'tahun'], 'uq_pip_kuota_rombel_tahun');
            });
        }

        // 2. Tabel Usulan PIP oleh Wali Kelas
        if (!Schema::hasTable('pip_usulan')) {
            Schema::create('pip_usulan', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->index();
                $table->string('rombongan_belajar_id', 50)->index();
                $table->string('tahun', 20)->default('2026')->index();
                $table->unsignedInteger('prioritas')->default(1)->index();
                $table->longText('alasan_usulan')->nullable(); // JSON array kriteria alasan
                $table->text('keterangan')->nullable();
                $table->string('diusulkan_oleh', 100)->nullable();
                $table->string('status_verifikasi', 30)->default('belum_diajukan')->index(); // belum_diajukan, sudah_diajukan, ditolak
                $table->string('diverifikasi_oleh', 100)->nullable();
                $table->dateTime('verifikasi_admin_at')->nullable();
                $table->text('catatan_admin')->nullable();
                $table->timestamps();

                $table->unique(['peserta_didik_id', 'tahun'], 'uq_pip_usulan_pd_tahun');
            });
        }

        // 3. Tabel Riwayat Penerima PIP (Data SK / Hasil Import SiPintar)
        if (!Schema::hasTable('pip_riwayat')) {
            Schema::create('pip_riwayat', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->nullable()->index();
                $table->string('nisn', 20)->nullable()->index();
                $table->string('nama_pd', 150)->index();
                $table->string('kelas', 10)->nullable();
                $table->string('rombel', 50)->nullable()->index();
                $table->string('rombongan_belajar_id', 50)->nullable()->index();
                $table->string('nama_ibu_kandung', 100)->nullable();
                $table->string('nama_ayah', 100)->nullable();
                $table->string('tanggal_lahir', 30)->nullable();
                $table->string('tempat_lahir', 100)->nullable();
                $table->string('jenis_kelamin', 5)->nullable();
                $table->decimal('nominal', 12, 2)->unsigned()->nullable();
                $table->string('nomor_sk', 100)->nullable()->index();
                $table->string('jenis_sk', 30)->default('pemberian')->index(); // pemberian, nominasi
                $table->string('tanggal_sk', 30)->nullable();
                $table->string('tahap', 100)->nullable()->index();
                $table->string('tahun', 20)->nullable()->index();
                $table->string('status_cair', 50)->nullable()->index();
                $table->string('tanggal_cair', 30)->nullable();
                $table->string('tanggal_aktivasi', 30)->nullable();
                $table->string('no_rekening', 50)->nullable();
                $table->string('no_kip', 50)->nullable();
                $table->string('no_kks', 50)->nullable();
                $table->string('no_kps', 50)->nullable();
                $table->string('virtual_acc', 50)->nullable();
                $table->text('keterangan_pencairan')->nullable();
                $table->longText('raw_data')->nullable(); // JSON data baris lengkap
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pip_riwayat');
        Schema::dropIfExists('pip_usulan');
        Schema::dropIfExists('pip_kuota_rombel');
    }
};
