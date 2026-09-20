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
        if (!Schema::hasTable('tendik_aktivitas')) {
            Schema::create('tendik_aktivitas', function (Blueprint $table) {
                $table->id();
                $table->string('user_id', 64)->nullable()->index();
                $table->string('ptk_id', 64)->nullable()->index();
                $table->string('nama_pegawai');
                $table->string('bidang', 64)->default('umum')->index();
                $table->date('tanggal')->index();
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->string('judul_aktivitas');
                $table->text('uraian_pekerjaan');
                $table->string('output_hasil')->nullable();
                $table->string('status', 32)->default('selesai')->index(); // selesai, proses, tertunda
                $table->string('lampiran_path')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tendik_aktivitas');
    }
};
