<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Beri peserta didik akses BACA modul CBT agar dapat melihat dasbor jadwal
 * ujian (read-only). Pelaksanaan ujian serentak belum ada — jadi hanya `read`,
 * tanpa create/update/delete. Idempoten: tidak menimpa konfigurasi manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $role = 'peserta_didik';

        $exists = DB::table('role_permissions')
            ->where('role', $role)
            ->where('permission_key', 'menu_cbt')
            ->exists();

        $payload = [
            'is_allowed' => true,
            'can_create' => false,
            'can_read'   => true,
            'can_update' => false,
            'can_delete' => false,
            'updated_at' => $now,
        ];

        if ($exists) {
            DB::table('role_permissions')
                ->where('role', $role)
                ->where('permission_key', 'menu_cbt')
                ->update($payload);

            return;
        }

        DB::table('role_permissions')->insert($payload + [
            'role'           => $role,
            'permission_key' => 'menu_cbt',
            'created_at'     => $now,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role', 'peserta_didik')
                ->where('permission_key', 'menu_cbt')
                ->delete();
        }
    }
};
