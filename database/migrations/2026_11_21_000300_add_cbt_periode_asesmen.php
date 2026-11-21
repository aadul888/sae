<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CBT "bagian tenang" lanjutan: Periode Asesmen (PTS/PAS/PAT/Ulangan).
     *
     * Admin menjalankan asesmen sebagai satu kesatuan (tahun ajaran + semester +
     * jadwal) lalu menautkan kertas ujian ke periode tersebut. TIDAK menyentuh
     * pelaksanaan serentak (attempt/jawaban) — masih menunggu fondasi §10.
     *
     * Tanpa FK constraint (pembelajaran_id = UUID Dapodik). Idempoten.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cbt_periode_asesmen')) {
            Schema::create('cbt_periode_asesmen', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 150);
                $table->string('tipe', 20)->default('uts')->comment('ulangan | uts | uas | pat | latihan');
                $table->string('tahun_ajaran', 20)->nullable();
                $table->string('semester', 20)->nullable()->comment('ganjil | genap');
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->string('status', 20)->default('draft')->index()->comment('draft | aktif | selesai');
                $table->text('keterangan')->nullable();
                $table->string('created_by', 50)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ujian') && !Schema::hasColumn('ujian', 'periode_id')) {
            Schema::table('ujian', function (Blueprint $table) {
                $table->unsignedBigInteger('periode_id')->nullable()->index()->after('pembelajaran_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ujian') && Schema::hasColumn('ujian', 'periode_id')) {
            Schema::table('ujian', function (Blueprint $table) {
                $table->dropColumn('periode_id');
            });
        }

        Schema::dropIfExists('cbt_periode_asesmen');
    }
};
