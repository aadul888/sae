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
        Schema::table('presensi_pengaturan', function (Blueprint $table) {
            if (!Schema::hasColumn('presensi_pengaturan', 'require_location')) {
                $table->boolean('require_location')->default(false)->after('allow_qr');
            }
            if (!Schema::hasColumn('presensi_pengaturan', 'latitude')) {
                $table->decimal('latitude', 11, 8)->nullable()->after('require_location');
            }
            if (!Schema::hasColumn('presensi_pengaturan', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('presensi_pengaturan', 'radius_meter')) {
                $table->unsignedInteger('radius_meter')->default(100)->after('longitude');
            }
        });

        Schema::table('presensi_harian', function (Blueprint $table) {
            if (!Schema::hasColumn('presensi_harian', 'latitude')) {
                $table->decimal('latitude', 11, 8)->nullable()->after('foto_pulang');
            }
            if (!Schema::hasColumn('presensi_harian', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('presensi_harian', 'jarak_meter')) {
                $table->decimal('jarak_meter', 8, 2)->nullable()->after('longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensi_pengaturan', function (Blueprint $table) {
            $table->dropColumn(['require_location', 'latitude', 'longitude', 'radius_meter']);
        });

        Schema::table('presensi_harian', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'jarak_meter']);
        });
    }
};
