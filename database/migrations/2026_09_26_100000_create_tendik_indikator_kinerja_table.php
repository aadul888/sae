<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tendik_indikator_kinerja')) {
            Schema::create('tendik_indikator_kinerja', function (Blueprint $table) {
                $table->id();
                $table->string('bidang', 64)->index();
                $table->string('sasaran');
                $table->text('indikator_kinerja');
                $table->integer('target_kuantitas')->default(1);
                $table->string('satuan', 50)->default('dokumen');
                $table->string('target_label', 100)->default('1 dokumen');
                $table->integer('urutan')->default(1);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tendik_aktivitas') && !Schema::hasColumn('tendik_aktivitas', 'indikator_id')) {
            Schema::table('tendik_aktivitas', function (Blueprint $table) {
                $table->unsignedBigInteger('indikator_id')->nullable()->after('bidang')->index();
            });
        }

        // Seed data standar Indikator Kinerja Dinas Pendidikan Provinsi Jawa Barat
        $now = now();
        $defaults = [
            // Kepegawaian (Sesuai Contoh Resmi Dinas Pendidikan Provinsi Jawa Barat)
            [
                'bidang' => 'kepegawaian',
                'sasaran' => 'Tersusunnya data kepegawaian',
                'indikator_kinerja' => 'Jumlah dokumen tenaga pendidik dan kependidikan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kepegawaian',
                'sasaran' => 'Tersusunnya data kenaikan gaji berkala ASN',
                'indikator_kinerja' => 'Jumlah dokumen tenaga pendidik dan kependidikan beserta periode pengajuan KGB',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kepegawaian',
                'sasaran' => 'Tersusunnya data kenaikan pangkat PNS',
                'indikator_kinerja' => 'Jumlah dokumen pegawai negeri sipil dan periode kenaikan pangkat',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kepegawaian',
                'sasaran' => 'Tersusunnya data urut kepangkatan',
                'indikator_kinerja' => 'Jumlah dokumen data urut kepangkatan tenaga pendidik dan kependidikan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kepegawaian',
                'sasaran' => 'Tersusunnya data kebutuhan guru',
                'indikator_kinerja' => 'Jumlah dokumen data kebutuhan guru',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Persuratan & Arsip
            [
                'bidang' => 'persuratan',
                'sasaran' => 'Terkendalinya pencatatan dan disposisi surat masuk',
                'indikator_kinerja' => 'Jumlah berkas agenda surat masuk yang tercatat dan terdisposisi ke pimpinan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 berkas',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'persuratan',
                'sasaran' => 'Penerbitan nomor resmi dan pengiriman surat keluar dinas',
                'indikator_kinerja' => 'Jumlah berkas surat keluar dinas yang bernomor klasifikasi dan terdistribusi',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 berkas',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'persuratan',
                'sasaran' => 'Terselenggaranya kearsipan berkas digital dan SK dinas sekolah',
                'indikator_kinerja' => 'Jumlah dokumen SK dinas, surat tugas, dan arsip notula yang tersimpan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'persuratan',
                'sasaran' => 'Terlayaninya pengesahan legalisir dan surat keterangan aktif',
                'indikator_kinerja' => 'Jumlah layanan pengesahan ijazah/rapor dan penerbitan surat keterangan dinas',
                'target_kuantitas' => 1,
                'satuan' => 'layanan',
                'target_label' => '1 layanan',
                'urutan' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Kesiswaan
            [
                'bidang' => 'kesiswaan',
                'sasaran' => 'Pemutakhiran Buku Induk dan Buku Klaper Siswa',
                'indikator_kinerja' => 'Jumlah dokumen Buku Induk dan Buku Klaper peserta didik yang termutakhirkan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kesiswaan',
                'sasaran' => 'Terdokumentasinya berkas mutasi dan kesiswaan',
                'indikator_kinerja' => 'Jumlah berkas permohonan mutasi siswa (masuk/keluar) dan administrasi siswa',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 berkas',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kesiswaan',
                'sasaran' => 'Tervalidasinya daftar nominasi kelulusan tingkat akhir',
                'indikator_kinerja' => 'Jumlah dokumen nominasi peserta didik tingkat akhir untuk SKL dan blanko Ijazah',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sarpras
            [
                'bidang' => 'sarpras',
                'sasaran' => 'Tersusunnya Buku Inventaris Aset dan Sarana Sekolah',
                'indikator_kinerja' => 'Jumlah dokumen rekapitulasi inventaris barang dan sarana pembelajaran',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'sarpras',
                'sasaran' => 'Pembaruan Kartu Inventaris Ruangan (KIR)',
                'indikator_kinerja' => 'Jumlah lembar KIR di ruang kelas, laboratorium, dan kantor yang diperbarui',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 lembar',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'sarpras',
                'sasaran' => 'Terkendalinya stok pemakaian barang habis pakai (ATK)',
                'indikator_kinerja' => 'Jumlah kartu kendali persediaan dan mutasi alat tulis serta perlengkapan kantor',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Laboran
            [
                'bidang' => 'laboran',
                'sasaran' => 'Tersedianya inventaris alat dan bahan praktikum laboratorium',
                'indikator_kinerja' => 'Jumlah dokumen inventarisasi peralatan dan bahan praktikum kejuruan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'laboran',
                'sasaran' => 'Kesiapan ruang dan peralatan untuk kegiatan KBM praktikum',
                'indikator_kinerja' => 'Jumlah sesi penyiapan dan pemeliharaan alat praktikum kejuruan',
                'target_kuantitas' => 1,
                'satuan' => 'kegiatan',
                'target_label' => '1 kegiatan',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Perpustakaan
            [
                'bidang' => 'perpustakaan',
                'sasaran' => 'Terselenggaranya katalogisasi dan klasifikasi buku pustaka',
                'indikator_kinerja' => 'Jumlah koleksi buku yang terdata pada sistem katalog dan berlabel DDC',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 katalog',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'perpustakaan',
                'sasaran' => 'Layanan sirkulasi peminjaman dan pengembalian literasi',
                'indikator_kinerja' => 'Jumlah transaksi sirkulasi dan rekapitulasi pengunjung perpustakaan',
                'target_kuantitas' => 1,
                'satuan' => 'layanan',
                'target_label' => '1 layanan',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Teknisi IT
            [
                'bidang' => 'teknisi',
                'sasaran' => 'Terpeliharanya infrastruktur jaringan internet dan WiFi sekolah',
                'indikator_kinerja' => 'Jumlah laporan pemeliharaan akses internet, router, dan server sekolah',
                'target_kuantitas' => 1,
                'satuan' => 'laporan',
                'target_label' => '1 laporan',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'teknisi',
                'sasaran' => 'Perawatan berkala dan troubleshooting komputer kantor / lab',
                'indikator_kinerja' => 'Jumlah unit komputer dan periferal kantor/lab yang berfungsi normal',
                'target_kuantitas' => 1,
                'satuan' => 'perangkat',
                'target_label' => '1 unit',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keamanan
            [
                'bidang' => 'keamanan',
                'sasaran' => 'Terpeliharanya keamanan dan ketertiban lingkungan sekolah',
                'indikator_kinerja' => 'Jumlah buku tamu dinas dan log pencatatan pintu gerbang sekolah',
                'target_kuantitas' => 1,
                'satuan' => 'buku',
                'target_label' => '1 buku',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'keamanan',
                'sasaran' => 'Pelaksanaan patroli keamanan rutin gedung sekolah',
                'indikator_kinerja' => 'Jumlah laporan log pelaksanaan patroli keliling fasilitas sekolah',
                'target_kuantitas' => 1,
                'satuan' => 'laporan',
                'target_label' => '1 laporan',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Penjaga
            [
                'bidang' => 'penjaga',
                'sasaran' => 'Terpeliharanya kebersihan dan kerapian ruang fasilitas sekolah',
                'indikator_kinerja' => 'Jumlah area selasar, ruang kelas, dan halaman yang dibersihkan harian',
                'target_kuantitas' => 1,
                'satuan' => 'kegiatan',
                'target_label' => '1 kegiatan',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Kepala TAS
            [
                'bidang' => 'kepala_tas',
                'sasaran' => 'Tersusunnya program kerja tahunan tenaga administrasi sekolah',
                'indikator_kinerja' => 'Jumlah dokumen rencana kerja operasional dan pembagian tugas staf TU',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kepala_tas',
                'sasaran' => 'Verifikasi dan validasi dokumen dinas sekolah',
                'indikator_kinerja' => 'Jumlah berkas usulan dan dokumen dinas sekolah yang diverifikasi pimpinan',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bidang' => 'kepala_tas',
                'sasaran' => 'Terlaksananya supervisi harian disiplin staf tendik',
                'indikator_kinerja' => 'Jumlah laporan supervisi dan evaluasi pelaksanaan tugas harian staf',
                'target_kuantitas' => 1,
                'satuan' => 'laporan',
                'target_label' => '1 laporan',
                'urutan' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Umum
            [
                'bidang' => 'umum',
                'sasaran' => 'Pelaksanaan layanan operasional administrasi perkantoran',
                'indikator_kinerja' => 'Jumlah berkas administrasi perkantoran dan perbanyakan dokumen dinas',
                'target_kuantitas' => 1,
                'satuan' => 'dokumen',
                'target_label' => '1 dokumen',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('tendik_indikator_kinerja')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tendik_aktivitas') && Schema::hasColumn('tendik_aktivitas', 'indikator_id')) {
            Schema::table('tendik_aktivitas', function (Blueprint $table) {
                $table->dropColumn('indikator_id');
            });
        }

        Schema::dropIfExists('tendik_indikator_kinerja');
    }
};
