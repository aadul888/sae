<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswa_usulan_perubahan') && !Schema::hasColumn('siswa_usulan_perubahan', 'batch_id')) {
            Schema::table('siswa_usulan_perubahan', function (Blueprint $table) {
                $table->string('batch_id', 64)->nullable()->index()->after('peserta_didik_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswa_usulan_perubahan', 'batch_id')) {
            Schema::table('siswa_usulan_perubahan', function (Blueprint $table) {
                $table->dropColumn('batch_id');
            });
        }
    }
};
