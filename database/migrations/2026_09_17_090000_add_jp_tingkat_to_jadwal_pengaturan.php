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
            if (!Schema::hasColumn('jadwal_pengaturan', 'jp_tingkat')) {
                $table->json('jp_tingkat')->nullable()->after('slot_harian')->comment('Alokasi JP per tingkat SMK (X: 50, XI: 48, XII: 46)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pengaturan', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_pengaturan', 'jp_tingkat')) {
                $table->dropColumn('jp_tingkat');
            }
        });
    }
};
