<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * URL pusat (sae-core) yang bisa diatur per instalasi.
 *
 * Beberapa server sekolah menunjuk pusat berbeda. Alamat pusat disimpan di
 * basis data (bukan ditanam di kode / wajib .env) agar bisa diubah dari UI
 * dan ikut terbawa saat update.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('settings', 'core_url')) {
                $table->string('core_url', 255)->nullable()->after('core_enrolled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (Schema::hasColumn('settings', 'core_url')) {
                $table->dropColumn('core_url');
            }
        });
    }
};
