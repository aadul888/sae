# ACUAN HAK AKSES (RBAC), SIDEBAR, DAN MODUL SISTEM SAE

Dokumen ini adalah acuan tunggal cara kerja hak akses di SAE. Sumber kebenaran adalah kode di `app/Models/RolePermission.php`; bila dokumen dan kode berbeda, kode yang benar dan dokumen wajib diperbarui.

---

## 1. Tujuan Hak Akses

Halaman **Pengaturan Hak Akses** (`/dashboard/hak-akses`) hanya melakukan dua hal:

1. **Memunculkan menu & submenu di sidebar** untuk suatu peran (kolom **Lihat**).
2. **Mengatur CRUD tiap modul** yang muncul (kolom **Tambah / Lihat / Ubah / Hapus**).

Tidak ada tab "Semua Peran (Global)". Konfigurasi dilakukan per peran, satu tab satu peran.

---

## 2. Arsitektur Otorisasi

```
RolePermission::canAccess($user, $key, $action)
  1. Admin  -> baris role 'admin' di role_permissions (dashboard & hak_akses selalu true)
  2. Peserta didik Koordinator Kelas -> mewarisi izin Wali Kelas
  3. Baris role ada?
       - is_allowed/can_read = false -> TOLAK (tugas tambahan tidak bisa bypass)
       - modul "spesifik tugas" (isDutySpecificPermission) -> wajib punya tugas tambahan terkait
       - lolos -> cek kolom can_{action}
  4. Baris role TIDAK ada -> izinkan hanya jika tugas tambahan aktif membukanya (hasDutyPermission)
  5. Belum ada data DB -> fallback default (isDefaultAllowed)
```

| Lapis | Sumber | Fungsi |
| :--- | :--- | :--- |
| Gerbang utama | tabel `role_permissions` (role, permission_key, is_allowed, can_create/read/update/delete) | Menentukan modul & CRUD per peran |
| Gerbang kedua | `ptk_tugas_tambahan` + `ref_tugas_tambahan` (kolom `granted_permissions` + peta bawaan di `hasDutyPermission`) | Membuka modul khusus bidang bagi pemegang tugas |
| Larangan | `RolePermission::FORBIDDEN_MODULES` | Kombinasi peran-modul yang tidak pernah berlaku |

Peran: `admin`, `guru`, `tendik`, `peserta_didik`, `orang_tua`.

Middleware route: `permission:{key}` atau `permission:{key},{action}`.

---

## 3. Prinsip Pemisahan Pengelolaan (Wajib Dijaga)

Guru, tendik, dan bidang tugas tambahan adalah **pengelolaan yang berbeda**. Menu hanya muncul pada grup yang sesuai.

| Pemegang | Grup sidebar yang muncul |
| :--- | :--- |
| Guru | **Guru** (Presensi Mengajar, Presensi Kelas, Jurnal & Agenda KBM, Jadwal Mengajar) |
| Guru + Wali Kelas | + **Dashboard Wali Kelas** |
| Guru + Kepala TAS | + **Kepala TAS** |
| Guru + Guru Piket | + **Guru Piket** |
| Tendik dengan bidang (persuratan, kesiswaan, kepegawaian, sarpras, laboran, perpustakaan, teknisi, keamanan, penjaga) | **Tendik** (submenu per bidang, plus Target & Capaian, Aktivitas Harian, Laporan Kinerja) |
| Tendik yang hanya Guru Piket | **hanya Guru Piket** (tanpa grup Guru dan Tendik); Dashboard Guru Piket + Target/Aktivitas/Laporan bidang piket ada di dalam grup itu |
| Peserta didik / orang tua | Portal Peserta Didik / Portal Pemantauan Siswa |

Aturan sidebar (`resources/views/partials/dash-sidebar.blade.php`):
- Grup **Guru** hanya untuk peran `guru` dan `admin`. Tugas tambahan tidak memunculkan grup Guru pada tendik.
- Grup **Tendik** disembunyikan bila tendik hanya memegang Guru Piket (`$isOnlyPiketTendik`).
- Setiap link wajib dibungkus `@if ($can('menu_xxx'))`. Jangan menaruh link tanpa gerbang izin.

### Modul terlarang per peran (`FORBIDDEN_MODULES`)

| Peran | Modul yang tidak berlaku |
| :--- | :--- |
| guru | `menu_identitas_siswa`, `menu_surat_izin_pd`, `menu_riwayat_rfid`, `menu_rapor`, `menu_validasi_berkas` |
| tendik | `menu_identitas_siswa`, `menu_surat_izin_pd`, `menu_riwayat_rfid`, `menu_rapor`, `menu_presensi_mengajar`, `menu_agenda_kbm`, `menu_presensi_peserta_didik` |
| peserta_didik | `menu_presensi_mengajar`, `menu_agenda_kbm`, `menu_presensi_peserta_didik`, `menu_target_capaian`, `menu_aktivitas_tendik`, `menu_laporan_tendik` |
| orang_tua | modul presensi/KBM guru, modul kinerja tendik, `menu_validasi_berkas` |

