<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sinkronisasi hak akses untuk modul Laboratorium per Jurusan yang baru:
 *  - Menambah baris role_permissions untuk key menu baru (peran tugas_tambahan).
 *  - Menambahkan key tersebut ke granted_permissions ref_tugas_tambahan LABORAN.
 *
 * Idempoten: hanya menambah yang belum ada.
 */
return new class extends Migration
{
    private array $keys = [
        'menu_laboran_kebutuhan'   => ['fa-clipboard-list', 'Laboratorium: Analisis & Kebutuhan Alat/Bahan'],
        'menu_laboran_perawatan'   => ['fa-screwdriver-wrench', 'Laboratorium: Perawatan & K3 Alat'],
        'menu_laboran_pemeriksaan' => ['fa-list-check', 'Laboratorium: Pemeriksaan Ceklist Alat'],
        'menu_laboran_pengadaan'   => ['fa-file-invoice-dollar', 'Laboratorium: Usulan Pengadaan'],
        'menu_laboran_kerusakan'   => ['fa-triangle-exclamation', 'Laboratorium: Berita Acara Kerusakan'],
        'menu_laboran_laporan'     => ['fa-file-lines', 'Laboratorium: Laporan Penggunaan & Kerusakan'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();

        foreach ($this->keys as $key => $meta) {
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
            $ref = DB::table('ref_tugas_tambahan')->where('kode', 'LABORAN')->first();

            if ($ref) {
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
