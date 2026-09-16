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
        if (Schema::hasTable('peserta_didik_meta')) {
            Schema::table('peserta_didik_meta', function (Blueprint $table) {
                if (!Schema::hasColumn('peserta_didik_meta', 'is_koordinator')) {
                    $table->boolean('is_koordinator')->default(false)->index()->after('rfid_registered_at')->comment('Apakah peserta didik ditunjuk sebagai Koordinator Kelas');
                }
                if (!Schema::hasColumn('peserta_didik_meta', 'jabatan_koordinator')) {
                    $table->string('jabatan_koordinator', 50)->nullable()->default('Koordinator Kelas')->after('is_koordinator')->comment('Jabatan koordinator (Koordinator Kelas, dsb)');
                }
                if (!Schema::hasColumn('peserta_didik_meta', 'koordinator_tmt')) {
                    $table->dateTime('koordinator_tmt')->nullable()->after('jabatan_koordinator')->comment('Waktu penunjukan koordinator');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('peserta_didik_meta')) {
            Schema::table('peserta_didik_meta', function (Blueprint $table) {
                if (Schema::hasColumn('peserta_didik_meta', 'koordinator_tmt')) {
                    $table->dropColumn('koordinator_tmt');
                }
                if (Schema::hasColumn('peserta_didik_meta', 'jabatan_koordinator')) {
                    $table->dropColumn('jabatan_koordinator');
                }
                if (Schema::hasColumn('peserta_didik_meta', 'is_koordinator')) {
                    $table->dropColumn('is_koordinator');
                }
            });
        }
    }
};
