<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('role', 50)->index();
                $table->string('permission_key', 100)->index();
                $table->boolean('is_allowed')->default(true);
                $table->timestamps();

                $table->unique(['role', 'permission_key']);
            });

            // Seed permission default untuk masing-masing role
            $defaults = [
                // Admin: semua diizinkan
                ['role' => 'admin', 'permission_key' => 'menu_dashboard', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_pengguna', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_guru', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_peserta_didik', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_rfid', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_dapodik', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_update', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_hak_akses', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_pengumuman', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'menu_pengaturan', 'is_allowed' => true],

                ['role' => 'admin', 'permission_key' => 'fitur_pengguna_edit', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'fitur_pengguna_hapus', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'fitur_pengguna_reset', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'fitur_dapodik_sync', 'is_allowed' => true],
                ['role' => 'admin', 'permission_key' => 'fitur_system_update', 'is_allowed' => true],

                // Guru default
                ['role' => 'guru', 'permission_key' => 'menu_dashboard', 'is_allowed' => true],
                ['role' => 'guru', 'permission_key' => 'menu_presensi_mengajar', 'is_allowed' => true],
                ['role' => 'guru', 'permission_key' => 'menu_agenda_kbm', 'is_allowed' => true],
                ['role' => 'guru', 'permission_key' => 'menu_penilaian', 'is_allowed' => true],
                ['role' => 'guru', 'permission_key' => 'menu_presensi_peserta_didik', 'is_allowed' => true],
                ['role' => 'guru', 'permission_key' => 'menu_pengumuman', 'is_allowed' => true],

                // Peserta Didik default
                ['role' => 'peserta_didik', 'permission_key' => 'menu_dashboard', 'is_allowed' => true],
                ['role' => 'peserta_didik', 'permission_key' => 'menu_riwayat_rfid', 'is_allowed' => true],
                ['role' => 'peserta_didik', 'permission_key' => 'menu_jadwal_pelajaran', 'is_allowed' => true],
                ['role' => 'peserta_didik', 'permission_key' => 'menu_rapor', 'is_allowed' => true],
                ['role' => 'peserta_didik', 'permission_key' => 'menu_validasi_berkas', 'is_allowed' => true],
                ['role' => 'peserta_didik', 'permission_key' => 'menu_pengumuman', 'is_allowed' => true],
            ];

            $now = now();
            foreach ($defaults as &$row) {
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
            }
            DB::table('role_permissions')->insert($defaults);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
