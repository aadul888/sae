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
        if (!Schema::hasTable('sekolah_meta')) {
            Schema::create('sekolah_meta', function (Blueprint $table) {
                $table->id();
                $table->string('sekolah_id', 50)->nullable()->index()->comment('ID Sekolah Dapodik');
                $table->string('npsn', 20)->nullable()->index()->comment('NPSN Sekolah');
                $table->string('logo_path', 255)->nullable()->comment('Path file di storage (assets/sekolah/...)');
                $table->unsignedInteger('logo_size')->nullable()->comment('Ukuran file logo dalam bytes');
                $table->string('kop_path', 255)->nullable()->comment('Path kop sekolah di storage');
                $table->unsignedInteger('kop_size')->nullable()->comment('Ukuran file kop dalam bytes');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sekolah_meta');
    }
};
