@extends('layouts.dashboard')

@section('title', 'Buku Tamu Pos Keamanan')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Buku Tamu Pos Keamanan (e-Tamu)
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan tamu mandiri via scan QR Code, deteksi otomatis orang tua siswa, dan input manual satpam.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" onclick="openModalQr()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tampilkan QR Code di Layar" aria-label="Tampilkan QR Code di Layar">
                    <i class="fas fa-qrcode"></i>
                </button>
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" onclick="openModalCetakKeamanan()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Cetak Buku Tamu (pilih periode)" aria-label="Cetak Buku Tamu">
                        <i class="fas fa-print"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="btnOpenImportKeamanan"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Import Buku Tamu via Excel" aria-label="Import Buku Tamu via Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="openModalTamu()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Catat Tamu Masuk Manual" aria-label="Catat Tamu Masuk Manual">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-address-book"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['total'] }}</div>
                    <div class="dash-stat-label">Total Kunjungan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['hari_ini'] }}</div>
                    <div class="dash-stat-label">Tamu Hari Ini</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['di_lokasi'] }}</div>
                    <div class="dash-stat-label">Sedang di Lokasi</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['sudah_keluar'] }}</div>
                    <div class="dash-stat-label">Sudah Keluar</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Keamanan Buku Tamu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.keamanan.buku-tamu.index') }}" id="filterForm"
                    style="margin: 0;">
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}"
                                            {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px;">

                            <select name="status" class="toolbar-filter-select" style="height: 38px; min-width: 130px;">
                                <option value="">Semua Status</option>
                                <option value="berada_di_lokasi" {{ $status === 'berada_di_lokasi' ? 'selected' : '' }}>Di
                                    Lokasi
                                </option>
                                <option value="sudah_keluar" {{ $status === 'sudah_keluar' ? 'selected' : '' }}>Sudah
                                    Keluar
                                </option>
                            </select>

                            <select name="jenis_tamu" class="toolbar-filter-select" style="height: 38px; min-width: 140px;">
                                <option value="">Semua Kategori</option>
                                <option value="orang_tua" {{ $jenisTamu === 'orang_tua' ? 'selected' : '' }}>Orang Tua Siswa</option>
                                <option value="kedinasan" {{ $jenisTamu === 'kedinasan' ? 'selected' : '' }}>Kedinasan</option>
                                <option value="umum" {{ $jenisTamu === 'umum' ? 'selected' : '' }}>Umum</option>
                                <option value="alumni" {{ $jenisTamu === 'alumni' ? 'selected' : '' }}>Alumni</option>
                            </select>

                            @if ($search || $status || $tanggal || $jenisTamu)
                                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" aria-label="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchBukuTamu" value="{{ $search }}"
                                placeholder="Cari nama tamu, siswa, nopol..." autocomplete="off">
                            <button type="button" id="clearSearch"
                                class="clear-search {{ !empty($search) ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- 2. Konten Datatable Responsive Stack --}}
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tanggal & Waktu</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Tamu & Kategori</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tujuan & Keperluan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Visitor / Kendaraan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        In: <strong>{{ substr($item->jam_masuk, 0, 5) }}</strong>
                                        @if ($item->jam_keluar)
                                            &bull; Out: <strong>{{ substr($item->jam_keluar, 0, 5) }}</strong>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        @if (!empty($item->foto_tamu))
                                            <div style="position: relative; flex-shrink: 0; cursor: pointer;"
                                                onclick="previewFotoTamu('{{ asset('storage/' . $item->foto_tamu) }}', '{{ addslashes($item->nama_tamu) }}')"
                                                title="Klik untuk perbesar foto tamu">
                                                <img src="{{ asset('storage/' . $item->foto_tamu) }}" alt="Foto {{ $item->nama_tamu }}"
                                                    style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 2px solid rgba(14, 165, 233, 0.4); box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                                <span style="position: absolute; bottom: -2px; right: -2px; background: #0ea5e9; color: #fff; border-radius: 50%; width: 14px; height: 14px; font-size: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-magnifying-glass"></i>
                                                </span>
                                            </div>
                                        @else
                                            <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--text-muted);">
                                                <i class="fas fa-user" style="font-size: 1.1rem; opacity: 0.5;"></i>
                                            </div>
                                        @endif
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 600; color: var(--text-heading); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span>{{ $item->nama_tamu }}</span>
                                                @if ($item->is_mandiri)
                                                    <span class="badge" style="background: rgba(139, 92, 246, 0.12); color: var(--purple, #8b5cf6); font-size: 10px; padding: 2px 6px;">
                                                        <i class="fas fa-qrcode me-1"></i>Mandiri
                                                    </span>
                                                @endif
                                                @if ($item->jenis_tamu === 'orang_tua')
                                                    <span class="badge" style="background: rgba(2, 132, 199, 0.12); color: var(--primary, #0284c7); font-size: 10px; padding: 2px 6px;">
                                                        <i class="fas fa-user-graduate me-1"></i>Orang Tua
                                                    </span>
                                                @elseif ($item->jenis_tamu === 'kedinasan')
                                                    <span class="badge" style="background: rgba(99, 102, 241, 0.12); color: #6366f1; font-size: 10px; padding: 2px 6px;">
                                                        <i class="fas fa-building me-1"></i>Dinas
                                                    </span>
                                                @endif
                                            </div>
                                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                                {{ $item->instansi_asal ?: 'Umum / Pribadi' }}
                                                @if ($item->nomor_kontak)
                                                    &bull; Telp: {{ $item->nomor_kontak }}
                                                @endif
                                            </div>
                                            @if ($item->nama_siswa)
                                                <div style="font-size: 11px; color: var(--primary, #0284c7); margin-top: 3px;">
                                                    <i class="fas fa-child me-1"></i> Siswa: <strong>{{ $item->nama_siswa }}</strong>
                                                    @if ($item->rombel_siswa) ({{ $item->rombel_siswa }}) @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading); font-size: var(--text-base, 0.875rem);">Bertemu:
                                        {{ $item->tujuan_bertemu }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Hal: {{ $item->keperluan }}
                                    </div>
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem);">
                                    @if ($item->nomor_kartu_visitor)
                                        <div><span class="badge"
                                                style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">Kartu:
                                                {{ $item->nomor_kartu_visitor }}</span></div>
                                    @endif
                                    @if ($item->nomor_polisi_kendaraan)
                                        <div style="color: var(--text-muted); margin-top: 2px;">Nopol:
                                            {{ $item->nomor_polisi_kendaraan }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($item->status === 'berada_di_lokasi')
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.3);">Di
                                            Lokasi</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.3);">Sudah
                                            Keluar</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($item->status === 'berada_di_lokasi')
                                            <form action="{{ route('dashboard.keamanan.buku-tamu.checkout', $item->id) }}"
                                                method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Checkout Tamu Keluar"
                                                    style="color: var(--warning, #f59e0b);">
                                                    <i class="fas fa-arrow-right-from-bracket"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon" title="Edit"
                                                onclick="editTamu({{ json_encode($item) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.keamanan.buku-tamu.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete" data-name="{{ $item->nama_tamu }}">
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
                                <td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-user-friends"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Belum ada catatan tamu.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 3. Footer Paginasi Terpadu Datatable --}}
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        {{-- Modal Tambah / Edit Tamu (Input Manual Satpam) --}}
        <div id="modalTamu"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 660px; margin: 20px; max-height: 90vh; overflow-y: auto; padding: 20px; border-radius: var(--radius-xl, 16px);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <div>
                        <h3 id="modalTamuTitle" style="font-size: var(--text-lg, 1.15rem); font-weight: 800; margin: 0; color: var(--text-heading);">
                            Catat Tamu Masuk (Manual)
                        </h3>
                        <p style="margin: 2px 0 0; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                            Pilih kategori dan lengkapi data kunjungan tamu pos keamanan
                        </p>
                    </div>
                    <button type="button" onclick="closeModalTamu()"
                        style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <form id="formTamu" method="POST" action="{{ route('dashboard.keamanan.buku-tamu.store') }}"
                    enctype="multipart/form-data"
                    data-store-url="{{ route('dashboard.keamanan.buku-tamu.store') }}"
                    data-update-url="{{ url('/dashboard/keamanan/buku-tamu') }}/:id">
                    @csrf
                    <input type="hidden" name="_method" id="tamuMethod" value="POST">
                    <input type="hidden" name="peserta_didik_id" id="tamu_pd_id" value="">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Tanggal <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal" id="tamu_tanggal" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="form-label">Jam Masuk <span style="color:red;">*</span></label>
                            <input type="time" name="jam_masuk" id="tamu_jam_masuk" class="form-control"
                                value="{{ date('H:i') }}" required>
                        </div>
                    </div>

                    {{-- Langkah 1: Kategori Tamu (Kartu Pilihan seperti Formulir Mandiri) --}}
                    <div class="category-step" id="manualCategoryStep" style="margin-bottom: 12px; padding: 12px;">
                        <div class="category-step-head" style="margin-bottom: 10px; gap: 8px;">
                            <span class="category-step-num" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                            <div>
                                <div class="category-step-title" style="font-size: 0.875rem;">Pilih Kategori Tamu</div>
                                <div class="category-step-sub" style="font-size: 0.7rem;">Pilih kategori kunjungan tamu</div>
                            </div>
                            <span class="category-step-tag" style="font-size: 0.6rem; padding: 2px 8px;">KATEGORI</span>
                        </div>

                        <div class="etamu-category-grid" id="manualCategoryGrid" style="gap: 8px;">
                            <label class="category-option active" data-type="orang_tua" style="padding: 10px 6px;">
                                <input type="radio" name="jenis_tamu" value="orang_tua" checked>
                                <span class="category-icon text-primary" style="font-size: 1.25rem;"><i class="fas fa-user-graduate"></i></span>
                                <span class="category-label" style="font-size: 0.72rem;">Orang Tua / Wali</span>
                                <span class="category-hint" style="font-size: 0.62rem;">Wali murid</span>
                            </label>
                            <label class="category-option" data-type="kedinasan" style="padding: 10px 6px;">
                                <input type="radio" name="jenis_tamu" value="kedinasan">
                                <span class="category-icon text-info" style="font-size: 1.25rem;"><i class="fas fa-building"></i></span>
                                <span class="category-label" style="font-size: 0.72rem;">Kedinasan</span>
                                <span class="category-hint" style="font-size: 0.62rem;">Instansi / dinas</span>
                            </label>
                            <label class="category-option" data-type="umum" style="padding: 10px 6px;">
                                <input type="radio" name="jenis_tamu" value="umum">
                                <span class="category-icon text-success" style="font-size: 1.25rem;"><i class="fas fa-user-group"></i></span>
                                <span class="category-label" style="font-size: 0.72rem;">Umum</span>
                                <span class="category-hint" style="font-size: 0.62rem;">Masyarakat umum</span>
                            </label>
                            <label class="category-option" data-type="alumni" style="padding: 10px 6px;">
                                <input type="radio" name="jenis_tamu" value="alumni">
                                <span class="category-icon text-warning" style="font-size: 1.25rem;"><i class="fas fa-user-tie"></i></span>
                                <span class="category-label" style="font-size: 0.72rem;">Alumni</span>
                                <span class="category-hint" style="font-size: 0.62rem;">Siswa Lulus</span>
                            </label>
                        </div>
                    </div>

                    {{-- Section Khusus Tamu Orang Tua (Deteksi Otomatis Siswa) --}}
                    <div id="manualSectionOrtu" class="student-lookup-box" style="margin-bottom: 12px; padding: 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                            <span style="font-weight: 700; font-size: var(--text-xs, 0.78rem); color: var(--primary, #0ea5e9);">
                                <i class="fas fa-id-card me-1"></i> Deteksi Otomatis Siswa
                            </span>
                            <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">Ketik nama siswa, NISN, atau NIK untuk memilih</span>
                        </div>

                        {{-- Search Input with Floating Results Dropdown --}}
                        <div class="student-search-wrapper" id="manualStudentSearchWrapper" style="position: relative; margin-bottom: 6px;">
                            <label class="form-label" for="manual_cari_siswa" style="font-size: 0.75rem;">Cari Data Siswa</label>
                            <div class="student-search-input-wrap" style="position: relative;">
                                <input type="text" id="manual_cari_siswa" class="form-control"
                                    placeholder="Ketik nama siswa, NISN, atau NIK..." autocomplete="off">
                                <span id="manualStudentSearchSpinner" class="student-search-spinner" style="display: none; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--primary, #0ea5e9);">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                            <div id="manualStudentDropdownList" class="student-dropdown-results" style="display: none;"></div>
                        </div>

                        {{-- Selected Student Card (Shown upon selection) --}}
                        <div id="manualStudentSelectedBox" style="display: none; margin-top: 8px;">
                            <div class="student-selected-card">
                                <div class="student-selected-avatar">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="student-selected-info">
                                    <div class="student-selected-name" id="manualSelectedStudentNama">-</div>
                                    <div class="student-selected-sub" id="manualSelectedStudentDetail">-</div>
                                </div>
                                <button type="button" class="btn-clear-student" id="manualBtnClearStudent" title="Ganti Siswa">
                                    <i class="fas fa-times me-1"></i> Ganti
                                </button>
                            </div>
                        </div>

                        {{-- Hidden storage for student data in satpam form --}}
                        <input type="hidden" name="nama_siswa" id="tamu_nama_siswa">
                        <input type="hidden" name="rombel_siswa" id="tamu_rombel_siswa">
                        <input type="hidden" name="nisn_siswa" id="manual_nisn_siswa">
                        <input type="hidden" name="nik_tamu" id="manual_nik_ortu">

                        {{-- Toggle manual input if student is not found --}}
                        <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: var(--text-2xs, 0.72rem);">
                            <span id="manualStudentSearchHint" style="color: var(--text-muted);">Pilih salah satu siswa dari daftar hasil pencarian.</span>
                            <button type="button" id="btnToggleManualModalStudent" style="background: none; border: none; color: var(--primary, #0ea5e9); cursor: pointer; text-decoration: underline; padding: 0;">
                                Tidak menemukan siswa? Ketik manual
                            </button>
                        </div>

                        <div id="manualStudentInputWrap" style="display: none; margin-top: 8px; border-top: 1px dashed var(--border-color, rgba(255,255,255,0.1)); padding-top: 8px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <div>
                                    <label class="form-label" style="font-size: 0.72rem;">Nama Siswa</label>
                                    <input type="text" id="manual_custom_nama_siswa" class="form-control" placeholder="Nama Putra/Putri">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 0.72rem;">Kelas / Rombel</label>
                                    <input type="text" id="manual_custom_rombel_siswa" class="form-control" placeholder="Kelas Siswa">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Identitas Tamu --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label" for="tamu_nama">Nama Lengkap Tamu <span style="color:red;">*</span></label>
                            <input type="text" name="nama_tamu" id="tamu_nama" class="form-control"
                                placeholder="Ketik nama lengkap..." required>
                        </div>
                        <div>
                            <label class="form-label" for="tamu_kontak">Nomor WhatsApp / HP <small class="text-muted">(Opsional)</small></label>
                            <input type="tel" name="nomor_kontak" id="tamu_kontak" class="form-control"
                                placeholder="08xxxxxxxxxx (opsional)" inputmode="tel">
                        </div>
                    </div>

                    {{-- Instansi Asal Dropdown + Custom --}}
                    <div id="manualGroupInstansi" style="margin-bottom: 12px; display: none;">
                        <label class="form-label" for="manual_instansi_select">Instansi / Lembaga Asal</label>
                        <select id="manual_instansi_select" class="form-control">
                            <option value="">-- Pilih Instansi / Asal Tamu --</option>
                            <option value="Dinas Pendidikan">Dinas Pendidikan</option>
                            <option value="Kementerian Agama">Kementerian Agama</option>
                            <option value="Pemerintah Daerah / Dinas">Pemerintah Daerah / Dinas</option>
                            <option value="Perusahaan / Mitra Industri (DUDI)">Perusahaan / Mitra Industri (DUDI)</option>
                            <option value="Lembaga / Yayasan Pendidikan">Lembaga / Yayasan Pendidikan</option>
                            <option value="Perguruan Tinggi / Universitas">Perguruan Tinggi / Universitas</option>
                            <option value="Puskesmas / Layanan Kesehatan">Puskesmas / Layanan Kesehatan</option>
                            <option value="Kepolisian / Koramil / Aparat">Kepolisian / Koramil / Aparat</option>
                            <option value="Masyarakat Umum">Masyarakat Umum</option>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <div id="wrapManualInstansiLainnya" style="display: none; margin-top: 6px;">
                            <input type="text" id="manual_instansi_lainnya_input" class="form-control" placeholder="Ketik nama instansi / lembaga asal...">
                        </div>
                        <input type="hidden" name="instansi_asal" id="tamu_instansi">
                    </div>

                    {{-- Khusus Tamu Kedinasan: NIP & Jabatan (Opsional) --}}
                    <div id="manualKedinasanIdentitas" style="display: none; margin-bottom: 12px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="form-label" for="manual_nip_tamu">NIP (Opsional)</label>
                                <input type="text" name="nip_tamu" id="manual_nip_tamu" class="form-control"
                                    placeholder="18 digit NIP..." maxlength="30" inputmode="numeric">
                            </div>
                            <div>
                                <label class="form-label" for="manual_jabatan_tamu">Jabatan (Opsional)</label>
                                <input type="text" name="jabatan_tamu" id="manual_jabatan_tamu" class="form-control"
                                    placeholder="Contoh: Pengawas, Kasi Kurikulum...">
                            </div>
                        </div>
                    </div>

                    {{-- Khusus Tamu Kedinasan: Konfirmasi Surat Tugas --}}
                    <div id="manualSectionKedinasan" class="student-lookup-box" style="display: none; background: rgba(6, 182, 212, 0.05); border-color: rgba(6, 182, 212, 0.25); margin-bottom: 12px; padding: 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 4px;">
                            <span style="font-weight: 700; font-size: var(--text-xs, 0.78rem); color: var(--accent, #06b6d4);">
                                <i class="fas fa-file-lines me-1"></i> Surat Tugas Kedinasan
                            </span>
                            <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">Pilih jika membawa surat tugas resmi</span>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label class="form-label">Apakah Tersedia Surat Tugas?</label>
                            <div style="display: flex; gap: 12px; margin-top: 4px;">
                                <label class="surat-tugas-radio" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: var(--text-xs, 0.78rem); font-weight: 600; padding: 6px 14px; border: 1px solid var(--border-glass, rgba(255,255,255,0.15)); border-radius: var(--radius-md, 8px); background: var(--card-bg, rgba(255,255,255,0.03));">
                                    <input type="radio" name="ada_surat_tugas" value="1" id="manualRadioSuratYa" onchange="toggleManualSuratTugas(true)" style="accent-color: var(--primary, #0ea5e9);">
                                    <span><i class="fas fa-circle-check text-success me-1"></i> Ya, Ada</span>
                                </label>
                                <label class="surat-tugas-radio" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: var(--text-xs, 0.78rem); font-weight: 600; padding: 6px 14px; border: 1px solid var(--border-glass, rgba(255,255,255,0.15)); border-radius: var(--radius-md, 8px); background: var(--card-bg, rgba(255,255,255,0.03));">
                                    <input type="radio" name="ada_surat_tugas" value="0" id="manualRadioSuratTidak" checked onchange="toggleManualSuratTugas(false)" style="accent-color: var(--primary, #0ea5e9);">
                                    <span><i class="fas fa-circle-xmark text-muted me-1"></i> Tidak Ada</span>
                                </label>
                            </div>
                        </div>

                        <div id="manualBoxSuratDetail" style="display: none; padding-top: 10px; border-top: 1px dashed rgba(6, 182, 212, 0.25);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label class="form-label" for="manual_nomor_surat">Nomor Surat Tugas</label>
                                    <input type="text" name="nomor_surat_tugas" id="manual_nomor_surat" class="form-control"
                                        placeholder="Contoh: 800/123/Disdik/2026">
                                </div>
                                <div>
                                    <label class="form-label" for="manual_tanggal_surat">Tanggal Surat Tugas</label>
                                    <input type="date" name="tanggal_surat_tugas" id="manual_tanggal_surat" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Dropdown Tujuan Bertemu & Keperluan --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label" for="manual_tujuan_select">Tujuan Bertemu <span style="color:red;">*</span></label>
                            <select id="manual_tujuan_select" class="form-control" required>
                                <option value="">-- Pilih Tujuan Bertemu --</option>
                                <optgroup label="Pimpinan & Manajemen">
                                    <option value="Kepala Sekolah">Kepala Sekolah</option>
                                    <option value="Wakil Kepala Sekolah (Kurikulum)">Wakil Kepala Sekolah (Kurikulum)</option>
                                    <option value="Wakil Kepala Sekolah (Kesiswaan)">Wakil Kepala Sekolah (Kesiswaan)</option>
                                    <option value="Wakil Kepala Sekolah (Sarpras)">Wakil Kepala Sekolah (Sarpras)</option>
                                    <option value="Wakil Kepala Sekolah (Humas)">Wakil Kepala Sekolah (Humas)</option>
                                </optgroup>
                                <optgroup label="Guru & Bimbingan">
                                    <option value="Wali Kelas">Wali Kelas</option>
                                    <option value="Guru Bimbingan Konseling (BK)">Guru Bimbingan Konseling (BK)</option>
                                    <option value="Guru Mata Pelajaran">Guru Mata Pelajaran</option>
                                </optgroup>
                                <optgroup label="Layanan Tata Usaha & Fasilitas">
                                    <option value="Bagian Tata Usaha (TU)">Bagian Tata Usaha (TU)</option>
                                    <option value="Staf Keuangan / Kasir">Staf Keuangan / Kasir</option>
                                    <option value="Petugas Perpustakaan">Petugas Perpustakaan</option>
                                    <option value="Petugas UKS">Petugas UKS</option>
                                    <option value="Pos Satpam / Keamanan">Pos Satpam / Keamanan</option>
                                    @foreach ($daftarRuang as $rng)
                                        <option value="Ruang {{ $rng->nama_ruang }}">Ruang {{ $rng->nama_ruang }}</option>
                                    @endforeach
                                </optgroup>
                                <option value="Lainnya">Lainnya (Ketik Manual)</option>
                            </select>
                            <div id="wrapManualTujuanLainnya" style="display: none; margin-top: 6px;">
                                <input type="text" id="manual_tujuan_lainnya_input" class="form-control" placeholder="Ketik tujuan bertemu lainnya...">
                            </div>
                            <input type="hidden" name="tujuan_bertemu" id="tamu_tujuan" required>
                        </div>

                        <div>
                            <label class="form-label" for="manual_keperluan_select">Keperluan <span style="color:red;">*</span></label>
                            <select id="manual_keperluan_select" class="form-control" required>
                                <option value="">-- Pilih Keperluan --</option>
                                <optgroup label="Urusan Siswa & Akademik">
                                    <option value="Konsultasi Perkembangan Belajar Siswa">Konsultasi Perkembangan Belajar Siswa</option>
                                    <option value="Pengambilan Rapot / Legalisir Ijazah">Pengambilan Rapot / Legalisir Ijazah</option>
                                    <option value="Mengantar Keperluan / Berkas Siswa">Mengantar Keperluan / Berkas Siswa</option>
                                    <option value="Pemanggilan BK / Urusan Disiplin">Pemanggilan BK / Urusan Disiplin</option>
                                    <option value="Pengurusan Mutasi / Pindah Siswa">Pengurusan Mutasi / Pindah Siswa</option>
                                </optgroup>
                                <optgroup label="Kedinasan & Kemitraan">
                                    <option value="Koordinasi Dinas / Undangan Resmi">Koordinasi Dinas / Undangan Resmi</option>
                                    <option value="Kunjungan Monev / Pengawasan">Kunjungan Monev / Pengawasan</option>
                                    <option value="Penawaran Kerjasama / Kemitraan">Penawaran Kerjasama / Kemitraan</option>
                                    <option value="Urusan Administrasi / Pembayaran">Urusan Administrasi / Pembayaran</option>
                                    <option value="Penyerahan Dokumen / Berkas Surat">Penyerahan Dokumen / Berkas Surat</option>
                                </optgroup>
                                <optgroup label="Umum & Alumni">
                                    <option value="Silaturahmi / Temu Guru">Silaturahmi / Temu Guru</option>
                                    <option value="Urusan Alumni">Urusan Alumni</option>
                                </optgroup>
                                <option value="Lainnya">Lainnya (Ketik Manual)</option>
                            </select>
                            <div id="wrapManualKeperluanLainnya" style="display: none; margin-top: 6px;">
                                <input type="text" id="manual_keperluan_lainnya_input" class="form-control" placeholder="Ketik keperluan kunjungan lainnya...">
                            </div>
                            <input type="hidden" name="keperluan" id="tamu_keperluan" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label" for="tamu_kartu">Nomor Kartu Visitor (Fisik)</label>
                            <input type="text" name="nomor_kartu_visitor" id="tamu_kartu" class="form-control"
                                placeholder="Contoh: VIS-01 / V-05">
                        </div>
                        <div>
                            <label class="form-label" for="tamu_nopol">Nomor Polisi Kendaraan (Opsional)</label>
                            <input type="text" name="nomor_polisi_kendaraan" id="tamu_nopol" class="form-control"
                                placeholder="Contoh: B 1234 ABC">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label" for="tamu_status">Status Kunjungan</label>
                            <select name="status" id="tamu_status" class="form-control">
                                <option value="berada_di_lokasi">Berada di Lokasi</option>
                                <option value="sudah_keluar">Sudah Keluar</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="tamu_jam_keluar">Jam Keluar</label>
                            <input type="time" name="jam_keluar" id="tamu_jam_keluar" class="form-control">
                        </div>
                    </div>

                    {{-- Bukti Foto Kunjungan Tamu (Kamera Langsung & Unggah Gambar) --}}
                    <div class="photo-capture-box" style="margin-bottom: 16px; border: 1px dashed var(--border-glass, rgba(255,255,255,0.2)); border-radius: var(--radius-lg, 12px); padding: 14px; background: rgba(255,255,255,0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px;">
                            <span style="font-weight: 700; font-size: var(--text-xs, 0.78rem); color: var(--text-heading);">
                                <i class="fas fa-camera text-primary me-1"></i> Bukti Foto Tamu (Opsional)
                            </span>
                            <span style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 999px;">
                                Kompresi Otomatis
                            </span>
                        </div>

                        {{-- Mode Switcher --}}
                        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                            <button type="button" class="btn btn-outline btn-photo-tab active" id="modalBtnTabKamera" style="flex: 1; padding: 6px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <i class="fas fa-video"></i> Kamera Satpam
                            </button>
                            <button type="button" class="btn btn-outline btn-photo-tab" id="modalBtnTabUnggah" style="flex: 1; padding: 6px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <i class="fas fa-file-image"></i> Unggah Berkas
                            </button>
                        </div>

                        {{-- Panel Kamera --}}
                        <div id="modalPanelKamera" style="display: block;">
                            <div style="position: relative; width: 100%; max-width: 340px; margin: 0 auto; aspect-ratio: 4/3; background: #000; border-radius: var(--radius-md, 8px); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <video id="modalWebcamVideo" playsinline autoplay muted style="width: 100%; height: 100%; object-fit: cover; display: none;"></video>
                                <div id="modalCameraPlaceholder" style="text-align: center; color: var(--text-muted); padding: 16px;">
                                    <i class="fas fa-video-slash" style="font-size: 1.8rem; margin-bottom: 6px; opacity: 0.5;"></i>
                                    <div style="font-size: var(--text-xs, 0.75rem);">Kamera web belum aktif</div>
                                    <button type="button" id="modalBtnStartCamera" class="btn btn-primary" style="margin-top: 8px; padding: 5px 12px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-camera"></i> Buka Kamera
                                    </button>
                                </div>
                            </div>

                            <div id="modalCameraControls" style="display: none; justify-content: center; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
                                <button type="button" id="modalBtnSwitchCamera" class="btn btn-secondary" style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-camera-rotate"></i> Putar
                                </button>
                                <button type="button" id="modalBtnCapturePhoto" class="btn btn-primary" style="padding: 5px 14px; font-size: var(--text-xs, 0.75rem); font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-circle-dot"></i> Ambil Foto
                                </button>
                                <button type="button" id="modalBtnStopCamera" class="btn btn-secondary" style="padding: 5px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-stop"></i> Tutup
                                </button>
                            </div>
                        </div>

                        {{-- Panel Unggah --}}
                        <div id="modalPanelUnggah" style="display: none;">
                            <div style="border: 1px dashed var(--border-glass, rgba(255,255,255,0.25)); border-radius: var(--radius-md, 8px); padding: 14px; text-align: center; background: rgba(255,255,255,0.02);">
                                <i class="fas fa-cloud-arrow-up" style="font-size: 1.5rem; color: var(--primary, #0ea5e9); margin-bottom: 4px;"></i>
                                <div style="font-size: var(--text-xs, 0.78rem); font-weight: 600; color: var(--text-heading); margin-bottom: 2px;">
                                    Pilih Berkas Foto Tamu
                                </div>
                                <div style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted); margin-bottom: 8px;">
                                    JPG/PNG, kompresi otomatis &lt; 200 KB
                                </div>
                                <input type="file" id="modalInputFileFoto" accept="image/*" class="form-control" style="font-size: var(--text-xs, 0.75rem); max-width: 260px; margin: 0 auto;">
                            </div>
                        </div>

                        {{-- Preview Box Foto --}}
                        <div id="modalPhotoPreviewWrap" style="display: none; margin-top: 10px; text-align: center;">
                            <div style="position: relative; display: inline-block; max-width: 220px; border-radius: var(--radius-md, 8px); overflow: hidden; border: 2px solid #10b981; box-shadow: 0 4px 10px rgba(16,185,129,0.2);">
                                <img id="modalImgPhotoPreview" src="" alt="Preview Foto Tamu" style="width: 100%; height: auto; display: block; object-fit: cover;">
                                <span style="position: absolute; top: 4px; left: 4px; background: rgba(16, 185, 129, 0.9); color: #fff; font-size: 9px; font-weight: 700; padding: 2px 5px; border-radius: 4px;">
                                    <i class="fas fa-check"></i> Foto Siap
                                </span>
                            </div>
                            <div style="margin-top: 6px;">
                                <button type="button" id="modalBtnHapusFoto" class="btn btn-outline" style="color: var(--danger, #ef4444); border-color: rgba(239,68,68,0.3); font-size: var(--text-2xs, 0.72rem); padding: 3px 10px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-trash"></i> Hapus / Ganti Foto
                                </button>
                            </div>
                        </div>

                        {{-- Hidden base64 input & hapus foto flag --}}
                        <input type="hidden" name="foto_tamu_data" id="modal_foto_tamu_data" value="">
                        <input type="hidden" name="hapus_foto_tamu" id="modal_hapus_foto_tamu" value="0">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModalTamu()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Popup QR Code (Untuk di-scan langsung di layar pos satpam) --}}
        <div id="modalQrCode"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 440px; margin: 20px; text-align: center; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; margin: 0; color: var(--text-heading);">
                        <i class="fas fa-qrcode text-primary me-1"></i> Scan QR e-Tamu Mandiri
                    </h3>
                    <button type="button" onclick="closeModalQr()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <div style="background: #ffffff; border: 2px solid var(--primary, #0284c7); border-radius: 16px; padding: 14px; display: inline-block; margin-bottom: 12px;">
                    {!! $qrSvg !!}
                </div>
                <div style="font-size: 11px; font-family: monospace; color: var(--primary, #0284c7); margin-bottom: 8px; word-break: break-all;">
                    {{ $publicFormUrl }}
                </div>
                <p style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin: 0 0 16px;">
                    Arahkan kamera ponsel tamu ke kode QR di atas untuk mengisi buku tamu mandiri tanpa perlu antre di meja pos.
                </p>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <a href="{{ $publicFormUrl }}" target="_blank" class="btn btn-outline" style="font-size: var(--text-xs, 0.75rem);">
                        <i class="fas fa-external-link-alt me-1"></i> Buka Link
                    </a>
                    <a href="{{ route('dashboard.keamanan.buku-tamu.cetak-qr') }}" target="_blank" class="btn btn-primary" style="font-size: var(--text-xs, 0.75rem);">
                        <i class="fas fa-print me-1"></i> Cetak Poster
                    </a>
                </div>
            </div>
        </div>

        {{-- Modal Popup Preview Foto Tamu --}}
        <div id="modalFotoPreview"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.75); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
            <div class="card" style="width: 100%; max-width: 500px; padding: 20px; text-align: center; border-radius: var(--radius-xl, 16px); background: var(--card-bg, #1e293b);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 14px;">
                    <h3 id="modalFotoTamuNama" style="font-size: var(--text-base, 1rem); font-weight: 700; margin: 0; color: var(--text-heading);">
                        Foto Tamu
                    </h3>
                    <button type="button" onclick="closeModalFotoPreview()"
                        style="background: none; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <div style="max-height: 70vh; overflow: hidden; border-radius: var(--radius-md, 8px); background: #000; display: flex; align-items: center; justify-content: center;">
                    <img id="modalFotoPreviewImg" src="" alt="Foto Tamu" style="max-width: 100%; max-height: 70vh; object-fit: contain; display: block;">
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import Buku Tamu via Excel --}}
    @include('dashboard.keamanan.partials.modal-import', [
        'modulKode' => 'tamu',
        'modulJudul' => 'Buku Tamu',
        'templateRoute' => route('dashboard.keamanan.buku-tamu.template'),
        'importRoute' => route('dashboard.keamanan.buku-tamu.import'),
    ])

    {{-- Modal Cetak periode Buku Tamu --}}
    @include('dashboard.keamanan.partials.modal-cetak', [
        'cetakRoute' => route('dashboard.keamanan.buku-tamu.cetak'),
        'modulJudul' => 'Buku Tamu',
    ])
