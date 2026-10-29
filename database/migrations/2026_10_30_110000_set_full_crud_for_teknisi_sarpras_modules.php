<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Beri Teknisi (dan tenaga Sarpras lain pemegang menu_sarpras_ruang /
 * menu_inventaris) hak penuh: tambah, ubah, hapus — setara pengelola Sarpras.
 *
 * Sebelumnya (2026_10_30_100000) hanya baca + ubah. Karena matriks CRUD
 * disimpan per peran `tugas_tambahan` (dan gerbang per pengguna memakai
 * daftar modul tugas tambahan), cukup hidupkan can_create & can_delete.
 * Idempoten: nilai lain (is_allowed, can_read, can_update) tidak disentuh.
 */
return new class extends Migration
{
    private array $keys = ['menu_sarpras_ruang', 'menu_inventaris'];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        foreach ($this->keys as $key) {
            DB::table('role_permissions')
                ->where('role', 'tugas_tambahan')
                ->where('permission_key', $key)
                ->update([
                    'is_allowed' => true,
                    'can_read'   => true,
                    'can_update' => true,
                    'can_create' => true,
                    'can_delete' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: matriks lama tidak menyimpan nilai sebelumnya.
    }
};