Modul terlarang: tidak tampil di tabel maupun "Tambah Modul", ditolak oleh endpoint toggle, dan dihapus saat sinkronisasi. Contoh: **Identitas Lengkap Siswa (Dapodik)** hanya untuk `admin`, `peserta_didik`, `orang_tua`.

Catatan: tugas tambahan Guru Piket tetap membuka `menu_presensi_mengajar` dan `menu_agenda_kbm` lewat gerbang tugas (bukan baris peran), sehingga tidak bertabrakan dengan larangan untuk tendik.

---

## 4. Katalog Modul (Default Peran)

Default di bawah adalah nilai `roles` pada `getBasePermissions()`. Admin selalu memiliki seluruh modul. Peran lain mendapat modul tambahan lewat **Tambah Modul ke Peran** di halaman hak akses, atau lewat tugas tambahan.

| Kelompok | Permission key | Default selain admin |
| :--- | :--- | :--- |
| Menu Utama | `menu_dashboard` | guru, tendik, peserta_didik, orang_tua |
| Menu Utama | `menu_dapodik` | - |
| Master Data | `menu_kompetensi_keahlian`, `menu_rombel`, `menu_pembelajaran`, `menu_jadwal_kbm`, `menu_kalender_pendidikan` | - |
| Manajemen Data | `menu_peserta_didik_aktif/_tidak_aktif`, `menu_guru_aktif/_tidak_aktif`, `menu_tendik_aktif/_tidak_aktif`, `menu_berkas_peserta_didik`, `menu_perubahan_data` | - |
| Layanan Digital | `menu_formulir`, `menu_pengumuman`, `menu_rfid`, `menu_e_izin`, `menu_poin`, `menu_kelulusan`, `menu_buku_tamu`, `menu_agenda` | - |
| Administrasi Guru | `menu_presensi_mengajar`, `menu_agenda_kbm`, `menu_presensi_peserta_didik` | guru |
| Administrasi Guru | `menu_jadwal_pelajaran` (jadwal) | guru, peserta_didik, orang_tua |
| Wali Kelas | `menu_wali_kelas`, `_aktif`, `_tidak_aktif`, `_presensi`, `_jadwal` | - (dibuka tugas Wali Kelas) |
| Tendik: Kinerja | `menu_target_capaian`, `menu_aktivitas_tendik`, `menu_laporan_tendik` | tendik |
| Tendik: bidang | `menu_kepala_tas`, `menu_persuratan` (+`_surat_masuk`, `_surat_keluar`, `_pengaturan_persuratan`), `menu_kesiswaan` (+5 sub), `menu_kepegawaian` (+4 sub), `menu_keuangan`, `menu_sarpras`, `menu_inventaris`, `menu_laboran`, `menu_perpustakaan`, `menu_teknisi`, `menu_keamanan`, `menu_penjaga`, `menu_piket` | - (dibuka tugas tambahan) |
| Portal Peserta Didik | `menu_identitas_siswa` | peserta_didik, orang_tua |
| Portal Peserta Didik | `menu_surat_izin_pd`, `menu_riwayat_rfid`, `menu_rapor` | peserta_didik, orang_tua |
| Portal Peserta Didik | `menu_validasi_berkas` | tendik, peserta_didik |
| Sistem | `menu_pengguna`, `menu_hak_akses`, `menu_pengaturan`, `menu_maintenance`, `menu_update` | - |
| Fitur Operasional | `fitur_pengguna_edit/_hapus/_reset`, `fitur_dapodik_sync`, `fitur_system_update` | - |

Modul "spesifik tugas" (`isDutySpecificPermission`): prefiks `menu_wali_kelas`, `menu_kepala_tas`, `menu_persuratan`, `menu_surat_*`, `menu_pengaturan_persuratan`, `menu_kesiswaan`, `menu_kepegawaian`, `menu_keuangan`, `menu_sarpras`, `menu_inventaris`, `menu_laboran`, `menu_perpustakaan`, `menu_teknisi`, `menu_keamanan`, `menu_penjaga`, `menu_piket`. Meski baris peran diizinkan, pemegang peran harus punya tugas tambahan terkait.

---

## 5. Hak Akses Berbasis Tugas Tambahan

Dipetakan di `RolePermission::hasDutyPermission()` (selain kolom `granted_permissions` pada `ref_tugas_tambahan`).

