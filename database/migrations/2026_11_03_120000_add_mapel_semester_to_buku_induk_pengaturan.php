<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemetaan mata pelajaran PER SEMESTER (Buku Induk).
     *
     * `mapel_semester` menyimpan JSON {semester: {umum: [...], kejuruan: [...]}}.
     * Semester yang tidak punya kunci = belum dipetakan (pakai daftar mapel global);
     * semester dengan kunci tetapi daftar kosong = memang tidak ada mapel diajarkan.
     */
    public function up(): void
    {
        if (Schema::hasTable('buku_induk_pengaturan') && !Schema::hasColumn('buku_induk_pengaturan', 'mapel_semester')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->text('mapel_semester')->nullable()->after('mapel_kejuruan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buku_induk_pengaturan', 'mapel_semester')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->dropColumn('mapel_semester');
            });
        }
    }
};
