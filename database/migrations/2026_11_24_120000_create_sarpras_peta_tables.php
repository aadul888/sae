<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peta / Denah Sekolah Interaktif (Sarpras).
 *
 * Tiga tabel:
 *  - sarpras_peta        : denah kerja per tingkat (tapak / lantai), kondisi terkini
 *  - sarpras_peta_area   : bentuk bangunan/blok (hybrid rect + polygon)
 *  - sarpras_peta_revisi : riwayat per perubahan (snapshot), bukan per tahun ajaran
 *
 * Lihat .agents/docs/RENCANA_PETA_DENAH_SARPRAS.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Denah kerja per tingkat
        if (!Schema::hasTable('sarpras_peta')) {
            Schema::create('sarpras_peta', function (Blueprint $table) {
                $table->id();
                $table->string('sekolah_id', 50)->nullable()->index();
                $table->unsignedBigInteger('parent_peta_id')->nullable()->index()
                    ->comment('null = tapak; isi = denah lantai di bawah tapak');
                $table->string('nama', 200);
                $table->string('tingkat', 10)->default('tapak')->index(); // tapak | lantai
                $table->string('skala', 20)->nullable()->comment('Metadata cetak, mis. 1:125');
                $table->decimal('luas_tanah', 12, 2)->nullable();
                $table->string('view_box', 40)->default('0 0 1000 700');
                $table->decimal('meter_per_unit', 10, 6)->nullable()
                    ->comment('Faktor konversi satuan viewBox ke meter');
                $table->string('latar_path', 255)->nullable();
                $table->string('latar_sumber', 30)->default('upload')
                    ->comment('google_earth | big | coreldraw | esri | upload');
                $table->string('latar_atribusi', 255)->nullable();
                $table->unsignedInteger('revisi_ke')->default(0);
                $table->string('file_svg', 255)->nullable();
                $table->boolean('is_aktif')->default(true)->index();
                $table->timestamps();
            });
        }

        // 2. Bentuk area (bangunan / blok / lahan)
        if (!Schema::hasTable('sarpras_peta_area')) {
            Schema::create('sarpras_peta_area', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('peta_id')->index();
                $table->unsignedBigInteger('parent_area_id')->nullable()->index()
                    ->comment('self-ref: blok menaungi ruang di denah lantai');
                $table->unsignedBigInteger('ruang_id')->nullable()->index()
                    ->comment('relasi ke sarpras_ruang bila area ini satu ruang');
                $table->string('kode_area', 50)->index();
                $table->string('nama_area', 200)->nullable();
                $table->string('kategori', 60)->nullable()->index();
                $table->string('zona', 60)->nullable();
                $table->string('bentuk', 10)->default('rect'); // rect | poly
                $table->decimal('x', 10, 2)->nullable();
                $table->decimal('y', 10, 2)->nullable();
                $table->decimal('w', 10, 2)->nullable();
                $table->decimal('h', 10, 2)->nullable();
                $table->decimal('rotate', 6, 2)->nullable();
                $table->longText('poligon')->nullable()->comment('JSON [[x,y],...] bila bentuk=poly');
                $table->decimal('label_x', 8, 2)->nullable();
                $table->decimal('label_y', 8, 2)->nullable();
                $table->string('warna_override', 20)->nullable();
                $table->string('highlight', 20)->nullable()
                    ->comment('revit_2025 | rusak_2026 | siap_bangun | lahan_kosong');
                $table->decimal('luas_area', 10, 2)->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['peta_id', 'kode_area']);
            });
        }

        // 3. Riwayat revisi (snapshot per perubahan)
        if (!Schema::hasTable('sarpras_peta_revisi')) {
            Schema::create('sarpras_peta_revisi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('peta_id')->index();
                $table->unsignedInteger('revisi_ke');
                $table->longText('snapshot_geometri')
                    ->comment('JSON seluruh area (bentuk + metadata) saat revisi ini');
                $table->text('ringkasan_perubahan')->nullable();
                $table->string('diubah_oleh', 150)->nullable();
                $table->text('catatan')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->unique(['peta_id', 'revisi_ke']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sarpras_peta_revisi');
        Schema::dropIfExists('sarpras_peta_area');
        Schema::dropIfExists('sarpras_peta');
    }
};
