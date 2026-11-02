<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('keamanan_insiden')) {
            Schema::table('keamanan_insiden', function (Blueprint $table) {
                $table->string('peserta_didik_id', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('keamanan_insiden')) {
            Schema::table('keamanan_insiden', function (Blueprint $table) {
                $table->unsignedBigInteger('peserta_didik_id')->nullable()->change();
            });
        }
    }
};
