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
        if (!Schema::hasTable('jurusan_meta')) {
            Schema::create('jurusan_meta', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->unique()->comment('ID Jurusan Dapodik (dari rombongan_belajar.jurusan_id)');
                $table->string('nama_jurusan', 200)->nullable();
                $table->string('singkatan', 50)->nullable();
                $table->string('logo_path', 255)->nullable()->comment('Path file di storage (assets/jurusan/...)');
                $table->unsignedInteger('logo_size')->nullable()->comment('Ukuran file dalam bytes');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurusan_meta');
    }
};
