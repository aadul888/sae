<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Selaraskan matriks hak akses untuk modul yang dipakai Teknisi:
 * `menu_sarpras_ruang` dan `menu_inventaris` = baca + ubah saja.
 *
 * Tambah/hapus data tetap milik pengelola Sarpras (`menu_sarpras`) dan
 * Laboran (`menu_laboran`), sesuai middleware route. Idempoten: hanya
 * mematikan `can_create`/`can_delete`, tidak menyentuh izin baca/ubah.
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
                    'can_create' => false,
                    'can_delete' => false,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: matriks lama tidak menyimpan nilai sebelumnya.
    }
};
