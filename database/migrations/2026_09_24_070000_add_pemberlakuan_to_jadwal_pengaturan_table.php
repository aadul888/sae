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
        if (Schema::hasTable('jadwal_pengaturan')) {
            Schema::table('jadwal_pengaturan', function (Blueprint $table) {
                if (!Schema::hasColumn('jadwal_pengaturan', 'status_jadwal')) {
                    $table->string('status_jadwal', 20)->default('aktif')->after('durasi_per_jp')->comment('aktif: Diberlakukan ke guru & siswa, draft: Masih dalam penyusunan');
                }
                if (!Schema::hasColumn('jadwal_pengaturan', 'is_diberlakukan')) {
                    $table->boolean('is_diberlakukan')->default(true)->after('status_jadwal')->comment('Flag apakah jadwal aktif diberlakukan');
                }
                if (!Schema::hasColumn('jadwal_pengaturan', 'diberlakukan_pada')) {
                    $table->timestamp('diberlakukan_pada')->nullable()->after('is_diberlakukan');
                }
                if (!Schema::hasColumn('jadwal_pengaturan', 'diberlakukan_oleh')) {
                    $table->string('diberlakukan_oleh', 100)->nullable()->after('diberlakukan_pada');
                }
                if (!Schema::hasColumn('jadwal_pengaturan', 'catatan_pemberlakuan')) {
                    $table->text('catatan_pemberlakuan')->nullable()->after('diberlakukan_oleh');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwal_pengaturan')) {
            Schema::table('jadwal_pengaturan', function (Blueprint $table) {
                $cols = ['status_jadwal', 'is_diberlakukan', 'diberlakukan_pada', 'diberlakukan_oleh', 'catatan_pemberlakuan'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('jadwal_pengaturan', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
