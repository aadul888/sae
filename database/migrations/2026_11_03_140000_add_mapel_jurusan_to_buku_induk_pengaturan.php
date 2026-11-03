<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemetaan mapel per jurusan (Buku Induk).
     *
     * `mapel_jurusan` menyimpan JSON {jurusan_id: {semester: {umum: [], kejuruan: []}}}
     * dengan jurusan_id = jurusan konsentrasi (XI/XII) Dapodik. Program keahlian (X)
     * dipetakan ke konsentrasi pasangannya. Cadangan: `mapel_semester` (global).
     */
    public function up(): void
    {
        if (Schema::hasTable('buku_induk_pengaturan') && !Schema::hasColumn('buku_induk_pengaturan', 'mapel_jurusan')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->text('mapel_jurusan')->nullable()->after('pkl_tingkat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buku_induk_pengaturan', 'mapel_jurusan')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->dropColumn('mapel_jurusan');
            });
        }
    }
};
