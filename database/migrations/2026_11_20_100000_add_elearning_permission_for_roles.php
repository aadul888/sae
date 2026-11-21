<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registrasi hak akses modul E-Learning untuk Guru & Peserta Didik.
 *
 * - Guru  : kelola materi + tugas + penilaian (create/update/delete).
 * - Peserta didik : baca saja (lihat materi terbit, kumpulkan tugas, lihat nilai).
 *
 * Idempoten: hanya menambah/menyetel yang belum ada; tidak menimpa konfigurasi manual.
 * Modul ini (grup "Akademik Digital") tidak termasuk daftar terlarang per peran,
 * lihat App\Support\RolePermissionRestrictions.
 */
return new class extends Migration
{
    /** role => [create, read, update, delete]. */
    private array $matrix = [
        'guru' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'peserta_didik' => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
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
                ->where('permission_key', 'menu_elearning')
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
                    ->where('permission_key', 'menu_elearning')
                    ->update($payload);
                continue;
            }

            DB::table('role_permissions')->insert($payload + [
                'role'           => $role,
                'permission_key' => 'menu_elearning',
                'created_at'     => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('role', array_keys($this->matrix))
                ->where('permission_key', 'menu_elearning')
                ->delete();
        }
    }
};
