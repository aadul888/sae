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
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'app_version')) {
                $table->string('app_version', 50)->default('1.0.0')->after('app_name');
            }
            if (!Schema::hasColumn('settings', 'last_update_at')) {
                $table->dateTime('last_update_at')->nullable()->after('last_sync');
            }
            if (!Schema::hasColumn('settings', 'last_commit_hash')) {
                $table->string('last_commit_hash', 50)->nullable()->after('last_update_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['app_version', 'last_update_at', 'last_commit_hash']);
        });
    }
};
