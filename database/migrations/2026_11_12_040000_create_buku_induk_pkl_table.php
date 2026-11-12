<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('buku_induk_pkl')) {
            Schema::create('buku_induk_pkl', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->unique();
                $table->string('nama_dudi', 200)->nullable();
                $table->text('alamat_dudi')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_induk_pkl');
    }
};
