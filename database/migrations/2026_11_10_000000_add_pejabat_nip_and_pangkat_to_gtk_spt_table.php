<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom NIP dan Pangkat/Golongan pejabat penandatangan SPT & SPPD.
     */
    public function up(): void
    {
        if (!Schema::hasTable('gtk_spt')) {
            return;
        }

        Schema::table('gtk_spt', function (Blueprint $table) {
            if (!Schema::hasColumn('gtk_spt', 'pejabat_nip')) {
                $table->string('pejabat_nip', 50)->nullable()->after('pejabat_nama');
            }
            if (!Schema::hasColumn('gtk_spt', 'pejabat_pangkat_golongan')) {
                $table->string('pejabat_pangkat_golongan', 100)->nullable()->after('pejabat_nip');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gtk_spt')) {
            return;
        }

        Schema::table('gtk_spt', function (Blueprint $table) {
            if (Schema::hasColumn('gtk_spt', 'pejabat_pangkat_golongan')) {
                $table->dropColumn('pejabat_pangkat_golongan');
            }
            if (Schema::hasColumn('gtk_spt', 'pejabat_nip')) {
                $table->dropColumn('pejabat_nip');
            }
        });
    }
};
