<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah data pengadaan pada inventaris alat & bahan laboratorium:
 *  - tanggal_pengadaan : tanggal perolehan/pengadaan
 *  - tahun_pengadaan   : tahun perolehan (untuk filter & laporan cepat)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laboran_bahan_alat')) {
            return;
        }

        Schema::table('laboran_bahan_alat', function (Blueprint $table) {
            if (!Schema::hasColumn('laboran_bahan_alat', 'tanggal_pengadaan')) {
                $table->date('tanggal_pengadaan')->nullable()->after('lokasi_lemari_rak');
            }
            if (!Schema::hasColumn('laboran_bahan_alat', 'tahun_pengadaan')) {
                $table->unsignedSmallInteger('tahun_pengadaan')->nullable()->after('tanggal_pengadaan');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('laboran_bahan_alat')) {
            return;
        }

        Schema::table('laboran_bahan_alat', function (Blueprint $table) {
            $cols = array_filter(
                ['tanggal_pengadaan', 'tahun_pengadaan'],
                fn($c) => Schema::hasColumn('laboran_bahan_alat', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
