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
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'sync_allowed')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('sync_allowed')->default(false)->after('archive_file_name')->comment('Status izin sinkronisasi dari feeder setelah unduh arsip');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'sync_allowed')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('sync_allowed');
            });
        }
    }
};
