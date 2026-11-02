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
                if (!Schema::hasColumn('keamanan_buku_tamu', 'ada_surat_tugas')) {
                    $table->boolean('ada_surat_tugas')->default(false)->after('instansi_asal')->index();
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'nomor_surat_tugas')) {
                    $table->string('nomor_surat_tugas', 150)->nullable()->after('ada_surat_tugas');
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'tanggal_surat_tugas')) {
                    $table->date('tanggal_surat_tugas')->nullable()->after('nomor_surat_tugas');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('keamanan_buku_tamu')) {
            Schema::table('keamanan_buku_tamu', function (Blueprint $table) {
                $cols = ['ada_surat_tugas', 'nomor_surat_tugas', 'tanggal_surat_tugas'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('keamanan_buku_tamu', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
