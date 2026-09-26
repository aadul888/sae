@extends('layouts.dashboard')

@section('title', 'Administrasi Kepegawaian GTK - SAE')

@section('content')
    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-card-alt text-primary me-2"></i> Administrasi Kepegawaian GTK
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                Pengelolaan arsip berkas pegawai guru &amp; tendik, kenaikan gaji berkala (KGB), serta permohonan cuti dan izin resmi.
            </p>
        </div>

        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.tendik', ['bidang' => 'kepegawaian']) }}" class="btn btn-outline" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;" title="Dashboard Ringkasan Kepegawaian">
                <i class="fas fa-gauge-high text-primary"></i>
            </a>

            @if($tab === 'pegawai' || $tab === 'berkas' || $tab === 'guru' || $tab === 'tendik')
                <button type="button" class="btn btn-primary" id="btnOpenModalUploadBerkas" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Unggah Berkas Digital GTK">
                    <i class="fas fa-upload"></i> Unggah Berkas
                </button>
            @elseif($tab === 'kgb')
                <button type="button" class="btn btn-primary" id="btnOpenModalKgb" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Catat Usulan KGB Baru">
                    <i class="fas fa-plus"></i> Catat KGB
                </button>
            @elseif($tab === 'cuti')
                <button type="button" class="btn btn-primary" id="btnOpenModalCuti" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Buat Pengajuan Cuti / Izin">
                    <i class="fas fa-plus"></i> Ajukan Cuti
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if(session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format(($stats['total_guru'] ?? 0) + ($stats['total_tendik'] ?? 0)) }}</div>
                <div class="dash-stat-label">Total Pegawai GTK</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_guru'] ?? 0) }}</div>
                <div class="dash-stat-label">Pendidik (Guru)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_tendik'] ?? 0) }}</div>
                <div class="dash-stat-label">Tenaga Kependidikan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-business-time"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['kgb_jatuh_tempo'] ?? 0) }}</div>
                <div class="dash-stat-label">KGB Jatuh Tempo (&le; 90 Hari)</div>
            </div>
        </div>
    </div>

    <!-- Alert Notifikasi KGB Jatuh Tempo -->
    @if($kgbJatuhTempoCount > 0)
        <div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bell" style="color: #f59e0b; font-size: 1.15rem;"></i>
                <span style="font-size: 0.86rem; color: #92400e;">
                    <strong>{{ $kgbJatuhTempoCount }} GTK</strong> jatuh tempo Kenaikan Gaji Berkala (KGB) dalam kurun waktu 90 hari ke depan.
                </span>
            </div>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}" class="btn btn-outline" style="border-color: #f59e0b; color: #b45309; padding: 4px 12px; font-size: 0.82rem; font-weight: 600;" title="Buka KGB Tracker">
                Buka KGB Tracker <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    @endif

    <!-- 4. Tab Navigasi Baku SAE -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'pegawai']) }}"
                class="periode-nav-tab {{ $tab === 'pegawai' || $tab === 'guru' ? 'active' : '' }}">
                <i class="fas fa-users"></i> Pegawai
            </a>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'berkas']) }}"
                class="periode-nav-tab {{ $tab === 'berkas' || $tab === 'tendik' ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Berkas Pegawai
            </a>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}"
                class="periode-nav-tab {{ $tab === 'kgb' ? 'active' : '' }}">
                <i class="fas fa-business-time"></i> KGB Tracker
                @if($kgbJatuhTempoCount > 0)
                    <span style="background: #f59e0b; color: white; font-size: 0.72rem; padding: 2px 7px; border-radius: 10px; margin-left: 4px;">{{ $kgbJatuhTempoCount }}</span>
                @endif
            </a>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'cuti']) }}"
                class="periode-nav-tab {{ $tab === 'cuti' ? 'active' : '' }}">
                <i class="fas fa-plane-departure"></i> Cuti &amp; Izin
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

                @if($tab === 'pegawai' || $tab === 'berkas' || $tab === 'guru' || $tab === 'tendik')
                    <!-- Filter Jenis PTK -->
                    <select id="filterJenis" class="toolbar-filter-select">
                        <option value="">Semua Jenis PTK</option>
                        @foreach ($jenisPtkList as $j)
                            <option value="{{ $j }}" {{ ($filterJenis ?? '') === $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>

                    <!-- Filter Status Kepegawaian -->
                    <select id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status Kepegawaian</option>
                        @foreach ($statusKepegawaianList as $s)
                            <option value="{{ $s }}" {{ ($filterStatus ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>

                    @if($tab === 'pegawai' || $tab === 'guru')
                        <!-- Filter Gender -->
                        <select id="filterGender" class="toolbar-filter-select" style="min-width: 120px;">
                            <option value="">Semua Gender</option>
                            <option value="L" {{ ($filterGender ?? '') === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                            <option value="P" {{ ($filterGender ?? '') === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                        </select>
                    @endif
                @endif

                @if(!empty($search) || !empty($filterJenis) || !empty($filterStatus) || !empty($filterGender))
                    <a href="{{ route('dashboard.kepegawaian.index', ['tab' => $tab]) }}"
                        class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                        title="Reset filter & pencarian">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="{{ $tab === 'kgb' ? 'Cari nama GTK / NIP / nomor SK...' : ($tab === 'cuti' ? 'Cari nama GTK / keperluan...' : 'Cari nama / NIP / NUPTK / NIK...') }}" value="{{ $search ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($search) ? 'visible' : '' }}" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 6. Konten Data Sesuai Tab Aktif -->

    {{-- TAB 1: PEGAWAI (GURU & TENDIK LENGKAP) --}}
    @if($tab === 'pegawai' || $tab === 'guru')
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Lengkap Pegawai</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NUPTK / NIP</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 70px;">L/P</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis PTK</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pendidikan Terakhir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pegawaiList as $idx => $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted); text-align: center;">
                                {{ $pegawaiList->firstItem() + $idx }}
                            </td>
                            <td data-label="Nama" style="padding: 12px 18px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.85rem; flex-shrink: 0;">
                                        <i class="fas {{ str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'guru') ? 'fa-chalkboard-user' : 'fa-id-badge' }}"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.9rem;">{{ $item->nama }}</div>
                                        @if(!empty($item->nik))
                                            <div style="font-size: 0.72rem; font-family: monospace; color: var(--text-muted);">
                                                NIK: <span class="copyable" data-copy="{{ $item->nik }}" title="Klik salin">{{ $item->nik }}</span>
                                            </div>
                                        @endif
                                        @if($item->email)
                                            <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $item->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="NUPTK/NIP" style="padding: 12px 18px; font-family: monospace; font-size: 0.84rem;">
                                <div style="color: var(--primary); font-weight: 600;">
                                    {{ $item->nuptk ? $item->nuptk : '-' }}
                                </div>
                                @if($item->nip)
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        NIP: {{ $item->nip }}
                                    </div>
                                @endif
                            </td>
                            <td data-label="L/P" style="padding: 12px 18px; text-align: center;">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}" style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td data-label="Jenis PTK" style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-color);">
                                <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                    {{ $item->jenis_ptk_id_str ?: 'GTK' }}
                                </span>
                            </td>
                            <td data-label="Status" style="padding: 12px 18px; font-size: 0.82rem;">
                                <span class="badge {{ str_contains(strtoupper($item->status_kepegawaian_id_str ?? ''), 'PNS') ? 'badge-primary' : 'badge-outline' }}" style="font-size: 0.72rem; padding: 3px 8px;">
                                    {{ $item->status_kepegawaian_id_str ?: '-' }}
                                </span>
                            </td>
                            <td data-label="Pendidikan" style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-muted);">
                                <div style="color: var(--text-color); font-weight: 600;">{{ $item->pendidikan_terakhir ?: '-' }}</div>
                                @if($item->bidang_studi_terakhir)
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $item->bidang_studi_terakhir }}</div>
                                @endif
                            </td>
                            <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                <div class="table-actions" style="justify-content: center; gap: 6px;">
                                    <button type="button" class="btn-icon btn-detail-pegawai" data-id="{{ $item->ptk_id }}" title="Rincian Lengkap Pegawai">
                                        <i class="fas fa-id-card" style="color: var(--primary);"></i>
                                    </button>
                                    <button type="button" class="btn-icon btn-upload-gtk-berkas" data-id="{{ $item->ptk_id }}" data-nama="{{ $item->nama }}" title="Unggah Berkas">
                                        <i class="fas fa-upload" style="color: #10b981;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                <i class="fas fa-users mb-2" style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                <div>Tidak ada data pegawai GTK ditemukan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pegawaiList->hasPages())
            <div class="custom-pagination">
                @if ($pegawaiList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $pegawaiList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $pegawaiList->currentPage();
                    $last = $pegawaiList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $pegawaiList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $pegawaiList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $pegawaiList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($pegawaiList->hasMorePages())
                    <a href="{{ $pegawaiList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    {{-- TAB 2: BERKAS PEGAWAI --}}
    @elseif($tab === 'berkas' || $tab === 'tendik')
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Identitas Pegawai</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis PTK &amp; Status</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Berkas Digital Tersimpan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($berkasList as $idx => $gtk)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted); text-align: center;">
                                {{ $berkasList->firstItem() + $idx }}
                            </td>
                            <td data-label="Identitas Pegawai" style="padding: 12px 18px;">
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $gtk->nama }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    NIP: {{ $gtk->nip ?: '-' }} &bull; NUPTK: {{ $gtk->nuptk ?: '-' }}
                                </div>
                            </td>
                            <td data-label="Jenis PTK & Status" style="padding: 12px 18px;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                        <i class="fas {{ str_contains(strtolower($gtk->jenis_ptk_id_str ?? ''), 'guru') ? 'fa-chalkboard-user' : 'fa-id-badge' }} me-1"></i>
                                        {{ $gtk->jenis_ptk_id_str ?: 'GTK' }}
                                    </span>
                                    <span style="font-size: 0.74rem; color: var(--text-muted);">
                                        {{ $gtk->status_kepegawaian_id_str ?: 'Aktif' }}
                                    </span>
                                </div>
                            </td>
                            <td data-label="Berkas Digital" style="padding: 12px 18px;">
                                @if($gtk->berkas->isNotEmpty())
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                        @foreach($gtk->berkas as $b)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.78rem;">
                                                <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                                <a href="{{ asset('storage/' . $b->file_path) }}" target="_blank" style="color: var(--text-color); text-decoration: none; font-weight: 500;">
                                                    {{ $b->judul_dokumen }}
                                                </a>
                                                <form action="{{ route('dashboard.kepegawaian.berkas.delete', $b->id) }}" method="POST" style="display: inline;" data-confirm="delete" data-name="{{ $b->judul_dokumen }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="tab_redirect" value="berkas">
                                                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0 2px; font-size: 0.85rem;" title="Hapus berkas">&times;</button>
                                                </form>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">Belum ada berkas digital</span>
                                @endif
                            </td>
                            <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                <div class="table-actions" style="justify-content: center;">
                                    <button type="button" class="btn-icon btn-upload-gtk-berkas" data-id="{{ $gtk->ptk_id }}" data-nama="{{ $gtk->nama }}" title="Unggah Berkas Pegawai">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                <div>Tidak ada data berkas pegawai ditemukan.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($berkasList->hasPages())
            <div class="custom-pagination">
                @if ($berkasList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $berkasList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $berkasList->currentPage();
                    $last = $berkasList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $berkasList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $berkasList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $berkasList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($berkasList->hasMorePages())
                    <a href="{{ $berkasList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    {{-- TAB 3: KGB TRACKER --}}
    @elseif($tab === 'kgb')
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">No</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama &amp; NIP Pegawai</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TMT KGB Terakhir</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Target KGB Berikutnya</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Usulan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kgbList as $idx => $item)
                        @php
                            $target = \Carbon\Carbon::parse($item->tmt_baru_target);
                            $diffDays = (int)\Carbon\Carbon::today()->diffInDays($target, false);
                            $isJatuhTempo = $diffDays <= 90 && $diffDays >= 0;
                            $isLewat = $diffDays < 0;
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); {{ $isJatuhTempo ? 'background: rgba(245, 158, 11, 0.05);' : '' }}">
                            <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted); text-align: center;">
                                {{ $kgbList->firstItem() + $idx }}
                            </td>
                            <td data-label="Nama & NIP" style="padding: 12px 18px;">
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $item->gtk?->nama ?? '-' }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    NIP: {{ $item->gtk?->nip ?: '-' }} &bull; MKG: {{ $item->mkg_tahun }} Thn {{ $item->mkg_bulan }} Bln
                                </div>
                            </td>
                            <td data-label="TMT Terakhir" style="padding: 12px 18px;">
                                <div style="font-size: 0.84rem; color: var(--text-color);">
                                    <i class="far fa-calendar-alt text-muted me-1"></i> {{ \Carbon\Carbon::parse($item->tmt_lama)->format('d/m/Y') }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    SK: {{ $item->nomor_sk_terakhir ?: '-' }}
                                </div>
                            </td>
                            <td data-label="Target Berikutnya" style="padding: 12px 18px;">
                                <div style="font-size: 0.85rem; font-weight: 600; color: {{ $isLewat ? '#ef4444' : ($isJatuhTempo ? '#d97706' : 'var(--text-color)') }};">
                                    <i class="far fa-clock me-1"></i> {{ $target->format('d/m/Y') }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    @if($isLewat)
                                        <span style="color: #ef4444; font-weight: 600;">Lewat {{ abs($diffDays) }} hari</span>
                                    @elseif($isJatuhTempo)
                                        <span style="color: #d97706; font-weight: 600;">Jatuh tempo dlm {{ $diffDays }} hari</span>
                                    @else
                                        <span>{{ $diffDays }} hari lagi</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Status" style="padding: 12px 18px;">
                                @php
                                    $statusLabels = [
                                        'belum_waktunya' => ['Belum Waktunya', 'badge-secondary'],
                                        'siap_diajukan'  => ['Siap Diajukan', 'badge-warning'],
                                        'diproses'       => ['Sedang Diproses', 'badge-info'],
                                        'terbit_sk'      => ['Terbit SK', 'badge-success'],
                                    ];
                                    $st = $statusLabels[$item->status_usulan] ?? ['Status', 'badge-secondary'];
                                @endphp
                                <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
                            </td>
                            <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                <div class="table-actions" style="justify-content: center;">
                                    <button type="button" class="btn-icon btn-edit-kgb"
                                        data-ptk-id="{{ $item->ptk_id }}"
                                        data-tmt-terakhir="{{ $item->tmt_lama }}"
                                        data-sk-terakhir="{{ $item->nomor_sk_terakhir }}"
                                        data-tmt-berikutnya="{{ $item->tmt_baru_target }}"
                                        data-mk-tahun="{{ $item->mkg_tahun }}"
                                        data-mk-bulan="{{ $item->mkg_bulan }}"
                                        data-status="{{ $item->status_usulan }}"
                                        data-catatan="{{ $item->catatan }}"
                                        title="Perbarui KGB">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                <i class="fas fa-business-time mb-2" style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                <div>Belum ada data tracker KGB yang dicatat.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kgbList->hasPages())
            <div class="custom-pagination">
                @if ($kgbList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $kgbList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $kgbList->currentPage();
                    $last = $kgbList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $kgbList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $kgbList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $kgbList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($kgbList->hasMorePages())
                    <a href="{{ $kgbList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    {{-- TAB 4: CUTI & IZIN GTK --}}
    @elseif($tab === 'cuti')
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">No</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Pegawai</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis &amp; Keperluan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rentang Waktu</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Lampiran</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cutiList as $idx => $cuti)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="No" style="padding: 12px 18px; font-size: 0.88rem; color: var(--text-muted); text-align: center;">
                                {{ $cutiList->firstItem() + $idx }}
                            </td>
                            <td data-label="Nama Pegawai" style="padding: 12px 18px;">
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $cuti->gtk?->nama ?? '-' }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    NIP: {{ $cuti->gtk?->nip ?: '-' }}
                                </div>
                            </td>
                            <td data-label="Jenis & Keperluan" style="padding: 12px 18px;">
                                <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                    {{ $cuti->jenis }}
                                </span>
                                <div style="font-size: 0.84rem; color: var(--text-color); margin-top: 4px;">
                                    {{ $cuti->keperluan }}
                                </div>
                            </td>
                            <td data-label="Rentang Waktu" style="padding: 12px 18px;">
                                <div style="font-size: 0.84rem; color: var(--text-color);">
                                    <i class="far fa-calendar-alt text-muted me-1"></i> {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->format('d/m/Y') }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    Durasi: <strong>{{ $cuti->jumlah_hari }} Hari</strong>
                                </div>
                            </td>
                            <td data-label="Lampiran" style="padding: 12px 18px;">
                                @if($cuti->file_pendukung)
                                    <a href="{{ asset('storage/' . $cuti->file_pendukung) }}" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.78rem; border-radius: 6px;">
                                        <i class="fas fa-paperclip text-primary me-1"></i> Berkas
                                    </a>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">Tanpa Lampiran</span>
                                @endif
                            </td>
                            <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                <div class="table-actions" style="justify-content: center;">
                                    <a href="{{ route('dashboard.kepegawaian.cuti.cetak', $cuti->id) }}" target="_blank" class="btn-icon" style="color: #2563eb;" title="Cetak Lembar Cuti Resmi">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                <i class="fas fa-plane-departure mb-2" style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                <div>Belum ada data permohonan cuti / izin yang tercatat.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cutiList->hasPages())
            <div class="custom-pagination">
                @if ($cutiList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $cutiList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $cutiList->currentPage();
                    $last = $cutiList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $cutiList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $cutiList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $cutiList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($cutiList->hasMorePages())
                    <a href="{{ $cutiList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- MODAL 1: Upload Berkas Digital GTK -->
    <div class="modal-overlay" id="modalUploadBerkas" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-arrow-up text-primary"></i> Unggah Berkas Digital GTK
                </h4>
                <button type="button" id="btnCloseModalUpload" style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kepegawaian.berkas.upload') }}" method="POST" enctype="multipart/form-data" id="formUploadBerkas">
                @csrf
                <input type="hidden" name="tab_redirect" value="{{ $tab }}">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih GTK Pegawai <span style="color: #ef4444;">*</span></label>
                    <select name="ptk_id" id="upload_ptk_id" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        <option value="">-- Pilih Pegawai --</option>
                        @foreach($allGtk as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis Dokumen <span style="color: #ef4444;">*</span></label>
                    <select name="jenis_dokumen" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        @foreach($jenisBerkasOptions as $k => $lbl)
                            <option value="{{ $k }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Judul Dokumen / Nama Berkas <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="judul_dokumen" class="form-control" placeholder="Contoh: SK Kenaikan Pangkat Gol. IV/a" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih File Berkas (PDF, JPG, PNG, Maks 5MB) <span style="color: #ef4444;">*</span></label>
                    <input type="file" name="file_berkas" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan / Keterangan (Opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalUpload" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;"><i class="fas fa-upload me-1"></i> Unggah Berkas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Catat / Update KGB Tracker -->
    <div class="modal-overlay" id="modalKgb" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-business-time text-primary"></i> Catat / Perbarui Kenaikan Gaji Berkala (KGB)
                </h4>
                <button type="button" id="btnCloseModalKgb" style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kepegawaian.kgb.store') }}" method="POST" id="formKgb">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih GTK <span style="color: #ef4444;">*</span></label>
                    <select name="ptk_id" id="kgb_ptk_id" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        <option value="">-- Pilih GTK --</option>
                        @foreach($allGtk as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Masa Kerja (Tahun)</label>
                        <input type="number" name="mkg_tahun" id="kgb_mk_tahun" class="form-control" placeholder="Tahun" min="0" value="0" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Masa Kerja (Bulan)</label>
                        <input type="number" name="mkg_bulan" id="kgb_mk_bulan" class="form-control" placeholder="Bulan" min="0" max="11" value="0" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">TMT KGB Terakhir <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tmt_lama" id="kgb_tmt_lama" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">TMT Target (+2 Thn) <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tmt_baru_target" id="kgb_tmt_baru_target" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nomor SK Terakhir</label>
                        <input type="text" name="nomor_sk_terakhir" id="kgb_sk_terakhir" class="form-control" placeholder="Contoh: 822/012/2024" style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Status Usulan <span style="color: #ef4444;">*</span></label>
                        <select name="status_usulan" id="kgb_status_usulan" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                            <option value="belum_waktunya">Belum Waktunya</option>
                            <option value="siap_diajukan">Siap Diajukan ke BKD</option>
                            <option value="diproses">Sedang Diproses BKD</option>
                            <option value="terbit_sk">Terbit SK KGB</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan Tambahan</label>
                    <textarea name="catatan" id="kgb_catatan" class="form-control" rows="2" placeholder="Catatan berkas..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalKgb" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;"><i class="fas fa-save me-1"></i> Simpan Data KGB</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Form Cuti & Izin GTK -->
    <div class="modal-overlay" id="modalCuti" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plane-departure text-primary"></i> Permohonan Cuti / Izin Pegawai
                </h4>
                <button type="button" id="btnCloseModalCuti" style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kepegawaian.cuti.store') }}" method="POST" enctype="multipart/form-data" id="formCuti">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih Pegawai GTK <span style="color: #ef4444;">*</span></label>
                    <select name="ptk_id" id="cuti_ptk_id" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        <option value="">-- Pilih GTK --</option>
                        @foreach($allGtk as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis Permohonan <span style="color: #ef4444;">*</span></label>
                    <select name="jenis" id="cuti_jenis" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                        <option value="Cuti Tahunan">Cuti Tahunan</option>
                        <option value="Cuti Sakit">Cuti Sakit</option>
                        <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                        <option value="Cuti Alasan Penting">Cuti Alasan Penting</option>
                        <option value="Cuti Besar">Cuti Besar</option>
                        <option value="Izin">Izin Tidak Masuk</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Mulai <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tanggal_mulai" id="cuti_tgl_mulai" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Selesai <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tanggal_selesai" id="cuti_tgl_selesai" class="form-control" required style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Keperluan / Alasan <span style="color: #ef4444;">*</span></label>
                    <textarea name="keperluan" id="cuti_keperluan" class="form-control" rows="2" placeholder="Jelaskan alasan permohonan cuti atau izin..." required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Surat Keterangan Dokter / Pendukung (Opsional)</label>
                    <input type="file" name="surat_pendukung" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalCuti" class="btn btn-outline" style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem;"><i class="fas fa-check-circle me-1"></i> Simpan Permohonan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Rincian Lengkap Pegawai GTK (Biodata Lengkap) -->
    <div id="pegawaiModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box;">
        <div class="card modal-card-responsive" style="max-width: 650px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
                <div>
                    <h3 id="pegawaiModalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-id-card text-primary"></i> Rincian Lengkap Pegawai GTK
                    </h3>
                    <div id="pegawaiSubtitle" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Data induk kependidikan bersumber dari Dapodik</div>
                </div>
                <button type="button" onclick="closeBiodataPegawaiModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="pegawaiLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.5rem; color: var(--primary);"></i>
                <div style="margin-top: 8px; font-size: 0.84rem;">Memuat rincian data pegawai...</div>
            </div>

            <div id="pegawaiContent" class="modal-body-scroll" style="overflow-y: auto; flex: 1; min-height: 0; display: none; font-size: 0.84rem; padding-right: 4px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted); width: 150px;">Nama Lengkap</td><td id="pegawaiNama" style="font-weight: 700; color: var(--text-color);">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">NUPTK / NIP</td><td id="pegawaiNuptk" style="font-family: monospace; color: var(--primary); font-weight: 600;">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">NIK</td><td id="pegawaiNik" style="font-family: monospace;">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Jenis Kelamin</td><td id="pegawaiGender">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td><td id="pegawaiTtl">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Agama</td><td id="pegawaiAgama">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Status Kepegawaian</td><td id="pegawaiStatus">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Pendidikan Terakhir</td><td id="pegawaiPend">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Bidang Studi / Mapel</td><td id="pegawaiMapel">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Status Induk / Tgl Tugas</td><td id="pegawaiInduk">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">No. HP / Email</td><td id="pegawaiHp">-</td></tr>
                    <tr style="border-bottom: 1px solid var(--border-color);"><td style="padding: 8px 0; color: var(--text-muted);">Alamat Tempat Tinggal</td><td id="pegawaiAlamat">-</td></tr>
                </table>

                <div id="pegawaiBebanSection" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: 0.85rem;"><i class="fas fa-book-bookmark text-primary me-1"></i> Beban Jam Mengajar (KBM)</span>
                        <span id="pegawaiJmlJam" class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.72rem; padding: 2px 7px;">0 JP</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.80rem; margin-bottom: 0;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Rombel</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam/Mg</th>
                                </tr>
                            </thead>
                            <tbody id="pegawaiBebanList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-shrink: 0;">
                <button type="button" class="btn btn-outline" onclick="closeBiodataPegawaiModal()" style="padding: 7px 18px; font-size: 0.82rem; border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/kepegawaian.js') }}"></script>
@endpush
