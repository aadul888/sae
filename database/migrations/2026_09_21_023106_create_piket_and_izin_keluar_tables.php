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
        // 1. Modul e-Izin Keluar-Masuk Siswa (Terintegrasi Guru Piket & Satpam)
        if (!Schema::hasTable('peserta_didik_izin_keluar')) {
            Schema::create('peserta_didik_izin_keluar', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_tiket', 50)->unique(); // IZN-YYYYMMDD-XXXX
                $table->string('peserta_didik_id')->index();
                $table->string('rombel_id')->nullable()->index();
                $table->date('tanggal')->index();
                $table->string('jenis_izin', 50)->default('keluar_sebentar'); // keluar_sebentar, pulang_cepat, terlambat_masuk
                $table->text('alasan');
                $table->time('jam_izin_keluar');
                $table->time('jam_rencana_kembali')->nullable();
                $table->time('jam_kembali_aktual')->nullable();
                $table->string('petugas_piket_ptk_id')->nullable();
                $table->string('guru_pengampu_ptk_id')->nullable();
                $table->string('status', 50)->default('menunggu_satpam')->index(); // menunggu_satpam, di_luar, kembali, pulang_selesai, dibatalkan
                $table->string('satpam_checkout_by')->nullable();
                $table->timestamp('satpam_checkout_at')->nullable();
                $table->string('satpam_checkin_by')->nullable();
                $table->timestamp('satpam_checkin_at')->nullable();
                $table->text('catatan_satpam')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 2. Modul Jurnal Guru Piket Harian
        if (!Schema::hasTable('guru_piket_jurnal')) {
            Schema::create('guru_piket_jurnal', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->string('ptk_id')->index();
                $table->string('shift_jam', 50)->default('pagi'); // pagi, siang, full_day
                $table->text('catatan_kejadian')->nullable();
                $table->integer('jumlah_siswa_terlambat')->default(0);
                $table->integer('jumlah_siswa_izin')->default(0);
                $table->json('guru_tidak_hadir')->nullable();
                $table->string('status', 50)->default('berjalan')->index(); // berjalan, selesai
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_piket_jurnal');
        Schema::dropIfExists('peserta_didik_izin_keluar');
    }
};
