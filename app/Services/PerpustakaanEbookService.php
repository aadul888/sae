<?php

namespace App\Services;

use App\Models\PerpusAnggota;
use App\Support\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PerpustakaanEbookService
 * -------------------------------------------------------------------------
 * Mengelola katalog e-book resmi Kemendikbud (SIBI, Kurikulum Merdeka,
 * modul kejuruan SMK Ditjen Pendidikan Vokasi), sinkronisasi ke katalog
 * perpustakaan sekolah, serta tracking aktivitas membaca digital pemustaka.
 */
class PerpustakaanEbookService
{
    /**
     * Sumber data e-book resmi terkurasi untuk SMK & SMA.
     * Termasuk Buku Teks Kurikulum Merdeka (Umum & Kejuruan Fase E/F)
     * dan Modul Vokasi Direktorat SMK Kemendikbudristek.
     */
    public static function katalogMaster(): array
    {
        return [
            // =================================================================
            // 1. KURIKULUM MERDEKA - MAPEL UMUM (SMK / SMA FASE E & F)
            // =================================================================
            [
                'kode_buku'       => 'EB-KMD-MTK-10',
                'isbn'            => '978-602-244-526-5',
                'judul'           => 'Matematika untuk SMA/SMK Kelas X',
                'penulis'         => 'Dicky Susanto, Theja Kurniawan, Savitri K. Sihombing',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '510',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/matematika-untuk-smasmk-kelas-x',
                'sinopsis'        => 'Buku teks matematika siswa kelas X Kurikulum Merdeka. Membahas Eksponen dan Logaritma, Barisan dan Deret, Vektor dan Operasinya, Trigonometri, Sistem Persamaan dan Pertidaksamaan Linear, Fungsi Kuadrat, Statistika, dan Peluang.',
            ],
            [
                'kode_buku'       => 'EB-KMD-MTK-11',
                'isbn'            => '978-602-244-790-0',
                'judul'           => 'Matematika Tingkat Lanjut untuk SMA/SMK Kelas XI',
                'penulis'         => 'Al Azhary Masta, Yosep Dwi Kristanto, Muhammad Tohir',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '510',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '11',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/matematika-tingkat-lanjut-untuk-smasmk-kelas-xi',
                'sinopsis'        => 'Buku teks matematika siswa kelas XI Kurikulum Merdeka Fase F. Memuat materi Bilangan Kompleks, Polinomial, Matriks, Transformasi Geometri, dan Fungsi Trigonometri untuk penguatan kompetensi sains dan kejuruan teknik.',
            ],
            [
                'kode_buku'       => 'EB-KMD-IND-10',
                'isbn'            => '978-602-244-370-4',
                'judul'           => 'Cerdas Cergas Berbahasa dan Bersastra Indonesia SMA/SMK Kelas X',
                'penulis'         => 'Fadillah Tri Aulia, Sefi Indra Gumilar',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '410',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/cerdas-cergas-berbahasa-dan-bersastra-indonesia-untuk-smasmk-kelas-x',
                'sinopsis'        => 'Buku teks Bahasa Indonesia Fase E dengan pendekatan literasi kritis, analisis teks laporan hasil observasi, anekdot, hikayat, negosiasi, biografi, dan karya puisi terapan.',
            ],
            [
                'kode_buku'       => 'EB-KMD-IND-11',
                'isbn'            => '978-602-244-666-8',
                'judul'           => 'Cerdas Cergas Berbahasa dan Bersastra Indonesia SMA/SMK Kelas XI',
                'penulis'         => 'Heny Marwati, K. Waskitaningtyas',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '410',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '11',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/cerdas-cergas-berbahasa-dan-bersastra-indonesia-untuk-smasmk-kelas-xi',
                'sinopsis'        => 'Buku Bahasa Indonesia Fase F tingkat lanjut. Membahas penulisan karya ilmiah, proposal kegiatan kejuruan, teks prosedur teknis, resensi buku, dan drama pementasan sekolah.',
            ],
            [
                'kode_buku'       => 'EB-KMD-ING-10',
                'isbn'            => '978-602-244-885-3',
                'judul'           => 'English for Change / Work in Progress untuk SMA/SMK Kelas X',
                'penulis'         => 'Budi Hermawan, Dwi Haryanti, Nining Suryaningsih',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '420',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/work-in-progress-untuk-smasmkma-kelas-x',
                'sinopsis'        => 'Materi pembelajaran Bahasa Inggris kontekstual Fase E meliputi Great Athletes, Sports Events, Healthy Food, Descriptive and Narrative texts untuk persiapan komunikasi kerja internasional.',
            ],
            [
                'kode_buku'       => 'EB-KMD-ING-11',
                'isbn'            => '978-602-244-899-0',
                'judul'           => 'English: Train of Thoughts untuk SMA/SMK Kelas XI',
                'penulis'         => 'Utami Widiati, Zuliati Rohmah, Furaidah',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '420',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '11',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/bahasa-inggris-tingkat-lanjut-train-of-thoughts-untuk-smasma-luar-biasasmk-kelas-xi',
                'sinopsis'        => 'Materi Bahasa Inggris terapan Fase F berfokus pada hortatory exposition, report texts, digital safety, dan business professional communication untuk lulusan SMK.',
            ],
            [
                'kode_buku'       => 'EB-KMD-INF-10',
                'isbn'            => '978-602-244-504-3',
                'judul'           => 'Informatika untuk SMA/SMK Kelas X',
                'penulis'         => 'Mushthofa, Auzi Asfarian, Hanson Prihantoro Putro',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '004',
                'kategori'        => 'Informatika & TIK',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/informatika-untuk-smasmk-kelas-x',
                'sinopsis'        => 'Materi Informatika Fase E mencakup Berpikir Komputasional, Teknologi Informasi dan Komunikasi, Sistem Komputer, Jaringan Komputer & Internet, Analisis Data, Algoritma Pemrograman, dan Dampak Sosial Informatika.',
            ],
            [
                'kode_buku'       => 'EB-KMD-PAN-10',
                'isbn'            => '978-602-244-321-6',
                'judul'           => 'Pendidikan Pancasila untuk SMA/SMK Kelas X',
                'penulis'         => 'Rochimudin, Mohamad Mustari, Atik Indrawati',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2023,
                'klasifikasi_ddc' => '320',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-pancasila-untuk-smasmkmak-kelas-x',
                'sinopsis'        => 'Pembahasan Pancasila sebagai dasar negara, pandangan hidup bangsa, Undang-Undang Dasar NRI 1945, Bhinneka Tunggal Ika, dan NKRI dengan penekanan profil pelajar Pancasila berakhlak mulia.',
            ],
            [
                'kode_buku'       => 'EB-KMD-SEJ-10',
                'isbn'            => '978-602-244-372-8',
                'judul'           => 'Sejarah untuk SMK/SMA Kelas X',
                'penulis'         => 'Sari Oktafiana, Martina Safitry, Indah Wahyu Puji Utami',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '900',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-sosial-sejarah-untuk-smasmk-kelas-x',
                'sinopsis'        => 'Pengantar ilmu sejarah, manusia ruang dan waktu, sejarah lokal nusantara, jalur rempah, dan perkembangan kebudayaan Indonesia dari masa praaksara hingga modern.',
            ],
            [
                'kode_buku'       => 'EB-KMD-IPAS-10',
                'isbn'            => '978-623-194-620-1',
                'judul'           => 'Projek Ilmu Pengetahuan Alam dan Sosial (IPAS) SMK/MAK Kelas X',
                'penulis'         => 'Endang Susilowati, Budi Waskito, Tim Pengembang Vokasi',
                'penerbit'        => 'Pusat Perbukuan Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '500',
                'kategori'        => 'Buku Pelajaran',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Umum',
                'sumber_ebook'    => 'sibi',
                'tautan_baca'     => 'https://buku.kemendikdasmen.go.id/katalog/projek-ipas-rumpun-teknologi-untuk-smkmak-kelas-x',
                'sinopsis'        => 'Materi IPAS integratif khusus siswa SMK: Makhluk Hidup & Lingkungannya, Zat & Perubahannya, Energi & Perubahannya, Bumi & Antariksa, Keruangan & Konektivitas, Interaksi Sosial, serta Perilaku Ekonomi & Kesejahteraan.',
            ],

            // =================================================================
            // 2. DASAR-DASAR KEJURUAN SMK (FASE E - KELAS 10 SMK)
            // =================================================================
            [
                'kode_buku'       => 'EB-SMK-TJKT-10',
                'isbn'            => '978-623-388-012-1',
                'judul'           => 'Dasar-Dasar Teknik Jaringan Komputer dan Telekomunikasi (TJKT) Kelas X',
                'penulis'         => 'Kushel Prasetyo, Joko Triyono, Tim Pengembang Kurikulum SMK',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '004.6',
                'kategori'        => 'Informatika & TIK',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Teknik Jaringan Komputer dan Telekomunikasi',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+teknik+jaringan+komputer',
                'sinopsis'        => 'Buku kejuruan dasar TJKT/TKJ: Proses bisnis bidang teknik jaringan komputer, wawasan perkembangan teknologi 5G/IoT, profesi teknisi jaringan, K3LH industri telekomunikasi, dan dasar perakitan serta konfigurasi LAN.',
            ],
            [
                'kode_buku'       => 'EB-SMK-PPLG-10',
                'isbn'            => '978-623-388-024-4',
                'judul'           => 'Dasar-Dasar Pengembangan Perangkat Lunak dan Gim (PPLG / RPL) Kelas X',
                'penulis'         => 'Novianto Puji, Hendra Siswanto',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '005.1',
                'kategori'        => 'Informatika & TIK',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Pengembangan Perangkat Lunak dan Gim',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+pengembangan+perangkat+lunak+gim',
                'sinopsis'        => 'Dasar pemrograman kejuruan RPL: Proses bisnis industri software, SDLC (System Development Life Cycle), Git version control, logika algoritma, dasar HTML/CSS/JavaScript, dan dasar pemrograman berorientasi objek (OOP).',
            ],
            [
                'kode_buku'       => 'EB-SMK-OTO-10',
                'isbn'            => '978-623-388-036-7',
                'judul'           => 'Dasar-Dasar Teknik Otomotif Kelas X (TKRO & TBSM)',
                'penulis'         => 'Bambang Sulistyo, Agus Setiawan',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '629.2',
                'kategori'        => 'Teknik Otomotif',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Teknik Otomotif',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+teknik+otomotif',
                'sinopsis'        => 'Buku kejuruan dasar otomotif: Proses bisnis bengkel otomotif, perkembangan teknologi mobil listrik (EV), K3LH bengkel, penggunaan alat ukur vernier caliper/micrometer, dan prinsip kerja motor 2 langkah & 4 langkah.',
            ],
            [
                'kode_buku'       => 'EB-SMK-MES-10',
                'isbn'            => '978-623-388-048-0',
                'judul'           => 'Dasar-Dasar Teknik Mesin Kelas X',
                'penulis'         => 'Wahyu Hidayat, M. Irfan Pratama',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '621.8',
                'kategori'        => 'Teknik Mesin',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Teknik Mesin',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+teknik+mesin',
                'sinopsis'        => 'Materi dasar teknik permesinan: Gambar teknik manufaktur, proses pemotongan logam, kerja bangku, prinsip pengoperasian mesin bubut dan frais, serta standar keselamatan kerja industri manufaktur.',
            ],
            [
                'kode_buku'       => 'EB-SMK-ELE-10',
                'isbn'            => '978-623-388-050-3',
                'judul'           => 'Dasar-Dasar Teknik Ketenagalistrikan & Elektronika Kelas X',
                'penulis'         => 'Suryanto, Eko Susilo Wardani',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '621.3',
                'kategori'        => 'Kejuruan Vokasi',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Teknik Ketenagalistrikan',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+ketenagalistrikan+elektronika',
                'sinopsis'        => 'Pengenalan hukum Ohm dan Kirchhoff, instalasi penerangan gedung, komponen elektronika pasif dan aktif, sensor, aktuator, dan standar keselamatan instalasi listrik tegangan rendah.',
            ],
            [
                'kode_buku'       => 'EB-SMK-AKL-10',
                'isbn'            => '978-623-388-062-6',
                'judul'           => 'Dasar-Dasar Akuntansi dan Keuangan Lembaga (AKL) Kelas X',
                'penulis'         => 'Sri Wahyuni, Dwi Hartati',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '657',
                'kategori'        => 'Bisnis & Akuntansi',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Akuntansi dan Keuangan Lembaga',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+akuntansi+keuangan+lembaga',
                'sinopsis'        => 'Membahas siklus akuntansi perusahaan jasa dan dagang, persamaan dasar akuntansi, jurnal umum, buku besar, neraca saldo, jurnal penyesuaian, dan etika profesi teknisi akuntansi yunior.',
            ],
            [
                'kode_buku'       => 'EB-SMK-MPLB-10',
                'isbn'            => '978-623-388-074-9',
                'judul'           => 'Dasar-Dasar Manajemen Perkantoran dan Layanan Bisnis (MPLB) Kelas X',
                'penulis'         => 'Tri Sulistiawati, Anik Nurhidayati',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '651',
                'kategori'        => 'Bisnis & Akuntansi',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Manajemen Perkantoran dan Layanan Bisnis',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+manajemen+perkantoran+bisnis',
                'sinopsis'        => 'Konsep kantor modern digital, otomasi tata kelola perkantoran, penanganan surat/dokumen elektronik, layanan prima (service excellence), komunikasi telepon bisnis, dan etika humas.',
            ],
            [
                'kode_buku'       => 'EB-SMK-DKV-10',
                'isbn'            => '978-623-388-086-2',
                'judul'           => 'Dasar-Dasar Desain Komunikasi Visual (DKV) Kelas X',
                'penulis'         => 'Rudi Hermawan, Maya Anggraini',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '741.6',
                'kategori'        => 'Kejuruan Vokasi',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Desain Komunikasi Visual',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+desain+komunikasi+visual',
                'sinopsis'        => 'Prinsip desain grafis: Nirmana dwimatra dan trimatra, tipografi, teori warna, fotografi dasar, ilustrasi digital berbasis vektor dan raster, serta hak cipta karya visual.',
            ],
            [
                'kode_buku'       => 'EB-SMK-BIS-10',
                'isbn'            => '978-623-388-098-5',
                'judul'           => 'Dasar-Dasar Pemasaran dan Bisnis Digital Kelas X',
                'penulis'         => 'Heru Kurniawan, Siti Rahmawati',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2022,
                'klasifikasi_ddc' => '658.8',
                'kategori'        => 'Bisnis & Akuntansi',
                'jenjang'         => 'SMK',
                'kelas'           => '10',
                'jurusan'         => 'Pemasaran dan Bisnis Digital',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=dasar+pemasaran+bisnis+digital',
                'sinopsis'        => 'Ekosistem e-commerce dan marketplace, digital marketing via social media, copywriting produk, riset kata kunci, visual merchandising toko fisik dan virtual, serta pembayaran nontunai QRIS.',
            ],

            // =================================================================
            // 3. KEJURUAN LANJUTAN & KEWIRAUSAHAAN (FASE F - KELAS 11 & 12 SMK)
            // =================================================================
            [
                'kode_buku'       => 'EB-SMK-PKK-11',
                'isbn'            => '978-623-388-110-4',
                'judul'           => 'Produk Kreatif dan Kewirausahaan (PKK) SMK/MAK Kelas XI & XII',
                'penulis'         => 'Dwi Astuti, M. Yusuf Rahman, Tim Vokasi',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2023,
                'klasifikasi_ddc' => '658.4',
                'kategori'        => 'Bisnis & Akuntansi',
                'jenjang'         => 'SMK',
                'kelas'           => '11',
                'jurusan'         => 'Semua Jurusan SMK',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=produk+kreatif+kewirausahaan+smk',
                'sinopsis'        => 'Buku wajib seluruh siswa SMK: Sikap wirausaha, analisis peluang usaha, Hak Atas Kekayaan Intelektual (HAKI), prototyping produk barang/jasa, pengujian mutu, perhitungan BEP & laba, serta pemasaran online.',
            ],
            [
                'kode_buku'       => 'EB-SMK-AIJ-11',
                'isbn'            => '978-602-427-401-1',
                'judul'           => 'Administrasi Infrastruktur Jaringan (AIJ) SMK Kelas XI',
                'penulis'         => 'Rudi Nurcahyo, S.Kom',
                'penerbit'        => 'Direktorat Pembinaan SMK',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '004.65',
                'kategori'        => 'Informatika & TIK',
                'jenjang'         => 'SMK',
                'kelas'           => '11',
                'jurusan'         => 'Teknik Jaringan Komputer dan Telekomunikasi',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=administrasi+infrastruktur+jaringan',
                'sinopsis'        => 'Konfigurasi VLAN (Virtual LAN), Inter-VLAN routing, Routing Statis dan Dinamis (RIP/OSPF), Access Control List (ACL), Mikrotik RouterOS, dan manajemen bandwidth Simple Queue.',
            ],
            [
                'kode_buku'       => 'EB-SMK-PWPB-11',
                'isbn'            => '978-602-427-512-4',
                'judul'           => 'Pemrograman Web dan Perangkat Bergerak (PWPB) SMK Kelas XI & XII',
                'penulis'         => 'Budi Raharjo, M.Kom',
                'penerbit'        => 'Direktorat Pembinaan SMK',
                'tahun_terbit'    => 2021,
                'klasifikasi_ddc' => '005.276',
                'kategori'        => 'Informatika & TIK',
                'jenjang'         => 'SMK',
                'kelas'           => '11',
                'jurusan'         => 'Pengembangan Perangkat Lunak dan Gim',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=pemrograman+web+perangkat+bergerak',
                'sinopsis'        => 'Pemrograman web modern: Front-end responsif dengan CSS Flexbox & Grid, integrasi backend PHP MySQL PDO, implementasi RESTful API, dan dasar framework mobile web.',
            ],

            // =================================================================
            // 4. PANDUAN PKL, K3LH, & BUDAYA KERJA INDUSTRI (VOKASI SMK)
            // =================================================================
            [
                'kode_buku'       => 'EB-SMK-PKL-GUIDE',
                'isbn'            => '978-623-388-150-0',
                'judul'           => 'Panduan Pelaksanaan Praktik Kerja Lapangan (PKL) Peserta Didik SMK',
                'penulis'         => 'Tim Direktorat SMK & Tim Dunia Usaha / Dunia Industri (DUDI)',
                'penerbit'        => 'Direktorat SMK Kemendikbudristek',
                'tahun_terbit'    => 2023,
                'klasifikasi_ddc' => '371.227',
                'kategori'        => 'Panduan & K3LH',
                'jenjang'         => 'SMK',
                'kelas'           => 'Semua Kelas',
                'jurusan'         => 'Semua Jurusan SMK',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=panduan+pkl+smk',
                'sinopsis'        => 'Pedoman resmi pelaksanaan PKL sesuai Kurikulum Merdeka (durasi minimal 6 bulan): Persiapan mental, etika kerja di industri, pengisian jurnal harian, supervisi pembimbing, dan penyusunan laporan uji sertifikasi kejuruan.',
            ],
            [
                'kode_buku'       => 'EB-SMK-K3LH-IND',
                'isbn'            => '978-623-388-160-9',
                'judul'           => 'Keselamatan, Kesehatan Kerja, dan Lingkungan Hidup (K3LH) Industri',
                'penulis'         => 'Ir. Hendro Basuki, MT & Dewan K3 Nasional',
                'penerbit'        => 'Direktorat Pendidikan Vokasi Kemendikbudristek',
                'tahun_terbit'    => 2023,
                'klasifikasi_ddc' => '613.62',
                'kategori'        => 'Panduan & K3LH',
                'jenjang'         => 'SMK',
                'kelas'           => 'Semua Kelas',
                'jurusan'         => 'Semua Jurusan SMK',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=k3lh+industri+vokasi',
                'sinopsis'        => 'Standar wajib keamanan kerja bengkel & lab sekolah: Penggunaan APD (Alat Pelindung Diri), penanganan bahaya kebakaran (APAR), ergonomi kerja komputer, P3K kecelakaan kerja, dan manajemen limbah B3 bengkel praktik.',
            ],
            [
                'kode_buku'       => 'EB-SMK-5R-BUDAYA',
                'isbn'            => '978-623-388-170-8',
                'judul'           => 'Budaya Kerja Industri 5S/5R: Ringkas, Rapi, Resik, Rawat, Rajin',
                'penulis'         => 'Tim Asosiasi Industri Mitra Vokasi',
                'penerbit'        => 'Direktorat Kemitraan dan Penyelarasan DUDI',
                'tahun_terbit'    => 2023,
                'klasifikasi_ddc' => '658.3',
                'kategori'        => 'Panduan & K3LH',
                'jenjang'         => 'SMK',
                'kelas'           => 'Semua Kelas',
                'jurusan'         => 'Semua Jurusan SMK',
                'sumber_ebook'    => 'ditjen_smk',
                'tautan_baca'     => 'https://repositori.kemdikbud.go.id/search?q=budaya+kerja+5r+vokasi',
                'sinopsis'        => 'Panduan praktis pembiasaan budaya kerja standar industri Jepang (5S: Seiri, Seiton, Seiso, Seiketsu, Shitsuke / 5R: Ringkas, Rapi, Resik, Rawat, Rajin) di lingkungan lab, workshop, dan kehidupan sehari-hari siswa SMK.',
            ],
        ];
    }

