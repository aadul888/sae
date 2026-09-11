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
        if (!Schema::hasTable('peserta_didik_meta')) {
            Schema::create('peserta_didik_meta', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->unique()->comment('ID Peserta Didik Dapodik (dari peserta_didik.peserta_didik_id)');
                $table->string('nisn', 20)->nullable()->index()->comment('NISN Peserta Didik untuk pencarian cepat');
                $table->string('foto_path', 255)->nullable()->comment('Path file di storage (assets/peserta-didik/foto/...)');
                $table->unsignedInteger('foto_size')->nullable()->comment('Ukuran file dalam bytes');
                $table->unsignedSmallInteger('foto_width')->nullable()->comment('Lebar gambar dalam piksel');
                $table->unsignedSmallInteger('foto_height')->nullable()->comment('Tinggi gambar dalam piksel');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_didik_meta');
    }
};
