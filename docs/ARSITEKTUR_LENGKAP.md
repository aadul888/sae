# PANDUAN LENGKAP ARSITEKTUR, FITUR & SISTEM: SAE (SISTEM APLIKASI EDUKASI)

Dokumen ini adalah spesifikasi teknis dan panduan arsitektur komprehensif (*Master System Blueprint & Single Source of Truth*) yang mendokumentasikan **seluruh sistem SAE (Sistem Aplikasi Edukasi)** secara menyeluruh dari hulu ke hilir. Dokumen ini dirancang sebagai panduan definitif bagi pengembang, administrator sistem, dan tim pengelola sekolah untuk memahami setiap alur bisnis, arsitektur data, integrasi perangkat keras, otorisasi hybrid, hingga standar kode operasional.

---

## DAFTAR ISI
1. [Ringkasan Eksekutif & Filosofi SAE](#1-ringkasan-eksekutif--filosofi-sae)
2. [Arsitektur Sistem & Tech Stack Lengkap](#2-arsitektur-sistem--tech-stack-lengkap)
3. [Arsitektur Model Pengguna, Autentikasi & Multi-Role](#3-arsitektur-model-pengguna-autentikasi--multi-role)
4. [Sistem Otorisasi Hybrid RBAC (Role & Duty-Based)](#4-sistem-otorisasi-hybrid-rbac-role--duty-based)
5. [Skema Basis Data & Mirrored Architecture](#5-skema-basis-data--mirrored-architecture)
6. [Mekanisme Ingesti Feeder Dapodik & Isolasi Metadata](#6-mekanisme-ingesti-feeder-dapodik--isolasi-metadata)
7. [Bedah Fungsional & Teknis Seluruh Modul SAE](#7-bedah-fungsional--teknis-seluruh-modul-sae)
   - [7.1 Landing Page, PWA & Kiosk Presensi Mandiri](#71-landing-page-pwa--kiosk-presensi-mandiri)
   - [7.2 Manajemen Pengguna & Keamanan Akun Multi-User](#72-manajemen-pengguna--keamanan-akun-multi-user)
   - [7.3 Master Data Terintegrasi (Kompetensi Keahlian, Rombel, Pembelajaran)](#73-master-data-terintegrasi-kompetensi-keahlian-rombel-pembelajaran)
   - [7.4 Penjadwalan KBM (Anti-Bentrok, Jam Sesi & Generator Otomatis)](#74-penjadwalan-kbm-anti-bentrok-jam-sesi--generator-otomatis)
   - [7.5 Kalender Pendidikan & Integrasi Agenda KBM](#75-kalender-pendidikan--integrasi-agenda-kbm)
   - [7.6 Manajemen Peserta Didik, Pasfoto Persisten & Kartu Pelajar Digital](#76-manajemen-peserta-didik-pasfoto-persisten--kartu-pelajar-digital)
   - [7.7 Manajemen GTK (Guru Aktif & Tendik Aktif)](#77-manajemen-gtk-guru-aktif--tendik-aktif)
   - [7.8 Ruang Kerja Khusus Wali Kelas (Terisolasi Per Kelas Binaan)](#78-ruang-kerja-khusus-wali-kelas-terisolasi-per-kelas-binaan)
   - [7.9 Presensi Terpadu & Terminal Kiosk RFID (Hardware, Multi-Mode, Presensi Kelas Auto-Alpha, SSE)](#79-presensi-terpadu--terminal-kiosk-rfid)
   - [7.10 Agenda KBM & Presensi Mengajar Guru](#710-agenda-kbm--presensi-mengajar-guru)
   - [7.11 Aktivitas & Laporan Kinerja Tendik Harian (Kinerja Murni Output Kerja Nyata)](#711-aktivitas--laporan-kinerja-tendik-harian)
   - [7.12 Administrasi Kesiswaan (Buku Klaper Digital, Mutasi Masuk/Keluar, & Berkas Siswa)](#712-administrasi-kesiswaan-buku-klaper-digital-mutasi-masukkeluar--berkas-siswa)
   - [7.13 Administrasi Kepegawaian GTK (Berkas Digital, KGB Tracker Otomatis, & Cuti BKN)](#713-administrasi-kepegawaian-gtk-berkas-digital-kgb-tracker-otomatis--cuti-bkn)
   - [7.14 Manajemen Persuratan & Kearsipan Fisik Harddisk (HDD)](#714-manajemen-persuratan--kearsipan-fisik-harddisk-hdd)
   - [7.15 Formulir & Survei Digital (SAE Forms / Alternatif Mandiri Google Forms)](#715-formulir--survei-digital-sae-forms)
   - [7.16 Pengumuman & Broadcast Sekolah](#716-pengumuman--broadcast-sekolah)
   - [7.17 Portal Mandiri Peserta Didik (E-Izin & Riwayat Presensi)](#717-portal-mandiri-peserta-didik-e-izin--riwayat-presensi)
   - [7.18 Pembaruan Sistem (Update Center) & Maintenance](#718-pembaruan-sistem-update-center--maintenance)
   - [7.19 Modul Layanan Pendukung (Buku Tamu Digital & Inventaris Sarpras)](#719-modul-layanan-pendukung-buku-tamu-digital--inventaris-sarpras)
8. [Proteksi Integritas Sistem (SystemSignature & Anti-Tamper)](#8-proteksi-integritas-sistem-systemsignature--anti-tamper)
9. [Standar Kode, Arsitektur UI/UX & Konvensi Frontend](#9-standar-kode-arsitektur-uiux--konvensi-frontend)
10. [Struktur Direktori & Pemetaan Komponen Proyek](#10-struktur-direktori--pemetaan-komponen-proyek)
11. [Panduan Operasional, Maintenance & CLI Laragon](#11-panduan-operasional-maintenance--cli-laragon)

---

## 1. Ringkasan Eksekutif & Filosofi SAE

**SAE (Sistem Aplikasi Edukasi)** adalah sistem operasi tata kelola sekolah digital mandiri (*on-premise / hybrid cloud*) yang dirancang khusus untuk memodernisasi sekolah menengah (SMA/SMK) di Indonesia. SAE menghubungkan pilar data pokok nasional (Dapodik Kemendikbudristek), perangkat keras lokal (IoT RFID Reader & partisi fisik harddisk server), serta operasional harian seluruh warga sekolah dalam satu ekosistem terpadu tanpa biaya lisensi berulang.

### 5 Pilar Utama Arsitektur SAE:
1. **Sinkronisasi Tanpa Redundansi (*Zero-Redundancy Dapodik Sync*)**:
   Data pokok satuan pendidikan (identitas sekolah, GTK, siswa, rombongan belajar, kurikulum, pembelajaran) disinkronkan langsung dari basis data Dapodik lokal melalui feeder. Tata usaha dan operator tidak perlu melakukan input ganda (*double entry*).
2. **Otorisasi Dua Lapis (*Hybrid Dynamic RBAC*)**:
   Menggabungkan role dasar akun (`admin`, `guru`, `tendik`, `peserta_didik`) dengan matriks penugasan Surat Keputusan (SK) Tugas Tambahan GTK (Wali Kelas, Waka Kurikulum, Waka Kesiswaan, KTU, Kaprog, Pembina OSIS, Staf Persuratan, dll.) secara otomatis dan dinamis.
3. **Kemandirian Hardware & Penyimpanan Fisik (*Hardware-First & On-Premise*)**:
   Berkas arsip digital berukuran besar (scan surat dinas, ijazah siswa, berkas kepegawaian) disimpan langsung pada partisi harddisk fisik (HDD) server lokal di luar webroot publik. Mencegah kebocoran data rahasia dan mengeliminasi ketergantungan pada *cloud storage* berbayar.
4. **Verifikasi Dokumen Publik Berbasis Privasi (*Privacy-by-Design Public Verification*)**:
   Setiap dokumen resmi (Surat Keterangan Siswa Aktif, Kartu Pelajar Digital, SK Mutasi) dilengkapi kode hash unik dan QR Code publik (`/v/doc/{id}` atau `/v/{nisn}`) yang dapat divalidasi keasliannya oleh instansi luar tanpa harus memiliki akun login SAE.
5. **Aksesibilitas Multi-Platform & PWA (*Progressive Web App*)**:
   Antarmuka web adaptif responsif (desktop, tablet, mobile), PWA offline-ready dengan manifest dinamis, serta mode terminal kiosk mandiri untuk presensi gerbang sekolah.

---

## 2. Arsitektur Sistem & Tech Stack Lengkap

```
┌────────────────────────────────────────────────────────────────────────┐
│                        FRONTEND CLIENT LAYER                           │
│  Blade Templates  │  Modular Vanilla JS  │  Custom CSS  │  PWA / Kiosk │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ HTTP / HTTPS (Session & Sanctum API)
┌───────────────────────────────────▼────────────────────────────────────┐
│                        BACKEND APPLICATION LAYER                       │
│                       Laravel 13.38.0 (PHP ^8.3)                       │
│  Middleware Pipeline (EnsureInstalled, CheckRolePermission, Throttle)  │
│  Service Layer (PersuratanHddService, QrCodeService, RfidService)      │
│  Anti-Tamper Core (SystemSignature SHA-256 Engine)                     │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ MySQL PDO Driver
┌───────────────────────────────────▼────────────────────────────────────┐
│                         DATA & STORAGE LAYER                           │
│  MySQL Database (`db_sae`)  │  Local Server HDD Storage (`D:\Arsip`)   │
│  Mirrored Backup Tables     │  Isolated Metadata Tables                │
└────────────────────────────────────────────────────────────────────────┘
```

### Spesifikasi Komponen & Konvensi Teknologi:
- **Backend Framework**: Laravel 13.38.0.
- **PHP Runtime**: PHP 8.3+ (Laragon Windows x64: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`).
- **Database Engine**: MySQL / MariaDB (`db_sae`), charset `utf8mb4_unicode_ci`.
- **Frontend Engine**: Laravel Blade Templating modular + komponen reusable (`resources/views/components/sae/`).
- **Styling Architecture**: Pure Vanilla CSS kustom berbasis **design token**:
  `public/css/tokens.css` (skala nilai), `public/css/components.css` (kelas komponen),
  `public/css/sae.css` & `public/css/dashboard.css` (gaya aplikasi). Mendukung tema Terang & Gelap.
  **Tailwind CSS & Alpine.js TIDAK dipakai.**
- **Client Scripting**: Pure Vanilla JavaScript modular (`public/js/*.js`), terpisah secara ketat dari file view Blade (`@push('scripts')`).
- **Pustaka Vendor UI**: SweetAlert2 (`public/vendor/sweetalert2/`), FontAwesome 6 Free, Chart.js.
- **Generator QR Code**: BaconQrCode / SVG Generator bawaan backend.
- **Penyimpanan Berkas Fisik**: `App\Services\PersuratanHddService` dengan proteksi anti-*path traversal* untuk penyimpanan pada drive fisik server.

---

## 3. Arsitektur Model Pengguna, Autentikasi & Multi-Role

Aplikasi **tidak menggunakan tabel `users` bawaan Laravel** untuk menghindari duplikasi terhadap data pokok Dapodik. Seluruh akun pengguna dikelola melalui tabel `pengguna`:

### A. Model `App\Models\User` & Tabel `pengguna`
- **Primary Key**: `pengguna_id` (String / UUID Dapodik, non-incrementing).
- **Atribut Pokok**:
  - `pengguna_id`: UUID identitas akun.
  - `sekolah_id`: UUID unit kerja satuan pendidikan.
  - `username`: ID login unik (Username, NIP, NUPTK, atau NISN).
  - `nama`: Nama lengkap pemilik akun.
  - `password`: Hash Bcrypt kata sandi.
  - `peran_id_str`: Teks string peran Dapodik (misal: `Administrator`, `Guru Mapel`, `Tenaga Administrasi Sekolah`, `Peserta Didik`).
  - `ptk_id`: Foreign key ke tabel `gtk.ptk_id` jika pemilik akun berstatus GTK.
  - `peserta_didik_id`: Foreign key ke tabel `peserta_didik.peserta_didik_id` jika akun siswa.
  - `alamat`, `no_telepon`, `no_hp`: Data kontak personal.
  - `raw_data`: Snapshot JSON data mentah profil Dapodik.

### B. Evaluasi Role Dinamis (`$user->role`)
Role pengguna tidak disimpan sebagai kolom enum statis di database, melainkan dievaluasi secara dinamis via accessor `getRoleAttribute()` pada Model `User`:
1. **`admin`**: Jika `peran_id_str` mengandung kata `admin`, `dinas`, `yayasan`, atau `operator`.
2. **`tendik`**: Jika `peran_id_str` merujuk ke non-guru (Tata Usaha, Tenaga Kependidikan, Laboran, Pustakawan, Satpam, Penjaga) ATAU relasi `ptk.jenis_ptk_id_str` merujuk ke non-guru/tendik.
3. **`guru`**: Jika `peran_id_str` merujuk ke guru/pendidik ATAU memiliki relasi `ptk_id` guru/kepala sekolah.
4. **`peserta_didik`**: Default jika memiliki relasi `peserta_didik_id` atau bukan GTK/Admin.

### C. Mekanisme Sesi Login Web Multi-Identifier (`AuthController`)
- **Admin**: Login menggunakan Username atau Email resmi.
- **Guru & Tendik**: Login menggunakan NIP, NUPTK, NIK, Username, atau Email.
- **Peserta Didik**: Login menggunakan NISN, NIK, NIPD, atau Username.
- **Password Fallback**: Verifikasi kata sandi Bcrypt dengan dukungan password default bawaan (`Sae12345!`, NISN, atau NUPTK).
- **Force Update Password**: Jika akun terdeteksi masih menggunakan password bawaan, sistem otomatis mengarahkan ke rute `/auth/update-password-wajib` hingga pengguna membuat kata sandi baru yang aman.
- **Sesi Web**: Biodata penting disimpan ke dalam `session(['user' => $userData])`.
- **API Mobile & Feeder**: Menggunakan Laravel Sanctum (`auth:sanctum`) pada rute `/api/v1/auth/*`.

---

## 4. Sistem Otorisasi Hybrid RBAC (Role & Duty-Based)

SAE menerapkan model otorisasi bertingkat dua lapis yang sangat fleksibel:

```
                               ┌───────────────────────────────┐
                               │     Request User ke Modul     │
                               └───────────────┬───────────────┘
                                               │
                                               ▼
                               ┌─────────────────────────────────┐
                               │ Middleware permission:{key},{act}│
                               └────────────────┬────────────────┘
                                               │
                                               ▼
                               ┌─────────────────────────────────┐
                               │ RolePermission::canAccess()     │
                               └────────────────┬────────────────┘
                                               │
                        ┌──────────────────────┴──────────────────────┐
                        ▼                                             ▼
             [1. Izin Peran Dasar]                         [2. Izin Tugas Tambahan]
            Tabel: `role_permissions`                     Tabel: `ptk_tugas_tambahan`
            Role: admin, guru, tendik,                     & `ref_tugas_tambahan`
                  peserta_didik                           (Wali Kelas, Waka, KTU, dll.)
                        │                                             │
                        │        (Jika Belum Diizinkan Role)          │
                        └──────────────────────► ◄────────────────────┘
                                               │
                                               ▼
                                 [3. Granular Hak Aksi CRUD]
                             can_create, can_read, can_update,
                                         can_delete
                                               │
                                     ┌─────────┴─────────┐
                                     │                   │
                                 [Diizinkan]         [Ditolak]
                                     ▼                   ▼
                                 Controller         HTTP 403 Forbidden
```

### A. Komponen Pengendali Akses
1. **`role_permissions`**: Matriks izin role dasar (`role`, `permission_key`, `can_create`, `can_read`, `can_update`, `can_delete`).
2. **`ref_tugas_tambahan`**: Master tugas tambahan dengan atribut `granted_permissions` (JSON array izin modul yang otomatis terbuka saat SK ditugaskan).
3. **`ptk_tugas_tambahan`**: Riwayat penetapan SK tugas tambahan personal GTK (`user_id`, `ptk_id`, nomor SK, TMT, TST, `rombel_id`, `jurusan_id`).
4. **Middleware `CheckRolePermission.php`**: Berjalan otomatis di level routing (`middleware('permission:{key},{action}')`).

### B. Matriks Penugasan Tugas Tambahan Resmi (*Duty-Based Matrix*)
| Kode Tugas | Nama Tugas Tambahan | Hak Akses yang Otomatis Terbuka (*Granted Permissions*) |
| :--- | :--- | :--- |
| `WAKA_KURIKULUM` | **Waka Bidang Kurikulum** | `menu_pembelajaran`, `menu_kompetensi_keahlian`, `menu_rombel`, `menu_jadwal_pelajaran` |
| `WAKA_KESISWAAN` | **Waka Bidang Kesiswaan** | `menu_peserta_didik_aktif`, `menu_poin`, `menu_e_izin`, `menu_riwayat_rfid` |
| `WAKA_HUBIN` | **Waka Hubungan Industri** | `menu_pengumuman`, `menu_buku_tamu`, `menu_agenda` |
| `WAKA_SARPRAS` | **Waka Sarana Prasarana** | `menu_inventaris` |
| `KAPROG` | **Kepala Program Keahlian**| `menu_kompetensi_keahlian`, `menu_rombel`, `menu_pembelajaran`, `menu_peserta_didik_aktif` |
| `KEPALA_PERPUS` | **Kepala Perpustakaan** | `menu_inventaris` |
| `KEPALA_LAB` | **Kepala Lab / Bengkel** | `menu_inventaris` |
| `WALI_KELAS` | **Wali Kelas** | `menu_peserta_didik_aktif`, `menu_presensi_peserta_didik`, `menu_e_izin`, `menu_rapor`, `menu_wali_kelas_aktif`, `menu_wali_kelas_tidak_aktif`, `menu_wali_kelas_presensi` |
| `PEMBINA_OSIS` | **Pembina OSIS** | `menu_agenda`, `menu_pengumuman`, `menu_poin` |
| `PEMBINA_EKSKUL`| **Pembina Ekstrakurikuler** | `menu_agenda`, `menu_pengumuman` |
| `GURU_PIKET` | **Guru Piket Harian** | `menu_buku_tamu`, `menu_e_izin`, `menu_riwayat_rfid` |
| `KEPALA_TAS` | **Kepala Tata Usaha (KTU)**| `menu_tendik_aktif`, `menu_guru_aktif`, `menu_peserta_didik_aktif`, `menu_buku_tamu`, `menu_inventaris`, `menu_persuratan` |
| `OPERATOR_DAPODIK`| **Operator Dapodik** | `menu_dapodik`, `menu_peserta_didik_aktif`, `menu_guru_aktif`, `menu_tendik_aktif`, `menu_rombel`, `menu_pembelajaran` |
| `STAF_KEPEGAWAIAN`| **Staf Kepegawaian** | `menu_guru_aktif`, `menu_tendik_aktif`, `menu_guru_tidak_aktif`, `menu_tendik_tidak_aktif`, `menu_kepegawaian` |
| `STAF_KESISWAAN` | **Staf Kesiswaan** | `menu_peserta_didik_aktif`, `menu_peserta_didik_tidak_aktif`, `menu_kelulusan`, `menu_kesiswaan` |
| `STAF_PERSURATAN`| **Staf Persuratan (Arsiparis)**| `menu_persuratan`, `menu_surat_masuk`, `menu_surat_keluar`, `menu_pengaturan_persuratan` |
| `STAF_SARPRAS` | **Staf Sarpras** | `menu_inventaris` |
| `TEKNISI_IT` | **Teknisi IT & Jaringan** | `menu_rfid`, `menu_inventaris` |
| `LABORAN` | **Laboran** | `menu_inventaris` |
| `PUSTAKAWAN` | **Pustakawan** | `menu_inventaris` |
| `SATPAM` | **Petugas Keamanan / Satpam**| `menu_buku_tamu`, `menu_rfid` |
| `PENJAGA_SEKOLAH`| **Penjaga Sekolah** | `menu_buku_tamu` |

---

## 5. Skema Basis Data & Mirrored Architecture

Database `db_sae` menggunakan arsitektur mirrored cache dari Dapodik untuk memastikan data live selalu aman dan dapat dipulihkan secara instan:

### A. Tabel Pokok Dapodik & Pasangan Backup
Setiap tabel data pokok memiliki pasangan tabel arsip (`backup_*`). Sebelum data live ditimpa oleh feeder baru, sistem melakukan snapshot otomatis:
- `sekolah` & `backup_sekolah`: Profil sekolah, NPSN, koordinat peta, kontak, alamat resmi.
- `gtk` & `backup_gtk`: Pendidik dan tenaga kependidikan, NUPTK, NIK, NIP, jenis PTK, status kepegawaian, gelar depan/belakang.
- `rombongan_belajar` & `backup_rombongan_belajar`: Rombel reguler/pilihan, tingkat kelas, kurikulum, wali kelas (`ptk_id`).
- `peserta_didik` & `backup_peserta_didik`: Biodata siswa, NISN, NIK, NIPD, tanggal lahir, nama ayah/ibu.
- `anggota_rombel` & `backup_anggota_rombel`: Relasi many-to-many siswa ke rombongan belajar.
- `pembelajaran` & `backup_pembelajaran`: Alokasi mapel per rombel, guru pengampu (`ptk_id`), alokasi JJM.

### B. Tabel Metadata Terisolasi (Anti-Overwrite Feeder)
Data kustom yang dibuat di dalam aplikasi SAE disimpan di tabel metadata terpisah agar tidak terhapus saat feeder melakukan truncate & re-insert data Dapodik:
- `peserta_didik_meta`: Pasfoto digital siswa untuk Kartu Pelajar Digital (`peserta_didik_id`, `nisn`, `foto_path`, `foto_size`, `foto_width`, `foto_height`).
- `jurusan_meta`: Logo jurusan (.png) dan nama singkatan jurusan.
- `sekolah_meta`: Logo satuan pendidikan, kop surat dinas, stempel, dan tanda tangan digital kepala sekolah.
- `jadwal_kbm_meta`: Pengaturan sesi jam pelajaran sekolah dan preferensi batasan mengajar guru.
- `settings`: Konfigurasi global sistem (ID=1), API key feeder Dapodik, waktu sinkronisasi terakhir (`last_sync`), status izin sinkronisasi (`sync_allowed`).

---

## 6. Mekanisme Ingesti Feeder Dapodik & Isolasi Metadata

Penerimaan data sinkronisasi berada pada `app/Http/Controllers/Api/FeederReceiverController.php`:
- **Endpoint Feeder**: `POST /api/receive-data`
- **Autentikasi Header**: `X-API-Key: <api_key>` atau `Authorization: Bearer <api_key>` (dicocokkan dengan `settings.api_key`).
- **Alur Kerja Ingesti Zero-Redundancy**:
  1. Validasi API Key dan proteksi pencadangan arsip (`sync_allowed`).
  2. Snapshot tabel live ke tabel `backup_*` via direct SQL statement:
     ```sql
     INSERT INTO backup_peserta_didik SELECT * FROM peserta_didik;
     ```
  3. Batch insert data baru dari payload JSON feeder dengan teknik chunking (misal `array_chunk($batch, 200)`).
  4. Auto-generate akun peserta didik baru di tabel `pengguna` dengan password bawaan NISN (jika belum ada).
  5. Sinkronisasi otomatis tugas tambahan Wali Kelas berdasarkan rombel aktif terbaru (`PtkTugasTambahan::syncWaliKelasFromDapodik()`).
  6. Perbarui `settings.last_sync = now()` dan kunci kembali izin sinkronisasi (`sync_allowed = false`).

---

## 7. Bedah Fungsional & Teknis Seluruh Modul SAE

Berikut adalah rincian teknis mendalam untuk seluruh 18+ modul operasional yang ada di sistem SAE:

### 7.1 Landing Page, PWA & Kiosk Presensi Mandiri
- **Landing Page Publik (`HomeController.php`)**:
  - Halaman depan resmi sekolah yang menampilkan profil satuan pendidikan, visi-misi, daftar jurusan, berita/artikel kegiatan, dan widget statistik ringkas.
  - **Widget Cek NISN Publik (`/api/check-nisn`)**: Fasilitas bagi siswa atau wali murid untuk mengecek status keaktifan peserta didik dan rombel belajar hanya dengan memasukkan NISN.
- **Progressive Web App (PWA) Standalone**:
  - Dynamic Web App Manifest (`/manifest.webmanifest` & `/manifest.json`) dengan konfigurasi universal latar putih.
  - Service Worker (`/sw.js`) untuk caching aset statis dan halaman offline fallback (`/offline`).
  - Web App Shortcuts: Pintasan cepat dari home-screen HP/tablet menuju Terminal Presensi, Portal Login, dan Cek NISN.
- **Terminal Kiosk Presensi Publik (`/scan` & `/presensi/scan`)**:
  - Tampilan layar penuh (*kiosk mode*) yang dirancang khusus untuk monitor layar sentuh/tablet lobi gerbang sekolah.
  - **Sistem Proteksi Kode Akses Kiosk**: Kiosk dapat dikunci (`kioskLock`) dan dibuka (`kioskUnlock`) dengan PIN/kode akses khusus agar pengunjung lobi tidak dapat keluar ke sembarang menu.
  - Direct scanner: Menerima tap kartu RFID instan atau pemindaian kamera barcode dengan animasi visual dan feedback audio (suara sukses, terlambat, atau kartu tidak terdaftar).

### 7.2 Manajemen Pengguna & Keamanan Akun Multi-User
- **Manajemen Akun (`UserController.php`)**:
  - Antarmuka 4 Tab Terpadu: Administrator, Guru, Tenaga Kependidikan, dan Peserta Didik.
  - Filter pencarian instan, status keaktifan akun, edit data biodata akun, dan hapus akun.
  - **Reset Password Sekali Klik**: Mengembalikan kata sandi akun ke format default (`Sae12345!`, NISN untuk siswa, atau NUPTK untuk GTK).
- **Profil Mandiri & Keamanan (`ProfileController.php`)**:
  - Ganti kata sandi pribadi dengan validasi konfirmasi password lama dan kekuatan karakter password baru.
  - Pembaruan nomor kontak WhatsApp dan alamat rumah.
  - Unggah foto profil pribadi dengan kompresi otomatis untuk menghemat ruang disk server.
- **Force Update Password Wajib (`AuthController.php`)**:
  - Akun yang baru pertama kali login dengan password default secara otomatis dipaksa membuat password baru sebelum dapat mengakses fitur dashboard.

### 7.3 Master Data Terintegrasi (Kompetensi Keahlian, Rombel, Pembelajaran)
- **Kompetensi Keahlian (`KompetensiKeahlianController.php`)**:
  - Menampilkan seluruh program keahlian kejuruan hasil sinkronisasi Dapodik.
  - Kustomisasi nama singkatan jurusan (misal: `RPL`, `TKJ`, `AKL`, `MPLB`).
  - Unggah logo kejuruan resmi berformat PNG transparan yang tersimpan aman di tabel terisolasi `jurusan_meta`.
  - Melihat seluruh rombel aktif yang bernaung di bawah jurusan terkait beserta statistik jumlah siswanya.
- **Rombongan Belajar (`RombelController.php`)**:
  - Tab Rombel Reguler dan Rombel Mata Pelajaran Pilihan.
  - Pemetaan otomatis wali kelas (`ptk_id`), tingkat kelas (X, XI, XII), kurikulum yang digunakan (Kurikulum Merdeka / K13), dan ruang belajar.
  - Menampilkan daftar seluruh siswa anggota rombel (`anggota_rombel`).
- **Pembelajaran (`PembelajaranController.php`)**:
  - Menampilkan struktur mata pelajaran per rombel, guru pengampu (`ptk_id`), SK mengajar, dan alokasi Jam Mengajar per Minggu (JJM).

### 7.4 Penjadwalan KBM (Anti-Bentrok, Jam Sesi & Generator Otomatis)
- **Modul Jadwal KBM (`JadwalKbmController.php`)**:
  - **Sesi Waktu KBM (`jadwal_kbm_meta`)**: Pengaturan jam pelajaran sekolah (Jam ke-0 s.d. jam ke-12), durasi per jam (misal: 45 menit), slot istirahat 1 dan istirahat 2.
  - **Preferensi Guru (`getGuruPreferensi` & `simpanGuruPreferensi`)**: Guru dapat mengajukan batas hari/jam berhalangan mengajar yang disetujui kurikulum.
  - **Deteksi Bentrok Real-Time (`checkConflictApi`)**: Algoritma cerdas yang memvalidasi agar guru tidak dijadwalkan di dua rombel berbeda pada hari dan jam yang sama.
  - **Auto-Generate Jadwal Otomatis (`autoGenerate`)**: Algoritma otomatis yang memetakan seluruh beban JJM pembelajaran Dapodik ke dalam slot waktu mingguan secara proporsional dan bebas bentrok.
  - **Cetak Dokumen Jadwal Siap Tanda Tangan**: Cetak Jadwal Induk Sekolah (Landscape A3/F4), Cetak Jadwal Mengajar per Guru, dan Cetak Jadwal Pelajaran per Rombel.

### 7.5 Kalender Pendidikan & Integrasi Agenda KBM
- **Kalender Pendidikan (`KalenderPendidikanController.php`)**:
  - Manajemen kalender akademik tahun ajaran berjalan (Semester Ganjil dan Genap).
  - Klasifikasi event berwarna: Hari Efektif Belajar (HEB), Libur Nasional, Libur Semester, Penilaian Tengah/Akhir Semester (PTS/PAS), Uji Kompetensi Keahlian (UKK), dan Kegiatan Khusus Sekolah.
  - **Integrasi Validasi Otomatis**: Agenda KBM dan Presensi Mengajar Guru terkunci secara otomatis pada tanggal-tanggal yang ditandai sebagai hari libur di kalender pendidikan.

### 7.6 Manajemen Peserta Didik, Pasfoto Persisten & Kartu Pelajar Digital
- **Peserta Didik Aktif (`PesertaDidikAktifController.php`)**:
  - Direktori seluruh peserta didik aktif hasil sinkronisasi Dapodik (NISN, NIK, NIPD, tempat/tanggal lahir, data orang tua, rombel aktif).
  - **Pasfoto Digital Persisten (`peserta_didik_meta`)**:
    - Unggah pasfoto tunggal dengan preview instan.
    - **Bulk Upload Pasfoto (ZIP / Folder)**: Mengunggah ratusan pasfoto siswa sekaligus dengan penamaan file berdasarkan NISN (misal `0071234567.jpg`), otomatis diekstrak dan dipetakan ke siswa terkait. Terisolasi di tabel `peserta_didik_meta` agar pasfoto tidak terhapus saat feeder melakukan re-insert data siswa.
  - **Toggle Koordinator Kelas (Ketua Kelas)**: Memberikan delegasi kepada siswa yang ditunjuk sebagai ketua kelas untuk membantu input presensi kelas.
  - Reset password akun siswa massal.
- **Peserta Didik Tidak Aktif & Alumni (`PesertaDidikTidakAktifController.php`)**:
  - Pengarsipan data siswa mutasi keluar, drop-out, dan kelulusan.
  - **Fitur Sekali Klik `archiveGrade12`**: Otomatis memindahkan seluruh siswa tingkat 12 menjadi alumni saat pergantian tahun ajaran.
  - Ekspor data arsip siswa ke format Excel.
- **Kartu Pelajar Digital (`KartuPelajarController.php`)**:
  - Desain kartu standar ID Card ISO CR-80 (85.6 mm × 53.98 mm) depan-belakang.
  - Memuat logo sekolah, identitas dinas, pasfoto resmi, barcode NISN (Code 128), dan QR Code verifikasi publik.
  - **Verifikasi Publik Privacy-by-Design (`/v/{nisn}` & `/verifikasi-pelajar/{nisn}`)**: QR Code dapat di-scan oleh masyarakat/instansi luar untuk memverifikasi keaslian status pelajar tanpa menampilkan data privat rahasia.
  - Layanan cetak satuan (`printSingle`) atau cetak satu rombel sekaligus siap potong (`printRombel`).

### 7.7 Manajemen GTK (Guru Aktif & Tendik Aktif)
- **Guru Aktif (`GuruAktifController.php`)**:
  - Direktori pendidik lengkap dengan NIP, NUPTK, status kepegawaian (PNS, PPPK, GTT), jenis PTK, gelar akademik, beban mengajar JJM, dan riwayat pendidikan.
  - Detail profil lengkap per guru beserta rombel dan mata pelajaran yang diampu.
- **Tendik Aktif (`TendikAktifController.php`)**:
  - Direktori tenaga kependidikan lengkap dengan NIP, jabatan tugas pokok administrasi (Kepala Tata Usaha, Staf Administrasi, Laboran, Pustakawan, Satpam, Penjaga Sekolah).

### 7.8 Ruang Kerja Khusus Wali Kelas (Terisolasi Per Kelas Binaan)
- **Modul Terisolasi (`WaliKelasController.php`)**:
  - Otomatis aktif bagi guru yang memiliki penugasan SK Tugas Tambahan `WALI_KELAS`.
  - **Akses Data Terisolasi**: Wali kelas hanya dapat melihat dan mengelola peserta didik yang terdaftar di rombel binaannya.
  - Monitoring rekapitulasi kehadiran harian siswa binaan, riwayat izin/sakit, dan catatan kedisiplinan.
  - **Presensi Kelas Binaan**: Input presensi manual jika siswa mengalami kendala tap kartu RFID di gerbang, verifikasi pengajuan e-izin sakit/dispensasi siswa binaan, serta unduh rekap kehadiran resmi format PDF bulanan dan semesteran siap cetak.

### 7.9 Presensi Terpadu & Terminal Kiosk RFID
- **Mesin Presensi RFID (`PresensiController.php` & `RealtimeController.php`)**:
  - Menerima payload dari modul hardware IoT NodeMCU ESP8266/ESP32 atau USB RFID Desktop Reader melalui endpoint `POST /presensi/scan/process`.
  - **3 Mode Pemindaian Terpadu**:
    1. *Mode Presensi GTK*: Pencatatan jam masuk & pulang guru dan tenaga kependidikan.
    2. *Mode Presensi Peserta Didik*: Pencatatan kehadiran siswa harian.
    3. *Mode Registrasi RFID*: Kartu RFID baru yang ditempelkan langsung dipetakan ke profil guru atau siswa yang dipilih via modal.
  - **Penentuan Status Kehadiran Otomatis**:
    - Hadir Tepat Waktu (sebelum batas jam masuk).
    - Terlambat (dengan pencatatan otomatis selisih menit keterlambatan).
    - Pulang Awal / Pulang Tepat Waktu.
    - Alpha (tidak hadir tanpa keterangan).
  - **Presensi Kelas Guru (`presensi.kelas`)**:
    - Guru pengampu atau wali kelas dapat memvalidasi kehadiran siswa di dalam ruang kelas.
    - **Fitur Auto-Alpha**: Tombol pintar yang secara otomatis menandai status Alpha bagi siswa yang tidak melakukan tap kartu RFID di gerbang sekolah pada hari tersebut.
  - **Realtime Server-Sent Events (SSE) & Polling Fallback**: Monitor pemindaian gerbang secara live tanpa harus merefresh browser.
  - Ekspor rekapitulasi data kehadiran ke format Microsoft Excel berdasarkan rentang tanggal.

### 7.10 Agenda KBM & Presensi Mengajar Guru
- **Agenda KBM (`AgendaKbmController.php`)**:
  - Buku jurnal harian mengajar digital guru di setiap kelas dan mata pelajaran.
  - Input rincian KBM: Pertemuan ke-, Capaian Pembelajaran (CP/TP), ringkasan materi yang diajarkan, metode/aktivitas kelas, kendala pembelajaran, dan tugas yang diberikan.
  - **Kalkulasi Pertemuan Otomatis (`getNextPertemuan`)**: Sistem otomatis mendeteksi nomor pertemuan berikutnya berdasarkan riwayat jurnal kelas terkait.
  - **Validasi Kalender Pendidikan (`cekKalenderTanggal`)**: Menolak pengisian agenda pada tanggal merah atau hari libur akademik.
  - Cetak jurnal agenda KBM format resmi siap verifikasi dan tanda tangan kepala sekolah.
- **Presensi Mengajar Guru (`PresensiMengajarController.php`)**:
  - Pencatatan kehadiran fisik guru di ruang kelas sesuai jam jadwal KBM aktif.

### 7.11 Aktivitas & Laporan Kinerja Tendik Harian
- **Filosofi Kinerja Tendik SAE**:
  Tenaga kependidikan (Tendik / Tata Usaha) **tidak dinilai berdasarkan absensi scanner jam masuk/pulang**, melainkan dinilai murni berdasarkan **output hasil kerja nyata** yang diselesaikan setiap hari.
- **Pencatatan Aktivitas Harian (`TendikAktivitasController.php`)**:
  - Tendik mencatat setiap rincian pekerjaan yang dilakukan: deskripsi tugas, volume/output hasil kerja (misal: "Memproses 15 lembar surat mutasi", "Melayani 8 legalisir ijazah"), waktu mulai, waktu selesai, durasi menit, dan unggah berkas bukti pendukung.
- **Rekapitulasi & Cetak Laporan Kinerja (`TendikLaporanController.php`)**:
  - Rekapitulasi aktivitas harian menjadi Laporan Capaian Kinerja berkala: Bulanan, Triwulan, Semester, dan Tahunan.
  - Cetak lembar laporan kinerja resmi ber-Kop Surat sekolah siap ditandatangani oleh staf tendik bersangkutan, Kepala Tenaga Administrasi Sekolah (KTU), dan Kepala Sekolah.

### 7.12 Administrasi Kesiswaan (Buku Klaper Digital, Mutasi Masuk/Keluar, & Berkas Siswa)
- **Modul Kesiswaan (`KesiswaanController.php`)**:
  - **Buku Klaper Digital**: Daftar abjad seluruh peserta didik dari tahun ke tahun yang terintegrasi langsung dengan NISN, nomor induk lokal, dan status kelulusan.
  - **Sinkronisasi Klaper (`syncKlaper`)**: Menarik otomatis data siswa baru dari Dapodik ke dalam buku klaper sekolah.
  - **Administrasi Mutasi Masuk & Keluar (`storeMutasi`)**: Pencatatan mutasi siswa pindahan atau keluar lengkap dengan nomor surat keterangan, tanggal mutasi, alasan kepindahan, dan nama sekolah tujuan.
  - **Cetak Surat Mutasi (`cetakMutasi`)**: Format resmi surat keterangan pindah sekolah ber-Kop Surat dinas.
  - **Pengarsipan Berkas Digital Siswa (`updateBerkas`)**: Manajemen arsip digital ijazah jenjang sebelumnya, kartu keluarga, dan akta kelahiran siswa.

### 7.13 Administrasi Kepegawaian GTK (Berkas Digital, KGB Tracker Otomatis, & Cuti BKN)
- **Modul Kepegawaian GTK (`KepegawaianGtkController.php`)**:
  - **Pengarsipan Berkas Digital GTK (`uploadBerkas`, `deleteBerkas`)**: Penyimpanan digital SK CPNS/PNS/PPPK, SK Pangkat/Golongan terakhir, SK Pembagian Tugas Mengajar, Ijazah Pendidikan, Sertifikat Pendidik (Serdik), dan Kartu Pegawai (Karpeg/KPE).
  - **KGB Tracker (Kenaikan Gaji Berkala Otomatis - `storeKgb`)**: Sistem memonitor masa kerja golongan (MKG) dan secara otomatis menghitung estimasi tanggal jatuh tempo KGB GTK berikutnya (siklus 2 tahunan) sehingga staf kepegawaian dapat menerbitkan SK KGB tepat waktu.
  - **Pengajuan & Cetak Cuti Standar BKN (`storeCuti`, `cetakCuti`)**: Layanan permohonan cuti kedinasan (Cuti Tahunan, Cuti Sakit, Cuti Besar, Cuti Melahirkan, Cuti Alasan Penting) dan pencetakan formulir permohonan cuti resmi standar Badan Kepegawaian Negara (BKN).

### 7.14 Manajemen Persuratan & Kearsipan Fisik Harddisk (HDD)
- **Pengaturan & Penyimpanan Fisik HDD (`PersuratanHddService.php` & `PersuratanSettingController.php`)**:
  - Dokumen scan surat masuk, surat keluar, dan lampiran dinas disimpan langsung pada partisi harddisk fisik lokal server (contoh: `D:\Arsip_Persuratan_SAE` atau direktori storage terlindung di luar webroot).
  - Deteksi status ruang penyimpanan fisik harddisk (*Free Space, Used Space, Total Capacity*).
  - Streaming berkas aman anti-*path traversal* (`streamFile()`).
- **Master 62 Indeks Klasifikasi Surat Baku Instansi Pendidikan**:
  - Berisi 62 kode klasifikasi resmi kearsipan dinas pendidikan (Kepegawaian `KPG`, Keuangan `KU`, Kesiswaan `KS`, Pendidikan & Pembelajaran `PK`, Rumah Tangga/Sarpras `RT`, Tata Usaha `TU`).
- **Format Penomoran Otomatis Baku**:
  - Template konfigurasi: `{nomor}/{kode_indeks}-{sekolah_kode}`
  - Contoh penomoran hasil generate: **`0029/KPG.11.01-SMKN1PGL`**
  - Nomor urut otomatis 4-digit zero-padded (`0001` s.d. `9999`) yang otomatis me-reset pada setiap pergantian tahun takwim.
- **Buku Agenda Surat Masuk & Disposisi (`SuratMasukController.php`)**:
  - Pencatatan nomor surat, perihal, asal instansi pengirim, tanggal surat, tanggal diterima, dan unggah scan berkas fisik.
  - **Penerbitan Lembar Disposisi Kepala Sekolah**: Catatan instruksi tindak lanjut pimpinan kepada wakasek/staf terkait beserta batas waktu penyelesaian.
  - Cetak format resmi lembar disposisi persuratan ber-Kop Surat sekolah.
- **Buku Agenda Surat Keluar & Keterangan Siswa (`SuratKeluarController.php`)**:
  - Penomoran surat dinas keluar otomatis berdasarkan kode indeks klasifikasi yang dipilih.
  - **Penerbitan Surat Keterangan Siswa Aktif**: Mengambil biodata siswa langsung dari Dapodik dan menyematkan **QR Code verifikasi publik** (`/v/doc/{doc_id}`) ber-Kop Surat dinas.

### 7.15 Formulir & Survei Digital (SAE Forms)
- **Pembangun Formulir Digital Mandiri (`FormulirController.php`)**:
  - Alternatif *on-premise* mandiri untuk Google Forms / Typeform / OpnForm.
  - Mendukung berbagai tipe field interaktif: Teks pendek, paragraf, pilihan ganda, checkbox, angka numerik, dropdown pilihan, tanggal/waktu, dan upload file lampiran.
  - Pengaturan fleksibel: Batas kuota respon maksimal, batas tanggal kedaluwarsa formulir, serta opsi akses publik atau khusus login akun.
  - URL publik ramah pengguna: `/f/{slug}`.
  - Analisis ringkasan respon dan ekspor seluruh data pengisian ke format CSV / Microsoft Excel.

### 7.16 Pengumuman & Broadcast Sekolah
- **Pusat Informasi & Warta Kedinasan (`PengumumanController.php`)**:
  - Pembuatan pengumuman resmi dengan segmentasi target audiens: Semua Pengguna, Khusus Guru Saja, Khusus Tendik Saja, atau Khusus Siswa Saja.
  - Mode siaran pop-up broadcast yang otomatis muncul di layar pengguna saat pertama kali login ke dashboard.
  - Pelacakan status baca personal (*mark as read* / *mark all read*) per akun pengguna.

### 7.17 Portal Mandiri Peserta Didik (E-Izin & Riwayat Presensi)
- **Presensi Mandiri Siswa (`PesertaDidikPresensiController.php`)**:
  - Siswa dapat memantau riwayat jam masuk dan jam pulang yang tercatat via sensor gerbang kartu RFID secara transparan.
  - Cetak rekap kehadiran mandiri untuk keperluan lampiran beasiswa atau persyaratan magang/PKL.
- **Layanan E-Izin Mandiri Siswa (`PesertaDidikIzinController.php`)**:
  - Siswa atau wali murid mengajukan permohonan izin sakit atau dispensasi secara daring melalui portal.
  - Unggah foto surat keterangan dokter atau surat izin bertanda tangan orang tua.
  - Status pengajuan izin (*Menunggu Verifikasi*, *Disetujui*, *Ditolak*) yang diverifikasi secara berjenjang oleh Guru Piket Harian atau Wali Kelas.

### 7.18 Pembaruan Sistem (Update Center) & Maintenance
- **Update Center (`UpdateController.php`)**:
  - Memeriksa ketersediaan pembaruan kode dari repositori GitHub resmi (`https://github.com/aadul888/sae.git`).
  - Eksekusi pembaruan sistem satu klik: Menarik commit kode terbaru (`git pull`), mengeksekusi skema migrasi basis data baru (`php artisan migrate --force`), dan membersihkan cache aplikasi (`artisan optimize:clear`).
- **Maintenance Server (`MaintenanceController.php`)**:
  - Pembersihan log transaksi kedaluwarsa dan berkas sementara.
  - Pengunduhan arsip pencadangan basis data berkala.

### 7.19 Modul Layanan Pendukung (Buku Tamu Digital & Inventaris Sarpras)
- **Buku Tamu Digital (`menu_buku_tamu`)**:
  - Pencatatan kehadiran tamu kedinasan, wali murid, atau mitra industri secara mandiri pada layar terminal tamu lobi sekolah.
- **Inventaris Sarpras (`menu_inventaris`)**:
  - Pendataan aset sarana dan prasarana sekolah, nomor inventaris barang, kondisi kelayakan aset, dan pemeliharaan ruang belajar.

---

## 8. Proteksi Integritas Sistem (SystemSignature & Anti-Tamper)

SAE dilengkapi dengan modul verifikasi integritas pengembang resmi yang terpasang secara **load-bearing**:
- **Engine Inti**: `app/Support/SystemSignature.php`.
- **Zero-Plaintext Policy**:
  Tidak ada string teks identitas pengembang dalam bentuk plain text di codebase. Data dikonversi ke format byte array integer (`RAW_MAP`), di-XOR bitwise dengan key `90` (`0x5A`), dan divalidasi dengan checksum SHA-256 digest:
  ```text
  ce20156fa40cf3fb7e6992ec3a575483a73c058e01ca5eeae86df0661be9ae15
  ```
- **Interlocking Guards**:
  1. `app/Providers/AppServiceProvider.php` (Boot Guard): Melempar exception fatal jika verifikasi signature gagal saat aplikasi di-boot.
  2. `app/Http/Middleware/EnsureInstalled.php` (Request Guard): Menghentikan request dengan HTTP 503 jika signature rusak atau dimodifikasi.
  3. `resources/views/partials/dash-footer.blade.php`: Merender badge hak cipta resmi yang terverifikasi.
- **Aturan Pengembang**: Dilarang memodifikasi atau menghapus SystemSignature karena akan memicu proteksi kegagalan fatal pada sistem.

---

## 9. Standar Kode, Arsitektur UI/UX & Konvensi Frontend

Setiap pengembangan atau modifikasi kode pada sistem SAE wajib mematuhi konvensi baku berikut:

1. **Pemisahan Ketat JavaScript (STRICT: DILARANG Inline Script)**:
   - Dilarang keras menaruh tag `<script>` dengan logika di dalam file Blade dashboard (`resources/views/dashboard/*.blade.php`).
   - Seluruh logika interaksi JavaScript wajib ditaruh di file terpisah `public/js/{nama_modul}.js` dan dipanggil via Blade stack:
     ```blade
     @push('scripts')
     <script src="{{ asset('js/{nama_modul}.js') }}"></script>
     @endpush
     ```
2. **Dialog Konfirmasi & Notifikasi Interaksi (SweetAlert2)**:
   - Menggunakan SweetAlert2 (`Swal.fire`). Dilarang menggunakan dialog bawaan browser (`confirm()` atau `alert()`).
   - Pola standar tombol hapus/reset:
     ```html
     <form action="..." method="POST" data-confirm="delete" data-name="Nama Objek">
         @csrf @method('DELETE')
         <button type="submit" class="btn btn-danger">Hapus</button>
     </form>
     ```
3. **Modal & Backdrop Handling**:
   - Seluruh modal wajib memiliki inline style atau CSS class dengan `z-index: 99999 !important` agar tidak tertutup header, sidebar, atau backdrop gelap.
4. **Paginasi Baku SAE (DILARANG `{{ $list->links() }}`)**:
   - Dilarang memanggil `{{ $list->links() }}` secara mentah. Wajib merender blok baku `.custom-pagination` yang mendukung navigasi chevron dan rentang halaman terkondensasi.
5. **Live Search & Filter Asinkron**:
   - Live search input, perubahan per-page, dan filter dropdown wajib memanggil `window.refreshLiveTable(url)` agar data diperbarui via AJAX tanpa reload halaman.
6. **Tombol Banner Aksi Ringkas (Icon-Only Header Buttons)**:
   - Tombol navigasi dan aksi pada banner atas modul didesain ringkas berupa tombol icon `38px × 38px` ber-tooltip (`title="..."`).

---

## 10. Struktur Direktori & Pemetaan Komponen Proyek

```
c:\laragon\www\sae\
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                         # FeederReceiverController, Auth API
│   │   │   ├── AgendaKbmController.php      # Modul Jurnal & Agenda KBM Guru
│   │   │   ├── AuthController.php           # Autentikasi Multi-Identifier
│   │   │   ├── DashboardController.php      # Portal Dashboard per Role
│   │   │   ├── DapodikController.php        # Integrasi Sinkronisasi Dapodik
│   │   │   ├── FormulirController.php       # SAE Forms (Survei Digital)
│   │   │   ├── GuruAktifController.php      # Manajemen Data Pendidik
│   │   │   ├── IdentitasSekolahController.php# Profil Satuan Pendidikan & Kop
│   │   │   ├── JadwalKbmController.php      # Penjadwalan KBM Anti-Bentrok
│   │   │   ├── KalenderPendidikanController.php# Kalender Akademik Sekolah
│   │   │   ├── KartuPelajarController.php   # Generator Kartu Pelajar Digital
│   │   │   ├── KepegawaianGtkController.php # Berkas GTK, KGB & Cuti BKN
│   │   │   ├── KesiswaanController.php      # Buku Klaper, Mutasi & Arsip Siswa
│   │   │   ├── KompetensiKeahlianController.php# Jurusan & Logo Kejuruan
│   │   │   ├── MaintenanceController.php    # Pembersihan Log & Backup DB
│   │   │   ├── PembelajaranController.php   # Mapel, Guru Pengampu & JJM
│   │   │   ├── PermissionController.php     # Matriks Otorisasi RBAC
│   │   │   ├── PersuratanSettingController.php# Pengaturan Format & HDD
│   │   │   ├── PesertaDidikAktifController.php# Siswa Aktif & Bulk Pasfoto
│   │   │   ├── PesertaDidikIzinController.php# Pengajuan E-Izin Mandiri Siswa
│   │   │   ├── PesertaDidikPresensiController.php# Monitoring Presensi Mandiri
│   │   │   ├── PesertaDidikTidakAktifController.php# Arsip Siswa & Alumni
│   │   │   ├── PresensiController.php       # Mesin RFID, Kiosk & Presensi Kelas
│   │   │   ├── PresensiMengajarController.php# Presensi Jam Tatap Muka Guru
│   │   │   ├── ProfileController.php        # Profil Personal & Ganti Password
│   │   │   ├── RealtimeController.php       # Server-Sent Events (SSE) Live
│   │   │   ├── RombelController.php         # Rombongan Belajar & Wali Kelas
│   │   │   ├── SuratKeluarController.php    # Agenda Surat Keluar & Ket. Siswa
│   │   │   ├── SuratMasukController.php     # Surat Masuk & Lembar Disposisi
│   │   │   ├── SuratPublicVerifyController.php# Verifikasi Publik Dokumen
│   │   │   ├── TendikAktifController.php    # Manajemen Tenaga Kependidikan
│   │   │   ├── TendikAktivitasController.php# Buku Jurnal Harian Tendik
│   │   │   ├── TendikLaporanController.php  # Rekap Kinerja Tendik Berkala
│   │   │   ├── UpdateController.php         # Update Center Git & Migrasi
│   │   │   ├── UserController.php           # Manajemen 4 Tab Akun Pengguna
│   │   │   └── WaliKelasController.php      # Ruang Binaan Khusus Wali Kelas
│   │   └── Middleware/                      # CheckRolePermission, EnsureInstalled
│   ├── Models/                              # Model Eloquent (User, GTK, PD, dll.)
│   ├── Services/                            # PersuratanHddService, RfidService
│   └── Support/                             # SystemSignature.php (Anti-Tamper Core)
├── database/
│   ├── migrations/                          # 50+ Skema Migrasi Tabel
│   └── seeders/                             # Seeder Hak Akses, Tugas Tambahan, Indeks
├── public/
│   ├── css/                                 # sae.css, dashboard.css
│   ├── js/                                  # Vanilla JS Modular (*.js)
│   └── vendor/                              # SweetAlert2, FontAwesome, Chart.js
├── resources/
│   └── views/
│       ├── dashboard/                       # View Modul Dashboard per Fitur
│       ├── layouts/                         # dashboard.blade.php, guest.blade.php
│       └── partials/                        # dash-header, dash-sidebar, dash-footer
├── routes/
│   ├── web.php                              # 100+ Rute Dashboard & Web Publik
│   └── api.php                              # Endpoint Feeder Dapodik & Mobile
└── storage/
    └── app/
        └── arsip_persuratan/                # Default Path Berkas Fisik HDD
```

---

## 11. Panduan Operasional, Maintenance & CLI Laragon

Gunakan perintah resmi melalui terminal PHP Laragon dari direktori root `c:\laragon\www\sae`:

```powershell
# 1. Membersihkan seluruh cache aplikasi, rute, view Blade, dan konfigurasi
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize:clear

# 2. Melakukan build ulang cache untuk performa maksimal produksi
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize

# 3. Menjalankan skema migrasi basis data terbaru
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate --force

# 4. Sinkronisasi seluruh matriks izin hak akses modul baru ke database
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan tinker --execute="App\Models\RolePermission::syncAvailablePermissions();"

# 5. Sinkronisasi penugasan tugas tambahan Wali Kelas otomatis dari Dapodik
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan tinker --execute="App\Models\PtkTugasTambahan::syncWaliKelasFromDapodik();"

# 6. Memastikan symlink storage publik terhubung
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan storage:link
```

---

*Dokumen ini adalah cetak biru resmi (*Master System Specification & Architecture Guide*) platform **SAE (Sistem Aplikasi Edukasi)**.*
