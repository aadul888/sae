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
        // 1. Tabel Pengaturan Kartu Pelajar (Biaya, Bank, QRIS, Template WA, Kuota Garansi)
        if (!Schema::hasTable('kartu_pelajar_pengaturan')) {
            Schema::create('kartu_pelajar_pengaturan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('biaya_standar')->default(25000);
                $table->string('bank_nama', 100)->nullable();
                $table->string('bank_rekening', 100)->nullable();
                $table->string('bank_atas_nama', 150)->nullable();
                $table->string('qris_image_path', 255)->nullable();
                $table->text('instruksi_pembayaran')->nullable();
                $table->text('template_wa')->nullable();
                $table->unsignedSmallInteger('kuota_ganti_kartu_full')->default(1);
                $table->unsignedSmallInteger('kuota_ganti_stiker')->default(3);
                $table->text('ketentuan_garansi')->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();
            });
        }

        // 2. Tambah kolom kuota klaim garansi pada tabel kartu_pelajar_pembayaran
        if (Schema::hasTable('kartu_pelajar_pembayaran')) {
            Schema::table('kartu_pelajar_pembayaran', function (Blueprint $table) {
                if (!Schema::hasColumn('kartu_pelajar_pembayaran', 'klaim_ganti_kartu')) {
                    $table->unsignedSmallInteger('klaim_ganti_kartu')->default(0)->after('status_cetak');
                }
                if (!Schema::hasColumn('kartu_pelajar_pembayaran', 'klaim_stiker')) {
                    $table->unsignedSmallInteger('klaim_stiker')->default(0)->after('klaim_ganti_kartu');
                }
            });
        }

        // 3. Tabel Log Riwayat Klaim Garansi Kartu Pelajar
        if (!Schema::hasTable('kartu_pelajar_garansi_log')) {
            Schema::create('kartu_pelajar_garansi_log', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->index();
                $table->string('nisn', 20)->nullable()->index();
                $table->string('jenis_klaim', 30); // ganti_kartu_full, cetak_stiker
                $table->text('alasan')->nullable();
                $table->dateTime('tanggal_klaim');
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
        Schema::dropIfExists('kartu_pelajar_garansi_log');

        if (Schema::hasTable('kartu_pelajar_pembayaran')) {
            Schema::table('kartu_pelajar_pembayaran', function (Blueprint $table) {
                if (Schema::hasColumn('kartu_pelajar_pembayaran', 'klaim_stiker')) {
                    $table->dropColumn('klaim_stiker');
                }
                if (Schema::hasColumn('kartu_pelajar_pembayaran', 'klaim_ganti_kartu')) {
                    $table->dropColumn('klaim_ganti_kartu');
                }
            });
        }

        Schema::dropIfExists('kartu_pelajar_pengaturan');
    }
};
