# PROJECT_STRUCTURE.md — Struktur & Tanggung Jawab Modul SAE

Dokumen ini memetakan struktur direktori aktual proyek Sistem Aplikasi Edukasi (SAE) serta mendefinisikan tanggung jawab setiap bagian kode.

---

## 1. Peta Direktori Tingkat Tinggi

```
sae/
├── app/                      # Inti logika aplikasi backend
│   ├── Console/              # Custom Artisan commands & scheduler
│   ├── Exceptions/           # Handler error & exception global
│   ├── Http/
│   │   ├── Controllers/      # 68 Web & API Controller
│   │   ├── Middleware/       # 12 Middleware (Auth, RBAC, PWA, CSRF, Realtime)
│   │   └── Kernel.php        # Pendaftaran middleware pipeline
│   ├── Models/               # 61 Eloquent Models (termasuk RolePermission)
│   ├── Providers/            # Service providers aplikasi
│   ├── Services/             # 13 Layanan bisnis independen (Scheduler, Backup, dsb.)
│   └── Support/              # Kelas pembantu (Helper Dapodik, Semester, Signature)
├── bootstrap/                # Inisialisasi framework & cache registrasi
├── config/                   # Konfigurasi sistem Laravel (app, auth, db, dsb.)
├── database/
│   ├── migrations/           # 85 file migrasi database
│   ├── factories/            # Model factories untuk testing
│   ├── seeders/              # Database seeders
│   └── db_sae.sql            # Dump basis data awal
├── public/                   # Web DocumentRoot publik
│   ├── build/                # Hasil build Vite (CSS & JS terkompilasi)
│   ├── css/                  # File CSS khusus (dashboard.css, cetak.css, sae.css)
│   ├── js/                   # File JavaScript per modul (presensi.js, dsb.)
│   ├── img/                  # Asset gambar, logo sekolah, favicon, ikon PWA
│   ├── manifest.webmanifest  # Manifest PWA
│   ├── sw.js                 # Service Worker PWA
│   └── index.php             # Entry point HTTP server publik
├── resources/
│   ├── css/                  # Sumber CSS Tailwind untuk Vite
│   ├── js/                   # Sumber JS utama untuk Vite
│   └── views/                # 173 template Blade
│       ├── auth/             # Halaman login, update password wajib
│       ├── dashboard/        # View modul dashboard berdasarkan fitur & peran
│       ├── layouts/          # Layout master (auth, dashboard, public, print)
│       └── partials/         # Komponen UI bersama (sidebar, navbar, notifikasi)
├── routes/
│   ├── web.php               # Rute web utama (~739 baris, 440+ endpoint)
│   ├── api.php               # Rute REST API internal
│   ├── channels.php          # Definisi channel broadcast
│   └── console.php           # Perintah CLI artisan kustom
├── scratch/                  # Direktori kerja script pengujian/audit sementara (di-ignore git)
├── storage/                  # Penyimpanan log, session, berkas unggahan, cache
└── tests/
    ├── Feature/              # Uji skenario alur fungsional (13 file uji)
    ├── Unit/                 # Uji unit komponen mandiri (2 file uji)
    └── TestCase.php          # Base test case konfigurasi
```

---

## 2. Tanggung Jawab Komponen Backend

### A. Controllers (`app/Http/Controllers/`)
Menangani HTTP request, validasi input awal, pemanggilan service/model, dan pengembalian respons (View atau JSON):
- **Autentikasi & Akun:** `AuthController.php`, `UserController.php`, `ProfileController.php`.
- **Integrasi Dapodik:** `DapodikController.php`, `InstallController.php`.
- **Jadwal & KBM:** `JadwalKbmController.php`, `JadwalKbmManualController.php`, `JadwalPelajaranController.php`, `AgendaKbmController.php`, `PembelajaranController.php`, `RombelController.php`.
- **Presensi & Kiosk:** `PresensiController.php`, `PresensiMengajarController.php`, `PesertaDidikPresensiController.php`, `TendikPresensiController.php`, `KeamananPresensiController.php`.
- **Kesiswaan & PIP:** `KesiswaanPesertaDidikController.php`, `PesertaDidikAktifController.php`, `PesertaDidikTidakAktifController.php`, `IdentitasPesertaDidikController.php`, `KartuPelajarController.php`, `PipController.php`, `KedisiplinanController.php`, `KegiatanSiswaController.php`, `PrestasiSiswaController.php`.
- **Persuratan & Dokumen:** `SuratMasukController.php`, `SuratKeluarController.php`, `PersuratanSettingController.php`, `SuratPublicVerifyController.php`. Fitur utama mencakup register agenda surat masuk & keluar, lembar disposisi, surat keterangan siswa, SPT tugas dinas, template Autocrat, buku ekspedisi pengiriman fisik, dan cetak buku agenda kearsipan resmi ANRI/Dinas.
- **Layanan TAS (Tenaga Administrasi Sekolah):** `DutyDashboardController.php`, `TendikAktivitasController.php`, `TendikTargetCapaianController.php`, `TendikLaporanController.php`, `KepegawaianGtkController.php`, `Laboran*`, `Perpus*`, `Sarpras*`, `Keamanan*`, `Piket*`, `Teknisi*`.
- **Pengaturan & Otorisasi:** `PermissionController.php`, `MaintenanceController.php`, `UpdateController.php`.

