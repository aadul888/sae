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
            if (!Schema::hasColumn('jadwal_pengaturan', 'max_jp_per_sesi')) {
                $table->unsignedTinyInteger('max_jp_per_sesi')->default(3)->after('skema_hari');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pengaturan', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_pengaturan', 'max_jp_per_sesi')) {
                $table->dropColumn('max_jp_per_sesi');
            }
        });
    }
};
