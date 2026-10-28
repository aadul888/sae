@extends('layouts.dashboard')

@section('title', 'Peminjaman Alat — Laboratorium SAE')
@section('dash_title', 'Peminjaman Alat')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(14,165,233,0.12); color: var(--info, #0ea5e9); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Peminjaman Alat Laboratorium
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Sirkulasi peminjaman alat praktik khusus jurusan beserta pengembalian &amp; cek kondisi.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                @if ($canUpdate && $statTerlambat > 0)
                    <button type="button" class="btn btn-outline" id="btnTandaiTerlambat"
                        title="Tandai Semua Peminjaman Terlambat ({{ $statTerlambat }})"
                        aria-label="Tandai Semua Peminjaman Terlambat">
                        <i class="fas fa-clock-rotate-left" style="color: var(--danger, #ef4444);"></i>
                    </button>
                @endif
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnOpenImportPeminjaman"
                        title="Import Peminjaman via Excel" aria-label="Import Peminjaman via Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenPeminjaman"
                        title="Ajukan Peminjaman Baru" aria-label="Ajukan Peminjaman Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-clipboard-list"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Peminjaman</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-hourglass-half"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDiajukan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Menunggu Persetujuan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--info, #0ea5e9);"><i class="fas fa-hand-holding"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDipinjam, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Sedang Dipinjam</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-clock"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTerlambat, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Terlambat</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.peminjaman-alat.index') }}" style="margin: 0;">
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

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="tipe" id="filterTipe" class="toolbar-filter-select" style="height: 38px;" title="Filter Tipe Peminjam">
                                <option value="">Semua Peminjam</option>
                                @foreach ($tipeList as $k => $v)
                                    <option value="{{ $k }}" {{ $tipeFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            @if ($search || $statusFilter || $tipeFilter || $overdueOnly)
                                <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}" class="btn btn-outline"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}" placeholder="Cari nomor pinjam, peminjam, atau keperluan..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nomor / Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Peminjam</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Keperluan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Tenggat</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $p)
                            @php
                                $terlambat = $p->tanggal_kembali_rencana
                                    && in_array($p->status, ['dipinjam', 'terlambat'], true)
                                    && \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->isPast();
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nomor" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-family: var(--font-family-mono); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--primary);">{{ $p->nomor_pinjam }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Carbon\Carbon::parse($p->tanggal_pinjam)->translatedFormat('d M Y') }}</div>
                                    @if ($p->singkatan || $p->nama_jurusan)
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">{{ $p->singkatan ?: $p->nama_jurusan }}</span>
                                    @endif
                                </td>
                                <td data-label="Peminjam" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $p->peminjam_nama }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ $tipeList[$p->peminjam_tipe] ?? $p->peminjam_tipe }}
                                        @if ($p->peminjam_kelas) &bull; {{ $p->peminjam_kelas }} @endif
                                    </div>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">
                                        <i class="fas fa-boxes-stacked me-1"></i>{{ $p->jumlah_item }} item
                                    </div>
                                </td>
                                <td data-label="Keperluan" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ \Illuminate\Support\Str::limit($p->keperluan, 80) }}</div>
                                    @if ($p->ruang_lab_nama)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);"><i class="fas fa-location-dot me-1"></i>{{ $p->ruang_lab_nama }}</div>
                                    @endif
                                </td>
                                <td data-label="Tenggat" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm);">
                                    @if ($p->tanggal_kembali_rencana)
                                        <span style="font-weight: var(--font-bold); color: {{ $terlambat ? 'var(--danger, #ef4444)' : 'var(--text-color)' }};">
                                            {{ \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->translatedFormat('d M Y') }}
                                        </span>
                                        @if ($p->tanggal_kembali_aktual)
                                            <div style="font-size: var(--text-2xs); color: var(--success, #10b981);">
                                                Kembali: {{ \Carbon\Carbon::parse($p->tanggal_kembali_aktual)->format('d/m/Y') }}
                                            </div>
                                        @elseif ($terlambat)
                                            <div style="font-size: var(--text-2xs); color: var(--danger, #ef4444);">
                                                Lewat {{ \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->diffInDays(now()) }} hari
                                            </div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($p->status) {
                                            'dikembalikan' => 'badge-success',
                                            'dipinjam' => 'badge-info',
                                            'disetujui' => 'badge-primary',
                                            'terlambat' => 'badge-danger',
                                            'ditolak' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$p->status] ?? $p->status }}</span>
                                    @if ($p->kondisi_kembali)
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-2);">{{ $kondisiList[$p->kondisi_kembali] ?? $p->kondisi_kembali }}</div>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        <a href="{{ route('dashboard.laboran.peminjaman-alat.show', $p->id) }}" class="btn-icon"
                                            title="Buka Detail" style="text-decoration: none;">
                                            <i class="fas fa-eye" style="color: var(--primary);"></i>
                                        </a>
                                        @if ($canUpdate && $p->status === 'diajukan')
                                            <button type="button" class="btn-icon btn-setujui-pinjam"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                                title="Setujui Peminjaman">
                                                <i class="fas fa-check" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canUpdate && in_array($p->status, ['disetujui'], true))
                                            <button type="button" class="btn-icon btn-serah-terima"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                                title="Serah Terima (Alat Dipinjam)">
                                                <i class="fas fa-hand-holding" style="color: var(--info, #0ea5e9);"></i>
                                            </button>
                                        @endif
                                        @if ($canUpdate && in_array($p->status, ['dipinjam', 'terlambat'], true))
                                            <button type="button" class="btn-icon btn-kembalikan-pinjam"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->peminjam_nama }}"
                                                title="Proses Pengembalian">
                                                <i class="fas fa-rotate-left" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-pinjam"
                                                data-id="{{ $p->id }}" data-nama="{{ $p->nomor_pinjam }}"
                                                title="Hapus Peminjaman" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-hand-holding mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada data peminjaman alat.</div>
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

        @include('dashboard.laboran.partials.peminjaman-modal-ajukan')

        @include('dashboard.laboran.partials.peminjaman-modal-kembali')

        @include('dashboard.laboran.partials.peminjaman-modal-import')
    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-peminjaman.js') }}?v={{ file_exists(public_path('js/laboran-peminjaman.js')) ? filemtime(public_path('js/laboran-peminjaman.js')) : time() }}"></script>
        @endpush
    @endsection