    /**
     * Cari / saring e-book master berdasarkan kata kunci, jenjang, kelas, dan kategori.
     */
    public static function cari(array $filter = []): array
    {
        $katalog = self::katalogMaster();
        $q = trim((string) ($filter['q'] ?? ''));
        $kelas = trim((string) ($filter['kelas'] ?? ''));
        $kategori = trim((string) ($filter['kategori'] ?? ''));
        $sumber = trim((string) ($filter['sumber'] ?? ''));

        $hasil = array_filter($katalog, function ($item) use ($q, $kelas, $kategori, $sumber) {
            if ($kelas !== '' && $item['kelas'] !== $kelas && $item['kelas'] !== 'Semua Kelas') {
                return false;
            }
            if ($kategori !== '' && $item['kategori'] !== $kategori) {
                return false;
            }
            if ($sumber !== '' && ($item['sumber_ebook'] ?? '') !== $sumber) {
                return false;
            }
            if ($q !== '') {
                $cocok = Str::contains(Str::lower($item['judul']), Str::lower($q))
                    || Str::contains(Str::lower($item['penulis']), Str::lower($q))
                    || Str::contains(Str::lower($item['jurusan']), Str::lower($q))
                    || Str::contains(Str::lower($item['sinopsis']), Str::lower($q))
                    || Str::contains(Str::lower($item['kode_buku']), Str::lower($q));
                if (!$cocok) {
                    return false;
                }
            }
            return true;
        });

        return array_values(self::tandaiStatusKatalog($hasil));
    }

