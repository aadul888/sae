# DOKUMENTASI LENGKAP FITUR, MENU, DAN HAK AKSES (RBAC) SISTEM APLIKASI EDUKASI (SAE)

Dokumen ini adalah referensi komprehensif seluruh arsitektur menu, fitur fungsional, dan matriks hak akses (*Role-Based & Duty-Based Access Control*) pada sistem **SAE (Sistem Aplikasi Edukasi)**.

---

## 1. Arsitektur Otorisasi Hybrid (Dual-Layer RBAC)

Sistem SAE menerapkan otorisasi bertingkat dua lapis yang fleksibel dan aman:

```
┌──────────────────────────────────────────────────────────────────┐
│                   Evaluasi Akses Pengguna                        │
│                RolePermission::canAccess($user)                  │
└───────────────────────────────┬──────────────────────────────────┘
                                │
             ┌──────────────────┴──────────────────┐
             ▼                                     ▼
    [1. Izin Peran Dasar]                [2. Izin Tugas Tambahan]
   Tabel: `role_permissions`             Tabel: `ptk_tugas_tambahan`
   (admin, guru, tendik, siswa)           & `ref_tugas_tambahan`
             │                                     │
             │     (Jika Belum Diizinkan Role)     │
             └──────────────────► ◄────────────────┘
                                │
                                ▼
                [3. Granular Hak Aksi (CRUD)]
              can_create, can_read, can_update,
                         can_delete
```

### Komponen Pengendali Akses
1. **Role Dasar (`role`)**:
   - **`admin`**: Administrator sistem & operator utama sekolah.
   - **`guru`**: Pendidik / guru mata pelajaran.
   - **`tendik`**: Tenaga kependidikan / staf tata usaha / laboran / pustakawan / satpam / penjaga sekolah.
   - **`peserta_didik`**: Siswa / siswi aktif sekolah.
2. **Penugasan Tugas Tambahan (`ptk_tugas_tambahan` & `ref_tugas_tambahan`)**:
   - Melekatkan hak akses menu khusus secara otomatis ke akun personal guru/tendik tanpa harus mengubah role dasar mereka.
3. **Granular Action Rights**:
   - Setiap permission key dapat dikunci atau dibuka per aksi: `can_create` (tambah), `can_read` (lihat), `can_update` (ubah), `can_delete` (hapus).
4. **Middleware Proteksi**:
   - `permission:{key}` atau `permission:{key},{action}` (contoh: `permission:menu_persuratan,create`).

---

## 2. Katalog Lengkap Cluster Menu & Fitur Sistem

Berikut adalah pengelompokan seluruh menu dan fitur operasional dalam sistem SAE:

### A. Cluster: Menu Utama
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_dashboard` | **Dashboard Utama** | Portal ringkasan statistik harian, pasfoto resmi, pintasan cepat, dan notifikasi aktivitas personal. | `admin`, `guru`, `tendik`, `peserta_didik` |
| `menu_dapodik` | **Tarik Data Dapodik** | Antarmuka sinkronisasi integrasi live feeder data lokal Dapodik Kemendikbudristek. | `admin` |

---

### B. Cluster: Master Data
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_kompetensi_keahlian` | **Kompetensi Keahlian** | Manajemen jurusan, bidang keahlian, kurikulum, dan logo program keahlian. | `admin` |
| `menu_rombel` | **Rombongan Belajar** | Daftar rombel reguler/pilihan, tingkat kelas, ruang kelas, dan wali kelas terkait. | `admin`, `guru`, `tendik` |
| `menu_pembelajaran` | **Pembelajaran** | Struktur mapel per rombel, guru pengampu, dan alokasi Jam Mengajar per Minggu (JJM). | `admin`, `guru` |
| `menu_jadwal_kbm` | **Jadwal KBM** | Matriks penjadwalan jam pelajaran harian per kelas, guru, dan ruangan. | `admin`, `guru`, `tendik`, `peserta_didik` |
| `menu_kalender_pendidikan` | **Kalender Pendidikan** | Agenda kegiatan akademik, libur nasional, jeda semester, dan pekan efektif KBM. | `admin`, `guru`, `tendik` |

---

