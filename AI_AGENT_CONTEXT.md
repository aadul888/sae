# SYSTEM ARCHITECTURE & DEVELOPER CONTEXT: SAE (Sistem Administrasi Edukasi)

Dokumen ini adalah ringkasan teknis definitif untuk AI Agent atau pengembang baru. Baca dokumen ini terlebih dahulu sebelum memodifikasi kode agar tidak perlu menelusuri ulang seluruh codebase.

---

## 1. Lingkungan & Tech Stack

- **Framework**: Laravel 10.50.3 (PHP ^8.1 / Direkomendasikan PHP 8.3 via Laragon: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`).
- **Database**: MySQL (`db_sae`).
- **Auth**:
    - Web: Laravel session (`web` guard).
    - API / Mobile: Laravel Sanctum (`auth:sanctum`, personal access token).
- **Frontend Stack**: Blade templating, Vanilla JS modular (terpisah dari Blade), CSS custom (`sae.css`, `dashboard.css`), SweetAlert2 (`vendor/sweetalert2/sweetalert2.all.min.js`), FontAwesome 6.
- **Root Project Utama**: `c:\laragon\www\sae`
- **Pendukung**:
    - `c:\laragon\www\loader-sae`: CLI/desktop sync loader Dapodik.
    - `c:\laragon\www\sae-app`: Versi legacy / modular PHP.

---

## 2. Aturan Struktur & Pemisahan Kode (Wajib Diikuti)

1. **Frontend / Backend / JS Separation**:
    - DILARANG menaruh `<script>` inline di dalam file Blade dashboard (`resources/views/dashboard/*.blade.php`).
    - Logika JavaScript wajib ditaruh di `public/js/{nama_modul}.js` dan dipanggil via `@push('scripts') <script src="{{ asset('js/{nama_modul}.js') }}"></script> @endpush`.
2. **Dialog Konfirmasi & Alert**:
    - Menggunakan **SweetAlert2** (`Swal.fire`).
    - Jangan gunakan `onsubmit="return confirm(...)"` native di Blade.
    - Pola baku: Berikan atribut `data-confirm="delete|reset"` dan `data-name="..."` pada elemen `<form>`, lalu tangani di JS dengan `e.preventDefault()`, jalankan SweetAlert2 Promise, lalu trigger `form.submit()`.
3. **Mobile Responsiveness**:
    - Dashboard menggunakan layout fleksibel di `resources/views/layouts/dashboard.blade.php`.
    - Sidebar desktop: lebar fixed `270px`. Mobile: offcanvas.
    - Tabel dashboard mendukung mode scroll atau stack view responsif (`@media (max-width: 768px)` di `public/css/dashboard.css`).

---

## 3. Peta Routing & Controller (`c:\laragon\www\sae`)

### Web Routes (`routes/web.php`)

- `/` (`home`): Portal publik pencarian kelulusan & info peserta didik (`HomeController@index`, `HomeController@checkNisn`).
- `/login`, `/logout`: Autentikasi web (`AuthController`).
- `/install`: Wizard instalasi sistem (`InstallController`).
- `/dashboard/admin`: Statistik ringkasan admin (`DashboardController@admin`).
- `/dashboard/guru`: Data GTK/Guru (`DashboardController@guru`).
- `/dashboard/tendik`: Dashboard Administrasi Tenaga Kependidikan (`DashboardController@tendik`).
- `/dashboard/peserta-didik`: Data Peserta Didik (`DashboardController@pesertaDidik`).
- `/dashboard/pengguna`: Manajemen user (`UserController@index`, `update`, `destroy`, `resetPassword`).
    - JS: `public/js/pengguna.js`.
- `/dashboard/tarik-data`: Sinkronisasi Dapodik via API key (`DapodikController`).
- `/dashboard/update`: Cek dan instal pembaruan kode Git & migrasi database (`UpdateController`).
    - JS: `public/js/update.js`.

### API Routes (`routes/api.php`)

- `POST /api/receive-data`: Endpoint penerima sinkronisasi data Dapodik dari `loader-sae` / `sae-feeder`.
- `POST /api/v1/auth/login`: Endpoint autentikasi REST API untuk Mobile App (menghasilkan Bearer token Sanctum).
- `GET /api/v1/auth/profile`: Profil user yang sedang login (Protected: `auth:sanctum`).
- `POST /api/v1/auth/logout`: Revoke access token saat ini (Protected: `auth:sanctum`).

---

## 4. Skema Database Kunci

- `users`: Kolom `id`, `name`, `username`, `email`, `password`, `role` (`admin`, `guru`, `tendik`, `peserta_didik`), `created_at`, `updated_at`.
- `personal_access_tokens`: Tabel token Laravel Sanctum untuk mobile/API.
- `dapodik_*`: Tabel cache/sinkronisasi dari Dapodik (sekolah, gtk, peserta_didik, rombel).
- `settings`: Konfigurasi sistem, status kelulusan, dan versi rilis.

---

## 5. Panduan Pengembangan Mobile App

Jika mengembangkan atau menghubungkan Mobile App (Flutter / React Native / Kotlin / Swift):

1. **Base URL**: `http://<domain_atau_ip>/api/v1`
2. **Autentikasi**:
    - Kirim `POST /api/v1/auth/login` dengan JSON: `{"username": "...", "password": "...", "device_name": "Android-Pixel"}`.
    - Simpan `token` yang dikembalikan ke Secure Storage / Keychain.
    - Semua request berikutnya menyertakan header:
        ```http
        Authorization: Bearer <token>
        Accept: application/json
        ```
3. **CORS**: Sudah diaktifkan untuk seluruh prefix `api/*` di `config/cors.php`.

---

## 6. Perintah Operasional Cepat

Jalankan dari direktori `c:\laragon\www\sae` menggunakan PHP Laragon 8.3:

```powershell
# Bersihkan cache framework
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize:clear

# Build/refresh cache config & routes agar ringan di production
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize

# Jalankan migrasi database
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate --force

# Cek daftar API endpoint
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan route:list --path=api
```