    /**
     * Cek apakah buku-buku di daftar sudah ada pada tabel perpus_koleksi_buku sekolah.
     */
    public static function tandaiStatusKatalog(array $items): array
    {
        if (empty($items) || !Table::exists('perpus_koleksi_buku')) {
            return array_map(function ($i) {
                $i['sudah_ada'] = false;
                $i['koleksi_id'] = null;
                return $i;
            }, $items);
        }

        $kodeList = array_column($items, 'kode_buku');
        $judulList = array_column($items, 'judul');

        $existing = DB::table('perpus_koleksi_buku')
            ->where(function ($query) use ($kodeList, $judulList) {
                $query->whereIn('kode_buku', $kodeList)
                    ->orWhereIn('judul', $judulList);
            })
            ->select('id', 'kode_buku', 'judul')
            ->get();

        $byKode = $existing->keyBy('kode_buku');
        $byJudul = $existing->keyBy('judul');

        return array_map(function ($item) use ($byKode, $byJudul) {
            $found = $byKode->get($item['kode_buku']) ?? $byJudul->get($item['judul']);
            $item['sudah_ada'] = (bool) $found;
            $item['koleksi_id'] = $found ? $found->id : null;
            return $item;
        }, $items);
    }

    /**
     * Impor satu e-book resmi ke tabel perpus_koleksi_buku.
     * Idempoten: jika sudah ada, update tautan_baca & sinopsis.
     */
    public static function imporKeKatalog(array $item): int
    {
        if (!Table::exists('perpus_koleksi_buku')) {
            return 0;
        }

        $existing = DB::table('perpus_koleksi_buku')
            ->where('kode_buku', $item['kode_buku'])
            ->orWhere('judul', $item['judul'])
            ->first();

        $payload = [
            'kode_buku'          => $item['kode_buku'],
            'isbn'               => $item['isbn'] ?? null,
            'judul'              => $item['judul'],
            'penulis'            => $item['penulis'] ?? null,
            'penerbit'           => $item['penerbit'] ?? 'Kemendikbudristek',
            'tahun_terbit'       => $item['tahun_terbit'] ?? null,
            'klasifikasi_ddc'    => $item['klasifikasi_ddc'] ?? null,
            'kategori'           => $item['kategori'] ?? 'Buku Pelajaran',
            'format'             => 'ebook',
            'jumlah_eksemplar'   => 999,
            'eksemplar_tersedia' => 999,
            'lokasi_rak'         => 'Rak Digital',
            'sumber_dana'        => 'Kemendikbud',
            'harga_perolehan'    => 0,
            'tgl_pembelian'      => now()->toDateString(),
            'tautan_baca'        => $item['tautan_baca'] ?? null,
            'file_ebook'         => $item['file_ebook'] ?? null,
            'sumber_ebook'       => $item['sumber_ebook'] ?? 'kemendikbud',
            'sinopsis'           => $item['sinopsis'] ?? null,
            'jenjang'            => $item['jenjang'] ?? 'SMK',
            'kelas'              => $item['kelas'] ?? null,
            'jurusan'            => $item['jurusan'] ?? null,
            'updated_at'         => now(),
        ];

        if ($existing) {
            DB::table('perpus_koleksi_buku')->where('id', $existing->id)->update($payload);
            return (int) $existing->id;
        }

        $payload['created_at'] = now();
        $payload['total_dibaca'] = 0;
        return (int) DB::table('perpus_koleksi_buku')->insertGetId($payload);
    }

