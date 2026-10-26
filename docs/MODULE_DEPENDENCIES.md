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

### G. Laboratorium — Penjadwalan Praktik & Laporan Penggunaan Lokasi
- **Komponen Inti:** `App\Http\Controllers\LaboranJadwalController`, `App\Http\Controllers\LaboranInventarisController`.
- **Tabel Basis Data Terkait:** `laboran_jadwal_penggunaan`, `laboran_bahan_alat`, `sarpras_ruang`, `pembelajaran`, `gtk`, `rombongan_belajar`.
- **Keterkaitan Antarmodul:**
  - **Lokasi praktik** diambil dari master `sarpras_ruang` (Sarpras & Aset) dan diklasifikasikan otomatis ke `jenis_lokasi`: `lab`, `bengkel`, `lahan`, `lapangan`, `lainnya`. Penambahan lokasi baru cukup dilakukan di modul Sarpras.
  - **Mata pelajaran & guru pengampu** diambil otomatis dari `pembelajaran` Dapodik berdasarkan `rombel_id` (endpoint `laboran.jadwal.pembelajaran`). Rombel jenis "Matapelajaran Pilihan" dengan nama sama ikut dicakup karena praktik kejuruan sering diampu di sana.
  - **Topik / materi praktik** bersifat opsional (`topik_praktik` NULLABLE).
  - **Laporan** (`laboran.laporan.index` & `laboran.laporan.cetak`) memakai pola rentang periode yang sama dengan `TendikLaporanController`: bulanan, triwulan (3 bulan), semester (Juli–Desember / Januari–Juni), dan tahun ajaran (1 Juli – 30 Juni).
  - Penanda tangan laporan: petugas laboran yang login, Kepala Laboratorium (`ref_tugas_tambahan.kode = KEPALA_LAB`), dan Kepala Sekolah (`gtk.jenis_ptk_id_str`/`jabatan_ptk_id_str`).
  - **Validasi bentrok**: satu lokasi tidak boleh dipakai dua sesi dengan jam beririsan pada tanggal yang sama; guru juga tidak boleh terjadwal ganda. Status `batal` dikecualikan dari pengecekan.

### G2. Laboratorium per JURUSAN — Tata Kelola Alat & Bahan Praktik
- **Komponen Inti:** `App\Support\LaboranScope`, `App\Http\Controllers\Laboran\LaboranBaseController`, serta controller `Laboran\Laboran{Dashboard,Kebutuhan,Perawatan,Pemeriksaan,Pengadaan,Kerusakan,Peminjaman,Laporan}Controller`.
- **Tabel Basis Data Terkait:** `laboran_bahan_alat`, `laboran_kebutuhan`, `laboran_perawatan`, `laboran_kartu_perawatan`, `laboran_pemeriksaan`, `laboran_pemeriksaan_item`, `laboran_usulan_pengadaan`, `laboran_usulan_pengadaan_item`, `laboran_berita_acara_kerusakan`, `laboran_peminjaman`, `laboran_peminjaman_item`, `ptk_tugas_tambahan`, `jurusan_meta`.

- **Aturan Scoping (WAJIB dipahami sebelum mengubah modul laboran):**
  Setiap Laboran ditugaskan pada **satu jurusan** melalui `ptk_tugas_tambahan.jurusan_id`. `LaboranScope::resolve()` menghasilkan konteks:

  | Kondisi | `unrestricted` | Perilaku data |
  |---|---|---|
  | `role === 'admin'` | `true` | Seluruh jurusan |
  | Tugas `KEPALA_LAB` | `true` | Lintas jurusan (koordinatif) |
  | Tugas `LABORAN` + `jurusan_id` terisi | `false` | Terkunci ke jurusan tersebut |
  | Tugas `LABORAN` + `jurusan_id` NULL | `true` | *Fallback permisif* + badge peringatan di UI |

  Tiga lapis penegakan: (1) `LaboranScope::apply()` pada query, (2) `findScoped()` untuk baris tunggal (gagal → 404), (3) `LaboranScope::jurusanForWrite()` yang **memaksa** jurusan milik laboran saat menyimpan (mengabaikan `jurusan_id` dari form).

  > **Catatan operasional:** saat ini keempat baris tugas LABORAN di database belum memiliki `jurusan_id`, sehingga berlaku fallback permisif. Lengkapi melalui `dashboard/pengguna` → tab Tugas Tambahan agar pembatasan aktif.

