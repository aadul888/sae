<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ref_tugas_tambahan')) {
            $wali = DB::table('ref_tugas_tambahan')->where('kode', 'WALI_KELAS')->first();
            if ($wali) {
                $raw = $wali->granted_permissions;
                $perms = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
                $perms = array_values(array_diff($perms, ['menu_e_izin', 'menu_rapor']));
                if (!in_array('menu_wali_kelas_aktif', $perms, true)) {
                    $perms[] = 'menu_wali_kelas_aktif';
                }
                if (!in_array('menu_wali_kelas_tidak_aktif', $perms, true)) {
                    $perms[] = 'menu_wali_kelas_tidak_aktif';
                }
                if (!in_array('menu_wali_kelas_presensi', $perms, true)) {
                    $perms[] = 'menu_wali_kelas_presensi';
                }
                DB::table('ref_tugas_tambahan')->where('kode', 'WALI_KELAS')->update([
                    'granted_permissions' => json_encode($perms),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
