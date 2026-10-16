<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftarkan hak akses Validasi Data (menu_wali_kelas_validasi) untuk tugas tambahan Wali Kelas.
     */
    public function up(): void
    {
        // 1. Tambahkan menu_wali_kelas_validasi ke granted_permissions tugas tambahan WALI_KELAS
        if (Schema::hasTable('ref_tugas_tambahan')) {
            $wali = DB::table('ref_tugas_tambahan')->where('kode', 'WALI_KELAS')->first();
            if ($wali) {
                $raw = $wali->granted_permissions;
                $perms = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
                if (!in_array('menu_wali_kelas_validasi', $perms, true)) {
                    $perms[] = 'menu_wali_kelas_validasi';
                    DB::table('ref_tugas_tambahan')->where('kode', 'WALI_KELAS')->update([
                        'granted_permissions' => json_encode(array_values(array_unique($perms))),
                        'updated_at'          => now(),
                    ]);
                }
            }
        }

        // 2. Tambahkan permission_key menu_wali_kelas_validasi ke tabel role_permissions
        if (Schema::hasTable('role_permissions')) {
            // Peran tugas_tambahan (Wali Kelas)
            $existing = DB::table('role_permissions')
                ->where('role', 'tugas_tambahan')
                ->where('permission_key', 'menu_wali_kelas_validasi')
                ->first();

            if (!$existing) {
                DB::table('role_permissions')->insert([
                    'role'           => 'tugas_tambahan',
                    'permission_key' => 'menu_wali_kelas_validasi',
                    'is_allowed'     => 1,
                    'can_create'     => 1,
                    'can_read'       => 1,
                    'can_update'     => 1,
                    'can_delete'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // Peran admin
            $existingAdmin = DB::table('role_permissions')
                ->where('role', 'admin')
                ->where('permission_key', 'menu_wali_kelas_validasi')
                ->first();

            if (!$existingAdmin) {
                DB::table('role_permissions')->insert([
                    'role'           => 'admin',
                    'permission_key' => 'menu_wali_kelas_validasi',
                    'is_allowed'     => 1,
                    'can_create'     => 1,
                    'can_read'       => 1,
                    'can_update'     => 1,
                    'can_delete'     => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('role', ['tugas_tambahan', 'admin'])
                ->where('permission_key', 'menu_wali_kelas_validasi')
                ->delete();
        }

        if (Schema::hasTable('ref_tugas_tambahan')) {
            $wali = DB::table('ref_tugas_tambahan')->where('kode', 'WALI_KELAS')->first();
            if ($wali) {
                $raw = $wali->granted_permissions;
                $perms = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
                $perms = array_values(array_filter($perms, fn($p) => $p !== 'menu_wali_kelas_validasi'));
                DB::table('ref_tugas_tambahan')->where('kode', 'WALI_KELAS')->update([
                    'granted_permissions' => json_encode($perms),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
};
