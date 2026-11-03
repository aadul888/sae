<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('perpus_koleksi_buku')) {
            Schema::table('perpus_koleksi_buku', function (Blueprint $table) {
                if (!Schema::hasColumn('perpus_koleksi_buku', 'format')) {
                    $table->string('format', 20)->default('fisik')->index()->after('kategori');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'tautan_baca')) {
                    $table->text('tautan_baca')->nullable()->after('cover_buku');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'file_ebook')) {
                    $table->string('file_ebook', 255)->nullable()->after('tautan_baca');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'sumber_ebook')) {
                    $table->string('sumber_ebook', 50)->nullable()->index()->after('file_ebook');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'sinopsis')) {
                    $table->text('sinopsis')->nullable()->after('sumber_ebook');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'jenjang')) {
                    $table->string('jenjang', 30)->nullable()->index()->after('sinopsis');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'kelas')) {
                    $table->string('kelas', 20)->nullable()->index()->after('jenjang');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'jurusan')) {
                    $table->string('jurusan', 100)->nullable()->index()->after('kelas');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'total_dibaca')) {
                    $table->unsignedInteger('total_dibaca')->default(0)->after('jurusan');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('perpus_koleksi_buku')) {
            Schema::table('perpus_koleksi_buku', function (Blueprint $table) {
                $columns = [
                    'format',
                    'tautan_baca',
                    'file_ebook',
                    'sumber_ebook',
                    'sinopsis',
                    'jenjang',
                    'kelas',
                    'jurusan',
                    'total_dibaca',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('perpus_koleksi_buku', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
