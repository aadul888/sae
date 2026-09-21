<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('persuratan') && !Schema::hasColumn('persuratan', 'sarpras_aset_id')) {
            Schema::table('persuratan', function (Blueprint $table) {
                $table->unsignedBigInteger('sarpras_aset_id')->nullable()->after('keterangan')->index();
            });
        }

        if (Schema::hasTable('surat_keterangan_pd')) {
            Schema::table('surat_keterangan_pd', function (Blueprint $table) {
                if (!Schema::hasColumn('surat_keterangan_pd', 'tanggal_agenda')) {
                    $table->date('tanggal_agenda')->nullable()->after('tanggal_surat');
                }
                if (!Schema::hasColumn('surat_keterangan_pd', 'waktu_agenda')) {
                    $table->string('waktu_agenda', 50)->nullable()->after('tanggal_agenda');
                }
                if (!Schema::hasColumn('surat_keterangan_pd', 'tempat_agenda')) {
                    $table->string('tempat_agenda', 100)->nullable()->after('waktu_agenda');
                }
                if (!Schema::hasColumn('surat_keterangan_pd', 'menghadap_agenda')) {
                    $table->string('menghadap_agenda', 100)->nullable()->after('tempat_agenda');
                }
                if (!Schema::hasColumn('surat_keterangan_pd', 'catatan_khusus')) {
                    $table->text('catatan_khusus')->nullable()->after('menghadap_agenda');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('persuratan') && Schema::hasColumn('persuratan', 'sarpras_aset_id')) {
            Schema::table('persuratan', function (Blueprint $table) {
                $table->dropColumn('sarpras_aset_id');
            });
        }

        if (Schema::hasTable('surat_keterangan_pd')) {
            Schema::table('surat_keterangan_pd', function (Blueprint $table) {
                $table->dropColumn([
                    'tanggal_agenda',
                    'waktu_agenda',
                    'tempat_agenda',
                    'menghadap_agenda',
                    'catatan_khusus',
                ]);
            });
        }
    }
};
