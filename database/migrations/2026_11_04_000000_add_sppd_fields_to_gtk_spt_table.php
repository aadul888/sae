<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tambahan SPPD pada gtk_spt agar hasil cetak SPT selaras dengan
     * format resmi SPPD (Surat Perintah Perjalanan Dinas).
     */
    public function up(): void
    {
        if (!Schema::hasTable('gtk_spt')) {
            return;
        }

        Schema::table('gtk_spt', function (Blueprint $table) {
            if (!Schema::hasColumn('gtk_spt', 'waktu_pelaksanaan')) {
                $table->string('waktu_pelaksanaan', 100)->nullable()->after('lokasi_tujuan');
            }
            if (!Schema::hasColumn('gtk_spt', 'alamat_tujuan')) {
                $table->string('alamat_tujuan', 255)->nullable()->after('waktu_pelaksanaan');
            }
            if (!Schema::hasColumn('gtk_spt', 'alat_angkutan')) {
                $table->string('alat_angkutan', 100)->nullable()->default('Kendaraan Darat')->after('alamat_tujuan');
            }
            if (!Schema::hasColumn('gtk_spt', 'tempat_berangkat')) {
                $table->string('tempat_berangkat', 150)->nullable()->after('alat_angkutan');
            }
            if (!Schema::hasColumn('gtk_spt', 'pengikut')) {
                $table->text('pengikut')->nullable()->after('tempat_berangkat');
            }
            if (!Schema::hasColumn('gtk_spt', 'instansi_pembebanan')) {
                $table->string('instansi_pembebanan', 150)->nullable()->after('pengikut');
            }
            if (!Schema::hasColumn('gtk_spt', 'akun_kodering')) {
                $table->string('akun_kodering', 100)->nullable()->after('instansi_pembebanan');
            }
            if (!Schema::hasColumn('gtk_spt', 'tingkat_biaya')) {
                $table->string('tingkat_biaya', 100)->nullable()->after('akun_kodering');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gtk_spt')) {
            return;
        }

        Schema::table('gtk_spt', function (Blueprint $table) {
            foreach ([
                'waktu_pelaksanaan', 'alamat_tujuan', 'alat_angkutan', 'tempat_berangkat',
                'pengikut', 'instansi_pembebanan', 'akun_kodering', 'tingkat_biaya',
            ] as $col) {
                if (Schema::hasColumn('gtk_spt', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
