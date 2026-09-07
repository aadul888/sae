<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('kompetensi_keahlian');
    }

    public function down(): void
    {
        if (!Schema::hasTable('kompetensi_keahlian')) {
            Schema::create('kompetensi_keahlian', function (Blueprint $table) {
                $table->string('kode', 20)->primary();
                $table->string('nama', 150);
                $table->string('bidang_keahlian', 150)->nullable();
                $table->string('program_keahlian', 150)->nullable();
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
                $table->text('deskripsi')->nullable();
                $table->timestamps();
            });
        }
    }
};
