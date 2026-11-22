<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lisensi bertanda-tangan dari sae-core (per instalasi).
 *
 * Token lisensi + kunci publik pusat disimpan agar penegakan bisa jalan
 * offline. Kunci privat pusat tidak pernah ada di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('settings', 'license_token')) {
                $table->text('license_token')->nullable();
            }
            if (!Schema::hasColumn('settings', 'license_public_key')) {
                $table->string('license_public_key', 191)->nullable();
            }
            if (!Schema::hasColumn('settings', 'license_grace_days')) {
                $table->unsignedSmallInteger('license_grace_days')->default(14);
            }
            if (!Schema::hasColumn('settings', 'license_synced_at')) {
                $table->timestamp('license_synced_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            foreach (['license_token', 'license_public_key', 'license_grace_days', 'license_synced_at'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
