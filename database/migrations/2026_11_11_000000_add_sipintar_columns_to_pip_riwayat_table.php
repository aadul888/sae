<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tambahan sesuai header "database" SiPintar (SK Pemberian & SK Nominasi).
     */
    private const COLUMNS = [
        'sekolah_id'        => ['string', 50],
        'npsn'              => ['string', 20],
        'nomenklatur'       => ['string', 150],
        'nik'               => ['string', 20],
        'tahap_id'          => ['string', 50],
        'nama_rekening'     => ['string', 150],
        'nama_kartu'        => ['string', 100],
        'semester_id'       => ['string', 20],
        'layak_pip'         => ['string', 20],
        'tahap_keterangan'  => ['string', 150],
        'nama_pengusul'     => ['string', 150],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('pip_riwayat')) {
            return;
        }

        Schema::table('pip_riwayat', function (Blueprint $table) {
            foreach (self::COLUMNS as $name => [$type, $length]) {
                if (!Schema::hasColumn('pip_riwayat', $name)) {
                    $table->{$type}($name, $length)->nullable();
                }
            }
            if (!Schema::hasColumn('pip_riwayat', 'confirmation_text')) {
                $table->text('confirmation_text')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pip_riwayat')) {
            return;
        }

        Schema::table('pip_riwayat', function (Blueprint $table) {
            foreach (array_keys(self::COLUMNS) as $name) {
                if (Schema::hasColumn('pip_riwayat', $name)) {
                    $table->dropColumn($name);
                }
            }
            if (Schema::hasColumn('pip_riwayat', 'confirmation_text')) {
                $table->dropColumn('confirmation_text');
            }
        });
    }
};
