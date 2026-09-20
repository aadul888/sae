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
        // 1. Checklist Harian Petugas Kebersihan / Sanitasi
        if (!Schema::hasTable('kebersihan_checklist')) {
            Schema::create('kebersihan_checklist', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->string('shift', 20)->default('pagi'); // pagi, siang, sore
                $table->string('area_zona', 100)->index(); // Toilet Siswa Lt 1, Ruang Guru, Selasar Depan, Kantin, Taman
                $table->string('kondisi_kebersihan', 50)->default('bersih'); // sangat_bersih, bersih, kotor, perlu_tindakan
                $table->string('ketersediaan_air_sabun', 50)->default('lengkap'); // lengkap, habis, air_mati
                $table->text('catatan_temuan')->nullable();
                $table->string('foto_sebelum')->nullable();
                $table->string('foto_sesudah')->nullable();
                $table->string('petugas_ptk_id')->nullable();
                $table->timestamps();
            });
        }

        // 2. Work Order & Tiket Perbaikan Teknisi (Listrik, AC, Jaringan, Komputer)
        if (!Schema::hasTable('teknisi_work_order')) {
            Schema::create('teknisi_work_order', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_wo', 50)->unique();
                $table->date('tanggal')->index();
                $table->string('lokasi_unit', 100);
                $table->string('kategori_perbaikan', 50)->default('listrik'); // listrik, ac_pendingin, internet_jaringan, sanitasi_plumbing, komputer, audio_bel
                $table->text('deskripsi_kerusakan');
                $table->string('tingkat_urgensi', 30)->default('normal'); // rendah, normal, darurat
                $table->string('pelapor_nama')->nullable();
                $table->string('teknisi_ptk_id')->nullable();
                $table->date('tgl_selesai')->nullable();
                $table->text('tindakan_perbaikan')->nullable();
                $table->decimal('estimasi_biaya_part', 12, 2)->default(0);
                $table->string('status', 50)->default('antrean')->index(); // antrean, proses, selesai, menunggu_sparepart
                $table->timestamps();
            });
        }

        // 3. Log Buku Jaga & Ronda Malam Penjaga Sekolah
        if (!Schema::hasTable('penjaga_malam_log')) {
            Schema::create('penjaga_malam_log', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->time('jam_kontrol');
                $table->string('zona_kontrol', 100); // Gerbang Utama, Ruang Guru & TU, Lab Komputer, Gedung Kelas Belakang
                $table->string('status_pintu_jendela', 50)->default('terkunci_rapi'); // terkunci_rapi, ditemukan_terbuka, kunci_rusak
                $table->string('status_lampu', 50)->default('normal'); // menyala_sesuai, mati_sebagian, konsleting
                $table->string('situasi_keamanan', 50)->default('kondusif'); // kondusif, orang_mencurigakan, kebocoran_air
                $table->text('catatan_penjaga')->nullable();
                $table->string('petugas_ptk_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjaga_malam_log');
        Schema::dropIfExists('teknisi_work_order');
        Schema::dropIfExists('kebersihan_checklist');
    }
};
