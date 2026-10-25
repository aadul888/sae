# MODULE_DEPENDENCIES.md — Dependensi & Relasi Antarmodul SAE

Dokumen ini mencatat dependensi antarmodul dan alur keterkaitan data yang telah diverifikasi pada kode sumber aktual SAE.

---

## 1. Peta Ketergantungan Tingkat Tinggi

```
                  ┌────────────────────────────────────────┐
                  │       Dapodik Kemendikbudristek        │
                  │   (REST API — Read-Only Sync Engine)   │
                  └───────────────────┬────────────────────┘
                                      │
                                      ▼
                  ┌────────────────────────────────────────┐
                  │          Basis Data Induk SAE          │
                  │ (Sekolah, Rombel, Pembelajaran, GTK,   │
                  │          Peserta Didik, UUID)          │
                  └───────┬──────────────┬──────────┬──────┘
                          │              │          │
            ┌─────────────┘              │          └─────────────┐
            ▼                            ▼                        ▼
┌───────────────────────┐    ┌──────────────────────┐   ┌──────────────────────┐
│  Autentikasi & RBAC   │    │  Jadwal & KBM Engine │   │  Presensi Terpadu    │
│  (RolePermission,     │    │  (AutoScheduler,     │   │  (RFID Scan, Kiosk,  │
│  PtkTugasTambahan,    │    │  BlockTimetable,     │   │  Harian, Mapel,      │
│  CheckRolePermission) │    │  JadwalPengaturan)   │   │  PresensiMengajar)   │
└───────────┬───────────┘    └──────────┬───────────┘   └──────────┬───────────┘
            │                           │                          │
            └───────────────────────────┼──────────────────────────┘
                                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                              Modul Operasional                               │
│  ├─ Kesiswaan & Kartu Pelajar (Identitas, Mutasi, Prestasi, Cetak Kartu)     │
│  ├─ PIP (Program Indonesia Pintar: Kuota Rombel, Usulan, Riwayat)            │
│  ├─ Persuratan & Arsip Digital (Surat Masuk, Disposisi, Surat Keluar, HDD)  │
│  ├─ TAS (Tenaga Administrasi Sekolah: Sarpras, Perpus, Lab, Keamanan, Piket) │
│  └─ Wali Kelas Terpadu (Presensi, Nilai/Validasi, Agenda, Cetak Raport)      │
└───────────────────────────────────────┬──────────────────────────────────────┘
                                        │
                                        ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                      Layanan Infrastruktur & Notifikasi                      │
│  ├─ BroadcastDataChanges (Middleware mutasi data)                            │
│  ├─ RealtimeService (Antrean event polling browser)                          │
│  ├─ SystemNotificationService (Agregasi badge notifikasi)                    │
│  └─ UpdateService & BackupService (Pembaruan GitHub & backup snapshot)       │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Rincian Dependensi Setiap Modul

### A. Autentikasi & Otorisasi Terdistribusi (RBAC)
- **Komponen Inti:** `App\Models\RolePermission`, `App\Http\Middleware\CheckRolePermission`, `App\Models\User`, `App\Models\Gtk`, `App\Models\PtkTugasTambahan`.
- **Alur Kerja:**
  1. Pengguna login via `AuthController` -> data sesi disimpan pada `session('user')`.
  2. Permintaan HTTP melewati middleware `permission:<key>,<action>`.
  3. `CheckRolePermission` memanggil `RolePermission::canAccess($user, $key, $action)`.
  4. Hak akses dievaluasi berdasarkan gabungan:
     - Hak akses peran dasar (`admin`, `guru`, `tendik`, `peserta_didik`).
     - Hak akses turunan dari tugas tambahan yang tercatat aktif di tabel `ptk_tugas_tambahan` (misal: `WALI_KELAS`, `WAKA_KURIKULUM`, `STAF_PERSURATAN`, `GURU_PIKET`).
- **Dependensi Terdampak:** `dash-sidebar.blade.php`, `DutyDashboardController`, seluruh route di bawah prefix `/dashboard/`.

### B. Sinkronisasi Dapodik & Data Induk Sekolah
- **Komponen Inti:** `App\Http\Controllers\DapodikController`, `App\Support\RefDapodikHelper`, Model: `Sekolah`, `Rombel`, `Pembelajaran`, `Gtk`, `PesertaDidik`.
- **Karakteristik Kritis:**
  - Kunci utama (primary key) menggunakan UUID resmi dari Dapodik (`sekolah_id`, `rombongan_belajar_id`, `ptk_id`, `peserta_didik_id`).
  - Koneksi Dapodik bersifat **Read-Only (GET strictly)**.
  - Normalisasi format single-quoted JSON Dapodik ditangani oleh wrapper decode khusus.

### C. Penjadwalan KBM (AutoScheduler & Manual)
- **Komponen Inti:** `App\Services\AutoSchedulerService`, `App\Services\BlockTimetableSolver`, `App\Http\Controllers\JadwalKbmController`, `App\Http\Controllers\JadwalKbmManualController`.
- **Tabel Basis Data Terkait:** `jadwal_kbm`, `jadwal_pengaturan`, `jadwal_guru_preferensi`, `pembelajaran`, `ptk`, `rombongan_belajar`.
- **Ketergantungan Algoritma:**
  - `AutoSchedulerService` memproses data beban mengajar (`pembelajaran`), preferensi libur/hari guru (`jadwal_guru_preferensi`), dan batasan JP tingkat (`jadwal_pengaturan`).
  - Algoritma Pass 4 memanggil `BlockTimetableSolver` (Simulated Annealing) untuk merapikan blok mata pelajaran tanpa tabrakan ruang dan guru sebelum fallback ke slot layout linear.

### D. Presensi Siswa, Guru & Kiosk RFID
- **Komponen Inti:** `App\Http\Controllers\PresensiController`, `App\Http\Controllers\PresensiMengajarController`, Model: `PresensiHarian`, `PresensiMapel`, `PresensiMengajar`, `PresensiPengaturan`.
- **Keterkaitan Antarmodul:**
  - Kiosk `/presensi/scan` memvalidasi `kode_akses` terminal dari `presensi_pengaturan`.
  - Scan RFID membaca `rfid_uid` pada tabel `pengguna` / `peserta_didik` / `gtk`.
  - Modul Presensi Mengajar terikat dengan jadwal KBM hari aktif (`jadwal_kbm`) dan kalender pendidikan (`kalender_pendidikan`).

### E. Program Indonesia Pintar (PIP)
- **Komponen Inti:** `App\Http\Controllers\PipController`, Model: `PipPengaturan`, `PipKuotaRombel`, `PipUsulan`, `PipRiwayat`, `PesertaDidik`, `Rombel`.
- **Keterkaitan:**
  - Wali kelas mengusulkan siswa via modul wali kelas -> masuk ke tabel `pip_usulan`.
  - Admin/Kesiswaan memverifikasi kuota rombel (`pip_kuota_rombel`) dan mengelola SK riwayat pencairan (`pip_riwayat`).
  - Ekspor/impor format Excel menggunakan `SimpleXlsxExporter` dan `SimpleXlsxReader`.

### F. Persuratan, Disposisi & Harddisk Archive
- **Komponen Inti:** `App\Http\Controllers\SuratMasukController`, `App\Http\Controllers\SuratKeluarController`, `App\Services\PersuratanHddService`, Model: `Persuratan`, `PersuratanDisposisi`, `PersuratanTemplate`.
- **Keterkaitan:**
  - Setiap surat masuk dapat didelegasikan menjadi disposisi ke ptk tujuan (`ptk_id_tujuan`).
  - File fisik disimpan melalui `PersuratanHddService` yang dapat dialokasikan ke jalur penyimpanan disk terpisah/eksternal.
  - Verifikasi keaslian publik surat dapat diakses tanpa login via rute `/v/doc/{doc_id}` (`SuratPublicVerifyController`).

### G. Realtime Mutation & Notifikasi Sistem
- **Komponen Inti:** `App\Http\Middleware\BroadcastDataChanges`, `App\Services\RealtimeService`, `App\Services\SystemNotificationService`, `App\Http\Controllers\RealtimeController`.
- **Keterkaitan:**
  - Mutasi POST/PUT/DELETE di-intercept oleh `BroadcastDataChanges` -> memicu `RealtimeService::trigger('channel', 'event', $data)`.
  - Polling AJAX dari browser (`public/js/dashboard.js`) mengambil event terbaru dari `RealtimeController`.
  - Topbar `dash-header.blade.php` memanggil `SystemNotificationService::getNotifications()` untuk memunculkan badge notifikasi sesuai penugasan aktif pengguna.

---

## 3. Komponen yang Perlu Perhatian Khusus Saat Refactoring

1. **`RolePermission.php`:** Digunakan oleh hampir seluruh controller dan view. Pemecahan atau perubahan struktur metode `canAccess()` wajib mempertahankan signature dan tipe data input `($user, $permissionKey, $action)`.
2. **`session('user')`:** Bukan objek User Eloquent murni, melainkan array asosiatif (atau fallback objek). Pastikan pengecekan field selalu mendukung sintaks `$user['field'] ?? $user->field`.
3. **`AutoSchedulerService.php`:** Memiliki batas waktu eksekusi (timeout budget ~25-30 detik) saat komputasi solver. Jangan menambahkan query N+1 di dalam loop optimasi slot KBM.
