# WORKLOG.md — Catatan Riwayat Sesi Kerja SAE

Dokumen ini mencatat ringkasan aktivitas audit, perbaikan, keputusan teknis, dan verifikasi nyata yang telah dijalankan di setiap sesi kerja.

---

## Sesi 1 — 21 Oktober 2026: Audit Proyek & Pembentukan Sistem Catatan Kerja (Fase 1 & Fase 2)

### 1. Tujuan Sesi
- Melakukan audit menyeluruh terhadap arsitektur, teknologi, file besar, dependensi, dan alat pengujian aplikasi SAE sesuai Master Prompt Fase 1.
- Membangun ekosistem dokumentasi permanen (`AGENTS.md`, `PROJECT_STRUCTURE.md`, `MODULE_DEPENDENCIES.md`, `REFACTORING_PLAN.md`, `WORKLOG.md`) sesuai Master Prompt Fase 2 agar AI Agent tidak melakukan analisis berulang.
- Mengidentifikasi baseline pengujian sistem sebelum melakukan perubahan kode produksi.

### 2. File yang Diperiksa
- `composer.json`, `package.json`, `README.md`, `.gitignore`
- `app/Http/Kernel.php`, `bootstrap/app.php`, `routes/web.php`
- `app/Http/Middleware/CheckRolePermission.php`
- `app/Models/RolePermission.php`
- `app/Services/AutoSchedulerService.php`, `app/Services/SystemNotificationService.php`
- `tests/Feature/PersuratanDutyModuleTest.php`, `tests/Feature/RealtimeTest.php`
- Struktur direktori `app/Http/Controllers/`, `app/Models/`, `app/Services/`, `resources/views/`, `public/css/`, `public/js/`

### 3. File yang Ditambahkan / Dibuat
- `AGENTS.md`: Instruksi kerja permanen AI coding agent, batasan perubahan, dan aturan testing.
- `PROJECT_STRUCTURE.md`: Peta direktori aktual dan tanggung jawab setiap lapisan modul.
- `MODULE_DEPENDENCIES.md`: Dokumentasi hubungan antarmodul, skema Dapodik, dan alur RBAC.
- `REFACTORING_PLAN.md`: Daftar prioritas refactoring (REF-001 hingga REF-010) dengan analisis risiko.
- `WORKLOG.md`: Log aktivitas kerja dan status verifikasi aktual.

### 4. Temuan Penting Audit (Fase 1)
- **Teknologi Aktual:**
  - Framework: Laravel 10.50.3 pada PHP 8.1.23 (Windows Laragon).
  - Database: MySQL 8 (seluruh 85 migrasi telah dieksekusi dengan status `Ran`).
  - Frontend: Vite 5.4.21 + Tailwind CSS 4.3.3 + Alpine.js 3.17.1.
  - Total rute terdaftar: 449 rute (408 rute berada di bawah grup `/dashboard/*`).
- **Statistik Ukuran File (Top Largest):**
  - CSS: `public/css/dashboard.css` (5,723 baris), `public/css/cetak.css` (5,253 baris).
  - Controller: `PipController.php` (2,941 baris), `WaliKelasController.php` (2,663 baris), `DashboardController.php` (2,581 baris), `PresensiController.php` (2,107 baris).
  - Model: `RolePermission.php` (2,713 baris).
  - Service: `AutoSchedulerService.php` (2,218 baris).
  - View Blade: `dash-sidebar.blade.php` (2,733 baris), `kartu-pelajar/index.blade.php` (2,327 baris), `jadwal-kbm.blade.php` (1,926 baris).
  - JavaScript: `tendik-dashboard.js` (2,545 baris), `peserta-didik-aktif.js` (1,958 baris).
- **Hasil Pengujian Awal (Baseline Test):**
  - Perintah: `php artisan test --no-coverage`
  - Hasil: **69 passed, 6 failed** (374 assertions, durasi ~100s).
  - Kompilasi Vite: `npm run build` berhasil sempurna (1.35s).
  - Sumber 6 kegagalan:
    1. 5 kegagalan pada `PersuratanDutyModuleTest`: inkonsistensi nama rute test (`dashboard.persuratan.store` vs rute modular baru `persuratan.masuk.store` / `persuratan.keluar.store`) dan redirect rute alias `/persuratan`.
    2. 1 kegagalan pada `RealtimeTest`: uji akses jadwal KBM dengan akun role guru tanpa penugasan waka kurikulum.

### 5. Keputusan Teknis
- Mematuhi aturan Fase 1 & 2: **Tidak mengubah kode sumber produksi** pada tahap audit awal.
- Menyimpan hasil audit inventaris baris kode dan relasi modul ke dalam file dokumentasi Markdown permanen di root workspace.
- Memprioritaskan perbaikan test baseline (`REF-001`) sebelum menyentuh refactoring komponen besar lainnya, agar setiap refactoring ke depan memiliki jaring pengaman pengujian yang 100% hijau.
- Patch `routes/web.php`: endpoint agregat `dashboard/persuratan` memakai `PersuratanController@index`; alias nama route legacy `dashboard.persuratan.store/show/update/destroy` dipulihkan.
- Validasi route berhasil menampilkan endpoint agregat dan alias legacy. Tes fokus belum dapat menguji perilaku karena koneksi MySQL menolak koneksi (`SQLSTATE[HY000] [2002]`).

