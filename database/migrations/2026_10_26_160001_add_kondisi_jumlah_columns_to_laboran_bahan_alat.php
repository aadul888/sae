<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian kondisi alat berdasarkan JUMLAH unit.
 *
 * Contoh: 10 traktor -> 5 baik, 3 rusak ringan, 2 rusak berat.
 * Kolom lama `kondisi` tetap dipertahankan sebagai kondisi dominan/kesimpulan
 * agar modul lain (perawatan, kerusakan, peminjaman) tidak perlu diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laboran_bahan_alat')) {
            return;
        }

        Schema::table('laboran_bahan_alat', function (Blueprint $table) {
            if (!Schema::hasColumn('laboran_bahan_alat', 'jumlah_baik')) {
                $table->decimal('jumlah_baik', 10, 2)->default(0)->after('stok_tersedia');
            }
            if (!Schema::hasColumn('laboran_bahan_alat', 'jumlah_rusak_ringan')) {
                $table->decimal('jumlah_rusak_ringan', 10, 2)->default(0)->after('jumlah_baik');
            }
            if (!Schema::hasColumn('laboran_bahan_alat', 'jumlah_rusak_berat')) {
                $table->decimal('jumlah_rusak_berat', 10, 2)->default(0)->after('jumlah_rusak_ringan');
            }
            if (!Schema::hasColumn('laboran_bahan_alat', 'jumlah_kedaluwarsa')) {
                $table->decimal('jumlah_kedaluwarsa', 10, 2)->default(0)->after('jumlah_rusak_berat');
            }
        });

        // Backfill: seluruh stok lama dianggap sesuai kondisi yang tercatat.
        \Illuminate\Support\Facades\DB::table('laboran_bahan_alat')
            ->where('jumlah_baik', 0)
            ->where('jumlah_rusak_ringan', 0)
            ->where('jumlah_rusak_berat', 0)
            ->where('jumlah_kedaluwarsa', 0)
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $total = (float) $row->stok_total;
                    $col = match ($row->kondisi) {
                        'rusak_ringan' => 'jumlah_rusak_ringan',
                        'rusak_berat'  => 'jumlah_rusak_berat',
                        'kedaluwarsa'  => 'jumlah_kedaluwarsa',
                        default        => 'jumlah_baik',
                    };

                    \Illuminate\Support\Facades\DB::table('laboran_bahan_alat')
                        ->where('id', $row->id)
                        ->update([$col => $total]);
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
                ['jumlah_baik', 'jumlah_rusak_ringan', 'jumlah_rusak_berat', 'jumlah_kedaluwarsa'],
                fn($c) => Schema::hasColumn('laboran_bahan_alat', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