@endsection

@push('styles')
    <style>
        .btn-photo-tab.active {
            background: var(--primary, #0ea5e9) !important;
            color: #fff !important;
            border-color: var(--primary, #0ea5e9) !important;
        }
        .category-step {
            border: 2px solid rgba(14, 165, 233, 0.45);
            background: linear-gradient(180deg, rgba(14, 165, 233, 0.12), rgba(14, 165, 233, 0.03));
            border-radius: var(--radius-xl, 16px);
            padding: 16px;
            margin-bottom: 1.25rem;
            position: relative;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.15);
        }
        .category-step.answered {
            border-color: rgba(16, 185, 129, 0.5);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.10), rgba(16, 185, 129, 0.02));
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.12);
        }
        .category-step-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .category-step-num {
            flex: 0 0 auto;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        }
        .category-step-title {
            font-weight: 800;
            font-size: var(--text-md, 1rem);
            color: var(--text-heading);
            line-height: 1.2;
        }
        .category-step-sub {
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted);
            margin-top: 2px;
        }
        .category-step-tag {
            margin-left: auto;
            align-self: flex-start;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #fff;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            padding: 4px 10px;
            border-radius: var(--radius-full, 999px);
            white-space: nowrap;
        }
        .etamu-category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        @media (min-width: 520px) {
            .etamu-category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        .category-option {
            cursor: pointer;
            border: 2px solid var(--border-glass, rgba(255, 255, 255, 0.12));
            border-radius: var(--radius-lg, 12px);
            padding: 14px 10px;
            text-align: center;
            transition: all 0.18s ease;
            background: var(--card-bg, rgba(255, 255, 255, 0.04));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            user-select: none;
        }
        .category-option:hover {
            border-color: var(--primary, #0ea5e9);
            transform: translateY(-2px);
            background: rgba(14, 165, 233, 0.08);
        }
        .category-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .category-option.active {
            border-color: var(--primary, #0ea5e9);
            background: rgba(14, 165, 233, 0.16);
            color: var(--primary, #0ea5e9);
            font-weight: 700;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(14, 165, 233, 0.25);
        }
        .category-option.active::after {
            content: "\f058";
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "FontAwesome";
            font-weight: 900;
            position: absolute;
            top: 6px;
            right: 8px;
            font-size: 0.95rem;
            color: var(--primary, #0ea5e9);
        }
        .category-icon {
            font-size: 1.6rem;
            line-height: 1;
            margin-bottom: 2px;
        }
        .category-label {
            font-size: var(--text-xs, 0.8rem);
            line-height: 1.2;
            font-weight: 700;
            color: var(--text-heading);
        }
        .category-option.active .category-label {
            color: var(--primary, #0ea5e9);
        }
        .category-hint {
            font-size: 0.68rem;
            color: var(--text-muted);
            line-height: 1.1;
        }
        .student-lookup-box {
            background: rgba(14, 165, 233, 0.05);
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: var(--radius-lg, 12px);
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .student-search-wrapper {
            position: relative;
            margin-bottom: 6px;
        }
        .student-search-input-wrap {
            position: relative;
        }
        .student-search-spinner {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary, #0ea5e9);
        }
        .student-dropdown-results {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: var(--card-bg, #1e293b);
            border: 1.5px solid var(--primary, #0ea5e9);
            border-radius: var(--radius-lg, 12px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35);
            z-index: 1000;
            max-height: 240px;
            overflow-y: auto;
        }
        .student-search-item {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-glass, rgba(255, 255, 255, 0.08));
            cursor: pointer;
            transition: background 0.15s ease;
            text-align: left;
            width: 100%;
            display: block;
            background: none;
            border-left: none;
            border-right: none;
            border-top: none;
            color: inherit;
        }
        .student-search-item:last-child {
            border-bottom: none;
        }
        .student-search-item:hover, .student-search-item:focus {
            background: rgba(14, 165, 233, 0.15);
            outline: none;
        }
        .student-item-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: var(--text-sm, 0.85rem);
            color: var(--text-heading, #fff);
        }
        .student-item-meta {
            font-size: var(--text-xs, 0.72rem);
            color: var(--text-muted, #94a3b8);
            margin-top: 2px;
        }
        .student-selected-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
            border: 1.5px solid rgba(16, 185, 129, 0.4);
            border-radius: var(--radius-lg, 12px);
            padding: 10px 14px;
        }
        .student-selected-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .student-selected-info {
            flex: 1;
        }
        .student-selected-name {
            font-weight: 800;
            font-size: var(--text-sm, 0.875rem);
            color: var(--text-heading, #fff);
        }
        .student-selected-sub {
            font-size: var(--text-xs, 0.72rem);
            color: var(--text-muted, #94a3b8);
        }
        .btn-clear-student {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: none;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s ease;
        }
        .btn-clear-student:hover {
            background: rgba(239, 68, 68, 0.25);
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.BUKU_TAMU_CONFIG = {
            checkStudentUrl: "{{ route('tamu.check-student') }}"
        };
    </script>
    <script
        src="{{ asset('js/keamanan-buku-tamu.js') }}?v={{ file_exists(public_path('js/keamanan-buku-tamu.js')) ? filemtime(public_path('js/keamanan-buku-tamu.js')) : time() }}">
    </script>
@endpush
