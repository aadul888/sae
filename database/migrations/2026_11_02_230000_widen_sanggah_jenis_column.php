<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kedisiplinan_pelanggaran')) {
            Schema::table('kedisiplinan_pelanggaran', function (Blueprint $table) {
                $table->string('sanggah_jenis', 255)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kedisiplinan_pelanggaran')) {
            Schema::table('kedisiplinan_pelanggaran', function (Blueprint $table) {
                $table->string('sanggah_jenis', 50)->nullable()->change();
            });
        }
    }
};
