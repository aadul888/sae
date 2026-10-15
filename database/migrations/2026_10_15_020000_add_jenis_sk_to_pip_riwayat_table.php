<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pip_riwayat')) {
            return;
        }

        Schema::table('pip_riwayat', function (Blueprint $table) {
            if (!Schema::hasColumn('pip_riwayat', 'jenis_sk')) {
                $table->string('jenis_sk', 30)->default('pemberian')->after('nomor_sk')->index();
            }
            if (!Schema::hasColumn('pip_riwayat', 'tanggal_aktivasi')) {
                $table->string('tanggal_aktivasi', 30)->nullable()->after('tanggal_cair');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pip_riwayat')) {
            return;
        }

        Schema::table('pip_riwayat', function (Blueprint $table) {
            if (Schema::hasColumn('pip_riwayat', 'tanggal_aktivasi')) {
                $table->dropColumn('tanggal_aktivasi');
            }
            if (Schema::hasColumn('pip_riwayat', 'jenis_sk')) {
                $table->dropColumn('jenis_sk');
            }
        });
    }
};
