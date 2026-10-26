<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi penambahan ukuran fisik (panjang, lebar, luas) pada ruang & gedung,
 * serta relasi jurusan pada inventaris & aset sarpras.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah panjang, lebar, dan luas pada sarpras_ruang
        if (Schema::hasTable('sarpras_ruang')) {
            Schema::table('sarpras_ruang', function (Blueprint $table) {
                if (!Schema::hasColumn('sarpras_ruang', 'panjang')) {
                    $table->decimal('panjang', 8, 2)->nullable()->after('lantai')
                        ->comment('Panjang ruangan dalam meter (m)');
                }
                if (!Schema::hasColumn('sarpras_ruang', 'lebar')) {
                    $table->decimal('lebar', 8, 2)->nullable()->after('panjang')
                        ->comment('Lebar ruangan dalam meter (m)');
                }
                if (!Schema::hasColumn('sarpras_ruang', 'luas')) {
                    $table->decimal('luas', 10, 2)->nullable()->after('lebar')
                        ->comment('Luas ruangan dalam meter persegi (m2)');
                }
            });
        }

        // 2. Tambah jurusan_id pada sarpras_aset untuk alokasi aset per jurusan
        if (Schema::hasTable('sarpras_aset')) {
            Schema::table('sarpras_aset', function (Blueprint $table) {
                if (!Schema::hasColumn('sarpras_aset', 'jurusan_id')) {
                    $table->string('jurusan_id', 50)->nullable()->index()->after('ruang_id')
                        ->comment('Alokasi ke jurusan spesifik jika bukan aset umum sekolah');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sarpras_ruang')) {
            Schema::table('sarpras_ruang', function (Blueprint $table) {
                $cols = array_filter(
                    ['panjang', 'lebar', 'luas'],
                    fn($c) => Schema::hasColumn('sarpras_ruang', $c)
                );
                if (!empty($cols)) {
                    $table->dropColumn(array_values($cols));
                }
            });
        }

        if (Schema::hasTable('sarpras_aset')) {
            Schema::table('sarpras_aset', function (Blueprint $table) {
                if (Schema::hasColumn('sarpras_aset', 'jurusan_id')) {
                    $table->dropColumn('jurusan_id');
                }
            });
        }
    }
};
