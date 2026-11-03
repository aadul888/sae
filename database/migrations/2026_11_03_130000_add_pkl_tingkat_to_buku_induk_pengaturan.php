<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pelaksanaan PKL per tingkat (Buku Induk).
     *
     * `pkl_tingkat` menyimpan JSON {"10": semester, "11": null, "12": 5} — nilai
     * semester 1..6 bila PKL dilaksanakan pada tingkat itu, null bila tidak.
     * PKL hanya butuh SATU nilai per tingkat.
     */
    public function up(): void
    {
        if (Schema::hasTable('buku_induk_pengaturan') && !Schema::hasColumn('buku_induk_pengaturan', 'pkl_tingkat')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->text('pkl_tingkat')->nullable()->after('mapel_semester');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buku_induk_pengaturan', 'pkl_tingkat')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->dropColumn('pkl_tingkat');
            });
        }
    }
};
