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
        Schema::create('agenda_kbm', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('presensi_mengajar_id')->nullable()->index()->comment('Relasi opsional ke presensi mengajar');
            $table->unsignedBigInteger('jadwal_kbm_id')->nullable()->index()->comment('Relasi ke tabel jadwal_kbm');
            $table->string('ptk_id', 50)->index()->comment('Guru Pengampu');
            $table->string('rombongan_belajar_id', 50)->index()->comment('Kelas yang diajar');
            $table->string('pembelajaran_id', 50)->nullable()->index()->comment('ID Pembelajaran Dapodik');
            $table->string('mata_pelajaran_id', 50)->nullable();
            $table->string('nama_mata_pelajaran', 150);
            $table->date('tanggal')->index();
            $table->string('hari', 20);
            $table->unsignedSmallInteger('jam_ke_mulai')->default(1);
            $table->unsignedSmallInteger('jam_ke_selesai')->default(1);
            $table->unsignedSmallInteger('pertemuan_ke')->default(1);
            $table->string('materi_pokok', 255)->comment('Judul Materi / Tujuan Pembelajaran / KD');
            $table->text('uraian_kegiatan')->comment('Uraian kegiatan KBM & aktivitas peserta didik');
            $table->text('penugasan')->nullable()->comment('Tugas mandiri, PR, kuis, atau asesmen');
            $table->string('status_kbm', 30)->default('Terlaksana')->comment('Terlaksana, Sebagian, Tertunda, Digantikan');
            $table->text('hambatan_catatan')->nullable()->comment('Kendala KBM atau catatan peserta didik tertentu');
            $table->string('created_by', 50)->nullable();
            $table->timestamps();

            $table->index(['ptk_id', 'tanggal']);
            $table->index(['rombongan_belajar_id', 'pembelajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_kbm');
    }
};
