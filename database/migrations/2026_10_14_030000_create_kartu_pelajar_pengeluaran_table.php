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
        if (!Schema::hasTable('kartu_pelajar_pengeluaran')) {
            Schema::create('kartu_pelajar_pengeluaran', function (Blueprint $table) {
                $table->id();
                $table->string('judul', 255);
                $table->string('kategori', 50)->default('bahan_pvc')->index(); // bahan_pvc, ribbon_stiker, alat_mesin, pemeliharaan, operasional, lainnya
                $table->unsignedInteger('jumlah')->default(1)->nullable();
                $table->unsignedBigInteger('nominal')->default(0);
                $table->date('tanggal')->index();
                $table->text('keterangan')->nullable();
                $table->string('bukti_nota_path', 255)->nullable();
                $table->string('petugas', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_pelajar_pengeluaran');
    }
};
