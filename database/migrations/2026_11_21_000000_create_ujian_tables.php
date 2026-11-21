<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CBT "bagian tenang": bank soal + definisi ujian. Lihat §14.7 RENCANA_EKOSISTEM.
     *
     * Pelaksanaan ujian serentak (attempt, autosave, submit) SENGAJA BELUM ADA —
     * menunggu fondasi Redis/queue/session §10. Tabel di sini hanya menyiapkan
     * soal & kertas ujian (beban rendah).
     *
     * Tanpa FK constraint (pembelajaran_id/peserta_didik_id = UUID Dapodik, feeder
     * delete+insert penuh). Cukup index.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ujian_soal')) {
            Schema::create('ujian_soal', function (Blueprint $table) {
                $table->id();
                $table->string('pembelajaran_id', 50)->index();
                $table->string('tipe', 20)->default('pg')->comment('pg | benar_salah | isian | uraian');
                $table->text('pertanyaan');
                // Opsi jawaban (JSON array) untuk pg/benar_salah; null untuk isian/uraian.
                $table->text('opsi')->nullable();
                // Kunci jawaban: huruf opsi (pg/benar_salah), teks (isian). Uraian dinilai manual.
                $table->string('kunci', 500)->nullable();
                $table->unsignedTinyInteger('bobot')->default(1);
                $table->string('tingkat', 20)->nullable()->comment('mudah | sedang | sulit');
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ujian')) {
            Schema::create('ujian', function (Blueprint $table) {
                $table->id();
                $table->string('pembelajaran_id', 50)->index();
                $table->string('judul', 200);
                $table->text('deskripsi')->nullable();
                $table->string('tipe', 20)->default('ulangan')->comment('ulangan | uts | uas | latihan');
                $table->unsignedSmallInteger('durasi_menit')->default(60);
                $table->unsignedTinyInteger('kkm')->default(75);
                $table->dateTime('jadwal_mulai')->nullable();
                $table->dateTime('jadwal_selesai')->nullable();
                $table->boolean('acak_soal')->default(false);
                $table->boolean('acak_opsi')->default(false);
                $table->string('status', 20)->default('draft')->index()->comment('draft | terbit | ditutup');
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ujian_soal_item')) {
            Schema::create('ujian_soal_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ujian_id')->index();
                $table->unsignedBigInteger('soal_id')->index();
                $table->unsignedInteger('urutan')->default(0);
                $table->timestamps();

                $table->unique(['ujian_id', 'soal_id'], 'ujian_soal_item_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_soal_item');
        Schema::dropIfExists('ujian');
        Schema::dropIfExists('ujian_soal');
    }
};
