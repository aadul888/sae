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
        if (!Schema::hasTable('notifikasi_transaksi')) {
            Schema::create('notifikasi_transaksi', function (Blueprint $table) {
                $table->id();
                $table->string('pengguna_id', 50)->nullable()->index();
                $table->string('peserta_didik_id', 50)->nullable()->index();
                $table->string('kategori', 50)->default('presensi')->index();
                $table->string('judul', 150);
                $table->text('pesan');
                $table->string('tipe', 20)->default('info');
                $table->string('icon', 50)->default('bell');
                $table->string('url', 255)->nullable();
                $table->boolean('is_read')->default(false)->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_transaksi');
    }
};
