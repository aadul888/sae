<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Longgarkan status_keluar pada peserta_didik_tidak_aktif agar muat referensi resmi Dapodik
        if (Schema::hasTable('peserta_didik_tidak_aktif')) {
            try {
                DB::statement("ALTER TABLE peserta_didik_tidak_aktif MODIFY COLUMN status_keluar VARCHAR(50) NOT NULL DEFAULT 'Alumni'");
            } catch (\Throwable $e) {
                // abaikan jika sudah di-alter
            }
        }

        // 2. Tambah kolom alur mutasi 2 tahap & snapshot pada kesiswaan_mutasi
        if (Schema::hasTable('kesiswaan_mutasi')) {
            Schema::table('kesiswaan_mutasi', function (Blueprint $table) {
                if (!Schema::hasColumn('kesiswaan_mutasi', 'nama_siswa')) {
                    $table->string('nama_siswa', 150)->nullable()->after('peserta_didik_id');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'nisn_siswa')) {
                    $table->string('nisn_siswa', 20)->nullable()->after('nama_siswa');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'nipd_siswa')) {
                    $table->string('nipd_siswa', 50)->nullable()->after('nisn_siswa');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'rombel_terakhir')) {
                    $table->string('rombel_terakhir', 100)->nullable()->after('nipd_siswa');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'file_surat_penerimaan')) {
                    $table->string('file_surat_penerimaan')->nullable()->after('file_berkas');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'file_surat_mutasi')) {
                    $table->string('file_surat_mutasi')->nullable()->after('file_surat_penerimaan');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'nomor_surat_dapodik')) {
                    $table->string('nomor_surat_dapodik')->nullable()->after('nomor_surat_mutasi');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'status_proses')) {
                    $table->string('status_proses', 30)->default('menunggu_dapodik')->after('catatan');
                }
                if (!Schema::hasColumn('kesiswaan_mutasi', 'dapodik_synced_at')) {
                    $table->timestamp('dapodik_synced_at')->nullable()->after('status_proses');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kesiswaan_mutasi')) {
            Schema::table('kesiswaan_mutasi', function (Blueprint $table) {
                $cols = ['nama_siswa', 'nisn_siswa', 'nipd_siswa', 'rombel_terakhir', 'file_surat_penerimaan', 'file_surat_mutasi', 'nomor_surat_dapodik', 'status_proses', 'dapodik_synced_at'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('kesiswaan_mutasi', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
