<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelaksanaan Harian — jurnal konfirmasi rencana Jadwal Area Tugas.
 *
 * Satu baris = satu konfirmasi "sudah/belum dikerjakan" untuk satu jadwal area
 * tugas pada satu tanggal. Baris dibuat otomatis saat halaman dibuka (lihat
 * TeknisiPelaksanaanHarianController) agar teknisi tinggal menandai selesai.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teknisi_pelaksanaan_harian')) {
            return;
        }

        Schema::create('teknisi_pelaksanaan_harian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pelaksanaan', 60)->unique();

            // Rencana yang dikonfirmasi (Jadwal Area Tugas).
            $table->unsignedBigInteger('area_tugas_id')->nullable()->index();
            $table->unsignedBigInteger('ruang_id')->nullable()->index();

            $table->date('tanggal')->index();
            $table->string('shift', 20)->default('pagi');   // pagi, siang, sore
            $table->string('area', 150);
            $table->string('periode_jadwal', 30)->nullable(); // snapshot periode rencana

            $table->string('petugas_ptk_id', 50)->nullable()->index();
            $table->string('petugas_nama')->nullable();

            // belum, selesai
            $table->string('status', 20)->default('belum')->index();

            $table->text('uraian')->nullable();
            $table->text('temuan')->nullable();
            $table->text('catatan')->nullable();
            $table->text('dokumentasi_paths')->nullable();

            $table->string('user_id', 50)->nullable()->index();
            $table->timestamps();

            // Satu konfirmasi per rencana per hari.
            $table->unique(['area_tugas_id', 'tanggal'], 'uniq_tek_plaksana_rencana_tgl');
            $table->index(['tanggal', 'status'], 'idx_tek_plaksana_tgl_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teknisi_pelaksanaan_harian');
    }
};
