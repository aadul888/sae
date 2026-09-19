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
        if (Schema::hasTable('pengguna')) {
            Schema::table('pengguna', function (Blueprint $table) {
                if (!Schema::hasColumn('pengguna', 'rfid_uid')) {
                    $table->string('rfid_uid', 64)->nullable()->unique()->after('ptk_id')->comment('UID Kartu RFID fisik Pengguna/GTK');
                }
                if (!Schema::hasColumn('pengguna', 'rfid_registered_at')) {
                    $table->timestamp('rfid_registered_at')->nullable()->after('rfid_uid')->comment('Waktu pendaftaran kartu RFID');
                }
            });
        }

        if (Schema::hasTable('backup_pengguna')) {
            Schema::table('backup_pengguna', function (Blueprint $table) {
                if (!Schema::hasColumn('backup_pengguna', 'rfid_uid')) {
                    $table->string('rfid_uid', 64)->nullable()->after('ptk_id');
                }
                if (!Schema::hasColumn('backup_pengguna', 'rfid_registered_at')) {
                    $table->timestamp('rfid_registered_at')->nullable()->after('rfid_uid');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengguna')) {
            Schema::table('pengguna', function (Blueprint $table) {
                if (Schema::hasColumn('pengguna', 'rfid_registered_at')) {
                    $table->dropColumn('rfid_registered_at');
                }
                if (Schema::hasColumn('pengguna', 'rfid_uid')) {
                    $table->dropColumn('rfid_uid');
                }
            });
        }

        if (Schema::hasTable('backup_pengguna')) {
            Schema::table('backup_pengguna', function (Blueprint $table) {
                if (Schema::hasColumn('backup_pengguna', 'rfid_registered_at')) {
                    $table->dropColumn('rfid_registered_at');
                }
                if (Schema::hasColumn('backup_pengguna', 'rfid_uid')) {
                    $table->dropColumn('rfid_uid');
                }
            });
        }
    }
};
