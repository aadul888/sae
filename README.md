# Sistem Aplikasi Edukasi (SAE)

Platform Sistem Informasi & Administrasi Digital Sekolah terintegrasi Dapodik Kemendikbudristek, Presensi RFID, dan Sistem Pembaruan Otomatis.

---

## 🚀 Fitur Utama

- **Instalasi Otomatis (Web Wizard):** Konfigurasi `.env`, migrasi database, dan akun admin instan via browser (`/install`).
- **Sinkronisasi Dapodik:** Tarik dan sinkronisasi data Sekolah, GTK, Rombongan Belajar, dan Peserta Didik dengan skema UUID Dapodik murni.
- **Manajemen Akun Otomatis:** Pembuatan akun peserta didik instan (Username & Password: NISN).
- **Pembaruan Sistem Otomatis (Update Center):** Deteksi commit remote GitHub, eksekusi migrasi database, sinkronisasi file kode, dan flush cache sekali klik.
- **Multi-Role Dashboard:** Akses terpisah untuk Administrator, Guru, Tendik, dan Peserta Didik.
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

> **⚠️ PERHATIAN KEAMANAN PENTING (DocumentRoot):**
> Titik masuk publik (_entry point_) aplikasi Laravel berada di folder **`public/`**.
> Konfigurasikan **DocumentRoot** web server Anda (Nginx/Apache/LiteSpeed) agar mengarah langsung ke folder **`public/`** (contoh: `/var/www/sae/public` atau `/home/user/public_html/public`).
> Jangan pernah membuka akses root proyek langsung ke internet tanpa pengalihan DocumentRoot atau proteksi `.htaccess`.

---

### Skenario A: Instalasi di VPS (Ubuntu / Debian / CentOS / aaPanel)

1. **Clone Repositori ke Direktori Web**

    ```bash
    cd /var/www
    git clone https://github.com/aadul888/sae.git
    cd sae
    ```

2. **Install Dependensi Composer**

    ```bash
    composer install --no-dev --optimize-autoloader
    ```

3. **Atur Hak Akses & Kepemilikan Folder (Permissions)**
   Gunakan script otomatisasi yang sudah disediakan:

    ```bash
    chmod +x deploy.sh
    ./deploy.sh
    ```

    _Atau atur manual sesuai user web server Anda:_

    ```bash
    # User webserver: www-data (Ubuntu/Debian), www (aaPanel), nginx (CentOS)
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    ```

4. **Konfigurasi Web Server (Arahkan ke `/public`)**
    - **Nginx:** Salin atau gunakan template `nginx.conf` yang tersedia di root proyek. Pastikan baris root mengarah ke:
        ```nginx
        root /var/www/sae/public;
        index index.php;
        ```
    - **Apache / LiteSpeed:** Pastikan modul `mod_rewrite` aktif dan `DocumentRoot` mengarah ke `/var/www/sae/public`. File `public/.htaccess` sudah tersedia bawaan.

5. **Jalankan Web Wizard**
    - Buka browser dan akses: `https://domain-anda.sch.id`
    - Sistem akan otomatis mengarahkan ke halaman instalasi: `https://domain-anda.sch.id/install`
    - Masukkan informasi koneksi database MySQL Anda. Wizard akan otomatis menguji koneksi, membuat database (jika belum ada), menulis file `.env`, mengimpor skema awal, dan menautkan storage link.

---

### Skenario B: Instalasi di Shared Hosting (cPanel / DirectAdmin / Hostinger)

1. **Buat Database MySQL di Panel Hosting**
    - Buka menu **MySQL Databases** di cPanel.
    - Buat database baru (misal: `sekolah_sae`) dan buat user MySQL beserta passwordnya.
    - Sambungkan user ke database dengan mencentang izin **ALL PRIVILEGES**.

2. **Upload / Clone Kode Sumber**
    - **Opsi 1 (Paling Aman - Subdomain / Addon Domain):**
      Saat menambahkan domain di cPanel, atur Document Root ke folder `public_html/public` atau folder khusus seperti `sae/public`.
    - **Opsi 2 (Domain Utama `public_html`):**
      Ekstrak/clone file ke dalam `public_html`. Sistem SAE sudah menyertakan root `.htaccess` khusus yang secara otomatis memproteksi file sensitif (`.env`, `storage`, `database/`) dan mengalihkan request pengunjung ke `/public`.

3. **Install Dependensi via Terminal cPanel (atau Upload Vendor)**
   Jika hosting menyediakan akses SSH / Terminal:

    ```bash
    composer install --no-dev --optimize-autoloader
    chmod -R 775 storage bootstrap/cache
    ```

4. **Selesaikan Instalasi via Web Wizard**
    - Akses `https://domain-anda.sch.id` di browser.
    - Masukkan nama database, username, dan password MySQL yang sudah dibuat pada Langkah 1.
    - Klik **Mulai Instalasi & Migrasi**.

---

### Skenario C: Instalasi Manual via Terminal / CLI (Pengembang / Pengujian)

1. **Clone & Install Dependensi**

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

    Sesuaikan parameter database di file `.env`:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=db_sae
    DB_USERNAME=root
    DB_PASSWORD=
    ```

3. **Migrasi Database & Storage Symlink**

    ```bash
    # Impor database awal atau migrasi:
    php artisan migrate --force
    php artisan storage:link
    ```

4. **Jalankan Server Lokal**
    ```bash
    php artisan serve
    ```

---

## 🔑 Akun Default Setelah Instalasi

| Role              | Username / Email             | Password Default          |
| :---------------- | :--------------------------- | :------------------------ |
| **Administrator** | `admin@sae.id` / `admin`     | `Admin543!`               |
| **Guru / Tendik** | `gtk@sae.id` / NUPTK         | `Geteka543!` / `123456`   |
| **Peserta Didik** | `pesertadidik@sae.id` / NISN | `PesertaDidik543!` / NISN |

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
