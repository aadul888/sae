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

1. **Clone Langsung ke Root Direktori Web / Hosting**
   Masuk ke folder root hosting Anda (misalnya `public_html` atau `/var/www/html`):

    ```bash
    # Clone langsung ke folder saat ini (titik di ujung)
    git clone https://github.com/aadul888/sae.git .
    composer install --no-dev --optimize-autoloader
    ```

2. **Set Izin Folder (Permissions)**
   Pastikan folder `storage` dan `bootstrap/cache` memiliki izin tulis (_writable_):

    ```bash
    chmod -R 775 storage bootstrap/cache
    ```

3. **Buka Web Browser**
    - Akses domain/IP server Anda (contoh: `https://sekolah-anda.sch.id` atau `http://localhost`).
    - Sistem akan otomatis mengarahkan ke halaman wizard: `http://domain-anda/install`.

4. **Lengkapi Form Instalasi**
    - Masukkan informasi koneksi database (Host, Port, Nama Database, Username, Password).
    - Masukkan nama instansi/sekolah dan akun Administrator.
    - Klik **Mulai Instalasi & Migrasi**. Sistem akan otomatis membuat database, file `.env`, `APP_KEY`, dan migrasi data awal.

---

### Metode 2: Instalasi Manual via Terminal / CLI

1. **Clone Langsung ke Root Direktori dan Install Dependensi**

    ```bash
    git clone https://github.com/aadul888/sae.git .
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

## 🔑 Akun Default Setelah Instalasi

| Role              | Username / Email         | Password Default        |
| :---------------- | :----------------------- | :---------------------- |
| **Administrator** | `admin@sae.id` / `admin` | `Admin543!`             |
| **Guru / Tendik** | `gtk@sae.id` / NUPTK     | `Geteka543!` / `123456` |
| **Siswa**         | `siswa@sae.id` / NISN    | `Siswa543!` / NISN      |

> **Catatan:** Segera ganti password akun setelah berhasil login pertama kali.

---

## 🔁 Konfigurasi Ulang Sistem

Jika ingin mengulang proses instalasi atau mengganti database server:

1. Hapus file `.env` di root folder (`rm .env`).
2. Akses kembali halaman installer melalui browser: `http://domain-anda/install`.
3. Masukkan kredensial database baru dan klik tombol instalasi.

---

## 🔄 Pembaruan Sistem (Update Center)

1. Masuk ke Dashboard sebagai **Administrator**.
2. Buka menu **Update Sistem** (`/dashboard/update`).
3. Klik **Periksa Pembaruan** untuk mengecek versi terbaru dari repositori GitHub.
4. Jika pembaruan tersedia, klik tombol **Pasang Sekarang**. Sistem akan menarik perubahan kode, menjalankan skema migrasi database baru, dan membersihkan cache secara otomatis.

---

## 📄 Lisensi

Sistem Aplikasi Edukasi (SAE) dilindungi hak cipta di bawah lisensi [MIT](LICENSE).
