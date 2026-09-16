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
        // 1. Tambah jurusan_aktif ke presensi_pengaturan
        if (Schema::hasTable('presensi_pengaturan') && !Schema::hasColumn('presensi_pengaturan', 'jurusan_aktif')) {
            Schema::table('presensi_pengaturan', function (Blueprint $table) {
                $table->json('jurusan_aktif')->nullable()->after('hari_aktif');
            });
        }

        // 2. Tambah mode_presensi ke kalender_pendidikan (luring, daring, libur)
        if (Schema::hasTable('kalender_pendidikan') && !Schema::hasColumn('kalender_pendidikan', 'mode_presensi')) {
            Schema::table('kalender_pendidikan', function (Blueprint $table) {
                $table->string('mode_presensi', 20)->default('luring')->after('tipe');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('presensi_pengaturan') && Schema::hasColumn('presensi_pengaturan', 'jurusan_aktif')) {
            Schema::table('presensi_pengaturan', function (Blueprint $table) {
                $table->dropColumn('jurusan_aktif');
            });
        }

        if (Schema::hasTable('kalender_pendidikan') && Schema::hasColumn('kalender_pendidikan', 'mode_presensi')) {
            Schema::table('kalender_pendidikan', function (Blueprint $table) {
                $table->dropColumn('mode_presensi');
            });
        }
    }
};
