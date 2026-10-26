# Panduan Upgrade Server — aaPanel

Panduan langkah demi langkah memindahkan aplikasi SAE dari **Laravel 12 + PHP 8.2**
ke **Laravel 13 + PHP 8.3** di server yang dikelola aaPanel.

> **Penting:** Laravel 13 **mewajibkan PHP ≥ 8.3**. Bila server masih PHP 8.2,
> aplikasi akan error total setelah kode diperbarui. Karena itu **langkah 1–3
> (pasang PHP 8.3) harus selesai sebelum langkah 6 (tarik kode baru)**.

---

## Ringkasan Alur

```
[1] Backup penuh  →  [2] Pasang PHP 8.3  →  [3] Set PHP 8.3 di situs
                                              ↓
[6] Tarik kode  ←  [5] Siapkan akses git  ←  [4] Cek ekstensi PHP
      ↓
[7] composer install  →  [8] migrate  →  [9] cache  →  [10] uji
```

Perkiraan waktu: **20–40 menit** (paling lama di langkah 2 bila PHP 8.3 belum ada).

---

## Langkah 1 — Backup Penuh (JANGAN DILEWATI)

### 1.1 Backup database

1. Buka aaPanel → menu **Database**
2. Cari database aplikasi (mis. `db_sae`)
3. Klik **Backup** → tunggu hingga selesai
4. Klik **Download** pada hasil backup dan simpan ke komputer Anda

Atau lewat SSH:

```bash
cd /www/wwwroot/<domain-anda>
mysqldump -u root -p --single-transaction --routines db_sae > ~/backup-db-sae-$(date +%F).sql
ls -lh ~/backup-db-sae-*.sql
```

### 1.2 Backup berkas aplikasi

1. Buka aaPanel → **Files** → masuk ke `/www/wwwroot/`
2. Klik kanan folder situs → **Compress** → pilih `.tar.gz`
3. Download hasil kompresi

Atau lewat SSH:

```bash
cd /www/wwwroot
tar -czf ~/backup-sae-files-$(date +%F).tar.gz <nama-folder-situs> \
    --exclude='<nama-folder-situs>/vendor' \
    --exclude='<nama-folder-situs>/node_modules'
ls -lh ~/backup-sae-files-*.tar.gz
```

### 1.3 Catat konfigurasi saat ini

```bash
cd /www/wwwroot/<domain-anda>
php -v | head -n1
cp .env ~/backup-env-$(date +%F).txt
grep -E '^(APP_URL|DB_DATABASE|APP_ENV|APP_DEBUG)=' .env
```

**Berhenti di sini bila backup belum berhasil.** Tanpa backup, kegagalan di
langkah migrasi tidak bisa dipulihkan.

---

## Langkah 2 — Pasang PHP 8.3 di aaPanel

1. Buka aaPanel → **App Store** (kiri)
2. Cari **PHP 8.3** → klik **Install**
   - Pilih **Compile** bila ingin paling kompatibel (lebih lama, ±20 menit)
   - Pilih **Fast** bila ingin cepat
3. Tunggu hingga status **Running**

### 2.1 Set PHP 8.3 sebagai default CLI

Versi CLI dan versi web bisa berbeda. Periksa dulu:

```bash
php -v | head -n1
```

Bila masih 8.2, ubah lewat aaPanel:

1. **App Store** → **PHP 8.3** → **Setting**
2. Pada bagian **Cli Version** / **Command line**, pilih PHP 8.3
3. Simpan

Verifikasi:

```bash
php -v | head -n1
# harus menampilkan PHP 8.3.x
```

Bila aaPanel tidak menyediakan opsi CLI, buat symlink manual:

```bash
# cari lokasi php 8.3
ls -d /www/server/php/83/bin/php

# arahkan ulang (cadangkan dulu yang lama)
sudo mv /usr/bin/php /usr/bin/php.bak 2>/dev/null
sudo ln -s /www/server/php/83/bin/php /usr/bin/php

php -v | head -n1
```

---

