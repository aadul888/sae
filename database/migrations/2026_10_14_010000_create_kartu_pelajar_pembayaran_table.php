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
        if (!Schema::hasTable('kartu_pelajar_pembayaran')) {
            Schema::create('kartu_pelajar_pembayaran', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->unique()->index();
                $table->string('nisn', 20)->nullable()->index();
                $table->string('status_pembayaran', 30)->default('belum_bayar')->index(); // belum_bayar, lunas
                $table->unsignedBigInteger('nominal')->default(25000);
                $table->string('metode_pembayaran', 50)->nullable(); // tunai, transfer, qris
                $table->date('tanggal_bayar')->nullable();
                $table->string('status_cetak', 30)->default('belum_cetak')->index(); // belum_cetak, sudah_cetak
                $table->dateTime('tanggal_cetak')->nullable();
                $table->string('nomor_wa', 30)->nullable();
                $table->dateTime('wa_terkirim_at')->nullable();
                $table->text('catatan')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_pelajar_pembayaran');
    }
};
