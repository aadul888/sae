<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Token ujian CBT: kode akses yang dimasukkan peserta didik sebelum
     * mengerjakan ujian. Menyiapkan fondasi sebelum pelaksanaan serentak §10.
     * Idempoten: cek hasColumn dulu.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ujian', 'token')) {
            Schema::table('ujian', function (Blueprint $table) {
                $table->string('token', 20)->nullable()->index()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ujian', 'token')) {
            Schema::table('ujian', function (Blueprint $table) {
                $table->dropColumn('token');
            });
        }
    }
};
