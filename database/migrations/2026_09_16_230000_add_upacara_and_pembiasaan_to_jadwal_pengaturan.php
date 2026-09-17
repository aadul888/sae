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
            if (!Schema::hasColumn('jadwal_pengaturan', 'upacara')) {
                $table->json('upacara')->nullable()->after('istirahat')->comment('Pengaturan Upacara Bendera otomatis');
            }
            if (!Schema::hasColumn('jadwal_pengaturan', 'pembiasaan')) {
                $table->json('pembiasaan')->nullable()->after('upacara')->comment('Pengaturan Pembiasaan rutin (Jumat/hari tertentu)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pengaturan', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_pengaturan', 'pembiasaan')) {
                $table->dropColumn('pembiasaan');
            }
            if (Schema::hasColumn('jadwal_pengaturan', 'upacara')) {
                $table->dropColumn('upacara');
            }
        });
    }
};