| Kode tugas | Modul yang dibuka |
| :--- | :--- |
| `WALI_KELAS` | `menu_wali_kelas_*`, `menu_peserta_didik_aktif`, `menu_presensi_peserta_didik`, `menu_agenda_kbm`, `menu_berkas_peserta_didik` |
| `KEPALA_SEKOLAH`, `WAKA_KURIKULUM` | `menu_rombel`, `menu_pembelajaran`, `menu_jadwal_kbm`, `menu_kompetensi_keahlian`, `menu_presensi_mengajar`, `menu_agenda_kbm` |
| `WAKA_KESISWAAN` | `menu_kesiswaan*`, `menu_peserta_didik_aktif/_tidak_aktif`, `menu_e_izin` |
| `PEMBINA_OSIS`, `PEMBINA_EKSKUL` | `menu_kesiswaan` (kegiatan, prestasi, [kedisiplinan OSIS]), `menu_pengumuman` |
| `WAKA_SARPRAS`, `KEPALA_BENGKEL` | `menu_sarpras`, `menu_inventaris` |
| `KEPALA_LAB` | `menu_laboran`, `menu_sarpras`, `menu_inventaris` |
| `LABORAN` | `menu_laboran`, `menu_inventaris` |
| `KEPALA_PERPUSTAKAAN`, `PUSTAKAWAN` | `menu_perpustakaan` |
| `TEKNISI_IT/GEDUNG/LAPANGAN` | `menu_teknisi` |
| `SATPAM`, `PETUGAS_KEAMANAN` | `menu_keamanan` |
| `PENJAGA_SEKOLAH`, `PESURUH` | `menu_penjaga` |
| `GURU_PIKET` | `menu_piket`, `menu_presensi_mengajar`, `menu_agenda_kbm`, `menu_presensi_peserta_didik`, `menu_e_izin`, `menu_riwayat_rfid` |
| `OPERATOR_DAPODIK` | `menu_dapodik`, `menu_peserta_didik_aktif`, `menu_guru_aktif`, `menu_tendik_aktif`, `menu_rombel`, `menu_pembelajaran` |
| `KEPALA_TAS` | seluruh modul bidang tendik (`menu_kepala_tas`, persuratan, kesiswaan, kepegawaian, keuangan, sarpras, laboran, perpustakaan, teknisi, keamanan, penjaga, piket, inventaris) + Kinerja Tendik |
| `STAF_PERSURATAN` | `menu_persuratan` + sub-modul |
| `STAF_KEPEGAWAIAN` | `menu_kepegawaian`, `menu_guru_aktif/_tidak_aktif`, `menu_tendik_aktif/_tidak_aktif`, `menu_rfid` |
| `STAF_KESISWAAN` | `menu_kesiswaan*`, `menu_peserta_didik_aktif/_tidak_aktif`, `menu_berkas_peserta_didik`, `menu_perubahan_data` |
| `STAF_SARPRAS` | `menu_sarpras`, `menu_inventaris`, `menu_rombel` |
| `KOORDINATOR_KELAS` | `menu_peserta_didik_aktif`, `menu_presensi_peserta_didik` |
| Semua tugas tendik (`KEPALA_TAS`, `STAF_*`, `LABORAN`, `PUSTAKAWAN`, `TEKNISI_IT`, `SATPAM`, `PENJAGA_SEKOLAH`) | + `menu_target_capaian`, `menu_aktivitas_tendik`, `menu_laporan_tendik` |

Menambah tugas baru: tambahkan baris di `ref_tugas_tambahan` (kolom `granted_permissions` berisi JSON daftar key) atau tambah cabang di `hasDutyPermission()`, lalu daftarkan grup/submenu di sidebar dengan gerbang `$can(...)` dan pengecekan kode tugas bila perlu.

---

## 6. Modul Modular & Sinkronisasi Otomatis

Tombol **Sinkronkan Modul** (dan pembukaan halaman hak akses) menjalankan `autoSyncNewDiscoveredModules()` / `syncAvailablePermissions()`:

1. `discoverSystemModules()` memindai `routes/`, `app/Http/Controllers/`, `resources/views/partials/` dan `resources/views/dashboard/` untuk pola `can('menu_x')`, `permission:menu_x`, `canAccess($u, 'menu_x')`, serta key yang sudah ada di database.
2. `resolveModuleGroupAndRoles($key)` menentukan **kelompok, ikon, dan peran default** dari kata kunci pada nama key:

