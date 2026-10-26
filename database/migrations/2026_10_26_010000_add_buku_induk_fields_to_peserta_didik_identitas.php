<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            return;
        }

        Schema::table('peserta_didik_identitas', function (Blueprint $table) {
            if (!Schema::hasColumn('peserta_didik_identitas', 'hubungan_wali')) {
                $table->string('hubungan_wali', 60)->nullable()->after('nik_wali');
            }
            if (!Schema::hasColumn('peserta_didik_identitas', 'golongan_darah')) {
                $table->string('golongan_darah', 5)->nullable()->after('berat_badan');
            }
            if (!Schema::hasColumn('peserta_didik_identitas', 'penyakit_pernah_diderita')) {
                $table->string('penyakit_pernah_diderita', 150)->nullable()->after('golongan_darah');
            }
            if (!Schema::hasColumn('peserta_didik_identitas', 'kelainan_jasmani')) {
                $table->string('kelainan_jasmani', 100)->nullable()->after('penyakit_pernah_diderita');
            }
            if (!Schema::hasColumn('peserta_didik_identitas', 'bahasa_sehari_hari')) {
                $table->string('bahasa_sehari_hari', 100)->nullable()->after('kelainan_jasmani');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            return;
        }

        Schema::table('peserta_didik_identitas', function (Blueprint $table) {
            foreach (['hubungan_wali', 'golongan_darah', 'penyakit_pernah_diderita', 'kelainan_jasmani', 'bahasa_sehari_hari'] as $col) {
                if (Schema::hasColumn('peserta_didik_identitas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
