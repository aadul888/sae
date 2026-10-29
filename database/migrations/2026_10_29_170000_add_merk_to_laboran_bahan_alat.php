<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laboran_bahan_alat') && !Schema::hasColumn('laboran_bahan_alat', 'merk')) {
            Schema::table('laboran_bahan_alat', function (Blueprint $table) {
                $table->string('merk', 100)->nullable()->after('nama_item');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laboran_bahan_alat') && Schema::hasColumn('laboran_bahan_alat', 'merk')) {
            Schema::table('laboran_bahan_alat', function (Blueprint $table) {
                $table->dropColumn('merk');
            });
        }
    }
};