| Kata kunci | Kelompok | Peran default |
| :--- | :--- | :--- |
| `fitur_` | Fitur Operasional | admin |
| `wali` | Wali Kelas | admin |
| `guru`, `mengajar`, `kbm`, `agenda` | Administrasi Guru | admin, guru |
| `surat`, `persuratan` | Tendik: Persuratan | admin |
| `kesiswaan`, `klaper`, `mutasi` | Tendik: Kesiswaan | admin |
| `kepegawaian`, `kgb`, `cuti` | Tendik: Kepegawaian | admin |
| `sarpras`, `inventaris`, `aset`, `ruang` | Tendik: Sarpras & Aset | admin |
| `laboran`, `lab` | Tendik: Laboratorium | admin |
| `perpustakaan`, `buku`, `koleksi` | Tendik: Perpustakaan | admin |
| `teknisi`, `hardware`, `jaringan` | Tendik: Teknisi IT | admin |
| `keamanan`, `satpam`, `patroli` | Tendik: Keamanan & Tamu | admin |
| `penjaga`, `kebersihan`, `ronda` | Tendik: Fasilitas & Penjaga | admin |
| `piket` | Tendik: Piket Sekolah | admin |
| `target`, `aktivitas`, `laporan_tendik`, `capaian` | Tendik: Kinerja & Aktivitas | admin, tendik |
| `peserta_didik`, `siswa`, `rapor`, `identitas` | Portal Peserta Didik | admin, peserta_didik, orang_tua |
| `pengguna`, `hak_akses`, `maintenance`, `update`, `pengaturan` | Sistem & Pengaturan | admin |
| lainnya | Layanan Digital | admin |

3. Modul baru otomatis ditambahkan ke `role_permissions` untuk admin dan peran default; Administrator selalu penuh.
4. **Pembersihan non-destruktif**: sinkronisasi hanya menghapus (a) kombinasi terlarang `FORBIDDEN_MODULES`, dan (b) baris nonaktif milik modul yang bukan default peran tersebut. Pengaturan aktif yang dibuat admin tidak dihapus.
5. Cache runtime dibersihkan (`clearRuntimeCache()`).

Agar modul baru benar-benar "posisinya sesuai peran":
- Tulis key dengan kata kunci yang cocok dengan tabel di atas, atau daftarkan eksplisit di `getBasePermissions()` (kelompok, label, ikon, `roles`).
- Bila modul tidak berlaku untuk peran tertentu, tambahkan ke `FORBIDDEN_MODULES`.
- Modul khusus bidang tugas: tambahkan prefiksnya ke `isDutySpecificPermission()` dan kodenya ke `hasDutyPermission()`.

---

## 7. Perilaku Halaman Hak Akses

- Tab per peran: Administrator, Guru, Tendik, Peserta Didik, Orang Tua / Wali. Default tab: Administrator.
- Tabel hanya memuat modul yang tercatat/default untuk peran itu dan tidak terlarang.
- **Tambah Modul ke Peran** menampilkan modul sistem yang belum aktif untuk peran tersebut (tanpa modul terlarang); opsi "Daftarkan Modul Baru" membuat key `menu_{slug}`.
- Toggle **Lihat** mati -> Tambah/Ubah/Hapus ikut mati. Menyalakan Tambah/Ubah/Hapus -> Lihat ikut menyala.
- Modul hierarkis disinkronkan induk-anak: `menu_wali_kelas*`, `menu_persuratan` + sub, `menu_kesiswaan` + sub.
- `menu_hak_akses` dan `menu_dashboard` milik admin dikunci.
- **Reset Default** mereset peran pada tab aktif saja ke default kodenya.
- Endpoint: `toggle`, `sync`, `reset`, `add-module`, `remove-module` di `PermissionController` (hanya admin).

---

## 8. Cara Membuat Modul Baru

1. Generator: `php artisan sae:make-module {nama} --title="Judul" --icon="fa-icon" --role=tendik`
2. Route dengan middleware izin:
   ```php
   Route::get('/nama-modul', [NamaModulController::class, 'index'])
       ->name('nama-modul.index')
       ->middleware('permission:menu_nama_modul,read');
   ```
3. Sidebar: bungkus link dengan `@if ($can('menu_nama_modul'))` pada grup peran/bidang yang tepat (lihat Bagian 3).
4. Klik **Sinkronkan Modul** di halaman hak akses, atau jalankan:
   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan tinker --execute="App\Models\RolePermission::syncAvailablePermissions();"
   ```
5. Bersihkan cache: `php artisan optimize:clear`
6. Atur CRUD per peran di halaman hak akses.

## 9. Checklist Sebelum Merilis Perubahan Hak Akses

- Setiap link sidebar baru punya gerbang `$can(...)` dan berada pada grup yang sesuai pengelolaannya (guru / tendik / bidang tugas).
- Modul yang tidak relevan untuk suatu peran sudah masuk `FORBIDDEN_MODULES`.
- Sinkronisasi dijalankan, lalu tab tiap peran dicek: tidak ada modul asing, tidak ada modul hilang.
- Simulasikan sidebar untuk kombinasi peran + tugas tambahan (guru biasa, guru wali kelas, guru piket, tendik per bidang, tendik hanya piket).
- Uji `tests/Feature/KioskPresensiTest.php` dan `tests/Feature/JadwalSmkTest.php`.
