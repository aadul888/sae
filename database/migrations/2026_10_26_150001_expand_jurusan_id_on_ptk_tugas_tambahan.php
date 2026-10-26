<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasColumn('ptk_tugas_tambahan', 'jurusan_id')) {
            Schema::table('ptk_tugas_tambahan', function (Blueprint $table) {
                $table->string('jurusan_id', 255)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasColumn('ptk_tugas_tambahan', 'jurusan_id')) {
            Schema::table('ptk_tugas_tambahan', function (Blueprint $table) {
                $table->string('jurusan_id', 50)->nullable()->change();
            });
        }
    }
};
