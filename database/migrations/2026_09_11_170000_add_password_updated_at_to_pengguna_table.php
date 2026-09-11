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
        if (Schema::hasTable('pengguna') && !Schema::hasColumn('pengguna', 'password_updated_at')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->timestamp('password_updated_at')->nullable()->after('password')->comment('Waktu terakhir user memperbarui password default');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengguna') && Schema::hasColumn('pengguna', 'password_updated_at')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->dropColumn('password_updated_at');
            });
        }
    }
};
