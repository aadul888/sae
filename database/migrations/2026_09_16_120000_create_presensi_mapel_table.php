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
        if (!Schema::hasTable('presensi_mapel')) {
            Schema::create('presensi_mapel', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('rombongan_belajar_id', 50)->index()->comment('ID Rombel Dapodik');
                $table->string('pembelajaran_id', 50)->nullable()->index()->comment('ID Pembelajaran/Mapel Rombel Dapodik');
                $table->string('ptk_id', 50)->nullable()->index()->comment('ID Guru Pengampu Dapodik');
                $table->string('mata_pelajaran_id', 50)->nullable();
                $table->string('nama_mata_pelajaran', 150)->nullable();
                $table->string('peserta_didik_id', 50)->index();
                $table->string('nisn', 20)->nullable()->index();
                $table->date('tanggal')->index()->comment('Tanggal KBM');
                $table->string('jam_ke', 20)->nullable()->comment('Jam ke-1, 2, dsb');
                
                // Status kehadiran di kelas: H = Hadir, T = Terlambat, I = Izin, S = Sakit, A = Alpha, D = Dispen
                $table->string('status', 5)->default('H')->index();
                $table->text('keterangan')->nullable();
                
                // Foreign linkage untuk Agenda Kelas
                $table->unsignedBigInteger('agenda_kelas_id')->nullable()->index()->comment('Relasi terintegrasi dengan Agenda Kelas');
                
                $table->string('created_by', 50)->nullable()->comment('User ID guru pencatat');
                $table->timestamps();

                // Unique per siswa per rombel per tanggal per pembelajaran & jam
                $table->unique(['peserta_didik_id', 'tanggal', 'rombongan_belajar_id', 'pembelajaran_id', 'jam_ke'], 'unique_presensi_siswa_mapel');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_mapel');
    }
};
