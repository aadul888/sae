<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('admin_aktivitas')) {
            Schema::create('admin_aktivitas', function (Blueprint $table) {
                $table->id();
                $table->string('admin_name')->default('Administrator');
                $table->string('admin_username')->nullable();
                $table->string('modul', 50)->default('Sistem');
                $table->string('aktivitas');
                $table->text('keterangan')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('tipe', 20)->default('success'); // success, info, warning, danger
                $table->timestamps();

                $table->index('created_at');
                $table->index('modul');
            });

            // Seed initial real administrative activities based on current database state
            $now = now();
            $initialLogs = [
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Formulir',
                    'aktivitas' => 'Pembaruan Skema & Ekspor Excel Formulir',
                    'keterangan' => 'Mengonfigurasi ekspor rekap respon native .xlsx dan quick toggle status formulir.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'success',
                    'created_at' => $now->copy()->subMinutes(12),
                    'updated_at' => $now->copy()->subMinutes(12),
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Formulir',
                    'aktivitas' => 'Pembuatan Formulir: Pendaftaran Pengelola SAE',
                    'keterangan' => 'Membuka formulir kuesioner koordinasi kelas untuk Sistem Aplikasi Edukasi.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'info',
                    'created_at' => $now->copy()->subHours(2)->subMinutes(15),
                    'updated_at' => $now->copy()->subHours(2)->subMinutes(15),
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Sistem',
                    'aktivitas' => 'Pembaruan Sistem Aplikasi Edukasi (SAE)',
                    'keterangan' => 'Memutakhirkan sistem ke commit 8c693a7 versi 1.0.4 via Git pull.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'success',
                    'created_at' => '2026-09-27 03:27:32',
                    'updated_at' => '2026-09-27 03:27:32',
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Dapodik',
                    'aktivitas' => 'Sinkronisasi Data Pokok Pendidikan (Dapodik)',
                    'keterangan' => 'Sinkronisasi komprehensif: 1,118 Peserta Didik, 49 Guru, 18 Tendik, 70 Rombel, 410 Mapel.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'success',
                    'created_at' => '2026-09-23 18:00:46',
                    'updated_at' => '2026-09-23 18:00:46',
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Backup',
                    'aktivitas' => 'Pembuatan Paket Cadangan Arsip Sekolah',
                    'keterangan' => 'Mengunduh paket arsip terpadu SAE_Arsip_Backup_20252031_20260923_173827.zip.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'info',
                    'created_at' => '2026-09-23 17:38:55',
                    'updated_at' => '2026-09-23 17:38:55',
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Pengumuman',
                    'aktivitas' => 'Publikasi Pengumuman: Monitoring Absensi RFID & Mobile',
                    'keterangan' => 'Menerbitkan pengumuman pemantauan kehadiran harian untuk seluruh civitas sekolah.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'info',
                    'created_at' => '2026-09-14 03:01:07',
                    'updated_at' => '2026-09-14 03:01:07',
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Hak Akses',
                    'aktivitas' => 'Sinkronisasi Matriks Hak Akses RBAC Pengguna',
                    'keterangan' => 'Pembaruan izin modul target capaian dan manajemen tendik di role_permissions.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'success',
                    'created_at' => '2026-09-12 11:20:00',
                    'updated_at' => '2026-09-12 11:20:00',
                ],
                [
                    'admin_name' => 'Abdul Azis',
                    'admin_username' => 'abdulazis75@guru.smk.belajar.id',
                    'modul' => 'Presensi',
                    'aktivitas' => 'Konfigurasi Jam Masuk & Toleransi Keterlambatan',
                    'keterangan' => 'Penyesuaian jam toleransi presensi RFID gerbang utama dan geotagging.',
                    'ip_address' => '127.0.0.1',
                    'tipe' => 'success',
                    'created_at' => '2026-09-10 08:45:00',
                    'updated_at' => '2026-09-10 08:45:00',
                ],
            ];

            DB::table('admin_aktivitas')->insert($initialLogs);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_aktivitas');
    }
};
