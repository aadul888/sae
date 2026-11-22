<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci privat jembatan sae-core per instalasi.
 *
 * Diisi otomatis oleh sistem saat pertama menyambung (tanpa .env), lalu
 * didaftarkan (enroll) ke sae-core. Tidak pernah diisi manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('settings', 'core_install_key')) {
                $table->string('core_install_key', 191)->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('settings', 'core_enrolled_at')) {
                $table->timestamp('core_enrolled_at')->nullable()->after('core_install_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            foreach (['core_install_key', 'core_enrolled_at'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
