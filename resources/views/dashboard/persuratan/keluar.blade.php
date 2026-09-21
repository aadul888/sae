@extends('layouts.dashboard')

@section('title', 'Buku Agenda Surat Keluar & Surat Keterangan — SAE')
@section('dash_title', 'Surat Keluar')

@section('content')
<div id="suratKeluarContainer"
     data-cetak-id="{{ session('cetak_id') ?? '' }}"
     data-cetak-base-url="{{ url('/dashboard/persuratan/keluar/keterangan') }}"
     data-store-url="{{ route('dashboard.persuratan.keluar.store') }}"
     data-base-url="{{ url('/dashboard/persuratan/keluar') }}"
     data-next-number-url="{{ route('dashboard.persuratan.keluar.next-number') }}"
     data-search-siswa-url="{{ route('dashboard.persuratan.keluar.search-siswa') }}"
     data-store-ket-url="{{ route('dashboard.persuratan.keterangan.store') }}">

    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Buku Agenda Surat Keluar
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Penomoran surat dinas otomatis, penerbitan surat keterangan aktif siswa ber-QR Code, dan arsip dokumen di harddisk.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.persuratan.masuk.index') }}" class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Buku Agenda Surat Masuk">
                <i class="fas fa-inbox text-primary"></i>
            </a>
            <a href="{{ route('dashboard.persuratan.pengaturan.index') }}" class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Pengaturan Persuratan &amp; Harddisk">
                <i class="fas fa-sliders text-muted"></i>
            </a>
            @if ($canCreate)
                @if (($tab ?? '') === 'spt')
                    <button type="button" class="btn btn-primary" id="btnOpenModalSpt" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Terbitkan Surat Perintah Tugas (SPT)">
                        <i class="fas fa-plus"></i> Buat SPT Baru
                    </button>
                @else
                    <button type="button" class="btn btn-outline" id="btnOpenCreateKet" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; border-color: rgba(99,102,241,0.4); color: var(--primary);" title="Buat Surat Keterangan Siswa Aktif">
                        <i class="fas fa-file-signature text-primary"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenCreateKeluar" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Catat Surat Keluar Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Surat Keluar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['surat_keterangan'] ?? 0) }}</div>
                <div class="dash-stat-label">Surat Keterangan Siswa</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
                <i class="fas fa-file-contract"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['spt'] ?? 0) }}</div>
                <div class="dash-stat-label">Surat Perintah Tugas (SPT)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-hard-drive"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1rem;">{{ $hddStatus['free_formatted'] }}</div>
                <div class="dash-stat-label">Ruang Bebas Harddisk</div>
            </div>
        </div>
    </div>

    <!-- 4. Tab Navigasi Baku SAE -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.persuratan.keluar.index') }}"
                class="periode-nav-tab {{ ($tab ?? 'keluar') === 'keluar' ? 'active' : '' }}">
                <i class="fas fa-paper-plane"></i> Semua Surat Keluar
            </a>
            <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'keterangan']) }}"
                class="periode-nav-tab {{ ($tab ?? '') === 'keterangan' ? 'active' : '' }}">
                <i class="fas fa-graduation-cap"></i> Surat Keterangan Siswa
            </a>
            <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'spt']) }}"
                class="periode-nav-tab {{ ($tab ?? '') === 'spt' ? 'active' : '' }}">
                <i class="fas fa-file-contract"></i> Surat Perintah Tugas (SPT)
            </a>
        </div>
    </div>

    <!-- 5. Toolbar & Filter Standar SAE -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                @if (($tab ?? '') !== 'spt')
                    <select id="filterKodeIndeks" class="toolbar-filter-select">
                        <option value="">Semua Indeks</option>
                        @foreach ($indeksList as $idx)
                            <option value="{{ $idx->kode }}" {{ ($kodeIndeks ?? '') === $idx->kode ? 'selected' : '' }}>
                                {{ $idx->kode }} - {{ $idx->judul }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status</option>
                        <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai / Terbit</option>
                        <option value="draf" {{ ($status ?? '') === 'draf' ? 'selected' : '' }}>Draf</option>
                        <option value="diarsipkan" {{ ($status ?? '') === 'diarsipkan' ? 'selected' : '' }}>Diarsipkan</option>
                    </select>
                @endif

                @if (!empty($q) || !empty($status) || !empty($kodeIndeks))
                    <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => $tab ?? 'keluar']) }}"
                        class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                        title="Reset filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="{{ ($tab ?? '') === 'spt' ? 'Cari nomor SPT, kegiatan, tujuan...' : 'Cari nomor surat, perihal, tujuan...' }}" value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    @if (($tab ?? '') === 'spt')
        <!-- Container Datatable Responsive-Stack: Surat Perintah Tugas (SPT) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">No</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nomor SPT &amp; Anggaran</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kegiatan &amp; Lokasi</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Waktu Penugasan</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">GTK Yang Ditugaskan</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $idx => $spt)
                        @php
                            $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
                            $assignedGtk = isset($allGtk) ? $allGtk->whereIn('ptk_id', $ptkIds) : collect();
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; text-align: center; font-size: 0.88rem; color: var(--text-muted);" data-label="No">
                                {{ $items->firstItem() + $idx }}
                            </td>
                            <td style="padding: 12px 16px;" data-label="Nomor SPT">
                                <div style="font-family: monospace; font-weight: 700; color: var(--text-color); font-size: 0.88rem;">{{ $spt->nomor_spt }}</div>
                                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                    Beban: <span class="badge-compact" style="background: rgba(16,185,129,0.1); color: #10b981;">{{ $spt->beban_anggaran }}</span>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;" data-label="Kegiatan & Lokasi">
                                <div style="font-weight: 600; color: var(--text-color); font-size: 0.85rem;">{{ $spt->nama_kegiatan }}</div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> {{ $spt->lokasi_tujuan }}
                                </div>
                                @if(!empty($spt->dasar_penugasan))
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; font-style: italic;">
                                        Dasar: {{ \Illuminate\Support\Str::limit($spt->dasar_penugasan, 60) }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;" data-label="Waktu">
                                <div style="font-size: 0.82rem; color: var(--text-color);">
                                    <i class="far fa-calendar-alt text-muted me-1"></i> {{ \Carbon\Carbon::parse($spt->tanggal_berangkat)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($spt->tanggal_kembali)->translatedFormat('d M Y') }}
                                </div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                    Durasi: <strong>{{ $spt->lama_hari }} Hari</strong>
                                </div>
                            </td>
                            <td style="padding: 12px 16px;" data-label="GTK Ditugaskan">
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @forelse($assignedGtk as $g)
                                        <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.76rem;">
                                            <i class="fas fa-user-check me-1"></i> {{ $g->nama }}
                                        </span>
                                    @empty
                                        <span style="font-size: 0.78rem; color: var(--text-muted); font-style: italic;">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;" data-label="Aksi">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    <a href="{{ route('dashboard.persuratan.keluar.spt.cetak', $spt->id) }}" target="_blank" class="btn-icon" style="color: #2563eb;" title="Cetak Lembar SPT Resmi (A4 Ber-KOP)">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @if($canDelete)
                                        <form action="{{ route('dashboard.persuratan.keluar.spt.delete', $spt->id) }}" method="POST" style="display: inline;" data-confirm="delete" data-name="{{ $spt->nomor_spt }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-danger" title="Hapus SPT">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-file-contract mb-2" style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                <div>Belum ada Surat Perintah Tugas (SPT) yang diterbitkan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- Container Datatable Responsive-Stack Standar SAE -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th class="sortable-th {{ ($sort ?? '') === 'nomor_surat' ? 'sorted' : '' }}" data-sort="nomor_surat" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            No. Surat &amp; Indeks
                            <span class="sort-icon">{!! ($sort ?? '') === 'nomor_surat' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Perihal &amp; Tujuan Penerima
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'tanggal_surat' ? 'sorted' : '' }}" data-sort="tanggal_surat" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                            Tgl Surat
                            <span class="sort-icon">{!! ($sort ?? '') === 'tanggal_surat' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">
                            Arsip HDD
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px; text-align: center;">
                            Status
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $suratKet = \App\Models\SuratKeteranganPd::where('nomor_surat', $item->nomor_surat)->first();
                            $isKet = !empty($suratKet);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px;">
                                <div style="font-family: monospace; font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                    {{ $item->nomor_surat }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                    Indeks: <span class="badge-compact" style="background: rgba(16,185,129,0.1); color: #10b981;">{{ $item->kode_indeks ?: '-' }}</span>
                                    @if ($isKet)
                                        <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary);">Surat Keterangan</span>
                                    @endif
                                </div>
                            </td>

                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; font-size: 0.84rem; color: var(--text-color); margin-bottom: 2px;">
                                    {{ $item->perihal }}
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    <i class="fas fa-location-arrow text-success me-1"></i> Kepada: <strong>{{ $item->tujuan_penerima ?: '-' }}</strong>
                                </div>
                            </td>

                            <td style="padding: 12px 16px;">
                                <div style="font-size: 0.8rem; color: var(--text-color);">
                                    <i class="far fa-calendar-alt text-muted me-1"></i> {{ date('d/m/Y', strtotime($item->tanggal_surat)) }}
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                    Oleh: {{ $item->created_by ?: 'Petugas' }}
                                </div>
                            </td>

                            <td style="padding: 12px 16px; text-align: center;">
                                @if (!empty($item->file_path))
                                    <button type="button" class="btn-icon text-primary btn-view-dokumen" title="Pratinjau Berkas dari HDD"
                                            data-view-url="{{ route('dashboard.persuratan.dokumen.view', $item->id) }}"
                                            data-download-url="{{ route('dashboard.persuratan.dokumen.download', $item->id) }}"
                                            data-title="Surat Keluar: {{ $item->nomor_surat }} - {{ $item->perihal }}"
                                            data-mime="{{ str_contains($item->file_path, '.pdf') ? 'application/pdf' : 'image/jpeg' }}">
                                        <i class="fas fa-file-pdf" style="font-size: 1.1rem; color: #ef4444;"></i>
                                    </button>
                                @else
                                    <span style="font-size: 0.72rem; color: var(--text-muted); font-style: italic;">
                                        Tanpa Berkas
                                    </span>
                                @endif
                            </td>

                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge-compact {{ $item->status === 'selesai' ? 'badge-success' : ($item->status === 'draf' ? 'badge-warning' : 'badge-secondary') }}">
                                    {{ $item->status === 'selesai' ? 'Terbit' : ucfirst($item->status) }}
                                </span>
                            </td>

                            <td style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    <!-- Jika Surat Keterangan Siswa: Tombol Cetak Resmi -->
                                    @if ($isKet && $suratKet)
                                        <a href="{{ route('dashboard.persuratan.keterangan.cetak', $suratKet->id) }}" target="_blank"
                                           class="btn-icon text-primary" title="Cetak Lembar Resmi Surat Keterangan (Kop &amp; QR Code)">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="{{ url('/v/doc/' . $suratKet->doc_id) }}" target="_blank"
                                           class="btn-icon text-info" title="Lihat Halaman Verifikasi Publik (QR)">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                    @endif

                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-keluar" title="Edit Surat Keluar"
                                                data-id="{{ $item->id }}"
                                                data-nomor="{{ $item->nomor_surat }}"
                                                data-indeks="{{ $item->kode_indeks }}"
                                                data-perihal="{{ $item->perihal }}"
                                                data-tujuan="{{ $item->tujuan_penerima }}"
                                                data-tgl-surat="{{ $item->tanggal_surat }}"
                                                data-status="{{ $item->status }}"
                                                data-keterangan="{{ $item->keterangan }}"
                                                data-file-path="{{ $item->file_path }}"
                                                data-file-name="{{ $item->file_name_original }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif

                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.persuratan.keluar.destroy', $item->id) }}" method="POST"
                                              data-confirm="delete" data-name="Surat Keluar No. {{ $item->nomor_surat }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-danger" title="Hapus Surat Keluar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-paper-plane" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Tidak ada data surat keluar yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

        <!-- Paginasi Baku SAE -->
        @if ($items->hasPages())
            <div class="custom-pagination">
                @if ($items->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $items->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif

                @php
                    $cur = $items->currentPage();
                    $last = $items->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp

                @if ($from > 1)
                    <a href="{{ $items->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif

                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $items->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor

                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $items->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif

                @if ($items->hasMorePages())
                    <a href="{{ $items->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    <!-- Modal 1: Catat / Edit Surat Keluar Umum -->
    <div class="modal-overlay" id="modalSuratKeluar" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <h4 id="modalKeluarTitle" style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Catat Surat Keluar Baru
                </h4>
                <button type="button" id="btnCloseModalKeluar" style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSuratKeluar" action="" method="POST" enctype="multipart/form-data" style="overflow-y: auto; flex: 1;">
                @csrf
                <input type="hidden" name="_method" id="methodSpoofKeluar" value="POST">

                <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Kode Indeks Klasifikasi: <span class="text-danger">*</span>
                        </label>
                        <select name="kode_indeks" id="selectKodeIndeksKeluar"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                            @foreach ($indeksList as $idx)
                                <option value="{{ $idx->kode }}">{{ $idx->kode }} - {{ $idx->judul }} ({{ $idx->kategori }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Nomor Surat Keluar:
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="nomor_surat" id="inputNomorSuratKeluar" value="{{ $suggestedNumber }}"
                                   placeholder="Otomatis digenerate sistem jika dikosongkan..."
                                   style="flex: 1; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.84rem;">
                            <button type="button" class="btn btn-outline" id="btnAutoNumberKeluar" style="padding: 0 12px; height: 38px; border-radius: 8px; font-size: 0.8rem;" title="Generate ulang nomor">
                                <i class="fas fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                            Sistem akan otomatis menghitung nomor urut selanjutnya jika Anda tidak mengubahnya.
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Tujuan Penerima: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="tujuan_penerima" id="inputTujuanKeluar" placeholder="Contoh: Kepala Balai Besar Penjaminan Mutu Pendidikan"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Perihal Surat: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="perihal" id="inputPerihalKeluar" placeholder="Contoh: Permohonan Narasumber Workshop Digitalisasi"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tanggal Surat: <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_surat" id="inputTanggalSuratKeluar" value="{{ date('Y-m-d') }}"
                                   style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Status Surat:
                            </label>
                            <select name="status" id="selectStatusKeluar"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                                <option value="selesai">Selesai / Terbit</option>
                                <option value="draf">Draf</option>
                                <option value="diarsipkan">Diarsipkan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Unggah Dokumen Final / Scan Bertanda Tangan (Disimpan ke HDD):
                        </label>
                        <input type="file" name="file_arsip" accept=".pdf,.jpg,.jpeg,.png"
                               style="width: 100%; padding: 6px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                        <div id="fileInfoExistingKeluar" style="display: none; font-size: 0.74rem; margin-top: 4px;"></div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                            Maksimal 25MB (PDF, JPG, PNG). Berkas fisik disimpan langsung di partisi harddisk.
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Keterangan Tambahan:
                        </label>
                        <textarea name="keterangan" id="inputKeteranganKeluar" rows="2" placeholder="Catatan perihal surat..."
                                  style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;"></textarea>
                    </div>
                </div>

                <div style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                    <button type="button" id="btnCancelModalKeluar" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;">
                        <i class="fas fa-save me-1"></i> Simpan Surat Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Pembuatan Surat Keterangan Siswa Aktif Otomatis -->
    <div class="modal-overlay" id="modalSuratKeterangan" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 580px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Terbitkan Surat Keterangan Siswa Aktif
                        </h4>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                            Otomatis terhubung ke data induk siswa Dapodik &amp; menghasilkan QR Code verifikasi.
                        </div>
                    </div>
                </div>
                <button type="button" id="btnCloseModalKet" style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSuratKeterangan" action="{{ route('dashboard.persuratan.keterangan.store') }}" method="POST" style="overflow-y: auto; flex: 1;">
                @csrf
                <input type="hidden" name="peserta_didik_id" id="inputPesertaDidikId" required>

                <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <!-- Autocomplete Siswa Dapodik -->
                    <div style="position: relative;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Cari Peserta Didik (Nama / NISN / NIPD): <span class="text-danger">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="text" id="searchSiswaInput" placeholder="Ketik nama siswa atau NISN..." autocomplete="off"
                                   style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.86rem;">
                        </div>

                        <!-- Dropdown Hasil Pencarian -->
                        <div id="siswaSearchResults" style="display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 1000; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 220px; overflow-y: auto; margin-top: 4px;"></div>
                    </div>

                    <!-- Kartu Info Siswa Terpilih -->
                    <div id="selectedSiswaCard" style="display: none; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 12px 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--primary);">
                                    <i class="fas fa-check-circle me-1"></i> Peserta Didik Terpilih
                                </div>
                                <div id="selectedSiswaNama" style="font-weight: 800; font-size: 0.94rem; color: var(--text-color); margin: 2px 0;">-</div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    NISN: <strong id="selectedSiswaNisn">-</strong> | Kelas: <strong id="selectedSiswaKelas">-</strong>
                                </div>
                            </div>
                            <button type="button" id="btnChangeSiswa" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.74rem; border-radius: 6px;">
                                Ganti
                            </button>
                        </div>
                    </div>

                    <!-- Kode Indeks Klasifikasi Surat -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Kode Indeks Klasifikasi Surat: <span class="text-danger">*</span>
                        </label>
                        <select name="kode_indeks" id="selectKodeIndeksKet"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                            @foreach ($indeksList as $idx)
                                <option value="{{ $idx->kode }}" {{ $idx->kode === 'KS.02.23' ? 'selected' : '' }}>
                                    {{ $idx->kode }} - {{ $idx->judul }} ({{ $idx->kategori }})
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                            Pilih klasifikasi indeks surat. Nomor surat otomatis akan menyesuaikan format indeks ini.
                        </div>
                    </div>

                    <!-- Nomor Surat Keterangan -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Nomor Surat Keterangan:
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="nomor_surat" id="inputNomorSuratKet" value="{{ $suggestedKetNumber }}"
                                   placeholder="Otomatis mengikuti template indeks..."
                                   style="flex: 1; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.84rem;">
                            <button type="button" class="btn btn-outline" id="btnAutoNumberKet" style="padding: 0 12px; height: 38px; border-radius: 8px; font-size: 0.8rem;" title="Generate ulang nomor">
                                <i class="fas fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                            Format standar mengikuti template pengaturan: <code>{kode_indeks}/{nomor}/{sekolah_kode}/{romawi_bulan}/{tahun}</code>.
                        </div>
                    </div>

                    <!-- Keperluan Surat -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Keperluan Penerbitan Surat: <span class="text-danger">*</span>
                        </label>
                        <select id="selectKeperluanPreset" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; margin-bottom: 6px;">
                            <option value="">-- Pilih Contoh Keperluan / Ketik Sendiri --</option>
                            <option value="Kelengkapan Berkas Tunjangan Gaji / Anak Orang Tua">Kelengkapan Berkas Tunjangan Gaji / Anak Orang Tua</option>
                            <option value="Pengurusan Beasiswa PIP / Prestasi">Pengurusan Beasiswa PIP / Prestasi</option>
                            <option value="Pembukaan Rekening Tabungan Bank">Pembukaan Rekening Tabungan Bank</option>
                            <option value="Pendaftaran BPJS / Asuransi Kesehatan">Pendaftaran BPJS / Asuransi Kesehatan</option>
                            <option value="Melanjutkan Studi / Pindah Sekolah">Melanjutkan Studi / Pindah Sekolah</option>
                            <option value="Pengurusan Visa / Paspor Kedutaan">Pengurusan Visa / Paspor Kedutaan</option>
                        </select>
                        <input type="text" name="keperluan" id="inputKeperluan" placeholder="Tuliskan keperluan lengkap..."
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Tanggal Surat:
                        </label>
                        <input type="date" name="tanggal_surat" value="{{ date('Y-m-d') }}"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>
                </div>

                <div style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                    <button type="button" id="btnCancelModalKet" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;">
                        <i class="fas fa-check-circle me-1"></i> Terbitkan &amp; Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Pratinjau Dokumen Berkas dari HDD Komputer -->
    <div class="modal-overlay" id="modalPreviewDokumen" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 900px; height: 85vh; box-shadow: 0 25px 50px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%;">
                    <i class="fas fa-file-lines text-success"></i>
                    <span id="previewTitle" style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        Pratinjau Dokumen Arsip
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="#" id="previewDownloadBtn" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px;" title="Unduh Berkas ke Komputer">
                        <i class="fas fa-download me-1"></i> Unduh
                    </a>
                    <button type="button" id="btnClosePreview" style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer; padding: 4px 8px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div style="flex: 1; background: #525659; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <iframe id="previewIframe" src="about:blank" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                <img id="previewImage" src="" alt="Preview Berkas" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
            </div>
    <!-- Modal 4: Buat Surat Perintah Tugas (SPT) -->
    <div class="modal-overlay" id="modalSpt" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-contract text-primary"></i> Terbitkan Surat Perintah Tugas (SPT)
                </h4>
                <button type="button" id="btnCloseModalSpt" style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.persuratan.keluar.spt.store') }}" method="POST" id="formSpt">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama Kegiatan / Tugas Dinas <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Mengikuti Bimtek Implementasi Kurikulum Merdeka" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lokasi / Tempat Tujuan <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="lokasi_tujuan" class="form-control" placeholder="Contoh: Hotel Grand Sahid / BBGP Provinsi" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Berangkat <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tanggal_berangkat" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Kembali <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tanggal_kembali" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Beban Anggaran <span style="color: #ef4444;">*</span></label>
                    <select name="beban_anggaran" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        <option value="BOS Reguler">BOS Reguler</option>
                        <option value="BOS Kinerja">BOS Kinerja</option>
                        <option value="Komite Sekolah">Komite Sekolah</option>
                        <option value="Penyelenggara / Panitia">Biaya Penyelenggara / Gratis</option>
                        <option value="Mandiri">Mandiri</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih GTK Yang Ditugaskan <span style="color: #ef4444;">*</span></label>
                    <div style="max-height: 160px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; background: var(--bg-hover);">
                        @if(isset($allGtk))
                            @foreach($allGtk as $g)
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 0.84rem; cursor: pointer; color: var(--text-color);">
                                <input type="checkbox" name="daftar_ptk_id[]" value="{{ $g->ptk_id }}">
                                <span><strong>{{ $g->nama }}</strong> <small style="color: var(--text-muted);">(NIP: {{ $g->nip ?: '-' }})</small></span>
                            </label>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Dasar Penugasan (Opsional)</label>
                    <textarea name="dasar_penugasan" class="form-control" rows="2" placeholder="Contoh: Surat Undangan Dinas Pendidikan No. 421/123/2026 tanggal 15 September 2026..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalSpt" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;"><i class="fas fa-check-circle me-1"></i> Terbitkan SPT</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/persuratan-keluar.js') }}"></script>
@endpush
