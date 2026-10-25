# STANDAR BAKU ARSITEKTUR UI, TOMBOL IKON, TAB & DATATABLE SAE

Panduan ini adalah **standar definitif, acuan tunggal, dan aturan wajib** bagi seluruh AI Agent dan pengembang yang membuat modul baru, memodifikasi, memperbaiki bug, maupun menambahkan fitur di sistem **SAE (Sistem Aplikasi Edukasi)**.

> **DUA ACUAN RESMI SISTEM**:
> 1. **Acuan Layout, Stat Card, Toolbar, Datatable, Table-Actions & Pagination**: [resources/views/dashboard/peserta-didik-aktif.blade.php](resources/views/dashboard/peserta-didik-aktif.blade.php) (`manajemen-data/peserta-didik-aktif`).
> 2. **Acuan Tab Navigasi Sub-Modul (Desktop Pill Tabs & Mobile Dropdown Switcher)**: [resources/views/dashboard/hak-akses.blade.php](resources/views/dashboard/hak-akses.blade.php) (`dashboard/hak-akses`).

---

## 1. Aturan Mutlak Tombol Ikon (Strict Icon-Only Rule)

1. **DILARANG KERAS MENGGUNAKAN TEKS PADA TOMBOL BER-IKON**:
   - Baik saat membuat modul baru, memperbaiki fitur lama, maupun menambahkan tombol fitur baru:
     - Tombol di Header Banner (`.dash-banner-actions`) **WAJIB IKON SAJA**.
     - Tombol di Toolbar Datatable (Reset filter, ekspor/cetak batch) **WAJIB IKON SAJA**.
     - Tombol di Kolom Aksi Baris Tabel (`.table-actions`) **WAJIB IKON SAJA**.
   - Dilarang keras menyisipkan teks atau tag `<span>` seperti `<span>Tambah ...</span>`, `<span>Catat ...</span>`, `<span>Kembali</span>`, `<span>Validasi</span>`, `<span>Cetak</span>`, dll.
2. **Wajib Memiliki Atribut `title="..."`**:
   - Setiap tombol ikon wajib menyertakan atribut `title="..."` dengan deskripsi jelas sebagai tooltip dan standar aksesibilitas browser/screen reader.
3. **Dimensi Baku Tombol Ikon**:
   - **Header Banner & Toolbar**: Kotak seragam `width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;`.
   - **Baris Tabel**: Kelas baku `.btn-icon` (misal `<button type="button" class="btn-icon" title="Edit Data"><i class="fas fa-pen-to-square"></i></button>`).
4. **Pengecualian yang Diizinkan**:
   - Tombol konfirmasi di dalam footer modal dialog ("Simpan", "Batal").
   - Selektor status presensi kode huruf tunggal ("H", "T", "I", "S", "A", "P").
   - Angka nomor halaman pada pagination ("1", "2", "3"...).

---

## 2. Urutan Vertikal Baku Halaman (Hierarki Layout)