    /**
     * Impor banyak e-book sekaligus berdasarkan daftar kode_buku.
     *
     * @return array{sukses:int, dilewati:int, total:int}
     */
    public static function imporBanyak(array $kodeList): array
    {
        $katalog = collect(self::katalogMaster())->keyBy('kode_buku');
        $sukses = 0;
        $dilewati = 0;

        foreach ($kodeList as $kode) {
            $item = $katalog->get($kode);
            if (!$item) {
                $dilewati++;
                continue;
            }
            self::imporKeKatalog($item);
            $sukses++;
        }

        return [
            'sukses'   => $sukses,
            'dilewati' => $dilewati,
            'total'    => count($kodeList),
        ];
    }

    /**
     * Catat aktivitas membaca e-book oleh pemustaka:
     * - Increment total_dibaca pada buku
     * - Catat presensi kunjungan digital bila ada akun anggota
     * - Beri reward poin literasi anggota (+3 poin)
     */
    public static function catatMembaca(int $bukuId, ?PerpusAnggota $member = null): array
    {
        if (!Table::exists('perpus_koleksi_buku')) {
            return ['ok' => false, 'pesan' => 'Tabel koleksi tidak ditemukan.'];
        }

        $buku = DB::table('perpus_koleksi_buku')->where('id', $bukuId)->first();
        if (!$buku) {
            return ['ok' => false, 'pesan' => 'Buku tidak ditemukan.'];
        }

        // 1. Tambah counter baca
        if (Table::hasColumn('perpus_koleksi_buku', 'total_dibaca')) {
            DB::table('perpus_koleksi_buku')->where('id', $bukuId)->increment('total_dibaca');
        }

        $poinDidapat = 0;

        // 2. Jika anggota login, catat kunjungan & tambah poin literasi
        if ($member) {
            if (Table::exists('perpus_kunjungan')) {
                DB::table('perpus_kunjungan')->insert([
                    'tanggal'          => now()->toDateString(),
                    'jam_kunjung'      => now()->toTimeString(),
                    'pengunjung_tipe'  => $member->tipe,
                    'pengunjung_id'    => $member->siswa_id ?: ($member->ptk_id ?: $member->nomor_anggota),
                    'nama'             => $member->nama,
                    'rombel_atau_unit' => $member->rombel_atau_unit,
                    'keperluan'        => 'Membaca E-Book: ' . Str::limit($buku->judul, 40),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            // Reward literasi: baca e-book (+3 poin)
            $poinDidapat = PerpustakaanMemberService::tambahPoin($member, 'baca_ebook');
            PerpustakaanMemberService::notifikasi(
                $member,
                "+{$poinDidapat} Poin Literasi: Membaca E-Book",
                "Selamat! Anda mendapatkan +{$poinDidapat} poin literasi karena membaca e-book \"{$buku->judul}\".",
                [
                    'kategori' => 'poin',
                    'tipe'     => 'success',
                    'icon'     => 'fa-solid fa-crown',
                    'url'      => route('perpustakaan.buku.detail', $buku->id),
                ]
            );
        }

        return [
            'ok'          => true,
            'buku'        => $buku,
            'poin'        => $poinDidapat,
            'tautan_baca' => $buku->tautan_baca ?: ($buku->file_ebook ? asset('storage/' . $buku->file_ebook) : null),
        ];
    }

    /**
     * Daftar opsi filter kategori e-book.
     */
    public static function daftarKategori(): array
    {
        return [
            'Buku Pelajaran',
            'Informatika & TIK',
            'Teknik Otomotif',
            'Teknik Mesin',
            'Bisnis & Akuntansi',
            'Kejuruan Vokasi',
            'Panduan & K3LH',
        ];
    }
}
