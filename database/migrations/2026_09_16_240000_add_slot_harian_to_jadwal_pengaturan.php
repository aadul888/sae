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
            if (!Schema::hasColumn('jadwal_pengaturan', 'slot_harian')) {
                $table->json('slot_harian')->nullable()->after('total_slot_jp')->comment('Konfigurasi total slot JP dan jam selesai per hari');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pengaturan', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_pengaturan', 'slot_harian')) {
                $table->dropColumn('slot_harian');
            }
        });
    }
};
