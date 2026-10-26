<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelengkapan TUPOKSI Laboran — menutup kesenjangan bukti fisik supervisi.
 *
 * Indikator yang dilayani:
 *  1. Buku Administrasi Jurusan (catatan administrasi program keahlian).
 *  2. Bon/Form Permintaan Bahan & Alat Praktik Pembelajaran.
 *  7. Arsip dokumen administrasi bengkel/lab (ordner digital).
 *  8. Jurnal Kegiatan harian laboran.
 *  9. Dokumentasi kegiatan kebersihan lingkungan produktif jurusan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 2. BON / FORM PERMINTAAN BAHAN & ALAT PRAKTIK
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('laboran_permintaan')) {
            Schema::create('laboran_permintaan', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('nomor_permintaan', 60)->unique();

                $table->date('tanggal_permintaan')->nullable()->index();
                $table->date('tanggal_dibutuhkan')->nullable()->index();

                // Pemohon = guru pengampu / wali kelas / kaprog
                $table->string('pemohon_ptk_id', 50)->nullable()->index();
                $table->string('pemohon_nama');
                $table->string('rombel_id', 50)->nullable()->index();
                $table->string('rombel_nama')->nullable();
                $table->string('mata_pelajaran')->nullable();
                $table->string('ruang_lab_nama', 100)->nullable()->index();

                $table->string('keperluan')->nullable();
                $table->text('catatan_pemohon')->nullable();

                $table->unsignedInteger('total_item')->default(0);

                // diajukan, disiapkan, diserahkan, selesai, ditolak
                $table->string('status', 30)->default('diajukan')->index();

                // Petugas laboran yang menyiapkan
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->timestamp('tanggal_disiapkan')->nullable();
                $table->timestamp('tanggal_diserahkan')->nullable();
                $table->text('catatan_petugas')->nullable();

                $table->timestamps();

                $table->index(['jurusan_id', 'status'], 'idx_lab_permintaan_jurusan_status');
            });
        }

        if (!Schema::hasTable('laboran_permintaan_item')) {
            Schema::create('laboran_permintaan_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permintaan_id')->index();
                $table->unsignedBigInteger('item_id')->nullable()->index();

                $table->string('nama_item');
                $table->string('kode_item', 50)->nullable();
                $table->string('jenis', 50)->default('alat'); // alat, bahan_habis_pakai

                $table->decimal('jumlah_diminta', 12, 2)->default(1);
                $table->decimal('jumlah_diberikan', 12, 2)->nullable();
                $table->string('satuan', 30)->default('unit');

                // menunggu, tersedia, kurang, tidak_ada, diserahkan
                $table->string('status_ketersediaan', 30)->default('menunggu')->index();
                $table->text('keterangan')->nullable();
                $table->unsignedSmallInteger('urutan')->default(0);

                $table->timestamps();
            });
        }

        // ---------------------------------------------------------------------
        // 8. JURNAL KEGIATAN LABORAN
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('laboran_jurnal')) {
            Schema::create('laboran_jurnal', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->date('tanggal')->index();
                $table->string('ruang_lab_nama', 100)->nullable()->index();

                $table->string('shift', 20)->default('pagi'); // pagi, siang, penuh

                $table->string('kegiatan');
                // Kategori kegiatan selaras tupoksi
                $table->string('kategori', 40)->default('lainnya')->index();
                // administrasi, penyiapan, pemeliharaan, inventarisasi, pemeriksaan,
                // kebutuhan, pengarsipan, pelaporan, kebersihan, pelayanan_praktik, lainnya

                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->unsignedSmallInteger('durasi_menit')->default(0);

                $table->text('uraian')->nullable();
                $table->text('hasil')->nullable();
                $table->text('kendala')->nullable();
                $table->text('tindak_lanjut')->nullable();

                $table->unsignedInteger('jumlah_siswa')->nullable();
                $table->string('guru_terkait')->nullable();

                $table->string('foto_path')->nullable();
                $table->text('catatan')->nullable();

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();

                $table->string('status', 30)->default('selesai')->index(); // draf, selesai, terverifikasi

                $table->timestamps();

                $table->index(['jurusan_id', 'tanggal'], 'idx_lab_jurnal_jurusan_tgl');
                $table->index(['jurusan_id', 'kategori'], 'idx_lab_jurnal_jurusan_kat');
            });
        }

        // ---------------------------------------------------------------------
        // 9. DOKUMENTASI KEBERSIHAN LINGKUNGAN PRODUKTIF
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('laboran_kebersihan')) {
            Schema::create('laboran_kebersihan', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('nomor_kegiatan', 60)->unique();

                $table->date('tanggal_kegiatan')->index();
                $table->string('area', 150);
                $table->string('ruang_lab_nama', 100)->nullable()->index();

                // harian, mingguan, bulanan, insidental
                $table->string('jenis_kegiatan', 30)->default('harian')->index();

                $table->text('uraian_kegiatan')->nullable();
                $table->text('peralatan_digunakan')->nullable();
                $table->text('bahan_digunakan')->nullable();

                $table->unsignedInteger('jumlah_petugas')->default(1);
                $table->text('nama_petugas')->nullable();

                // bersih, cukup, kotor, perlu_tindakan
                $table->string('kondisi_akhir', 40)->default('bersih')->index();

                $table->text('temuan')->nullable();
                $table->text('tindak_lanjut')->nullable();

                $table->string('foto_sebelum')->nullable();
                $table->string('foto_sesudah')->nullable();
                $table->text('dokumentasi_paths')->nullable()->comment('JSON daftar path foto dokumentasi');

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('status', 30)->default('selesai')->index();

                $table->timestamps();

                $table->index(['jurusan_id', 'tanggal_kegiatan'], 'idx_lab_bersih_jurusan_tgl');
            });
        }

        // ---------------------------------------------------------------------
        // 1. BUKU ADMINISTRASI JURUSAN
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('laboran_administrasi')) {
            Schema::create('laboran_administrasi', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();

                $table->string('nomor_urut', 30)->nullable();
                $table->date('tanggal')->index();
                $table->string('tahun_ajaran', 20)->nullable()->index();
                $table->string('semester', 10)->nullable();

                // Jenis administrasi jurusan
                $table->string('jenis_dokumen', 60)->index();
                // surat_masuk, surat_keluar, sk, notulen_rapat, daftar_hadir,
                // inventaris_ruang, program_kerja, laporan, berita_acara, lainnya

                $table->string('nomor_dokumen', 100)->nullable();
                $table->string('perihal');
                $table->string('asal_tujuan')->nullable();

                $table->date('tanggal_dokumen')->nullable();
                $table->text('ringkasan')->nullable();
                $table->text('keterangan')->nullable();

                $table->string('file_path')->nullable();
                $table->string('status', 30)->default('aktif')->index(); // aktif, arsip, dibatalkan

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();

                $table->timestamps();

                $table->index(['jurusan_id', 'tanggal'], 'idx_lab_admin_jurusan_tgl');
                $table->index(['jurusan_id', 'jenis_dokumen'], 'idx_lab_admin_jurusan_jenis');
            });
        }

        // ---------------------------------------------------------------------
        // 7. ARSIP DOKUMEN (ORDNER DIGITAL)
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('laboran_arsip')) {
            Schema::create('laboran_arsip', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();

                $table->string('kode_arsip', 60)->unique();
                $table->string('nama_dokumen');

                // Jenis arsip sesuai bukti fisik tupoksi
                $table->string('kategori', 60)->index();
                // berita_acara_kerusakan, kartu_peminjaman, kartu_perawatan,
                // bon_permintaan, checklist_pemeriksaan, buku_inventaris,
                // usulan_pengadaan, laporan, administrasi, dokumentasi, lainnya

                $table->string('nomor_dokumen', 100)->nullable();
                $table->date('tanggal_dokumen')->nullable()->index();
                $table->string('tahun_ajaran', 20)->nullable()->index();

                $table->string('sumber_tabel', 60)->nullable()->comment('Tabel asal bila arsip otomatis');
                $table->unsignedBigInteger('sumber_id')->nullable()->index();

                $table->text('deskripsi')->nullable();
                $table->string('file_path')->nullable();
                $table->string('lokasi_fisik', 150)->nullable()->comment('Ordner/lemari penyimpanan fisik');

                $table->string('status', 30)->default('aktif')->index(); // aktif, inaktif, dimusnahkan
                $table->string('tingkat_akses', 20)->default('internal'); // internal, terbatas, publik

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();

                $table->timestamps();

                $table->index(['jurusan_id', 'kategori'], 'idx_lab_arsip_jurusan_kat');
                $table->index(['jurusan_id', 'tanggal_dokumen'], 'idx_lab_arsip_jurusan_tgl');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laboran_arsip');
        Schema::dropIfExists('laboran_administrasi');
        Schema::dropIfExists('laboran_kebersihan');
        Schema::dropIfExists('laboran_jurnal');
        Schema::dropIfExists('laboran_permintaan_item');
        Schema::dropIfExists('laboran_permintaan');
    }
};
