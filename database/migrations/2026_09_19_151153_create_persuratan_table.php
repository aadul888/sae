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
        Schema::create('persuratan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->index();
            $table->string('jenis_surat', 50)->default('masuk')->index(); // masuk, keluar, disposisi, keputusan, tugas
            $table->string('perihal');
            $table->string('pengirim_asal')->nullable();
            $table->string('tujuan_penerima')->nullable();
            $table->date('tanggal_surat');
            $table->date('tanggal_diterima')->nullable();
            $table->string('status', 50)->default('menunggu_disposisi')->index(); // draf, menunggu_disposisi, diproses, selesai, diarsipkan
            $table->string('file_path')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persuratan');
    }
};