### C. Cluster: Manajemen Data
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_peserta_didik_aktif` | **Peserta Didik Aktif** | Direktori siswa aktif, Buku Induk Siswa, NISN, NIK, biodata ortu, dan pasfoto digital. | `admin`, `guru`, `tendik` |
| `menu_guru_aktif` | **Guru Aktif** | Direktori pendidik aktif, NIP, NUPTK, mapel utama, riwayat pendidikan, dan status GTK. | `admin`, `guru`, `tendik` |
| `menu_tendik_aktif` | **Tendik Aktif** | Direktori tenaga kependidikan aktif, NIP, jenis PTK, dan bidang penugasan administrasi. | `admin`, `guru`, `tendik` |
| `menu_berkas_peserta_didik` | **Berkas Peserta Didik** | Pengelolaan arsip digital dokumen siswa (akta lahir, KK, ijazah, rapor asal). | `admin`, `guru`, `tendik` |
| `menu_perubahan_data` | **Perubahan Data** | Log pengajuan atau riwayat revisi data identitas siswa/GTK dari sinkronisasi. | `admin`, `guru`, `tendik` |
| `menu_peserta_didik_tidak_aktif` | **Peserta Didik Tidak Aktif** | Arsip data siswa lulus, mutasi keluar, dikeluarkan, atau mengundurkan diri. | `admin`, `tendik` |
| `menu_guru_tidak_aktif` | **Guru Tidak Aktif** | Arsip riwayat pendidik purnatugas/pensiun atau mutasi dinas. | `admin`, `tendik` |
| `menu_tendik_tidak_aktif` | **Tendik Tidak Aktif** | Arsip riwayat staf tata usaha / tendik purnatugas atau mutasi. | `admin`, `tendik` |

---

### D. Cluster: Layanan Digital
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_formulir` | **Formulir & Survei** | Pembangun form dinamis (SAE Forms) untuk angket, survei, pendaftaran, dan pemilu sekolah. | `admin`, `guru`, `tendik`, `peserta_didik` |
| `menu_pengumuman` | **Pengumuman & Broadcast** | Siaran warta digital sekolah, info edaran dinas, dan notifikasi kegiatan resmi. | `admin`, `guru`, `tendik`, `peserta_didik` |
| `menu_rfid` | **RFID & Presensi Realtime** | Terminal pemantau presensi gerbang tap RFID live dan log kehadiran gerbang. | `admin`, `guru`, `tendik` |
| `menu_e_izin` | **E-Izin** | Pengajuan dan verifikasi permohonan izin/sakit/dispensasi digital berjenjang. | `admin`, `guru`, `tendik`, `peserta_didik` |
| `menu_poin` | **Poin & Pelanggaran** | Pencatatan buku saku kedisiplinan siswa: poin pelanggaran dan poin prestasi akademik. | `admin`, `guru`, `tendik` |
| `menu_kelulusan` | **Kelulusan Peserta Didik** | Portal publikasi status kelulusan angkatan akhir dan cetak SKL digital. | `admin`, `guru`, `tendik`, `peserta_didik` |

---

### E. Cluster: Administrasi Guru
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_presensi_mengajar` | **Presensi Mengajar** | Pencatatan presensi tatap muka guru di rombel per jam pelajaran real-time. | `admin`, `guru` |
| `menu_agenda_kbm` | **Jurnal & Agenda KBM** | Pengisian jurnal harian materi, capaian kompetensi (CP/TP), kendala KBM, dan absensi kelas. | `admin`, `guru` |
| `menu_penilaian` | **Penilaian Peserta Didik** | Entri nilai asesmen formatif, sumatif, PTS, PAS, dan rekapitulasi nilai rapor. | `admin`, `guru` |

---

### F. Cluster: Wali Kelas
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_wali_kelas_aktif` | **Siswa Binaan Aktif** | Monitoring khusus daftar siswa binaan dalam satu rombel yang diampu oleh wali kelas. | `admin`, `guru` (Wali Kelas) |
| `menu_wali_kelas_tidak_aktif`| **Siswa Binaan Tidak Aktif**| Riwayat siswa binaan yang mutasi atau berhenti dari rombel terkait. | `admin`, `guru` (Wali Kelas) |
| `menu_wali_kelas_presensi` | **Presensi Kelas Wali** | Rekapitulasi kehadiran siswa binaan, persentase absensi, dan penerbitan laporan wali kelas. | `admin`, `guru` (Wali Kelas) |

---

