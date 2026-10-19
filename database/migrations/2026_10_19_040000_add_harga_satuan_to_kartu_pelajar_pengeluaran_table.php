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
        if (Schema::hasTable('kartu_pelajar_pengeluaran')) {
            Schema::table('kartu_pelajar_pengeluaran', function (Blueprint $table) {
                if (!Schema::hasColumn('kartu_pelajar_pengeluaran', 'harga_satuan')) {
                    $table->unsignedBigInteger('harga_satuan')->nullable()->after('jumlah');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kartu_pelajar_pengeluaran')) {
            Schema::table('kartu_pelajar_pengeluaran', function (Blueprint $table) {
                if (Schema::hasColumn('kartu_pelajar_pengeluaran', 'harga_satuan')) {
                    $table->dropColumn('harga_satuan');
                }
            });
        }
    }
};