## Langkah 3 — Pasang Ekstensi PHP 8.3

1. **App Store** → **PHP 8.3** → **Setting** → tab **Install extension**
2. Pasang yang belum ada:

```
bcmath  ctype  curl  dom  fileinfo  json  mbstring
openssl  pdo  pdo_mysql  tokenizer  xml  zip
```

3. Klik **Install** pada masing-masing, lalu **Reload** PHP 8.3

Verifikasi lewat SSH:

```bash
for e in bcmath ctype curl dom fileinfo json mbstring openssl pdo pdo_mysql tokenizer xml zip; do
  php -m | grep -qi "^$e$" && echo "OK   $e" || echo "HILANG $e"
done
```

Semua harus **OK** sebelum lanjut.

### 3.1 Naikkan batas upload (opsional, untuk impor Excel)

Di **PHP 8.3 → Setting → Configuration**:

| Setelan | Nilai disarankan |
|---|---|
| `upload_max_filesize` | `25M` |
| `post_max_size` | `30M` |
| `max_execution_time` | `300` |
| `memory_limit` | `512M` |

Impor peserta didik menerima berkas hingga **20 MB**, jadi batas di atasnya wajib lebih besar.

---

## Langkah 4 — Arahkan Situs ke PHP 8.3

1. aaPanel → **Website** → klik nama situs Anda
2. Tab **Site directory** → pastikan **Document Root** berakhir dengan `/public`

   ```
   /www/wwwroot/<domain-anda>/public      ← BENAR
   /www/wwwroot/<domain-anda>              ← SALAH (kode terekspos)
   ```

3. Tab **PHP version** → pilih **PHP-83** → **Save**
4. Tab **URL rewrite** → pilih **laravel5** (atau tempel isi `nginx.conf` proyek)

### 4.1 Bila memakai OpenLiteSpeed

1. **Website** → **Site directory** → Document Root ke `.../public`
2. **PHP version** → pilih PHP 8.3
3. **Rewrite** → aktifkan, tempel aturan Laravel

---

## Langkah 5 — Siapkan Akses Git

```bash
cd /www/wwwroot/<domain-anda>

# pastikan ini repo git dan remote benar
git remote -v

# bila folder .git dianggap "dubious ownership"
git config --global --add safe.directory /www/wwwroot/<domain-anda>

# lihat kondisi saat ini
git status
git log --oneline -1
```

### 5.1 Bila server belum punya repo git

Bila aplikasi dipasang manual (bukan `git clone`), buat cadangan lalu clone ulang:

```bash
cd /www/wwwroot
mv <domain-anda> <domain-anda>-old

git clone https://github.com/aadul888/sae.git <domain-anda>
cd <domain-anda>

# pulihkan konfigurasi & data dari folder lama
cp ../<domain-anda>-old/.env .
cp -r ../<domain-anda>-old/storage/app/* storage/app/
cp -r ../<domain-anda>-old/public/img/* public/img/ 2>/dev/null || true
cp -r ../<domain-anda>-old/public/downloads/* public/downloads/ 2>/dev/null || true
```

> **Jangan menyalin folder `vendor/` lama** — dependensi harus dipasang ulang
> agar sesuai Laravel 13.

---

## Langkah 6 — Tarik Kode Baru

```bash
cd /www/wwwroot/<domain-anda>

git fetch origin
git checkout main
git pull origin main

git log --oneline -3
```

Commit terakhir harus **`b55bb22 Dokumentasi Sesi 8: penguatan pondasi aplikasi`**
(atau lebih baru).

### 6.1 Bila `git pull` bentrok

```bash
# lihat file yang bentrok
git status

# simpan perubahan lokal lalu buang (kode produksi seharusnya tidak diedit manual)
git stash
git pull origin main
```

Bila masih gagal, paksa samakan dengan remote (**backup dulu!**):

```bash
git fetch origin
git reset --hard origin/main
```

---

## Langkah 7 — Pasang Dependensi (LANGKAH PALING PENTING)

```bash
cd /www/wwwroot/<domain-anda>
composer install --no-dev --optimize-autoloader
```

