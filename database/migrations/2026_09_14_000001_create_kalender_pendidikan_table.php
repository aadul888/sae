<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kalender_pendidikan')) {
            Schema::create('kalender_pendidikan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('nama_kegiatan', 200);
                $table->string('tipe', 50)->default('kegiatan_sekolah')->index();
                $table->date('tanggal_mulai')->index();
                $table->date('tanggal_selesai')->index();
                $table->boolean('libur_pd')->default(false)->index();
                $table->boolean('libur_guru')->default(false)->index();
                $table->boolean('libur_tendik')->default(false)->index();
                $table->string('warna', 20)->default('#3b82f6');
                $table->text('keterangan')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_pendidikan');
    }
};
