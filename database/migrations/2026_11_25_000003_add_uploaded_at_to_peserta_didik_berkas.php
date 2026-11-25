<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Waktu unggah berkas yang BERGESER setiap kali siswa/guru mengganti berkas.
 *
 * Urutan baku daftar validasi berkas (KesiswaanQueryService::getBerkasTab)
 * memakai waktu unggah agar siswa yang konsisten (tidak ganti berkas) tetap di
 * atas, dan yang baru mengganti/unggah baru turun ke bawah. `created_at` tidak
 * bisa dipakai karena tetap saat ganti berkas (updateOrCreate), dan `updated_at`
 * ikut bergeser ketika admin memverifikasi — jadi butuh kolom tersendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('peserta_didik_berkas')) {
            return;
        }

        if (!Schema::hasColumn('peserta_didik_berkas', 'uploaded_at')) {
            Schema::table('peserta_didik_berkas', function (Blueprint $table) {
                $table->timestamp('uploaded_at')->nullable()->after('verified_at')->index();
            });
        }

        // Backfill: berkas lama memakai created_at (unggah pertama) sebagai tebakan
        // terbaik; mulai sekarang tiap unggah mengisi uploaded_at sendiri.
        DB::table('peserta_didik_berkas')
            ->whereNull('uploaded_at')
            ->update(['uploaded_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('peserta_didik_berkas')
            && Schema::hasColumn('peserta_didik_berkas', 'uploaded_at')) {
            Schema::table('peserta_didik_berkas', function (Blueprint $table) {
                $table->dropColumn('uploaded_at');
            });
        }
    }
};
