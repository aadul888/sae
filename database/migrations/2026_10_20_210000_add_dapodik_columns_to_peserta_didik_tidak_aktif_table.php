<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('peserta_didik_tidak_aktif')) {
            Schema::table('peserta_didik_tidak_aktif', function (Blueprint $table) {
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'kebutuhan_khusus')) {
                    $table->string('kebutuhan_khusus', 100)->nullable()->after('agama_id_str');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'rt')) {
                    $table->string('rt', 10)->nullable()->after('alamat_jalan');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'rw')) {
                    $table->string('rw', 10)->nullable()->after('rt');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'dusun')) {
                    $table->string('dusun', 100)->nullable()->after('rw');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'kelurahan')) {
                    $table->string('kelurahan', 100)->nullable()->after('dusun');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'kecamatan')) {
                    $table->string('kecamatan', 100)->nullable()->after('kelurahan');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'kode_pos')) {
                    $table->string('kode_pos', 10)->nullable()->after('kecamatan');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'jenis_tinggal')) {
                    $table->string('jenis_tinggal', 100)->nullable()->after('kode_pos');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'alat_transportasi')) {
                    $table->string('alat_transportasi', 100)->nullable()->after('jenis_tinggal');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'nomor_telepon_rumah')) {
                    $table->string('nomor_telepon_rumah', 50)->nullable()->after('alat_transportasi');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'skhun')) {
                    $table->string('skhun', 100)->nullable()->after('nomor_telepon_rumah');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'penerima_kps')) {
                    $table->string('penerima_kps', 20)->nullable()->after('skhun');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'no_kps')) {
                    $table->string('no_kps', 100)->nullable()->after('penerima_kps');
                }

                // Data Ayah
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'nama_ayah')) {
                    $table->string('nama_ayah', 150)->nullable()->after('no_kps');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'tahun_lahir_ayah')) {
                    $table->string('tahun_lahir_ayah', 10)->nullable()->after('nama_ayah');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'jenjang_pendidikan_ayah')) {
                    $table->string('jenjang_pendidikan_ayah', 100)->nullable()->after('tahun_lahir_ayah');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'pekerjaan_ayah')) {
                    $table->string('pekerjaan_ayah', 100)->nullable()->after('jenjang_pendidikan_ayah');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'penghasilan_ayah')) {
                    $table->string('penghasilan_ayah', 100)->nullable()->after('pekerjaan_ayah');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'kebutuhan_khusus_ayah')) {
                    $table->string('kebutuhan_khusus_ayah', 100)->nullable()->after('penghasilan_ayah');
                }

                // Data Ibu
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'nama_ibu')) {
                    $table->string('nama_ibu', 150)->nullable()->after('kebutuhan_khusus_ayah');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'tahun_lahir_ibu')) {
                    $table->string('tahun_lahir_ibu', 10)->nullable()->after('nama_ibu');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'jenjang_pendidikan_ibu')) {
                    $table->string('jenjang_pendidikan_ibu', 100)->nullable()->after('tahun_lahir_ibu');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'pekerjaan_ibu')) {
                    $table->string('pekerjaan_ibu', 100)->nullable()->after('jenjang_pendidikan_ibu');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'penghasilan_ibu')) {
                    $table->string('penghasilan_ibu', 100)->nullable()->after('pekerjaan_ibu');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'kebutuhan_khusus_ibu')) {
                    $table->string('kebutuhan_khusus_ibu', 100)->nullable()->after('penghasilan_ibu');
                }

                // Data Wali
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'nama_wali')) {
                    $table->string('nama_wali', 150)->nullable()->after('kebutuhan_khusus_ibu');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'tahun_lahir_wali')) {
                    $table->string('tahun_lahir_wali', 10)->nullable()->after('nama_wali');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'jenjang_pendidikan_wali')) {
                    $table->string('jenjang_pendidikan_wali', 100)->nullable()->after('tahun_lahir_wali');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'pekerjaan_wali')) {
                    $table->string('pekerjaan_wali', 100)->nullable()->after('jenjang_pendidikan_wali');
                }
                if (!Schema::hasColumn('peserta_didik_tidak_aktif', 'penghasilan_wali')) {
                    $table->string('penghasilan_wali', 100)->nullable()->after('pekerjaan_wali');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('peserta_didik_tidak_aktif')) {
            Schema::table('peserta_didik_tidak_aktif', function (Blueprint $table) {
                $cols = [
                    'kebutuhan_khusus', 'rt', 'rw', 'dusun', 'kelurahan', 'kecamatan', 'kode_pos',
                    'jenis_tinggal', 'alat_transportasi', 'nomor_telepon_rumah', 'skhun', 'penerima_kps', 'no_kps',
                    'nama_ayah', 'tahun_lahir_ayah', 'jenjang_pendidikan_ayah', 'pekerjaan_ayah', 'penghasilan_ayah', 'kebutuhan_khusus_ayah',
                    'nama_ibu', 'tahun_lahir_ibu', 'jenjang_pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'kebutuhan_khusus_ibu',
                    'nama_wali', 'tahun_lahir_wali', 'jenjang_pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali',
                ];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('peserta_didik_tidak_aktif', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