Seluruh halaman dashboard SAE wajib mengikuti urutan vertikal komponen berikut:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 1. BANNER HEADER (.dash-banner)                                       │
│    Judul modul (h2 + ikon), deskripsi (p), & tombol aksi (.dash-banner-actions)│
│    -> Tombol wajib icon-only 38x38px dengan title="..."                │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 2. FLASH MESSAGES (session('success'), session('error'), $errors)      │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 3. REKAPITULASI STATISTIK (.dash-stat-grid)                            │
│    Tepat 4 Card Genap per tab/halaman aktif (grid-template-columns)    │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 4. TAB NAVIGASI BAKU (.periode-nav-wrapper) [WAJIB DI ATAS DATATABLE]  │
│    (Jika modul memiliki sub-tab / kategori / peran / alur bertahap)    │
│    Desktop: .periode-nav-desktop | Mobile: .periode-nav-mobile select │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
┌──────────────────────────────────▼─────────────────────────────────────┐
│ 5. UNIFIED GLOBAL DATATABLE CARD (.card.datatable-card-wrapper)        │
│    Kontainer Tunggal Terpadu (#tableDataContainer):                    │
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
│ 6. MODAL FORM DIALOG (z-index: 99999 !important)                       │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Komponen Baku & Kode Acuan Master

### A. Header Banner & Actions
```blade
<div class="dash-banner">
    <div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
            <i class="fas fa-folder-tree text-primary me-2"></i> Judul Modul
        </h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
            Deskripsi singkat modul dan peruntukannya.
        </p>
    </div>
    <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        @if ($canCreate)
            <button type="button" class="btn btn-primary" id="btnOpenCreateModal"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                title="Tambah Data Baru">
                <i class="fas fa-plus"></i>
            </button>
        @endif
        <a href="{{ route('...') }}" target="_blank" class="btn btn-outline"
            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; text-decoration: none;"
            title="Cetak Dokumen Resmi">
            <i class="fas fa-print"></i>
        </a>
    </div>
</div>
```

### B. Stat Grid (Tepat 4 Card Genap)
```blade
<div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['total'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Total Data</div>
        </div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['aktif'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Status Aktif</div>
        </div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['proses'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Dalam Proses</div>
        </div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['selesai'], 0, ',', '.') }}</div>
            <div class="dash-stat-label">Selesai</div>
        </div>
    </div>
</div>
```

### C. Tab Navigasi Baku SAE (Acuan: dashboard/hak-akses)
> **PENTING**: Jika halaman memiliki tab navigasi, posisinya **WAJIB DI ATAS DATATABLE**!
```blade
<div class="periode-nav-wrapper" style="margin-bottom: 20px;">
    {{-- 1. Desktop & Tablet Tabs --}}
    <div class="periode-nav-desktop">
        @foreach ($tabItems as $tabKey => $tabMeta)
            <a href="{{ route('...', ['tab' => $tabKey]) }}"
                class="periode-nav-tab {{ $activeTab === $tabKey ? 'active' : '' }}">
                <i class="fas {{ $tabMeta['icon'] }}"></i> {{ $tabMeta['label'] }} ({{ $tabMeta['count'] ?? 0 }})
            </a>
        @endforeach
    </div>

    {{-- 2. Mobile Responsive Dropdown Switcher (< 768px) --}}
    <div class="periode-nav-mobile">
        <div class="periode-mobile-select-box" style="margin-bottom: 0;">
            <label><i class="fas fa-filter text-primary me-1"></i> Pilih Kategori / Tab:</label>
            <select class="periode-mobile-select form-select"
                onchange="if(this.value) window.location.href=this.value;"
                style="font-size: 0.85rem; height: 42px; border-radius: 10px; width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                @foreach ($tabItems as $tabKey => $tabMeta)
                    <option value="{{ route('...', ['tab' => $tabKey]) }}" {{ $activeTab === $tabKey ? 'selected' : '' }}>
                        {{ $tabMeta['label'] }} ({{ $tabMeta['count'] ?? 0 }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
```

### D. Kontainer Datatable Terpadu Global (Acuan: peserta-didik-aktif)
> **STANDAR GLOBAL TERPADU**: Toolbar (entri hingga search), tabel data, dan footer paginasi **DIGABUNGKAN KE DALAM 1 CARD TUNGGAL** `#tableDataContainer` (Dilarang memisahkan toolbar atau pagination di luar card):

```blade
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- 1. Toolbar Header: Entri, Filter, Reset & Live Search -->
    <div class="datatable-toolbar">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                {{-- Filter Kategori / Relasi Tambahan --}}
                @if (!empty($filterOptions))
                    <select id="filterKategori" name="kategori" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Kategori</option>
                        @foreach ($filterOptions as $optVal => $optLabel)
                            <option value="{{ $optVal }}" {{ ($kategori ?? '') === $optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Tombol Reset Filter (Icon-Only 38x38px) --}}
                @if (!empty($q) || !empty($kategori))
                    <a href="{{ route('...') }}" class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                        title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            {{-- Live Search Input (Tinggi 38px) --}}
            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari..." value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02); text-align: left;">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No.</th>
                    <th class="sortable-th {{ ($sort ?? '') === 'nama' ? 'sorted' : '' }}" data-sort="nama" style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Nama Data
                        <span class="sort-icon">{!! ($sort ?? '') === 'nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                        <td data-label="No." style="padding: 14px 18px; text-align: center; font-weight: 700; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $idx + 1 }}
                        </td>
                        <td data-label="Nama Data" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                            {{ $item->nama }}
                        </td>
                        <td data-label="Keterangan" style="padding: 14px 18px; font-size: 0.84rem; color: var(--text-muted);">
                            {{ $item->keterangan ?? '-' }}
                        </td>
                        <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                            <span class="badge badge-success" style="font-size: 0.72rem; padding: 2px 7px;">Aktif</span>
                        </td>
                        <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                            <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon" data-id="{{ $item->id }}" title="Edit Data" style="color: var(--primary);">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('...', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->nama }}" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" title="Hapus Data" style="color: #ef4444;">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Belum ada data tersedia pada kriteria ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 3. Datatable Footer Pagination (Terintegrasi di dalam Card Datatable) -->
    @if ($list instanceof \Illuminate\Pagination\LengthAwarePaginator && $list->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $list])
        </div>
    @endif
</div>
```
            @empty
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                        <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                        <div>Belum ada data tersedia pada kriteria ini.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
```

### F. Paginasi Kustom Baku
```blade
@if ($list instanceof \Illuminate\Pagination\LengthAwarePaginator && $list->hasPages())
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

---

## 4. Karakteristik Datatable Responsive Mobile (Card-Stack Mode Hemat Ruang)

Pada viewport mobile (< 768px), container `.table-responsive-stack` secara otomatis merender setiap baris tabel (`tr`) menjadi **kartu modular compact** yang hemat ruang:

1. **Penyembunyian Nomor Urut (`No.`)**:
   - Kolom nomor urut (`data-label="No"` / `data-label="No."`) disembunyikan otomatis (`display: none !important`) di mobile karena pagination sudah memberikan navigasi halaman dan nomor urut memboroskan 1 baris per kartu.
2. **Item Judul / Nama Utama Sebagai Header Kartu**:
   - Kolom nama/judul (`.cell-pd-nama`, `data-label="Nama Lengkap"`, `data-label="Nama Data"`, dsb.) diletakkan di puncak kartu dengan `font-weight: 700`, `font-size: 0.88rem`, dan garis bawah pemisah (`border-bottom`). Label `::before` pada judul disembunyikan agar bersih.
3. **Kepadatan Baris / Tight Vertical Padding**:
   - Sel-sel data di dalam kartu diberi padding kompak `3.5px 0` dengan garis pembatas halus (`1px dashed rgba(255,255,255,0.05)`). Ini memangkas tinggi kartu sebesar **lebih dari 50%**, sehingga layar HP dapat menampilkan 3–4 data sekaligus tanpa terasa sesak.
4. **Footer Aksi Baris Rapi**:
   - Kolom aksi (`data-label="Aksi"` / `.cell-pd-aksi`) diletakkan di bagian bawah kartu dengan garis pembatas atas, tanpa label teks "AKSI", dan tombol ikon kompak `32px x 32px`.
5. **Pasfoto & Badge Compact**:
   - Avatar pasfoto peserta didik/GTK berukuran `36px x 36px` dengan sudut membulat 8px.
   - Badge status dan kategori berukuran `font-size: 0.68rem; padding: 2px 6px;` dengan `white-space: nowrap !important` agar teks badge tidak terpotong per huruf.

---

## 5. Checklist Wajib Sebelum Finalisasi Kode

Setiap AI Agent atau developer yang menyelesaikan pekerjaan di modul SAE wajib memvalidasi checklist ini:
- [ ] Tombol header banner **icon-only** berdimensi `38px x 38px` dengan atribut `title="..."`.
- [ ] Tombol aksi baris datatable (`.table-actions`) **icon-only** kelas `.btn-icon` dengan atribut `title="..."`.
- [ ] Mode mobile card-stack rapi, tidak memboroskan ruang vertikal, dan kolom `No.` tidak menjadi baris terpisah.
- [ ] Tidak ada tag `<script>` inline di Blade view; seluruh interaksi dipisah ke `public/js/{nama_modul}.js`.
- [ ] Menggunakan SweetAlert2 (`Swal.fire`) untuk konfirmasi hapus/reset data (dilarang `confirm()` browser bawaan).
- [ ] Menggunakan wrapper `.periode-nav-wrapper` dengan `.periode-nav-desktop` dan `.periode-nav-mobile` jika memiliki tab.
- [ ] Modal dialog memiliki style `z-index: 99999 !important`.
- [ ] Filter dan live search memanggil `window.refreshLiveTable(url)` tanpa reload halaman penuh.
- [ ] Jalankan `php artisan view:clear` setelah melakukan perubahan template.

