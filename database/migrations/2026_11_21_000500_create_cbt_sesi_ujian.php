<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CBT — Jadwal & kuota sesi ujian per periode asesmen.
     *
     * Tiap baris = satu slot pelaksanaan (mapel/rombel + tanggal + jam) di dalam
     * sebuah periode. `kuota` = jumlah maksimal peserta per slot (0 = tanpa batas).
     * Hanya penjadwalan — pelaksanaan serentak (attempt/jawaban) belum ada (§10).
     *
     * Tanpa FK constraint (pembelajaran_id = UUID Dapodik). Idempoten.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cbt_sesi_ujian')) {
            Schema::create('cbt_sesi_ujian', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('periode_id')->index();
                $table->string('pembelajaran_id', 50)->index();
                $table->date('tanggal')->nullable();
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->unsignedSmallInteger('kuota')->default(0)->comment('0 = tanpa batas');
                $table->string('ruang', 100)->nullable();
                $table->string('catatan', 500)->nullable();
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_sesi_ujian');
    }
};
