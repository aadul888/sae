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
        if (!Schema::hasTable('gtk_spt')) {
            Schema::create('gtk_spt', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_spt')->unique();
                $table->text('dasar_penugasan')->nullable();
                $table->string('nama_kegiatan');
                $table->string('lokasi_tujuan');
                $table->date('tanggal_berangkat');
                $table->date('tanggal_kembali');
                $table->integer('lama_hari')->default(1);
                $table->string('beban_anggaran', 50)->default('BOS');
                $table->string('pejabat_penandatangan_ptk_id')->nullable();
                $table->string('pejabat_nama')->nullable();
                $table->string('pejabat_jabatan')->default('Kepala Sekolah');
                $table->json('daftar_ptk_id')->nullable();
                $table->string('status', 50)->default('disetujui')->index();
                $table->string('file_lampiran')->nullable();
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
        Schema::dropIfExists('gtk_spt');
    }
};
