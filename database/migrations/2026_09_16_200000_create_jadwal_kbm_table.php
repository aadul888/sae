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
        if (!Schema::hasTable('jadwal_kbm')) {
            Schema::create('jadwal_kbm', function (Blueprint $table) {
                $table->id();
                $table->string('rombongan_belajar_id', 50)->index()->comment('Relasi ke rombongan_belajar');
                $table->string('pembelajaran_id', 50)->nullable()->index()->comment('Relasi ke pembelajaran Dapodik');
                $table->string('ptk_id', 50)->nullable()->index()->comment('Relasi ke gtk / guru pengampu');
                $table->string('mata_pelajaran_id', 50)->nullable();
                $table->string('nama_mata_pelajaran', 150);
                $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])->index();
                $table->unsignedTinyInteger('jam_ke_mulai')->default(1);
                $table->unsignedTinyInteger('jam_ke_selesai')->default(1);
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->string('ruangan', 50)->nullable();
                $table->string('semester_id', 10)->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->text('keterangan')->nullable();
                $table->timestamps();

                // Composite Index for efficient conflict checks and daily lookups
                $table->index(['rombongan_belajar_id', 'hari', 'is_active'], 'idx_jadwal_rombel_hari');
                $table->index(['ptk_id', 'hari', 'is_active'], 'idx_jadwal_ptk_hari');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_kbm');
    }
};
