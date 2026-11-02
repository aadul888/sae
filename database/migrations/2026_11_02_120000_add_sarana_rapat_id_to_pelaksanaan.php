<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautkan baris Ceklist Persiapan Rapat ke Jurnal Pelaksanaan Tugas.
 *
 * Saat laporan sarana rapat disimpan, satu baris pelaksanaan ikut dibuat agar
 * persiapan rapat muncul di Jurnal Pelaksanaan (dan ikut cakupan PJ ruang).
 * Kolom `area_tugas_id` pada baris tersebut dibiarkan kosong — baris ini
 * ditautkan lewat `sarana_rapat_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teknisi_pelaksanaan_harian')
            || Schema::hasColumn('teknisi_pelaksanaan_harian', 'sarana_rapat_id')) {
            return;
        }

        Schema::table('teknisi_pelaksanaan_harian', function (Blueprint $t) {
            $t->unsignedBigInteger('sarana_rapat_id')->nullable()->index()->after('ruang_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('teknisi_pelaksanaan_harian')
            && Schema::hasColumn('teknisi_pelaksanaan_harian', 'sarana_rapat_id')) {
            Schema::table('teknisi_pelaksanaan_harian', function (Blueprint $t) {
                $t->dropIndex(['sarana_rapat_id']);
                $t->dropColumn('sarana_rapat_id');
            });
        }
    }
};
