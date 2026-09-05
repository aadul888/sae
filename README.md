# Sistem Aplikasi Edukasi (SAE)

Platform Sistem Informasi & Administrasi Digital Sekolah terintegrasi Dapodik Kemendikbudristek, Presensi RFID, dan Sistem Pembaruan Otomatis.

---

## 🚀 Fitur Utama

- **Instalasi Otomatis (Web Wizard):** Konfigurasi `.env`, migrasi database, dan akun admin instan via browser (`/install`).
- **Sinkronisasi Dapodik:** Tarik dan sinkronisasi data Sekolah, GTK, Rombongan Belajar, dan Peserta Didik dengan skema UUID Dapodik murni.
- **Manajemen Akun Otomatis:** Pembuatan akun siswa instan (Username & Password: NISN).
- **Pembaruan Sistem Otomatis (Update Center):** Deteksi commit remote GitHub, eksekusi migrasi database, sinkronisasi file kode, dan flush cache sekali klik.
- **Multi-Role Dashboard:** Akses terpisah untuk Administrator, Guru / Tendik, dan Siswa.
- **Modern Responsive UI:** Dukungan mode Gelap / Terang (Dark/Light Mode), SweetAlert2 interaktif, dan navigasi mobile.

---

## 📋 Persyaratan Sistem

- PHP `>= 8.2` (Rekomendasi PHP 8.3)
- Ekstensi PHP: `BCMath`, `Ctype`, `cURL`, `DOM`, `Fileinfo`, `JSON`, `Mbstring`, `OpenSSL`, `PDO`, `PDO_MySQL`, `Tokenizer`, `XML`, `Zip`
- MySQL `>= 8.0` atau MariaDB `>= 10.4`
- Composer `>= 2.2`
- Git (opsional, direkomendasikan untuk fitur Update Center)

---

## 🛠️ Panduan Instalasi (Fresh Install)

### Metode 1: Instalasi Cepat via Web Wizard (Rekomendasi Hosting & VPS)

1. **Upload / Clone Project ke Server**
   ```bash
   git clone https://github.com/aadul888/sae.git
   cd sae
   composer install --no-dev --optimize-autoloader
   ```

2. **Set Izin Folder (Permissions)**
   Pastikan folder `storage` dan `bootstrap/cache` memiliki izin tulis (*writable*):
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

3. **Buka Web Browser**
   - Akses domain/IP server Anda (contoh: `https://sekolah-anda.sch.id` atau `http://localhost/sae/public`).
   - Sistem akan otomatis mengarahkan ke halaman wizard: `http://domain-anda/install`.

4. **Lengkapi Form Instalasi**
   - Masukkan informasi koneksi database (Host, Port, Nama Database, Username, Password).
   - Masukkan nama instansi/sekolah dan akun Administrator.
   - Klik **Pasang Sistem Sekarang**. Sistem akan membuat file `.env`, `APP_KEY`, skema database, dan akun admin.

---

### Metode 2: Instalasi Manual via Terminal / CLI

1. **Clone dan Install Dependensi**
   ```bash
   git clone https://github.com/aadul888/sae.git
   cd sae
   composer install
   ```

2. **Konfigurasi Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan konfigurasi database pada file `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_sae
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Migrasi Database & Seeder**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```

4. **Jalankan Aplikasi**
   ```bash
   php artisan serve
   ```

---

## 🔑 Akun Default Awal

| Role | Username / Identifier | Password Default |
| :--- | :--- | :--- |
| **Administrator** | Dibuat saat wizard / `admin` | Sesuai saat instalasi / `password` |
| **Guru / Tendik** | NUPTK / Email GTK | `123456` (dapat diubah) |
| **Siswa** | NISN Siswa | NISN Siswa |

---

## 🔄 Pembaruan Sistem (Update Center)

1. Masuk ke Dashboard sebagai **Administrator**.
2. Buka menu **Update Sistem** (`/dashboard/update`).
3. Klik **Periksa Pembaruan** untuk mengecek versi terbaru dari repositori GitHub.
4. Jika pembaruan tersedia, klik tombol **Pasang Sekarang**. Sistem akan menarik perubahan kode, menjalankan skema migrasi database baru, dan membersihkan cache secara otomatis.

---

## 📄 Lisensi

Sistem Aplikasi Edukasi (SAE) dilindungi hak cipta di bawah lisensi [MIT](LICENSE).

