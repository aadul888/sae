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
        if (!Schema::hasTable('referensi_jurusan')) {
            Schema::create('referensi_jurusan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_bidang', 10)->index();
                $table->string('nama_bidang', 150);
                $table->string('kode_program', 20)->index();
                $table->string('nama_program', 150);
                $table->string('kode_konsentrasi', 30)->unique();
                $table->string('nama_konsentrasi', 200);
                $table->unsignedTinyInteger('durasi')->default(3)->comment('Durasi program: 3 atau 4 tahun');
                $table->string('kurikulum', 50)->default('Kurikulum Merdeka');
                $table->string('bidang_k13', 255)->nullable()->comment('Bidang Keahlian Asal pada Kurikulum 2013 (Konversi)');
                $table->timestamps();
            });

            // Seed master data spektrum keahlian nasional
            (new \Database\Seeders\ReferensiJurusanSeeder())->run();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referensi_jurusan');
    }
};
