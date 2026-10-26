# PANDUAN STANDAR BAKU PEMBUATAN MODUL & FITUR SAE

Dokumen panduan arsitektur antarmuka dan interaktivitas frontend-backend sistem **SAE (Sistem Aplikasi Edukasi)**. Seluruh modul dan fitur baru wajib mengikuti panduan ini agar tampilan konsisten, rapi, responsif, dan bebas gangguan (*zero-friction*).

---

## 1. Hierarki Tata Letak Baku Halaman (Urutan Vertikal)

Seluruh halaman modul SAE mengikuti urutan penempatan komponen dari atas ke bawah sebagai berikut:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 1. BANNER HEADER (.dash-banner)                                       │
│    Judul modul, deskripsi ringkas, dan tombol aksi utama (ikon saja)   │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 2. REKAPITULASI STATISTIK (.dash-stat-grid)                            │
│    Tepat 4 Card Genap per tab aktif (simetris & responsif 2 kolom)     │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 3. NAVIGASI SUB-TAB / SCOPE / VIEW (.periode-nav-wrapper)              │
│    WAJIB BERADA DI ATAS DATATABLE (di bawah card statistik)            │
│    Desktop: .periode-nav-desktop | Mobile: .periode-nav-mobile select │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 4. KONTAINER DATATABLE TERPADU TUNGGAL (.card.datatable-card-wrapper)  │
│    Id #tableDataContainer:                                             │
│    ┌─────────────────────────────────────────────────────────────┐     │
│    │ a. Toolbar Header (.datatable-toolbar): Entri s/d Search    │     │
│    ├─────────────────────────────────────────────────────────────┤     │
│    │ b. Konten Tabel (.table-responsive-stack): table.table-pd   │     │
│    ├─────────────────────────────────────────────────────────────┤     │
│    │ c. Footer Paginasi (.datatable-footer): .custom-pagination  │     │
│    └─────────────────────────────────────────────────────────────┘     │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 5. MODAL FORM DIALOG (z-index: 99999 !important)                       │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Standar Desain Kartu Rekapitulasi (Stat Grid)

1. **Jumlah Kartu Harus Genap (Tepat 4 Kartu)**:
   - Hindari jumlah ganjil (seperti 3 atau 5 kartu) yang menyisakan ruang kosong asimetris pada layar desktop maupun mobile.
   - Susun 4 indikator paling relevan sesuai tab yang sedang aktif.
   - Contoh distribusi 4 card per tab:
     - **Tab Aktif**: Total Siswa, Laki-Laki, Perempuan, Total Rombel.
     - **Tab Tidak Aktif**: Total Keluar, Mutasi, Dikeluarkan (DO), Mengundurkan Diri & Lainnya.
     - **Tab Validasi Berkas**: Menunggu Validasi, Ada Berkas Ditolak, Belum Lengkap, Lengkap & Valid.
     - **Tab Usulan Perubahan**: Menunggu Review, Disetujui (Siap Dapodik), Selesai di Dapodik, Ditolak.
     - **Tab Alumni**: Total Alumni, Alumni Laki-Laki, Alumni Perempuan, Total Angkatan.
2. **Layout CSS Grid Baku**:
   ```html
   <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
       <div class="dash-stat-card">
           <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
               <i class="fas fa-icon"></i>
           </div>
           <div class="dash-stat-info">
               <div class="dash-stat-value">{{ number_format($val) }}</div>
               <div class="dash-stat-label">Label Indikator</div>
           </div>
       </div>
       <!-- ... 3 card lainnya ... -->
   </div>
   ```

---

## 3. Standar Tata Letak Responsif Mobile (< 768px)

### A. Tombol Aksi Banner (.dash-banner-actions)
- **Aturan Wajib (STRICT)**:
  1. **SELURUH TOMBOL BER-IKON WAJIB ICON-ONLY TANPA TEKS**:
     Dilarang keras menyisipkan teks atau tag `<span>` di dalam tombol banner maupun tombol aksi baris tabel (`.table-actions`).
     ```html
     <!-- BENAR (Icon-Only + title tooltip) -->
     <button type="button" class="btn btn-primary" id="btnTambahAgenda"
         style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
         title="Tambah Agenda Baru">
         <i class="fas fa-plus"></i>
     </button>

     <!-- SALAH (DILARANG KERAS) -->
     <button type="button" class="btn btn-primary" id="btnTambahAgenda">
         <i class="fas fa-plus"></i> <span>Tambah Agenda</span>
     </button>
     ```
  2. **Wajib Memiliki Atribut `title="..."`**: Setiap tombol wajib memiliki tooltip deskriptif agar fungsionalitasnya jelas saat disentuh/dihantam kursor.
  3. **Wadah Banner Actions**:
     Gunakan pembungkus seragam:
     ```html
     <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
         <a href="..." class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Cetak Dokumen">
             <i class="fas fa-print"></i>
         </a>
         <button type="button" class="btn btn-primary" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Tambah Data">
             <i class="fas fa-plus"></i>
         </button>
     </div>
     ```

