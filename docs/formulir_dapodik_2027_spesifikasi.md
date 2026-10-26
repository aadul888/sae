# Spesifikasi Teknis Formulir Peserta Didik & Referensi Master Dapodik 2026/2027 (Revisi 1)

**Sistem Aplikasi Edukasi (SAE)**

*Dokumen ini disusun ulang berdasarkan pembaruan formulir resmi Dapodik (Form Dapodik 2027 rev1). Bagian rekening bank dan mutasi keluar telah dihapus, data periodik peserta didik ditambahkan, urutan form diselaraskan persis dengan dokumen fisik/PDF, dan atribut Dapodik pusat tambahan ditempatkan di bagian akhir formulir.*

---

## DAFTAR ISI
1. [Master Tabel Referensi Bersama (Shared Lookups)](#1-master-tabel-referensi-bersama-shared-lookups)
   - [1.1 Kebutuhan Khusus](#11-tabel-referensi-kebutuhan-khusus-ref_kebutuhan_khusus)
   - [1.2 Agama & Kepercayaan](#12-tabel-referensi-agama--kepercayaan-ref_agama)
   - [1.3 Tempat Tinggal](#13-tabel-referensi-tempat-tinggal-ref_tempat_tinggal)
   - [1.4 Moda Transportasi](#14-tabel-referensi-moda-transportasi-ref_transportasi)
   - [1.5 Jenjang Pendidikan Formal & Non-Formal](#15-tabel-referensi-jenjang-pendidikan-ref_jenjang_pendidikan)
   - [1.6 Pekerjaan](#16-tabel-referensi-pekerjaan-ref_pekerjaan)
   - [1.7 Rentang Penghasilan Bulanan](#17-tabel-referensi-rentang-penghasilan-ref_penghasilan)
   - [1.8 Prestasi (Jenis & Tingkat)](#18-tabel-referensi-prestasi-ref_prestasi)
   - [1.9 Jenis Kesejahteraan Sosial](#19-tabel-referensi-kesejahteraan-ref_kesejahteraan)
   - [1.10 Jenis Pendaftaran](#110-tabel-referensi-jenis-pendaftaran-ref_jenis_pendaftaran)
   - [1.11 Hobi / Kegemaran](#111-tabel-referensi-hobi-ref_hobi)
   - [1.12 Cita-cita / Impian Profesi](#112-tabel-referensi-cita-cita-ref_cita_cita)
2. [Spesifikasi Field Formulir Peserta Didik (Sesuai Urutan PDF Rev1)](#2-spesifikasi-field-formulir-peserta-didik-sesuai-urutan-pdf-rev1)
   - [Bagian 1: Data Pribadi & Domisili](#bagian-1-data-pribadi--domisili-halaman-1-2)
   - [Bagian 2: Data Ayah Kandung](#bagian-2-data-ayah-kandung-halaman-2-3)
   - [Bagian 3: Data Ibu Kandung](#bagian-3-data-ibu-kandung-halaman-4)
   - [Bagian 4: Data Wali](#bagian-4-data-wali-halaman-4)
   - [Bagian 5: Kontak](#bagian-5-kontak-halaman-4)
   - [Bagian 6: Prestasi](#bagian-6-prestasi-halaman-4-5)
   - [Bagian 7: Kesejahteraan](#bagian-7-kesejahteraan-halaman-5)
   - [Bagian 8: Pendaftaran Masuk](#bagian-8-pendaftaran-masuk-halaman-5)
   - [Bagian 9: Minat & Bakat (Hobby & Cita-cita)](#bagian-9-minat--bakat-hobby--cita-cita-halaman-5-6)
   - [Bagian 10: Data Periodik Peserta Didik](#bagian-10-data-periodik-peserta-didik-halaman-6)
3. [Field Dapodik Tambahan (Atribut Pusat Belum Terpetakan di Form Fisik)](#3-field-dapodik-tambahan-atribut-pusat-belum-terpetakan-di-form-fisik)
4. [Rekomendasi Arsitektur Database SAE](#4-rekomendasi-arsitektur-database-sae)

---

## 1. MASTER TABEL REFERENSI BERSAMA (SHARED LOOKUPS)

### 1.1 Tabel Referensi Kebutuhan Khusus (`ref_kebutuhan_khusus`)
*Digunakan oleh: Peserta Didik, Ayah Kandung, Ibu Kandung, dan Wali.*

| Kode / ID | Deskripsi Kebutuhan Khusus | Simbol / Label Form |
| :--- | :--- | :--- |
| `0` | Tidak ada | - |
| `A` | Netra | Netra (A) |
| `B` | Rungu | Rungu (B) |
| `C` | Grahita ringan | Grahita ringan (C) |
| `C1` | Grahita sedang | Grahita sedang (C1) |
| `D` | Daksa ringan | Daksa ringan (D) |
| `D1` | Daksa sedang | Daksa sedang (D1) |
| `E` | Laras | Laras (E) |
| `F` | Wicara | Wicara (F) |
| `H` | Hyperaktif | Hyperaktif (H) |
| `I` | Cerdas istimewa | Cerdas istimewa (I) |
| `J` | Bakat istimewa | Bakat istimewa (J) |
| `K` | Kesulitan belajar | Kesulitan belajar (K) |
| `N` | Narkoba | Narkoba (N) |
| `O` | Indigo | Indigo (O) |
| `P` | Down syndrome | Down syndrome (P) |
| `Q` | Autis | Autis (Q) |
| `99` | Lainnya | Lainnya |

### 1.2 Tabel Referensi Agama & Kepercayaan (`ref_agama`)
*Digunakan oleh: Peserta Didik.*

| ID | Nama Agama / Kepercayaan |
| :--- | :--- |
| `1` | Islam |
| `2` | Kristen |
| `3` | Katholik |
| `4` | Hindu |
| `5` | Budha |
| `6` | Khonghucu |
| `7` | Kepercayaan kpd Tuhan YME |
| `99` | Lainnya |

### 1.3 Tabel Referensi Tempat Tinggal (`ref_tempat_tinggal`)
*Digunakan oleh: Domisili Siswa.*

| ID | Tipe Tempat Tinggal |
| :--- | :--- |
| `1` | Bersama orang tua |
| `2` | Wali |
| `3` | Kost |
| `4` | Asrama |
| `5` | Panti asuhan |
| `6` | Pesantren |
| `99` | Lainnya |

### 1.4 Tabel Referensi Moda Transportasi (`ref_transportasi`)
*Digunakan oleh: Domisili Siswa.*

| ID | Nama Moda Transportasi |
| :--- | :--- |
| `1` | Jalan kaki |
| `2` | Sepeda |
| `3` | Sepeda motor |
| `4` | Mobil pribadi |
| `5` | Angkutan umum/bus/pete-pete |
| `6` | Mobil/bus antar jemput |
| `7` | Kereta api |
| `8` | Ojek |
| `9` | Andong/bendi/sado/dokar/delman/becak |
| `10` | Perahu penyeberangan/rakit/getek |
| `11` | Kuda |
| `99` | Lainnya |

### 1.5 Tabel Referensi Jenjang Pendidikan (`ref_jenjang_pendidikan`)
*Digunakan oleh: Ayah Kandung, Ibu Kandung, dan Wali.*

| ID | Nama Jenjang |
| :--- | :--- |
| `0` | Tidak sekolah |
| `1` | PAUD |
| `2` | TK / sederajat |
| `3` | Putus SD |
| `4` | SD / sederajat |
| `5` | SMP / sederajat |
| `6` | SMA / sederajat |
| `7` | Paket A |
| `8` | Paket B |
| `9` | Paket C |
| `10` | D1 |
| `11` | D2 |
| `12` | D3 |
| `13` | D4 |
| `14` | S1 |
| `15` | S2 |
| `16` | S2 Terapan |
| `17` | S3 |
| `18` | S3 Terapan |
| `19` | Profesi |
| `20` | Sp-1 |
| `21` | Sp-2 |
| `22` | Non formal |
| `23` | Informal |
| `99` | Lainnya |

### 1.6 Tabel Referensi Pekerjaan (`ref_pekerjaan`)
*Digunakan oleh: Ayah Kandung, Ibu Kandung, dan Wali.*

| ID | Nama Pekerjaan |
| :--- | :--- |
| `1` | Tidak bekerja |
| `2` | Nelayan |
| `3` | Petani |
| `4` | Peternak |
| `5` | PNS/TNI/Polri |
| `6` | Aparatur Sipil Negara |
| `7` | Karyawan Swasta |
| `8` | Karyawan BUMN |
| `9` | Pedagang Kecil |
| `10` | Pedagang Besar |
| `11` | Perdagangan |
| `12` | Wiraswasta |
| `13` | Wirausaha |
| `14` | Buruh |
| `15` | Pensiunan |
| `16` | Tenaga Kerja Indonesia |
| `97` | Tidak dapat diterapkan |
| `98` | Sudah Meninggal |
| `99` | Lainnya |

### 1.7 Tabel Referensi Rentang Penghasilan (`ref_penghasilan`)
*Digunakan oleh: Ayah Kandung, Ibu Kandung, dan Wali.*

| ID | Rentang Nominal | Keterangan |
| :--- | :--- | :--- |
| `0` | Tidak Berpenghasilan | Khusus tidak berpenghasilan/meninggal |
| `1` | Kurang dari Rp. 500,000 / < Rp1.000.000 | Kelompok sangat rendah |
| `2` | Rp. 500,000 - Rp. 999,999 | Kelompok rendah |
| `3` | Rp. 1.000.000 - Rp. 1,999,999 (Rp1.000.001 - Rp3.000.000) | Menengah ke bawah |
| `4` | Rp. 2.000.000 - Rp. 4.999.999 (Rp3.000.001 - Rp5.500.000) | Menengah |
| `5` | Rp. 5,000,000 - Rp. 20,000,000 (Rp5.500.001 - Rp10.000.000) | Menengah ke atas |
| `6` | Lebih dari Rp. 20,000,000 (> Rp10.000.000) | Kelompok mampu/tinggi |

### 1.8 Tabel Referensi Prestasi (`ref_prestasi`)
*Digunakan oleh: Riwayat Prestasi Siswa.*

* **Jenis Prestasi:** `Sains`, `Seni`, `Olahraga`, `Lain-lain`
* **Tingkat Prestasi:** `Sekolah`, `Kecamatan`, `Kab/kota`, `Propinsi`, `Nasional`, `Internasional`, `Lainnya`

### 1.9 Tabel Referensi Kesejahteraan (`ref_kesejahteraan`)
*Digunakan oleh: Bantuan Kesejahteraan Sosial Siswa.*

* `Kartu Keluarga Sejahtera`
* `Kartu Indonesia Sehat`
* `Orang Asli Papua (OAP)`

### 1.10 Tabel Referensi Jenis Pendaftaran (`ref_jenis_pendaftaran`)
*Digunakan oleh: Pendaftaran Masuk.*

| ID | Nama Jenis Pendaftaran |
| :--- | :--- |
| `1` | Siswa baru |
| `2` | Pindahan |
| `3` | Naik kelas |
| `4` | Kembali bersekolah |

### 1.11 Tabel Referensi Hobi (`ref_hobi`)
*Digunakan oleh: Minat Siswa.*

| ID | Nama Hobi | ID | Nama Hobi |
| :--- | :--- | :--- | :--- |
| `0` | (belum diisi) | `15` | Jogging |
| `1` | Belanja | `16` | Kesenian |
| `2` | Berkemah | `17` | Main Puzzle |
| `3` | Berlari | `18` | Makan |
| `4` | Bermain Biola | `19` | Memancing |
| `5` | Bermain Bola | `20` | Membaca |
| `6` | Bermain Bola Tenis | `21` | Mendaki |
| `7` | Bermain Boneka | `22` | Menggambar |
| `8` | Bermain Bulu Tangkis | `23` | Menjahit |
| `9` | Bermain Gitar | `24` | Menulis |
| `10` | Bermain Musik | `25` | Mewarnai |
| `11` | Bermain Piano | `26` | Olah Raga |
| `12` | Berselancar | `27` | Traveling |
| `13` | Fitness | `99` | Lainnya |
| `14` | Fotografi | | |

### 1.12 Tabel Referensi Cita-cita (`ref_cita_cita`)
*Digunakan oleh: Orientasi Karir Siswa.*

| ID | Pilihan Cita-cita | ID | Pilihan Cita-cita |
| :--- | :--- | :--- | :--- |
| `1` | Dai/Ustadz | `19` | Penulis |
| `2` | Designer | `20` | Penyiar Radio |
| `3` | Dokter | `21` | Perawat |
| `4` | Entertainer / Pekerja Seni | `22` | Perawat / Suster |
| `5` | Arsitek | `23` | TNI/Polri |
| `6` | Astronot | `24` | Pegawai Negeri Sipil / PNS |
| `7` | Atlet | `25` | Pelaut |
| `8` | Atlet E-Sport Profesional | `26` | Pemadam Kebakaran |
| `9` | Atlit Olahraga | `27` | Pembalap |
| `10` | Bidan | `28` | Pembawa Acara / Master Ceremony |
| `11` | Content Creator | `29` | Pendeta |
| `12` | Guru / Dosen | `30` | Pengacara |
| `13` | Koki | `31` | Penghafal Al-Qur'an |
| `14` | Masinis Kereta Api | `32` | Pilot |
| `15` | Pengusaha / Bisnismen | `33` | Polisi |
| `16` | Politikus | `34` | Translator |
| `17` | Presiden | `35` | Vloger |
| `18` | Seni / Lukis / Artis / Sejenis | `36` | Wartawan |
| `37` | Wiraswasta | `99` | Lainnya |

---

## 2. SPESIFIKASI FIELD FORMULIR PESERTA DIDIK (SESUAI URUTAN PDF REV1)

### Bagian 1: Data Pribadi & Domisili (Halaman 1-2)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 1 | **Nama** | Text Input | **WAJIB** | Sesuai Akta Kelahiran / Ijazah |
| 2 | **Jenis kelamin** | Radio Button | **WAJIB** | `L` (Laki-laki) / `P` (Perempuan) |
| 3 | **NISN** | Number / Text | Opsional | `CHAR(10)` |
| 4 | **Kewarganegaraan** | Dropdown | **WAJIB** | `WNI` / `WNA` |
| 5 | **NIK** | Number / Text | Opsional | `CHAR(16)` |
| 6 | **No KK** | Number / Text | Opsional | `CHAR(16)` |
| 7 | **Tempat lahir** | Text Input | Opsional | `VARCHAR(60)` |
| 8 | **Tanggal lahir** | Datepicker | Opsional | Format `YYYY-MM-DD` |
| 9 | **No Registrasi Akta Lahir** | Text Input | Opsional | `VARCHAR(80)` |
| 10 | **Berkebutuhan khusus** | Dropdown | Opsional | Lookup: `ref_kebutuhan_khusus` |
| 11 | **Agama & Kepercayaan** | Dropdown | **WAJIB** | Lookup: `ref_agama` |
| 12 | **Alamat jalan** | Text Input | **WAJIB** | `VARCHAR(255)` |
| 13 | **RT** | Number Input | Opsional | Maksimal 3 digit |
| 14 | **RW** | Number Input | Opsional | Maksimal 3 digit |
| 15 | **Nama dusun** | Text Input | Opsional | `VARCHAR(100)` |
| 16 | **Desa/Kelurahan** | Dropdown | Opsional | Master Kode Wilayah Administrasi |
| 17 | **Kode pos** | Number Input | Opsional | 5 digit numerik |
| 18 | **Lintang** | Number / Text | Opsional | `DECIMAL(10, 7)` |
| 19 | **Bujur** | Number / Text | Opsional | `DECIMAL(11, 7)` |
| 20 | **Tempat tinggal** | Dropdown | **WAJIB** | Lookup: `ref_tempat_tinggal` |
| 21 | **Moda transportasi** | Dropdown | **WAJIB** | Lookup: `ref_transportasi` |
| 22 | **Anak ke-berapa (berdasarkan KK)** | Number Input | **WAJIB** | `SMALLINT` |

---

### Bagian 2: Data Ayah Kandung (Halaman 2-3)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 23 | **Status Hidup** | Radio Button | Opsional | `Masih Hidup` (1) / `Sudah Meninggal` (0) |
| 24 | **Nama ayah** | Text Input | Opsional | `VARCHAR(150)` |
| 25 | **NIK Ayah** | Number / Text | Opsional | `CHAR(16)` |
| 26 | **Tahun lahir ayah** | Number Input | Opsional | `YEAR` (YYYY) |
| 27 | **Pendidikan ayah** | Dropdown | Opsional | Lookup: `ref_jenjang_pendidikan` |
| 28 | **Pekerjaan ayah** | Dropdown | **WAJIB** | Lookup: `ref_pekerjaan` |
| 29 | **Berkebutuhan khusus ayah** | Dropdown | Opsional | Lookup: `ref_kebutuhan_khusus` |
| 30 | **Penghasilan ayah** | Dropdown | **WAJIB** | Lookup: `ref_penghasilan` |

---

### Bagian 3: Data Ibu Kandung (Halaman 4)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 31 | **Status Hidup** | Radio Button | Opsional | `Masih Hidup` (1) / `Sudah Meninggal` (0) |
| 32 | **Nama ibu** | Text Input | **WAJIB** | `VARCHAR(150)` (Nama kandung) |
| 33 | **NIK Ibu** | Number / Text | Opsional | `CHAR(16)` |
| 34 | **Tahun lahir ibu** | Number Input | Opsional | `YEAR` (YYYY) |
| 35 | **Pendidikan** | Dropdown | **WAJIB** | Lookup: `ref_jenjang_pendidikan` |
| 36 | **Pekerjaan ibu** | Dropdown | **WAJIB** | Lookup: `ref_pekerjaan` |
| 37 | **Berkebutuhan khusus ibu** | Dropdown | Opsional | Lookup: `ref_kebutuhan_khusus` |
| 38 | **Penghasilan ibu** | Dropdown | **WAJIB** | Lookup: `ref_penghasilan` |

---

### Bagian 4: Data Wali (Halaman 4)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 39 | **Mempunyai wali** | Radio Button | Opsional | `Ya` (1) / `Tidak` (0) |
| *39a* | *Nama wali* | Text Input | Kondisional | Muncul jika "Mempunyai wali" = Ya |
| *39b* | *NIK Wali* | Number / Text | Kondisional | `CHAR(16)` |
| *39c* | *Tahun lahir wali* | Number Input | Kondisional | `YEAR` (YYYY) |
| *39d* | *Pendidikan wali* | Dropdown | Kondisional | Lookup: `ref_jenjang_pendidikan` |
| *39e* | *Pekerjaan wali* | Dropdown | Kondisional | Lookup: `ref_pekerjaan` |
| *39f* | *Penghasilan wali* | Dropdown | Kondisional | Lookup: `ref_penghasilan` |
| *39g* | *Berkebutuhan khusus wali* | Dropdown | Kondisional | Lookup: `ref_kebutuhan_khusus` |

---

### Bagian 5: Kontak (Halaman 4)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 40 | **Nomor telepon Rumah** | Number Input | Opsional | `VARCHAR(20)` |
| 41 | **Nomor HP** | Number / Text | Opsional | `VARCHAR(20)` |
| 42 | **Email** | Email / Text | Opsional | `VARCHAR(100)` |

---

### Bagian 6: Prestasi (Halaman 4-5)
*(Tabel multi-baris / dynamic rows)*

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 43 | **Jenis Prestasi** | Dropdown | Opsional | `Sains`, `Seni`, `Olahraga`, `Lain-lain` |
| 44 | **Tingkat Prestasi** | Dropdown | Opsional | `Sekolah`, `Kecamatan`, `Kab/kota`, `Propinsi`, `Nasional`, `Internasional`, `Lainnya` |
| 45 | **Nama Prestasi** | Number / Text | Opsional | `VARCHAR(200)` |
| 46 | **Tahun Prestasi** | Number Input | Opsional | `YEAR` (YYYY) |
| 47 | **Penyelenggara** | Number / Text | Opsional | `VARCHAR(150)` |
| 48 | **Peringkat** | Number Input | Opsional | Angka juara (1, 2, 3, dsb.) |

---

### Bagian 7: Kesejahteraan (Halaman 5)
*(Tabel multi-baris / dynamic rows)*

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 49 | **Jenis Kesejahteraan** | Dropdown | Opsional | Lookup: `ref_kesejahteraan` |
| 50 | **Nomor Kartu** | Number / Text | Opsional | `VARCHAR(40)` |
| 51 | **Nama Di Kartu** | Text Input | Opsional | `VARCHAR(150)` |
| 52 | **Tahun Mulai** | Number Input | Opsional | `YEAR` (YYYY) |
| 53 | **Tahun Selesai** | Number Input | Opsional | `YEAR` (YYYY) |

---

### Bagian 8: Pendaftaran Masuk (Halaman 5)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 54 | **Jenis pendaftaran** | Dropdown | **WAJIB** | Lookup: `ref_jenis_pendaftaran` |
| 55 | **Nomor Induk PD / NIS** | Number / Text | Opsional | `VARCHAR(30)` |
| 56 | **Tanggal masuk sekolah** | Datepicker | Opsional | Format `YYYY-MM-DD` |
| 57 | **Program/Kompetensi Keahlian** | Dropdown | Opsional | Relasi Program Keahlian / Jurusan |
| 58 | **Sekolah asal** | Text Input | Opsional | `VARCHAR(150)` |
| 59 | **Apakah pernah PAUD Formal (TK)** | Radio Button | **WAJIB** | `Ya` (1) / `Tidak` (0) |
| 60 | **Apakah pernah PAUD Non Formal (KB/TPA/SPS)** | Radio Button | **WAJIB** | `Ya` (1) / `Tidak` (0) |

---

### Bagian 9: Minat & Bakat (Hobby & Cita-cita) (Halaman 5-6)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Mapping / Referensi |
| :---: | :--- | :--- | :--- | :--- |
| 61 | **Hobby** | Dropdown | Opsional | Lookup: `ref_hobi` |
| 62 | **Cita-cita** | Dropdown | Opsional | Lookup: `ref_cita_cita` |

---

### Bagian 10: Data Periodik Peserta Didik (Halaman 6)

| Urutan | Label Isian Formulir | Tipe Kontrol UI | Validasi | Satuan & Format Data |
| :---: | :--- | :--- | :--- | :--- |
| 63 | **Tinggi badan (cm)** | Number / Text | **WAJIB** | Satuan: cm (`SMALLINT`) |
| 64 | **Berat badan (kg)** | Number / Text | **WAJIB** | Satuan: kg (`SMALLINT`) |
| 65 | **Lingkar kepala** | Number / Text | Opsional | Satuan: cm (`SMALLINT`) |
| 66 | **Jarak rumah ke sekolah** | Radio Button | **WAJIB** | `Kurang dari 1 km` / `Lebih dari 1 km` |
| 67 | **Sebutkan (dalam kilometer)** | Number / Text | Opsional | `DECIMAL(5, 2)` (aktif jika > 1 km) |
| 68 | **Waktu tempuh ke sekolah (jam/menit)** | Number / Text (2 kolom) | Opsional | Kolom 1: Jam (`INT`), Kolom 2: Menit (`INT`) |
| 69 | **Jumlah saudara kandung** | Number / Text | **WAJIB** | `TINYINT` |

---

## 3. FIELD DAPODIK TAMBAHAN (ATRIBUT PUSAT BELUM TERPETAKAN DI FORM FISIK)

Atribut berikut merupakan standar bawaan entitas Dapodik pusat (`get_peserta_didik` dan `registrasi_peserta_didik`) yang tidak dicantumkan di lembar fisik revisi 1, namun direkomendasikan tetap disediakan pada skema database atau tab lanjutan sistem SAE:

| No | Nama Atribut Database | Tipe Data | Keterangan Sistem Dapodik |
| :---: | :--- | :--- | :--- |
| 1 | `peserta_didik_id` | `UUID` | Primary Key Dapodik pusat untuk relasi rombel dan anggota rombel |
| 2 | `registrasi_id` | `UUID` | ID unik riwayat pendaftaran masuk siswa di Dapodik |
| 3 | `sekolah_id` | `UUID` | Foreign Key ke tabel profil sekolah |
| 4 | `penerima_kip` | `BOOLEAN` | Status flag verifikasi penerima Kartu Indonesia Pintar (KIP) |
| 5 | `nomor_kip` | `VARCHAR(40)` | Nomor seri resmi KIP jika kartu fisik sudah diterbitkan |
| 6 | `tertera_di_kip` | `VARCHAR(150)` | Nama siswa persis sebagaimana tercetak pada fisik KIP |
| 7 | `layak_pip` | `BOOLEAN` | Flag usulan sekolah untuk kelayakan penerima dana PIP |
| 8 | `id_layak_pip` / `alasan_layak_pip` | `VARCHAR(100)` | Alasan usulan PIP (misal: Pemegang KKS, Yatim Piatu, Terdampak Bencana) |
| 9 | `kode_wilayah` | `CHAR(8)` | Kode referensi wilayah Kemendikbudristek (Kecamatan/Kelurahan) |
| 10 | `is_sync_to_server` | `BOOLEAN` | Flag status sinkronisasi rekaman data ke server pusat Dapodik |

---

## 4. REKOMENDASI ARSITEKTUR DATABASE SAE

```text
ref_* (Shared Master Lookups)
├── ref_kebutuhan_khusus
├── ref_agama
├── ref_tempat_tinggal
├── ref_transportasi
├── ref_jenjang_pendidikan
├── ref_pekerjaan
├── ref_penghasilan
├── ref_jenis_pendaftaran
├── ref_hobi
└── ref_cita_cita

Core Student Tables
├── dapodik_peserta_didik (Profil Siswa, Alamat, Orang Tua, Wali, Kontak, Minat)
├── dapodik_peserta_didik_periodik (Tinggi, Berat, Lingkar Kepala, Jarak/Waktu, Sdr Kandung)
├── dapodik_registrasi_peserta_didik (Registrasi Masuk, NIS/NIPD, Asal Sekolah, PAUD)
├── dapodik_peserta_didik_prestasi (Riwayat Prestasi [1:M])
└── dapodik_peserta_didik_kesejahteraan (Riwayat Kartu Bantuan Sosial [1:M])
```

## 5. SUSUNAN FORMULIR PESERTA DIDIK (REVISI 1)
Data Pribadi
•	Nama: Text (WAJIB)
•	Jenis kelamin: Radio Button (WAJIB)
•	NISN: Number/Text
•	Kewarganegaraan: Dropdown (WAJIB)
•	NIK: Number/Text
•	No KK: Number/Text
•	Tempat lahir: Text
•	Tanggal lahir: Date
•	No Registrasi Akta Lahir: Text
•	Berkebutuhan khusus: Dropdown
•	Agama & Kepercayaan: Dropdown (WAJIB)
•	Alamat jalan: Text (WAJIB)
•	RT: Number
•	RW: Number
•	Nama dusun: Text
•	Desa/Kelurahan: Dropdown
•	Kode pos: Number
•	Lintang: Number/Text
•	Bujur: Number/Text
•	Tempat tinggal: Dropdown (WAJIB)
•	Moda transportasi: Dropdown (WAJIB)
•	Anak ke-berapa (berdasarkan KK): Number (WAJIB)
Data Ayah Kandung
•	Status Hidup: Radio Button
•	Nama ayah: Text
•	NIK Ayah: Number/Text
•	Tahun lahir ayah: Number
•	Pendidikan ayah: Dropdown 
•	Pekerjaan ayah: Dropdown (WAJIB)
•	Berkebutuhan khusus ayah: Dropdown
•	Penghasilan ayah: Dropdown (WAJIB)
Data Ibu Kandung
•	Status Hidup: Radio Button
•	Nama ibu: Text (WAJIB)
•	NIK Ibu: Number/Text
•	Tahun lahir ibu: Number
•	Pendidikan: Dropdown (WAJIB)
•	Pekerjaan ibu: Dropdown (WAJIB)
•	Berkebutuhan khusus ibu: Dropdown
•	Penghasilan ibu: Dropdown (WAJIB)
Data Wali
•	Mempunyai wali: Radio Button
Kontak
•	Nomor telepon Rumah: Number
•	Nomor HP: Number/Text 
•	Email: Email/Text
Prestasi
•	Jenis Prestasi: Dropdown
•	Tingkat Prestasi: Dropdown
•	Nama Prestasi: Number/Text
•	Tahun Prestasi: Number
•	Penyelenggara: Number/Text
•	Peringkat: Number
Kesejahteraan
•	Jenis Kesejahteraan: Dropdown
•	Nomor Kartu: Number/Text
•	Nama Di Kartu : Text
•	Tahun Mulai: Number
•	Tahun Selesai: Number
Pendaftaran Masuk
•	Jenis pendaftaran: Dropdown (WAJIB)
•	Nomor Induk PD / NIS: Number/Text
•	Tanggal masuk sekolah: Date
•	Program/Kompetensi Keahlian: Dropdown
•	Sekolah asal: Text
•	Apakah pernah PAUD Formal (TK): Radio Button (WAJIB)
•	Apakah pernah PAUD Non Formal (KB/TPA/SPS): Radio Button (WAJIB)
•	Hobby: Dropdown   
•	Cita-cita: Dropdown    
Data Periodik Peserta Didik
•	Tinggi badan (cm): Number/Text (WAJIB)
•	Berat badan (kg): Number/Text (WAJIB)
•	Lingkar kepala: Number/Text
•	Jarak rumah ke sekolah: Radio Button (WAJIB)
•	Sebutkan (dalam kilometer): Number/Text
•	Waktu tempuh ke sekolah (jam / menit): Number/Text (terdapat dua kolom isian untuk jam dan menit)
•	Jumlah saudara kandung: Number/Text (WAJIB)