### G. Cluster: Administrasi Tendik & TAS
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_aktivitas_tendik` | **Aktivitas Harian Tendik** | Pencatatan buku jurnal kerja harian, volume output kegiatan, jam kerja, durasi menit, dan status tugas. | `admin`, `tendik` |
| `menu_laporan_tendik` | **Laporan Kinerja Tendik** | Rekapitulasi capaian kinerja dan log aktivitas periodik (bulan, triwulan, semester, tahun) siap cetak resmi ber-Kop Surat. Tidak ada absensi scanner bagi tendik; kinerja dinilai murni dari hasil kerja nyata. | `admin`, `tendik` |
| `menu_persuratan` | **Persuratan & Arsip** | Pencatatan agenda surat masuk, surat keluar, nomor agenda dinas, file arsip digital, dan disposisi. | `admin`, `tendik` |
| `menu_surat_keluar` | **Surat Keluar** | Registrasi surat keterangan, surat izin operasional, dan nomor surat keluar resmi. | `admin`, `tendik` |
| `menu_buku_tamu` | **Buku Tamu Digital** | Pencatatan kehadiran tamu kedinasan, wali murid, atau pihak luar sekolah secara mandiri. | `admin`, `tendik` |
| `menu_inventaris` | **Inventaris Sarpras** | Pendataan sarana prasarana, nomor inventaris barang, kondisi aset, dan pemeliharaan ruang. | `admin`, `tendik` |
| `menu_agenda` | **Agenda Sekolah / Kelas**| Kalender agenda kedinasan sekolah dan pemakaian ruang serbaguna/aula. | `admin`, `guru`, `tendik` |

---

### H. Cluster: Portal Peserta Didik
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_surat_izin_pd` | **Surat Izin & Sakit Siswa** | Formulir mandiri pengajuan izin/sakit siswa dengan upload bukti surat dokter/ortu. | `peserta_didik`, `admin` |
| `menu_riwayat_rfid` | **Riwayat Presensi RFID** | Log riwayat jam masuk dan jam pulang yang tercatat via sensor gerbang kartu RFID. | `peserta_didik` |
| `menu_jadwal_pelajaran` | **Jadwal Pelajaran Siswa** | Tampilan jadwal mingguan mata pelajaran spesifik untuk rombel siswa yang bersangkutan. | `peserta_didik` |
| `menu_rapor` | **Transkrip & Rapor** | Lembar transkrip capaian kompetensi dan riwayat rapor semester siswa. | `peserta_didik` |
| `menu_validasi_berkas` | **Validasi Berkas & Ijazah**| Verifikasi keabsahan data ijazah, NISN, dan nomor induk kependudukan. | `peserta_didik` |
| `menu_presensi_peserta_didik`| **Presensi Peserta Didik** | Verifikasi dan rekap data kehadiran harian peserta didik di kelas. | `admin`, `guru` (Wali Kelas) |

---

### I. Cluster: Sistem & Pengaturan
| Permission Key | Label Menu | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `menu_pengguna` | **Manajemen Pengguna** | Kelola akun multi-role: reset password, ganti username, blokir akun, atau pembuatan akun baru. | `admin` |
| `menu_hak_akses` | **Pengaturan Hak Akses** | Matriks kontrol RBAC dinamis: kelola izin per role, tugas tambahan, dan toggle hak aksi. | `admin` |
| `menu_pengaturan` | **Identitas Sekolah** | Konfigurasi profil sekolah, NPSN, kop surat dinas, logo aplikasi, dan identitas kepsek. | `admin` |
| `menu_maintenance` | **Arsip & Maintenance** | Operasi pencadangan database, snapshot arsip, dan diagnostik performa server. | `admin` |
| `menu_update` | **Update Sistem** | Pembaruan versi rilis aplikasi SAE, migrasi database skema, dan integritas berkas. | `admin` |

---

### J. Cluster: Fitur Operasional Granular
| Permission Key | Label Fitur | Deskripsi Fungsi | Default Roles |
| :--- | :--- | :--- | :--- |
| `fitur_pengguna_edit` | **Edit Data Pengguna** | Hak untuk mengubah biodata akun pengguna lain di portal manajemen pengguna. | `admin` |
| `fitur_pengguna_hapus` | **Hapus Akun Pengguna** | Hak menghapus rekaman akun pengguna di tabel `pengguna`. | `admin` |
| `fitur_pengguna_reset` | **Reset Password Akun** | Hak melakukan reset password akun pengguna menjadi kata sandi default. | `admin` |
| `fitur_dapodik_sync` | **Generate API Key Feeder** | Hak memperbarui API Key rahasia untuk integrasi feeder sinkronisasi Dapodik. | `admin` |
| `fitur_system_update` | **Eksekusi Update Sistem**| Hak menjalankan proses update patch sistem ke server produksi. | `admin` |

