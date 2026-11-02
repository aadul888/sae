<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('peserta_didik_izin_keluar')) {
            Schema::table('peserta_didik_izin_keluar', function (Blueprint $table) {
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'wali_kelas_ptk_id')) {
                    $table->string('wali_kelas_ptk_id')->nullable()->after('guru_pengampu_ptk_id');
                }
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'wali_status')) {
                    $table->string('wali_status', 50)->default('menunggu')->after('wali_kelas_ptk_id');
                }
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'wali_approved_at')) {
                    $table->timestamp('wali_approved_at')->nullable()->after('wali_status');
                }
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'wali_catatan')) {
                    $table->text('wali_catatan')->nullable()->after('wali_approved_at');
                }
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'piket_status')) {
                    $table->string('piket_status', 50)->default('menunggu')->after('wali_catatan');
                }
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'piket_approved_at')) {
                    $table->timestamp('piket_approved_at')->nullable()->after('piket_status');
                }
                if (!Schema::hasColumn('peserta_didik_izin_keluar', 'piket_catatan')) {
                    $table->text('piket_catatan')->nullable()->after('piket_approved_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('peserta_didik_izin_keluar')) {
            Schema::table('peserta_didik_izin_keluar', function (Blueprint $table) {
                $columns = [
                    'wali_kelas_ptk_id',
                    'wali_status',
                    'wali_approved_at',
                    'wali_catatan',
                    'piket_status',
                    'piket_approved_at',
                    'piket_catatan',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('peserta_didik_izin_keluar', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
