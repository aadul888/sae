<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sinkronisasi hak akses 5 modul kelengkapan TUPOKSI Laboran:
 *  - Buku Administrasi Jurusan      (Indikator #1)
 *  - Bon/Form Permintaan            (Indikator #2)
 *  - Arsip Dokumen (Ordner Digital) (Indikator #7)
 *  - Jurnal Kegiatan Harian         (Indikator #8)
 *  - Kebersihan Lingkungan Produktif(Indikator #9)
 *
 * Idempoten: hanya menambah yang belum ada.
 */
return new class extends Migration
{
    private array $keys = [
        'menu_laboran_administrasi' => 'Buku Administrasi Jurusan',
        'menu_laboran_permintaan'   => 'Bon Permintaan Alat & Bahan',
        'menu_laboran_arsip'        => 'Arsip Dokumen (Ordner)',
        'menu_laboran_jurnal'       => 'Jurnal Kegiatan Harian',
        'menu_laboran_kebersihan'   => 'Kebersihan Lingkungan Produktif',
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
                continue;
            }

            DB::table('role_permissions')->insert([
                'role'           => 'tugas_tambahan',
                'permission_key' => $key,
                'is_allowed'     => true,
                'can_create'     => true,
                'can_read'       => true,
                'can_update'     => true,
                'can_delete'     => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // Tambahkan ke granted_permissions tugas tambahan LABORAN
        if (Schema::hasTable('ref_tugas_tambahan')) {
            $ref = DB::table('ref_tugas_tambahan')->where('kode', 'LABORAN')->first();

            if ($ref) {
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

        // Sinkronkan juga KEPALA_LAB (koordinatif, akses lintas jurusan)
        if (Schema::hasTable('ref_tugas_tambahan')) {
            $ref = DB::table('ref_tugas_tambahan')->where('kode', 'KEPALA_LAB')->first();

            if ($ref) {
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
                if (!$ref) continue;

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
