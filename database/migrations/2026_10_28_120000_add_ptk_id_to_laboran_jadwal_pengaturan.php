<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan pemberlakuan jadwal praktik laboran DIPISAH PER LABORAN.
 *
 * Sebelumnya tabel ini hanya punya satu baris (id = 1) sebagai pengaturan
 * global, sehingga pilihan Semester/Mingguan milik satu laboran menimpa
 * laboran lain. Sekarang setiap laboran punya barisnya sendiri lewat
 * `ptk_id`, dan kunci unik (ptk_id, mode_pemberlakuan) mencegah duplikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laboran_jadwal_pengaturan')) {
            return;
        }

        if (!Schema::hasColumn('laboran_jadwal_pengaturan', 'ptk_id')) {
            Schema::table('laboran_jadwal_pengaturan', function (Blueprint $table) {
                $table->string('ptk_id', 100)->nullable()->after('id')
                    ->comment('Laboran pemilik pengaturan; null = pengaturan bawaan/global');
            });
        }

        // Kunci unik per (laboran, mode). Pengecekan eksplisit agar migrasi aman
        // dijalankan ulang pada database yang sudah punya indeks ini.
        $indexes = collect(DB::select('SHOW INDEX FROM laboran_jadwal_pengaturan'))
            ->pluck('Key_name')->all();

        if (!in_array('laboran_jadwal_pengaturan_ptk_mode_unique', $indexes, true)) {
            Schema::table('laboran_jadwal_pengaturan', function (Blueprint $table) {
                $table->unique(['ptk_id', 'mode_pemberlakuan'], 'laboran_jadwal_pengaturan_ptk_mode_unique');
            });
        }

        // Baris id = 1 (bawaan/global) dibiarkan sebagai fallback bagi laboran
        // yang belum pernah memilih mode; datanya tidak diubah.
    }

    public function down(): void
    {
        if (!Schema::hasTable('laboran_jadwal_pengaturan')) {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM laboran_jadwal_pengaturan'))
            ->pluck('Key_name')->all();

        if (in_array('laboran_jadwal_pengaturan_ptk_mode_unique', $indexes, true)) {
            Schema::table('laboran_jadwal_pengaturan', function (Blueprint $table) {
                $table->dropUnique('laboran_jadwal_pengaturan_ptk_mode_unique');
            });
        }

        if (Schema::hasColumn('laboran_jadwal_pengaturan', 'ptk_id')) {
            Schema::table('laboran_jadwal_pengaturan', function (Blueprint $table) {
                $table->dropColumn('ptk_id');
            });
        }
    }
};
