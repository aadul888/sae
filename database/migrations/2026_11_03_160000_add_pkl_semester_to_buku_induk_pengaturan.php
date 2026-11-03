<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pelaksanaan PKL disimpan per SEMESTER (bukan per tingkat).
     *
     * `pkl_semester` menyimpan JSON daftar semester, mis. `[5]`. Semester yang
     * terdaftar otomatis terkunci: hanya mapel "Praktik Kerja Lapangan" yang
     * berlaku. Kolom lama `pkl_tingkat` tetap dibaca sebagai cadangan.
     */
    public function up(): void
    {
        if (Schema::hasTable('buku_induk_pengaturan') && !Schema::hasColumn('buku_induk_pengaturan', 'pkl_semester')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->text('pkl_semester')->nullable()->after('pkl_tingkat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buku_induk_pengaturan', 'pkl_semester')) {
            Schema::table('buku_induk_pengaturan', function (Blueprint $table) {
                $table->dropColumn('pkl_semester');
            });
        }
    }
};
