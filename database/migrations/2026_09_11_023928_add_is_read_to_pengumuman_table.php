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
        if (!Schema::hasTable('pengumuman')) {
            return;
        }

        if (!Schema::hasColumn('pengumuman', 'dibaca_pengguna')) {
            Schema::table('pengumuman', function (Blueprint $table) {
                $table->json('dibaca_pengguna')->nullable()->after('penulis_nama');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengumuman') && Schema::hasColumn('pengumuman', 'dibaca_pengguna')) {
            Schema::table('pengumuman', function (Blueprint $table) {
                $table->dropColumn('dibaca_pengguna');
            });
        }
    }
};