---

## 3. Matriks Hak Akses Peran Dasar (Default Base Roles)

Tabel berikut menunjukkan hak akses bawaan (*default permissions*) untuk empat peran operasional:

| Permission Key | Menu / Fitur | Admin | Guru | Tendik | Peserta Didik |
| :--- | :--- | :---: | :---: | :---: | :---: |
| `menu_dashboard` | Dashboard Utama | ✅ | ✅ | ✅ | ✅ |
| `menu_dapodik` | Tarik Data Dapodik | ✅ | ❌ | ❌ | ❌ |
| `menu_kompetensi_keahlian` | Kompetensi Keahlian | ✅ | ❌ | ❌ | ❌ |
| `menu_rombel` | Rombongan Belajar | ✅ | ✅ | ✅ | ❌ |
| `menu_pembelajaran` | Pembelajaran & Matpel | ✅ | ✅ | ❌ | ❌ |
| `menu_jadwal_kbm` | Jadwal KBM | ✅ | ✅ | ✅ | ✅ |
| `menu_kalender_pendidikan` | Kalender Pendidikan | ✅ | ✅ | ✅ | ❌ |
| `menu_peserta_didik_aktif` | Peserta Didik Aktif | ✅ | ✅ | ✅ | ❌ |
| `menu_guru_aktif` | Guru Aktif | ✅ | ✅ | ✅ | ❌ |
| `menu_tendik_aktif` | Tendik Aktif | ✅ | ✅ | ✅ | ❌ |
| `menu_berkas_peserta_didik`| Berkas Peserta Didik | ✅ | ✅ | ✅ | ❌ |
| `menu_perubahan_data` | Perubahan Data | ✅ | ✅ | ✅ | ❌ |
| `menu_peserta_didik_tidak_aktif` | Siswa Tidak Aktif | ✅ | ❌ | ✅ | ❌ |
| `menu_guru_tidak_aktif` | Guru Tidak Aktif | ✅ | ❌ | ✅ | ❌ |
| `menu_tendik_tidak_aktif` | Tendik Tidak Aktif | ✅ | ❌ | ✅ | ❌ |
| `menu_formulir` | Formulir & Survei | ✅ | ✅ | ✅ | ✅ |
| `menu_pengumuman` | Pengumuman & Broadcast | ✅ | ✅ | ✅ | ✅ |
| `menu_rfid` | RFID & Presensi Gate | ✅ | ✅ | ✅ | ❌ |
| `menu_e_izin` | Layanan E-Izin | ✅ | ✅ | ✅ | ✅ |
| `menu_poin` | Poin Pelanggaran | ✅ | ✅ | ✅ | ❌ |
| `menu_kelulusan` | Portal Kelulusan | ✅ | ✅ | ✅ | ✅ |
| `menu_presensi_mengajar` | Presensi Mengajar Guru | ✅ | ✅ | ❌ | ❌ |
| `menu_agenda_kbm` | Jurnal & Agenda KBM | ✅ | ✅ | ❌ | ❌ |
| `menu_penilaian` | Penilaian Siswa | ✅ | ✅ | ❌ | ❌ |
| `menu_wali_kelas_aktif` | Siswa Binaan Wali Kelas| ✅ | 🔒 (Wali) | ❌ | ❌ |
| `menu_wali_kelas_presensi` | Presensi Binaan Wali | ✅ | 🔒 (Wali) | ❌ | ❌ |
| `menu_persuratan` | Persuratan & Arsip | ✅ | ❌ | ✅ | ❌ |
| `menu_surat_keluar` | Surat Keluar | ✅ | ❌ | ✅ | ❌ |
| `menu_buku_tamu` | Buku Tamu Digital | ✅ | ❌ | ✅ | ❌ |
| `menu_inventaris` | Inventaris Sarpras | ✅ | ❌ | ✅ | ❌ |
| `menu_agenda` | Agenda Kelas/Sekolah | ✅ | ✅ | ✅ | ❌ |
| `menu_surat_izin_pd` | Surat Izin Mandiri Siswa| ✅ | ❌ | ❌ | ✅ |
| `menu_riwayat_rfid` | Riwayat Scan Siswa | ❌ | ❌ | ❌ | ✅ |
| `menu_jadwal_pelajaran` | Jadwal Pelajaran Siswa | ❌ | ❌ | ❌ | ✅ |
| `menu_rapor` | Transkrip & Rapor | ❌ | ❌ | ❌ | ✅ |
| `menu_validasi_berkas` | Validasi Berkas Siswa | ❌ | ❌ | ❌ | ✅ |
| `menu_pengguna` | Manajemen Pengguna | ✅ | ❌ | ❌ | ❌ |
| `menu_hak_akses` | Pengaturan Hak Akses | ✅ | ❌ | ❌ | ❌ |
| `menu_pengaturan` | Pengaturan Identitas | ✅ | ❌ | ❌ | ❌ |
| `menu_maintenance` | Arsip & Maintenance | ✅ | ❌ | ❌ | ❌ |
| `menu_update` | Update Sistem | ✅ | ❌ | ❌ | ❌ |
| `fitur_pengguna_edit` | Edit Pengguna | ✅ | ❌ | ❌ | ❌ |
| `fitur_pengguna_hapus` | Hapus Pengguna | ✅ | ❌ | ❌ | ❌ |
| `fitur_pengguna_reset` | Reset Password | ✅ | ❌ | ❌ | ❌ |
| `fitur_dapodik_sync` | Token Feeder Dapodik | ✅ | ❌ | ❌ | ❌ |
| `fitur_system_update` | Eksekusi Update Patch | ✅ | ❌ | ❌ | ❌ |

