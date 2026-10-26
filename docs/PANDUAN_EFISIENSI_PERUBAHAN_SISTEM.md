# PANDUAN EFISIENSI & SOP PERUBAHAN SISTEM SAE

Dokumen ini mencatat akar penyebab kelambatan proses modifikasi sebelumnya beserta protokol kerja baku (SOP) agar seluruh perubahan atau perbaikan sistem di masa mendatang berjalan cepat, tepat sasaran, efisien, dan bebas hambatan berulang.

---

## 1. Akar Penyebab Kelambatan (Root Cause Analysis)

### A. Fragmentasi Logika Menu & Hak Akses (Multi-Layer Coupling)
Satu perubahan menu sederhana (misal memindahkan atau menghapus sebuah menu peran) tersebar di 5 lapisan yang saling bergantung:
1. `app/Models/RolePermission.php`: Array `FORBIDDEN_MODULES`, `getBasePermissions()`, `isDutySpecificPermission()`, `canAccess()`, dan sinkronisasi tugas tambahan.
2. `app/Http/Controllers/PermissionController.php`: Filter tab, counter jumlah modul per peran, dan default CRUD.
3. `resources/views/partials/dash-sidebar.blade.php`: Conditional rendering per peran, helper `$canRole`, filter `$hasManajemenData` / `$hasLayananDigital`, dan markup link manual.
4. `routes/web.php`: Middleware permission guard (`permission:menu_xxx,action`).
5. Database MySQL (`role_permissions` & `ref_tugas_tambahan`): Data persisten yang harus di-sync ulang agar perubahan kode langsung terefleksi.

### B. Ukuran Berkas Monolitik Besar (>2.000 Baris)
- `resources/views/partials/dash-sidebar.blade.php` (~2.000 baris) dan `app/Models/RolePermission.php` (~2.600 baris) mencampur rendering HTML, logika bisnis, query database, array mapping statis, dan pengondisian peran.
- Akibatnya, operasi edit berbasis string matching kerap memicu tabrakan (*multiple matches found*) karena blok menu serupa berulang pada beberapa peran (misal: menu di grup Kepala TAS versus grup Tendik mandiri).

### C. Jebakan Eksekusi Perintah di Windows PowerShell 5.1
- Perintah *one-liner* `php -r "..."` langsung di terminal PowerShell sering gagal (*Parse error*) akibat PowerShell menginterpolasi karakter `$` (variabel) dan `\` (namespace) sebelum dikirim ke engine PHP.
- Kegagalan ini memaksa pembuatan berkas pengujian sementara (`scratch/*.php`), eksekusi, lalu penghapusan, yang memakan waktu dan token ganda.

### D. Keberadaan Menu Placeholder / Dummy (`href="#"`)
- Menu seperti `Berkas Peserta Didik`, `Perubahan Data`, dan `E-Izin` sebelumnya ada di sidebar dengan tautan dummy (`#`), padahal implementasi riilnya telah tertampung di tab Kesiswaan (`/dashboard/kesiswaan/peserta-didik`) dan Guru Piket (`/dashboard/piket/izin`). Hal ini menimbulkan ambiguitas apakah menu tersebut memiliki controller mandiri atau hanya tampilan semu.

---

## 2. SOP 4-Langkah Cepat Perubahan Menu & Hak Akses

Gunakan alur ringkas ini secara berurutan setiap kali ada permintaan penyesuaian menu:

```
┌────────────────────────────────────────────────────────────────────────┐
│ Langkah 1: Kunci Kebijakan Peran di Model (RolePermission.php)        │
│ • Tambahkan key ke FORBIDDEN_MODULES jika dilarang untuk peran itu     │
│ • Pindahkan key ke cluster pemilik sebenarnya di getBasePermissions    │
│ • Daftarkan ke isDutySpecificPermission jika khusus penugasan bidang   │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ Langkah 2: Rapikan Tampilan Sidebar (dash-sidebar.blade.php)           │
│ • Pastikan $canRole menghormati isForbiddenForRole                     │
│ • Hapus/sesuaikan tag <a> dan pembungkus section/group                 │
│ • Jangan pernah membuat link dummy href="#" baru                       │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ Langkah 3: Sinkronkan Database & Bersihkan Cache                       │
│ • Jalankan sinkronisasi DB agar record usang terhapus otomatis         │
│ • artisan optimize:clear                                               │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ Langkah 4: Verifikasi dengan Tes Skrip Otomatis                        │
│ • Buat 1 berkas assert mandiri -> jalankan -> hapus                    │
│ • Validasi DB (0 baris terlarang) & View (HTML bersih)                 │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Protokol Eksekusi Terminal Aman (PowerShell Laragon)

1. **Path PHP Eksekusi Standar**:
   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
   ```

2. **DILARANG Menjalankan Skrip Panjang via `php -r "..."` di PowerShell**:
   Karakter `$var` dan `\App\Models` selalu rusak akibat escaping PowerShell 5.1.
   **Gunakan pola file sementara**:
   ```powershell
   # Tulis ke scratch/test.php -> jalankan -> hapus langsung
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe scratch/test.php; Remove-Item scratch/test.php
   ```

3. **Pembersihan Cache Komprehensif Satu Perintah**:
   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize:clear
   ```

4. **Verifikasi Kompilasi Template Blade**:
   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan view:cache; C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan view:clear
   ```

---

## 4. Pola Edit Tepat Sasaran pada File Besar

1. **Gunakan Konteks Minimal 4-5 Baris Unik**:
   Saat menggunakan alat edit string pada berkas monolitik seperti `dash-sidebar.blade.php`, sertakan atribut unik terdekat (misal nama route atau ID elemen spesifik), bukan sekadar `<span class="nav-label">`.
2. **Prioritaskan Operasi Multi-Replace**:
   Jika perubahan menyentuh beberapa blok dalam satu berkas, gunakan operasi multi-replace terpadu untuk menghemat round-trip percakapan.
3. **Cek `git diff` Langsung Setelah Edit**:
   Segera jalankan `git diff --stat` atau `git diff` untuk memastikan hanya baris yang dituju yang berubah tanpa merusak lekukan (*indentation*) kode lainnya.

---

## 5. Rencana Refactoring Berkelanjutan (Arsitektur Jangka Panjang)

Untuk membuat sistem lebih modular dan mudah dirawat:
1. **Pecah Sidebar Menjadi Partial Modular**:
   - `partials/sidebar/nav-utama.blade.php`
   - `partials/sidebar/nav-guru.blade.php`
   - `partials/sidebar/nav-tendik.blade.php`
   - `partials/sidebar/nav-siswa.blade.php`
   - `partials/sidebar/nav-orang-tua.blade.php`
   - `partials/sidebar/nav-sistem.blade.php`
   Mengurangi ukuran file dari 2.000 baris menjadi <200 baris per berkas, mempercepat pembacaan dan menghilangkan tabrakan edit.
2. **Pusatkan Definisi Modul ke Config File**:
   Pindahkan katalog modul dari konstanta model monolitik ke `config/modules.php`. Tambah/hapus modul cukup dilakukan di satu array konfigurasi tanpa menyentuh logika izin internal model.
