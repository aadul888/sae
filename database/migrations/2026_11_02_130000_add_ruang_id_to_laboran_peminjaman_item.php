<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peminjaman terpadu mencakup RUANG/BANGUNAN selain alat & aset.
 *
 * `laboran_peminjaman_item.sumber` kini bernilai: inventaris | aset | ruang.
 * Kolom `ruang_id` menampung `sarpras_ruang.id` bila sumber = ruang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laboran_peminjaman_item')
            || Schema::hasColumn('laboran_peminjaman_item', 'ruang_id')) {
            return;
        }

        Schema::table('laboran_peminjaman_item', function (Blueprint $t) {
            $t->unsignedBigInteger('ruang_id')->nullable()->index()->after('aset_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('laboran_peminjaman_item')
            && Schema::hasColumn('laboran_peminjaman_item', 'ruang_id')) {
            Schema::table('laboran_peminjaman_item', function (Blueprint $t) {
                $t->dropIndex(['ruang_id']);
                $t->dropColumn('ruang_id');
            });
        }
    }
};
