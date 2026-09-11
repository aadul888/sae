<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pengumuman') || Schema::hasColumn('pengumuman', 'dibaca_pengguna')) {
            return;
        }

        Schema::table('pengumuman', function (Blueprint $table) {
            $table->json('dibaca_pengguna')->nullable()->after('penulis_nama');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pengumuman') || !Schema::hasColumn('pengumuman', 'dibaca_pengguna')) {
            return;
        }

        Schema::table('pengumuman', function (Blueprint $table) {
            $table->dropColumn('dibaca_pengguna');
        });
    }
};
