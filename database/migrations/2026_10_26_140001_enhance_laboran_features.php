<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            });
        }

        if (Schema::hasTable('laboran_usulan_pengadaan')) {
            Schema::table('laboran_usulan_pengadaan', function (Blueprint $table) {
                if (!Schema::hasColumn('laboran_usulan_pengadaan', 'jenis')) {
                    $table->string('jenis', 30)->default('semua')->after('semester');
                }
            });
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
