<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Laboratorium per JURUSAN — perluasan tata kelola alat & bahan praktik.
 *
 * Menambah:
 *  1. Kolom `jurusan_id` pada tabel laboran yang sudah ada (scoping per jurusan).
 *  2. Tabel analisis kebutuhan alat & bahan.
 *  3. Tabel perawatan/pemeliharaan/penataan alat (K3) + kartu perawatan.
 *  4. Tabel pemeriksaan (daftar ceklist) alat & bahan.
 *  5. Tabel usulan pengadaan alat & bahan.
 *  6. Tabel berita acara kerusakan alat.
 *  7. Tabel peminjaman alat khusus jurusan.
 *
 * Seluruh operasi bersifat idempoten agar aman dijalankan berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->tambahKolomJurusan();
        $this->buatTabelKebutuhan();
        $this->buatTabelPerawatan();
        $this->buatTabelPemeriksaan();
        $this->buatTabelPengadaan();
        $this->buatTabelKerusakan();
        $this->buatTabelPeminjaman();
    }

    public function down(): void
    {
        Schema::dropIfExists('laboran_peminjaman_item');
        Schema::dropIfExists('laboran_peminjaman');
        Schema::dropIfExists('laboran_berita_acara_kerusakan');
        Schema::dropIfExists('laboran_usulan_pengadaan_item');
        Schema::dropIfExists('laboran_usulan_pengadaan');
        Schema::dropIfExists('laboran_pemeriksaan_item');
        Schema::dropIfExists('laboran_pemeriksaan');
        Schema::dropIfExists('laboran_kartu_perawatan');
        Schema::dropIfExists('laboran_perawatan');
        Schema::dropIfExists('laboran_kebutuhan');

        foreach (['laboran_bahan_alat', 'laboran_jadwal_penggunaan'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'jurusan_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('jurusan_id');
                });
            }
        }
    }

    // -------------------------------------------------------------------------
    // 1. Scoping per jurusan pada tabel lama
    // -------------------------------------------------------------------------
    private function tambahKolomJurusan(): void
    {
        foreach (['laboran_bahan_alat', 'laboran_jadwal_penggunaan'] as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'jurusan_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->string('jurusan_id', 50)->nullable()->index()
                    ->comment('Scoping data per jurusan / kompetensi keahlian');
            });
        }
    }

    // -------------------------------------------------------------------------
    // 2. Analisis & Input Kebutuhan Alat/Bahan
    // -------------------------------------------------------------------------
    private function buatTabelKebutuhan(): void
    {
        if (Schema::hasTable('laboran_kebutuhan')) {
            return;
        }

        Schema::create('laboran_kebutuhan', function (Blueprint $table) {
            $table->id();
            $table->string('jurusan_id', 50)->nullable()->index();
            $table->string('ruang_lab_nama', 100)->nullable()->index();
            $table->string('tahun_ajaran', 20)->nullable()->index();
            $table->string('semester', 10)->nullable();

            $table->string('nama_item');
            $table->string('jenis', 50)->default('alat')->index();   // alat, bahan_habis_pakai
            $table->string('kategori', 100)->nullable();             // Instrumen, APD, Habis Pakai, dll
            $table->text('spesifikasi')->nullable();

            $table->decimal('jumlah_kebutuhan', 12, 2)->default(0);
            $table->decimal('jumlah_tersedia', 12, 2)->default(0);
            $table->string('satuan', 30)->default('unit');
            $table->decimal('selisih', 12, 2)->default(0)->comment('Kekurangan = kebutuhan - tersedia');

            $table->string('prioritas', 20)->default('sedang')->index(); // rendah, sedang, tinggi, kritis
            $table->string('sumber_analisis', 50)->default('praktik');   // praktik, jadwal, permintaan_guru, kerusakan, lainnya
            $table->text('analisis_kebutuhan')->nullable();
            $table->string('referensi_mapel')->nullable();

            $table->string('status', 30)->default('draf')->index();      // draf, diverifikasi, disetujui, ditolak, dipenuhi
            $table->date('tanggal_input')->nullable()->index();
            $table->string('dibuat_oleh_ptk_id', 50)->nullable();
            $table->text('catatan_verifikasi')->nullable();

            $table->timestamps();

            $table->index(['jurusan_id', 'status'], 'idx_lab_kebutuhan_jurusan_status');
        });
    }

    // -------------------------------------------------------------------------
    // 3. Perawatan / Pemeliharaan / Penataan Alat (K3) + Kartu Perawatan
    // -------------------------------------------------------------------------
    private function buatTabelPerawatan(): void
    {
        if (!Schema::hasTable('laboran_perawatan')) {
            Schema::create('laboran_perawatan', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('ruang_lab_nama', 100)->nullable()->index();
                $table->string('kode_perawatan', 60)->unique();

                $table->unsignedBigInteger('item_id')->nullable()->index();
                $table->string('nama_alat')->nullable();
                $table->string('kode_alat', 50)->nullable();

                // rutin, berkala, perbaikan, penataan, kalibrasi
                $table->string('jenis_perawatan', 40)->default('rutin')->index();
                $table->string('kategori_k3', 60)->nullable()
                    ->comment('Klasifikasi K3: listrik, bahan_kimia, mekanik, ergonomi, kebakaran, biologis');

                $table->date('tanggal_perawatan')->nullable()->index();
                $table->date('periode_berikutnya')->nullable()->index();
                $table->string('pelaksana_ptk_id', 50)->nullable();
                $table->string('pelaksana_nama')->nullable();

                $table->text('uraian_pekerjaan')->nullable();
                $table->text('prosedur_k3')->nullable();
                $table->json('apd_digunakan')->nullable()->comment('Daftar APD yang dipakai saat perawatan');
                $table->text('hasil')->nullable();
                $table->string('kondisi_akhir', 40)->nullable()->index(); // baik, rusak_ringan, rusak_berat, tidak_laik
                $table->decimal('biaya', 15, 2)->nullable();

                $table->string('status', 30)->default('dijadwalkan')->index(); // dijadwalkan, proses, selesai, ditunda
                $table->text('catatan')->nullable();
                $table->string('dibuat_oleh_ptk_id', 50)->nullable();

                $table->timestamps();

                $table->index(['jurusan_id', 'status'], 'idx_lab_perawatan_jurusan_status');
                $table->index(['jurusan_id', 'jenis_perawatan'], 'idx_lab_perawatan_jurusan_jenis');
            });
        }

        if (!Schema::hasTable('laboran_kartu_perawatan')) {
            Schema::create('laboran_kartu_perawatan', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('kode_kartu', 60)->unique();

                $table->unsignedBigInteger('item_id')->nullable()->index();
                $table->string('nama_alat');
                $table->string('kode_alat', 50)->nullable();
                $table->string('ruang_lab_nama', 100)->nullable()->index();
                $table->string('merk_tipe')->nullable();

                $table->date('tanggal_mulai')->nullable();
                $table->unsignedSmallInteger('interval_hari')->default(30);
                $table->date('jadwal_berikutnya')->nullable()->index();
                $table->string('penanggung_jawab_ptk_id', 50)->nullable();
                $table->string('penanggung_jawab_nama')->nullable();

                $table->text('instruksi_perawatan')->nullable();
                $table->json('apd_wajib')->nullable();

                $table->string('status', 30)->default('aktif')->index(); // aktif, nonaktif
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['jurusan_id', 'status'], 'idx_lab_kartu_jurusan_status');
            });
        }
    }

    // -------------------------------------------------------------------------
    // 4. Pemeriksaan / Daftar Ceklist Alat & Bahan
    // -------------------------------------------------------------------------
    private function buatTabelPemeriksaan(): void
    {
        if (!Schema::hasTable('laboran_pemeriksaan')) {
            Schema::create('laboran_pemeriksaan', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('ruang_lab_nama', 100)->nullable()->index();
                $table->string('nomor_pemeriksaan', 60)->unique();

                $table->date('tanggal_pemeriksaan')->nullable()->index();
                $table->string('jenis', 30)->default('harian')->index(); // harian, mingguan, bulanan, semester
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();

                $table->unsignedInteger('total_item')->default(0);
                $table->unsignedInteger('total_sesuai')->default(0);
                $table->unsignedInteger('total_tidak_sesuai')->default(0);
                $table->unsignedInteger('total_tidak_ada')->default(0);

                $table->text('kesimpulan')->nullable();
                $table->text('rekomendasi')->nullable();
                $table->string('status', 30)->default('draf')->index(); // draf, final
                $table->timestamps();

                $table->index(['jurusan_id', 'status'], 'idx_lab_periksa_jurusan_status');
            });
        }

        if (!Schema::hasTable('laboran_pemeriksaan_item')) {
            Schema::create('laboran_pemeriksaan_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pemeriksaan_id')->index();
                $table->unsignedBigInteger('item_id')->nullable()->index();

                $table->string('nama_item');
                $table->string('kode_item', 50)->nullable();
                $table->string('satuan', 30)->nullable();
                $table->text('kondisi_standar')->nullable();

                // sesuai, tidak_sesuai, tidak_ada
                $table->string('hasil', 30)->default('sesuai')->index();
                $table->decimal('jumlah_diperiksa', 12, 2)->nullable();
                $table->text('keterangan')->nullable();
                $table->text('tindak_lanjut')->nullable();
                $table->unsignedSmallInteger('urutan')->default(0);

                $table->timestamps();
            });
        }
    }

    // -------------------------------------------------------------------------
    // 5. Usulan Pengadaan Alat & Bahan Praktik
    // -------------------------------------------------------------------------
    private function buatTabelPengadaan(): void
    {
        if (!Schema::hasTable('laboran_usulan_pengadaan')) {
            Schema::create('laboran_usulan_pengadaan', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('nomor_usulan', 60)->unique();

                $table->string('tahun_ajaran', 20)->nullable()->index();
                $table->string('semester', 10)->nullable();
                $table->date('tanggal_usulan')->nullable()->index();

                $table->string('pemohon_ptk_id', 50)->nullable();
                $table->string('pemohon_nama')->nullable();

                $table->unsignedInteger('total_item')->default(0);
                $table->decimal('total_estimasi_biaya', 18, 2)->default(0);

                $table->string('sumber_dana', 40)->default('BOS'); // BOS, DAK, Komite, Yayasan, Hibah
                $table->string('urgensi', 20)->default('sedang')->index(); // rendah, sedang, tinggi, kritis
                $table->string('status', 30)->default('draf')->index(); // draf, diajukan, diverifikasi, disetujui, ditolak, direalisasi

                $table->text('justifikasi')->nullable();
                $table->text('catatan_verifikasi')->nullable();
                $table->string('diverifikasi_oleh_ptk_id', 50)->nullable();
                $table->timestamp('tanggal_verifikasi')->nullable();

                $table->timestamps();

                $table->index(['jurusan_id', 'status'], 'idx_lab_pengadaan_jurusan_status');
            });
        }

        if (!Schema::hasTable('laboran_usulan_pengadaan_item')) {
            Schema::create('laboran_usulan_pengadaan_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('usulan_id')->index();
                $table->unsignedBigInteger('kebutuhan_id')->nullable()->index();

                $table->string('nama_item');
                $table->string('jenis', 50)->default('alat'); // alat, bahan_habis_pakai
                $table->text('spesifikasi')->nullable();
                $table->decimal('jumlah', 12, 2)->default(1);
                $table->string('satuan', 30)->default('unit');
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->string('prioritas', 20)->default('sedang');
                $table->text('keterangan')->nullable();
                $table->unsignedSmallInteger('urutan')->default(0);

                $table->timestamps();
            });
        }
    }

    // -------------------------------------------------------------------------
    // 6. Berita Acara Kerusakan Alat
    // -------------------------------------------------------------------------
    private function buatTabelKerusakan(): void
    {
        if (Schema::hasTable('laboran_berita_acara_kerusakan')) {
            return;
        }

        Schema::create('laboran_berita_acara_kerusakan', function (Blueprint $table) {
            $table->id();
            $table->string('jurusan_id', 50)->nullable()->index();
            $table->string('ruang_lab_nama', 100)->nullable()->index();
            $table->string('nomor_ba', 60)->unique();

            $table->date('tanggal_kejadian')->nullable()->index();
            $table->date('tanggal_ba')->nullable()->index();

            $table->unsignedBigInteger('item_id')->nullable()->index();
            $table->string('nama_alat');
            $table->string('kode_alat', 50)->nullable();
            $table->decimal('jumlah_rusak', 12, 2)->default(1);
            $table->string('satuan', 30)->default('unit');

            // ringan, sedang, berat, total
            $table->string('tingkat_kerusakan', 30)->default('ringan')->index();
            $table->text('kronologi')->nullable();
            $table->text('penyebab')->nullable();

            $table->string('pelaku_tipe', 30)->default('siswa'); // siswa, guru, tendik, eksternal, tidak_diketahui
            $table->string('pelaku_id', 50)->nullable();
            $table->string('pelaku_nama')->nullable();

            $table->string('saksi_1_nama')->nullable();
            $table->string('saksi_2_nama')->nullable();

            $table->text('tindakan_lanjut')->nullable();
            $table->decimal('estimasi_biaya', 15, 2)->nullable();
            $table->boolean('perlu_usulan_penggantian')->default(false);

            $table->string('status', 30)->default('draf')->index(); // draf, diajukan, diverifikasi, disetujui, selesai
            $table->string('pelapor_ptk_id', 50)->nullable();
            $table->string('pelapor_nama')->nullable();
            $table->string('verifikator_ptk_id', 50)->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->text('catatan_verifikasi')->nullable();

            $table->timestamps();

            $table->index(['jurusan_id', 'status'], 'idx_lab_ba_jurusan_status');
        });
    }

    // -------------------------------------------------------------------------
    // 7. Peminjaman Alat Khusus Jurusan/Laboran
    // -------------------------------------------------------------------------
    private function buatTabelPeminjaman(): void
    {
        if (!Schema::hasTable('laboran_peminjaman')) {
            Schema::create('laboran_peminjaman', function (Blueprint $table) {
                $table->id();
                $table->string('jurusan_id', 50)->nullable()->index();
                $table->string('ruang_lab_nama', 100)->nullable()->index();
                $table->string('nomor_pinjam', 60)->unique();

                $table->string('peminjam_tipe', 30)->default('siswa')->index(); // siswa, guru, tendik, umum
                $table->string('peminjam_id', 50)->nullable();
                $table->string('peminjam_nama');
                $table->string('peminjam_kelas')->nullable();

                $table->string('keperluan');
                $table->date('tanggal_pinjam')->nullable()->index();
                $table->date('tanggal_kembali_rencana')->nullable()->index();
                $table->date('tanggal_kembali_aktual')->nullable()->index();

                $table->unsignedInteger('jumlah_item')->default(0);
                $table->string('kondisi_kembali', 40)->nullable();

                $table->string('status', 30)->default('diajukan')->index(); // diajukan, disetujui, dipinjam, dikembalikan, terlambat, ditolak
                $table->string('disetujui_oleh_ptk_id', 50)->nullable();
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->text('catatan')->nullable();
                $table->text('catatan_pengembalian')->nullable();

                $table->timestamps();

                $table->index(['jurusan_id', 'status'], 'idx_lab_pinjam_jurusan_status');
            });
        }

        if (!Schema::hasTable('laboran_peminjaman_item')) {
            Schema::create('laboran_peminjaman_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('peminjaman_id')->index();
                $table->unsignedBigInteger('item_id')->nullable()->index();

                $table->string('nama_item');
                $table->string('kode_item', 50)->nullable();
                $table->decimal('jumlah', 12, 2)->default(1);
                $table->string('satuan', 30)->default('unit');

                $table->string('kondisi_pinjam', 40)->default('baik');
                $table->string('kondisi_kembali', 40)->nullable();
                $table->text('keterangan')->nullable();

                $table->timestamps();
            });
        }
    }
};
