<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jadwal_guru_preferensi')) {
            Schema::create('jadwal_guru_preferensi', function (Blueprint $table) {
                $table->id();
                $table->string('ptk_id', 50)->index();
                $table->json('hari_off')->nullable()->comment('Daftar hari tidak bisa mengajar (misal: ["Senin", "Jumat"])');
                $table->json('jam_unavailable')->nullable()->comment('Detail jam tidak bersedia mengajar');
                $table->unsignedSmallInteger('max_jp_per_hari')->nullable()->comment('Batas maksimal JP per hari');
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_guru_preferensi');
    }
};