> **Keterangan:**
> - ✅ = Diizinkan secara penuh secara *default*.
> - ❌ = Tidak diizinkan secara bawaan role (namun dapat dibuka melalui Penugasan Tugas Tambahan atau toggle RBAC).
> - 🔒 (Wali) = Otomatis terbuka bila guru memiliki SK aktif sebagai Wali Kelas di Dapodik.

---

## 4. Matriks Hak Akses Berbasis Tugas Tambahan (Duty-Based RBAC)

Jika seorang guru atau tendik diberikan surat keputusan (SK) penugasan tertentu, sistem secara otomatis membuka izin akses (*granted permissions*) ke modul-modul berikut:

| Kode Tugas | Nama Tugas Tambahan | Hak Akses yang Otomatis Terbuka (*Granted Permissions*) |
| :--- | :--- | :--- |
| `WAKA_KURIKULUM` | **Waka Bidang Kurikulum** | `menu_pembelajaran`, `menu_kompetensi_keahlian`, `menu_rombel`, `menu_jadwal_pelajaran` |
| `WAKA_KESISWAAN` | **Waka Bidang Kesiswaan** | `menu_peserta_didik_aktif`, `menu_poin`, `menu_e_izin`, `menu_riwayat_rfid` |
| `WAKA_HUBIN` | **Waka Bidang Hubin / Humas** | `menu_pengumuman`, `menu_buku_tamu`, `menu_agenda` |
| `WAKA_SARPRAS` | **Waka Bidang Sarana Prasarana** | `menu_inventaris` |
| `KAPROG` | **Kepala Program Keahlian** | `menu_kompetensi_keahlian`, `menu_rombel`, `menu_pembelajaran`, `menu_peserta_didik_aktif` |
| `KEPALA_PERPUS` | **Kepala Perpustakaan** | `menu_inventaris` |
| `KEPALA_LAB` | **Kepala Lab / Bengkel / Praktik** | `menu_inventaris` |
| `WALI_KELAS` | **Wali Kelas** | `menu_peserta_didik_aktif`, `menu_presensi_peserta_didik`, `menu_e_izin`, `menu_rapor`, `menu_wali_kelas_aktif`, `menu_wali_kelas_tidak_aktif` |
| `PEMBINA_OSIS` | **Pembina OSIS** | `menu_agenda`, `menu_pengumuman`, `menu_poin` |
| `PEMBINA_EKSKUL`| **Pembina Ekstrakurikuler / Pramuka** | `menu_agenda`, `menu_pengumuman` |
| `GURU_PIKET` | **Guru Piket Harian** | `menu_buku_tamu`, `menu_e_izin`, `menu_riwayat_rfid` |
| `KEPALA_TAS` | **Kepala Tenaga Administrasi (KTU)**| `menu_tendik_aktif`, `menu_guru_aktif`, `menu_peserta_didik_aktif`, `menu_buku_tamu`, `menu_inventaris`, `menu_berkas_peserta_didik`, `menu_persuratan` |
| `OPERATOR_DAPODIK`| **Operator Dapodik Sekolah (Ops)**| `menu_dapodik`, `menu_peserta_didik_aktif`, `menu_guru_aktif`, `menu_tendik_aktif`, `menu_rombel`, `menu_pembelajaran` |
| `STAF_KEPEGAWAIAN`| **Staf Administrasi Kepegawaian** | `menu_guru_aktif`, `menu_tendik_aktif`, `menu_guru_tidak_aktif`, `menu_tendik_tidak_aktif` |
| `STAF_KESISWAAN` | **Staf Administrasi Kesiswaan** | `menu_peserta_didik_aktif`, `menu_peserta_didik_tidak_aktif`, `menu_berkas_peserta_didik`, `menu_kelulusan` |
| `STAF_PERSURATAN`| **Staf Persuratan (Arsiparis)** | `menu_berkas_peserta_didik`, `menu_buku_tamu`, `menu_persuratan` |
| `STAF_SARPRAS` | **Staf Administrasi Sarpras** | `menu_inventaris` |
| `TEKNISI_IT` | **Teknisi IT & Jaringan** | `menu_rfid`, `menu_inventaris` |
| `LABORAN` | **Laboran (Staf Khusus Lab)** | `menu_inventaris` |
| `PUSTAKAWAN` | **Pustakawan (Pelayanan Perpustakaan)**| `menu_inventaris` |
| `SATPAM` | **Petugas Keamanan / Satpam** | `menu_buku_tamu`, `menu_rfid` |
| `PENJAGA_SEKOLAH`| **Penjaga Sekolah & Fasilitas** | `menu_buku_tamu` |

