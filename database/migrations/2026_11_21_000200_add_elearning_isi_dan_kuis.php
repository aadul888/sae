<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * E-Learning lanjutan — isi materi kaya (HTML editor "seperti Word") + kuis
     * auto-nilai per materi. Lihat §14 RENCANA_EKOSISTEM.
     *
     * Kolom referensi tetap string UUID Dapodik tanpa FK (sinkron delete+insert).
     * Idempoten: cek hasTable/hasColumn dulu.
     */
    public function up(): void
    {
        if (Schema::hasTable('learn_materi') && !Schema::hasColumn('learn_materi', 'isi_html')) {
            Schema::table('learn_materi', function (Blueprint $table) {
                $table->longText('isi_html')->nullable()->after('deskripsi');
            });
        }

        if (!Schema::hasTable('learn_materi_soal')) {
            Schema::create('learn_materi_soal', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('materi_id')->index();
                $table->string('tipe', 20)->default('pg')->comment('pg | bs | isian');
                $table->text('pertanyaan');
                $table->json('opsi')->nullable();
                $table->string('kunci', 255)->nullable();
                $table->unsignedTinyInteger('bobot')->default(1);
                $table->unsignedInteger('urutan')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('learn_materi_jawaban')) {
            Schema::create('learn_materi_jawaban', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('materi_id')->index();
                $table->string('peserta_didik_id', 50)->index();
                $table->json('jawaban')->nullable();
                $table->decimal('skor', 5, 2)->default(0);
                $table->unsignedSmallInteger('benar')->default(0);
                $table->unsignedSmallInteger('total_soal')->default(0);
                $table->dateTime('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['materi_id', 'peserta_didik_id'], 'learn_materi_jawaban_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learn_materi_jawaban');
        Schema::dropIfExists('learn_materi_soal');
        if (Schema::hasTable('learn_materi') && Schema::hasColumn('learn_materi', 'isi_html')) {
            Schema::table('learn_materi', function (Blueprint $table) {
                $table->dropColumn('isi_html');
            });
        }
    }
};
