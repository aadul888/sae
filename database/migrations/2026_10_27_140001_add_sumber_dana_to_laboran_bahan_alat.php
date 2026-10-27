<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laboran_bahan_alat')) {
            Schema::table('laboran_bahan_alat', function (Blueprint $table) {
                if (!Schema::hasColumn('laboran_bahan_alat', 'sumber_dana')) {
                    $table->string('sumber_dana', 50)->default('BOS')->nullable()->after('tahun_pengadaan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laboran_bahan_alat')) {
            Schema::table('laboran_bahan_alat', function (Blueprint $table) {
                if (Schema::hasColumn('laboran_bahan_alat', 'sumber_dana')) {
                    $table->dropColumn('sumber_dana');
                }
            });
        }
    }
};
