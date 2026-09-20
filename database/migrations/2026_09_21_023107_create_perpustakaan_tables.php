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
        // 1. Katalog Buku & Bahan Pustaka
        if (!Schema::hasTable('perpus_koleksi_buku')) {
            Schema::create('perpus_koleksi_buku', function (Blueprint $table) {
                $table->id();
                $table->string('kode_buku', 50)->unique();
                $table->string('isbn', 30)->nullable()->index();
                $table->string('judul');
                $table->string('penulis')->nullable();
                $table->string('penerbit')->nullable();
                $table->integer('tahun_terbit')->nullable();
                $table->string('klasifikasi_ddc', 50)->nullable()->index();
                $table->string('kategori', 50)->default('Umum')->index(); // Buku Pelajaran, Fiksi, Non-Fiksi, Referensi, Majalah
                $table->integer('jumlah_eksemplar')->default(1);
                $table->integer('eksemplar_tersedia')->default(1);
                $table->string('lokasi_rak', 50)->nullable();
                $table->string('cover_buku')->nullable();
                $table->timestamps();
            });
        }

        // 2. Sirkulasi Peminjaman & Pengembalian Buku
        if (!Schema::hasTable('perpus_sirkulasi')) {
            Schema::create('perpus_sirkulasi', function (Blueprint $table) {
                $table->id();
                $table->string('kode_transaksi', 50)->unique();
                $table->unsignedBigInteger('buku_id')->index();
                $table->string('peminjam_tipe', 30)->default('siswa'); // siswa, gtk, umum
                $table->string('peminjam_id')->index(); // peserta_didik_id atau ptk_id
                $table->string('peminjam_nama');
                $table->date('tgl_pinjam');
                $table->date('tgl_jatuh_tempo');
                $table->date('tgl_kembali')->nullable();
                $table->string('status', 30)->default('dipinjam')->index(); // dipinjam, kembali, terlambat, hilang
                $table->decimal('denda', 12, 2)->default(0);
                $table->string('status_denda', 30)->default('lunas'); // belum_lunas, lunas
                $table->string('petugas_ptk_id')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        // 3. Buku Kunjungan Pengunjung Perpustakaan
        if (!Schema::hasTable('perpus_kunjungan')) {
            Schema::create('perpus_kunjungan', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->time('jam_kunjung');
                $table->string('pengunjung_tipe', 30)->default('siswa'); // siswa, gtk, umum
                $table->string('pengunjung_id')->nullable()->index();
                $table->string('nama');
                $table->string('rombel_atau_unit')->nullable();
                $table->string('keperluan')->default('Membaca'); // Membaca, Meminjam Buku, Mengerjakan Tugas, WiFi/Komputer, Lainnya
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perpus_kunjungan');
        Schema::dropIfExists('perpus_sirkulasi');
        Schema::dropIfExists('perpus_koleksi_buku');
    }
};
