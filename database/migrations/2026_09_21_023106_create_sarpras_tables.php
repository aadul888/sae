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
        // 1. Data Ruang & Lokasi Gedung Sarpras
        if (!Schema::hasTable('sarpras_ruang')) {
            Schema::create('sarpras_ruang', function (Blueprint $table) {
                $table->id();
                $table->string('kode_ruang', 50)->unique();
                $table->string('nama_ruang');
                $table->string('gedung', 100)->nullable();
                $table->string('lantai', 20)->nullable();
                $table->string('penanggung_jawab_ptk_id')->nullable();
                $table->string('kondisi', 50)->default('baik')->index(); // baik, rusak_ringan, rusak_berat
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 2. Inventaris Aset & Barang Sarpras
        if (!Schema::hasTable('sarpras_aset')) {
            Schema::create('sarpras_aset', function (Blueprint $table) {
                $table->id();
                $table->string('kode_aset', 50)->unique();
                $table->string('nama_barang');
                $table->string('kategori', 100)->default('Elektronik')->index(); // Elektronik, Mebel/Furnitur, Kendaraan, Alat Peraga, Mesin, Lainnya
                $table->string('merk_tipe')->nullable();
                $table->string('no_seri_pabrik')->nullable();
                $table->integer('tahun_perolehan')->nullable();
                $table->string('sumber_dana', 50)->default('BOS'); // BOS, DAK, Komite, Yayasan, Hibah
                $table->decimal('harga_perolehan', 15, 2)->nullable();
                $table->string('kondisi', 50)->default('baik')->index(); // baik, rusak_ringan, rusak_berat
                $table->unsignedBigInteger('ruang_id')->nullable()->index();
                $table->integer('jumlah')->default(1);
                $table->string('satuan', 30)->default('unit');
                $table->string('status_ketersediaan', 50)->default('tersedia')->index(); // tersedia, dipinjam, perbaikan, dihapuskan
                $table->string('foto')->nullable();
                $table->timestamps();
            });
        }

        // 3. Peminjaman & Pengembalian Aset Sarpras
        if (!Schema::hasTable('sarpras_peminjaman')) {
            Schema::create('sarpras_peminjaman', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_pinjam', 50)->unique();
                $table->unsignedBigInteger('aset_id')->index();
                $table->string('peminjam_tipe', 50)->default('gtk'); // gtk, siswa, umum
                $table->string('peminjam_id')->nullable();
                $table->string('peminjam_nama');
                $table->string('keperluan');
                $table->date('tanggal_pinjam');
                $table->date('tanggal_kembali_rencana');
                $table->date('tanggal_kembali_aktual')->nullable();
                $table->string('kondisi_sebelum', 50)->default('baik');
                $table->string('kondisi_sesudah', 50)->nullable();
                $table->string('status', 50)->default('dipinjam')->index(); // menunggu, dipinjam, kembali, terlambat
                $table->string('petugas_ptk_id')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_peminjaman');
        Schema::dropIfExists('sarpras_aset');
        Schema::dropIfExists('sarpras_ruang');
    }
};
