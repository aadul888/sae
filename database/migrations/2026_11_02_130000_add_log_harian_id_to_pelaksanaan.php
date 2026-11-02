<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautkan Pemakaian Bahan Kebersihan (baris `teknisi_log_harian`) ke Jurnal
 * Pelaksanaan Tugas (`teknisi_pelaksanaan_harian`).
 *
 * Setiap pemakaian bahan dicatat sekali di Pelaksanaan agar muncul pada jurnal
 * pelaksanaan (dan ikut cakupan PJ ruang). Kolom `area_tugas_id` dibiarkan
 * kosong — baris ditautkan lewat `log_harian_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teknisi_pelaksanaan_harian')
            || Schema::hasColumn('teknisi_pelaksanaan_harian', 'log_harian_id')) {
            return;
        }

        Schema::table('teknisi_pelaksanaan_harian', function (Blueprint $t) {
            $t->unsignedBigInteger('log_harian_id')->nullable()->index()->after('sarana_rapat_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teknisi_pelaksanaan_harian')
            || !Schema::hasColumn('teknisi_pelaksanaan_harian', 'log_harian_id')) {
            return;
        }

        Schema::table('teknisi_pelaksanaan_harian', function (Blueprint $t) {
            $t->dropColumn('log_harian_id');
        });
    }
};
