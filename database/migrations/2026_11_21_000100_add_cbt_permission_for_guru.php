<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registrasi hak akses modul CBT (bank soal & definisi ujian) untuk Guru.
 *
 * Selaras .agents/docs/RENCANA_EKOSISTEM.md §14.7 (bagian "tenang"): guru menyusun
 * bank soal & kertas ujian pada pembelajaran yang diampu. Pelaksanaan ujian serentak
 * belum ada, jadi belum ada izin khusus peserta didik.
 *
 * Idempoten: hanya menambah/menyetel yang belum ada; tidak menimpa konfigurasi manual.
 */
return new class extends Migration
{
    /** role => [create, read, update, delete]. */
    private array $matrix = [
        'guru' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();

        foreach ($this->matrix as $role => $crud) {
            $exists = DB::table('role_permissions')
                ->where('role', $role)
                ->where('permission_key', 'menu_cbt')
                ->exists();

            $payload = [
                'is_allowed' => true,
                'can_create' => $crud['create'],
                'can_read'   => $crud['read'],
                'can_update' => $crud['update'],
                'can_delete' => $crud['delete'],
                'updated_at' => $now,
            ];

            if ($exists) {
                DB::table('role_permissions')
                    ->where('role', $role)
                    ->where('permission_key', 'menu_cbt')
                    ->update($payload);
                continue;
            }

            DB::table('role_permissions')->insert($payload + [
                'role'           => $role,
                'permission_key' => 'menu_cbt',
                'created_at'     => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('role', array_keys($this->matrix))
                ->where('permission_key', 'menu_cbt')
                ->delete();
        }
    }
};