### B. Services (`app/Services/`)
Menyimpan logika bisnis kompleks yang tidak boleh membebani controller:
- `JadwalPrintService.php`: Kompilasi data cetak matriks jadwal induk sekolah, jadwal mengajar per guru, dan jadwal pelajaran per rombel.
- `JadwalPengaturanService.php`: Pengaturan skema hari sekolah, durasi slot JP, sinkronisasi kegiatan rutin, dan preferensi ketersediaan guru.
- `JadwalMutasiService.php`: Penyimpanan, pembaruan, penghapusan slot jadwal KBM, dan verifikasi potensi bentrok secara langsung.
- `KesiswaanQueryService.php`: Query agregator 5 tab kesiswaan (aktif, mutasi/keluar, alumni, verifikasi berkas, usulan) dan modal biodata lengkap.
- `KesiswaanUsulanService.php`: Penanganan formulir usulan perubahan data siswa mandiri, verifikasi usulan, dan integrasi penanda Dapodik.
- `KesiswaanBerkasService.php`: Verifikasi berkas fisik/digital (KK, Ijazah, Akta, dsb.) dan pengunggahan langsung berkas valid oleh staf kesiswaan.
- `PesertaDidikFotoService.php`: Unggah, kompresi pasfoto PNG, komparasi visual, dan ekstraksi arsip ZIP pasfoto masal per rombel.
- `PesertaDidikImportService.php`: Generator template spreadsheet Excel/CSV dan engine impor peserta didik aktif mandiri.
- `PesertaDidikQueryService.php`: Query datatable peserta didik, penyusunan abjad presisi, reset password default NISN, dan penetapan koordinator kelas.
- `KartuPelajarManageService.php`: Agregasi tab manajemen kartu pelajar, aksi pembayaran, toggle status cetak fisik, aksi massal, integrasi WhatsApp & QR, dan klaim garansi siswa aktif.
- `KartuPelajarKeuanganService.php`: Pengelolaan catatan pengeluaran alat/bahan cetak, laporan pembukuan resmi, autocomplete siswa, antrean request cetak kartu, dan verifikasi selesai via scan QR/tap RFID.
- `KartuPelajarPrintService.php` & `KartuPelajarSelectedPrintTrait.php`: Kompilasi data cetak kartu pelajar tunggal/rombel/antrean terpilih, resolusi metadata sekolah/jurusan, dan generasi barcode/QR code SVG.
- `PresensiKioskService.php`: Autentikasi kiosk scan RFID, verifikasi UID kartu GTK & siswa, penyimpanan snapshot webcam, dan streaming live-log event scan.
- `PresensiKelasService.php`: Monitoring presensi kelas piket harian, perubahan status presensi per siswa, presensi hadir massal per rombel, reset kelas, dan ekspor spreadsheet.
- `WaliKelasAgendaService.php`: Pengelolaan pencatatan agenda KBM harian rombel binaan, riwayat semester, dan data cetak jurnal.
- `WaliKelasPresensiService.php`: Rekapitulasi presensi harian rombel, pencatatan manual, approval izin siswa, dan generator cetak PDF multi-periode.
- `WaliKelasValidasiService.php`: Pengelolaan rekapitulasi kelengkapan data identitas Dapodik dan verifikasi berkas digital/fisik siswa.
- `AdminDashboardStatsService.php`: Agregasi metrik, tren presensi 7 hari, sebaran jurusan, polar tingkat, dan log aktivitas admin.
- `GuruDashboardStatsService.php`: Kompilasi data jadwal mengajar harian, status kalender pendidikan, dan statistik perwalian guru.
- `TendikDashboardStatsService.php`: Agregasi metrik 10 cluster bidang tugas Tenaga Administrasi Sekolah (TAS) dan status penugasan tendik.
- `KepegawaianStatsService.php`: Pembangun dataset multi-periode (hari, minggu, bulan, triwulan, semester, tahun, TA) aktivitas GTK.
- `SuratKeluarService.php`: Layanan agregasi query datatable surat keluar terkoordinir, sinkronisasi SPT, kalkulasi nomor surat terakhir, dan penanganan CRUD berkas arsip HDD.
- `SuratKeteranganService.php`: Logika penerbitan surat keterangan siswa (aktif, berkelakuan baik, panggilan ortu, rekomendasi), integrasi BK, dan kompilasi cetak ber-Kop + QR.
- `SuratSptService.php`: Logika penerbitan Surat Perintah Tugas (SPT) GTK, kalkulasi durasi penugasan, registrasi nomor otomatis, dan kompilasi cetak resmi A4.
- `SuratTemplateService.php`: Pengelolaan dokumen surat dinas berbasis template Autocrat locked layout, integrasi QR verifikasi, dan cetak lembar resmi.
- `SuratAgendaEkspedisiService.php`: Generator buku register agenda kedinasan (masuk & keluar) A4 Landscape dan tanda terima pengiriman fisik buku ekspedisi.
- `AutoSchedulerService.php`: Engine utama orkestrasi penyusunan jadwal KBM otomatis multi-attempt dengan adaptive fallback.
- `AutoSchedulerPlacementService.php`: Pemindaian kandidat slot valid (zero-gap, bridge istirahat, fallback) dan bubble compaction rombel.
- `AutoSchedulerSwapResolver.php`: Resolusi sesi unallocated via 1-step ejection chain swap dan inter/intra-rombel slice swap.
- `AutoSchedulerLayoutOptimizer.php`: Rekonstruksi layout jadwal KBM bebas bentrok menjadi blok utuh anti-1-JP via solver simulated annealing.
- `AutoSchedulerSupport.php`: Helper pemecah JJM menjadi blok proporsional, verifikasi jam berhalangan guru, dan builder baris jadwal.
- `BlockTimetableSolver.php`: Solver Simulated Annealing untuk penataan blok mata pelajaran anti-bentrok.
- `SystemNotificationService.php`: Agregasi dan deteksi notifikasi sistem lintas bidang (surat pending, izin siswa, agenda, dsb.).
- `UpdateService.php`: Logika pembaruan sistem via remote commit GitHub, migrasi database, dan pembersihan cache.
- `BackupService.php`: Pencadangan database dan berkas sistem.
- `PersuratanHddService.php`: Pengelolaan penyimpanan dokumen persuratan ke disk lokal / dedicated storage.
- `PipService.php`: Pengelolaan skema runtime PIP, pembagian kuota proporsional (metode Hamilton), dan pemrosesan impor transaksi riwayat SiPintar.
- `PipQueryService.php`: Pengambilan data tab halaman index PIP, filter, pencarian, dan paginasi terpadu.
- `PipDashboardStatsService.php`: Agregasi metrik, sebaran kriteria alasan, dan statistik tab Dashboard Overview PIP.
- `PipExportService.php`: Pembangun berkas ekspor Microsoft Excel (.xlsx) untuk rekapitulasi data usulan PIP.
- `RealtimeService.php`: Pengelolaan antrean event realtime untuk polling pembaruan antarmuka.
- `SimpleXlsxExporter.php` & `SimpleXlsxReader.php`: Utilitas baca-tulis file spreadsheet mandiri tanpa dependency berat.
- `ImageOptimizerService.php`: Kompresi dan optimasi foto pengguna serta dokumen.