### B. Pembungkus Tab / Scope Navigasi Baku SAE (Acuan: dashboard/hak-akses)
- **Standar Baku**:
  Gunakan `.periode-nav-wrapper` dengan dua representasi responsif:
  1. `.periode-nav-desktop` (tampil di desktop/tablet, tersembunyi di mobile).
  2. `.periode-nav-mobile` (dropdown switcher yang tampil otomatis di mobile `< 768px`).
  ```html
  <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
      {{-- 1. Desktop & Tablet Pill Tabs --}}
      <div class="periode-nav-desktop">
          <a href="{{ route('...', ['tab' => 'kategori_a']) }}"
              class="periode-nav-tab {{ $activeTab === 'kategori_a' ? 'active' : '' }}">
              <i class="fas fa-users"></i> Kategori A ({{ $countA }})
          </a>
          <a href="{{ route('...', ['tab' => 'kategori_b']) }}"
              class="periode-nav-tab {{ $activeTab === 'kategori_b' ? 'active' : '' }}">
              <i class="fas fa-file-lines"></i> Kategori B ({{ $countB }})
          </a>
      </div>

      {{-- 2. Mobile Responsive Dropdown Switcher --}}
      <div class="periode-nav-mobile">
          <div class="periode-mobile-select-box" style="margin-bottom: 0;">
              <label><i class="fas fa-filter text-primary me-1"></i> Pilih Kategori / Tab:</label>
              <select class="periode-mobile-select form-select"
                  onchange="if(this.value) window.location.href=this.value;"
                  style="font-size: 0.85rem; height: 42px; border-radius: 10px; width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                  <option value="{{ route('...', ['tab' => 'kategori_a']) }}" {{ $activeTab === 'kategori_a' ? 'selected' : '' }}>
                      Kategori A ({{ $countA }})
                  </option>
                  <option value="{{ route('...', ['tab' => 'kategori_b']) }}" {{ $activeTab === 'kategori_b' ? 'selected' : '' }}>
                      Kategori B ({{ $countB }})
                  </option>
              </select>
          </div>
      </div>
  </div>
  ```

### C. Navigasi Harian & Kategori (Horizontal Touch Scroll)
- Untuk pilihan hari (Senin–Jumat) atau kategori yang panjang di perangkat mobile, gunakan strip horizontal yang dapat digeser:
  ```css
  .jadwal-days-strip {
      display: flex;
      width: 100%;
      overflow-x: auto;
      flex-wrap: nowrap;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 4px;
      gap: 6px;
  }
  .jadwal-days-strip .btn-day-tab {
      flex-shrink: 0;
  }
  ```

---

## 4. Standar Baku Live Search & AJAX Table Swapping

### A. Protokol Anti-Kursor Melompat & Anti-Reload
Ketika pengguna sedang mengetik di kolom pencarian `#liveSearch`, sistem **DILARANG**:
1. Mereset posisi kursor dengan `setSelectionRange()` ke nilai sebelum `fetch()` dimulai.
2. Memaksa reload penuh (`window.location.href`) jika request asinkron dibatalkan atau mengalami *network abort*.
3. Menimpa isi teks yang sedang diketik pengguna dengan respons lama server.

### B. Implementasi Baku di JavaScript Engine (`dashboard.js`)
1. **Gunakan AbortController untuk Membatalkan Request Usang**:
   ```javascript
   let liveTableAbortController = null;

   window.refreshLiveTable = async function (url) {
       if (liveTableAbortController) {
           try { liveTableAbortController.abort(); } catch (e) {}
       }
       liveTableAbortController = new AbortController();
       const currentSignal = liveTableAbortController.signal;

       try {
           const response = await fetch(targetUrl, { signal: currentSignal, ... });
           // ... pertukaran DOM tabel ...
       } catch (err) {
           if (err.name === 'AbortError' || currentSignal.aborted) {
               return; // Batalkan secara hening, JANGAN reload halaman!
           }
       }
   };
   ```

