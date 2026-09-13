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
        if (!Schema::hasTable('peserta_didik_tidak_aktif')) {
            Schema::create('peserta_didik_tidak_aktif', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 36)->index();
                $table->string('registrasi_id', 36)->nullable();
                $table->string('nipd', 50)->nullable();
                $table->string('nama', 150);
                $table->string('nisn', 20)->nullable()->index();
                $table->string('nik', 30)->nullable();
                $table->string('jenis_kelamin', 5)->nullable();
                $table->string('tempat_lahir', 100)->nullable();
                $table->date('tanggal_lahir')->nullable();
                $table->string('agama_id_str', 50)->nullable();
                $table->string('nama_rombel_terakhir', 100)->nullable();
                $table->string('rombongan_belajar_id', 36)->nullable();
                $table->string('tingkat_pendidikan_terakhir', 10)->nullable();
                $table->string('kurikulum_id_str', 150)->nullable();
                $table->string('tahun_lulus', 20)->nullable()->index();
                $table->date('tanggal_keluar')->nullable();
                $table->enum('status_keluar', ['Alumni', 'Mutasi', 'Keluar', 'Dikeluarkan', 'Lainnya'])->default('Alumni')->index();
                $table->text('alasan_keluar')->nullable();
                $table->string('foto_path', 255)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('nomor_telepon_seluler', 30)->nullable();
                $table->text('alamat_jalan')->nullable();
                $table->longText('raw_data')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_didik_tidak_aktif');
    }
};
