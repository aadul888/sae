<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registrasi hak akses modul "Data Peserta Didik" untuk Laboran.
 *
 * Modul ini bersifat BACA-SAJA (hanya melihat detail biodata + ekspor Excel),
 * sehingga hanya `can_read` yang diaktifkan. Hak akses tetap didaftarkan pada
 * role `tugas_tambahan` agar muncul di halaman pengaturan hak akses tugas
 * tambahan, dan ditambahkan ke `granted_permissions` LABORAN & KEPALA_LAB.
 *
 * Idempoten: hanya menambah yang belum ada, tidak menimpa konfigurasi manual.
 */
return new class extends Migration
{
    private array $keys = [
        'menu_laboran_peserta_didik' => 'Data Peserta Didik (per Jurusan)',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();

        foreach (array_keys($this->keys) as $key) {
            $exists = DB::table('role_permissions')
                ->where('role', 'tugas_tambahan')
                ->where('permission_key', $key)
                ->exists();

            if ($exists) {
                // Pastikan baris lama ikut memenuhi sifat baca-saja modul ini.
                DB::table('role_permissions')
                    ->where('role', 'tugas_tambahan')
                    ->where('permission_key', $key)
                    ->update([
                        'is_allowed' => true,
                        'can_read'   => true,
                        'can_create' => false,
                        'can_update' => false,
                        'can_delete' => false,
                        'updated_at' => $now,
                    ]);
                continue;
            }

            DB::table('role_permissions')->insert([
                'role'           => 'tugas_tambahan',
                'permission_key' => $key,
                'is_allowed'     => true,
                'can_create'     => false,
                'can_read'       => true,
                'can_update'     => false,
                'can_delete'     => false,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        if (!Schema::hasTable('ref_tugas_tambahan')) {
            return;
        }

        // LABORAN (terbatasi jurusan penugasan) & KEPALA_LAB (lintas jurusan).
        foreach (['LABORAN', 'KEPALA_LAB'] as $kode) {
            $ref = DB::table('ref_tugas_tambahan')->where('kode', $kode)->first();
            if (!$ref) {
                continue;
            }

            $current = is_string($ref->granted_permissions)
                ? (json_decode($ref->granted_permissions, true) ?: [])
                : (is_array($ref->granted_permissions) ? $ref->granted_permissions : []);

            $merged = array_values(array_unique(array_merge($current, array_keys($this->keys))));

            if (count($merged) !== count($current)) {
                DB::table('ref_tugas_tambahan')->where('id', $ref->id)->update([
                    'granted_permissions' => json_encode($merged),
                    'updated_at'          => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role', 'tugas_tambahan')
                ->whereIn('permission_key', array_keys($this->keys))
                ->delete();
        }

        if (Schema::hasTable('ref_tugas_tambahan')) {
            foreach (['LABORAN', 'KEPALA_LAB'] as $kode) {
                $ref = DB::table('ref_tugas_tambahan')->where('kode', $kode)->first();
                if (!$ref) {
                    continue;
                }

                $current = is_string($ref->granted_permissions)
                    ? (json_decode($ref->granted_permissions, true) ?: [])
                    : (is_array($ref->granted_permissions) ? $ref->granted_permissions : []);

                $filtered = array_values(array_diff($current, array_keys($this->keys)));

                DB::table('ref_tugas_tambahan')->where('id', $ref->id)->update([
                    'granted_permissions' => json_encode($filtered),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
};
