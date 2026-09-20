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
                $table->string('format_nomor_surat_keluar')->default('{nomor}/{kode_indeks}-{sekolah_kode}');
                $table->string('format_nomor_surat_keterangan')->default('{nomor}/{kode_indeks}-{sekolah_kode}');
                $table->integer('nomor_terakhir_surat_keluar')->default(0);
                $table->integer('nomor_terakhir_surat_keterangan')->default(0);
                $table->integer('tahun_terakhir')->default(2026);
                $table->string('sekolah_kode')->default('SMKN1PGL');
                $table->boolean('auto_subfolder')->default(true);
                $table->timestamps();
            });

            // Insert default row
            DB::table('persuratan_settings')->insert([
                'hdd_path' => storage_path('app/arsip_persuratan'),
                'is_hdd_active' => true,
                'format_nomor_surat_keluar' => '{nomor}/{kode_indeks}-{sekolah_kode}',
                'format_nomor_surat_keterangan' => '{nomor}/{kode_indeks}-{sekolah_kode}',
                'nomor_terakhir_surat_keluar' => 0,
                'nomor_terakhir_surat_keterangan' => 0,
                'tahun_terakhir' => (int) date('Y'),
                'sekolah_kode' => 'SMKN1PGL',
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
                $table->string('kategori', 50)->default('Umum'); // Kepegawaian, Keuangan, Kesiswaan, Pendidikan, Sarpras, Tata Usaha
                $table->text('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Data Klasifikasi Baku Kearsipan Sekolah (62 Indeks Resmi)
            $defaultIndeks = [
                ['kode' => 'KPG.01', 'judul' => 'Formasi Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Formasi Pegawai (usulan dari unit kerja/SKPD)'],
                ['kode' => 'KPG.01.01', 'judul' => 'Analisa Jabatan', 'kategori' => 'Kepegawaian', 'keterangan' => 'Analisa Jabatan'],
                ['kode' => 'KPG.01.02', 'judul' => 'Beban Kerja', 'kategori' => 'Kepegawaian', 'keterangan' => 'Beban Kerja'],
                ['kode' => 'KPG.02', 'judul' => 'Pengadaan Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Pengadaan Pegawai'],
                ['kode' => 'KPG.02.04.02', 'judul' => 'Ijazah', 'kategori' => 'Kepegawaian', 'keterangan' => 'Ijazah'],
                ['kode' => 'KPG.03', 'judul' => 'Pembinaan Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Pembinaan Pegawai'],
                ['kode' => 'KPG.03.01.01', 'judul' => 'SK/Surat Izin', 'kategori' => 'Kepegawaian', 'keterangan' => 'SK/Surat Izin'],
                ['kode' => 'KPG.03.03', 'judul' => 'Daftar Usul Penetapan Angka Kredit', 'kategori' => 'Kepegawaian', 'keterangan' => 'Daftar Usul Penetapan Angka Kredit'],
                ['kode' => 'KPG.03.04', 'judul' => 'Disiplin Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Disiplin Pegawai'],
                ['kode' => 'KPG.03.06', 'judul' => 'Penghargaan dan Tanda Jasa', 'kategori' => 'Kepegawaian', 'keterangan' => 'Penghargaan dan Tanda Jasa'],
                ['kode' => 'KPG.04', 'judul' => 'Mutasi Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Mutasi Pegawai'],
                ['kode' => 'KPG.04.01', 'judul' => 'Alih Status, Pindah Instansi, Mutasi', 'kategori' => 'Kepegawaian', 'keterangan' => 'Alih Status, Pindah Instansi, Pindah Wilayah Kerja, Mutasi'],
                ['kode' => 'KPG.05.01', 'judul' => 'Surat Izin Pernikahan/Perceraian', 'kategori' => 'Kepegawaian', 'keterangan' => 'Surat Izin Pernikahan/Perceraian'],
                ['kode' => 'KPG.05.03', 'judul' => 'Surat Penolakan Izin Pernikahan/Perceraian', 'kategori' => 'Kepegawaian', 'keterangan' => 'Surat Penolakan Izin Pernikahan/Perceraian'],
                ['kode' => 'KPG.06', 'judul' => 'Usulan Kenaikan Pangkat/Golongan/Jabatan', 'kategori' => 'Kepegawaian', 'keterangan' => 'Usulan Kenaikan Pangkat/Golongan/Jabatan'],
                ['kode' => 'KPG.11', 'judul' => 'Administrasi Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Administrasi Pegawai'],
                ['kode' => 'KPG.11.01', 'judul' => 'Surat Perintah Dinas/Surat Tugas', 'kategori' => 'Kepegawaian', 'keterangan' => 'Surat Perintah Dinas/Surat Tugas'],
                ['kode' => 'KPG.11.02', 'judul' => 'Cuti Besar', 'kategori' => 'Kepegawaian', 'keterangan' => 'Cuti Besar'],
                ['kode' => 'KPG.11.03', 'judul' => 'Cuti Sakit, Cuti Bersalin, Cuti Tahunan', 'kategori' => 'Kepegawaian', 'keterangan' => 'Cuti Sakit, Cuti Bersalin, Cuti Tahunan'],
                ['kode' => 'KPG.11.04', 'judul' => 'Cuti Alasan Penting', 'kategori' => 'Kepegawaian', 'keterangan' => 'Cuti Alasan Penting'],
                ['kode' => 'KPG.11.05', 'judul' => 'Cuti Diluar Tanggungan Negara (CLTN)', 'kategori' => 'Kepegawaian', 'keterangan' => 'Cuti Diluar Tanggungan Negara (CLTN)'],
                ['kode' => 'KPG.12', 'judul' => 'Dokumentasi Identitas Pegawai', 'kategori' => 'Kepegawaian', 'keterangan' => 'Dokumentasi Identitas Pegawai'],
                ['kode' => 'KPG.12.01', 'judul' => 'Usulan Penetapan Karpeg/KPE/Karis/Karsu', 'kategori' => 'Kepegawaian', 'keterangan' => 'Usulan Penetapan Karpeg/KPE/Karis/Karsu'],
                ['kode' => 'KPG.14', 'judul' => 'Berkas Kenaikan Gaji Berkala', 'kategori' => 'Kepegawaian', 'keterangan' => 'Berkas Kenaikan Gaji Berkala'],
                ['kode' => 'KPG.15.02', 'judul' => 'Berkas Layanan Asuransi Pegawai/BPJS', 'kategori' => 'Kepegawaian', 'keterangan' => 'Berkas tentang Layanan Asuransi Pegawai/BPJS'],
                ['kode' => 'KPG.15.08', 'judul' => 'Pemberian Piagam Penghargaan, Sertifikat & Tanda Jasa', 'kategori' => 'Kepegawaian', 'keterangan' => 'Pemberian Piagam Penghargaan, Sertifikat dan tanda jasa'],
                ['kode' => 'KPG.16.01', 'judul' => 'Usulan Pemberhentian & Penetapan Pensiun', 'kategori' => 'Kepegawaian', 'keterangan' => 'Usulan Pemberhentian dan Penetapan Pensiun Pegawai/Janda/Duda dan PNS Meninggal Dunia'],
                ['kode' => 'KU.01', 'judul' => 'RAPBD & Anggaran Pendapatan', 'kategori' => 'Keuangan', 'keterangan' => 'Rencana Anggaran Pendapatan dan Belanja Daerah, dan Anggaran Pendapatan'],
                ['kode' => 'KU.01.02', 'judul' => 'Penyusunan RKA-SKPD', 'kategori' => 'Keuangan', 'keterangan' => 'Penyusunan Rencana Kerja Anggaran Satuan Kerja Perangkat Daerah (RKA-SKPD)'],
                ['kode' => 'KU.02', 'judul' => 'Penyusunan Anggaran', 'kategori' => 'Keuangan', 'keterangan' => 'Penyusunan Anggaran'],
                ['kode' => 'KU.03', 'judul' => 'Pelaksanaan Anggaran', 'kategori' => 'Keuangan', 'keterangan' => 'Pelaksanaan Anggaran'],
                ['kode' => 'KU.03.01', 'judul' => 'Surat Penyedia Dana (SPP, SPM, SP2D)', 'kategori' => 'Keuangan', 'keterangan' => 'Surat Penyedia Dana (SPP,SPM, dan SP2D); UP, GU, TU, LS'],
                ['kode' => 'KU.03.03.05', 'judul' => 'Dana Alokasi Khusus (DAK)', 'kategori' => 'Keuangan', 'keterangan' => 'Dana Alokasi Khusus (DAK)'],
                ['kode' => 'KU.03.10.01', 'judul' => 'Belanja Pegawai', 'kategori' => 'Keuangan', 'keterangan' => 'Belanja Pegawai'],
                ['kode' => 'KU.03.10.02', 'judul' => 'Belanja Barang Jasa', 'kategori' => 'Keuangan', 'keterangan' => 'Belanja Barang Jasa'],
                ['kode' => 'KU.03.10.03', 'judul' => 'Belanja Modal', 'kategori' => 'Keuangan', 'keterangan' => 'Belanja Modal'],
                ['kode' => 'KU.03.11.02', 'judul' => 'Hibah', 'kategori' => 'Keuangan', 'keterangan' => 'Hibah'],
                ['kode' => 'KU.05', 'judul' => 'Dokumen Penatausahaan Keuangan', 'kategori' => 'Keuangan', 'keterangan' => 'Dokumen Penatausahaan Keuangan'],
                ['kode' => 'KU.05.01', 'judul' => 'Surat Penyedia Dana (SPD)', 'kategori' => 'Keuangan', 'keterangan' => 'Surat Penyedia Dana (SPD)'],
                ['kode' => 'KU.05.02', 'judul' => 'Surat Permohonan Pembayaran (SPP)', 'kategori' => 'Keuangan', 'keterangan' => 'Surat Permohonan Pembayaran (SPP)'],
                ['kode' => 'KU.05.03', 'judul' => 'Surat Perintah Membayar (SPM)', 'kategori' => 'Keuangan', 'keterangan' => 'Surat Perintah Membayar (SPM)'],
                ['kode' => 'KU.05.04', 'judul' => 'Surat Perintah Pencairan Dana (SP2D)', 'kategori' => 'Keuangan', 'keterangan' => 'Surat Perintah Pencairan Dana (SP2D)'],
                ['kode' => 'KP.11.08', 'judul' => 'Rekomendasi', 'kategori' => 'Kepegawaian', 'keterangan' => 'REKOMENDASI'],
                ['kode' => 'KS.02.23', 'judul' => 'Sertifikat', 'kategori' => 'Kesiswaan', 'keterangan' => 'SERTIFIKAT'],
                ['kode' => 'PK', 'judul' => 'Pendidikan', 'kategori' => 'Pendidikan', 'keterangan' => 'PENDIDIKAN'],
                ['kode' => 'PK.01', 'judul' => 'Kebijakan Bersifat Pengaturan', 'kategori' => 'Pendidikan', 'keterangan' => 'Kebijakan Bersifat Pengaturan'],
                ['kode' => 'PK.01.02', 'judul' => 'MoU (Memorandum of Understanding)', 'kategori' => 'Pendidikan', 'keterangan' => 'MoU (Memorandum of Understanding)'],
                ['kode' => 'PK.02.01', 'judul' => 'Kebijakan Bersifat Penetapan', 'kategori' => 'Pendidikan', 'keterangan' => 'Kebijakan Bersifat Penetapan'],
                ['kode' => 'PK.03.01.04', 'judul' => 'Pendidikan Masyarakat (Program/Bansos/Publikasi)', 'kategori' => 'Pendidikan', 'keterangan' => 'Pendidikan masyarakat (Penyelenggaraan program, pemberian bantuan sosial, pameran, publikasi)'],
                ['kode' => 'PK.03.01.05', 'judul' => 'Pendidikan Masyarakat (Lomba/Penghargaan)', 'kategori' => 'Pendidikan', 'keterangan' => 'Pendidikan masyarakat (lomba, penghargaan, anugerah)'],
                ['kode' => 'PK.03.02.11', 'judul' => 'Pendidikan Khusus/PKLK', 'kategori' => 'Pendidikan', 'keterangan' => 'Pendidikan khusus/PKLK (sosialisasi, lomba, sayembara, festival)'],
                ['kode' => 'PK.03.01.15', 'judul' => 'Pendidik & Tendik (Prestasi Kerja & Angka Kredit)', 'kategori' => 'Pendidikan', 'keterangan' => 'Pendidik dan tenaga pendidik (Penilaian prestasi kerja, angka kredit, pengawas sekolah, Bimtek, Sosialisai)'],
                ['kode' => 'PK.03.01.16', 'judul' => 'Pendidik & Tendik (Penghargaan)', 'kategori' => 'Pendidikan', 'keterangan' => 'Pendidik dan tenaga pendidik (Penghargaan guru dan tenaga pendidikan)'],
                ['kode' => 'PK.03.03.01', 'judul' => 'Sekolah Menengah Atas (Kurikulum & Bahan Ajar)', 'kategori' => 'Pendidikan', 'keterangan' => 'Sekolah Menengah Atas (Kurikulum, bahan ajar, pelatihan bimtek/sosialisasi. Lomba. Sayembara, Festival)'],
                ['kode' => 'PK.03.03.02', 'judul' => 'Sekolah Menengah Atas (BOS & Bantuan Siswa)', 'kategori' => 'Pendidikan', 'keterangan' => 'Sekolah Menengah Atas (Block grant, Bantuan Operasional Sekolah (BOS), bantuan siswa miskin)'],
                ['kode' => 'PK.09.01', 'judul' => 'Pengembangan Profesi Pendidik', 'kategori' => 'Pendidikan', 'keterangan' => 'Pengembangan profesi pendidik'],
                ['kode' => 'PK.09.01.02', 'judul' => 'Sertifikasi', 'kategori' => 'Pendidikan', 'keterangan' => 'Sertifikasi'],
                ['kode' => 'PK.11.01', 'judul' => 'Data Peserta Didik, Pendidik & Tendik', 'kategori' => 'Pendidikan', 'keterangan' => 'Data peserta didik, pendidik, tenaga kependidikan'],
                ['kode' => 'RT.05.01', 'judul' => 'Kendaraan Dinas', 'kategori' => 'Sarpras', 'keterangan' => 'Kendaraan dinas'],
                ['kode' => 'RT.05.03', 'judul' => 'Telekomunikasi', 'kategori' => 'Sarpras', 'keterangan' => 'Telekomunikasi'],
                ['kode' => 'TU.01', 'judul' => 'Persuratan', 'kategori' => 'Tata Usaha', 'keterangan' => 'Persuratan'],
                ['kode' => 'TU.04', 'judul' => 'Rapat & Rakor', 'kategori' => 'Tata Usaha', 'keterangan' => 'Rapat.Rakor'],
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
