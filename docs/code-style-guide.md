---
trigger: always_on
description: Panduan gaya kode, arsitektur sistem SAE, konvensi penamaan, batas ukuran file max 1.000 baris, zero code duplication, dan aturan integritas.
---

# SYSTEM ARCHITECTURE, CODE STYLE & DEVELOPER CONVENTIONS: SAE

Panduan ini adalah standar definitif dan aturan wajib untuk AI Agent dan pengembang yang bekerja pada sistem **SAE (Sistem Aplikasi Edukasi)**. Seluruh instruksi dalam aturan ini selalu aktif (`always_on`) dan wajib dipatuhi tanpa pengecualian.

---

## 1. Aturan Batas Ukuran File (STRICT: MAX 1.000 BARIS)

1. **Dilarang Menghasilkan File > 1.000 Baris:**
   - Tidak ada satu pun file kode (`.php`, `.blade.php`, `.js`, `.css`) di dalam workspace yang boleh melebihi **1.000 baris**.
   - Jika suatu Controller, Model, Service, Blade View, atau JavaScript mendekati **800–900 baris**, AI Agent **WAJIB** langsung memecahnya secara modular sebelum file tersebut melampaui 1.000 baris.
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

## 3. Lingkungan & Tech Stack

- **Framework**: Laravel 13.38.0 (PHP ^8.3 / Aktif: PHP 8.3.30 via Laragon: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`).
- **Database**: MySQL (`db_sae`), charset `utf8mb4_unicode_ci`.
- **Autentikasi**:
  - **Web**: Laravel session (`session('user')`) dengan provider Eloquent model `App\Models\User` yang mengikat tabel `pengguna`.
  - **API / Mobile**: Laravel Sanctum (`auth:sanctum`, personal access token) dengan endpoint `/api/v1/auth/*`.
- **Frontend Stack**:
  - Blade templating modular + komponen reusable `resources/views/components/sae/`.
  - **Vanilla JS modular terpisah dari Blade** (`public/js/*.js`).
  - CSS Custom berbasis design token: `public/css/tokens.css` (skala nilai),
    `public/css/components.css` (kelas komponen), `public/css/sae.css` & `public/css/dashboard.css`
    (gaya aplikasi). Lihat `docs/UI_DESIGN_STANDARDS.md`.
  - Vendor Assets: SweetAlert2 (`public/vendor/sweetalert2/`), FontAwesome 6, Chart.js.
- **Root Project Utama**: `c:\laragon\www\sae`
- **Ekosistem Pendukung**:
  - `c:\laragon\www\sae-feeder` (dan `loader-sae`): CLI / Web feeder data Dapodik lokal.
  - `c:\laragon\www\sae-app`: Versi legacy / modular PHP terdahulu.
- **Dokumen Acuan Produk & Otorisasi**:
  - `c:\laragon\www\sae\.agents\PRD.md`: Product Requirements Document (kebutuhan produk, persona, dan aturan invariant).
  - `c:\laragon\www\sae\.agents\HAK_AKSES_DAN_FITUR.md`: Acuan resmi matriks hak akses, RBAC, dan filter modul.

---

## 2. Aturan Struktur & Pemisahan Kode (Wajib Diikuti)

1. **Frontend / Backend / JS Separation (STRICT)**:
   - **DILARANG KERAS** menaruh tag `<script>` dengan logika inline di dalam file Blade dashboard (`resources/views/dashboard/*.blade.php`).
   - Seluruh logika interaksi JavaScript wajib ditaruh di file terpisah `public/js/{nama_modul}.js` dan di-push dari Blade:
     ```blade
     @push('scripts')
     <script src="{{ asset('js/{nama_modul}.js') }}"></script>
     @endpush
     ```
2. **Dialog Konfirmasi & Notifikasi Interaksi**:
   - Menggunakan **SweetAlert2** (`Swal.fire`). Dilarang menggunakan dialog bawaan browser (`confirm()` atau `alert()`).
   - Pola standar form tombol hapus/reset:
     ```html
     <form action="..." method="POST" data-confirm="delete" data-name="Nama Objek">
       @csrf @method('DELETE')
       <button type="submit" class="btn btn-danger">Hapus</button>
     </form>
     ```
     Lalu ditangani di JS secara *event delegation* dengan `Swal.fire({ showCancelButton: true, ... })`.
3. **Modal & Backdrop Handling**:
   - Seluruh modal wajib memiliki inline style atau CSS class dengan `z-index: 99999 !important` agar tidak tertutup header, sidebar, atau backdrop gelap.
   - Script penutup modal wajib mengembalikan style `display: none` dan menghapus backdrop yang tersisa di `document.body`.
4. **Desain Responsif & Mobile Support**:
   - Sidebar desktop: lebar fixed `270px`. Mobile: offcanvas via tombol hamburger di header.
   - Mobile navigation bar di `resources/views/partials/mobile-bottom-nav.blade.php`.
   - Seluruh tabel dashboard wajib mendukung horizontal scroll responsif atau card-stack pada viewport mobile (`< 768px`).
5. **Penanganan JSON & Keamanan Data**:
   - Selalu gunakan opsi `JSON_UNESCAPED_UNICODE` saat melakukan `json_encode` agar data nama/gelar berkarakter khusus tidak ter-escape secara biner.
   - Sanitasi input form dengan validasi form request standar Laravel (`$request->validate([...])`).
6. **Standar Baku Tata Letak Card, Toolbar, Tab & Datatable Terpadu (ACUAN: PESERTA-DIDIK-AKTIF & HAK-AKSES)**:
   - **Urutan Vertikal Baku Halaman**:
     1. **Header Banner** (`.dash-banner`) dengan tombol aksi ikon murni 38x38px (`.dash-banner-actions`).
     2. **Summary / Mini-Stats Grid** (`.dash-stat-grid` dengan tepat 4 `.dash-stat-card`).
     3. **Tab Navigasi Baku** (`.periode-nav-wrapper`) jika memiliki sub-halaman/kategori/peran [WAJIB DI ATAS DATATABLE].
     4. **Kontainer Datatable Terpadu Tunggal** (`.card.datatable-card-wrapper#tableDataContainer`):
        - Bagian Atas: `.datatable-toolbar` (Entri, Filter, Reset & Live Search).
        - Bagian Tengah: `.table-responsive-stack` dengan `table.table-pd`.
        - Bagian Bawah: `.datatable-footer` dengan paginasi kustom (`@include('partials.datatable-pagination', ['paginator' => $items])`).
     5. **Modal Dialog** (`z-index: 99999 !important`).

   - **Tab Navigasi Baku (`.periode-nav-wrapper`, ACUAN: DASHBOARD/HAK-AKSES)**:
     Jika modul memiliki sub-tab atau pemisahan kategori/peran data, posisinya **WAJIB DI ATAS DATATABLE**, menggunakan wrapper tab standar dengan representasi ganda (desktop pill tabs + mobile dropdown switcher):
     ```blade
     <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
         {{-- 1. Desktop & Tablet Pill Tabs --}}
         <div class="periode-nav-desktop">
             <a href="{{ route('...', ['tab' => 'kategori_a']) }}"
                 class="periode-nav-tab {{ $activeTab === 'kategori_a' ? 'active' : '' }}">
                 <i class="fas fa-icon"></i> Label Tab A
             </a>
             <a href="{{ route('...', ['tab' => 'kategori_b']) }}"
                 class="periode-nav-tab {{ $activeTab === 'kategori_b' ? 'active' : '' }}">
                 <i class="fas fa-icon"></i> Label Tab B
             </a>
         </div>

         {{-- 2. Mobile Responsive Dropdown Switcher (< 768px) --}}
         <div class="periode-nav-mobile">
             <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                 <label><i class="fas fa-filter text-primary me-1"></i> Pilih Kategori / Tab:</label>
                 <select class="periode-mobile-select form-select"
                     onchange="if(this.value) window.location.href=this.value;"
                     style="font-size: 0.85rem; height: 42px; border-radius: 10px; width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                     <option value="{{ route('...', ['tab' => 'kategori_a']) }}" {{ $activeTab === 'kategori_a' ? 'selected' : '' }}>Label Tab A</option>
                     <option value="{{ route('...', ['tab' => 'kategori_b']) }}" {{ $activeTab === 'kategori_b' ? 'selected' : '' }}>Label Tab B</option>
                 </select>
             </div>
         </div>
     </div>
     ```

   - **Kontainer Datatable Terpadu Global (`.card.datatable-card-wrapper#tableDataContainer`, ACUAN: PESERTA-DIDIK-AKTIF)**:
     Seluruh entri perPage, filter kategori, tombol reset, liveSearch, tabel data, dan paginasi **WAJIB DIGABUNGKAN** ke dalam 1 kartu terpadu tunggal:
     ```blade
     <div class="card datatable-card-wrapper" id="tableDataContainer">
         {{-- 1. Toolbar Header Datatable (Entri hingga Search) --}}
         <div class="datatable-toolbar">
             <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                 <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                     <div class="toolbar-entries">
                         <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                         <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                             @foreach ([10, 15, 25, 50, 100] as $n)
                                 <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                             @endforeach
                         </select>
                         <span>entri</span>
                     </div>

                     {{-- Filter Tambahan (Dropdown .toolbar-filter-select setinggi 38px) --}}
                     @if (!empty($filterOptions))
                         <select id="filterKategori" class="toolbar-filter-select" style="height: 38px;">
                             <option value="">Semua Kategori</option>
                             ...
                         </select>
                     @endif

                     {{-- Tombol Reset Filter: WAJIB ICON-ONLY 38x38px DENGAN TITLE --}}
                     @if (!empty($q) || !empty($filterKategori))
                         <a href="{{ route('...', ['tab' => $activeTab]) }}"
                             class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                             title="Reset filter">
                             <i class="fas fa-undo"></i>
                         </a>
                     @endif
                 </div>

                 <div class="live-search-wrap" style="height: 38px;">
                     <i class="fas fa-search search-icon"></i>
                     <input type="text" id="liveSearch" placeholder="Cari..." value="{{ $q ?? '' }}" autocomplete="off">
                     <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                         <i class="fas fa-times"></i>
                     </button>
                 </div>
             </div>
         </div>

         {{-- 2. Konten Tabel Responsif --}}
         <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
             <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                 ...
             </table>
         </div>

         {{-- 3. Footer Paginasi Terpadu --}}
         @if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->hasPages())
             <div class="datatable-footer">
                 @include('partials.datatable-pagination', ['paginator' => $items])
             </div>
         @endif
     </div>
     ```
                 </button>
             </div>
         </div>
     </div>
     ```
   - **Container Datatable**: Wajib dibungkus kartu khusus responsive-stack:
     ```html
     <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
         <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
             ...
         </table>
     </div>
     ```
   - **Header Kolom Sortable**:
     ```blade
     <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}" data-sort="{{ $key }}" style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
         {{ $label }}
         <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
     </th>
     ```
   - **Kebijakan Tombol Ikon Tanpa Teks Panjang (STRICT - Lihat `.agents/STANDAR_TOMBOL_ICON_DAN_DATATABLE.md`)**:
     - **Header Banner Actions (`.dash-banner-actions`)**: Seluruh tombol aksi header wajib berupa ikon saja berdimensi kotak `38px x 38px`, `border-radius: 8px`, `padding: 0`, dan WAJIB memiliki atribut `title="..."` untuk tooltip. Dilarang menyisipkan teks/span di dalam tombol banner.
     - **Kolom Aksi Baris (`.table-actions`)**: Wajib menggunakan `<div class="table-actions">` dengan tombol ikon berkelas `<button type="button" class="btn-icon" title="...">` atau `<a class="btn-icon" title="...">`. Dilarang menaruh teks tombol seperti "Edit", "Hapus", "Validasi", dll.
     - **Toolbar Datatable**: Tombol reset filter atau aksi pembantu di toolbar wajib icon-only dengan `title="..."`.
   - **Mode Datatable Responsive Mobile (Card-Stack Mode Hemat Ruang)**:
     - Di layar mobile (< 768px), baris tabel `.table-responsive-stack` otomatis bertransformasi menjadi kartu compact:
       1. Kolom nomor urut (`data-label="No"`/`"No."`) otomatis disembunyikan (`display: none !important;`) untuk menghemat baris vertikal.
       2. Sel nama/judul utama diletakkan di puncak sebagai card header dengan pembatas `border-bottom`, tanpa label `::before`.
       3. Sel-sel atribut di dalam kartu dipadatkan dengan padding `3.5px 0` dan pembatas halus `1px dashed`, memangkas ketinggian kartu > 50%.
       4. Kolom aksi diletakkan di footer kartu rata kanan dengan tombol ikon compact `32px x 32px` tanpa teks "AKSI".
   - **Paginasi Baku (DILARANG MENGGUNAKAN `{{ $list->links() }}`)**:
     Dilarang memanggil `{{ $list->links() }}` secara mentah karena merender pagination unstyled bawaan. Wajib merender blok baku `.custom-pagination`:
     ```blade
     @if ($list->hasPages())
         <div class="custom-pagination">
             @if ($list->onFirstPage())
                 <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
             @else
                 <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
             @endif
             @php
                 $cur = $list->currentPage();
                 $last = $list->lastPage();
                 $from = max(1, $cur - 2);
                 $to = min($last, $cur + 2);
             @endphp
             @if ($from > 1)
                 <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                 @if ($from > 2) <span class="page-info">&hellip;</span> @endif
             @endif
             @for ($i = $from; $i <= $to; $i++)
                 <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
             @endfor
             @if ($to < $last)
                 @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                 <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
             @endif
             @if ($list->hasMorePages())
                 <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
             @else
                 <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
             @endif
         </div>
     @endif
     ```
   - **Live Search & Filter Asinkron**:
     Semua aksi pencarian live search (`input`), perubahan perPage, filter dropdown, dan sorting th wajib memanggil `window.refreshLiveTable(url)` agar tabel diperbarui via AJAX tanpa reload halaman.

---

## 3. Arsitektur Model Pengguna & Multi-Role

### A. Model `App\Models\User` & Tabel `pengguna`
Aplikasi **tidak menggunakan tabel `users` standar** untuk data akun operasional. Model `App\Models\User` dikonfigurasi langsung ke tabel `pengguna`:
- **Primary Key**: `pengguna_id` (String / UUID Dapodik), non-incrementing (`public $incrementing = false; keyType = 'string'`).
- **Kolom Utama**: `pengguna_id`, `sekolah_id`, `username`, `nama`, `peran_id_str`, `password`, `alamat`, `no_telepon`, `no_hp`, `ptk_id`, `peserta_didik_id`, `raw_data`.
- **Derivasi Role Dinamis (`$user->role`)**:
  Role tidak disimpan sebagai kolom enum statis, melainkan dievaluasi secara dinamis via accessor `getRoleAttribute()`:
  1. **`admin`**: Jika `peran_id_str` mengandung kata `admin`, `dinas`, `yayasan`, atau `operator`.
  2. **`tendik`**: Jika `peran_id_str` mengandung kata `tendik`, `tenaga kependidikan`, `tata usaha`, `laboran`, `pustakawan`, atau relasi `ptk_id` pada tabel `gtk.jenis_ptk_id_str` merujuk ke non-guru/tendik.
  3. **`guru`**: Jika `peran_id_str` mengandung kata `guru`, `ptk`, atau memiliki `ptk_id` guru/kepala sekolah.
  4. **`peserta_didik`**: Default jika memiliki `peserta_didik_id` atau bukan GTK/Admin.

### B. Mekanisme Sesi Login Web (`AuthController`)
- User dapat login menggunakan: **Username**, **Email**, **NIP / NUPTK / NIK** (untuk GTK), atau **NISN / NIK / NIPD** (untuk Peserta Didik).
- Password diverifikasi dengan `password_verify` (Bcrypt) dengan fallback dukungan password default `Sae12345!`.
- Saat login sukses, seluruh biodata esensial disimpan ke dalam `session(['user' => $userData])`.
- Pengalihan otomatis ke dashboard spesifik role: `/dashboard/{role}` (`admin`, `guru`, `tendik`, `peserta-didik`).

---

## 4. Sistem Otorisasi & Dynamic RBAC (Role & Duty-Based)

Sistem menggunakan otorisasi hybrid dua lapis (Role dasar + Penugasan Tugas Tambahan):

```
┌──────────────────────────────────────────────────────────────┐
│                  Evaluasi Akses Pengguna                     │
│               RolePermission::canAccess($user)               │
└──────────────────────────────┬───────────────────────────────┘
                               │
            ┌──────────────────┴──────────────────┐
            ▼                                     ▼
   [1. Izin Peran Dasar]                [2. Izin Tugas Tambahan]
  Tabel: `role_permissions`             Tabel: `ptk_tugas_tambahan`
  (admin, guru, tendik, siswa)           & `ref_tugas_tambahan`
            │                                     │
            │   (Jika Belum Diizinkan Role)       │
            └─────────────────► ◄─────────────────┘
                               │
                               ▼
               [3. Granular Action Rights]
             can_create, can_read, can_update,
                        can_delete
```

### A. Komponen RBAC
1. **`role_permissions`**: Menyimpan matriks hak akses per role terhadap permission key (`menu_*` dan `fitur_*`), beserta flag granular (`can_create`, `can_read`, `can_update`, `can_delete`).
2. **`ref_tugas_tambahan`**: Daftar master tugas tambahan Dapodik yang memiliki atribut `granted_permissions` (JSON daftar permission key yang otomatis terbuka).
3. **`ptk_tugas_tambahan`**: Penetapan tugas tambahan ke personal GTK / akun pengguna (`user_id`, `ptk_id`, nomor SK, TMT/TST, relasi `rombel_id` untuk Wali Kelas, atau `jurusan_id` untuk Kaprog).
4. **Middleware `permission:{key}` (`CheckRolePermission.php`)**: Memeriksa izin akses secara otomatis sebelum controller dijalankan. Jika ditolak, redirect dengan pesan error atau kirim JSON 403.
5. **Sinkronisasi Otomatis Wali Kelas**: Method `PtkTugasTambahan::syncWaliKelasFromDapodik()` membaca seluruh rombel reguler aktif dari tabel `rombongan_belajar` dan secara otomatis menetapkan tugas tambahan `WALI_KELAS` kepada PTK terkait.

---

## 5. Skema Database Kunci & Isolasi Metadata

Database `db_sae` menggunakan arsitektur mirrored cache & backup table dari Dapodik:

### A. Tabel Pokok Dapodik & Backup
Setiap tabel live memiliki pasangan tabel arsip (`backup_*`) yang secara otomatis mencatat snapshot sebelum data live ditimpa oleh sinkronisasi:
- **`sekolah`** & **`backup_sekolah`**: Identitas satuan pendidikan, NPSN, koordinat, kontak, dan alamat.
- **`gtk`** & **`backup_gtk`**: Data pendidik dan tenaga kependidikan, NUPTK, NIK, NIP, jenis PTK, riwayat pendidikan & kepangkatan.
- **`rombongan_belajar`** & **`backup_rombongan_belajar`**: Rombel reguler dan matpel pilihan, tingkat, kurikulum, semester, wali kelas (`ptk_id`), dan jurusan.
- **`peserta_didik`** & **`backup_peserta_didik`**: Data induk peserta didik, NISN, NIK, NIPD, biodata ortu, dan rombel aktif.
- **`anggota_rombel`** & **`backup_anggota_rombel`**: Relasi foreign key many-to-many peserta didik ke rombel.
- **`pembelajaran`** & **`backup_pembelajaran`**: Rincian mapel per rombel, guru pengampu (`ptk_id`), alokasi JJM.

### B. Tabel Metadata Persisten (Isolasi Sinkronisasi)
- **`jurusan_meta`**: Metadata kompetensi keahlian/jurusan (ID jurusan Dapodik, nama, singkatan, path logo PNG). Terisolasi agar logo tidak hilang saat sinkronisasi Dapodik.
- **`peserta_didik_meta`**: Metadata persisten pasfoto peserta didik (`peserta_didik_id`, `nisn`, `foto_path`, `foto_size`, `foto_width`, `foto_height`). Terisolasi dari tabel `peserta_didik` agar pasfoto Kartu Pelajar Digital tidak terhapus saat feeder melakukan truncate & re-insert data siswa.
- **`settings`**: Konfigurasi global (ID = 1), live API key Dapodik feeder (`api_key`), status backup requirement (`sync_allowed`), `last_sync`, dan versi rilis.

---

## 6. Mekanisme Ingesti Feeder Dapodik

Penerima data sinkronisasi berada di `app/Http/Controllers/Api/FeederReceiverController.php`:
- **Endpoint**: `POST /api/receive-data`
- **Autentikasi**: Header `X-API-Key: <api_key>` atau `Authorization: Bearer <api_key>` (dicocokkan ke `settings.api_key`).
- **Alur Ingesti**:
  1. Validasi API Key & proteksi pencadangan arsip (`sync_allowed`).
  2. Snapshot tabel live ke `backup_*` via direct SQL statement (`INSERT INTO backup_... SELECT ...`).
  3. Batch insert data baru dengan chunking (misal `array_chunk($batch, 200)`).
  4. Auto-generate akun peserta didik di tabel `pengguna` dengan password default.
  5. Update `settings.last_sync = now()` dan kunci kembali status `sync_allowed = false`.

---

## 7. Proteksi Integritas Sistem (Anti-Tamper & Zero-Plaintext)

Aplikasi memiliki proteksi identitas pengembang resmi yang terintegrasi secara **load-bearing**:
- **Engine**: `app/Support/SystemSignature.php`.
- **Zero-Plaintext**: Tidak ada string teks identitas pengembang dalam bentuk plain text di codebase. Data dikonversi ke JSON biner dan di-XOR bitwise dengan key `90` (`0x5A`) menjadi array integer (`RAW_MAP`), divalidasi dengan SHA-256 digest `6115b46465f07a789350ceb97718bb3c7f5bad730a48c1c9d03c3466ae032c70`.
- **Interlocking Guards**:
  1. `app/Providers/AppServiceProvider.php` (Boot Guard): Melempar exception jika verifikasi signature gagal.
  2. `app/Http/Middleware/EnsureInstalled.php` (Request Guard): Menghentikan request dengan HTTP 503 jika signature rusak.
  3. `resources/views/partials/dash-footer.blade.php`: Merender badge hak cipta dinamis.
- **ATURAN**: Dilarang menghapus, membypass, atau memodifikasi mekanisme SystemSignature ini karena akan mengakibatkan crash sistem (500 / 503).

---

## 8. Panduan Operasional Cepat (CLI Laragon)

Jalankan perintah berikut dari direktori `c:\laragon\www\sae` menggunakan PHP Laragon:

```powershell
# 1. Bersihkan seluruh cache aplikasi & route
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize:clear

# 2. Build ulang cache untuk optimasi performa
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan optimize

# 3. Jalankan migrasi database terbaru
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate --force

# 4. Sinkronkan permission modul baru ke matriks RBAC
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan tinker --execute="App\Models\RolePermission::syncAvailablePermissions();"
```