Tanpa langkah ini, kode sudah Laravel 13 tetapi folder `vendor/` masih Laravel 12
→ aplikasi akan error.

Verifikasi:

```bash
php artisan --version
# harus: Laravel Framework 13.38.0
```

Bila masih 12.x:

```bash
composer show laravel/framework | head -n3
composer install --no-dev --optimize-autoloader -v
```

### 7.1 Bila composer kehabisan memori

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

### 7.2 Bila composer lambat / timeout

```bash
composer config --global process-timeout 2000
```

---

## Langkah 8 — Migrasi Database

```bash
cd /www/wwwroot/<domain-anda>

# lihat dulu apa yang akan dijalankan (aman, tidak mengubah apa pun)
php artisan migrate --pretend

# jalankan
php artisan migrate --force
```

`--force` diperlukan karena `APP_ENV=production`.

Bila ada migrasi yang gagal:

1. **Jangan** menjalankan ulang berulang kali
2. Baca pesan error dengan teliti
3. Pulihkan backup bila perlu:

```bash
mysql -u root -p db_sae < ~/backup-db-sae-TANGGAL.sql
```

---

## Langkah 9 — Bersihkan & Bangun Ulang Cache

```bash
cd /www/wwwroot/<domain-anda>

# WAJIB: bersihkan cache config lama (berisi konfigurasi Laravel 12)
php artisan optimize:clear

# bangun ulang untuk produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# storage link
php artisan storage:link
```

> **Catatan `route:cache`:** bila gagal dengan pesan *"Unable to prepare route
> for serialization. Uses Closure"*, itu berarti ada route berbasis Closure.
> Aplikasi tetap berjalan normal tanpa route cache — lewati langkah ini.

### 9.1 Perbaiki izin berkas

```bash
cd /www/wwwroot/<domain-anda>

# ganti 'www' dengan user web server Anda (cek: ps aux | grep nginx)
chown -R www:www .
find . -type d -exec chmod 755 {} +
find . -type f -exec chmod 644 {} +
chmod -R 775 storage bootstrap/cache
```

### 9.2 Muat ulang PHP (wajib agar OPcache tidak menyajikan kode lama)

```bash
# PHP-FPM
systemctl reload php8.3-fpm     # atau: systemctl reload php-fpm

# OpenLiteSpeed (aaPanel sering memakai ini)
/usr/local/lsws/bin/lswsctrl restart
```

Di aaPanel: **App Store → PHP 8.3 → Reload**.

---

## Langkah 10 — Uji Aplikasi

### 10.1 Uji lewat browser

1. Buka `https://domain-anda/` → halaman beranda tampil
2. Buka `https://domain-anda/login` → form login tampil
3. **Login sebagai admin**
4. Periksa menu berikut (mewakili modul yang berubah):

| Menu | Yang harus terlihat |
|---|---|
| Dashboard | Kartu statistik terisi, sidebar lengkap |
| Identitas Sekolah | Halaman terbuka (sebelumnya error 500) |
| Presensi → Kelas | Tabel siswa muncul |
| Tarik Data Dapodik | API key tampil |
| Pengaturan → Hak Akses | Daftar modul tampil |

5. Coba **ganti tema** (ikon matahari/bulan di pojok kanan atas)
   → warna badge & kartu harus ikut berubah, bukan tetap sama

### 10.2 Uji lewat SSH

```bash
cd /www/wwwroot/<domain-anda>

# versi framework
php artisan --version

# aplikasi bisa boot & membaca seluruh route
php artisan route:list --json | head -c 200

# cek error terakhir
tail -n 50 storage/logs/laravel.log
```

### 10.3 Jalankan test suite (opsional, disarankan)

```bash
cd /www/wwwroot/<domain-anda>
php artisan config:clear     # WAJIB sebelum test
php artisan test --no-coverage
```

Harapan: **161 passed**. Bila banyak test gagal padahal kode tidak diubah,
kemungkinan `config:cache` masih aktif — jalankan `php artisan config:clear` dulu.

