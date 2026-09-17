<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('presensi_pengaturan')) {
            Schema::table('presensi_pengaturan', function (Blueprint $table) {
                if (!Schema::hasColumn('presensi_pengaturan', 'kode_akses')) {
                    $table->string('kode_akses', 50)->default('SAE123')->after('auto_alpha_time')->comment('Kode akses otorisasi terminal kiosk publik tanpa login');
                }
            });

            // Pastikan baris konfigurasi pertama memiliki kode akses
            DB::table('presensi_pengaturan')
                ->where('id', 1)
                ->whereNull('kode_akses')
                ->update(['kode_akses' => 'SAE123']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('presensi_pengaturan')) {
            Schema::table('presensi_pengaturan', function (Blueprint $table) {
                if (Schema::hasColumn('presensi_pengaturan', 'kode_akses')) {
                    $table->dropColumn('kode_akses');
                }
            });
        }
    }
};
