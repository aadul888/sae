<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel hasil import berkas Residu Verval PD (satu baris = satu peserta didik).
     * Sel perbandingan menyimpan centang hijau/merah apa adanya dari berkas Residu.
     */
    public function up(): void
    {
        if (!Schema::hasTable('verval_pd_residu')) {
            Schema::create('verval_pd_residu', function (Blueprint $table) {
                $table->id();
                $table->string('import_batch', 36)->index();
                $table->string('nisn', 30)->nullable()->index();
                $table->string('nik', 30)->nullable();
                $table->string('nama', 150)->nullable();
                $table->string('rombel_sekolah', 100)->nullable();
                $table->string('nisn_banding', 30)->nullable();
                $table->string('nik_dukcapil', 30)->nullable();
                $table->string('nik_ganda', 100)->nullable();
                $table->string('nama_banding', 150)->nullable();
                $table->string('tempat_lahir', 100)->nullable();
                $table->string('tanggal_lahir', 50)->nullable();
                $table->string('ibu_kandung', 150)->nullable();
                $table->string('jenis_kelamin', 20)->nullable();
                $table->string('desa_kelurahan', 150)->nullable();
                $table->string('imported_by', 150)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verval_pd_residu');
    }
};