> Jangan menjalankan test di server produksi yang sedang dipakai pengguna,
> karena sebagian test menulis ke database.

---

## Bila Terjadi Masalah

### Aplikasi menampilkan error 500 setelah upgrade

```bash
tail -n 100 storage/logs/laravel.log
```

| Pesan | Penyebab | Solusi |
|---|---|---|
| `Class "..." not found` | `composer install` belum dijalankan | Ulangi Langkah 7 |
| `could not find driver` | ekstensi `pdo_mysql` belum ada | Langkah 3 |
| `Parse error: syntax error` | PHP masih 8.2 | Langkah 2 (set CLI + web) |
| `No application encryption key` | `APP_KEY` kosong | `php artisan key:generate` |
| `View [..] not found` | `view:cache` dengan view lama | `php artisan view:clear` |
| `SQLSTATE[42S02]` tabel tidak ada | migrasi belum jalan | Langkah 8 |

### Halaman kosong / aset CSS tidak termuat

```bash
# pastikan file CSS ada
ls -l public/css/tokens.css public/css/components.css

# pastikan izin terbaca
chmod 644 public/css/*.css

# pastikan Document Root mengarah ke /public (Langkah 4)
```

Bila muncul error `ERR_CONNECTION_CLOSED` pada aset, periksa `.env`:

```bash
grep -E '^(APP_URL|FORCE_HTTPS)=' .env
```

- `APP_URL` harus memakai `https://` bila situs ber-HTTPS
- `FORCE_HTTPS=true` hanya bila situs memang ber-TLS

### Login berhasil tetapi kembali ke halaman login

```bash
# pastikan session dapat ditulis
chmod -R 775 storage/framework/sessions
chown -R www:www storage

# bersihkan session lama bila perlu
php artisan session:table 2>/dev/null || true
```

### Ingin membatalkan upgrade (rollback)

```bash
cd /www/wwwroot
rm -rf <domain-anda>
tar -xzf ~/backup-sae-files-TANGGAL.tar.gz
mysql -u root -p db_sae < ~/backup-db-sae-TANGGAL.sql
```

---

## Cara Tercepat: Pakai `deploy.sh`

Setelah Langkah 1–5 selesai (backup, PHP 8.3, ekstensi, Document Root, git),
seluruh Langkah 6–9 dapat dijalankan dengan satu perintah:

```bash
cd /www/wwwroot/<domain-anda>
bash deploy.sh
```

Skrip akan:

1. Memeriksa PHP ≥ 8.3 dan 13 ekstensi wajib
2. Memeriksa Composer
3. Mendeteksi user web server
4. Memeriksa `.env` (APP_KEY, database, APP_URL, APP_DEBUG)
5. **Backup database** ke `storage/backups/`
6. `composer install --no-dev --optimize-autoloader`
7. `php artisan migrate --force`
8. `optimize:clear` → `config:cache` → `route:cache` → `view:cache`
9. Perbaiki izin berkas + reload PHP/OpenLiteSpeed

Berbeda dari versi lama, skrip ini **berhenti dengan pesan `[GAGAL]`** bila ada
langkah kritis gagal — tidak lagi melaporkan "selesai" padahal gagal.

---

## Daftar Periksa

Centang sebelum menganggap upgrade selesai:

- [ ] Backup database tersimpan **dan** sudah diunduh
- [ ] Backup berkas aplikasi tersimpan
- [ ] PHP 8.3 terpasang, CLI **dan** web
- [ ] 13 ekstensi PHP tersedia
- [ ] Document Root mengarah ke `/public`
- [ ] `php artisan --version` → **13.38.0**
- [ ] `php artisan migrate` tanpa error
- [ ] Halaman login terbuka
- [ ] Login admin berhasil
- [ ] Dashboard & 4 modul uji terbuka normal
- [ ] Ganti tema berfungsi (warna ikut berubah)
- [ ] `storage/logs/laravel.log` tanpa error baru
- [ ] `APP_DEBUG=false` di produksi
