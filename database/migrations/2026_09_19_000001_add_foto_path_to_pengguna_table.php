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
        if (Schema::hasTable('pengguna') && !Schema::hasColumn('pengguna', 'foto_path')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->string('foto_path', 255)->nullable()->after('alamat')->comment('Path file pasfoto guru/tendik di storage');
            });
        }

        if (Schema::hasTable('backup_pengguna') && !Schema::hasColumn('backup_pengguna', 'foto_path')) {
            Schema::table('backup_pengguna', function (Blueprint $table) {
                $table->string('foto_path', 255)->nullable()->after('alamat')->comment('Path file pasfoto guru/tendik di storage');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengguna') && Schema::hasColumn('pengguna', 'foto_path')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->dropColumn('foto_path');
            });
        }

        if (Schema::hasTable('backup_pengguna') && Schema::hasColumn('backup_pengguna', 'foto_path')) {
            Schema::table('backup_pengguna', function (Blueprint $table) {
                $table->dropColumn('foto_path');
            });
        }
    }
};
