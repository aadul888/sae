<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kedisiplinan_tata_tertib') && !Schema::hasColumn('kedisiplinan_tata_tertib', 'tingkat')) {
            Schema::table('kedisiplinan_tata_tertib', function (Blueprint $table) {
                $table->string('tingkat', 20)->default('ringan')->after('kategori')->index();
            });

            // Sinkronkan data lama berdasarkan bobot poin & kategori
            DB::table('kedisiplinan_tata_tertib')
                ->where('kategori', 'larangan_berat')
                ->orWhere('bobot_poin', '>=', 35)
                ->update(['tingkat' => 'berat']);

            DB::table('kedisiplinan_tata_tertib')
                ->where('kategori', '!=', 'larangan_berat')
                ->where('bobot_poin', '>=', 15)
                ->where('bobot_poin', '<', 35)
                ->update(['tingkat' => 'sedang']);

            DB::table('kedisiplinan_tata_tertib')
                ->where('kategori', '!=', 'larangan_berat')
                ->where('bobot_poin', '<', 15)
                ->update(['tingkat' => 'ringan']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kedisiplinan_tata_tertib') && Schema::hasColumn('kedisiplinan_tata_tertib', 'tingkat')) {
            Schema::table('kedisiplinan_tata_tertib', function (Blueprint $table) {
                $table->dropColumn('tingkat');
            });
        }
    }
};
