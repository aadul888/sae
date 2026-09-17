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
        Schema::create('presensi_mengajar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jadwal_kbm_id')->nullable()->index()->comment('Relasi opsional ke tabel jadwal_kbm');
            $table->string('ptk_id', 50)->index()->comment('ID PTK / Guru yang mengampu KBM');
            $table->string('rombongan_belajar_id', 50)->index()->comment('ID Kelas Rombongan Belajar');
            $table->string('pembelajaran_id', 50)->nullable()->index()->comment('ID Pembelajaran Dapodik');
            $table->string('mata_pelajaran_id', 50)->nullable();
            $table->string('nama_mata_pelajaran', 150);
            $table->date('tanggal')->index();
            $table->string('hari', 20);
            $table->unsignedSmallInteger('jam_ke_mulai')->default(1);
            $table->unsignedSmallInteger('jam_ke_selesai')->default(1);
            $table->unsignedSmallInteger('total_jp')->default(1);
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->string('status', 20)->default('H')->comment('H=Hadir, S=Sakit, I=Izin, T=Tugas Luar, D=Digantikan/Inval');
            $table->string('guru_pengganti_ptk_id', 50)->nullable()->comment('PTK ID guru pengganti jika berhalangan');
            $table->string('nama_guru_pengganti', 150)->nullable();
            $table->unsignedSmallInteger('jumlah_siswa_hadir')->nullable();
            $table->unsignedSmallInteger('jumlah_siswa_tidak_hadir')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->timestamps();

            $table->index(['ptk_id', 'tanggal']);
            $table->index(['rombongan_belajar_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_mengajar');
    }
};
