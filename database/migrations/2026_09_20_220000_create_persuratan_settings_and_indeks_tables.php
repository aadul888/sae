<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Pengaturan Sistem Persuratan & Penyimpanan HDD
        if (!Schema::hasTable('persuratan_settings')) {
            Schema::create('persuratan_settings', function (Blueprint $table) {
                $table->id();
                $table->string('hdd_path')->default(storage_path('app/arsip_persuratan'));
                $table->boolean('is_hdd_active')->default(true);
                $table->string('format_nomor_surat_keluar')->default('{nomor}/{kode_indeks}/SMK-SAE/{romawi_bulan}/{tahun}');
                $table->string('format_nomor_surat_keterangan')->default('421.5/{nomor}/SMK-SAE/{romawi_bulan}/{tahun}');
                $table->integer('nomor_terakhir_surat_keluar')->default(0);
                $table->integer('nomor_terakhir_surat_keterangan')->default(0);
                $table->integer('tahun_terakhir')->default(2026);
                $table->string('sekolah_kode')->default('SMK-SAE');
                $table->boolean('auto_subfolder')->default(true);
                $table->timestamps();
            });

            // Insert default row
            DB::table('persuratan_settings')->insert([
                'hdd_path' => storage_path('app/arsip_persuratan'),
                'is_hdd_active' => true,
                'format_nomor_surat_keluar' => '{nomor}/{kode_indeks}/SMK-SAE/{romawi_bulan}/{tahun}',
                'format_nomor_surat_keterangan' => '421.5/{nomor}/SMK-SAE/{romawi_bulan}/{tahun}',
                'nomor_terakhir_surat_keluar' => 0,
                'nomor_terakhir_surat_keterangan' => 0,
                'tahun_terakhir' => (int) date('Y'),
                'sekolah_kode' => 'SMK-SAE',
                'auto_subfolder' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Tabel Master Referensi Indeks Klasifikasi Surat
        if (!Schema::hasTable('ref_indeks_surat')) {
            Schema::create('ref_indeks_surat', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('judul');
                $table->string('kategori', 50)->default('Umum'); // Kesiswaan, Kepegawaian, Umum, Keuangan, Sarpras, Kurikulum
                $table->text('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Data Klasifikasi Baku Kearsipan Sekolah
            $defaultIndeks = [
                ['kode' => '421', 'judul' => 'Pendidikan Dasar & Menengah', 'kategori' => 'Umum', 'keterangan' => 'Kebijakan umum penyelenggaraan pendidikan sekolah'],
                ['kode' => '421.1', 'judul' => 'Prasarana Sekolah & Akreditasi', 'kategori' => 'Sarpras', 'keterangan' => 'Akreditasi satuan pendidikan dan sarana fisik sekolah'],
                ['kode' => '421.2', 'judul' => 'Kurikulum & Pembelajaran', 'kategori' => 'Kurikulum', 'keterangan' => 'Kalender akademik, jadwal kbm, dan perangkat kurikulum'],
                ['kode' => '421.3', 'judul' => 'Kesiswaan & Ekstrakurikuler', 'kategori' => 'Kesiswaan', 'keterangan' => 'Kegiatan kesiswaan, OSIS, lomba, dan kepramukaan'],
                ['kode' => '421.5', 'judul' => 'Surat Keterangan Peserta Didik', 'kategori' => 'Kesiswaan', 'keterangan' => 'Keterangan siswa aktif, rekomendasi, kelakuan baik, beasiswa PIP'],
                ['kode' => '421.7', 'judul' => 'Kelulusan, Ijazah & Ujian', 'kategori' => 'Kesiswaan', 'keterangan' => 'Pelaksanaan asesmen, kelulusan, dan penyerahan ijazah'],
                ['kode' => '800', 'judul' => 'Kepegawaian & Ketenagaan GTK', 'kategori' => 'Kepegawaian', 'keterangan' => 'Surat tugas dinas, KGB, cuti, dan pembinaan guru/tendik'],
                ['kode' => '005', 'judul' => 'Undangan Kedinasan & Rapat', 'kategori' => 'Umum', 'keterangan' => 'Undangan rapat dinas, komite sekolah, dan orang tua wali murid'],
                ['kode' => '045', 'judul' => 'Kearsipan & Dokumentasi', 'kategori' => 'Umum', 'keterangan' => 'Tata naskah dinas, serah terima arsip, dan dokumentasi'],
                ['kode' => '900', 'judul' => 'Keuangan & Pembiayaan Sekolah', 'kategori' => 'Keuangan', 'keterangan' => 'Administrasi dana BOS, komite, dan pertanggungjawaban anggaran'],
            ];

            foreach ($defaultIndeks as $indeks) {
                DB::table('ref_indeks_surat')->insert(array_merge($indeks, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 3. Pastikan tabel persuratan memiliki kolom referensi indeks dan file_size
        if (Schema::hasTable('persuratan')) {
            Schema::table('persuratan', function (Blueprint $table) {
                if (!Schema::hasColumn('persuratan', 'kode_indeks')) {
                    $table->string('kode_indeks', 50)->nullable()->after('nomor_surat')->index();
                }
                if (!Schema::hasColumn('persuratan', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('persuratan', 'file_name_original')) {
                    $table->string('file_name_original')->nullable()->after('file_size');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persuratan_settings');
        Schema::dropIfExists('ref_indeks_surat');
        if (Schema::hasTable('persuratan')) {
            Schema::table('persuratan', function (Blueprint $table) {
                if (Schema::hasColumn('persuratan', 'kode_indeks')) {
                    $table->dropColumn('kode_indeks');
                }
                if (Schema::hasColumn('persuratan', 'file_size')) {
                    $table->dropColumn('file_size');
                }
                if (Schema::hasColumn('persuratan', 'file_name_original')) {
                    $table->dropColumn('file_name_original');
                }
            });
        }
    }
};