- **Alur Prosedural antar-Modul:**
  - **Kebutuhan → Pengadaan**: usulan pengadaan dapat menarik item dari `laboran_kebutuhan` (status `diverifikasi`/`disetujui` yang belum masuk usulan). Jumlah ditarik dari `selisih` (kekurangan).
  - **Perawatan → Inventaris**: perawatan berstatus `selesai` dengan `kondisi_akhir` menyinkronkan `laboran_bahan_alat.kondisi`. `kondisi_akhir = tidak_laik` dipetakan ke `rusak_berat`.
  - **Kerusakan → Inventaris**: berita acara menurunkan kondisi alat (`ringan`→`rusak_ringan`, `sedang`/`berat`/`total`→`rusak_berat`).
  - **Peminjaman → Inventaris**: stok berkurang saat transisi `disetujui`→`dipinjam`, dan pulih saat `dikembalikan`. Kondisi kembali `rusak_ringan`/`rusak_berat` memperbarui kondisi alat. `hilang` tidak mengembalikan stok.
  - **Kartu Perawatan**: menyimpan `interval_hari` dan menghitung `jadwal_berikutnya`; aksi "tandai sudah dirawat" menggeser jadwal satu interval ke depan.
  - **Pemeriksaan**: header + item ceklist; rekap `total_sesuai`/`total_tidak_sesuai`/`total_tidak_ada` dihitung ulang setiap penyimpanan.
  - **Laporan Lengkap**: mengagregasi seluruh modul di atas per periode (bulanan/triwulan/semester/tahun ajaran) dan menyediakan dokumen cetak A4 resmi.

- **Penomoran Dokumen Otomatis** (dari `LaboranBaseController::generateNomor()`):
  `PRW/YYYYMM/NNNN` (perawatan), `KRT/…` (kartu), `PRK/…` (pemeriksaan), `USL/…` (usulan pengadaan), `BAK/…` (berita acara kerusakan), `PJM/…` (peminjaman).

- **Permission Key:** `menu_laboran_kebutuhan`, `menu_laboran_perawatan`, `menu_laboran_pemeriksaan`, `menu_laboran_pengadaan`, `menu_laboran_kerusakan`, `menu_laboran_laporan`, `menu_laboran_administrasi`, `menu_laboran_permintaan`, `menu_laboran_arsip`, `menu_laboran_jurnal`, `menu_laboran_kebersihan` (terdaftar di `PermissionDictionary` dan `DutyPermissionDefaults::forDuty('LABORAN')`).

### G3. Kelengkapan TUPOKSI Laboran — Pemetaan 9 Indikator Supervisi
Modul laboran dipetakan langsung ke 9 indikator tupoksi supervisi agar setiap bukti fisik punya penghasil dokumen:

| # | Indikator Tupoksi | Bukti Fisik | Modul / Route | Cetak |
|---|---|---|---|---|
| 1 | Administrasi program keahlian | Buku Administrasi Jurusan & Jadwal Lab | `laboran/administrasi` + `laboran/jadwal` | `administrasi/cetak` |
| 2 | Penyiapan alat & bahan praktik | Bon/Form Permintaan | `laboran/permintaan` | `permintaan/{id}/cetak` |
| 3 | Pemeliharaan & penyimpanan alat | Kartu Perawatan & Penataan K3 | `laboran/perawatan` | — |
| 4 | Inventarisasi alat & bahan | Buku Induk Inventaris | `laboran/inventaris` | — |
| 5 | Pemeriksaan kelayakan alat | Checklist Sebelum/Sesudah Pakai | `laboran/pemeriksaan` (jenis `sebelum_pakai`/`sesudah_pakai`) | — |
| 6 | Pendataan kebutuhan | Daftar Usulan Pengadaan | `laboran/kebutuhan` + `laboran/pengadaan` | — |
| 7 | Pengarsipan dokumen | Ordner BA Kerusakan & Kartu Peminjaman | `laboran/kerusakan` + `laboran/peminjaman-alat` + `laboran/arsip` | `kerusakan/{id}/cetak` |
| 8 | Penyusunan laporan | Laporan Rekapitulasi & Jurnal Kegiatan | `laboran/laporan-lengkap` + `laboran/jurnal` | `laporan-lengkap/cetak`, `jurnal/cetak` |
| 9 | Kebersihan lingkungan produktif | Dokumentasi Kegiatan | `laboran/kebersihan` | `kebersihan/cetak` |

- **Tabel tambahan:** `laboran_permintaan` + `laboran_permintaan_item`, `laboran_jurnal`, `laboran_kebersihan`, `laboran_administrasi`, `laboran_arsip`.
- **Integrasi stok:** serah terima bon permintaan mengurangi stok **hanya untuk `jenis = bahan_habis_pakai`**; alat tidak dikurangi (hanya dipinjam).
- **Peta indikator:** `LaboranJurnalController::KATEGORI` memetakan kategori jurnal ke nomor indikator (`administrasi`→#1, `penyiapan`→#2, `pemeliharaan`→#3, `inventarisasi`→#4, `pemeriksaan`→#5, `kebutuhan`→#6, `pengarsipan`→#7, `pelaporan`→#8, `kebersihan`→#9), sehingga rekap jurnal dapat langsung dipakai sebagai bukti penilaian.
- **Arsip massal:** `laboran/arsip` dapat menarik dokumen dari modul lain (`laboran_berita_acara_kerusakan`, `laboran_peminjaman`, `laboran_permintaan`, `laboran_pemeriksaan`, `laboran_usulan_pengadaan`) dengan penanda `sumber_tabel` + `sumber_id` untuk mencegah duplikasi.
- **Penomoran:** `PMT/YYYYMM/NNNN` (permintaan), `KBR/…` (kebersihan), `ARS/…` (arsip).

### H. Realtime Mutation & Notifikasi Sistem
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
