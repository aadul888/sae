<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom catatan masalah manual per baris Residu Verval PD.
     */
    public function up(): void
    {
        if (Schema::hasTable('verval_pd_residu') && !Schema::hasColumn('verval_pd_residu', 'catatan')) {
            Schema::table('verval_pd_residu', function (Blueprint $table) {
                $table->text('catatan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('verval_pd_residu') && Schema::hasColumn('verval_pd_residu', 'catatan')) {
            Schema::table('verval_pd_residu', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }
};
