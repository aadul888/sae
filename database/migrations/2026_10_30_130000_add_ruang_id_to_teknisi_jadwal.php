<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `ruang_id` pada jadwal area tugas & pemeliharaan agar keduanya bisa
 * difilter langsung terhadap penanggung jawab ruang di master Sarpras.
 *
 * Tanpa kolom ini filter harus menebak lewat nama area, sehingga penggantian
 * PJ atau penamaan ulang ruang tidak ikut menyesuaikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['teknisi_area_tugas' => 'area', 'teknisi_pemeliharaan' => 'lokasi_aset'] as $table => $nameColumn) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'ruang_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('ruang_id')->nullable()->index()->after('id');
            });

            if (!Schema::hasTable('sarpras_ruang')) {
                continue;
            }

            // Backfill: cocokkan nama ruang persis (kolom nama bersifat unik
            // secara praktik karena berasal dari master yang sama).
            foreach (DB::table('sarpras_ruang')->get(['id', 'nama_ruang']) as $r) {
                DB::table($table)
                    ->where($nameColumn, $r->nama_ruang)
                    ->whereNull('ruang_id')
                    ->update(['ruang_id' => $r->id]);
            }
        }
    }

    public function down(): void
    {
        foreach (['teknisi_area_tugas', 'teknisi_pemeliharaan'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'ruang_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['ruang_id']);
                    $t->dropColumn('ruang_id');
                });
            }
        }
    }
};
