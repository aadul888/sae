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
        // 1. Usulan Perubahan Data Siswa
        if (!Schema::hasTable('siswa_usulan_perubahan')) {
            Schema::create('siswa_usulan_perubahan', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->index();
                $table->string('kolom_perubahan', 100); // nama, nisn, nik, tempat_lahir, tanggal_lahir, nama_ibu, dll
                $table->text('nilai_lama')->nullable();
                $table->text('nilai_baru');
                $table->string('alasan')->nullable();
                $table->string('berkas_bukti')->nullable();
                $table->string('status', 50)->default('menunggu')->index(); // menunggu, disetujui, ditolak
                $table->text('catatan_verifikasi')->nullable();
                $table->string('created_by')->nullable();
                $table->string('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Administrasi Kelulusan Siswa & SKL
        if (!Schema::hasTable('kesiswaan_kelulusan')) {
            Schema::create('kesiswaan_kelulusan', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->unique();
                $table->string('tahun_ajaran', 20)->index();
                $table->string('nomor_peserta_ujian', 50)->nullable()->index();
                $table->string('nomor_ijazah', 50)->nullable()->index();
                $table->string('nomor_skl', 50)->nullable()->index();
                $table->string('status_kelulusan', 50)->default('lulus')->index(); // lulus, tidak_lulus, ditunda
                $table->date('tanggal_lulus')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('file_skl')->nullable();
                $table->string('doc_id', 50)->nullable()->unique();
                $table->timestamps();
            });
        }

        // 3. Kedisiplinan: Tata Tertib & Aturan Poin
        if (!Schema::hasTable('kedisiplinan_tata_tertib')) {
            Schema::create('kedisiplinan_tata_tertib', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('kategori', 50)->index(); // kerapian, kehadiran, perilaku, larangan_berat
                $table->string('nama_aturan');
                $table->integer('bobot_poin')->default(5);
                $table->string('sanksi_rekomendasi')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        // 4. Kedisiplinan: Pencatatan Pelanggaran Poin Siswa
        if (!Schema::hasTable('kedisiplinan_pelanggaran')) {
            Schema::create('kedisiplinan_pelanggaran', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->index();
                $table->unsignedBigInteger('tata_tertib_id')->nullable()->index();
                $table->date('tanggal_kejadian')->index();
                $table->string('tempat_kejadian')->nullable();
                $table->integer('poin')->default(5);
                $table->text('keterangan')->nullable();
                $table->string('pelapor_ptk_id')->nullable()->index();
                $table->string('pelapor_nama')->nullable();
                $table->string('foto_bukti')->nullable();
                $table->string('status_tindak_lanjut', 50)->default('pending')->index(); // pending, proses, selesai
                $table->timestamps();

                $table->foreign('tata_tertib_id')->references('id')->on('kedisiplinan_tata_tertib')->onDelete('set null');
            });
        }

        // 5. Kedisiplinan: Sesi Pembinaan & Konseling (BK / Wali Kelas)
        if (!Schema::hasTable('kedisiplinan_pembinaan')) {
            Schema::create('kedisiplinan_pembinaan', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->index();
                $table->unsignedBigInteger('pelanggaran_id')->nullable()->index();
                $table->date('tanggal_pembinaan')->index();
                $table->string('guru_bk_ptk_id')->nullable()->index();
                $table->string('wali_kelas_ptk_id')->nullable()->index();
                $table->string('bentuk_pembinaan')->default('Konseling Individual'); // Konseling Individual, Peringatan Lisan, Surat Pernyataan
                $table->text('hasil_pembinaan')->nullable();
                $table->string('status', 50)->default('proses')->index(); // proses, selesai
                $table->string('surat_perjanjian_file')->nullable();
                $table->timestamps();

                $table->foreign('pelanggaran_id')->references('id')->on('kedisiplinan_pelanggaran')->onDelete('set null');
            });
        }

        // 6. Kedisiplinan: Surat Pemanggilan Orang Tua / Wali
        if (!Schema::hasTable('kedisiplinan_pemanggilan_wali')) {
            Schema::create('kedisiplinan_pemanggilan_wali', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->index();
                $table->string('nomor_surat')->unique();
                $table->date('tanggal_surat');
                $table->date('tanggal_hadir');
                $table->time('jam_hadir')->default('08:00:00');
                $table->string('tempat')->default('Ruang Bimbingan Konseling / Kesiswaan');
                $table->string('alasan');
                $table->string('menghadap_ke')->default('Guru BK / Waka Kesiswaan');
                $table->string('status', 50)->default('diterbitkan')->index(); // diterbitkan, hadir, tidak_hadir
                $table->text('catatan_hasil')->nullable();
                $table->string('doc_id', 50)->nullable()->unique();
                $table->timestamps();
            });
        }

        // 7. Kegiatan Siswa: Organisasi Kesiswaan (OSIS, MPK, Pramuka, PMR, Rohis, dll)
        if (!Schema::hasTable('kegiatan_organisasi')) {
            Schema::create('kegiatan_organisasi', function (Blueprint $table) {
                $table->id();
                $table->string('jenis', 50)->index(); // osis, mpk, pramuka, pmr, rohis, paskibra, lainnya
                $table->string('nama_organisasi');
                $table->string('masa_bakti', 30); // contoh: 2026/2027
                $table->string('ketua_peserta_didik_id')->nullable()->index();
                $table->string('pembina_ptk_id')->nullable()->index();
                $table->text('visi_misi')->nullable();
                $table->string('logo_path')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kegiatan_organisasi_anggota')) {
            Schema::create('kegiatan_organisasi_anggota', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisasi_id')->index();
                $table->string('peserta_didik_id')->index();
                $table->string('jabatan', 100)->default('Anggota'); // Ketua, Wakil, Sekretaris, Bendahara, Sekbid, Anggota
                $table->string('sk_pengangkatan')->nullable();
                $table->timestamps();

                $table->foreign('organisasi_id')->references('id')->on('kegiatan_organisasi')->onDelete('cascade');
            });
        }

        // 8. Kegiatan Siswa: Ekstrakurikuler
        if (!Schema::hasTable('kegiatan_ekskul')) {
            Schema::create('kegiatan_ekskul', function (Blueprint $table) {
                $table->id();
                $table->string('nama_ekskul');
                $table->string('kategori', 50)->index(); // olahraga, seni, keagamaan, bela_diri, sains, teknologi
                $table->string('pembina_ptk_id')->nullable()->index();
                $table->string('pelatih_nama')->nullable();
                $table->string('jadwal_hari', 50)->nullable(); // Senin, Selasa, dll
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->string('tempat')->nullable();
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kegiatan_ekskul_anggota')) {
            Schema::create('kegiatan_ekskul_anggota', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ekskul_id')->index();
                $table->string('peserta_didik_id')->index();
                $table->string('nomor_anggota', 50)->nullable();
                $table->string('status', 50)->default('aktif')->index(); // aktif, nonaktif
                $table->timestamps();

                $table->foreign('ekskul_id')->references('id')->on('kegiatan_ekskul')->onDelete('cascade');
            });
        }

        // 9. Kegiatan Siswa: Agenda Kegiatan Kesiswaan
        if (!Schema::hasTable('kegiatan_agenda')) {
            Schema::create('kegiatan_agenda', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisasi_id')->nullable()->index();
                $table->unsignedBigInteger('ekskul_id')->nullable()->index();
                $table->string('judul_kegiatan');
                $table->string('jenis_kegiatan', 50)->default('internal'); // internal, eksternal, lomba, upacara, bakti_sosial
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai')->nullable();
                $table->string('tempat')->nullable();
                $table->string('penanggung_jawab')->nullable();
                $table->decimal('anggaran', 15, 2)->default(0);
                $table->string('status', 50)->default('rencana')->index(); // rencana, berlangsung, selesai, dibatalkan
                $table->string('laporan_kegiatan_file')->nullable();
                $table->timestamps();
            });
        }

        // 10. Prestasi Siswa: Akademik & Nonakademik
        if (!Schema::hasTable('kesiswaan_prestasi')) {
            Schema::create('kesiswaan_prestasi', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id')->index();
                $table->string('kategori', 50)->index(); // akademik, nonakademik
                $table->string('bidang_lomba'); // misal: LKS Web Tech, OSN Matematika, Futsal, Tari Tradisional
                $table->string('nama_event'); // misal: LKS SMK Tingkat Provinsi Jawa Barat 2026
                $table->string('penyelenggara')->nullable(); // Kemendikbudristek, Disdik, Universitas, dll
                $table->string('tingkat', 50)->index(); // sekolah, kecamatan, kabupaten_kota, provinsi, nasional, internasional
                $table->string('peringkat', 50)->index(); // juara_1, juara_2, juara_3, harapan_1, harapan_2, harapan_3, finalis, peserta
                $table->date('tanggal_prestasi')->index();
                $table->string('pembimbing_ptk_id')->nullable()->index();
                $table->string('pembimbing_nama')->nullable();
                $table->string('sertifikat_file')->nullable();
                $table->string('foto_kegiatan')->nullable();
                $table->string('nomor_piagam')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kesiswaan_prestasi');
        Schema::dropIfExists('kegiatan_agenda');
        Schema::dropIfExists('kegiatan_ekskul_anggota');
        Schema::dropIfExists('kegiatan_ekskul');
        Schema::dropIfExists('kegiatan_organisasi_anggota');
        Schema::dropIfExists('kegiatan_organisasi');
        Schema::dropIfExists('kedisiplinan_pemanggilan_wali');
        Schema::dropIfExists('kedisiplinan_pembinaan');
        Schema::dropIfExists('kedisiplinan_pelanggaran');
        Schema::dropIfExists('kedisiplinan_tata_tertib');
        Schema::dropIfExists('kesiswaan_kelulusan');
        Schema::dropIfExists('siswa_usulan_perubahan');
    }
};
