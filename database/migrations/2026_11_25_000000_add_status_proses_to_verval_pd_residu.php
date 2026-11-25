<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom status proses manual per baris Residu Verval PD:
     * belum (Belum Diproses) | proses (Sedang Proses) | selesai (Selesai).
     */
    public function up(): void
    {
        if (Schema::hasTable('verval_pd_residu') && !Schema::hasColumn('verval_pd_residu', 'status_proses')) {
            Schema::table('verval_pd_residu', function (Blueprint $table) {
                $table->string('status_proses', 20)->default('belum')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('verval_pd_residu') && Schema::hasColumn('verval_pd_residu', 'status_proses')) {
            Schema::table('verval_pd_residu', function (Blueprint $table) {
                $table->dropColumn('status_proses');
            });
        }
    }
};
