<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kompetensi_keahlian', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('Kode kompetensi keahlian (misal: C2.01)');
            $table->string('nama', 200)->comment('Nama kompetensi keahlian');
            $table->string('bidang_keahlian', 200)->nullable()->comment('Bidang keahlian induk');
            $table->string('program_keahlian', 200)->nullable()->comment('Program keahlian');
            $table->year('tahun_berlaku')->nullable()->comment('Tahun kurikulum berlaku');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kompetensi_keahlian');
    }
};
