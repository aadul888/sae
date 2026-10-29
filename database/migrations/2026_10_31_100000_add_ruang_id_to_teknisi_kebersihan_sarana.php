<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `ruang_id` pada modul kebersihan & sarana rapat agar cakupan datanya
 * mengikuti penanggung jawab ruang di master Sarpras (bukan nama ruang bebas).
 *
 * Tanpa kolom ini pencocokan hanya lewat nama, sehingga penggantian PJ atau
 * penamaan ulang ruang tidak ikut menggeser cakupan data teknisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['teknisi_kebersihan_ruang' => 'ruang_nama', 'teknisi_sarana_rapat' => 'ruang_nama'] as $table => $nameColumn) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'ruang_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('ruang_id')->nullable()->index()->after('id');
            });

            if (Schema::hasTable('sarpras_ruang')) {
                foreach (DB::table('sarpras_ruang')->get(['id', 'nama_ruang']) as $r) {
                    DB::table($table)
                        ->where($nameColumn, $r->nama_ruang)
                        ->whereNull('ruang_id')
                        ->update(['ruang_id' => $r->id]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['teknisi_kebersihan_ruang', 'teknisi_sarana_rapat'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'ruang_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['ruang_id']);
                    $t->dropColumn('ruang_id');
                });
            }
        }
    }
};
