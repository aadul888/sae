<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buang kolom lisensi dari tabel settings. Modul lisensi dibuang; pusat kini
 * hanya mengirim status donasi/iklan (tanpa pemblokiran akses).
 *
 * Idempoten: aman dijalankan walau kolom sudah tak ada (instalasi baru).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table): void {
            foreach (['license_token', 'license_public_key', 'license_grace_days', 'license_synced_at'] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        // Tak dipulihkan: modul lisensi sengaja dibuang.
    }
};
