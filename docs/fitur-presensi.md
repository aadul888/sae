# Rancangan Sistem Presensi Peserta Didik Terintegrasi Database

Dokumen ini menyajikan kerangka kerja dan spesifikasi komprehensif untuk implementasi Sistem Presensi Peserta Didik Terintegrasi Database. Sistem ini dirancang untuk mengotomatisasi pencatatan kehadiran, meningkatkan akurasi data melalui verifikasi ganda, serta menyediakan visibilitas operasional secara real-time bagi pihak sekolah, pengajar, dan orang tua/wali murid.

---

### 1. Metode Otentikasi & Identifikasi Multimoda

* **Kartu RFID (Radio Frequency Identification):** Menggunakan kartu fisik tanpa kontak (*contactless*) untuk proses pencatatan presensi yang cepat di gerbang/pintu masuk sekolah.
* **QR Code Dinamis / Kartu Pelajar Digital:** Kode QR terpindai dari aplikasi mobile siswa yang diperbarui secara berkala guna mencegah penggunaan tangkapan layar (*screenshot* / penitipan presensi).
* **Verifikasi Visual & Live Camera Capture:** Pengambilan foto otomatis saat verifikasi identitas di mesin pemindai untuk memastikan kecocokan fisik peserta didik secara real-time.

---

### 2. Manajemen Hak Akses & Manajemen Peran (RBAC)

* **Administrator Sistem:** Memiliki akses penuh terhadap konfigurasi master data (peserta didik, guru, kelas), manajemen kartu RFID, dan pengaturan jadwal/shift.
* **Wali Kelas & Guru Mata Pelajaran:** Mengakses Dasbor Kelas untuk verifikasi manual, pengajuan izin/sakit, koreksi data presensi harian, serta pencatatan kehadiran di jam pelajaran khusus.
* **Pengurus Kelas (Ketua & Sekretaris):** Memiliki portal khusus untuk memantau kehadiran harian teman sekelas dan melakukan rekapitulasi mandiri sebelum divalidasi oleh Wali Kelas.

---

### 3. Konfigurasi Jam Kerja, Kalender, & Logika Kategori Kehadiran

* **Integrasi Kalender Pendidikan:** Sinkronisasi otomatis dengan kalender akademis untuk mengakomodasi jadwal ujian, kegiatan ekstrakurikuler, dan hari libur nasional/khusus.
* **Pengaturan Fleksibel Jam Masuk/Pulang:** Dukungan konfigurasi hari aktif (termasuk Sabtu/Minggu) yang dilengkapi fitur *toggle switch button* untuk penetapan hari libur mendadak atau perubahan jadwal.
* **Kategori Ketepatan Waktu:** Klasifikasi otomatis menjadi Masuk Tepat Waktu, Terlambat (dengan perhitungan menit keterlambatan), Pulang Cepat, dan Pulang Tepat Waktu.
* **Status Kehadiran Legal:** Pencatatan status formal yang terdiri dari Hadir, Izin (dengan lampiran dokumen pendukung), Sakit (dengan surat dokter), dan Alpha (Tanpa Keterangan).

---

### 4. Arsitektur Teknis, Keamanan Data, & Notifikasi Real-Time

* **Basis Data Central & Enkripsi Data:** Seluruh log presensi tersimpan dalam database terpusat dengan enkripsi SSL/TLS saat transit data dan enkripsi AES-256 untuk data sensitif.