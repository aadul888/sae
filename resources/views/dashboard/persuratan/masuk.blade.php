@extends('layouts.dashboard')

@section('title', 'Buku Agenda Surat Masuk — SAE')
@section('dash_title', 'Surat Masuk')

@section('content')
<div id="suratMasukContainer"
     data-store-url="{{ route('dashboard.persuratan.masuk.store') }}"
     data-base-url="{{ url('/dashboard/persuratan/masuk') }}">

    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-inbox"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Buku Agenda Surat Masuk
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Pencatatan surat masuk, lembar disposisi pimpinan, dan pengarsipan berkas scan ke harddisk.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.86rem; border-radius: 8px;">
                <i class="fas fa-paper-plane text-success me-1"></i> Surat Keluar
            </a>
            <a href="{{ route('dashboard.persuratan.pengaturan.index') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.86rem; border-radius: 8px;" title="Pengaturan & Harddisk">
                <i class="fas fa-sliders text-muted"></i>
            </a>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenCreateMasuk" style="padding: 8px 16px; font-size: 0.86rem; border-radius: 8px;">
                    <i class="fas fa-plus me-1"></i> Catat Surat Masuk
                </button>
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
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Surat Masuk</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['menunggu'] ?? 0) }}</div>
                <div class="dash-stat-label">Menunggu Disposisi</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['proses'] ?? 0) }}</div>
                <div class="dash-stat-label">Sedang Diproses</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['selesai'] ?? 0) }}</div>
                <div class="dash-stat-label">Selesai / Tindak Lanjut</div>
            </div>
        </div>
    </div>

    <!-- 4. Main Data Card & Table -->
    <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 24px; background: var(--card-bg);">
        <!-- Toolbar: Search, Filter Status & Dropdown Baris -->
        <form method="GET" action="{{ route('dashboard.persuratan.masuk.index') }}" id="filterForm" class="dash-toolbar" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <div class="live-search-wrap" style="flex: 1; min-width: 260px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" id="liveSearchInput" value="{{ $q ?? '' }}" placeholder="Cari nomor surat, perihal, atau instansi pengirim..." autocomplete="off">
                @if (!empty($q))
                    <a href="{{ route('dashboard.persuratan.masuk.index', array_filter(['status' => $status, 'per_page' => $perPage])) }}" class="clear-search" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <select name="status" id="filterStatus" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="">Semua Status</option>
                    <option value="menunggu_disposisi" {{ ($status ?? '') === 'menunggu_disposisi' ? 'selected' : '' }}>Menunggu Disposisi</option>
                    <option value="diproses" {{ ($status ?? '') === 'diproses' ? 'selected' : '' }}>Sedang Diproses</option>
                    <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="diarsipkan" {{ ($status ?? '') === 'diarsipkan' ? 'selected' : '' }}>Diarsipkan</option>
                </select>

                <select name="per_page" id="perPageSelect" onchange="this.form.submit()" style="height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                    <option value="10" {{ ($perPage ?? 25) == 10 ? 'selected' : '' }}>10 Baris</option>
                    <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25 Baris</option>
                    <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50 Baris</option>
                    <option value="100" {{ ($perPage ?? 25) == 100 ? 'selected' : '' }}>100 Baris</option>
                </select>
            </div>
        </form>

        <!-- Container Datatable Responsive-Stack Standar SAE -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 16px; border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th class="sortable-th {{ ($sort ?? '') === 'nomor_surat' ? 'sorted' : '' }}" data-sort="nomor_surat" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            No. Surat &amp; Indeks
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Pengirim &amp; Perihal
                        </th>
                        <th class="sortable-th {{ ($sort ?? '') === 'tanggal_surat' ? 'sorted' : '' }}" data-sort="tanggal_surat" style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                            Tgl Surat / Masuk
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">
                            Arsip HDD
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                            Status
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px;">
                                <div style="font-family: monospace; font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                    {{ $item->nomor_surat }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                    Indeks: <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary);">{{ $item->kode_indeks ?: '421' }}</span>
                                </div>
                            </td>

                            <td style="padding: 12px 16px;">
                                <div style="font-weight: 600; font-size: 0.84rem; color: var(--text-color); margin-bottom: 2px;">
                                    {{ $item->perihal }}
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    <i class="fas fa-building text-primary me-1"></i> Dari: <strong>{{ $item->pengirim_asal ?: '-' }}</strong>
                                </div>
                            </td>

                            <td style="padding: 12px 16px;">
                                <div style="font-size: 0.8rem; color: var(--text-color);">
                                    <i class="far fa-calendar-alt text-muted me-1"></i> {{ date('d/m/Y', strtotime($item->tanggal_surat)) }}
                                </div>
                                @if ($item->tanggal_diterima)
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                        Terima: {{ date('d/m/Y', strtotime($item->tanggal_diterima)) }}
                                    </div>
                                @endif
                            </td>

                            <td style="padding: 12px 16px; text-align: center;">
                                @if (!empty($item->file_path))
                                    <button type="button" class="btn-icon text-primary btn-view-dokumen" title="Pratinjau Berkas dari HDD"
                                            data-view-url="{{ route('dashboard.persuratan.dokumen.view', $item->id) }}"
                                            data-download-url="{{ route('dashboard.persuratan.dokumen.download', $item->id) }}"
                                            data-title="Surat Masuk: {{ $item->nomor_surat }} - {{ $item->perihal }}"
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
                                @php
                                    $badgeClass = match($item->status) {
                                        'menunggu_disposisi' => 'badge-warning',
                                        'diproses' => 'badge-info',
                                        'selesai' => 'badge-success',
                                        default => 'badge-secondary',
                                    };
                                    $badgeLabel = match($item->status) {
                                        'menunggu_disposisi' => 'Menunggu Disp.',
                                        'diproses' => 'Diproses',
                                        'selesai' => 'Selesai',
                                        'diarsipkan' => 'Diarsipkan',
                                        default => ucfirst($item->status),
                                    };
                                @endphp
                                <span class="badge-compact {{ $badgeClass }}">
                                    {{ $badgeLabel }}
                                </span>
                            </td>

                            <td style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    <!-- Tombol Disposisi -->
                                    <button type="button" class="btn-icon text-warning btn-disposisi-masuk" title="Buat Lembar Disposisi"
                                            data-id="{{ $item->id }}"
                                            data-nomor="{{ $item->nomor_surat }}"
                                            data-perihal="{{ $item->perihal }}">
                                        <i class="fas fa-share-nodes"></i>
                                    </button>

                                    <!-- Tombol Cetak Lembar Disposisi -->
                                    <a href="{{ route('dashboard.persuratan.masuk.disposisi.cetak', $item->id) }}" target="_blank"
                                       class="btn-icon text-info" title="Cetak Lembar Disposisi Resmi">
                                        <i class="fas fa-print"></i>
                                    </a>

                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-masuk" title="Edit Surat Masuk"
                                                data-id="{{ $item->id }}"
                                                data-nomor="{{ $item->nomor_surat }}"
                                                data-indeks="{{ $item->kode_indeks }}"
                                                data-perihal="{{ $item->perihal }}"
                                                data-pengirim="{{ $item->pengirim_asal }}"
                                                data-tgl-surat="{{ $item->tanggal_surat }}"
                                                data-tgl-diterima="{{ $item->tanggal_diterima }}"
                                                data-status="{{ $item->status }}"
                                                data-keterangan="{{ $item->keterangan }}"
                                                data-file-path="{{ $item->file_path }}"
                                                data-file-name="{{ $item->file_name_original }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif

                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.persuratan.masuk.destroy', $item->id) }}" method="POST"
                                              data-confirm="delete" data-name="Surat Masuk No. {{ $item->nomor_surat }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-danger" title="Hapus Surat Masuk">
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
                                <i class="fas fa-inbox" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Tidak ada data surat masuk yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
    </div>

    <!-- Modal 1: Catat / Edit Surat Masuk -->
    <div class="modal-overlay" id="modalSuratMasuk" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <h4 id="modalMasukTitle" style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Catat Surat Masuk Baru
                </h4>
                <button type="button" id="btnCloseModalMasuk" style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSuratMasuk" action="" method="POST" enctype="multipart/form-data" style="overflow-y: auto; flex: 1;">
                @csrf
                <input type="hidden" name="_method" id="methodSpoofMasuk" value="POST">

                <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: flex; gap: 12px;">
                        <div style="flex: 2;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Nomor Surat Pengirim: <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nomor_surat" id="inputNomorSurat" placeholder="Nomor resmi pada surat masuk..."
                                   style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-family: monospace; font-size: 0.84rem;" required>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Kode Indeks:
                            </label>
                            <select name="kode_indeks" id="selectKodeIndeks"
                                    style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                                <option value="">Pilih...</option>
                                @foreach ($indeksList as $idx)
                                    <option value="{{ $idx->kode }}">{{ $idx->kode }} - {{ $idx->judul }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Instansi / Pengirim Asal: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="pengirim_asal" id="inputPengirimAsal" placeholder="Contoh: Dinas Pendidikan Prov. Jawa Barat"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Perihal Surat: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="perihal" id="inputPerihal" placeholder="Contoh: Undangan Rapat Koordinasi Kurikulum Merdeka"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tanggal Surat: <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_surat" id="inputTanggalSurat"
                                   style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tanggal Diterima:
                            </label>
                            <input type="date" name="tanggal_diterima" id="inputTanggalDiterima"
                                   style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Unggah Berkas Fisik / Scan (Disimpan Langsung ke Harddisk):
                        </label>
                        <input type="file" name="file_arsip" accept=".pdf,.jpg,.jpeg,.png"
                               style="width: 100%; padding: 6px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;">
                        <div id="fileInfoExisting" style="display: none; font-size: 0.74rem; margin-top: 4px;"></div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                            Maksimal 25MB (PDF, JPG, PNG). Berkas otomatis disimpan di direktori HDD: <code>{{ $hddStatus['path'] }}</code>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Ringkasan Isi / Catatan Tambahan:
                        </label>
                        <textarea name="keterangan" id="inputKeterangan" rows="2" placeholder="Catatan singkat perihal atau tujuan surat..."
                                  style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;"></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Status Surat:
                        </label>
                        <select name="status" id="selectStatus"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                            <option value="menunggu_disposisi">Menunggu Disposisi</option>
                            <option value="diproses">Sedang Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="diarsipkan">Diarsipkan</option>
                        </select>
                    </div>
                </div>

                <div style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                    <button type="button" id="btnCancelModalMasuk" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;">
                        <i class="fas fa-save me-1"></i> Simpan Surat Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Lembar Disposisi Surat -->
    <div class="modal-overlay" id="modalDisposisi" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 540px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        Lembar Disposisi Surat Masuk
                    </h4>
                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                        No: <strong id="disposisiNomorSuratText">-</strong>
                    </div>
                </div>
                <button type="button" id="btnCloseModalDisposisi" style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formDisposisi" action="" method="POST">
                @csrf
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="background: rgba(99,102,241,0.06); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(99,102,241,0.15);">
                        <div style="font-size: 0.76rem; color: var(--text-muted);">Perihal Surat:</div>
                        <div id="disposisiPerihalText" style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">-</div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Disposisi Dari:
                        </label>
                        <input type="text" name="disposisi_dari" id="inputDisposisiDari" value="Kepala Sekolah"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Tujuan Disposisi (Pilih PTK / Bidang): <span class="text-danger">*</span>
                        </label>
                        <select id="selectPtkTujuan" name="ptk_id_tujuan"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; margin-bottom: 6px;">
                            <option value="">-- Pilih Guru / Tendik / Pejabat Sekolah --</option>
                            @foreach ($ptkList as $ptk)
                                <option value="{{ $ptk->ptk_id }}" data-nama="{{ $ptk->nama }}">
                                    {{ $ptk->nama }} ({{ $ptk->jabatan_ptk_id_str ?: $ptk->jenis_ptk_id_str }})
                                </option>
                            @endforeach
                        </select>
                        <input type="text" name="disposisi_ke" id="inputDisposisiKe" placeholder="Atau ketik nama/bagian (misal: Waka Kurikulum, KTU)"
                               style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Instruksi Disposisi: <span class="text-danger">*</span>
                            </label>
                            <select name="instruksi" id="selectInstruksi"
                                    style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;" required>
                                <option value="Tindak Lanjuti">Tindak Lanjuti</option>
                                <option value="Pelajari / Teliti">Pelajari / Teliti</option>
                                <option value="Hadiri / Wakili">Hadiri / Wakili</option>
                                <option value="Koordinasikan">Koordinasikan</option>
                                <option value="Buatkan Tanggapan / Balasan">Buatkan Tanggapan / Balasan</option>
                                <option value="Arsipkan">Arsipkan</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tanggal Disposisi:
                            </label>
                            <input type="date" name="tanggal_disposisi" id="inputTanggalDisposisi"
                                   style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Catatan / Arahan Pimpinan:
                        </label>
                        <textarea name="catatan" id="inputDisposisiCatatan" rows="3" placeholder="Catatan instruksi khusus dari Kepala Sekolah..."
                                  style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem;"></textarea>
                    </div>
                </div>

                <div style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelModalDisposisi" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;">
                        <i class="fas fa-check me-1"></i> Simpan Disposisi
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
                    <i class="fas fa-file-lines text-primary"></i>
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
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/persuratan-masuk.js') }}"></script>
@endpush
