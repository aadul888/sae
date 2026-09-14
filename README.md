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

### Skenario A: Instalasi di VPS (aaPanel / CyberPanel / CLI Ubuntu / Debian)

#### 1. Setup Melalui Panel Hosting (aaPanel / CyberPanel) — Paling Mudah:

1. **Tambah Situs Web (Add Website):**
    - Domain: `sae.smakpal.sch.id` (sesuaikan domain Anda).
    - PHP Version: `PHP 8.2` atau `PHP 8.3`.
2. **Kloning Kode Sumber:**
   Masuk ke menu **Files** atau Terminal panel, clone repositori ke root direktori situs:
    ```bash
    cd /www/wwwroot/sae.smakpal.sch.id
    git clone https://github.com/aadul888/sae.git .
    composer install --no-dev --optimize-autoloader
    chmod +x deploy.sh && ./deploy.sh
    ```
3. **⚠️ Konfigurasi Nginx Web Server (KUNCI MENCEGAH ERROR 404):**
    - Buka menu **Website** -> Klik nama domain Anda.
    - Tab **Site Directory**: Ubah **Running directory** dari `/` menjadi **`/public`** -> Klik **Save**.
    - Tab **URL Rewrite**: Pilih preset **Laravel 5** (atau tempel: `location / { try_files $uri $uri/ /index.php?$query_string; }`) -> Klik **Save**.
4. **Buka Browser:**
   Akses `https://domain-anda.sch.id` (otomatis redirect ke `/install`), masukkan info database, selesai!

---

#### 2. Setup Manual via Terminal VPS (Nginx Standalone):

1. **Clone Repositori & Install Dependensi:**
    ```bash
    cd /var/www
    git clone https://github.com/aadul888/sae.git
    cd sae
    composer install --no-dev --optimize-autoloader
    chmod +x deploy.sh && ./deploy.sh
    ```
2. **Konfigurasi Virtual Host Nginx:**
   Pastikan konfigurasi Nginx domain Anda (`/etc/nginx/sites-available/...`) memiliki:

    ```nginx
    root /var/www/sae/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    ```

    Lalu jalankan `nginx -t && systemctl reload nginx`.

3. **Buka Web Wizard:**
   Akses `https://domain-anda.sch.id` di browser untuk menyelesaikan setup via GUI.

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
