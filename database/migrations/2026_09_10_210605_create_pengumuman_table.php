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
        if (!Schema::hasTable('pengumuman')) {
            Schema::create('pengumuman', function (Blueprint $table) {
                $table->id();
                $table->string('judul', 255);
                $table->text('isi');
                $table->enum('target', ['publik', 'pengguna', 'semua'])->default('semua')->index();
                $table->string('target_peran', 50)->default('semua')->index(); // semua, admin, guru, tendik, peserta_didik
                $table->boolean('is_active')->default(true)->index();
                $table->string('penulis_nama', 100)->nullable();
                $table->timestamps();
            });

            // Seed pengumuman awal
            $now = now();
            DB::table('pengumuman')->insert([
                [
                    'judul' => 'Pendaftaran Ujian Sekolah TA 2026/2027',
                    'isi' => 'Pendaftaran Ujian Sekolah Tahun Ajaran 2026/2027 telah dibuka bagi seluruh peserta didik tingkat akhir.',
                    'target' => 'semua',
                    'target_peran' => 'semua',
                    'is_active' => true,
                    'penulis_nama' => 'Administrator',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'judul' => 'Monitoring Absensi RFID & Mobile',
                    'isi' => 'Sinkronisasi absensi tap kartu RFID dan mobile berjalan normal secara realtime.',
                    'target' => 'publik',
                    'target_peran' => 'semua',
                    'is_active' => true,
                    'penulis_nama' => 'Administrator',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'judul' => 'Sosialisasi Portal Kelulusan Online',
                    'isi' => 'Sosialisasi penggunaan portal kelulusan online mandiri dijadwalkan pada hari Jumat pekan ini.',
                    'target' => 'semua',
                    'target_peran' => 'semua',
                    'is_active' => true,
                    'penulis_nama' => 'Administrator',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'judul' => 'Pembaruan Sistem Aplikasi Edukasi (SAE)',
                    'isi' => 'Sistem telah diperbarui ke versi terbaru dengan peningkatan stabilitas hak akses dan manajemen modul.',
                    'target' => 'pengguna',
                    'target_peran' => 'semua',
                    'is_active' => true,
                    'penulis_nama' => 'Sistem SAE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