### C. Models (`app/Models/`)
Mewakili entitas data dan aturan hubungan Eloquent:
- `RolePermission.php`: Model pusat definisi modul, hak akses CRUD bawaan, sinkronisasi matriks peran, dan resolusi hak akses gabungan (peran + tugas tambahan).
- `User.php`: Pengguna sistem terhubung ke data `Gtk` atau `PesertaDidik`.
- `Gtk.php`: Guru dan Tenaga Kependidikan dengan relasi ke `PtkTugasTambahan`.
- `PesertaDidik.php`, `PesertaDidikIdentitas.php`, `PesertaDidikBerkas.php`: Data induk peserta didik terintegrasi Dapodik.
- `JadwalKbm.php`, `JadwalPengaturan.php`: Entitas penjadwalan dan batasan jam pelajaran harian.
- `PresensiHarian.php`, `PresensiMapel.php`, `PresensiMengajar.php`: Rekaman kehadiran waktu-nyata.
- `Persuratan.php`, `PersuratanDisposisi.php`: Manajemen arsip surat masuk dan keluar.

### D. Support Classes (`app/Support/`)
Menyediakan kamus data, helper otorisasi, dan scanner sistem:
- `PermissionDictionary.php`: Kamus definisi cluster modul, menu, dan izin default sistem.
- `DutyPermissionDefaults.php`: Kamus pemetaan izin default untuk 20+ kode tugas tambahan (Waka, Kepala TAS, Piket, dsb.).
- `DutyPermissionManager.php`: Pengelolaan sinkronisasi, pembaruan, reset default, dan toggle izin tugas tambahan.
- `ModuleDiscovery.php`: Scanner dinamis rute, controller, dan view untuk mendeteksi modul baru serta sinkronisasi otomatis ke database.
- `RolePermissionRestrictions.php`: Daftar batasan modul terlarang (`FORBIDDEN_MODULES`) per peran.
- `WaliKelasAccessHelper.php`: Helper pengecekan hak akses Wali Kelas, Koordinator Kelas, dan resolusi rombel binaan.
- `DapodikSyncHelper.php`: Pengolah sinkronisasi Dapodik dan pemetaan payload.
- `SemesterHelper.php`: Resolusi semester aktif dan tahun ajaran Dapodik.

