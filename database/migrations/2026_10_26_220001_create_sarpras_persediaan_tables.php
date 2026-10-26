<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi tabel barang habis pakai (ATK / persediaan kantor & sekolah)
 * serta pencatatan mutasi keluar-masuk barang persediaan sarpras.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Barang Habis Pakai & ATK
        if (!Schema::hasTable('sarpras_persediaan')) {
            Schema::create('sarpras_persediaan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_barang', 50)->unique();
                $table->string('nama_barang', 255);
                $table->string('kategori', 100)->default('ATK Kantor')->index();
                $table->string('spesifikasi', 255)->nullable();
                $table->integer('stok_saat_ini')->default(0);
                $table->integer('stok_minimum')->default(5);
                $table->string('satuan', 30)->default('pcs');
                $table->decimal('harga_satuan', 12, 2)->default(0);
                $table->string('lokasi_simpan', 100)->nullable();
                $table->string('sumber_dana', 50)->default('BOS');
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 2. Riwayat Mutasi Barang Persediaan (Masuk / Keluar)
        if (!Schema::hasTable('sarpras_persediaan_mutasi')) {
            Schema::create('sarpras_persediaan_mutasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('persediaan_id')->index();
                $table->date('tanggal')->index();
                $table->string('jenis_mutasi', 20)->default('keluar')->index(); // 'masuk', 'keluar'
                $table->integer('jumlah')->default(1);
                $table->string('penerima_atau_sumber', 255);
                $table->string('keperluan', 255)->nullable();
                $table->string('petugas_ptk_id', 100)->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->foreign('persediaan_id')
                    ->references('id')
                    ->on('sarpras_persediaan')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sarpras_persediaan_mutasi');
        Schema::dropIfExists('sarpras_persediaan');
    }
};
