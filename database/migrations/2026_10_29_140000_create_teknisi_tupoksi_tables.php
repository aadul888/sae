<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelengkapan TUPOKSI Teknisi & IT — menutup kesenjangan bukti fisik supervisi.
 *
 * Indikator yang dilayani (selaras lembar observasi supervisi teknisi):
 *  1. Administrasi & jadwal pembagian area tugas kebersihan + laporan siswa tidak disiplin.
 *  2. Kebersihan lingkungan & ruang kerja (kepala sekolah, wakasek, operator,
 *     bendahara, ruang rapat, dapur, WC, teras, TU, guru, humas/hubin,
 *     jamban, koperasi, lapangan upacara, gor, aula balarea, sarana prasarana,
 *     jamban siswa).
 *  3. Kebersihan lingkungan ruang rapat (teras, halaman, ruang rapat).
 *  4. Laporan sarana kegiatan rapat (dokumen rekapitulasi & kelengkapan).
 *  5. Pengarsipan & laporan perbaikan sarana prasarana (jurnal perbaikan).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 1. ADMINISTRASI & JADWAL PEMBAGIAN AREA TUGAS KEBERSIHAN
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('teknisi_area_tugas')) {
            Schema::create('teknisi_area_tugas', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_jadwal', 60)->unique();

                $table->string('area', 150)->index();          // Ruang Kepala Sekolah, WC, dll
                $table->string('zona', 80)->nullable()->index(); // gedung/blok pengelompokan area

                // harian, mingguan, bulanan, insidental
                $table->string('periode_jadwal', 30)->default('harian')->index();

                $table->string('petugas_ptk_id', 50)->nullable()->index();
                $table->string('petugas_nama');                // nama petugas kebersihan penanggung jawab
                $table->string('shift', 20)->default('pagi');  // pagi, siang, sore

                $table->date('tanggal_mulai')->nullable()->index();
                $table->date('tanggal_selesai')->nullable();

                // tukang_kebersihan, pengolahan_sampah, laporan_siswa, lainnya
                $table->string('jenis_tugas', 40)->default('tukang_kebersihan')->index();

                // Untuk jenis laporan_siswa: laporan siswa tidak disiplin dari Tim Keamanan
                $table->text('laporan_siswa')->nullable();
                $table->string('sumber_laporan', 100)->nullable(); // Tim Keamanan

                $table->text('uraian')->nullable();
                $table->text('catatan')->nullable();

                // draf, aktif, selesai
                $table->string('status', 30)->default('aktif')->index();

                $table->timestamps();

                $table->index(['area', 'status'], 'idx_tek_area_status');
            });
        }

        // ---------------------------------------------------------------------
        // 2. KEBERSIHAN LINGKUNGAN & RUANG KERJA
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('teknisi_kebersihan_ruang')) {
            Schema::create('teknisi_kebersihan_ruang', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_kegiatan', 60)->unique();

                $table->date('tanggal_kegiatan')->index();
                $table->string('ruang_nama', 150)->index();       // Ruang Kepala Sekolah, Ruang TU, dll

                // administrasi, perkantoran, fasilitas_umum, teras_halaman, jamban, lapangan, lainnya
                $table->string('kategori_ruang', 40)->default('perkantoran')->index();

                // sangat_bersih, bersih, cukup, kotor, perlu_tindakan
                $table->string('kondisi', 40)->default('bersih')->index();

                $table->text('uraian_kegiatan')->nullable();
                $table->text('peralatan_digunakan')->nullable();
                $table->text('temuan')->nullable();
                $table->text('tindak_lanjut')->nullable();

                $table->string('foto_sebelum')->nullable();
                $table->string('foto_sesudah')->nullable();
                $table->text('dokumentasi_paths')->nullable()->comment('JSON daftar path foto dokumentasi');

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('status', 30)->default('selesai')->index(); // draf, selesai, terverifikasi

                $table->timestamps();

                $table->index(['tanggal_kegiatan', 'kategori_ruang'], 'idx_tek_bersih_tgl_kat');
            });
        }

        // ---------------------------------------------------------------------
        // 3. KEBERSIHAN LINGKUNGAN RUANG RAPAT
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('teknisi_kebersihan_rapat')) {
            Schema::create('teknisi_kebersihan_rapat', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_kegiatan', 60)->unique();

                $table->date('tanggal_kegiatan')->index();
                $table->string('lokasi', 150)->default('Ruang Rapat'); // teras, halaman, ruang rapat
                $table->string('nama_rapat', 200)->nullable();         // kegiatan rapat yang dilayani

                // teras, halaman, ruang_rapat
                $table->string('area', 40)->default('ruang_rapat')->index();

                // sebelum_acara, saat_acara, sesudah_acara
                $table->string('tahap', 30)->default('sebelum_acara')->index();

                // sangat_bersih, bersih, cukup, kotor, perlu_tindakan
                $table->string('kondisi', 40)->default('bersih')->index();

                $table->text('uraian_kegiatan')->nullable();
                $table->text('peralatan_digunakan')->nullable();
                $table->text('temuan')->nullable();
                $table->text('tindak_lanjut')->nullable();

                $table->string('foto_sebelum')->nullable();
                $table->string('foto_sesudah')->nullable();
                $table->text('dokumentasi_paths')->nullable()->comment('JSON daftar path foto dokumentasi');

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('status', 30)->default('selesai')->index(); // draf, selesai

                $table->timestamps();

                $table->index(['tanggal_kegiatan', 'area'], 'idx_tek_rapat_tgl_area');
            });
        }

        // ---------------------------------------------------------------------
        // 4. LAPORAN SARANA KEGIATAN RAPAT
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('teknisi_sarana_rapat')) {
            Schema::create('teknisi_sarana_rapat', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_laporan', 60)->unique();

                $table->date('tanggal_rapat')->index();
                $table->string('nama_rapat', 200);
                $table->string('ruang_nama', 150)->nullable()->index();

                // peserta dan kebutuhan
                $table->unsignedInteger('jumlah_peserta')->nullable();

                // daftar sarana yang disiapkan (JSON: [{nama,jumlah,kondisi}])
                $table->text('daftar_sarana')->nullable()->comment('JSON daftar sarana disiapkan');

                // lengkap, kurang, tidak_lengkap
                $table->string('kelengkapan_sarana', 30)->default('lengkap')->index();

                $table->text('catatan_kebutuhan')->nullable();
                $table->text('catatan')->nullable();

                $table->string('file_path')->nullable();
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('status', 30)->default('selesai')->index(); // draf, selesai, terverifikasi

                $table->timestamps();

                $table->index(['tanggal_rapat', 'kelengkapan_sarana'], 'idx_tek_sarana_tgl_lengkap');
            });
        }

        // ---------------------------------------------------------------------
        // 5. PENGARSIPAN & LAPORAN PERBAIKAN SARANA PRASARANA (JURNAL)
        // ---------------------------------------------------------------------
        if (!Schema::hasTable('teknisi_jurnal_perbaikan')) {
            Schema::create('teknisi_jurnal_perbaikan', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_jurnal', 60)->unique();

                $table->date('tanggal')->index();
                $table->string('lokasi_unit', 150)->index();
                $table->string('jenis_pekerjaan', 60)->index(); // perbaikan, penggantian, pengecekan, kebutuhan

                $table->string('kategori_sarana', 50)->nullable()->index();
                // listrik, air_plumbing, bangunan, furnitur, jaringan_it, sanitasi, kendaraan, lainnya

                $table->text('uraian_kegiatan');
                $table->text('hasil')->nullable();
                $table->text('kebutuhan_sarana')->nullable();  // usulan/pengadaan yang dibutuhkan
                $table->text('kendala')->nullable();
                $table->text('tindak_lanjut')->nullable();

                $table->decimal('estimasi_biaya', 12, 2)->default(0);
                $table->decimal('realisasi_biaya', 12, 2)->nullable();

                $table->string('foto_sebelum')->nullable();
                $table->string('foto_sesudah')->nullable();
                $table->text('dokumentasi_paths')->nullable()->comment('JSON daftar path foto dokumentasi');

                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();

                // draf, selesai, terverifikasi
                $table->string('status', 30)->default('selesai')->index();

                $table->timestamps();

                $table->index(['tanggal', 'jenis_pekerjaan'], 'idx_tek_jurnal_tgl_jenis');
            });
        }

        // ---------------------------------------------------------------------
        // PENILAIAN SUPERVISI TEKNISI — modul dihapus. Tabel dibuang agar tidak
        // menyisakan skema usang (menggantikan blok CREATE lama).
        // ---------------------------------------------------------------------
        Schema::dropIfExists('teknisi_supervisi_item');
        Schema::dropIfExists('teknisi_supervisi');
    }

    public function down(): void
    {
        // Modul supervisi dihapus dan tidak dikembalikan oleh down().
        Schema::dropIfExists('teknisi_jurnal_perbaikan');
        Schema::dropIfExists('teknisi_sarana_rapat');
        Schema::dropIfExists('teknisi_kebersihan_rapat');
        Schema::dropIfExists('teknisi_kebersihan_ruang');
        Schema::dropIfExists('teknisi_area_tugas');
    }
};
