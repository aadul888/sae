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
        // 1. Buku Tamu Satpam Pos Depan
        if (!Schema::hasTable('keamanan_buku_tamu')) {
            Schema::create('keamanan_buku_tamu', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->time('jam_masuk');
                $table->time('jam_keluar')->nullable();
                $table->string('nama_tamu');
                $table->string('instansi_asal')->nullable();
                $table->string('nomor_kontak', 30)->nullable();
                $table->string('tujuan_bertemu');
                $table->string('keperluan');
                $table->string('nomor_kartu_visitor', 30)->nullable();
                $table->string('nomor_polisi_kendaraan', 30)->nullable();
                $table->string('foto_tamu')->nullable();
                $table->string('foto_identitas')->nullable();
                $table->string('petugas_satpam_ptk_id')->nullable();
                $table->string('status', 30)->default('berada_di_lokasi')->index(); // berada_di_lokasi, sudah_keluar
                $table->timestamps();
            });
        }

        // 2. Log Patroli Keliling Pos Keamanan
        if (!Schema::hasTable('keamanan_patroli')) {
            Schema::create('keamanan_patroli', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->time('jam_patroli');
                $table->string('rute_zona', 100); // Gerbang Utama, Gedung A & B, Lapangan/Kantin, Lab & Bengkel, Parkir Belakang
                $table->string('kondisi_lingkungan', 50)->default('aman_terkendali'); // aman_terkendali, pintu_terbuka, lampu_mati, mencurigakan, lainnya
                $table->text('catatan_temuan')->nullable();
                $table->string('foto_bukti')->nullable();
                $table->string('petugas_satpam_ptk_id')->nullable();
                $table->timestamps();
            });
        }

        // 3. Laporan Insiden & Kejadian Khusus Keamanan
        if (!Schema::hasTable('keamanan_insiden')) {
            Schema::create('keamanan_insiden', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_laporan', 50)->unique();
                $table->date('tanggal')->index();
                $table->time('jam_kejadian');
                $table->string('lokasi_kejadian');
                $table->string('judul_insiden');
                $table->text('kronologi');
                $table->string('pihak_terlibat')->nullable();
                $table->string('tingkat_urgensi', 30)->default('sedang'); // rendah, sedang, tinggi, darurat
                $table->string('tindakan_diambil')->nullable();
                $table->string('status_penyelesaian', 50)->default('dalam_penanganan')->index(); // dalam_penanganan, selesai, diserahkan_ke_polsek
                $table->string('petugas_satpam_ptk_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keamanan_insiden');
        Schema::dropIfExists('keamanan_patroli');
        Schema::dropIfExists('keamanan_buku_tamu');
    }
};
