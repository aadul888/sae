<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singkatan nama mapel (Buku Induk).
     *
     * `mapel_singkatan` menyimpan JSON {nama_mapel: singkatan} untuk dipakai
     * sebagai judul kolom ringkas di tab Input Nilai (nama lengkap tetap
     * tersimpan pada data nilai & template impor).
     */
    public function up(): void
    {
        if (Schema::hasTable('buku_induk_pengaturan') && !Schema::hasColumn('buku_induk_pengaturan', 'mapel_singkatan')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->text('mapel_singkatan')->nullable()->after('mapel_jurusan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buku_induk_pengaturan', 'mapel_singkatan')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->dropColumn('mapel_singkatan');
            });
        }
    }
};
