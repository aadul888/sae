<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peminjaman TEKNISI/SARPRAS — halaman sendiri, TERPISAH dari peminjaman
 * jurusan (Laboran). Sumber hanya RUANG (`sarpras_ruang`) + ASET
 * (`sarpras_aset`); tidak menyentuh `laboran_peminjaman`.
 *
 * Cakupan data mengikuti penanggung jawab ruang (PJ), sama seperti jadwal
 * area tugas Teknisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teknisi_peminjaman')) {
            Schema::create('teknisi_peminjaman', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_pinjam', 60)->unique();

                $table->string('peminjam_tipe', 30)->default('tendik')->index();
                $table->string('peminjam_id', 50)->nullable();
                $table->string('peminjam_nama');
                $table->string('peminjam_kelas', 100)->nullable();

                $table->string('keperluan', 500);
                $table->string('lokasi', 150)->nullable();
                $table->date('tanggal_pinjam')->nullable()->index();
                $table->date('tanggal_kembali_rencana')->nullable()->index();
                $table->date('tanggal_kembali_aktual')->nullable();

                $table->unsignedInteger('jumlah_item')->default(0);
                $table->string('kondisi_kembali', 40)->nullable();

                $table->string('status', 30)->default('diajukan')->index();
                $table->string('disetujui_oleh_ptk_id', 50)->nullable();

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama', 150)->nullable();
                $table->string('user_id', 50)->nullable()->index();

                $table->text('catatan')->nullable();
                $table->text('catatan_pengembalian')->nullable();
                $table->timestamps();

                $table->index(['petugas_ptk_id', 'status'], 'idx_teknisi_pinjam_petugas_status');
            });
        }

        if (!Schema::hasTable('teknisi_peminjaman_item')) {
            Schema::create('teknisi_peminjaman_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('peminjaman_id')->index();
                $table->string('sumber', 20)->default('ruang'); // ruang | aset
                $table->unsignedBigInteger('ruang_id')->nullable()->index();
                $table->unsignedBigInteger('aset_id')->nullable()->index();

                $table->string('nama_item');
                $table->string('kode_item', 50)->nullable();
                $table->decimal('jumlah', 12, 2)->default(1);
                $table->string('satuan', 30)->default('unit');

                $table->string('kondisi_pinjam', 40)->default('baik');
                $table->string('kondisi_kembali', 40)->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teknisi_peminjaman_item');
        Schema::dropIfExists('teknisi_peminjaman');
    }
};
