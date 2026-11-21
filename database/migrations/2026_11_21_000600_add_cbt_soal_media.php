<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Media bank soal CBT: gambar soal, video (embed URL), dan gambar per opsi.
     *
     * `opsi_gambar` = JSON array sejajar dengan `opsi` (indeks sama, null bila
     * opsi tanpa gambar). Idempoten: cek hasColumn dulu.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ujian_soal', 'gambar')) {
            Schema::table('ujian_soal', function (Blueprint $table) {
                $table->string('gambar', 255)->nullable()->after('pertanyaan');
            });
        }

        if (! Schema::hasColumn('ujian_soal', 'video_url')) {
            Schema::table('ujian_soal', function (Blueprint $table) {
                $table->string('video_url', 500)->nullable()->after('gambar');
            });
        }

        if (! Schema::hasColumn('ujian_soal', 'opsi_gambar')) {
            Schema::table('ujian_soal', function (Blueprint $table) {
                $table->text('opsi_gambar')->nullable()->after('opsi');
            });
        }
    }

    public function down(): void
    {
        foreach (['opsi_gambar', 'video_url', 'gambar'] as $col) {
            if (Schema::hasColumn('ujian_soal', $col)) {
                Schema::table('ujian_soal', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
