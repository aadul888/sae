<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tandai asal akun agar akun yang diinput manual tidak tertimpa/terhapus
 * oleh sinkronisasi Dapodik (`FeederReceiverController`).
 *
 * Nilai: `dapodik` (dari feeder), `manual` (dibuat di SAE), `seed` (akun awal).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pengguna') || Schema::hasColumn('pengguna', 'sumber_data')) {
            return;
        }

        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('sumber_data', 20)->nullable()->index()->after('peran_id_str');
        });

        // Akun tanpa ptk_id & tanpa peserta_didik_id = buatan lokal, bukan Dapodik.
        DB::table('pengguna')
            ->where(fn ($q) => $q->whereNull('ptk_id')->orWhere('ptk_id', ''))
            ->where(fn ($q) => $q->whereNull('peserta_didik_id')->orWhere('peserta_didik_id', ''))
            ->update(['sumber_data' => 'manual']);

        DB::table('pengguna')
            ->where(fn ($q) => $q->where('pengguna_id', 'like', 'seed-%'))
            ->update(['sumber_data' => 'seed']);
    }

    public function down(): void
    {
        if (Schema::hasTable('pengguna') && Schema::hasColumn('pengguna', 'sumber_data')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->dropIndex(['sumber_data']);
                $table->dropColumn('sumber_data');
            });
        }
    }
};