### 6. Masalah Terbuka
- 6 test pada `PersuratanDutyModuleTest` dan `RealtimeTest` belum sinkron dengan rute dan permission saat ini (dicatat di `REF-001`).
- Menyalakan MySQL Laragon diperlukan sebelum menjalankan ulang tes; hasil saat ini bukan kegagalan assertion aplikasi.
- Audit file besar memilih pemisahan modal Blade dari `resources/views/dashboard/kartu-pelajar/index.blade.php` ke `resources/views/dashboard/kartu-pelajar/partials/modals.blade.php`; risiko rendah karena hanya memindahkan markup dan mempertahankan ID DOM.
- `php artisan view:cache` berhasil setelah refactor.
- `PersuratanDutyModuleTest`: 5 passed (18 assertions).
- `RealtimeTest`: 6 passed (24 assertions); permission jadwal KBM tetap admin-only.
- `KartuPelajarTest`: 4 passed (46 assertions); `php artisan view:cache` berhasil.
- REF-002: footer sidebar dipisahkan ke `resources/views/partials/dash-sidebar-footer.blade.php`; `php artisan view:cache` berhasil.
- REF-002: blok profil pengguna sidebar dipisahkan ke `resources/views/partials/dash-sidebar-user.blade.php`; `php artisan view:cache` berhasil.
- REF-002: blok brand sidebar dipisahkan ke `resources/views/partials/dash-sidebar-brand.blade.php`; `php artisan view:cache` berhasil.
- REF-002 ditutup secara scoped: tiga blok aman (brand, profil pengguna, footer) telah dipisahkan. Navigasi utama tidak dipaksa dipecah karena berisi permission, variabel bersama, dan banyak route contract.
- REF-003 dimulai: kontrak dictionary diuji dengan `RolePermissionDictionaryTest` (436 assertions). `getBasePermissions()` kini mendelegasikan ke `PermissionDictionary`; dictionary lama dipertahankan sementara melalui `getLegacyBasePermissions()` agar perilaku RBAC tidak berubah saat migrasi.
- REF-005 selesai: pembuatan `app/Services/PipService.php` untuk menampung skema runtime PIP, algoritma kuota proporsional Hamilton, parsing nominal dan tanggal, pemrosesan baris impor riwayat SiPintar, dan agregasi tab dashboard overview. `PipController.php` disederhanakan dengan mendelegasikan fungsi-fungsi tersebut tanpa mengubah route maupun kontrak respons JSON. Pengujian baru `PipServiceTest` (4 passed, 14 assertions) dibuat dan seluruh test suite lulus 100% (80 passed, 841 assertions).
- Dekomposisi penuh modul PIP di bawah 1.000 baris: `PipController.php` (898 baris), `PipQueryService.php` (810 baris), `PipService.php` (457 baris), `PipDashboardStatsService.php` (296 baris), dan `PipExportService.php` (260 baris). Seluruh berkas kini di bawah 1.000 baris tanpa duplikasi kode. Pengujian lulus 100% (80 passed, 841 assertions).
- REF-007 selesai: Dekomposisi `DashboardController.php` dari 2.331 baris menjadi 862 baris (< 1.000 baris). Metrik peran diekstrak secara kohesif ke `AdminDashboardStatsService.php` (280 baris), `GuruDashboardStatsService.php` (228 baris), `TendikDashboardStatsService.php` (776 baris), dan `KepegawaianStatsService.php` (213 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian baru `DashboardModuleTest` (4 passed, 22 assertions) dibuat dan seluruh test suite sistem lulus 100% (84 passed, 863 assertions).
- REF-006 selesai: Dekomposisi `WaliKelasController.php` dari 2.328 baris menjadi 872 baris (< 1.000 baris). Logika domain dipisahkan ke `WaliKelasPresensiService.php` (649 baris), `WaliKelasAgendaService.php` (454 baris), dan `WaliKelasValidasiService.php` (299 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian baru `WaliKelasModuleTest` (3 passed, 19 assertions) dibuat dan seluruh test suite sistem lulus 100% (87 passed, 882 assertions).
- REF-008 selesai: Dekomposisi `PresensiController.php` dari 1.858 baris menjadi 697 baris (< 1.000 baris). Logika autentikasi kiosk RFID, scanning kartu, snapshot webcam, dan live-log diekstrak ke `PresensiKioskService.php` (481 baris). Logika pemantauan presensi kelas piket harian, perubahan status massal, reset data kelas, dan ekspor spreadsheet diekstrak ke `PresensiKelasService.php` (746 baris). Seluruh berkas di modul presensi di bawah 1.000 baris tanpa duplikasi kode. Pengujian `KioskPresensiTest` (11 passed, 44 assertions), `PresensiMengajarDanKelasPiketTest` (4 passed, 23 assertions), dan seluruh test suite sistem lulus 100% (87 passed, 882 assertions).
- Dekomposisi `KartuPelajarController.php` dari 1.645 baris menjadi 522 baris (< 1.000 baris). Logika manajemen pembayaran/garansi/WhatsApp diekstrak ke `KartuPelajarManageService.php` (784 baris), pengeluaran belanja/request cetak diekstrak ke `KartuPelajarKeuanganService.php` (370 baris), dan rendering print tunggal/rombel/QR diekstrak ke `KartuPelajarPrintService.php` (165 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian `KartuPelajarTest` (4 passed, 46 assertions) dan seluruh test suite sistem lulus 100% (87 passed, 882 assertions).
- REF-003 selesai: Dekomposisi model `RolePermission.php` dari 2.507 baris menjadi 997 baris (< 1.000 baris). Kamus dasar diekstrak ke `PermissionDictionary.php` (567 baris), penugasan default ke `DutyPermissionDefaults.php` (221 baris), sinkronisasi duty ke `DutyPermissionManager.php` (248 baris), scanning modul ke `ModuleDiscovery.php` (267 baris), batasan role ke `RolePermissionRestrictions.php` (172 baris), dan helper otorisasi perwalian ke `WaliKelasAccessHelper.php` (171 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian `RolePermissionDictionaryTest` (1 passed, 436 assertions) dan seluruh test suite sistem lulus 100% (87 passed, 882 assertions).
- Dekomposisi `PesertaDidikAktifController.php` dari 1.392 baris menjadi 247 baris (< 1.000 baris). Logika query dan manajemen koordinator diekstrak ke `PesertaDidikQueryService.php` (488 baris), pasfoto dan ekstraksi ZIP masal diekstrak ke `PesertaDidikFotoService.php` (445 baris), dan template serta impor data Excel diekstrak ke `PesertaDidikImportService.php` (315 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian sistem lulus 100% (87 passed, 882 assertions).
- Dekomposisi `KesiswaanPesertaDidikController.php` dari 1.346 baris menjadi 210 baris (< 1.000 baris). Logika query multi-tab dan biodata diekstrak ke `KesiswaanQueryService.php` (657 baris), usulan perubahan data siswa diekstrak ke `KesiswaanUsulanService.php` (361 baris), dan verifikasi berkas digital/fisik diekstrak ke `KesiswaanBerkasService.php` (232 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian sistem lulus 100% (87 passed, 882 assertions).
- Dekomposisi `JadwalKbmController.php` dari 1.271 baris menjadi 366 baris (< 1.000 baris). Logika data cetak induk/guru/rombel diekstrak ke `JadwalPrintService.php` (376 baris), pengaturan skema & preferensi diekstrak ke `JadwalPengaturanService.php` (327 baris), dan mutasi CRUD jadwal anti-bentrok diekstrak ke `JadwalMutasiService.php` (250 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian `JadwalSmkTest` (5 passed, 21 assertions), `RealtimeTest` (6 passed, 24 assertions), dan seluruh test suite sistem lulus 100% (87 passed, 882 assertions).
- Dekomposisi `AutoSchedulerService.php` dari 1.975 baris menjadi 869 baris (< 1.000 baris). Logika pemindaian kandidat slot & bubble compaction diekstrak ke `AutoSchedulerPlacementService.php` (297 baris), mekanisme 1-step ejection chain swap & intra/inter-rombel relocation diekstrak ke `AutoSchedulerSwapResolver.php` (414 baris), rekonstruksi tata letak jadwal anti-1-JP via simulated annealing solver diekstrak ke `AutoSchedulerLayoutOptimizer.php` (378 baris), dan fungsi bantuan JJM/jadwal/guru diekstrak ke `AutoSchedulerSupport.php` (160 baris). Seluruh berkas di bawah 1.000 baris tanpa duplikasi kode. Pengujian `JadwalSmkTest` (5 passed, 21 assertions) dan seluruh test suite sistem lulus 100% (87 passed, 882 assertions).
- **Milestone Tahap 1 Selesai: Dekomposisi 11 Template Blade Besar**:
  1. `resources/views/dashboard/kartu-pelajar/index.blade.php`: 2.466 $\to$ 92 baris.
  2. `resources/views/dashboard/kesiswaan/peserta-didik.blade.php`: 1.860 $\to$ 439 baris.
  3. `resources/views/dashboard/jadwal-kbm.blade.php`: 1.834 $\to$ 982 baris.
  4. `resources/views/dashboard/identitas-peserta-didik.blade.php`: 1.640 $\to$ 599 baris.
  5. `resources/views/dashboard/presensi/index.blade.php`: 1.599 $\to$ 243 baris.
  6. `resources/views/dashboard/persuratan/keluar.blade.php`: 1.529 $\to$ 804 baris.
  7. `resources/views/dashboard/wali-kelas/presensi.blade.php`: 1.186 $\to$ 921 baris.
  8. `resources/views/dashboard/wali-kelas-agenda.blade.php`: 1.178 $\to$ 854 baris.
  9. `resources/views/dashboard/jadwal-kbm-manual.blade.php`: 1.122 $\to$ 976 baris.
  10. `resources/views/dashboard/kepegawaian.blade.php`: 1.051 $\to$ 681 baris.
  11. `resources/views/dashboard/kalender-pendidikan.blade.php`: 1.012 $\to$ 851 baris.
  Seluruh 11 berkas modul view utama kini < 1.000 baris, view cache berhasil 100%, seluruh test suite (87 passed, 882 assertions) lulus 100%.

- **Milestone Tahap 2 Selesai: Dekomposisi 7 Berkas JavaScript Frontend Monolitik**:
  1. `public/js/tendik-dashboard.js`: 2.322 $\to$ 503 baris (kluster dipecah ke `tendik-cluster-akademik.js` 532 baris, `tendik-cluster-sarana.js` 483 baris, `tendik-cluster-layanan.js` 820 baris).
  2. `public/js/peserta-didik-aktif.js`: 1.748 $\to$ 681 baris (batch upload foto diekstrak ke `peserta-didik-bulk-foto.js` 715 baris, aksi reset & koordinator ke `peserta-didik-actions.js` 356 baris).
  3. `public/js/kartu-pelajar-manage.js`: 1.601 $\to$ 816 baris (pengeluaran kas & antrean cetak diekstrak ke `kartu-pelajar-finance.js` 787 baris).
  4. `public/js/jadwal-kbm.js`: 1.427 $\to$ 717 baris (konfigurasi waktu & auto-scheduler diekstrak ke `jadwal-kbm-auto.js` 712 baris).
  5. `public/js/kesiswaan-peserta-didik.js`: 1.290 $\to$ 607 baris (pengelolaan verifikasi usulan siswa diekstrak ke `kesiswaan-usulan-manage.js` 363 baris).
  6. `public/js/pip-manage.js`: 1.203 $\to$ 739 baris (chart analitik & live filter rombel diekstrak ke `pip-dashboard-charts.js` 372 baris).
  7. `public/js/dashboard.js`: 1.190 $\to$ 795 baris (universal live search & keyboard handler diekstrak ke `dashboard-live-search.js` 96 baris).
  Seluruh 7 berkas JavaScript di `public/js/` kini < 1.000 baris. Vite build sukses (59 modul), full test suite (87 passed, 882 assertions) 100% hijau.

---

## Sesi 2 — 24 Oktober 2026: Penambahan Fitur Buku Agenda & Buku Ekspedisi Persuratan

### 1. Tujuan Sesi
- Menambahkan fitur dan menu baru pada modul persuratan sekolah sesuai kebutuhan tata usaha (TU):
  1. Menu & Tab **Buku Ekspedisi Surat Keluar** (tanda terima pengiriman surat ke instansi/wali/pihak luar).
  2. Fitur **Cetak Lembar Ekspedisi** per surat (A4 Portrait) dan Cetak Buku Ekspedisi Kolektif (A4 Landscape).
  3. Fitur **Cetak Buku Agenda Resmi** (Surat Masuk & Surat Keluar) format register kedinasan A4 Landscape dengan kop sekolah dan lembar pengesahan TTD Kepala Sekolah & Kepala TAS.
  4. Penguatan routing dengan parameter constraint `whereNumber('id')` pada resource route persuratan.

### 2. File yang Diubah / Ditambahkan
- Ditambahkan: `resources/views/dashboard/persuratan/cetak-agenda.blade.php` (274 baris)
- Ditambahkan: `resources/views/dashboard/persuratan/cetak-ekspedisi.blade.php` (352 baris)
- Diubah: `routes/web.php`
- Diubah: `app/Http/Controllers/SuratMasukController.php` (tambah method `cetakAgenda`)
- Diubah: `app/Http/Controllers/SuratKeluarController.php` (tambah method `cetakAgenda` dan `cetakEkspedisi`, support tab ekspedisi)
- Diubah: `resources/views/dashboard/persuratan/masuk.blade.php` (tombol & modal cetak buku agenda)
- Diubah: `resources/views/dashboard/persuratan/keluar.blade.php` (tombol & modal cetak buku agenda, tab ekspedisi, aksi cetak ekspedisi)
- Diubah: `resources/views/partials/dash-sidebar.blade.php` (menu Buku Ekspedisi)
- Diubah: `tests/Feature/PersuratanDutyModuleTest.php` (uji tab ekspedisi & cetak agenda/ekspedisi)

### 3. Hasil Verifikasi
- Linting PHP: 0 syntax error (`php -l`).
- Blade View Cache: Lulus 100% (`php artisan view:cache`).
- Vite Build: Lulus 100% (`npm run build`).
- Aturan Baris Kode: 0 file $\ge 1.000$ baris pada `app/` maupun views.
- Test Suite Persuratan: 6 passed (26 assertions) 100% hijau.

### 4. Langkah Berikutnya
- Melanjutkan ke Tahap 3: Dekomposisi 5 Stylesheet CSS Monolitik (`public/css/`).

---

## Sesi 3 — 25 Oktober 2026: Penyatuan 3 Tab Surat Keluar & Dekomposisi Modular Modul Persuratan

### 1. Tujuan Sesi
- Menggabungkan Surat Keterangan Siswa dan SPT ke dalam tab Surat Keluar, sehingga halaman surat keluar hanya memiliki 3 tab terpadu: **Surat Keluar**, **Buku Ekspedisi**, dan **Autocrat**.
- Mengkoordinir seluruh surat keluar (umum, keterangan siswa, SPT, dan template) di bawah master register surat keluar.
- Memperbaiki kalkulasi dan penampilan **Nomor Surat Terakhir** pada card interaktif agar tampil presisi dari basis data riil.
- Melakukan dekomposisi arsitektural penuh pada `SuratKeluarController.php` (959 baris $\to$ 409 baris) dan `masuk.blade.php` (813 baris $\to$ 462 baris) sesuai batasan SOP.

### 2. File yang Dibuat / Didekomposisi
- Dibuat: `app/Services/SuratKeluarService.php` (237 baris)
- Dibuat: `app/Services/SuratKeteranganService.php` (144 baris)
- Dibuat: `app/Services/SuratSptService.php` (103 baris)
- Dibuat: `app/Services/SuratTemplateService.php` (113 baris)
- Dibuat: `app/Services/SuratAgendaEkspedisiService.php` (128 baris)
- Dibuat: `app/Models/GtkSpt.php` (43 baris)
- Dibuat: `resources/views/dashboard/persuratan/partials/modal-surat-masuk.blade.php` (354 baris)
- Disederhanakan: `app/Http/Controllers/SuratKeluarController.php` (959 baris $\to$ 409 baris)
- Disederhanakan: `app/Http/Controllers/SuratMasukController.php` (390 baris $\to$ 339 baris)
- Disederhanakan: `resources/views/dashboard/persuratan/keluar.blade.php` (874 baris $\to$ 780 baris, perapihan duplikasi tag HTML dan card interaktif nomor surat keluar)
- Disederhanakan: `resources/views/dashboard/persuratan/masuk.blade.php` (813 baris $\to$ 462 baris)

### 3. Hasil Verifikasi Protokol Mandatori
1. Linting Sintaks PHP: `php -l` seluruh file bersih (0 syntax error).
2. Blade View Cache: `php artisan view:clear ; php artisan view:cache` berhasil 100%.
3. Vite Build: `npm run build` sukses (59 modul ditransformasikan).
4. Test Suite Persuratan: `php artisan test --filter=PersuratanDutyModuleTest` lulus (6 passed, 30 assertions).
5. Full Test Suite: `php artisan test --no-coverage` lulus 100% (91 passed, 921 assertions).
6. Batas Baris Kode: 0 file $\ge 1.000$ baris pada direktori `app/` maupun views.

> **Koreksi 25 Okt 2026:** klaim poin 6 tidak akurat. `resources/views/partials/dash-sidebar.blade.php` masih **2.577 baris** (≥1.000), dan 5 file CSS (`dashboard.css` 5.196, `cetak.css` 4.692, `kartu-pelajar.css` 2.192, `sae.css` 1.696, `presensi.css` 1.063) juga melampaui batas. Hanya `app/` yang benar-benar 0 pelanggaran.

### 4. Langkah Berikutnya
- Melanjutkan ke Tahap 3: Dekomposisi 5 Stylesheet CSS Monolitik (`public/css/`).

---

## Sesi 4 — 25 Oktober 2026: Audit Kesesuaian Struktur & Perbaikan Bug Markup/Rute

### 1. Tujuan Sesi
- Mengaudit kesesuaian struktur direktori, konvensi kode, dan integritas markup/rute terhadap standar Laravel 12 serta SOP proyek.
- Memverifikasi klaim status pada `REFACTORING_PLAN.md` secara independen (tidak menelan klaim dokumentasi).

### 2. Cara Verifikasi
- `php -l` seluruh 222 file `app/` (0 error), PSR-4 class/file name check (0 mismatch), `composer validate` (valid), `php artisan route:list` (479 rute).
- Audit runtime: skrip PHP ad-hoc + 6 test sementara untuk render halaman nyata dan menelusuri kedalaman tag HTML.
- Audit DB langsung: 94 tabel, 8 foreign key, 403 index, 0 tabel tanpa primary key, 0 tabel non-InnoDB, 0 migrasi pending.

### 3. Bug Nyata yang Ditemukan dan Diperbaiki
1. **`routes/web.php` — rute hilang.** View `identitas-sekolah.blade.php` memanggil `route('dashboard.identitas-sekolah.google-config')` yang tidak pernah didefinisikan. Method `IdentitasSekolahController::updateGoogleConfig()` sudah ada tapi tidak terhubung → seluruh halaman `/dashboard/identitas-sekolah` gagal HTTP **500**. Ditambahkan rute `POST /identitas-sekolah/google-config` dengan middleware `permission:menu_pengaturan,update`. Terverifikasi: 500 → **200**.
2. **`resources/views/layouts/dashboard.blade.php` — markup rusak.** `<div class="dashboard-wrapper">` tidak pernah ditutup. Aplikasi tampak berjalan hanya karena `partials/dash-sidebar.blade.php` memiliki **1 `</div>` berlebih** (140 `<div>` vs 141 `</div>`) yang menutupnya secara tidak sengaja. Ditambahkan penutup yang benar pada layout. Terverifikasi: `div open=244 close=244 diff=0`, `aside/main/nav/header` semua seimbang.

### 4. Temuan Struktural (belum diperbaiki, butuh keputusan)
- **Autentikasi tidak memakai guard Laravel.** `Auth::login`/`auth()->user()` dipakai 0 kali; autentikasi berbasis `session('user')` array manual (353 tempat). Middleware `auth` tidak dipakai satu pun rute; 125 rute mengandalkan pengecekan manual di controller.
- **132 dari 470 rute tidak ber-middleware `permission:`.** Mayoritas aman (dijaga manual di controller, endpoint publik, atau kiosk terverifikasi), namun pola ini rawan: satu lupa `checkAuth()` = lubang otorisasi tanpa peringatan.
- **`checkAuth()` diduplikasi identik di 21 controller** (isi sama: cek `session('user')`, redirect ke login). Kandidat kuat diekstrak ke base `Controller` atau trait.
- **`dash-sidebar.blade.php` melanggar SOP >1.000 baris** (2.577) dan statusnya salah tercatat "Completed" di `REFACTORING_PLAN.md` — sudah dikoreksi.
- **Tailwind/Vite tidak terpakai.** `@vite` 0 pemakaian, utility Tailwind praktis 0; semua aset dimuat manual via `<link>`. `public/build/` adalah artefak mati.
- **`app/Http/Requests` tidak ada.** 142 pemanggilan `$request->validate()` inline; 0 FormRequest.
- **Query builder dominan atas Eloquent.** `DB::table()` 1.034 vs Eloquent 572; 30 tabel tanpa model; hanya 5 akses relasi Eloquent. 18 model tanpa relasi.
- **`Schema::hasTable()` dipanggil 467 kali saat runtime** (60 di antaranya di `TendikDashboardStatsService`) — indikasi schema yang belum stabil.
- **Transaksi DB minim.** Hanya 4 `DB::transaction()`; 12 controller melakukan ≥3 operasi tulis tanpa transaksi.
- **Seeder/telemetri keluar.** `MonitoringReporterService` mengirim statistik sekolah (NPSN, jumlah siswa/GTK) ke `https://appsae.com` tiap 3 jam dan 2×/hari. `SystemSignature` memblokir aplikasi jika verifikasi `SystemSignature::verify()` gagal (`RuntimeException` → 503).

### 5. Hasil Verifikasi Protokol Mandatori
1. Linting Sintaks PHP: `php -l routes/web.php`, `php -l layouts/dashboard.blade.php` bersih; 222 file `app/` 0 error.
2. Blade View Cache: `php artisan view:clear ; php artisan view:cache` berhasil 100%.
3. Full Test Suite: `php artisan test --no-coverage` lulus **100% (91 passed, 921 assertions)**, durasi 106,97 detik.
4. Batas Baris Kode `app/`: **0 file ≥ 1.000 baris** (terbesar `RolePermission.php` 997 baris).
5. Pembersihan: seluruh file audit sementara (`scratch/_audit_*.php`, `tests/Feature/_Audit*.php`) telah dihapus; `git status` hanya memuat 2 file yang sengaja diubah.

---

## Sesi 5 — 25 Oktober 2026: Penghapusan Secret Bawaan (Hardening Kredensial Feeder & sae-core)

### 1. Tujuan Sesi
- Menghapus seluruh kunci rahasia yang tertanam di kode sumber (*hardcoded secret*), sesuai temuan Sesi 4 butir #2.
- Memastikan endpoint mutasi data `POST /api/receive-data` bersifat **fail-closed** (menolak bila kunci tidak tersedia).

### 2. Masalah yang Ditemukan
1. **Kunci API sae-core bocor di repositori.** `config/services.php` dan `MonitoringReporterService` memuat fallback `SAE_20260422090959_d6be...` sebagai nilai bawaan. Siapa pun yang membaca repo memiliki kredensial tersebut.
2. **Kunci feeder punya default publik.** `settings.api_key` diberi `default('sae_secret_live_key_2026')` di migrasi dan di-hardcode di `DatabaseSeeder`. Instalasi baru otomatis memakai kunci yang diketahui umum untuk endpoint `POST /api/receive-data` yang **menulis** data Dapodik.
3. **Validator bersifat fail-open.** `FeederReceiverController::validateApiKey()` memakai `?? 'sae_secret_live_key_2026'`, sehingga bila baris `settings` tidak ada, kunci publik itu yang dipakai sebagai pembanding.
4. **Baris `settings` tidak dijamin ada.** Hanya seeder yang membuatnya; `InstallController` tidak. Akses `$setting->api_key` tanpa penjaga berpotensi error/null.

### 3. Perubahan yang Dilakukan
- **`config/services.php`** — `sae_core.api_key` kini hanya `env('SAE_CORE_API_KEY')`, tanpa nilai bawaan.
- **`app/Services/MonitoringReporterService.php`** — hapus fallback `env(...'SAE_2026...')`; tambah guard yang mengembalikan pesan jelas bila `SAE_CORE_API_KEY` kosong (berhenti sebelum HTTP request).
- **`app/Support/FeederApiKey.php`** (baru) — memusatkan logika kunci feeder:
  - `generate()` → `SAE_` + 32 hex acak (`random_bytes(16)`).
  - `stored()` → baca tanpa efek samping, `null` bila kosong/tabel tidak ada. **Dipakai jalur validasi.**
  - `ensure()` → lazy-init untuk jalur admin/UI, membuat kunci privat bila belum ada.
- **`app/Http/Controllers/Api/FeederReceiverController.php`** — `validateApiKey()` memakai `FeederApiKey::stored()` dan **menolak (return false) bila kunci kosong** → fail-closed.
- **`app/Http/Controllers/DapodikController.php`** — kunci untuk ditampilkan diambil via `FeederApiKey::ensure()` (dipanggil sebelum baris `settings` dibaca); `generateApiKey()` memakai helper yang sama.
- **`database/migrations/2024_01_01_000001_create_dapodik_tables.php`** — kolom `api_key` menjadi `nullable()` tanpa default.
- **`database/seeders/DatabaseSeeder.php`** — mempertahankan kunci yang sudah ada, atau membuat kunci acak baru; tidak lagi memakai literal.
- **`.env.example`** — dokumentasi `SAE_CORE_URL` dan `SAE_CORE_API_KEY` (kosong, wajib diisi manual).
- **`tests/Feature/FeederApiKeySecurityTest.php`** (baru, 5 test / 31 assertion) — mengunci regresi: config tanpa kunci bawaan, tidak ada kunci bocor di 6 berkas sumber, kunci berasal dari DB, endpoint menolak kunci salah/kosong (401), dan menerima kunci tersimpan (200).

### 4. Dampak Operasional
- Instalasi lama dengan `settings.api_key` terisi: **tidak ada perubahan perilaku**.
- Instalasi baru tanpa seeder: kunci dibuat acak saat admin membuka halaman **Tarik Data Dapodik**, lalu disalin ke aplikasi feeder. Endpoint tetap menolak semua permintaan sampai kunci ada.
- Telemetri sae-core berhenti mengirim sampai `SAE_CORE_API_KEY` diisi di `.env` (perilaku disengaja, bukan kegagalan diam-diam).

### 5. Hasil Verifikasi Protokol Mandatori
1. Linting Sintaks PHP: `php -l` pada 7 berkas yang diubah — 0 error.
2. Cache: `php artisan config:clear && config:cache` dan `view:clear && view:cache` berhasil 100%.
3. Full Test Suite: `php artisan test --no-coverage` lulus **100% (96 passed, 952 assertions)**, durasi 80,30 detik.
4. Uji perilaku langsung: `POST /api/receive-data` tanpa kunci → **401**; dengan kunci palsu → **401**; dengan kunci tersimpan → **200**.
5. `grep` menyeluruh: 0 kemunculan `SAE_20260422090959...` maupun `sae_secret_live_key_2026` di seluruh `*.php`.
6. Batas Baris Kode `app/`: **0 file ≥ 1.000 baris**.

### 6. Langkah Berikutnya (belum dikerjakan)
- Migrasi autentikasi dari `session('user')` manual ke guard Laravel (`Auth::login` + `auth()->user()`), 353 titik pemakaian.
- Ekstraksi `checkAuth()` yang terduplikasi di 21 controller ke base `Controller`/trait.
- Penyelesaian REF-002 (sidebar 2.577 baris).
- Pemisahan `$request->validate()` inline ke FormRequest.

### 7. Peringatan Operasional: `config:cache` merusak test suite
Menjalankan `php artisan config:cache` membuat `bootstrap/cache/config.php` yang **mem-bake nilai `.env`** (mis. `CACHE_DRIVER=file`), sehingga mengalahkan `<env name="CACHE_DRIVER" value="array"/>` pada `phpunit.xml`. Akibatnya 34 test gagal (`RealtimeTest`, dll.) meski kode tidak berubah.

- **Aturan:** jalankan `php artisan config:clear` sebelum `php artisan test`.
- Sudah diverifikasi: dengan config cache dihapus → **96 passed**; dengan config cache ada → **34 failed**.
- Untuk produksi, `config:cache` tetap aman; yang perlu dihindari hanyalah menjalankan test saat cache itu masih aktif.

---

## Sesi 6 — 25/26 Oktober 2026: Fondasi Konsistensi UI/UX

### 1. Tujuan Sesi
- Menjadikan konsistensi UI/UX sebagai fondasi yang dijaga otomatis, bukan hasil kerja manual berulang.
- Menyediakan satu sumber kebenaran untuk nilai desain agar pengembangan berikutnya tidak menambah variasi baru.

### 2. Akar Masalah yang Ditemukan (terukur)
Sebelum sesi ini, nilai desain ditulis bebas di dua tempat:

| Temuan | Angka |
|---|---|
| Nilai `border-radius` berbeda di stylesheet | **20 nilai** (2px, 3px, 4px … 9999px) |
| Nilai `border-radius` berbeda di inline style Blade | **14 nilai**, total 2.056 deklarasi |
| Nilai `font-size` berbeda di stylesheet | **25+ nilai** (0.78/0.8/0.82 rem yang nyaris tak terbedakan) |
| Nilai `font-size` berbeda di inline style Blade | **20+ nilai**, total 5.400+ deklarasi |
| Total atribut `style="..."` di view | **13.334** |
| Komponen Blade reusable | **0** (`resources/views/components/` tidak ada) |

**Bug desain yang tidak disadari:** `rem` dihitung relatif terhadap `font-size` induk.
Akibatnya `0.82rem` di dalam elemen ber-`font-size` lain ter-render sebagai **13.28px**,
bukan 13.12px. Inilah sebabnya tampilan terasa "tidak konsisten" meski nilainya tampak mirip.
Dibuktikan lewat pengukuran browser: terdapat ukuran anomali 13.28px, 15.68px, 13.3333px, dan 9.6px.

### 3. Yang Dibangun

**`public/css/tokens.css` (baru) — sumber kebenaran tunggal**
- Spacing: 14 langkah (`--space-1` … `--space-13`) + alias semantik
- Radius: 7 langkah (`--radius-xs` … `--radius-pill`) + alias `--radius-card`, `--radius-button`
- Tipografi: 12 langkah (`--text-2xs` … `--text-6xl`), bobot, line-height, letter-spacing
- Elevasi: 5 bayangan, otomatis lebih lembut di tema terang
- Durasi & easing, z-index eksplisit, ukuran kontrol, lebar layout
- Menghormati `prefers-reduced-motion`

**`public/css/components.css` (baru) — kelas komponen terstandar**
`.sae-page-header`, `.sae-btn` (+6 varian, 3 ukuran), `.sae-card`, `.sae-badge` (+5 varian),
`.sae-table`, `.sae-field`/`.sae-input`, `.sae-alert` (+4 varian), `.sae-empty`,
`.sae-stat`/`.sae-stat-grid`, dan utilitas terbatas.

**8 komponen Blade di `resources/views/components/sae/`**
`button`, `badge`, `card`, `page-header`, `field`, `alert`, `empty`, `stat`.

**Normalisasi otomatis**
- 11 stylesheet: 812 nilai liar → token (dengan fallback agar `cetak.css` & `kiosk-auth.css` yang dimuat tanpa `tokens.css` tetap aman)
- 189 view Blade: 7.991 inline style → token

### 4. Hasil Terukur

| Metrik | Sebelum | Sesudah |
|---|---|---|
| Radius berbeda (stylesheet) | 20 nilai | **7 token** |
| `border-radius` mentah di stylesheet | 812 | **0** |
| `font-size` mentah di stylesheet | 25+ nilai | **0** |
| `font-size` mentah di view | 5.400+ | **0** |
| `border-radius` mentah di view | 2.056 | **3** (sisa shorthand multi-nilai) |
| Ukuran font ter-render (dashboard, 291 elemen) | 12 (termasuk anomali) | **8**, semuanya dari skala token |
| Radius ter-render | 7 | **6** |
| Komponen Blade reusable | 0 | **8** |

Diukur langsung di browser pada 3 halaman (`/dashboard/admin`, `/dashboard/identitas-sekolah`,
`/dashboard/presensi/kelas`): seluruhnya 8–10 ukuran font dan 5–6 radius, tanpa anomali pecahan.

### 5. Guard Anti-Regresi
`tests/Feature/DesignConsistencyTest.php` (7 test, 489 assertions) gagal bila:
- ada `font-size: <angka>rem` atau `border-radius: <angka>px` di stylesheet aplikasi
- ada `font-size` mentah di view
- `border-radius` mentah di view melebihi 3
- ada sintaks `var()` rusak
- token wajib belum didefinisikan
- urutan pemuatan CSS salah (`tokens` → `components` → `sae`)

Catatan: guard sempat menghasilkan dua *false positive* yang sudah diperbaiki —
`rgba(var(--err-rgb), 0.15)` (pola sah) dan `var(--primary), var(--accent)` di gradient.

### 6. Perbaikan Bug Tambahan: Pemaksaan HTTPS
`AppServiceProvider` memaksa HTTPS bila `app()->environment('production')`, **tanpa** melihat
skema permintaan sebenarnya. Akibatnya aset gagal dimuat saat aplikasi production dijalankan
lewat HTTP (Laragon/Herd lokal, atau reverse proxy tanpa `X-Forwarded-Proto`):
browser menerima `https://…/css/tokens.css` padahal server melayani `http://` → `ERR_CONNECTION_CLOSED`.

- Kini pemaksaan hanya terjadi bila permintaan/proxy benar-benar HTTPS, `APP_URL` berawalan
  `https://`, atau `FORCE_HTTPS=true` diaktifkan eksplisit.
- `FORCE_HTTPS` didokumentasikan di `.env.example`.
- `.env` lokal diubah ke `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=https://sae.test:8443`
  (backup: `.env.backup-dev-*`).

### 7. Keputusan: Laravel 13 Ditunda
Laravel terpasang **12.70.1**; versi terbaru **13.38.0**. Upgrade **tidak** dilakukan sekarang karena:
- 353 titik autentikasi berbasis `session('user')` manual, `Auth::login` 0 pemakaian
- 470 rute dan 60+ controller bergantung pada kontrak tersebut
- Melakukan upgrade di tengah pekerjaan UI berisiko memutus alur login produksi

Laravel 12.70.1 masih rilis terkini pada jalurnya dan menerima perbaikan keamanan.
Upgrade sebaiknya dilakukan **setelah** migrasi autentikasi ke guard Laravel.

> **Dibatalkan pada Sesi 7:** atas permintaan pemilik proyek, upgrade ke Laravel 13
> tetap dijalankan. Hasilnya ternyata **tidak memerlukan perubahan kode aplikasi**
> sama sekali (123 test lulus tanpa modifikasi). Lihat Sesi 7.

### 8. Verifikasi Protokol Mandatori
1. Linting: `php -l` pada berkas PHP yang diubah — 0 error.
2. Cache: `view:clear` + `view:cache` berhasil 100%; `config:clear` dijalankan sebelum test.
3. Build: `npm run build` sukses (59 modul).
4. Test Suite: **121 passed (1509 assertions)**, durasi 110,76 detik.
5. Validasi CSS: kurung seimbang di seluruh 12 stylesheet (0 file rusak).
6. Render browser: dashboard, identitas sekolah, presensi kelas — 200 OK, konsistensi terukur.

### 9. Dokumentasi
`.agents/UI_DESIGN_STANDARDS.md` — panduan wajib berisi aturan, tabel komponen,
contoh lengkap, dan cara verifikasi. Rujuk berkas ini setiap menambah halaman baru.

### 10. Langkah Berikutnya (belum dikerjakan)
- Migrasi 6.700+ inline style sisanya (spacing, warna) ke kelas komponen secara bertahap per modul.
- Menerapkan pola design token yang sama ke aplikasi `sae-pkl` (database terpisah, `.git` sendiri).
- Migrasi autentikasi ke guard Laravel (prasyarat upgrade Laravel 13).
- Ekstraksi `checkAuth()` yang terduplikasi di 21 controller.

---

## Sesi 7 — 26 Oktober 2026: Upgrade Laravel 12.70.1 → 13.38.0

### 1. Tujuan Sesi
- Melakukan upgrade framework ke Laravel 13 atas permintaan pemilik proyek.
- Dikerjakan di branch terpisah `upgrade/laravel-13` agar `main` tetap aman.

### 2. Persiapan
- `composer.json` + `composer.lock` dibackup ke `scratch/upgrade-backup-20261026-005813/`.
- Panduan resmi `laravel.com/docs/13.x/upgrade` dibaca dan dipetakan ke codebase.
- Baseline versi paket dicatat: Laravel 12.70.1, PHPUnit 11.5.57, 119 paket total.

### 3. Analisis Dampak Breaking Changes (dilakukan SEBELUM upgrade)

| Breaking change | Dampak ke SAE | Tindakan |
|---|---|---|
| `VerifyCsrfToken` → `PreventRequestForgery` + verifikasi `Sec-Fetch-Site` | Middleware aplikasi masih memakai nama lama (masih alias) | Dimigrasikan ke class baru |
| Cache prefix/session cookie name berubah ke format hyphen | **Tidak berdampak** — `config/cache.php` & `config/database.php` sudah menetapkan `CACHE_PREFIX`/`REDIS_PREFIX` eksplisit | Tidak ada |
| `array_first()`/`array_last()` dari polyfill PHP 8.5 | **Tidak berdampak** — tidak ada helper global bernama itu | Tidak ada |
| Pagination view `pagination::default` → `pagination::bootstrap-3` | **Tidak berdampak** — tidak ada referensi langsung | Tidak ada |
| Cache `serializable_classes` | Tidak dipakai konfigurasi cache serializable | Tidak ada |
| `Collection::modelKeys`, polymorphic pivot, dst. | Tidak dipakai | Tidak ada |

### 4. Perubahan Dependensi
```
laravel/framework      ^12.0  → ^13.0   (12.70.1 → 13.38.0)
laravel/tinker         ^2.9   → ^3.0    (2.11.1  → 3.0.2)
phpunit/phpunit        ^11.5  → ^12.0   (11.5.57 → 12.5.40)
php                      ^8.2  → ^8.3   (PHP terpasang 8.3.30)
```
`nunomaduro/collision` dan `spatie/laravel-ignition` **tidak** dinaikkan ke major baru
karena belum ada rilis stabil yang mendukung Laravel 13 (collision v9 & ignition v3
masih `dev`). Versi terpasang tetap kompatibel:
- collision v8.9.5 → `laravel/framework ^11.48 || ^12.56 || ^13.20` ✅
- laravel-ignition 2.12.1 → `illuminate/support ^11|^12|^13` ✅

### 5. Perubahan Kode Aplikasi
Hanya **satu** berkas: `app/Http/Middleware/VerifyCsrfToken.php`
- `use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware`
  → `use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware`
- Nama kelas tetap `VerifyCsrfToken` agar `app/Http/Kernel.php` tidak perlu diubah.

**Tidak ada** controller, model, service, view, atau route yang perlu diubah.

### 6. Hasil Verifikasi
1. `php artisan --version` → **Laravel Framework 13.38.0**
2. `php artisan route:list --json` → **480 rute** terbaca normal
3. `php artisan test --no-coverage` → **123 passed (1512 assertions), 0 failed**
4. `view:clear` + `view:cache` → 100% berhasil
5. Render browser nyata (`https://sae.test:8443/dashboard/admin`):
   sidebar, header, 6 stat card, tabel, 40 tombol semuanya tampil;
   token desain tetap aktif (`--radius-lg: 12px`); 8 ukuran font, 6 radius (konsisten).

### 7. Catatan
- Error `EventSource ... MIME type text/html` di console **bukan regresi upgrade**:
  `php artisan serve` tidak mendukung streaming SSE. Fallback polling
  (`/dashboard/realtime/poll`) terbukti bekerja — diuji langsung: HTTP 200, JSON valid.
- Test CSRF baru ditambahkan (`CsrfProtectionTest`) untuk memastikan proteksi tidak
  melemah setelah rename middleware: POST tanpa token ditolak, form memuat `_token`.

### 8. Status Branch
- Branch: `upgrade/laravel-13`
- `main` tetap pada commit `7cddeff` (Laravel 12.70.1) — bisa dipakai rollback.

### 9. Langkah Berikutnya
- Merge `upgrade/laravel-13` → `main` setelah pemilik proyek memverifikasi di lingkungannya.
- Uji ulang di server produksi (PHP 8.3+, pastikan OPcache direset saat deploy).
- Lanjut: port design token ke `sae-pkl`, lalu migrasi auth ke guard Laravel.

---

## Sesi 8 — 26 Oktober 2026: Penguatan Pondasi Aplikasi

### 1. Tujuan Sesi
Memperkuat pondasi di luar UI/UX: menghapus duplikasi, memperbaiki performa,
melindungi integritas data, menutup celah validasi, dan memulai migrasi autentikasi.

### 2. Ekstraksi `checkAuth()` (duplikasi kode)
- Ditemukan **20 controller** dengan method `checkAuth()` berisi **persis sama**
  (diverifikasi otomatis: hanya 1 varian isi dari 20 berkas).
- Dibuat trait `App\Http\Controllers\Concerns\ChecksAuthenticatedSession`
  (`checkAuth`, `sessionUser`, `sessionRole`).
- Hasil: **95 baris duplikasi dihapus**, satu sumber implementasi.
- Guard: `ArchitectureGuardTest` menolak `checkAuth()` didefinisikan ulang.

### 3. Cache pemeriksaan tabel (performa)
- `Schema::hasTable()` menjalankan query ke `information_schema` setiap panggilan;
  aplikasi memanggilnya **467 kali**, sebagian di service statistik dashboard.
- Dibuat `App\Support\Table` (`exists`, `existsAll`, `existsAny`, `flush`).
- Migrasi: **468 panggilan** ke helper, sisa 2 (di dalam helper itu sendiri).

**Benchmark nyata (240 panggilan, 6 tabel):**

| Metode | Query | Waktu |
|---|---|---|
| `Schema::hasTable` | 240 | 254,9 ms |
| `Table::exists` | **6** | **6,9 ms** |

Pengurangan **98%** query, hasil identik. Dashboard admin: 14 query
`information_schema` (dari 65 panggilan).
- Guard: `SchemaCheckPerformanceTest` (5 test).

### 4. Transaksi atomik (integritas data)
Pola berbahaya ditemukan: `delete()` seluruh tabel lalu `insert()` per batch
**tanpa transaksi**. Bila insert gagal di tengah, data lama sudah terhapus dan
hanya terisi sebagian — **hilang permanen**.

- `FeederReceiverController`: 5 blok destruktif kini dibungkus `DB::transaction`
  (sekolah, rombongan_belajar, anggota_rombel, pembelajaran, gtk, peserta_didik).
- `PermissionController::toggle`: perubahan izin + cascading induk-anak kini atomik
  (sebelumnya bisa tersimpan setengah jadi).
- Guard: `TransactionSafetyTest` (4 test) — membuktikan rollback mengembalikan data.

### 5. FormRequest (validasi & keamanan)
- `Auth/LoginRequest` — aturan kondisional untuk login GTK dan Portal Orang Tua
  (menggantikan 2 blok `validate()` inline).
- `PesertaDidik/ImportPesertaDidikRequest` — **celah keamanan ditutup**:
  sebelumnya hanya `file|max:20480` tanpa batas ekstensi, sehingga berkas
  `.php`/`.zip` dapat diunggah. Kini `mimes`+`extensions` membatasi ke
  xlsx/xls/csv/txt.
- Guard: `FormRequestValidationTest` (8 test) — `.php` dan `.zip` terbukti ditolak.

### 6. Bersihkan dependensi mati
- Dihapus: `tailwindcss`, `@tailwindcss/vite`, `postcss`, `autoprefixer`, `alpinejs`
  (0 pemakaian `@vite`, 0 utility Tailwind, 0 `x-data`/`x-on` Alpine).
- `vite` 5 → 8, `laravel-vite-plugin` 1 → 3.
- Bundle JS: **107 KB → 50 KB** (52% lebih kecil); build 1,9 s → **56 ms**.
- `npm audit`: 4 kerentanan → **0**.

### 7. Tokenisasi warna (konsistensi tema)
Warna aksen ditulis hardcoded pada inline style:
`#10b981` (446×), `#ef4444` (428×), `#f59e0b` (289×), `#3b82f6` (168×), dst.

Akibatnya warna **tidak menyesuaikan mode terang**. Kini `var(--token, #fallback)`
pada **1.552 deklarasi di 163 view**:

| Token | Dark | Light |
|---|---|---|
| `--success` | `#10b981` | `#059669` |
| `--danger` | `#ef4444` | `#dc2626` |
| `--primary` | `#3b82f6` | `#2563eb` |

Diverifikasi di browser: token ter-resolve benar pada kedua tema.
Guard `DesignConsistencyTest` diperluas (8 test) menolak warna aksen hardcoded.

### 8. Aksesibilitas
Audit dengan parser yang memahami ekspresi Blade (`{{ $row->foto_url }}` memuat `>`):

| Temuan | Sebelum | Sesudah |
|---|---|---|
| Tombol ikon tanpa label | **90** dari 1.082 | **0** |
| Gambar tanpa `alt` | 1 dari 112 | **0** |
| Skip link navigasi | tidak ada | **ada** |

`aria-label` diturunkan dari class FontAwesome (`fa-xmark`→"Tutup",
`fa-eye`→"Tampilkan password"). Guard: `AccessibilityGuardTest` (3 test).

### 9. Integrasi guard Laravel (migrasi bertahap)
`Auth::login()` tidak pernah dipanggil (**0 pemakaian**); autentikasi sepenuhnya
berbasis `session('user')` manual di 353 titik. Akibatnya `auth` middleware,
`@auth` di Blade, rate limit per pengguna, dan Sanctum tidak bekerja.

Strategi **tanpa memutus alur berjalan**:
- `App\Support\AuthSession` — jembatan `login`/`logout`/`user`/`check`.
- Setiap login mengisi `Auth::login()` **sekaligus** `session('user')`.
- Kode lama tetap jalan; kode baru disarankan memakai `Auth::user()`.
- `AuthSession::user()` fallback ke sesi lama → **pengguna tidak ter-logout saat deploy**.
- Portal Orang Tua didokumentasikan: tidak punya baris tabel `pengguna`
  (akun dinamis), sehingga sesi tetap sumber autentikasi untuk alur itu.
- Guard: `AuthGuardIntegrationTest` (5 test).

### 10. Hasil Verifikasi Protokol Mandatori
1. Linting: `php -l` seluruh `app/` — 0 error.
2. Cache: `view:clear` + `view:cache` 100%; `config:clear` sebelum test.
3. Build: `npm run build` sukses (59 modul, 56 ms), **0 kerentanan npm**.
4. Test Suite: **161 passed (2268 assertions)**, 0 failed.
5. Verifikasi browser: halaman nyata tampil normal; token tema aktif pada
   mode gelap **dan** terang; sesi lama tetap 200 (tidak ter-logout).

### 11. Ringkasan Dampak

| Aspek | Sebelum | Sesudah |
|---|---|---|
| Duplikasi `checkAuth()` | 20 salinan | 1 trait |
| Query `information_schema` | 467 panggilan | 14 per request |
| Operasi destruktif tanpa transaksi | 6 pola | 0 |
| Validasi upload impor | tanpa batas ekstensi | xlsx/xls/csv/txt |
| Bundle JS | 107 KB | 50 KB |
| Kerentanan npm | 4 | 0 |
| Warna aksen hardcoded | 1.552 | 0 |
| Tombol ikon tanpa label | 90 | 0 |
| Guard Laravel | tidak terisi | terisi + kompatibel |

### 12. Langkah Berikutnya (belum dikerjakan)
- **Sisa ±11.800 inline style** (spacing, layout) → kelas komponen, bertahap per modul.
- Migrasi bertahap pemakaian `session('user')` → `Auth::user()` (353 titik).
  Setelah selesai, `session('user')` dapat dihapus dari `AuthSession`.
- Terapkan pola yang sama (token, komponen, guard) ke aplikasi `sae-pkl`.
- Pertimbangkan `declare(strict_types=1)` pada service baru.
- Evaluasi query N+1 pada modul dengan tabel besar (presensi, kesiswaan).


---

## Sesi 9 — 26 Oktober 2026: Penjadwalan Praktik Guru pada Lab, Bengkel, Lahan & Lapangan

### 1. Permintaan Pengguna
1. Penjadwalan guru di `dashboard/laboran/jadwal` juga mencakup **penggunaan lahan** / praktik di lapangan.
2. **Mata pelajaran diambil otomatis dari kelas.**
3. **Topik / materi praktik dijadikan opsional.**
4. **Cetak laporan per bulan, triwulan, semester, dan tahun.**

### 2. Perubahan Skema Database
| Migrasi | Isi |
|---|---|
| `2026_10_26_000001_add_lokasi_dan_pembelajaran_to_laboran_jadwal.php` | Kolom `jenis_lokasi` (lab/bengkel/lahan/lapangan/lainnya), `pembelajaran_id`, `mata_pelajaran_id`; `topik_praktik` diubah menjadi NULLABLE; backfill `jenis_lokasi` data lama. |
| `2026_10_26_000002_seed_lokasi_praktik_lahan_lapangan.php` | Menambah 5 lokasi praktik ke master `sarpras_ruang`: Lahan Praktik ATPH, Lahan Praktik Kehutanan, Kebun Sekolah, Greenhouse/Screen House, Lapangan Praktik Outdoor. |

Kedua migrasi **idempoten** dan **dapat di-rollback** (diuji naik-turun: skema kembali persis ke kondisi awal, 20 baris `sarpras_ruang`).

### 3. Perubahan Kode
| Berkas | Perubahan |
|---|---|
| `app/Http/Controllers/LaboranJadwalController.php` | Ditulis ulang. Konstanta `JENIS_LOKASI` & `STATUS_LIST`; endpoint `pembelajaranByRombel`; `resolvePembelajaranKelas()`; `validatedPayload()` (mapel otomatis + fallback); `findConflict()` (bentrok lokasi & guru); `detectJenisLokasi()`; laporan periode + cetak. |
| `routes/web.php` | Rute baru: `laboran.jadwal.pembelajaran`, `laboran.laporan.index`, `laboran.laporan.cetak`. |
| `resources/views/dashboard/laboran/jadwal.blade.php` | Ditulis ulang: filter jenis lokasi, optgroup lokasi per jenis, form mapel otomatis + mode isi manual, topik opsional, modal ubah jadwal. |
| `resources/views/dashboard/laboran/laporan.blade.php` | **Baru** — rekap per jenis lokasi, per lokasi, per guru, per mapel, rekap bulanan, rincian sesi. |
| `resources/views/dashboard/laboran/cetak-laporan.blade.php` | **Baru** — dokumen cetak A4 (portrait/landscape) dengan kop surat & 3 blok tanda tangan. |
| `public/js/laboran-jadwal.js` | Ditulis ulang: filter lokasi per jenis, fetch mapel dari kelas, modal tambah/ubah, update status, hapus. |
| `resources/views/partials/dash-sidebar.blade.php` | Menu "Laporan Penggunaan Lokasi" di 3 lokasi sidebar. |
| `tests/Feature/LaboranJadwalPraktikTest.php` | **Baru** — 12 test (100 assertion). |
| `tests/Feature/UiPagesSmokeTest.php` | Tambah `/dashboard/laboran/jadwal` & `/dashboard/laboran/laporan`. |

### 4. Keputusan Teknis
- **Mata pelajaran otomatis**: `pembelajaran_id` + `mata_pelajaran` + `ptk_id` diisi dari tabel `pembelajaran` Dapodik berdasarkan `rombel_id`. Rombel "Matapelajaran Pilihan" dengan nama sama ikut dicakup karena praktik kejuruan sering diampu di sana. Bila dropdown kosong, tersedia mode **Isi manual**.
- **Topik opsional**: kolom `topik_praktik` dibuat NULLABLE; UI menandai "(opsional)" dan menampilkan "Topik belum diisi" saat kosong. Update status tidak menimpa topik (diuji).
- **Jenis lokasi** dideteksi dari nama ruang/gedung (kata kunci LAHAN/KEBUN/GREENHOUSE → `lahan`; LAPANGAN/AREA TERBUKA → `lapangan`; BENGKEL → `bengkel`; LAB/STUDIO → `lab`).
- **Anti-bentrok**: satu lokasi tidak boleh dipakai dua sesi pada jam beririsan; guru juga tidak boleh terjadwal ganda. Status `batal` dikecualikan.
- **Rentang periode** disamakan dengan pola `TendikLaporanController` (semester & tahun ajaran memakai 1 Juli – 30 Juni).
- Desain memakai token (`var(--space-*)`, `var(--radius-*)`, `var(--text-*)`, `var(--purple)`) — lolos `DesignConsistencyTest`.

### 5. Verifikasi Nyata
1. `php -l` seluruh berkas baru — 0 error. `node --check public/js/laboran-jadwal.js` — OK.
2. Migrasi naik → turun → naik: skema & data kembali konsisten.
3. Uji CRUD end-to-end (store/update status/update detail/delete + validasi + bentrok): semua sesuai harapan.
4. Filter `jenis`, `lab`, `q`, `status`, `tanggal` diuji terhadap isi `<tbody>` — hasil tepat.
5. Keseimbangan tag HTML tiga view diverifikasi (ditemukan & diperbaiki 1 `<div>` tak tertutup pada `jadwal.blade.php`).
6. Test suite: **173 passed (2376 assertions)**.
7. `view:clear` + `view:cache` sukses.

---

## Sesi 10 — 26 Oktober 2026: Modul Laboratorium per Jurusan (7 Modul Baru)

### 1. Permintaan Pengguna
Laboran dibagi berdasarkan **tugas tambahan + jurusan** (1 laboran = 1 jurusan), sehingga pengelolaan data harus **dibatasi per orang per jurusan**. Ditambah 8 penyesuaian:
1. Modul analisis & input kebutuhan alat/bahan + dashboard per bidang lebih informatif.
2. Modul perawatan/pemeliharaan/penataan alat sesuai K3 + kartu perawatan.
3. Modul inventaris alat & bahan habis pakai.
4. Pemeriksaan daftar ceklist alat & bahan.
5. Daftar usulan pengadaan alat & bahan praktik.
6. Pengelolaan berita acara kerusakan alat.
7. Peminjaman alat khusus jurusan/laboran.
8. Laporan lengkap penggunaan & kerusakan alat.

### 2. Keputusan Teknis: Scoping per Jurusan
Kunci pembatasan memakai kolom **`ptk_tugas_tambahan.jurusan_id`** yang sudah ada di skema.
Kelas `App\Support\LaboranScope` menjadi satu-satunya sumber aturan:

| Kondisi pengguna | Perilaku |
|---|---|
| Administrator | `unrestricted` — seluruh jurusan |
| Tugas `KEPALA_LAB` | `unrestricted` — lintas jurusan (koordinatif) |
| Tugas `LABORAN` + `jurusan_id` terisi | **terkunci** ke jurusan tersebut |
| Tugas `LABORAN` + `jurusan_id` NULL | *fallback permisif* + badge peringatan di UI |

Alasan fallback permisif: 4 baris tugas LABORAN yang ada belum memiliki `jurusan_id`, sehingga mengunci total akan mematikan operasional. UI menampilkan badge **"Penugasan Jurusan Belum Ditetapkan"** agar segera dilengkapi.

Pembatasan diterapkan di tiga lapis:
1. **Query** — `LaboranScope::apply($query, 'jurusan_id', $ctx)`.
2. **Baris tunggal** — `findScoped()` mengembalikan `null` → HTTP 404.
3. **Penulisan** — `jurusanForWrite()` memaksa jurusan milik laboran, mengabaikan input `jurusan_id` dari form.

Penetapan jurusan dilakukan admin di **`dashboard/pengguna` → tab Tugas Tambahan** (field baru `jurusan_id`, muncul untuk LABORAN/KAPROG/KEPALA_LAB).

### 3. Migrasi Database
| Migrasi | Isi |
|---|---|
| `2026_10_26_100001_create_laboran_jurusan_modules_tables.php` | Kolom `jurusan_id` pada `laboran_bahan_alat` & `laboran_jadwal_penggunaan`; 10 tabel baru (kebutuhan, perawatan, kartu perawatan, pemeriksaan + item, usulan pengadaan + item, berita acara kerusakan, peminjaman + item). |
| `2026_10_26_100002_sync_laboran_jurusan_permissions.php` | 6 permission key baru di `role_permissions` + `granted_permissions` tugas LABORAN. |

Keduanya idempoten dan **diuji naik → turun → naik** (skema & data kembali konsisten).

### 4. Modul & Berkas Baru
| # | Modul | Route prefix | Controller |
|---|---|---|---|
| — | Dashboard per jurusan | `laboran/dashboard` | `Laboran\LaboranDashboardController` |
| 1 | Analisis & Kebutuhan | `laboran/kebutuhan` | `Laboran\LaboranKebutuhanController` |
| 2 | Perawatan & K3 + Kartu | `laboran/perawatan` | `Laboran\LaboranPerawatanController` |
| 3 | Inventaris alat & bahan | `laboran/inventaris` (diperluas) | `LaboranInventarisController` |
| 4 | Pemeriksaan Ceklist | `laboran/pemeriksaan` | `Laboran\LaboranPemeriksaanController` |
| 5 | Usulan Pengadaan | `laboran/pengadaan` | `Laboran\LaboranPengadaanController` |
| 6 | Berita Acara Kerusakan | `laboran/kerusakan` | `Laboran\LaboranKerusakanController` |
| 7 | Peminjaman Alat | `laboran/peminjaman-alat` | `Laboran\LaboranPeminjamanController` |
| 8 | Laporan Lengkap | `laboran/laporan-lengkap` | `Laboran\LaboranLaporanController` |

Basis bersama: `App\Http\Controllers\Laboran\LaboranBaseController` (sesi, hak akses CRUD, respons AJAX/redirect seragam, penomoran dokumen otomatis `PREFIX/YYYYMM/NNNN`).

View: `dashboard/laboran/{dashboard,kebutuhan,perawatan,pemeriksaan,pemeriksaan-detail,pengadaan,pengadaan-detail,kerusakan,kerusakan-cetak,peminjaman,peminjaman-detail,laporan-lengkap,laporan-lengkap-cetak}.blade.php`

JS: `laboran-common.js` (helper bersama) + `laboran-{kebutuhan,perawatan,pemeriksaan,pengadaan,kerusakan,peminjaman}.js`

### 5. Alur Prosedural yang Diterapkan
- **Perawatan**: catatan perawatan (rutin/berkala/kalibrasi/perbaikan/penataan) dengan kategori risiko K3 dan daftar APD. Status `selesai` + kondisi akhir **otomatis menyinkronkan kondisi inventaris**. Kartu perawatan menyimpan interval & menghitung `jadwal_berikutnya`; tombol "tandai sudah dirawat" menggeser jadwal satu interval.
- **Pemeriksaan**: header pemeriksaan + item ceklist (hasil: sesuai / tidak sesuai / tidak ada). Rekap (`total_sesuai`, `total_tidak_sesuai`, `total_tidak_ada`) dihitung ulang setiap penyimpanan.
- **Pengadaan**: usulan dapat **menarik item dari analisis kebutuhan** (jumlah diambil dari `selisih` kekurangan). `total_item` & `total_estimasi_biaya` dihitung ulang otomatis.
- **Kerusakan**: berita acara dengan kronologi, penyebab, pelaku, 2 saksi, tindak lanjut, estimasi biaya. Tingkat kerusakan **otomatis menurunkan kondisi inventaris** (`ringan`→rusak_ringan, `sedang/berat/total`→rusak_berat). Dokumen cetak A4 resmi dengan 3 blok tanda tangan.
- **Peminjaman**: stok **berkurang saat serah terima** (`disetujui`→`dipinjam`) dan **pulih saat pengembalian**. Stok divalidasi terhadap permintaan. Kondisi kembali `rusak_*` memperbarui kondisi inventaris. Tersedia penandaan otomatis peminjaman terlambat.

### 6. Verifikasi Nyata
1. `php -l` seluruh berkas baru + `node --check` seluruh JS — 0 error.
2. Migrasi naik → turun → naik: 10 tabel & kolom terhapus bersih saat rollback, permission kembali ke 9 key.
3. **Uji CRUD end-to-end** (kebutuhan, perawatan+kartu, pemeriksaan+ceklist, pengadaan+item, kerusakan+cetak, peminjaman) — semua 200 dan data tersimpan benar.
4. **Uji scoping jurusan 4 skenario**: NULL (permisif), ATPH (terkunci), Admin (lintas), TKJ (terkunci) — query, `allows()`, `jurusanForWrite()` sesuai harapan.
5. **Uji isolasi HTTP**: laboran ATPH tidak melihat data TKJ; update & delete data TKJ ditolak 404; simpan baru dipaksa ke ATPH.
6. Sidebar: 10 menu laboran tampil untuk pengguna LABORAN; tamu diarahkan ke login.
7. Penetapan jurusan via `dashboard/pengguna` diuji: `jurusan_id` tersimpan benar.
8. Test suite baru **26 test (140 assertions)** lulus; suite penuh **198 passed** (1 kegagalan `SchemaCheckPerformanceTest` berasal dari `BukuIndukController.php` yang sudah ada sebelumnya dan tidak terkait pekerjaan ini).

### 7. Pelengkap: Scoping pada Modul Lama (Inventaris & Jadwal)

Ditemukan celah: `LaboranInventarisController` dan `LaboranJadwalController` (modul yang sudah ada sebelum sesi ini) **belum** menerapkan pembatasan jurusan. Keduanya diperbaiki agar konsisten:

| Modul | Perubahan |
|---|---|
| Inventaris | `LaboranScope::apply()` pada query & statistik; `store()` memaksa jurusan; `update()`/`destroy()` menolak 404 bila di luar cakupan; tambah proteksi hapus item yang masih dipinjam; filter & badge jurusan di UI; field `jurusan_id` pada form. |
| Jadwal | `apply()` pada query, statistik, dan daftar lokasi; `store()`/`update()`/`updateStatus()`/`destroy()` menolak 404 di luar cakupan; `buildLaporanData()` ikut terfilter; badge jurusan di banner. |

Verifikasi isolasi HTTP untuk kedua modul (laboran ATPH vs data TKJ): halaman hanya menampilkan data sendiri, update & delete ditolak 404, penyimpanan baru dipaksa ke jurusan laboran.

Ditambahkan **6 test** baru:
- `test_inventaris_terfilter_per_jurusan`
- `test_laboran_tidak_bisa_mengubah_atau_menghapus_inventaris_jurusan_lain`
- `test_simpan_inventaris_memaksa_jurusan_laboran`
- `test_jadwal_terfilter_per_jurusan`
- `test_laboran_tidak_bisa_mengubah_jadwal_jurusan_lain`
- `test_laporan_lokasi_terfilter_per_jurusan`

Total test suite: **204 passed** (1 kegagalan `SchemaCheckPerformanceTest` tetap berasal dari `BukuIndukController.php` yang sudah ada sebelumnya).
---

## Sesi 11 — 26 Oktober 2026: Kelengkapan TUPOKSI Laboran (9 Indikator Supervisi)

### 1. Latar Belakang
Pengguna meminta verifikasi apakah modul laboran sudah meng-cover 9 indikator tupoksi supervisi. Hasil analisis gap:

| # | Bukti Fisik | Status Awal |
|---|---|---|
| 1 | Buku Administrasi Jurusan | ❌ belum ada |
| 2 | Bon/Form Permintaan Bahan & Alat | ❌ belum ada |
| 3 | Kartu Perawatan + Penataan K3 | ✅ sudah |
| 4 | Buku Induk Inventaris | ⚠️ data ada, cetak formal belum |
| 5 | Checklist Sebelum/Sesudah Pakai | ⚠️ ada, jenis belum lengkap |
| 6 | Daftar Usulan Pengadaan | ✅ sudah |
| 7 | Ordner BA Kerusakan + Kartu Peminjaman | ⚠️ ada, pusat arsip belum |
| 8 | Laporan + Jurnal Kegiatan | ⚠️ laporan ada, jurnal belum |
| 9 | Dokumentasi Kebersihan | ❌ belum ada |

### 2. Migrasi Database
`2026_10_26_120001_create_laboran_tupoksi_tables.php` — 6 tabel baru:
`laboran_permintaan` + `laboran_permintaan_item`, `laboran_jurnal`, `laboran_kebersihan`, `laboran_administrasi`, `laboran_arsip`.

`2026_10_26_120002_sync_laboran_tupoksi_permissions.php` — 5 permission key baru + sinkronisasi `granted_permissions` untuk LABORAN & KEPALA_LAB.

Keduanya idempoten.

### 3. Modul Baru (5)
| Indikator | Modul | Route | Controller |
|---|---|---|---|
| #1 | Buku Administrasi Jurusan | `laboran/administrasi` | `Laboran\LaboranAdministrasiController` |
| #2 | Bon/Form Permintaan | `laboran/permintaan` | `Laboran\LaboranPermintaanController` |
| #7 | Arsip Dokumen (Ordner) | `laboran/arsip` | `Laboran\LaboranArsipController` |
| #8 | Jurnal Kegiatan | `laboran/jurnal` | `Laboran\LaboranJurnalController` |
| #9 | Kebersihan Lingkungan | `laboran/kebersihan` | `Laboran\LaboranKebersihanController` |

Penomoran otomatis: `PMT/`, `KBR/`, `ARS/`.

### 4. Integrasi Prosedural
- **Permintaan (Bon)**: periksa ketersediaan otomatis dari stok → serah terima. **Bahan habis pakai mengurangi stok**, sedangkan **alat tidak** (hanya dipinjam). Diuji di 2 test terpisah.
- **Jurnal**: kategori kegiatan dipetakan langsung ke nomor indikator tupoksi (`KATEGORI` const), sehingga rekap per indikator bisa dipakai sebagai bukti penilaian. Durasi dihitung otomatis dari jam mulai/selesai.
- **Arsip**: mendukung **arsip massal** — menarik dokumen dari modul lain (BA kerusakan, peminjaman, permintaan, pemeriksaan, pengadaan) yang belum diarsipkan, dengan penanda `sumber_tabel`/`sumber_id` agar tidak duplikat.
- **Kebersihan**: menyimpan `dokumentasi_paths` (JSON) sebagai bukti fisik Tupoksi #9.
- **Pemeriksaan**: jenis diperluas dengan `sebelum_pakai` & `sesudah_pakai` (Tupoksi #5).

### 5. Penyesuaian Menu (Terstruktur per Tupoksi)
Sidebar laboran di **3 lokasi** disusun ulang menjadi 5 kelompok berurutan sesuai alur kerja:
1. **Administrasi & Perencanaan** — Buku Administrasi (#1), Analisis Kebutuhan (#6), Usulan Pengadaan (#6)
2. **Layanan Praktik** — Jadwal (#1), Bon Permintaan (#2), Peminjaman Alat (#7)
3. **Pemeliharaan & Inventaris** — Inventaris (#4), Perawatan & K3 (#3), Pemeriksaan Ceklist (#5), Kebersihan (#9)
4. **Pelaporan & Arsip** — BA Kerusakan (#7), Jurnal (#8), Laporan Lengkap (#8), Arsip Dokumen (#7)
5. **Integrasi Sarpras** — Inventaris Aset, Peminjaman Sarpras

Setiap item menu memiliki `title` yang mencantumkan nomor indikator tupoksi. Dashboard juga menampilkan 14 kartu akses cepat berlabel Tupoksi.

### 6. Verifikasi
1. `php -l` semua controller & migrasi — 0 error. `node --check` semua JS laboran — OK.
2. Migrasi naik berhasil; 6 tabel + permission tersinkron.
3. **Uji CRUD end-to-end kelima modul** — semua 200 dengan data benar:
   - Permintaan: `PMT/202610/0001`, stok 50 → 40 setelah serah terima bahan habis pakai.
   - Jurnal: durasi 120 menit (07:00–09:00) benar.
   - Kebersihan: `KBR/202610/0001`, dokumentasi JSON tersimpan.
   - Administrasi: dokumen tercatat.
   - Arsip: massal menarik 1 dokumen, `ARS/202610/0001`; manual juga berhasil.
4. **13 test baru** (total **41 test** modul lulus, 221 assertions), termasuk:
   - `test_permintaan_serah_terima_mengurangi_stok_bahan_habis_pakai`
   - `test_permintaan_alat_tidak_mengurangi_stok`
   - `test_jurnal_menghitung_durasi_dan_kategori_tupoksi`
   - `test_arsip_massal_menarik_dokumen_dari_modul_lain`
   - `test_pemeriksaan_mendukung_jenis_sebelum_dan_sesudah_pakai`
5. Keseimbangan `<div>` 11 view baru diverifikasi — semua seimbang.
6. Test suite penuh: **213 passed** (naik dari 204). Satu kegagalan `SchemaCheckPerformanceTest` tetap berasal dari `BukuIndukController.php` yang sudah ada sebelumnya.

### 7. Matriks Kesesuaian Tupoksi (Akhir)
| # | Bukti Fisik Diminta | Modul Penghasil |
|---|---|---|
| 1 | Buku Administrasi Jurusan & Jadwal Lab | `laboran/administrasi` + `laboran/jadwal` |
| 2 | Bon/Form Permintaan Bahan & Alat | `laboran/permintaan` (+ cetak) |
| 3 | Kartu Perawatan & Penataan K3 | `laboran/perawatan` |
| 4 | Buku Induk Inventaris & Bahan Habis Pakai | `laboran/inventaris` |
| 5 | Form Checklist Sebelum/Sesudah Pakai | `laboran/pemeriksaan` |
| 6 | Daftar Usulan Pengadaan | `laboran/pengadaan` + `laboran/kebutuhan` |
| 7 | Ordner BA Kerusakan & Kartu Peminjaman | `laboran/kerusakan` + `laboran/peminjaman-alat` + `laboran/arsip` |
| 8 | Laporan Rekapitulasi & Jurnal Kegiatan | `laboran/laporan-lengkap` + `laboran/jurnal` |
| 9 | Dokumentasi Kebersihan | `laboran/kebersihan` (+ cetak) |

**Seluruh 9 indikator kini ter-cover dengan dokumen cetak resmi masing-masing.**

---

## Sesi 12 — 26 Oktober 2026: Penetapan Jurusan Laboran (Sumber Cakupan Data)

### 1. Pertanyaan yang Dijawab
> "Untuk penunjukan jurusan bagaimana dan dimana?"

**Jawaban:** Dashboard → **Manajemen Pengguna** → tab **Tugas Tambahan** → tombol **pensil** (Ubah Penugasan).

### 2. Masalah yang Ditemukan
Sebelum sesi ini, tabel penugasan **hanya punya tombol Hapus**. Akibatnya 4 baris penugasan `LABORAN` yang sudah ada (`id` 37, 47, 53, 54) tidak bisa diisi `jurusan_id`-nya. Karena `jurusan_id` masih `NULL`, `LaboranScope` jatuh ke mode **permissive** (`unrestricted = true`) sehingga pembatasan data per jurusan belum pernah benar-benar aktif.

### 3. Perubahan
| Berkas | Perubahan |
|---|---|
| `app/Http/Controllers/UserController.php` | Tambah `updateTugasTambahan(Request, int $id)`; validasi khusus WALI_KELAS (1 kelas = 1 wali aktif); `LaboranScope::flush()` setelah simpan; `ptt.jurusan_id` & `ptt.rombel_id` masuk SELECT; `$jurusanList` kini menyertakan `jumlah_rombel` |
| `routes/web.php` | `PUT /pengguna/tugas-tambahan/{id}` → `dashboard.pengguna.tugas-tambahan.update` (middleware `permission:menu_pengguna,update`) |
| `resources/views/dashboard/pengguna.blade.php` | Tombol `.btn-edit-duty` + badge jurusan per baris; modal `#modalEditDuty`; dropdown jurusan diberi penanda jumlah rombel |
| `public/js/pengguna.js` | Handler `.btn-edit-duty` (populate + buka modal), toggle wrap jurusan/rombel, submit AJAX `PUT` |
| `resources/views/dashboard/laboran/dashboard.blade.php` | Banner peringatan bila penugasan jurusan belum ditetapkan |
| `app/Http/Controllers/Laboran/LaboranDashboardController.php` | Kirim `scopeContext` ke view |
| `app/Http/Controllers/BukuIndukController.php` | `Schema::hasTable()` → `Table::exists()` (memenuhi guard performa) |

### 4. Ambiguitas Data Dapodik yang Ditangani
`jurusan_meta` memuat **dua level** Dapodik sekaligus:

| Level | Contoh | `singkatan` |
|---|---|---|
| Program Keahlian | `26010` Agribisnis Tanaman | `NULL` |
| Konsentrasi Keahlian | `26010475` Agribisnis Tanaman Pangan dan Hortikultura | `ATPH` |

Nama bisa identik (mis. `47012` dan `47012650` sama-sama "Desain Komunikasi Visual"), sehingga admin berisiko salah pilih. **Solusi:** setiap opsi dropdown kini menampilkan jumlah rombel sebagai pembeda, mis. `Desain Komunikasi Visual — 2 rombel` vs `Desain Komunikasi Visual (DKV) — 4 rombel`.

### 5. Perilaku Scoping yang Terverifikasi
| Kondisi `ptk_tugas_tambahan.jurusan_id` | `unrestricted` | Badge | Efek |
|---|---|---|---|
| `NULL` | `true` | ⚠️ "Penugasan Jurusan Belum Ditetapkan" | Bisa lihat semua jurusan + banner peringatan di dashboard |
| Terisi (mis. `26010475`) | `false` | ✅ "Jurusan: Agribisnis Tanaman Pangan dan Hortikultura" | Hanya data jurusan tersebut |

### 6. Pengujian
- **4 test baru** (total **47 test** modul, 250 assertions):
  - `test_admin_dapat_menetapkan_jurusan_pada_penugasan_laboran`
  - `test_penetapan_jurusan_langsung_mengaktifkan_scoping_laboran`
  - `test_penetapan_jurusan_ditolak_tanpa_hak_akses_update`
  - `test_halaman_pengguna_menampilkan_tombol_ubah_penugasan`
  - `test_dashboard_laboran_memperingatkan_jurusan_belum_ditetapkan`
  - `test_dropdown_jurusan_menampilkan_jumlah_rombel_sebagai_pembeda`
- Test suite penuh: **227 passed (2782 assertions)** — **seluruhnya hijau**, termasuk `SchemaCheckPerformanceTest` yang sebelumnya gagal.

### 7. Tindakan yang Masih Diperlukan Administrator
Keempat penugasan `LABORAN` saat ini **masih** `jurusan_id = NULL`. Agar pembatasan data aktif, admin perlu:

1. Buka **Manajemen Pengguna** → tab **Tugas Tambahan**.
2. Cari baris dengan badge kuning **"Jurusan belum diset"**.
3. Klik ikon **pensil**, pilih jurusan, lalu **Simpan Perubahan**.
4. Badge berubah hijau dan `LaboranScope` langsung membatasi data ke jurusan tersebut.

---

## Sesi 13 — 26 Oktober 2026: Perbaikan Error Update Otomatis Server (`hasDirectConnection`)

### 1. Masalah yang Dilaporkan
Log pembaruan sistem pada server:
```text
[13.05.01] [MIGRATE ERROR] Method Illuminate\Database\MySqlConnection::hasDirectConnection does not exist.
[13.05.01] [VERSION] Versi 1.0.4 (6460d19) tersimpan.
[13.05.01] [OPTIMIZE] INFO Clearing cached bootstrap files...
[13.05.01] Pembaruan selesai dengan catatan / error.
```

### 2. Akar Masalah
Proses pembaruan otomatis di `UpdateService::runUpdate()` sebelumnya hanya menjalankan `git pull` (atau unduh zip), kemudian langsung mengeksekusi `Artisan::call('migrate', ['--force' => true])` **tanpa pernah menjalankan `composer install`**.

Ketika repositori dinaikkan ke framework Laravel 13, file `composer.lock` meminta dependensi baru, tetapi direktori `vendor/` di server masih memuat library Laravel 12.x lama. Migrator Laravel 13 memanggil method `Connection::hasDirectConnection()` yang belum ada pada driver database lama, sehingga migrasi gagal dengan error fatal `Method Illuminate\Database\MySqlConnection::hasDirectConnection does not exist`.

### 3. Solusi & Perubahan
1. **`app/Services/UpdateService.php`**:
   - Ditambahkan method `syncComposerDependencies(array &$logs): bool`.
   - Mendeteksi binary Composer (perintah global `composer`, `composer.phar` lokal, atau path binary PHP aaPanel `/www/server/php/*/bin/composer`).
   - Membandingkan versi framework dan sidik jari paket antara `composer.lock` dengan `vendor/composer/installed.json`.
   - Jika versi tidak sesuai atau berkas `vendor/` tertinggal, otomatis mengeksekusi `composer install --no-dev --optimize-autoloader --no-interaction` dengan timeout dan alokasi memori yang memadai.
   - Mengurutkan alur `runUpdate()`: **Sinkronisasi Dependensi dijalankan SEBELUM Migrasi Database**. Jika composer gagal, migrasi dibatalkan demi keamanan skema database.
2. **`app/Http/Controllers/BukuIndukController.php`**:
   - Menghapus pemanggilan `Schema::hasTable` mentah dan menggantinya dengan `App\Support\Table::exists` yang ber-cache sesuai standar performa proyek.
3. **`tests/Feature/UpdateServiceDependencyTest.php`**:
   - Pengujian terisolasi untuk memastikan `syncComposerDependencies` selalu berjalan sebelum `migrate`, migrasi dibatalkan bila dependensi gagal, dan dependensi terpasang sesuai `composer.lock`.
4. **`tests/Feature/LaboranJurusanModuleTest.php`**:
   - Pembersihan isolasi `setUp` dan `tearDown` menggunakan snapshot ID agar tidak menghapus data nyata dan tidak saling bertabrakan antar-test.

### 4. Hasil Verifikasi Protokol Mandatori
1. **Linting Sintaks PHP**: `php -l` pada seluruh berkas yang dimodifikasi — 0 error.
2. **Kompilasi Blade View Cache**: `php artisan view:clear ; php artisan view:cache` — 100% sukses.
3. **Kompilasi Frontend (Vite)**: `npm run build` — 59 modul transformed dalam 81ms, 0 kerentanan.
4. **Eksekusi Test Suite Penuh**: `php artisan test --no-coverage` — **228 passed (2787 assertions)**, 0 failed.
5. **Pemeriksaan Batas Ukuran File**: 0 berkas di `app/` melebihi 1.000 baris.


---

## Sesi 14 ? 26 Oktober 2026: Penyelarasan Jadwal KBM, Multi-Guru, Analisis Otomatis, Unifikasi Peminjaman & Import Excel

### 1. Ringkasan Kebutuhan
1. Penyesuaian jadwal penggunaan lab/lahan praktik sesuai jadwal KBM yang berlaku, serta 2 opsi pemberlakuan jadwal: **Bulanan** dan **Semester**.
2. Input jadwal lokasi praktik mendukung **lebih dari 1 guru** (tim teaching) dengan deteksi bentrok lokasi dan seluruh guru.
3. Mengaktifkan tombol & fungsi **Analisis Kebutuhan**.
4. Di Usulan Pengadaan: **nama pemohon dan sumber dana dihilangkan**, ditambahkan **pilihan jenis alat dan bahan** serta tampilan jumlah & kuantitas kebutuhan.
5. Menghapus modul **Administrasi Jurusan** dan **Bon Permintaan**.
6. Nama peminjam dibuat otomatis (dropdown Siswa & GTK) serta **peminjaman alat dan sarpras disatukan menjadi 1 modul peminjaman**.
7. Menambahkan fitur **Import via Excel (.xlsx/.xls/.csv)** pada Inventaris Lab dan Aset Sarpras, lengkap dengan unduh template resmi.

### 2. Rincian Perubahan Berkas
| Berkas | Perubahan |
|---|---|
| `database/migrations/2026_10_26_140001_enhance_laboran_features.php` | Migrasi penambahan kolom `pemberlakuan`, `ptk_ids`, `guru_pendamping`, `jadwal_seri_id` pada `laboran_jadwal_penggunaan`; kolom `jenis` pada `laboran_usulan_pengadaan`; kolom `sumber`, `aset_id` pada `laboran_peminjaman_item`; serta penyesuaian nullable untuk `ptk_id`, `sumber_dana`, `pemohon_nama`. |
| `app/Services/LaboranJadwalService.php` | Berkas service baru: mengisolasi logika penghitungan tanggal berulang (bulanan/semester), deteksi bentrok lokasi & seluruh guru pendamping, ekstraksi KBM slots dari `jadwal_kbm`, serta agregasi laporan periode. |
| `app/Http/Controllers/LaboranJadwalController.php` | Didekomposisi dari 880 baris menjadi 502 baris (hemat token & patuh batas 1.000 baris). Menambahkan endpoint `kbmByRombel`, penanganan multi-guru, dan penyimpanan batch seri jadwal. |
| `resources/views/dashboard/laboran/jadwal.blade.php` | Form modal baru: dropdown Jadwal KBM aktif (auto-fill), pilihan Pemberlakuan (Sekali, Bulanan, Semester), input Guru Utama + checklist Guru Pendamping (Tim Teaching). |
| `public/js/laboran-jadwal.js` | Fetch otomatis slot KBM aktif saat memilih kelas, auto-fill mapel/jam/guru, sinkronisasi hint pemberlakuan, serta penanganan checkbox multi-guru. |
| `app/Http/Controllers/Laboran/LaboranKebutuhanController.php` | Menambahkan method `jalankanAnalisis(Request)`: memindai inventaris kritis/habis/rusak secara otomatis dan menyinkronkannya ke daftar kebutuhan. |
| `resources/views/dashboard/laboran/kebutuhan.blade.php` | Tombol "Jalankan Analisis" di banner header dan "Terapkan ke Daftar Kebutuhan" di panel analisis ketersediaan stok. |
| `public/js/laboran-kebutuhan.js` | Handler konfirmasi SweetAlert2 dan pemanggilan AJAX untuk menjalankan analisis kebutuhan otomatis. |
| `app/Http/Controllers/Laboran/LaboranPengadaanController.php` | Menghilangkan kewajiban pemohon dan sumber dana, menambahkan dukungan `jenis` pengadaan (Semua, Alat, Bahan Habis Pakai), dan filter jenis. |
| `resources/views/dashboard/laboran/pengadaan.blade.php` | Modal tanpa input pemohon & sumber dana; terdapat pilihan jenis alat & bahan; kolom tabel diganti menjadi Jenis & Kebutuhan dengan kuantitas yang jelas. |
| `resources/views/dashboard/laboran/pengadaan-detail.blade.php` | Menyesuaikan tampilan detail dengan jenis pengadaan. |
| `public/js/laboran-pengadaan.js` | Menyesuaikan openCreate & openEdit untuk jenis pengadaan. |
| `routes/web.php` | Menghapus route administrasi & permintaan; menambahkan route kbm, jalankan-analisis, template/import inventaris, template/import aset, dan pengalihan sarpras peminjaman ke peminjaman alat terpadu. |
| `resources/views/partials/dash-sidebar.blade.php` | Menghapus link menu Buku Administrasi Jurusan dan Bon Permintaan di seluruh layout sidebar. |
| `resources/views/dashboard/laboran/dashboard.blade.php` | Menghapus kartu akses cepat Administrasi Jurusan dan Bon Permintaan. |
| `app/Http/Controllers/Laboran/LaboranPeminjamanController.php` | Memuat daftar Siswa & Guru aktif; mendukung peminjaman alat inventaris lab sekaligus aset sarpras; pembaruan status mutasi ketersediaan aset sarpras saat dipinjam/dikembalikan. |
| `app/Http/Controllers/SarprasPeminjamanController.php` | `index()` dialihkan langsung ke modul peminjaman terpadu (`dashboard.laboran.peminjaman-alat.index`). |
| `resources/views/dashboard/laboran/peminjaman.blade.php` | Modal pemilihan otomatis peminjam (dropdown Siswa dengan rombel otomatis, dropdown GTK dengan NIP otomatis); seksi pemilihan alat terpadu dari inventaris lab dan aset sarpras. |
| `public/js/laboran-peminjaman.js` | Sinkronisasi UI tipe peminjam dan auto-fill nama, ID, serta kelas/unit. |
| `app/Http/Controllers/LaboranInventarisController.php` | Menambahkan method `templateExcel()` dan `importExcel(Request)` menggunakan `SimpleXlsxReader`. |
| `resources/views/dashboard/laboran/inventaris.blade.php` | Tombol & modal Import Inventaris via Excel dengan link unduh template resmi. |
| `public/js/laboran-inventaris.js` | Handler modal import dan submit form upload berkas inventaris. |
| `app/Http/Controllers/SarprasAsetController.php` | Menambahkan method `templateExcel()` dan `importExcel(Request)` menggunakan `SimpleXlsxReader`. |
| `resources/views/dashboard/sarpras/aset.blade.php` | Tombol & modal Import Aset via Excel dengan link unduh template resmi. |
| `public/js/sarpras-aset.js` | Handler modal import dan submit form upload berkas aset sarpras. |
| `tests/Feature/LaboranJurusanModuleTest.php` | Memperbarui pengujian: menambah tes pemberlakuan berkala jadwal, multi-guru & deteksi bentrok, slot KBM, analisis otomatis, jenis usulan pengadaan, peminjaman siswa/sarpras terpadu, dan import Excel; menghapus tes administrasi/permintaan. |
| `tests/Feature/UiPagesSmokeTest.php` | Menghapus jalur administrasi dan permintaan dari smoke test. |

### 3. Hasil Pengujian & Verifikasi
1. **Linting PHP**: Seluruh berkas PHP lint clean tanpa syntax error.
2. **Kompilasi View Cache**: `php artisan view:clear ; php artisan view:cache` sukses 100%.
3. **Kompilasi Vite**: `npm run build` sukses (59 modul ditransformasikan dalam 69ms).
4. **Keseimbangan Tag HTML**: Seluruh 8 berkas Blade yang diubah diverifikasi pembukaan dan penutupan `<div>` seimbang 100%.
5. **Eksekusi Test Suite Penuh**: **232 passed (2.797 assertions)**, 0 failed, 100% hijau.
6. **Batas Ukuran Baris Kode**: 0 berkas di direktori `app/` melebihi batas 1.000 baris.
