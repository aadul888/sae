<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengumpulan tugas E-Learning memakai TAUTAN (link), bukan unggahan berkas
     * bebas. Tujuan: hemat storage & beban server. Lihat §14 RENCANA_EKOSISTEM.
     * Idempoten agar aman dijalankan di DB yang sudah/tidak punya kolom lama.
     */
    public function up(): void
    {
        if (!Schema::hasTable('learn_pengumpulan')) {
            return;
        }

        if (!Schema::hasColumn('learn_pengumpulan', 'link_url')) {
            Schema::table('learn_pengumpulan', function (Blueprint $table) {
                $table->string('link_url', 500)->nullable()->after('jawaban_teks');
            });
        }

        if (Schema::hasColumn('learn_pengumpulan', 'file_path')) {
            Schema::table('learn_pengumpulan', function (Blueprint $table) {
                $table->dropColumn('file_path');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('learn_pengumpulan')) {
            return;
        }

        if (!Schema::hasColumn('learn_pengumpulan', 'file_path')) {
            Schema::table('learn_pengumpulan', function (Blueprint $table) {
                $table->string('file_path', 500)->nullable()->after('jawaban_teks');
            });
        }

        if (Schema::hasColumn('learn_pengumpulan', 'link_url')) {
            Schema::table('learn_pengumpulan', function (Blueprint $table) {
                $table->dropColumn('link_url');
            });
        }
    }
};
