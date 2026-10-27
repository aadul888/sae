<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('laboran_jadwal_penggunaan')) {
            Schema::table('laboran_jadwal_penggunaan', function (Blueprint $table) {
                if (!Schema::hasColumn('laboran_jadwal_penggunaan', 'pemberlakuan')) {
                    $table->string('pemberlakuan', 20)->default('sekali')->after('tanggal');
                }
                if (!Schema::hasColumn('laboran_jadwal_penggunaan', 'ptk_ids')) {
                    $table->text('ptk_ids')->nullable()->after('ptk_id');
                }
                if (!Schema::hasColumn('laboran_jadwal_penggunaan', 'guru_pendamping')) {
                    $table->text('guru_pendamping')->nullable()->after('ptk_ids');
                }
                if (!Schema::hasColumn('laboran_jadwal_penggunaan', 'jadwal_seri_id')) {
                    $table->string('jadwal_seri_id', 60)->nullable()->index()->after('pemberlakuan');
                }
                $table->string('ptk_id', 255)->nullable()->change();
            });
        }

        if (Schema::hasTable('laboran_usulan_pengadaan')) {
            Schema::table('laboran_usulan_pengadaan', function (Blueprint $table) {
                if (!Schema::hasColumn('laboran_usulan_pengadaan', 'jenis')) {
                    $table->string('jenis', 30)->default('semua')->after('semester');
                }
                $table->string('sumber_dana', 40)->nullable()->change();
                $table->string('pemohon_nama', 255)->nullable()->change();
            });

            // Fallback bila schema change gagal dijalankan (mis. doctrine/dbal
            // belum terpasang): ubah nullability langsung via SQL.
            $sd = DB::selectOne(
                'SELECT IS_NULLABLE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['laboran_usulan_pengadaan', 'sumber_dana']
            );
            if ($sd && strtoupper((string) $sd->IS_NULLABLE) !== 'YES') {
                DB::statement("ALTER TABLE `laboran_usulan_pengadaan` MODIFY `sumber_dana` VARCHAR(40) NULL DEFAULT NULL");
            }
        }

        if (Schema::hasTable('laboran_peminjaman_item')) {
            Schema::table('laboran_peminjaman_item', function (Blueprint $table) {
                if (!Schema::hasColumn('laboran_peminjaman_item', 'sumber')) {
                    $table->string('sumber', 30)->default('inventaris')->after('item_id');
                }
                if (!Schema::hasColumn('laboran_peminjaman_item', 'aset_id')) {
                    $table->unsignedBigInteger('aset_id')->nullable()->after('sumber');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laboran_jadwal_penggunaan')) {
            Schema::table('laboran_jadwal_penggunaan', function (Blueprint $table) {
                $cols = array_filter(['pemberlakuan', 'ptk_ids', 'guru_pendamping', 'jadwal_seri_id'], fn($c) => Schema::hasColumn('laboran_jadwal_penggunaan', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('laboran_usulan_pengadaan')) {
            Schema::table('laboran_usulan_pengadaan', function (Blueprint $table) {
                if (Schema::hasColumn('laboran_usulan_pengadaan', 'jenis')) {
                    $table->dropColumn('jenis');
                }
            });
        }

        if (Schema::hasTable('laboran_peminjaman_item')) {
            Schema::table('laboran_peminjaman_item', function (Blueprint $table) {
                $cols = array_filter(['sumber', 'aset_id'], fn($c) => Schema::hasColumn('laboran_peminjaman_item', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
