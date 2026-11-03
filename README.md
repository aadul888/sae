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

- PHP `>= 8.3` (Laravel 13)
- Ekstensi PHP wajib: `pdo_mysql`, `zip`, `gd` (ekstensi lain sudah menyatu di PHP 8.3)
- MySQL `>= 8.0` atau MariaDB `>= 10.4`
- Composer `>= 2.2`
- Git (opsional, direkomendasikan untuk fitur Update Center)

---

## 📚 Dokumentasi Pengembangan

Dokumentasi pengembangan lengkap (arsitektur, standar UI, konvensi kode, RBAC,
panduan modul, upgrade server) berada di folder **`.agents/docs/`** — folder ini
**sengaja tidak masuk repositori** agar repo tetap bersih (hanya `README.md` ini
yang berupa markdown).

| Dokumen di `.agents/docs/` | Isi |
|---|---|
| `README.md` | Indeks dokumentasi — mulai dari sini |
| `ARSITEKTUR_LENGKAP.md` | Arsitektur sistem menyeluruh dari hulu ke hilir |
| `UPGRADE_SERVER_AAPANEL.md` | Panduan upgrade server aaPanel (PHP 8.3, composer, migrasi, rollback) |
| `UI_DESIGN_STANDARDS.md` | **Wajib dibaca** sebelum mengubah antarmuka: design token, komponen, konsistensi |
| `PANDUAN_PEMBUATAN_MODUL_DAN_FITUR.md` | Alur baku menambah modul/fitur baru |
| `code-style-guide.md` | Konvensi penulisan kode |
| `PROJECT_STRUCTURE.md` | Peta struktur folder & arsitektur |
| `MODULE_DEPENDENCIES.md` | Ketergantungan antar modul |
| `HAK_AKSES.md` | Cara kerja RBAC, hak akses, sidebar |
| `WORKLOG.md` | Riwayat keputusan teknis & hasil tes |

> **Catatan AI Agent:** petunjuk kerja + peta dokumen ada di `.agents/AGENTS.md`,
> cheat sheet modul di `.agents/skills/SKILL.md`.

### Ringkasan Standar UI

Seluruh tampilan memakai **design token** dari `public/css/tokens.css` dan **komponen Blade** dari `resources/views/components/sae/`.

```blade
{{-- Jangan menulis nilai mentah seperti style="border-radius: 10px" --}}
<x-sae.page-header title="Data Siswa" subtitle="Daftar peserta didik" icon="fas fa-users">
    <x-slot:actions>
        <x-sae.button variant="primary" icon="fas fa-plus">Tambah</x-sae.button>
    </x-slot:actions>
</x-sae.page-header>

<x-sae.card title="Ringkasan">
    <div class="sae-stat-grid">
        <x-sae.stat :value="42" label="Total" icon="fas fa-users" />
    </div>
</x-sae.card>
```

Urutan pemuatan CSS wajib: `tokens.css` → `components.css` → `sae.css` → CSS modul.
Dijaga otomatis oleh `DesignConsistencyTest`.

> **Penting saat menjalankan test:** jalankan `php artisan config:clear` lebih dulu.
> `config:cache` mem-bake nilai `.env` sehingga mengalahkan `CACHE_DRIVER=array` pada
> `phpunit.xml` dan menyebabkan kegagalan test palsu.

---

## 🛠️ Panduan Instalasi (Fresh Install)

> **⚠️ PERHATIAN KEAMANAN PENTING (DocumentRoot):**
> Titik masuk publik (_entry point_) aplikasi Laravel berada di folder **`public/`**.
> Konfigurasikan **DocumentRoot** web server Anda agar mengarah langsung ke folder **`public/`** (contoh: `/var/www/sae/public`).
> Jangan pernah membuka akses root proyek langsung ke internet. Root `.htaccess` hanya berlaku di Apache/LiteSpeed; di **nginx** proteksi wajib lewat server block (lihat bagian **Hardening Nginx** di bawah).

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
    - Tab **URL Rewrite**: Pilih preset **Laravel 5** -> Klik **Save**.
      (setara dengan `location / { try_files $uri $uri/ /index.php?$query_string; }`)
    - Tab **Web Server**: pastikan **WebEngine = Nginx**.
4. **PENTING — batasi eksekusi PHP (anti RCE berkas unggahan):**
   aaPanel bawaan memakai handler generik `location ~ [^/]\.php(/|$)` yang mengeksekusi
   berkas `.php` **di mana saja**, termasuk di folder unggahan. Ganti menjadi hanya
   `/index.php`. Lihat bagian **aaPanel: Menutup Eksekusi PHP di Folder Unggahan**.
