<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftarkan hak akses Pengelolaan Buku Induk (menu_buku_induk)
     * untuk tugas tambahan Kesiswaan, Kepala TAS, dan peran admin.
     */
    public function up(): void
    {
        $kodeTugas = ['STAF_KESISWAAN', 'WAKA_KESISWAAN', 'KEPALA_TAS'];

        if (Schema::hasTable('ref_tugas_tambahan')) {
            foreach ($kodeTugas as $kode) {
                $row = DB::table('ref_tugas_tambahan')->where('kode', $kode)->first();
                if (!$row) {
                    continue;
                }

                $raw = $row->granted_permissions;
                $perms = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);

                if (!in_array('menu_buku_induk', $perms, true)) {
                    $perms[] = 'menu_buku_induk';
                    DB::table('ref_tugas_tambahan')->where('kode', $kode)->update([
                        'granted_permissions' => json_encode(array_values(array_unique($perms))),
                        'updated_at'          => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('role_permissions')) {
            foreach (['tugas_tambahan', 'admin'] as $role) {
                $exists = DB::table('role_permissions')
                    ->where('role', $role)
                    ->where('permission_key', 'menu_buku_induk')
                    ->exists();

                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'role'           => $role,
                        'permission_key' => 'menu_buku_induk',
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
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('role', ['tugas_tambahan', 'admin'])
                ->where('permission_key', 'menu_buku_induk')
                ->delete();
        }

        if (Schema::hasTable('ref_tugas_tambahan')) {
            foreach (['STAF_KESISWAAN', 'WAKA_KESISWAAN', 'KEPALA_TAS'] as $kode) {
                $row = DB::table('ref_tugas_tambahan')->where('kode', $kode)->first();
                if (!$row) {
                    continue;
                }

                $raw = $row->granted_permissions;
                $perms = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
                $perms = array_values(array_filter($perms, fn ($p) => $p !== 'menu_buku_induk'));

                DB::table('ref_tugas_tambahan')->where('kode', $kode)->update([
                    'granted_permissions' => json_encode($perms),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
};
