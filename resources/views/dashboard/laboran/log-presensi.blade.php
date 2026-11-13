@extends('layouts.dashboard')

@section('title', 'Log Presensi — Laboratorium SAE')
@section('dash_title', 'Log Presensi')

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    @include('dashboard.laboran.partials.header-actions')

    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(99,102,241,0.12); color: var(--primary, #6366f1); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-list-check"></i>
                </div>
                <div>
                    <h2
                        style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Log Presensi Harian
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Catatan kehadiran peserta didik jurusan
                        <strong>{{ $context['jurusan_nama'] ?: ($context['jurusan_id'] ?: 'Anda') }}</strong>
                        &mdash; hasil tap RFID / QR / input manual. Data baca-saja.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <span class="badge badge-{{ $scopeBadge['tone'] }}"
                    title="{{ $scopeBadge['label'] }}">
                    <i class="fas {{ $scopeBadge['icon'] }} me-1"></i>{{ $scopeBadge['label'] }}
                </span>
            </div>
        </div>

        {{-- 2. Ringkasan tanggal terpilih --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary, #6366f1);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Siswa (Hadir: {{ $stats['persen'] }}%)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="color: var(--success, #10b981);">
                        {{ number_format($stats['hadir'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Hadir Tepat Waktu</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="color: var(--warning, #f59e0b);">
                        {{ number_format($stats['terlambat'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Terlambat Masuk</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-file-circle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="color: var(--danger, #ef4444);">
                        {{ number_format($stats['izin'] + $stats['sakit'] + $stats['alpha'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">
                        Tidak Hadir (I: {{ $stats['izin'] }} &bull; S: {{ $stats['sakit'] }} &bull; A: {{ $stats['alpha'] }})
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                                    </option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <input type="date" id="filterTanggal" name="tanggal" value="{{ $tanggal }}"
                            class="form-control" title="Pilih tanggal presensi"
                            style="height: 38px; padding: 0 10px; font-size: var(--text-sm); border-radius: var(--radius-md, 8px); width: auto;">

                        <select name="rombel_id" id="filterRombel" class="toolbar-filter-select"
                            style="min-width: 150px; height: 38px;" title="Filter kelas / rombel">
                            <option value="">Semua Kelas</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ $rombelId === (string) $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>

                        <select name="status" id="filterStatus" class="toolbar-filter-select"
                            style="min-width: 140px; height: 38px;" title="Filter status kehadiran">
                            <option value="">Semua Status</option>
                            <option value="H" {{ $status === 'H' ? 'selected' : '' }}>Hadir Tepat Waktu</option>
                            <option value="T" {{ $status === 'T' ? 'selected' : '' }}>Terlambat</option>
                            <option value="I" {{ $status === 'I' ? 'selected' : '' }}>Izin</option>
                            <option value="S" {{ $status === 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="D" {{ $status === 'D' ? 'selected' : '' }}>Dispen</option>
                            <option value="A" {{ $status === 'A' ? 'selected' : '' }}>Alpha</option>
                        </select>

                        @if ($q || $rombelId || $status || $tanggal !== now()->toDateString())
                            <a href="{{ route('dashboard.laboran.log-presensi') }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" aria-label="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q" value="{{ $q }}"
                            placeholder="Cari nama / NISN..." autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd table-presensi-log"
                    style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
                            @foreach ([
            ['nama_siswa', 'Nama Peserta Didik', ''],
            ['nama_rombel', 'Rombel', ''],
            ['jam_masuk', 'Jam Masuk', ''],
            ['jam_pulang', 'Jam Pulang', ''],
            ['status', 'Status', 'center'],
        ] as [$key, $label, $align])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}" data-sort="{{ $key }}"
                                    style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; cursor: pointer; {{ $align === 'center' ? 'text-align: center;' : '' }}">
                                    {{ $label }}
                                    <span
                                        class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Keterangan</th>
                            <th
                                style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Snapshot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $index => $log)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="cell-log-no" style="padding: var(--space-6); text-align: center; color: var(--text-muted);"
                                    data-label="No">
                                    {{ $logs->firstItem() + $index }}
                                </td>
                                <td class="cell-log-siswa" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Nama Lengkap">
                                    <div class="log-siswa-wrapper"
                                        style="display: flex; align-items: center; gap: var(--space-5);">
                                        <img src="{{ $log->foto_profil_url ?: asset('img/logo-dark.png') }}"
                                            alt="{{ $log->nama_siswa }}" class="log-avatar"
                                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0; {{ $log->foto_profil_url ? 'cursor: pointer;' : '' }}"
                                            onerror="this.src='/img/logo-dark.png';">
                                        <div>
                                            <div
                                                style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                                {{ $log->nama_siswa }}</div>
                                            <div
                                                style="font-size: var(--text-xs); color: var(--text-muted); font-family: var(--font-family-mono); margin-top: 2px;">
                                                NISN: {{ $log->nisn_siswa }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-log-rombel" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Rombel Kelas">
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-sm); padding: 3px 8px;">{{ $log->nama_rombel ?: '-' }}</span>
                                </td>
                                <td class="cell-log-masuk" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Presensi Masuk">
                                    @if ($log->jam_masuk)
                                        <div
                                            style="font-weight: var(--font-bold); font-family: var(--font-family-mono); color: var(--text-color); font-size: var(--text-base);">
                                            {{ substr($log->jam_masuk, 0, 5) }} WIB
                                        </div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            <i class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_masuk ?: 'manual') }}
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td class="cell-log-pulang" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Presensi Pulang">
                                    @if ($log->jam_pulang)
                                        <div
                                            style="font-weight: var(--font-bold); font-family: var(--font-family-mono); color: var(--text-color); font-size: var(--text-base);">
                                            {{ substr($log->jam_pulang, 0, 5) }} WIB
                                        </div>
                                        <div style="font-size: var(--text-xs); margin-top: 2px;">
                                            @if ($log->status_ketepatan_pulang === 'pulang_cepat')
                                                <span class="badge badge-warning"
                                                    style="font-size: var(--text-2xs); padding: 1px 6px;">
                                                    <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                                </span>
                                            @else
                                                <span style="color: var(--text-muted);"><i
                                                        class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_pulang ?: 'manual') }}</span>
                                            @endif
                                        </div>
                                    @elseif ($log->status_ketepatan_pulang === 'pulang_cepat')
                                        <span class="badge badge-warning" style="font-size: var(--text-xs); padding: 2px 7px;">
                                            <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td class="cell-log-status"
                                    style="padding: var(--space-6) var(--space-7); text-align: center;"
                                    data-label="Status Kehadiran">
                                    @if ($log->status === 'H')
                                        <span class="badge badge-success" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-check-circle me-1"></i> Hadir</span>
                                    @elseif ($log->status === 'T')
                                        <span class="badge badge-warning" style="font-size: var(--text-xs); padding: 3px 8px;"
                                            title="Terlambat {{ $log->menit_terlambat }} menit">
                                            <i class="fas fa-clock me-1"></i> +{{ $log->menit_terlambat }}m
                                        </span>
                                    @elseif ($log->status === 'I')
                                        <span class="badge badge-primary" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-file-signature me-1"></i> Izin</span>
                                    @elseif ($log->status === 'S')
                                        <span class="badge badge-info" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-notes-medical me-1"></i> Sakit</span>
                                    @elseif ($log->status === 'D')
                                        <span class="badge badge-accent" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-award me-1"></i> Dispen</span>
                                    @else
                                        <span class="badge badge-danger" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-times-circle me-1"></i> Alpha</span>
                                    @endif
                                </td>
                                <td class="cell-log-keterangan" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Keterangan">
                                    <div style="max-width: 220px; font-size: var(--text-sm); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        title="{{ $log->keterangan }}">
                                        {{ $log->keterangan ?: '-' }}
                                    </div>
                                </td>
                                <td class="cell-log-snapshot"
                                    style="padding: var(--space-6) var(--space-7); text-align: right;"
                                    data-label="Snapshot Foto">
                                    <div class="snapshot-actions"
                                        style="display: flex; justify-content: flex-end; gap: var(--space-3);">
                                        @if ($log->foto_masuk_url)
                                            <button type="button" class="btn btn-outline btn-preview-snapshot"
                                                data-url="{{ $log->foto_masuk_url }}"
                                                data-title="Snapshot Masuk — {{ $log->nama_siswa }}"
                                                style="padding: 5px 9px; font-size: var(--text-xs);"
                                                title="Lihat snapshot masuk">
                                                <i class="fas fa-camera text-primary"></i> Masuk
                                            </button>
                                        @endif
                                        @if ($log->foto_pulang_url)
                                            <button type="button" class="btn btn-outline btn-preview-snapshot"
                                                data-url="{{ $log->foto_pulang_url }}"
                                                data-title="Snapshot Pulang — {{ $log->nama_siswa }}"
                                                style="padding: 5px 9px; font-size: var(--text-xs);"
                                                title="Lihat snapshot pulang">
                                                <i class="fas fa-camera text-success"></i> Pulang
                                            </button>
                                        @endif
                                        @if (!$log->foto_masuk_url && !$log->foto_pulang_url)
                                            <span style="color: var(--text-muted); font-size: var(--text-sm);">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="padding: var(--space-11); text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    <i class="fas fa-clipboard-question mb-2"
                                        style="font-size: var(--text-3xl); opacity: 0.4;"></i>
                                    <div>Belum ada data presensi yang tercatat untuk filter tanggal dan kelas ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $logs])
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== MODAL SNAPSHOT FOTO ===================== --}}
    <div id="modalFotoSnapshot" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 640px; width: 94%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px; background: var(--bg-card); border: 1px solid var(--border-color);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <h4 id="fotoSnapshotTitle"
                    style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi</h4>
                <button type="button" id="btnCloseFotoModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg);"
                    aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow: auto; text-align: center;">
                <img id="imgFotoSnapshot" src="" alt="Snapshot presensi"
                    style="max-width: 100%; max-height: 70vh; border-radius: var(--radius-lg, 12px);">
            </div>
        </div>
    </div>

    @push('scripts')
        <script
            src="{{ asset('js/laboran-log-presensi.js') }}?v={{ file_exists(public_path('js/laboran-log-presensi.js')) ? filemtime(public_path('js/laboran-log-presensi.js')) : time() }}"></script>
    @endpush
@endsection
