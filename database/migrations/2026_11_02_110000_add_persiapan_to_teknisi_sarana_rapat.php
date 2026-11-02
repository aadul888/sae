<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sarana Kegiatan Rapat kini menggabungkan persiapan kebersihan ruang rapat:
 * tahap penyiapan + kondisi ruang, di samping daftar sarana dari inventaris.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teknisi_sarana_rapat')) {
            return;
        }

        Schema::table('teknisi_sarana_rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('teknisi_sarana_rapat', 'tahap')) {
                $table->string('tahap', 30)->default('sebelum_acara')->index();
            }
            if (!Schema::hasColumn('teknisi_sarana_rapat', 'kondisi_ruang')) {
                $table->string('kondisi_ruang', 40)->default('bersih')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teknisi_sarana_rapat')) {
            return;
        }

        Schema::table('teknisi_sarana_rapat', function (Blueprint $table) {
            if (Schema::hasColumn('teknisi_sarana_rapat', 'tahap')) {
                $table->dropColumn('tahap');
            }
            if (Schema::hasColumn('teknisi_sarana_rapat', 'kondisi_ruang')) {
                $table->dropColumn('kondisi_ruang');
            }
        });
    }
};
