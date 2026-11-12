<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pip_penggunaan_dana')) {
            return;
        }

        Schema::table('pip_penggunaan_dana', function (Blueprint $table) {
            if (!Schema::hasColumn('pip_penggunaan_dana', 'catatan_verifikasi')) {
                $table->text('catatan_verifikasi')->nullable();
            }
            if (!Schema::hasColumn('pip_penggunaan_dana', 'diverifikasi_oleh')) {
                $table->string('diverifikasi_oleh', 100)->nullable();
            }
            if (!Schema::hasColumn('pip_penggunaan_dana', 'diverifikasi_pada')) {
                $table->timestamp('diverifikasi_pada')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pip_penggunaan_dana')) {
            return;
        }

        Schema::table('pip_penggunaan_dana', function (Blueprint $table) {
            $table->dropColumn(['catatan_verifikasi', 'diverifikasi_oleh', 'diverifikasi_pada']);
        });
    }
};
