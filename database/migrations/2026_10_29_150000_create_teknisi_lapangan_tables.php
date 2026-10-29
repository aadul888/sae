<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelengkapan pencatatan TEKNISI LAPANGAN (staf sarana prasarana teknis).
 *
 * Menutup celah:
 *  - Kepemilikan data per akun petugas (user_id) → tiap teknisi hanya melihat
 *    dan mengubah catatannya sendiri; koordinator (Kepala TAS / Waka Sarpras /
 *    admin) melihat seluruh data.
 *  - Master area/zona kebersihan (menggantikan teks bebas).
 *  - Log harian generik: inspeksi utilitas, APAR/K3, pest control, higiene
 *    kantin, dan pemakaian bahan kebersihan.
 *  - Usulan pengadaan, tindak lanjut supervisi, mutasi lokasi aset,
 *    dan peminjaman ruang.
 *  - Relasi aset/ruang pada work order & jurnal perbaikan (riwayat per aset).
 */
return new class extends Migration
{
    /** Tabel teknisi yang perlu penanda akun pembuat. */
    private const OWNED_TABLES = [
        'teknisi_area_tugas',
        'teknisi_kebersihan_ruang',
        'teknisi_kebersihan_rapat',
        'teknisi_sarana_rapat',
        'teknisi_jurnal_perbaikan',
        'teknisi_supervisi',
        'teknisi_work_order',
        'teknisi_pemeliharaan',
    ];

    public function up(): void
    {
        // -----------------------------------------------------------------
        // 1. Kepemilikan data per akun petugas
        // -----------------------------------------------------------------
        foreach (self::OWNED_TABLES as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('user_id', 50)->nullable()->index();
                });
            }
        }

        // -----------------------------------------------------------------
        // 2. Relasi aset & ruang pada work order + jurnal perbaikan
        // -----------------------------------------------------------------
        foreach (['teknisi_work_order', 'teknisi_jurnal_perbaikan'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'aset_id')) {
                    $table->unsignedBigInteger('aset_id')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'ruang_id')) {
                    $table->unsignedBigInteger('ruang_id')->nullable()->index();
                }
            });
        }

        // -----------------------------------------------------------------
        // 3. Master area / zona kebersihan
        // -----------------------------------------------------------------
        if (!Schema::hasTable('teknisi_area_master')) {
            Schema::create('teknisi_area_master', function (Blueprint $table) {
                $table->id();
                $table->string('kode_area', 50)->unique();
                $table->string('nama_area', 150)->index();
                $table->string('zona', 80)->nullable()->index();
                $table->string('gedung', 100)->nullable();
                $table->string('lantai', 20)->nullable();
                $table->unsignedBigInteger('ruang_id')->nullable()->index();
                // administrasi, perkantoran, fasilitas_umum, teras_halaman, lapangan, lainnya
                $table->string('kategori', 40)->default('perkantoran')->index();
                // harian, mingguan, bulanan, insidental
                $table->string('frekuensi', 30)->default('harian')->index();
                $table->string('petugas_ptk_id', 50)->nullable()->index();
                $table->string('petugas_nama')->nullable();
                $table->string('status', 20)->default('aktif')->index();
                $table->text('keterangan')->nullable();
                $table->string('user_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        // -----------------------------------------------------------------
        // 4. Log harian generik (5 sub-modul lewat kolom `modul`)
        // -----------------------------------------------------------------
        if (!Schema::hasTable('teknisi_log_harian')) {
            Schema::create('teknisi_log_harian', function (Blueprint $table) {
                $table->id();
                // inspeksi-utilitas, apar-k3, pest-control, higiene-kantin, pemakaian-bahan
                $table->string('modul', 40)->index();
                $table->string('nomor', 60)->unique();

                $table->date('tanggal')->index();
                $table->string('lokasi', 150)->nullable()->index();
                $table->string('kategori', 60)->nullable()->index();
                $table->string('subjek', 200)->nullable();       // nama alat / bahan / kegiatan
                $table->unsignedBigInteger('persediaan_id')->nullable()->index();
                $table->unsignedBigInteger('area_id')->nullable()->index();
                $table->integer('jumlah')->nullable();
                $table->string('satuan', 30)->nullable();
                $table->string('kondisi', 40)->nullable()->index(); // baik, rusak_ringan, rusak_berat, habis

                $table->text('uraian')->nullable();
                $table->text('temuan')->nullable();
                $table->text('tindak_lanjut')->nullable();
                $table->text('hasil')->nullable();
                $table->decimal('biaya', 12, 2)->default(0);

                $table->string('foto')->nullable();
                $table->text('dokumentasi_paths')->nullable();
                $table->date('tanggal_berikutnya')->nullable()->index();

                $table->string('petugas_ptk_id', 50)->nullable()->index();
                $table->string('petugas_nama')->nullable();
                $table->string('user_id', 50)->nullable()->index();
                $table->string('status', 30)->default('selesai')->index(); // draf, selesai, terverifikasi

                $table->timestamps();

                $table->index(['modul', 'tanggal'], 'idx_tek_log_modul_tgl');
            });
        }

        // -----------------------------------------------------------------
        // 5. Usulan pengadaan sarana prasarana
        // -----------------------------------------------------------------
        if (!Schema::hasTable('teknisi_usulan_pengadaan')) {
            Schema::create('teknisi_usulan_pengadaan', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_usulan', 60)->unique();
                $table->date('tanggal')->index();
                $table->string('unit_pemohon', 150)->index();
                $table->string('nama_barang', 200);
                $table->string('kategori', 60)->nullable()->index();
                $table->integer('jumlah')->default(1);
                $table->string('satuan', 30)->default('unit');
                $table->decimal('estimasi_biaya', 12, 2)->default(0);
                $table->decimal('harga_satuan', 12, 2)->default(0);
                $table->string('urgensi', 20)->default('normal')->index(); // rendah, normal, mendesak
                $table->text('alasan')->nullable();
                $table->text('spesifikasi')->nullable();
                $table->string('status', 30)->default('draf')->index(); // draf, diajukan, disetujui, ditolak, terealisasi
                $table->text('catatan_verifikasi')->nullable();
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('user_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        // -----------------------------------------------------------------
        // 6. Tindak lanjut hasil supervisi
        // -----------------------------------------------------------------
        if (!Schema::hasTable('teknisi_tindak_lanjut')) {
            Schema::create('teknisi_tindak_lanjut', function (Blueprint $table) {
                $table->id();
                $table->string('nomor', 60)->unique();
                $table->unsignedBigInteger('supervisi_id')->nullable()->index();
                $table->string('supervisi_nomor', 60)->nullable()->index();
                $table->date('tanggal')->index();
                $table->string('teknisi_nama', 150)->nullable()->index();
                $table->text('temuan');
                $table->text('rencana_aksi');
                $table->string('penanggung_jawab', 150)->nullable();
                $table->date('target_selesai')->nullable()->index();
                $table->date('tanggal_realisasi')->nullable();
                $table->text('bukti_tindak_lanjut')->nullable();
                $table->string('status', 30)->default('terbuka')->index(); // terbuka, proses, selesai
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('user_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        // -----------------------------------------------------------------
        // 7. Mutasi lokasi aset (riwayat perpindahan barang antar ruang)
        // -----------------------------------------------------------------
        if (!Schema::hasTable('teknisi_aset_mutasi')) {
            Schema::create('teknisi_aset_mutasi', function (Blueprint $table) {
                $table->id();
                $table->string('nomor', 60)->unique();
                $table->date('tanggal')->index();
                $table->unsignedBigInteger('aset_id')->nullable()->index();
                $table->string('aset_nama', 200)->nullable();
                $table->string('kode_aset', 60)->nullable()->index();
                $table->unsignedBigInteger('dari_ruang_id')->nullable();
                $table->string('dari_ruang', 150)->nullable();
                $table->unsignedBigInteger('ke_ruang_id')->nullable();
                $table->string('ke_ruang', 150)->nullable()->index();
                $table->string('kondisi', 40)->nullable();
                $table->text('alasan')->nullable();
                $table->string('berita_acara')->nullable();
                $table->string('status', 30)->default('selesai')->index(); // draf, selesai
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('user_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        // -----------------------------------------------------------------
        // 8. Peminjaman / penggunaan ruang & fasilitas
        // -----------------------------------------------------------------
        if (!Schema::hasTable('sarpras_peminjaman_ruang')) {
            Schema::create('sarpras_peminjaman_ruang', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_pinjam', 60)->unique();
                $table->unsignedBigInteger('ruang_id')->nullable()->index();
                $table->string('ruang_nama', 150)->index();
                $table->string('peminjam_nama');
                $table->string('peminjam_tipe', 30)->default('internal'); // internal, eksternal
                $table->string('instansi', 150)->nullable();
                $table->string('kontak', 50)->nullable();
                $table->string('keperluan', 255);
                $table->date('tanggal')->index();
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->unsignedInteger('jumlah_peserta')->nullable();
                $table->text('sarana_diminta')->nullable();
                $table->string('kondisi_sesudah', 40)->nullable();
                $table->string('status', 30)->default('diajukan')->index(); // diajukan, disetujui, ditolak, selesai
                $table->text('catatan')->nullable();
                $table->string('petugas_ptk_id', 50)->nullable();
                $table->string('petugas_nama')->nullable();
                $table->string('user_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sarpras_peminjaman_ruang');
        Schema::dropIfExists('teknisi_aset_mutasi');
        Schema::dropIfExists('teknisi_tindak_lanjut');
        Schema::dropIfExists('teknisi_usulan_pengadaan');
        Schema::dropIfExists('teknisi_log_harian');
        Schema::dropIfExists('teknisi_area_master');

        foreach (['teknisi_work_order', 'teknisi_jurnal_perbaikan'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    foreach (['aset_id', 'ruang_id'] as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }

        foreach (self::OWNED_TABLES as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
            }
        }
    }
};
