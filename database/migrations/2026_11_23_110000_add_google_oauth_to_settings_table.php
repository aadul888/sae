<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredensial Google OAuth (Belajar.id) disimpan di basis data, bukan .env.
 * Diubah lewat halaman Pengaturan → Identitas Sekolah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('settings', 'google_client_id')) {
                $table->string('google_client_id', 255)->nullable()->after('site_name');
            }
            if (!Schema::hasColumn('settings', 'google_client_secret')) {
                $table->string('google_client_secret', 255)->nullable()->after('google_client_id');
            }
            if (!Schema::hasColumn('settings', 'google_redirect_uri')) {
                $table->string('google_redirect_uri', 255)->nullable()->after('google_client_secret');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            foreach (['google_client_id', 'google_client_secret', 'google_redirect_uri'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
