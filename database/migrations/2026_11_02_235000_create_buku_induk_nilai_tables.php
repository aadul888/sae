<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel nilai buku induk (satu nilai per mapel per semester) dan
     * pengaturan pengelolaan buku induk (daftar mapel + penandatangan).
     */
    public function up(): void
    {
        if (!Schema::hasTable('buku_induk_nilai')) {
            Schema::create('buku_induk_nilai', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->index();
                $table->string('nama_mata_pelajaran', 150);
                $table->string('kelompok', 20)->default('umum')->index(); // umum | kejuruan
                $table->string('tingkat', 10)->default('10')->index();     // 10 | 11 | 12
                $table->unsignedTinyInteger('semester')->default(1)->index(); // 1..6
                $table->decimal('nilai', 5, 2)->nullable();
                $table->timestamps();

                $table->unique(
                    ['peserta_didik_id', 'nama_mata_pelajaran', 'semester'],
                    'buku_induk_nilai_unik'
                );
            });
        }

        if (!Schema::hasTable('buku_induk_pengaturan')) {
            Schema::create('buku_induk_pengaturan', function (Blueprint $table) {
                $table->id();
                $table->string('tahun_ajaran', 20)->nullable();
                $table->string('nama_kepala_sekolah', 150)->nullable();
                $table->string('nip_kepala_sekolah', 30)->nullable();
                $table->string('nama_petugas', 150)->nullable();
                $table->string('nip_petugas', 30)->nullable();
                $table->text('mapel_umum')->nullable();      // JSON array nama mapel
                $table->text('mapel_kejuruan')->nullable();   // JSON array nama mapel
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_induk_pengaturan');
        Schema::dropIfExists('buku_induk_nilai');
    }
};
