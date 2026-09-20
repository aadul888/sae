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
        // 1. Inventaris Bahan & Alat Laboratorium / Bengkel Praktik
        if (!Schema::hasTable('laboran_bahan_alat')) {
            Schema::create('laboran_bahan_alat', function (Blueprint $table) {
                $table->id();
                $table->string('ruang_lab_nama', 100)->index(); // Lab Komputer 1, Lab IPA/Kimia, Lab Bahasa, Bengkel Otomotif, dll
                $table->string('kode_item', 50)->unique();
                $table->string('nama_item');
                $table->string('jenis', 50)->default('alat')->index(); // alat, bahan_habis_pakai
                $table->text('spesifikasi')->nullable();
                $table->decimal('stok_total', 10, 2)->default(1);
                $table->decimal('stok_tersedia', 10, 2)->default(1);
                $table->string('satuan', 30)->default('unit');
                $table->string('kondisi', 50)->default('baik')->index(); // baik, rusak_ringan, rusak_berat, kedaluwarsa
                $table->date('tgl_kedaluwarsa')->nullable();
                $table->string('lokasi_lemari_rak', 100)->nullable();
                $table->timestamps();
            });
        }

        // 2. Jadwal & Pemakaian Ruang Lab / Bengkel
        if (!Schema::hasTable('laboran_jadwal_penggunaan')) {
            Schema::create('laboran_jadwal_penggunaan', function (Blueprint $table) {
                $table->id();
                $table->string('ruang_lab_nama', 100)->index();
                $table->string('ptk_id')->index(); // Guru Pengampu
                $table->string('rombel_id')->nullable()->index();
                $table->string('mata_pelajaran');
                $table->string('topik_praktik');
                $table->date('tanggal')->index();
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->text('alat_bahan_digunakan')->nullable();
                $table->string('status', 50)->default('dijadwalkan')->index(); // dijadwalkan, berlangsung, selesai, batal
                $table->text('laporan_kerusakan')->nullable();
                $table->string('laboran_petugas_ptk_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboran_jadwal_penggunaan');
        Schema::dropIfExists('laboran_bahan_alat');
    }
};
