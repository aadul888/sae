@extends('layouts.dashboard')

@section('title', 'Katalog Koleksi Buku - Perpustakaan')

@section('content')
<div class="content-wrapper">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-book"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Katalog Koleksi Buku &amp; Bahan Pustaka
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Manajemen data inventaris buku perpustakaan, klasifikasi DDC, ISBN, dan ketersediaan eksemplar.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" onclick="openModalBuku()" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;" title="Tambah Koleksi Buku">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Koleksi Buku</span>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                <i class="fas fa-book-bookmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_judul'] }}</div>
                <div class="dash-stat-label">Total Judul Buku</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: #6366f1;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_eksemplar'] }}</div>
                <div class="dash-stat-label">Total Eksemplar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['tersedia'] }}</div>
                <div class="dash-stat-label">Eksemplar Tersedia</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-book-reader"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['dipinjam'] }}</div>
                <div class="dash-stat-label">Sedang Dipinjam</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.perpustakaan.koleksi.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari judul buku, kode, penulis, penerbit, ISBN..." style="width: 100%;">
            </div>
            <div style="min-width: 180px;">
                <select name="kategori" class="form-control" style="width: 100%;">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode &amp; ISBN</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Buku</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Penulis &amp; Penerbit</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kategori &amp; DDC</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Stok Tersedia</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Lokasi Rak</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <span style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: 0.85rem;">{{ $item->kode_buku }}</span>
                            @if ($item->isbn)
                                <div style="font-size: 0.75rem; color: var(--text-muted);">ISBN: {{ $item->isbn }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $item->judul }}</div>
                            @if ($item->tahun_terbit)
                                <div style="font-size: 0.78rem; color: var(--text-muted);">Tahun: {{ $item->tahun_terbit }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.85rem;">
                            <div style="color: var(--text-heading);">{{ $item->penulis ?: '—' }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">{{ $item->penerbit ?: '—' }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);">
                                {{ $item->kategori }}
                            </span>
                            @if ($item->klasifikasi_ddc)
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">DDC: {{ $item->klasifikasi_ddc }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <span style="font-weight: 700; color: {{ $item->eksemplar_tersedia > 0 ? '#10b981' : '#ef4444' }}; font-size: 1rem;">
                                {{ $item->eksemplar_tersedia }}
                            </span>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">/ {{ $item->jumlah_eksemplar }} Eks</span>
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.85rem; color: var(--text-heading);">
                            {{ $item->lokasi_rak ?: '—' }}
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                @if ($canUpdate)
                                    <button type="button" class="btn-icon" title="Edit" onclick="editBuku({{ json_encode($item) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form action="{{ route('dashboard.perpustakaan.koleksi.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->judul }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-book-open" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Belum ada koleksi buku di katalog.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Baku Pagination --}}
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
</div>

{{-- Modal Tambah / Edit Koleksi Buku --}}
<div id="modalBuku" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 id="modalBukuTitle" style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--text-heading);">Tambah Koleksi Buku</h3>
            <button type="button" onclick="closeModalBuku()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="formBuku" method="POST" action="{{ route('dashboard.perpustakaan.koleksi.store') }}"
              data-store-url="{{ route('dashboard.perpustakaan.koleksi.store') }}"
              data-update-url="{{ url('/dashboard/perpustakaan/koleksi') }}/:id">
            @csrf
            <input type="hidden" name="_method" id="bukuMethod" value="POST">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Kode Buku <span style="color:red;">*</span></label>
                    <input type="text" name="kode_buku" id="buku_kode" class="form-control" placeholder="Contoh: BK-001" required>
                </div>
                <div>
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" id="buku_isbn" class="form-control" placeholder="978-xxxxxxxx">
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label class="form-label">Judul Buku <span style="color:red;">*</span></label>
                <input type="text" name="judul" id="buku_judul" class="form-control" placeholder="Judul lengkap buku" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Penulis / Pengarang</label>
                    <input type="text" name="penulis" id="buku_penulis" class="form-control" placeholder="Nama penulis">
                </div>
                <div>
                    <label class="form-label">Penerbit</label>
                    <input type="text" name="penerbit" id="buku_penerbit" class="form-control" placeholder="Nama penerbit">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="form-label">Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" id="buku_tahun" class="form-control" placeholder="Contoh: 2024">
                </div>
                <div>
                    <label class="form-label">Klasifikasi DDC</label>
                    <input type="text" name="klasifikasi_ddc" id="buku_ddc" class="form-control" placeholder="Contoh: 005.1 (Ilmu Komputer)">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label class="form-label">Kategori</label>
                    <select name="kategori" id="buku_kategori" class="form-control">
                        <option value="Buku Pelajaran">Buku Pelajaran</option>
                        <option value="Fiksi">Fiksi / Novel</option>
                        <option value="Non-Fiksi">Non-Fiksi</option>
                        <option value="Referensi">Referensi / Ensiklopedia</option>
                        <option value="Majalah">Majalah / Jurnal</option>
                        <option value="Umum" selected>Umum</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Jumlah Eksemplar <span style="color:red;">*</span></label>
                    <input type="number" name="jumlah_eksemplar" id="buku_eksemplar" class="form-control" min="1" value="1" required>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Lokasi Rak / Penempatan</label>
                <input type="text" name="lokasi_rak" id="buku_rak" class="form-control" placeholder="Contoh: Rak A-02, Baris 3">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModalBuku()">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Buku</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/perpus-koleksi.js') }}"></script>
@endpush
