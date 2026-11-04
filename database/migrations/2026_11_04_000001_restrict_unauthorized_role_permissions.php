<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus izin e-izin yang salah terdaftar untuk role dasar non-admin / non-peserta didik
        DB::table('role_permissions')
            ->whereIn('role', ['guru', 'tendik', 'orang_tua'])
            ->where('permission_key', 'menu_e_izin')
            ->delete();

        // Hapus izin menu kebutuhan MBG untuk seluruh role non-admin
        DB::table('role_permissions')
            ->where('role', '!=', 'admin')
            ->where('permission_key', 'menu_kebutuhan_mbg')
            ->delete();

        // Bersihkan menu kartu pelajar, PIP, dan buku induk untuk role yang dilarang
        DB::table('role_permissions')
            ->whereIn('role', ['guru', 'tendik', 'orang_tua', 'peserta_didik'])
            ->whereIn('permission_key', ['menu_kartu_pelajar', 'menu_pip', 'menu_buku_induk'])
            ->delete();
    }

    public function down(): void
    {
        // Deliberate no-op: row izin yang dibersihkan adalah akses tidak sah
    }
};
