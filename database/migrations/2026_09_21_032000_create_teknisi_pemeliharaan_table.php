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
        if (!Schema::hasTable('teknisi_pemeliharaan')) {
            Schema::create('teknisi_pemeliharaan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_pemeliharaan', 50)->unique();
                $table->string('nama_kegiatan', 150);
                $table->string('kategori', 50)->default('kelistrikan'); // kelistrikan, perairan, bangunan, kebersihan, ac_pendingin, it_jaringan
                $table->string('lokasi_aset', 100);
                $table->string('frekuensi', 50)->default('bulanan'); // mingguan, bulanan, triwulan, semesteran, tahunan
                $table->date('tgl_jadwal')->index();
                $table->date('tgl_realisasi')->nullable();
                $table->string('penanggung_jawab', 100)->nullable();
                $table->string('status', 30)->default('terjadwal')->index(); // terjadwal, proses, selesai, tertunda
                $table->decimal('biaya', 12, 2)->default(0);
                $table->text('catatan_hasil')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisi_pemeliharaan');
    }
};
