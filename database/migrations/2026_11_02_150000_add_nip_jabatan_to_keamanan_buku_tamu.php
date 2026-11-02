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
                if (!Schema::hasColumn('keamanan_buku_tamu', 'nip_tamu')) {
                    $table->string('nip_tamu', 50)->nullable()->after('nik_tamu');
                }
                if (!Schema::hasColumn('keamanan_buku_tamu', 'jabatan_tamu')) {
                    $table->string('jabatan_tamu', 150)->nullable()->after('nip_tamu');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('keamanan_buku_tamu')) {
            Schema::table('keamanan_buku_tamu', function (Blueprint $table) {
                $cols = ['nip_tamu', 'jabatan_tamu'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('keamanan_buku_tamu', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
