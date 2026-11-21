<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel E-Learning (fase 3 — lihat .agents/docs/RENCANA_EKOSISTEM.md §14).
     *
     * Catatan: kolom referensi (pembelajaran_id, peserta_didik_id, created_by,
     * dinilai_oleh) adalah string UUID Dapodik. DIBUAT TANPA foreign key constraint
     * karena sinkron Dapodik (feeder) memakai delete+insert penuh — FK akan memblokirnya.
     */
    public function up(): void
    {
        if (!Schema::hasTable('learn_materi')) {
            Schema::create('learn_materi', function (Blueprint $table) {
                $table->id();
                $table->string('pembelajaran_id', 50)->index();
                $table->string('judul', 200);
                $table->text('deskripsi')->nullable();
                $table->string('tipe', 20)->default('teks')->comment('file | link | teks');
                $table->string('url_embed', 500)->nullable();
                $table->string('file_path', 500)->nullable();
                $table->unsignedInteger('urutan')->default(0);
                $table->string('status', 20)->default('draft')->index()->comment('draft | terbit');
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('learn_tugas')) {
            Schema::create('learn_tugas', function (Blueprint $table) {
                $table->id();
                $table->string('pembelajaran_id', 50)->index();
                $table->string('judul', 200);
                $table->text('deskripsi')->nullable();
                $table->dateTime('deadline')->nullable();
                $table->unsignedTinyInteger('bobot')->default(0);
                $table->string('status', 20)->default('draft')->index()->comment('draft | terbit | tutup');
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('learn_pengumpulan')) {
            Schema::create('learn_pengumpulan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tugas_id')->index();
                $table->string('peserta_didik_id', 50)->index();
                $table->text('jawaban_teks')->nullable();
                // Jawaban berupa tautan (video YouTube/IG/FB, Canva/Slides/Docs/Sheets) —
                // tanpa unggahan berkas agar hemat storage & beban server.
                $table->string('link_url', 500)->nullable();
                $table->decimal('nilai', 5, 2)->nullable();
                $table->string('dinilai_oleh', 50)->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['tugas_id', 'peserta_didik_id'], 'learn_pengumpulan_tugas_pd_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learn_pengumpulan');
        Schema::dropIfExists('learn_tugas');
        Schema::dropIfExists('learn_materi');
    }
};
