<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penyelarasan kolom inventaris aset Sarpras agar sama dengan modul Laboran:
 *  - Rincian kondisi per jumlah unit (jumlah_baik, jumlah_rusak_ringan, jumlah_rusak_berat)
 *  - Tanggal perolehan / pengadaan
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sarpras_aset')) {
            return;
        }

        Schema::table('sarpras_aset', function (Blueprint $table) {
            if (!Schema::hasColumn('sarpras_aset', 'tanggal_perolehan')) {
                $table->date('tanggal_perolehan')->nullable()->after('tahun_perolehan')
                    ->comment('Tanggal perolehan / pengadaan aset');
            }
            if (!Schema::hasColumn('sarpras_aset', 'jumlah_baik')) {
                $table->integer('jumlah_baik')->default(0)->after('kondisi')
                    ->comment('Jumlah unit dengan kondisi baik');
            }
            if (!Schema::hasColumn('sarpras_aset', 'jumlah_rusak_ringan')) {
                $table->integer('jumlah_rusak_ringan')->default(0)->after('jumlah_baik')
                    ->comment('Jumlah unit dengan kondisi rusak ringan');
            }
            if (!Schema::hasColumn('sarpras_aset', 'jumlah_rusak_berat')) {
                $table->integer('jumlah_rusak_berat')->default(0)->after('jumlah_rusak_ringan')
                    ->comment('Jumlah unit dengan kondisi rusak berat');
            }
        });

        // Backfill rincian kondisi dari data yang sudah ada
        DB::table('sarpras_aset')
            ->where('jumlah_baik', 0)
            ->where('jumlah_rusak_ringan', 0)
            ->where('jumlah_rusak_berat', 0)
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $total = (int) ($row->jumlah ?? 1);
                    $col = match ($row->kondisi) {
                        'rusak_ringan' => 'jumlah_rusak_ringan',
                        'rusak_berat'  => 'jumlah_rusak_berat',
                        default        => 'jumlah_baik',
                    };

                    DB::table('sarpras_aset')
                        ->where('id', $row->id)
                        ->update([$col => $total]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sarpras_aset')) {
            return;
        }

        Schema::table('sarpras_aset', function (Blueprint $table) {
            $cols = array_filter(
                ['tanggal_perolehan', 'jumlah_baik', 'jumlah_rusak_ringan', 'jumlah_rusak_berat'],
                fn($c) => Schema::hasColumn('sarpras_aset', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
