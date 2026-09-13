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
        // 1. Tambahkan password_updated_at pada tabel backup_pengguna jika belum ada
        if (Schema::hasTable('backup_pengguna') && !Schema::hasColumn('backup_pengguna', 'password_updated_at')) {
            Schema::table('backup_pengguna', function (Blueprint $table) {
                $table->timestamp('password_updated_at')->nullable()->after('password')->comment('Waktu terakhir user memperbarui password default');
            });
        }

        // 2. Tambahkan pelacakan unduh arsip pada tabel settings
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'archive_downloaded_at')) {
                    $table->dateTime('archive_downloaded_at')->nullable()->after('last_sync')->comment('Waktu terakhir admin mengunduh berkas ZIP arsip');
                }
                if (!Schema::hasColumn('settings', 'archive_file_name')) {
                    $table->string('archive_file_name', 255)->nullable()->after('archive_downloaded_at')->comment('Nama berkas arsip terakhir');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('backup_pengguna') && Schema::hasColumn('backup_pengguna', 'password_updated_at')) {
            Schema::table('backup_pengguna', function (Blueprint $table) {
                $table->dropColumn('password_updated_at');
            });
        }

        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                $colsToDrop = [];
                if (Schema::hasColumn('settings', 'archive_downloaded_at')) {
                    $colsToDrop[] = 'archive_downloaded_at';
                }
                if (Schema::hasColumn('settings', 'archive_file_name')) {
                    $colsToDrop[] = 'archive_file_name';
                }
                if (!empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }
    }
};
