<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            return;
        }

        foreach (['nama_bank', 'no_rekening', 'kcp_bank', 'rekening_atas_nama'] as $column) {
            if (Schema::hasColumn('peserta_didik_identitas', $column)) {
                Schema::table('peserta_didik_identitas', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            return;
        }

        Schema::table('peserta_didik_identitas', function ($table) {
            $table->string('nama_bank', 50)->nullable();
            $table->string('no_rekening', 40)->nullable();
            $table->string('kcp_bank', 100)->nullable();
            $table->string('rekening_atas_nama', 150)->nullable();
        });
    }
};