<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom sanggah & resolusi ke tabel kedisiplinan_pelanggaran
        if (Schema::hasTable('kedisiplinan_pelanggaran')) {
            Schema::table('kedisiplinan_pelanggaran', function (Blueprint $table) {
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_status')) {
                    $table->string('sanggah_status', 30)->nullable()->after('status_tindak_lanjut')->index();
                }
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_jenis')) {
                    $table->string('sanggah_jenis', 50)->nullable()->after('sanggah_status');
                }
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_keterangan')) {
                    $table->text('sanggah_keterangan')->nullable()->after('sanggah_jenis');
                }
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_poin_potong')) {
                    $table->integer('sanggah_poin_potong')->default(0)->after('sanggah_keterangan');
                }
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_validator')) {
                    $table->string('sanggah_validator', 100)->nullable()->after('sanggah_poin_potong');
                }
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_tanggal')) {
                    $table->date('sanggah_tanggal')->nullable()->after('sanggah_validator');
                }
                if (!Schema::hasColumn('kedisiplinan_pelanggaran', 'sanggah_bukti')) {
                    $table->string('sanggah_bukti', 255)->nullable()->after('sanggah_tanggal');
                }
            });
        }

        // 2. Tambah kolom relasi siswa & kedisiplinan ke tabel keamanan_insiden
        if (Schema::hasTable('keamanan_insiden')) {
            Schema::table('keamanan_insiden', function (Blueprint $table) {
                if (!Schema::hasColumn('keamanan_insiden', 'kategori_insiden')) {
                    $table->string('kategori_insiden', 50)->default('umum')->after('judul_insiden')->index();
                }
                if (!Schema::hasColumn('keamanan_insiden', 'peserta_didik_id')) {
                    $table->unsignedBigInteger('peserta_didik_id')->nullable()->after('pihak_terlibat')->index();
                }
                if (!Schema::hasColumn('keamanan_insiden', 'tata_tertib_id')) {
                    $table->unsignedBigInteger('tata_tertib_id')->nullable()->after('peserta_didik_id')->index();
                }
                if (!Schema::hasColumn('keamanan_insiden', 'pelanggaran_id')) {
                    $table->unsignedBigInteger('pelanggaran_id')->nullable()->after('tata_tertib_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kedisiplinan_pelanggaran')) {
            Schema::table('kedisiplinan_pelanggaran', function (Blueprint $table) {
                $cols = ['sanggah_status', 'sanggah_jenis', 'sanggah_keterangan', 'sanggah_poin_potong', 'sanggah_validator', 'sanggah_tanggal', 'sanggah_bukti'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('kedisiplinan_pelanggaran', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('keamanan_insiden')) {
            Schema::table('keamanan_insiden', function (Blueprint $table) {
                $cols = ['kategori_insiden', 'peserta_didik_id', 'tata_tertib_id', 'pelanggaran_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('keamanan_insiden', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
