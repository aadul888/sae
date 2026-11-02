<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('keamanan_buku_tamu')) {
            Schema::table('keamanan_buku_tamu', function (Blueprint $table) {
                if (!Schema::hasColumn('keamanan_buku_tamu', 'jenis_tamu')) {
                    $table->string('jenis_tamu', 30)->default('umum')->after('nama_tamu')->index();
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'nik_tamu')) {
                    $table->string('nik_tamu', 30)->nullable()->after('jenis_tamu')->index();
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'peserta_didik_id')) {
                    $table->string('peserta_didik_id', 50)->nullable()->after('nik_tamu')->index();
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'nisn_siswa')) {
                    $table->string('nisn_siswa', 30)->nullable()->after('peserta_didik_id')->index();
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'nama_siswa')) {
                    $table->string('nama_siswa', 200)->nullable()->after('nisn_siswa');
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'rombel_siswa')) {
                    $table->string('rombel_siswa', 100)->nullable()->after('nama_siswa');
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'is_mandiri')) {
                    $table->boolean('is_mandiri')->default(false)->after('petugas_satpam_ptk_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('keamanan_buku_tamu')) {
            Schema::table('keamanan_buku_tamu', function (Blueprint $table) {
                $cols = ['jenis_tamu', 'nik_tamu', 'peserta_didik_id', 'nisn_siswa', 'nama_siswa', 'rombel_siswa', 'is_mandiri'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('keamanan_buku_tamu', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