### E. Middleware (`app/Http/Middleware/`)
- `CheckRolePermission.php`: Memverifikasi kecukupan izin pengguna terhadap permission key (`permission:nama_key,action`).
- `BroadcastDataChanges.php`: Mendeteksi mutasi data via HTTP POST/PUT/DELETE dan memicu event perubahan realtime.
- `EnsureInstalled.php`: Memastikan aplikasi telah menyelesaikan instalasi awal sebelum rute utama diakses.

---

## 3. Tanggung Jawab Frontend & Aset Publik

### A. Template Blade (`resources/views/`)
- `layouts/`:
  - `admin.blade.php`: Kerangka master dashboard dengan integrasi sidebar, topbar, modal global, dan polling realtime.
  - `auth.blade.php`: Kerangka halaman autentikasi (login, reset password).
  - `public.blade.php`: Kerangka portal publik depan dan kiosk scanner presensi.
- `partials/`:
  - `dash-sidebar.blade.php`: Navigasi samping dengan render dinamis per permission key dan status tugas tambahan.
  - `dash-header.blade.php`: Topbar berisi profil pengguna, switcher tema (dark/light), dan dropdown notifikasi.
- `dashboard/`:
  - Subfolder terstruktur per modul fitur (`presensi/`, `jadwal-kbm/`, `kesiswaan/`, `persuratan/`, `pip/`, `wali-kelas/`, dsb.).

### B. Aset Statis Publik (`public/`)
- `public/css/`: CSS tema spesifik per modul (`dashboard.css`, `cetak.css`, `kartu-pelajar.css`, `presensi.css`).
- `public/js/`: Skrip interaktif modular yang di-load per halaman (`dashboard.js`, `presensi-scan.js`, `tendik-dashboard.js`, dsb.).
- `public/build/`: Asset CSS dan JS hasil bundler Vite dari Tailwind CSS.

---

## 4. Aturan Penempatan Kode Baru

1. **Logika Bisnis Berat:** Jangan meletakkan kalkulasi berat atau manipulasi data masif di dalam Controller atau Blade. Buat Service baru di `app/Services/`.
2. **Komponen Tampilan Berulang:** Pecah komponen UI yang digunakan lebih dari dua kali menjadi partials di `resources/views/partials/` atau subfolder terkait di `resources/views/components/`.
3. **Penambahan Rute Baru:**
   - Selalu sertakan middleware `permission:<permission_key>,<action>` pada setiap rute dashboard.
   - Daftarkan key permission baru pada `App\Models\RolePermission` melalui migrasi database terstruktur.
4. **Skrip Otomasi / Audit:** Letakkan seluruh script uji dan inspeksi sementara di dalam folder `scratch/` agar tidak masuk ke pelacakan git.
