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
        // 1. Bidang Persuratan: Disposisi Surat
        if (!Schema::hasTable('persuratan_disposisi')) {
            Schema::create('persuratan_disposisi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('persuratan_id')->index();
                $table->string('disposisi_dari')->nullable(); // Kepsek / KTU
                $table->string('disposisi_ke')->nullable(); // Nama / Bidang Tugas / PTK
                $table->string('ptk_id_tujuan')->nullable()->index();
                $table->string('instruksi', 100)->default('Tindak Lanjuti'); // Tindak lanjuti, teliti, hadiri, arsipkan, dll
                $table->text('catatan')->nullable();
                $table->date('tanggal_disposisi')->nullable();
                $table->string('status', 50)->default('menunggu')->index(); // menunggu, diproses, selesai
                $table->timestamps();

                $table->foreign('persuratan_id')->references('id')->on('persuratan')->onDelete('cascade');
            });
        }

        // 2. Bidang Persuratan: Surat Keterangan Peserta Didik (Siswa Aktif, dll)
        if (!Schema::hasTable('surat_keterangan_pd')) {
            Schema::create('surat_keterangan_pd', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_surat')->unique();
                $table->string('peserta_didik_id')->index();
                $table->string('jenis_surat', 50)->default('siswa_aktif')->index(); // siswa_aktif, kelakuan_baik, rekomendasi, bebas_pustaka
                $table->string('keperluan');
                $table->date('tanggal_surat');
                $table->string('penandatangan_ptk_id')->nullable();
                $table->string('penandatangan_nama')->nullable();
                $table->string('penandatangan_jabatan')->default('Kepala Sekolah');
                $table->string('doc_id', 50)->unique();
                $table->string('qrcode_url')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 3. Bidang Kesiswaan: Buku Klaper & Buku Induk
        if (!Schema::hasTable('kesiswaan_buku_klaper')) {
            Schema::create('kesiswaan_buku_klaper', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->unique();
                $table->string('nomor_klaper', 50)->nullable()->index();
                $table->string('nomor_induk', 50)->nullable()->index();
                $table->string('huruf_abjad', 5)->nullable()->index();
                $table->integer('tahun_masuk')->nullable()->index();
                $table->string('status_klaper', 50)->default('aktif')->index(); // aktif, lulus, mutasi_keluar, do
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 4. Bidang Kesiswaan: Mutasi Siswa (Masuk, Keluar, DO)
        if (!Schema::hasTable('kesiswaan_mutasi')) {
            Schema::create('kesiswaan_mutasi', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->index();
                $table->string('jenis_mutasi', 50)->index(); // masuk, keluar, do, meninggal
                $table->date('tanggal_mutasi');
                $table->string('alasan')->nullable();
                $table->string('sekolah_tujuan_asal')->nullable();
                $table->string('nomor_surat_mutasi')->nullable();
                $table->string('file_berkas')->nullable();
                $table->text('catatan')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 5. Bidang Kesiswaan: Verifikasi Kelengkapan Berkas Fisik Siswa Baru
        if (!Schema::hasTable('kesiswaan_berkas_verifikasi')) {
            Schema::create('kesiswaan_berkas_verifikasi', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->unique();
                $table->boolean('akta_kelahiran')->default(false);
                $table->boolean('kartu_keluarga')->default(false);
                $table->boolean('ijazah_smp')->default(false);
                $table->boolean('ktp_orang_tua')->default(false);
                $table->boolean('kip_pip')->default(false);
                $table->text('catatan_verifikasi')->nullable();
                $table->string('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        // 6. Bidang Kepegawaian GTK: Arsip Berkas Digital GTK
        if (!Schema::hasTable('gtk_berkas')) {
            Schema::create('gtk_berkas', function (Blueprint $table) {
                $table->id();
                $table->string('ptk_id')->index();
                $table->string('jenis_dokumen', 50)->index(); // sk_pengangkatan, sk_pembagian_tugas, kgb, ijazah, sertifikat, lainnya
                $table->string('judul_dokumen');
                $table->string('nomor_dokumen')->nullable();
                $table->date('tanggal_dokumen')->nullable();
                $table->date('tmt')->nullable();
                $table->date('tst')->nullable();
                $table->string('file_path')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 7. Bidang Kepegawaian GTK: Tracker Kenaikan Gaji Berkala (KGB)
        if (!Schema::hasTable('gtk_kgb_tracker')) {
            Schema::create('gtk_kgb_tracker', function (Blueprint $table) {
                $table->id();
                $table->string('ptk_id')->index();
                $table->string('nomor_sk_terakhir')->nullable();
                $table->date('tgl_sk_terakhir')->nullable();
                $table->date('tmt_lama')->nullable();
                $table->date('tmt_baru_target')->nullable()->index();
                $table->decimal('gaji_pokok_lama', 15, 2)->nullable();
                $table->decimal('gaji_pokok_baru', 15, 2)->nullable();
                $table->integer('mkg_tahun')->default(0);
                $table->integer('mkg_bulan')->default(0);
                $table->string('status_usulan', 50)->default('belum_waktunya')->index(); // belum_waktunya, siap_diajukan, diproses, terbit_sk
                $table->string('nomor_sk_baru')->nullable();
                $table->string('file_sk_baru')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        // 8. Bidang Kepegawaian GTK: Manajemen Cuti, Izin & Tugas Dinas Luar
        if (!Schema::hasTable('gtk_cuti_izin')) {
            Schema::create('gtk_cuti_izin', function (Blueprint $table) {
                $table->id();
                $table->string('ptk_id')->index();
                $table->string('jenis', 50)->index(); // cuti_tahunan, cuti_melahirkan, cuti_alasan_penting, izin_sakit, dinas_luar
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->integer('jumlah_hari')->default(1);
                $table->string('keperluan');
                $table->string('file_pendukung')->nullable();
                $table->string('status', 50)->default('diajukan')->index(); // diajukan, disetujui_ktu, disetujui_kepsek, ditolak
                $table->text('catatan_pimpinan')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gtk_cuti_izin');
        Schema::dropIfExists('gtk_kgb_tracker');
        Schema::dropIfExists('gtk_berkas');
        Schema::dropIfExists('kesiswaan_berkas_verifikasi');
        Schema::dropIfExists('kesiswaan_mutasi');
        Schema::dropIfExists('kesiswaan_buku_klaper');
        Schema::dropIfExists('surat_keterangan_pd');
        Schema::dropIfExists('persuratan_disposisi');
    }
};
