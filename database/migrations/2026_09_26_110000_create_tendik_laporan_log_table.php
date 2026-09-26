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
        if (!Schema::hasTable('tendik_laporan_log')) {
            Schema::create('tendik_laporan_log', function (Blueprint $table) {
                $table->id();
                $table->string('user_id', 64)->nullable()->index();
                $table->string('ptk_id', 64)->nullable()->index();
                $table->string('nama_pegawai', 200);
                $table->string('bidang', 64)->default('kepegawaian')->index();
                $table->string('periode_tipe', 32)->default('bulan')->index();
                $table->string('periode_label', 150);
                $table->dateTime('tanggal_cetak')->index();
                $table->integer('total_aktivitas')->default(0);
                $table->integer('total_selesai')->default(0);
                $table->float('persentase_selesai', 5, 2)->default(0);
                $table->string('orientasi', 20)->default('portrait');
                $table->string('kode_verifikasi', 64)->unique();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tendik_laporan_log');
    }
};
