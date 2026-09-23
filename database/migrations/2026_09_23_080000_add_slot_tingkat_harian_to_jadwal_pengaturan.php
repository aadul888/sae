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
        Schema::table('jadwal_pengaturan', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwal_pengaturan', 'slot_tingkat_harian')) {
                $table->json('slot_tingkat_harian')->nullable()->after('slot_harian')->comment('Rincian alokasi slot harian per tingkat pendidikan (X, XI, XII)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pengaturan', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_pengaturan', 'slot_tingkat_harian')) {
                $table->dropColumn('slot_tingkat_harian');
            }
        });
    }
};