5. **Buka Browser:**
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
   Jangan tulis manual — pakai template yang sudah dikurasi di repo (`nginx.conf`), lalu sesuaikan `server_name` + `fastcgi_pass`:

    ```bash
    cp nginx.conf /etc/nginx/conf.d/sae.conf
    # edit server_name dan fastcgi_pass di dalamnya
    nginx -t && systemctl reload nginx
    ```

   Isi pentingnya (nilai minimum yang wajib ada):

    ```nginx
    root /var/www/sae/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP hanya untuk entry point. Berkas .php lain (termasuk di folder unggahan) ditolak.
    location = /index.php {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ \.php$ { deny all; }
    ```

    Lihat **Hardening Nginx** di bagian akhir dokumen untuk aturan lengkap.

3. **Buka Web Wizard:**
   Akses `https://domain-anda.sch.id` di browser untuk menyelesaikan setup via GUI.

---

## 🔒 Hardening Nginx (keamanan berkas unggahan)

Berkas unggahan pengguna disimpan di `storage/app/public/` (dibuka lewat symlink
`public/storage`) dan sebagian di `public/uploads/`. Aplikasi sudah menolak ekstensi
berbahaya dan meng-encode ulang gambar, tetapi **server harus jadi lapis kedua**:

1. **PHP hanya boleh dijalankan untuk `/index.php`.** Bila `location ~ \.php$` cocok
   dengan `.php` di mana saja, berkas `.php` yang lolos ke folder unggahan akan
   dieksekusi penuh. Aturan yang benar ada di `nginx.conf`:
    ```nginx
    location = /index.php { fastcgi_pass ...; }   # entry point
    location ~ \.php$ { deny all; return 403; }   # sisanya ditolak
    ```
2. **`autoindex off;`** supaya isi folder unggahan tidak bisa dienumerasi.
3. **Jangan blokir `/storage/`.** Sebagian template lama menaruh `storage` di daftar
   `deny all` — itu membuat semua foto & berkas siswa 404. Yang perlu diblokir hanya
   berkas sensitif di dalamnya: `/storage/(logs|framework|app/private|app/archives)/`.
4. **`.htaccess` tidak berlaku di nginx.** Penjaga `.htaccess` yang ditulis otomatis oleh
   `ImageOptimizerService` hanya untuk Apache/OpenLiteSpeed (nginx mengabaikannya,
   jadi berkas itu tidak ditulis saat server terdeteksi nginx).

### aaPanel: Menutup Eksekusi PHP di Folder Unggahan

Berkas vhost aaPanel ada di `/www/server/panel/vhost/nginx/<domain>.conf`.
Handler PHP default-nya `include enable-php-83.conf;` yang isinya:

```nginx
# /www/server/nginx/conf/enable-php-83.conf  (JANGAN diubah global — dipakai semua situs)
location ~ [^/]\.php(/|$) {
    fastcgi_pass unix:/tmp/php-cgi-83.sock;
    ...
}
```

Pola `[^/]\.php(/|$)` mengeksekusi **setiap** `.php` di bawah DocumentRoot. Ganti
**khusus untuk situs ini** — di vhost milik domain Anda, hapus baris
`include enable-php-83.conf;` lalu tempel:

```nginx
# PHP hanya untuk entry point Laravel
location = /index.php {
    fastcgi_pass unix:/tmp/php-cgi-83.sock;   # sesuaikan socket PHP-83 aaPanel
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_read_timeout 300;
}

# Semua .php lain (termasuk di folder unggahan) tidak boleh dieksekusi
location ~ \.php$ {
    deny all;
    return 403;
}

autoindex off;   # cegah enumerasi isi folder unggahan
```

Terapkan:

```bash
nginx -t && systemctl reload nginx
```

**Catatan aaPanel:** mengubah **PHP version** atau pengaturan situs dari panel dapat
menulis ulang baris `include` tersebut. Setelah perubahan apa pun di panel, ulangi
langkah ini — sumber acuan tetap `nginx.conf` di repo. Bila situs beralih WebEngine ke
**OpenLiteSpeed**, `.htaccess` penjaga akan otomatis berlaku (OLS membacanya).

Verifikasi cepat setelah reload:

```bash
# harus 403 / 404, BUKAN dieksekusi
echo '<?php echo "RCE";' > public/uploads/probe.php
curl -i https://domain-anda.sch.id/uploads/probe.php
rm public/uploads/probe.php

# foto harus 200
curl -I https://domain-anda.sch.id/storage/assets/sekolah/<nama-file-logo>
```

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
      Ekstrak/clone file ke dalam `public_html`. Root `.htaccess` khusus (berlaku hanya di **Apache/LiteSpeed**) memproteksi file sensitif (`.env`, `storage`, `database/`) dan mengalihkan request pengunjung ke `/public`. Bila server Anda **nginx**, gunakan server block dari `nginx.conf` (lihat **Hardening Nginx**).

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