2. **Proteksi Kursor Pengguna**:
   ```javascript
   const activeEl = document.activeElement;
   const isInputActive = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA');

   // Saat pertukaran DOM selesai:
   if (activeEl && document.body.contains(activeEl)) {
       // Jika elemen masih fokus dan pengguna sedang mengetik:
       // JANGAN panggil setSelectionRange! Biarkan kursor tetap di posisi ketikan saat ini.
       if (document.activeElement !== activeEl) {
           activeEl.focus();
           const curLen = activeEl.value ? activeEl.value.length : 0;
           activeEl.setSelectionRange(curLen, curLen);
       }
   }
   ```

3. **Interval Debounce Wajib**:
   - Seluruh input live search wajib menggunakan interval debounce **450 ms** (bukan 250–300 ms).
   - Menekan tombol `Enter` wajib mengeksekusi pencarian seketika tanpa menunggu debounce timer.

4. **Isolasi Script Modul**:
   - Jangan menyertakan script dari modul lain yang menargetkan ID `#liveSearch` tanpa pengaman rute.
   - Pasang *guard* pada awal script jika script dipakai bersama:
     ```javascript
     if (window.location.pathname.includes('/modul-lain/')) return;
     ```

---

## 5. Logika Pengurutan Default Data (FIFO untuk Antrean)

1. **Modul Pengesahan, Verifikasi Berkas, dan Usulan Perubahan Data**:
   - **Wajib menggunakan prinsip FIFO (First In, First Out)**: Dokumen atau usulan yang pertama kali masuk harus diproses terlebih dahulu.
   - **Hirarki Prioritas Status**:
     1. Status `menunggu` / belum diproses diletakkan di paling atas.
     2. Status bermasalah / `tidak_valid` / ditolak di urutan berikutnya.
     3. Status tuntas / `lengkap` / `sudah_ke_dapodik` di urutan paling bawah.
   - **Klausul SQL Baku**:
     ```php
     $query->orderByRaw("CASE status
         WHEN 'menunggu' THEN 1
         WHEN 'tidak_valid' THEN 2
         WHEN 'disetujui' THEN 3
         WHEN 'sudah_ke_dapodik' THEN 4
         ELSE 5
     END ASC")
     ->orderBy('created_at', 'asc');
     ```
2. **Modul Direktori Master Data (Siswa, Guru, Tendik, Rombel)**:
   - Default diurutkan secara alfabetis nama case-insensitive:
     ```php
     $query->orderByRaw("LOWER(TRIM(nama)) asc")->orderBy('nisn', 'asc');
     ```

---

## 6. Standar Komponen Datatable Terpadu Baku SAE

1. **Kontainer Tunggal Terpadu**:
   `<div class="card datatable-card-wrapper" id="tableDataContainer">`
   Menampung toolbar entri s/d search di header (`.datatable-toolbar`), tabel data di tengah (`.table-responsive-stack`), dan paginasi di footer (`.datatable-footer`). Dilarang memisahkan toolbar atau pagination di luar kartu tabel.
2. **Tag Tabel**:
   `<table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">`
3. **Header Kolom Sortable**:
   `<th class="sortable-th {{ $sort === 'nama' ? 'sorted' : '' }}" data-sort="nama">`
4. **Thumbnail Pasfoto Siswa/Guru**:
   Menggunakan rasio kotak sudut melengkung 10px, background conic checkered, dan pembungkus fallback icon.
5. **Tombol Aksi**:
   Menggunakan kelas `.table-actions` dengan elemen ikon murni `<button type="button" class="btn-icon" title="...">` (dilarang teks).
6. **Paginasi**:
   Wajib menggunakan markup kustom terintegrasi `@include('partials.datatable-pagination', ['paginator' => $items])` di dalam `.datatable-footer`. Dilarang menampilkan `{{ $list->links() }}` mentah.

---

## 7. Checklist Verifikasi Sebelum Selesai

- [ ] Posisi Navigasi Sub-Tab / View Switcher berada tepat di bawah Card Rekapitulasi Statistik.
- [ ] Card rekap berjumlah genap (4 kartu) dan angkanya disesuaikan per tab aktif.
- [ ] Tombol pada banner header di layar mobile (< 768px) tidak meregang menjadi baris kosong bertumpuk.
- [ ] Tab cakupan dan tombol navigasi hari/bulan tidak hilang di tampilan mobile.
- [ ] Mengetik di kolom pencarian tidak memicu reload halaman penuh.
- [ ] Kursor tidak terlempar ke belakang atau melompat posisi saat hasil pencarian tabel dimuat.
- [ ] Request fetch lama otomatis dibatalkan jika pengguna terus mengetik tanpa jeda.
- [ ] Paginasi, sort header, dan filter dropdown berfungsi mulus via `window.refreshLiveTable`.
- [ ] Cache tampilan telah dibersihkan (`php artisan view:clear`).
