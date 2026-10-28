<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat cetak dokumen Laboran.
 *
 * Setiap kali dokumen Laboran dirender untuk dicetak (A4), baris di sini
 * dicatat agar bukti fisik supervisi dapat ditelusuri kapan saja: jenis
 * dokumen, mode (ringkasan/lengkap), cakupan jurusan, petugas, dan filternya.
 *
 * Kolom `arsip_id` menautkan ke `laboran_arsip` sehingga dokumen yang dicetak
 * otomatis muncul di modul Arsip Dokumen (ordner digital).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laboran_log_cetak')) {
            return;
        }

        Schema::create('laboran_log_cetak', function (Blueprint $table) {
            $table->id();
            $table->string('jurusan_id', 50)->nullable()->index();

            $table->string('nomor_cetak', 60)->unique();
            $table->string('jenis_dokumen', 60)->index();
            $table->string('judul_dokumen');

            // ringkasan | lengkap | detail
            $table->string('mode', 20)->default('ringkasan')->index();

            $table->string('periode_label')->nullable();
            $table->date('periode_dari')->nullable();
            $table->date('periode_sampai')->nullable();

            $table->string('sumber_tabel', 60)->nullable();
            $table->unsignedBigInteger('sumber_id')->nullable()->index();

            $table->string('orientasi', 20)->default('portrait');
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->json('filter')->nullable();
            // Sidik filter (sha1) → mencegah riwayat ganda saat halaman dimuat ulang.
            $table->string('filter_signature', 40)->nullable()->index();

            $table->string('kepala_lab_nama')->nullable();
            $table->string('kaprog_nama')->nullable();

            $table->string('petugas_ptk_id', 50)->nullable();
            $table->string('petugas_nama')->nullable();

            $table->unsignedBigInteger('arsip_id')->nullable()->index();

            $table->timestamps();

            $table->index(['jurusan_id', 'jenis_dokumen'], 'idx_lab_log_cetak_jurusan_jenis');
            $table->index(['jurusan_id', 'created_at'], 'idx_lab_log_cetak_jurusan_waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboran_log_cetak');
    }
};
