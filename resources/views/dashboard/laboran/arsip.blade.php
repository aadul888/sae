@extends('layouts.dashboard')

@section('title', 'Arsip Dokumen Laboratorium — SAE')
@section('dash_title', 'Arsip Dokumen')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- Banner (Tupoksi #7) --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-box-archive"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Arsip Dokumen Laboratorium
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Bukti fisik Tupoksi #7 — ordner digital BA kerusakan, kartu peminjaman, &amp; dokumen lain.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.arsip.cetak', request()->query()) }}" class="btn btn-outline"
                    title="Cetak Daftar Arsip Dokumen (A4)" aria-label="Cetak Daftar Arsip Dokumen">
                    <i class="fas fa-print"></i>
                </a>
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnArsipMassal"
                        title="Arsipkan Massal dari Modul Lain" aria-label="Arsipkan Massal dari Modul Lain">
                        <i class="fas fa-layer-group" style="color: var(--purple, #8b5cf6);"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenArsip"
                        title="Tambah Arsip Manual" aria-label="Tambah Arsip Manual">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-box-archive"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Arsip</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statAktif, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-folder-minus"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statInaktif, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Inaktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-tags"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statKategori, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Kategori Ordner</div>
                </div>
            </div>
        </div>

        {{-- Tab: Arsip / Peta Ordner --}}
        <div class="periode-nav-wrapper" style="margin-bottom: var(--space-7);">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.laboran.arsip.index', ['tab' => 'arsip']) }}"
                    class="periode-nav-tab {{ $tab === 'arsip' ? 'active' : '' }}">
                    <i class="fas fa-folder"></i> Daftar Arsip ({{ $statTotal }})
                </a>
                <a href="{{ route('dashboard.laboran.arsip.index', ['tab' => 'indeks']) }}"
                    class="periode-nav-tab {{ $tab === 'indeks' ? 'active' : '' }}">
                    <i class="fas fa-sitemap"></i> Peta Ordner ({{ $statKategori }})
                </a>
                <a href="{{ route('dashboard.laboran.arsip.index', ['tab' => 'riwayat']) }}"
                    class="periode-nav-tab {{ $tab === 'riwayat' ? 'active' : '' }}">
                    <i class="fas fa-clock-rotate-left"></i> Riwayat Cetak
                </a>
            </div>
            <div class="periode-nav-mobile">
                <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                    <label><i class="fas fa-sliders text-primary me-1"></i> Pilih Tampilan:</label>
                    <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;">
                        <option value="{{ route('dashboard.laboran.arsip.index', ['tab' => 'arsip']) }}" {{ $tab === 'arsip' ? 'selected' : '' }}>Daftar Arsip ({{ $statTotal }})</option>
                        <option value="{{ route('dashboard.laboran.arsip.index', ['tab' => 'indeks']) }}" {{ $tab === 'indeks' ? 'selected' : '' }}>Peta Ordner ({{ $statKategori }})</option>
                        <option value="{{ route('dashboard.laboran.arsip.index', ['tab' => 'riwayat']) }}" {{ $tab === 'riwayat' ? 'selected' : '' }}>Riwayat Cetak</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($tab === 'indeks')
            {{-- Peta Ordner --}}
            <div class="card" style="padding: var(--space-9);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-2) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-sitemap text-primary"></i> Peta Ordner Arsip
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-7) 0;">
                    Ringkasan jumlah dokumen per kategori ordner — acuan penyusunan ordner fisik.
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-5);">
                    @forelse ($indeks as $idx)
                        <a href="{{ route('dashboard.laboran.arsip.index', ['tab' => 'arsip', 'kategori' => $idx->kategori]) }}"
                            style="display: block; padding: var(--space-6); border-radius: var(--radius-lg); background: var(--bg-hover); border: 1px solid var(--border-color); text-decoration: none; color: var(--text-color);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); margin-bottom: var(--space-3);">
                                <span style="width: 34px; height: 34px; border-radius: var(--radius-md); background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6); display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-folder"></i>
                                </span>
                                <span class="badge badge-primary" style="font-size: var(--text-sm); font-weight: var(--font-bold);">{{ $idx->jumlah }}</span>
                            </div>
                            <div style="font-size: var(--text-sm); font-weight: var(--font-bold);">{{ $kategoriList[$idx->kategori] ?? ucfirst($idx->kategori) }}</div>
                            @if ($idx->terakhir)
                                <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">
                                    Terakhir: {{ \Carbon\Carbon::parse($idx->terakhir)->translatedFormat('d M Y') }}
                                </div>
                            @endif
                        </a>
                    @empty
                        <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">Belum ada arsip tersimpan.</p>
                    @endforelse
                </div>
            </div>
        @elseif ($tab === 'riwayat')
            {{-- Riwayat Cetak Dokumen (jejak bukti supervisi) --}}
            <div class="card" style="padding: var(--space-9);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-2) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-clock-rotate-left text-primary"></i> Riwayat Cetak Dokumen
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-7) 0;">
                    Setiap dokumen yang dicetak dari modul Laboratorium otomatis tercatat di sini dan
                    didaftarkan sebagai arsip digital &mdash; bukti fisik supervisi dapat ditelusuri kapan saja.
                </p>

                <div class="table-responsive">
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th style="width: 140px;">Nomor Cetak</th>
                                <th>Dokumen</th>
                                <th style="width: 90px;">Versi</th>
                                <th style="width: 130px;">Periode</th>
                                <th style="width: 70px;">Baris</th>
                                <th style="width: 140px;">Petugas</th>
                                <th style="width: 130px;">Waktu Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayat as $i => $r)
                                <tr>
                                    <td class="center font-mono">{{ $i + 1 }}</td>
                                    <td class="center font-mono">{{ $r->nomor_cetak ?: '—' }}</td>
                                    <td>
                                        <strong>{{ $r->judul_dokumen ?: $r->jenis_dokumen }}</strong>
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">
                                            {{ $r->jenis_dokumen }} &bull; A4 {{ ucfirst((string) $r->orientasi) }}
                                            @if ($r->kaprog_nama)
                                                &bull; Kaprog: {{ $r->kaprog_nama }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="center">{{ ucfirst((string) $r->mode) }}</td>
                                    <td>{{ $r->periode_label ?: '—' }}</td>
                                    <td class="center">{{ (int) $r->jumlah_baris }}</td>
                                    <td>{{ $r->petugas_nama ?: '—' }}</td>
                                    <td class="center">
                                        {{ $r->created_at ? \Carbon\Carbon::parse($r->created_at)->translatedFormat('d M Y H:i') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="center" style="padding: 16px; color: var(--text-muted);">
                                        <em>Belum ada riwayat cetak pada cakupan jurusan ini.</em>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- Daftar Arsip --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.laboran.arsip.index') }}" style="margin: 0;">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                    <span>entri</span>
                                </div>

                                <select name="kategori" id="filterKategori" class="toolbar-filter-select" style="height: 38px;" title="Filter Kategori">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($kategoriList as $k => $v)
                                        <option value="{{ $k }}" {{ $kategoriFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>

                                <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                    <option value="">Semua Status</option>
                                    @foreach ($statusList as $k => $v)
                                        <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>

                                @if ($search || $kategoriFilter || $statusFilter)
                                    <a href="{{ route('dashboard.laboran.arsip.index') }}" class="btn btn-outline"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                        title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                                @endif
                            </div>

                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nama dokumen, kode, atau lokasi ordner..." autocomplete="off">
                                <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Kode / Tanggal</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nama Dokumen</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kategori Ordner</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $a)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Kode" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $a->kode_arsip }}</div>
                                        @if ($a->tanggal_dokumen)
                                            <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($a->tanggal_dokumen)->translatedFormat('d M Y') }}</div>
                                        @endif
                                        @if ($a->lokasi_fisik)
                                            <div style="font-size: var(--text-2xs); color: var(--text-muted);"><i class="fas fa-location-dot me-1"></i>{{ $a->lokasi_fisik }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Dokumen" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $a->nama_dokumen }}</div>
                                        @if ($a->nomor_dokumen)
                                            <div style="font-size: var(--text-xs); color: var(--text-muted); font-family: var(--font-family-mono);">{{ $a->nomor_dokumen }}</div>
                                        @endif
                                        @if ($a->deskripsi)
                                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">{{ \Illuminate\Support\Str::limit($a->deskripsi, 80) }}</div>
                                        @endif
                                        @if ($a->singkatan || $a->nama_jurusan)
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $a->singkatan ?: $a->nama_jurusan }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Kategori" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $kategoriList[$a->kategori] ?? $a->kategori }}</span>
                                    </td>
                                    <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        @php
                                            $sc = match ($a->status) {
                                                'aktif' => 'badge-success',
                                                'inaktif' => 'badge-warning',
                                                default => 'badge-danger',
                                            };
                                        @endphp
                                        <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$a->status] ?? $a->status }}</span>
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">{{ $aksesList[$a->tingkat_akses] ?? $a->tingkat_akses }}</div>
                                    </td>
                                    <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                        <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                            @if ($canDelete)
                                                <button type="button" class="btn-icon btn-delete-arsip"
                                                    data-id="{{ $a->id }}" data-nama="{{ $a->nama_dokumen }}"
                                                    title="Hapus Arsip" style="color: var(--danger, #ef4444);">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-box-archive mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                        <div>Belum ada arsip dokumen. Gunakan "Arsipkan Massal" untuk menarik dokumen dari modul lain.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($list->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $list])
                    </div>
                @endif
            </div>
        @endif

        {{-- Modal Tambah Arsip Manual --}}
        <div id="modalArsip" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 640px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Tambah Arsip Manual</h3>
                    <button type="button" data-close="modalArsip" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formArsip" method="POST" action="{{ route('dashboard.laboran.arsip.store') }}">
                    @csrf

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Nama Dokumen <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" name="nama_dokumen" class="form-control" required placeholder="Nama/judul dokumen">
                    </div>

                    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Kategori Ordner <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="kategori" class="form-control" required>
                                @foreach ($kategoriList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Nomor Dokumen</label>
                            <input type="text" name="nomor_dokumen" class="form-control">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal Dokumen</label>
                            <input type="date" name="tanggal_dokumen" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" class="form-control" placeholder="2026/2027">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi Fisik (Ordner)</label>
                            <input type="text" name="lokasi_fisik" class="form-control" placeholder="Contoh: Lemari A - Ordner 2">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tingkat Akses</label>
                            <select name="tingkat_akses" class="form-control">
                                @foreach ($aksesList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                        <select name="status" class="form-control">
                            @foreach ($statusList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalArsip">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Arsip</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Arsip Massal --}}
        <div id="modalMassal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Arsipkan Massal</h3>
                    <button type="button" data-close="modalMassal" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formMassal" method="POST" action="{{ route('dashboard.laboran.arsip.arsipkan-massal') }}">
                    @csrf
                    <p style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: var(--space-7);">
                        Tarik seluruh dokumen dari modul sumber yang belum diarsipkan ke ordner digital.
                    </p>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Sumber Dokumen <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="kategori" class="form-control" required>
                            <option value="berita_acara_kerusakan">Berita Acara Kerusakan Alat ({{ $sumberOptions['berita_acara_kerusakan']->count() ?? 0 }} belum diarsip)</option>
                            <option value="kartu_peminjaman">Kartu Peminjaman Alat ({{ $sumberOptions['kartu_peminjaman']->count() ?? 0 }})</option>
                            <option value="checklist_pemeriksaan">Checklist Pemeriksaan ({{ $sumberOptions['checklist_pemeriksaan']->count() ?? 0 }})</option>
                            <option value="usulan_pengadaan">Usulan Pengadaan ({{ $sumberOptions['usulan_pengadaan']->count() ?? 0 }})</option>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalMassal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-layer-group me-1"></i> Arsipkan Sekarang</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-arsip.js') }}?v={{ file_exists(public_path('js/laboran-arsip.js')) ? filemtime(public_path('js/laboran-arsip.js')) : time() }}"></script>
        @endpush
    </div>
@endsection
