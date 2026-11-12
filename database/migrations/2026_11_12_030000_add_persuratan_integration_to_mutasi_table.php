<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kesiswaan_mutasi')) {
            Schema::table('kesiswaan_mutasi', function (Blueprint $table) {
                if (!Schema::hasColumn('kesiswaan_mutasi', 'nomor_surat_penerimaan')) {
                    $table->string('nomor_surat_penerimaan')->nullable()->after('sekolah_tujuan_asal');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'surat_masuk_id')) {
                    $table->unsignedBigInteger('surat_masuk_id')->nullable()->after('file_surat_penerimaan');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'surat_keluar_id')) {
                    $table->unsignedBigInteger('surat_keluar_id')->nullable()->after('file_surat_mutasi');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kesiswaan_mutasi')) {
            Schema::table('kesiswaan_mutasi', function (Blueprint $table) {
                $cols = ['nomor_surat_penerimaan', 'surat_masuk_id', 'surat_keluar_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('kesiswaan_mutasi', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
