<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanggung jawab (PJ) baris Inventaris Aset & BHP.
 *
 * PJ = Laboran jurusan ybs (diisi OTOMATIS dari `jurusan_id`); baris umum
 * (tanpa jurusan) ber-PJ NULL = tanggung jawab bersama (Administrasi
 * Sarpras, Teknisi & IT). Nama kolom mengikuti
 * `sarpras_ruang.penanggung_jawab_ptk_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sarpras_aset')
            && !Schema::hasColumn('sarpras_aset', 'penanggung_jawab_ptk_id')) {
            Schema::table('sarpras_aset', function (Blueprint $t) {
                $t->string('penanggung_jawab_ptk_id', 100)->nullable()->index()->after('jurusan_id');
            });
        }

        if (Schema::hasTable('laboran_bahan_alat')
            && !Schema::hasColumn('laboran_bahan_alat', 'penanggung_jawab_ptk_id')) {
            Schema::table('laboran_bahan_alat', function (Blueprint $t) {
                $t->string('penanggung_jawab_ptk_id', 100)->nullable()->index()->after('jurusan_id');
            });
        }

        // Backfill: isi PJ baris lama dari penugasan LABORAN tiap jurusan.
        if (\App\Support\Table::exists('ptk_tugas_tambahan')) {
            foreach (['sarpras_aset', 'laboran_bahan_alat'] as $table) {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'penanggung_jawab_ptk_id')) {
                    continue;
                }
                $rows = \Illuminate\Support\Facades\DB::table($table)
                    ->whereNotNull('jurusan_id')
                    ->whereNull('penanggung_jawab_ptk_id')
                    ->select('id', 'jurusan_id')
                    ->get();
                foreach ($rows as $row) {
                    $pj = \App\Support\PenanggungJawab::untukJurusan($row->jurusan_id);
                    if ($pj) {
                        \Illuminate\Support\Facades\DB::table($table)
                            ->where('id', $row->id)
                            ->update(['penanggung_jawab_ptk_id' => $pj]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['sarpras_aset', 'laboran_bahan_alat'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'penanggung_jawab_ptk_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['penanggung_jawab_ptk_id']);
                    $t->dropColumn('penanggung_jawab_ptk_id');
                });
            }
        }
    }
};