---

## 5. Dashboard Khusus Berdasarkan Bidang (Domain-Specific Dashboards)

Untuk Tenaga Administrasi Sekolah (Tendik), sistem menyediakan antarmuka dashboard khusus yang otomatis berganti tata letak sesuai bidang penugasan:

| Bidang Penugasan | Sub-View Partial | Fitur & Metrik Utama |
| :--- | :--- | :--- |
| **Kepala TAS** | `section-kepala-tas.blade.php` | Monitoring komprehensif 10 bidang, matriks staf TAS, ringkasan surat, siswa, GTK, & **Supervisi Switcher** ke seluruh bidang. |
| **Kesiswaan** | `section-kesiswaan.blade.php` | Total siswa L/P, pembagian rombel, pendaftaran siswa mutasi/terbaru, Buku Induk Siswa. |
| **Kepegawaian** | `section-kepegawaian.blade.php` | Statistik GTK (PNS, PPPK, Honorer), daftar penetapan SK tugas tambahan, direktori pegawai. |
| **Sarana & Prasarana** | `section-sarpras.blade.php` | Rekapitulasi ruang kelas, laboratorium, kantor, sanitasi, pemetaan fasilitas dan aset. |
| **Laboran** | `section-laboran.blade.php` | Jadwal praktikum aktif laboratorium, alokasi jam tatap muka, daftar lab sekolah. |
| **Perpustakaan** | `section-perpustakaan.blade.php` | Sirkulasi buku harian, status inventaris koleksi, log peminjaman. |
| **Teknisi IT** | `section-teknisi.blade.php` | Status server Dapodik, koneksi database, monitor terminal Kiosk RFID, infrastruktur IoT. |
| **Keamanan / Satpam** | `section-keamanan.blade.php` | Shortcut buka Kiosk Gate Presensi, lalu lintas kedatangan gerbang, buku tamu digital. |
| **Penjaga Sekolah** | `section-penjaga.blade.php` | Checklist kebersihan zona gedung/halaman, status keamanan fasilitas. |
| **Persuratan & Arsip** | `section-persuratan.blade.php` | Agenda surat masuk/keluar, pencatatan disposisi dokumen, riwayat arsip. |

---

## 6. Prosedur Pembuatan Modul Baru & Sinkronisasi Hak Akses

Jika pengembang menambahkan modul baru di masa mendatang, sistem SAE mendukung sinkronisasi dinamis:

1. **Gunakan Generator Modul**:
   ```bash
   php artisan sae:make-module {nama_modul} --title="Judul Modul" --icon="fa-icon" --role=tendik
   ```
2. **Daftarkan Route dengan Middleware Permission**:
   ```php
   Route::get('/nama-modul', [NamaModulController::class, 'index'])
       ->name('nama-modul.index')
       ->middleware('permission:menu_nama_modul,read');
   ```
3. **Sinkronkan ke Matriks Hak Akses Database**:
   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan tinker --execute="App\Models\RolePermission::syncAvailablePermissions();"
   ```
4. **Bersihkan Cache**:
   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize:clear
   ```
