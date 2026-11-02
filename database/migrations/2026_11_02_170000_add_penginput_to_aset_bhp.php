<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak penginput baris Inventaris Aset & BHP.
 *
 * Menandai SIAPA yang mengisi/mengunggah baris (audit), terpisah dari PJ
 * (`penanggung_jawab_ptk_id`) yang menunjukkan tanggung jawab per jurusan.
 * Pola kolom mengikuti tabel modul Teknisi (`petugas_ptk_id`, `petugas_nama`,
 * `user_id`).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sarpras_aset', 'laboran_bahan_alat'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'petugas_ptk_id')) {
                    $t->string('petugas_ptk_id', 100)->nullable()->index();
                }
                if (!Schema::hasColumn($table, 'petugas_nama')) {
                    $t->string('petugas_nama', 150)->nullable();
                }
                if (!Schema::hasColumn($table, 'user_id')) {
                    $t->string('user_id', 100)->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['sarpras_aset', 'laboran_bahan_alat'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['petugas_ptk_id', 'user_id'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropIndex([$col]);
                    }
                }
                foreach (['petugas_ptk_id', 'petugas_nama', 'user_id'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
