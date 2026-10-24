# Copilot Instructions — Standard Operasional AI Coding Agent SAE

Dokumen ini dibaca secara otomatis oleh GitHub Copilot / AI Coding Agent pada setiap sesi kerja.
Seluruh instruksi di bawah ini bersifat **MANDATORI (WAJIB)** dan berlaku setiap saat (`always_on`). Dilarang keras melanggar aturan-aturan ini agar tidak perlu dilakukan audit ulang.

---

## 1. Aturan Batas Ukuran File (STRICT: MAX 1.000 BARIS)

1. **Dilarang Menghasilkan File > 1.000 Baris:**
   - Tidak ada satu pun file kode (`.php`, `.blade.php`, `.js`, `.css`) di dalam workspace yang boleh melebihi **1.000 baris**.
   - Jika suatu Controller, Model, Service, Blade View, atau JavaScript mendekati **800–900 baris**, Anda **WAJIB** langsung memecahnya secara modular sebelum file tersebut melampaui 1.000 baris.
2. **Prinsip Dekomposisi:**
   - **Backend Controller:** Ekstrak logika bisnis, query kompleks, dan format ekspor ke `app/Services/{Domain}{TanggungJawab}Service.php`. Controller hanya bertindak sebagai HTTP routing & response delivery (maksimal 200–400 baris).
   - **Backend Model:** Ekstrak kamus data array statis, pemetaan default, atau scanner ke kelas pembantu di `app/Support/`.
   - **Blade View:** Ekstrak modal dialog, tab navigasi, dan seksi formulir ke subdirektori partials terpisah (`resources/views/{modul}/partials/tab-*.blade.php` atau `modal-*.blade.php`), lalu panggil via `@include()`.
   - **JavaScript:** Ekstrak handler modal terpisah, analitik/charts, atau batch handler ke file terpisah di `public/js/{modul}-{fitur}.js`.
   - **CSS:** Pisahkan styling spesifik per fitur atau modul.

---

## 2. Larangan Duplikasi Kode (ZERO CODE DUPLICATION)

1. **Dilarang Menyalin Logika Identik:**
   - Jika suatu fungsi, query, kalkulasi, atau algoritma dibutuhkan oleh lebih dari satu tempat, buat kelas Service atau Helper tunggal di `app/Services/` atau `app/Support/`.
   - Jangan menyalin blok kode yang sama ke controller atau service lain. Panggil service yang sudah ada melalui dependency injection atau static helper.
2. **Dilarang Duplikasi Array Konfigurasi:**
   - Seluruh daftar izin, kode tugas tambahan, dan pemetaan peran berada di `app/Support/PermissionDictionary.php`, `DutyPermissionDefaults.php`, atau `RolePermissionRestrictions.php`. Jangan membuat hardcoded copy di tempat lain.

---

## 3. Kompatibilitas Kontrak & Proteksi Regresi (ZERO BREAKING CHANGES)

1. **Pertahankan Nama Route & URL Endpoint:**
   - Jangan pernah mengubah atau menghapus `route('...')` yang sudah ada, parameter URL, atau method HTTP (`GET`, `POST`, `PUT`, `DELETE`).
   - Jangan mengubah struktur payload respons JSON yang sudah dikonsumsi oleh frontend atau kiosk scanner (`{ success: true, message: "...", data: ... }`).
2. **Pertahankan Struktur Sesi Pengguna:**
   - Sesi autentikasi aplikasi menggunakan `session('user')` dengan key `role`, `id` / `pengguna_id`, `ptk_id`, `peserta_didik_id`, `nama`, `foto_url`. Jangan mengubah konvensi ini.
3. **Pertahankan Kontrak Otorisasi RBAC:**
   - Seluruh pengecekan izin wajib melalui `RolePermission::canAccess($user, $permissionKey, $action)` atau `RolePermission::can($user, $permissionKey, $action)`.
   - Jangan membuat bypass otorisasi baru di controller yang mengabaikan tugas tambahan GTK (`PtkTugasTambahan`).

---

## 4. Standar UI/UX, Blade, dan Frontend

1. **Pemisahan Logika JavaScript:**
   - **DILARANG MENARUH INLINE SCRIPT BESAR DI DALAM BLADE.** Seluruh logika JS ditaruh di `public/js/{nama_modul}.js` dan di-push via `@push('scripts')`.
2. **Dialog Konfirmasi:**
   - Gunakan **SweetAlert2** (`Swal.fire`). Dilarang menggunakan `alert()` atau `confirm()` bawaan browser.
3. **Z-Index Modal:**
   - Seluruh modal wajib memiliki `z-index: 99999 !important` agar tidak tertutup sidebar/topbar.
4. **Paginasi:**
   - Gunakan `@include('partials.datatable-pagination', ['paginator' => $list])`. Dilarang menggunakan `{{ $list->links() }}` mentah.
5. **Tombol Ikon:**
   - Tombol pada header banner (`.dash-banner-actions`) dan aksi baris (`.table-actions`) wajib icon-only dengan atribut `title="..."`.

---

## 5. Protokol Verifikasi Mandatori (Wajib Dijalankan Setiap Perubahan)

Sebelum menyatakan suatu tugas selesai, Anda **WAJIB** mengeksekusi urutan verifikasi berikut melalui terminal PowerShell:

1. **Linting Sintaks PHP:**
   ```powershell
   php -l <file-yang-diubah.php>
   ```
2. **Kompilasi Blade View Cache:**
   ```powershell
   php artisan view:clear ; php artisan view:cache
   ```
3. **Kompilasi Frontend Asset Bundle (Vite):**
   ```powershell
   npm run build
   ```
4. **Eksekusi Test Suite (Harus 100% Hijau):**
   ```powershell
   php artisan test --no-coverage
   ```
5. **Pemeriksaan Batas Ukuran Baris Kode (Harus 0 Pelanggaran):**
   ```powershell
   (Get-ChildItem -Path app -Recurse -File -Include *.php | Where-Object { (Get-Content -LiteralPath $_.FullName | Measure-Object -Line).Lines -ge 1000 }).Count
   ```

---

## 6. Pembaruan Log & Inventaris Otomatis

Setiap kali melakukan penambahan file atau refactoring:
1. Catat berkas baru dan baris kode pada `.agents/FILES_OVER_1000_LINES.md`.
2. Daftarkan service/support baru pada `.agents/PROJECT_STRUCTURE.md`.
3. Catat riwayat aksi dan hasil verifikasi pada `.agents/WORKLOG.md`.
