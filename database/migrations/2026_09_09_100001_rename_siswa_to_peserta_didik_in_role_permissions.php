<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename role 'siswa' → 'peserta_didik' dan permission keys siswa → peserta_didik
     */
    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        // 1. Rename role string
        DB::table('role_permissions')
            ->where('role', 'siswa')
            ->update(['role' => 'peserta_didik']);

        // 2. Rename permission keys
        $keyMap = [
            'menu_siswa'              => 'menu_peserta_didik',
            'menu_siswa_aktif'        => 'menu_peserta_didik_aktif',
            'menu_siswa_tidak_aktif'  => 'menu_peserta_didik_tidak_aktif',
            'menu_berkas_siswa'       => 'menu_berkas_peserta_didik',
            'menu_presensi_siswa'     => 'menu_presensi_peserta_didik',
        ];

        foreach ($keyMap as $old => $new) {
            DB::table('role_permissions')
                ->where('permission_key', $old)
                ->update(['permission_key' => $new]);
        }
    }

    /**
     * Rollback: peserta_didik → siswa
     */
    public function down(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')
            ->where('role', 'peserta_didik')
            ->update(['role' => 'siswa']);

        $keyMap = [
            'menu_peserta_didik'              => 'menu_siswa',
            'menu_peserta_didik_aktif'        => 'menu_siswa_aktif',
            'menu_peserta_didik_tidak_aktif'  => 'menu_siswa_tidak_aktif',
            'menu_berkas_peserta_didik'       => 'menu_berkas_siswa',
            'menu_presensi_peserta_didik'     => 'menu_presensi_siswa',
        ];

        foreach ($keyMap as $old => $new) {
            DB::table('role_permissions')
                ->where('permission_key', $old)
                ->update(['permission_key' => $new]);
        }
    }
};
