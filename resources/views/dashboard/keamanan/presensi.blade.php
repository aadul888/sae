@extends('layouts.dashboard')

@section('title', 'Monitoring Presensi Siswa Gerbang - Keamanan')

@section('content')
    <div class="content-wrapper">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-id-card-alt"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Monitoring Presensi Siswa Gerbang
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pemantauan kehadiran dan keluar-masuk murid secara realtime di pos keamanan/gerbang sekolah.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.keamanan.presensi.index') }}" class="btn btn-secondary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                    title="Refresh Data Presensi Satpam">
                    <i class="fas fa-sync"></i>
                </a>
            </div>
        </div>

        <!-- 2. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['totalSiswa'] }}</div>
                    <div class="dash-stat-label">Total Siswa Aktif</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['hadir'] }}</div>
                    <div class="dash-stat-label">Hadir Tepat Waktu</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['terlambat'] }}</div>
                    <div class="dash-stat-label">Terlambat</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: #6366f1;">
                    <i class="fas fa-clipboard-user"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['izin'] }}</div>
                    <div class="dash-stat-label">Izin / Sakit / Dispen</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: #06b6d4;">
                    <i class="fas fa-house-user"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $stats['pulang'] }}</div>
                    <div class="dash-stat-label">Sudah Pulang</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Keamanan Presensi (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            {{-- 1. Toolbar Header (Entries, Filters & Live Search) --}}
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.keamanan.presensi.index') }}" id="filterForm"
                    style="margin: 0;">
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 20, 25, 50, 100] as $n)
                                        <option value="{{ $n }}"
                                            {{ request('perPage', 20) == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                                style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px;">

                            <select name="status" class="toolbar-filter-select" style="height: 38px; min-width: 140px;">
                                <option value="">Semua Status</option>
                                <option value="H" {{ $status === 'H' ? 'selected' : '' }}>H - Hadir</option>
                                <option value="T" {{ $status === 'T' ? 'selected' : '' }}>T - Terlambat</option>
                                <option value="I" {{ $status === 'I' ? 'selected' : '' }}>I - Izin</option>
                                <option value="S" {{ $status === 'S' ? 'selected' : '' }}>S - Sakit</option>
                                <option value="D" {{ $status === 'D' ? 'selected' : '' }}>D - Dispensasi</option>
                                <option value="A" {{ $status === 'A' ? 'selected' : '' }}>A - Alpha</option>
                            </select>

                            <select name="rombel_id" class="toolbar-filter-select" style="height: 38px; min-width: 170px;">
                                <option value="">Semua Rombel/Kelas</option>
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}"
                                        {{ $rombelId == $r->rombongan_belajar_id ? 'selected' : '' }}>
                                        {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($search || $status || $rombelId || ($tanggal && $tanggal !== date('Y-m-d')))
                                <a href="{{ route('dashboard.keamanan.presensi.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" id="searchPresensi" value="{{ $search }}"
                                placeholder="Cari nama siswa, NISN, kelas..." autocomplete="off">
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
                                Siswa</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kelas / Rombel</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jam Masuk</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jam Pulang</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Metode Scan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $row)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 18px;">
                                    <div style="font-weight: 600; color: var(--text-heading);">
                                        {{ $row->nama_siswa ?? '-' }}
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">NISN:
                                        {{ $row->pd_nisn ?? ($row->nisn ?? '-') }}</div>
                                </td>
                                <td style="padding: 12px 18px;">
                                    <span
                                        style="font-weight: 600; color: var(--text-heading);">{{ $row->nama_rombel ?? '-' }}</span>
                                </td>
                                <td style="padding: 12px 18px;">
                                    @if ($row->status === 'H')
                                        <span class="badge"
                                            style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Hadir</span>
                                    @elseif ($row->status === 'T')
                                        <span class="badge"
                                            style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                                            Terlambat {{ $row->menit_terlambat ? "({$row->menit_terlambat}m)" : '' }}
                                        </span>
                                    @elseif ($row->status === 'I')
                                        <span class="badge"
                                            style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Izin</span>
                                    @elseif ($row->status === 'S')
                                        <span class="badge"
                                            style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3);">Sakit</span>
                                    @elseif ($row->status === 'D')
                                        <span class="badge"
                                            style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.3);">Dispensasi</span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Alpha</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-base, 0.875rem);">
                                    @if ($row->jam_masuk)
                                        <span
                                            style="font-weight: 600; color: var(--text-heading);">{{ substr($row->jam_masuk, 0, 5) }}</span>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-base, 0.875rem);">
                                    @if ($row->jam_pulang)
                                        <span
                                            style="font-weight: 600; color: var(--text-heading);">{{ substr($row->jam_pulang, 0, 5) }}</span>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    {{ strtoupper($row->metode_masuk ?? '-') }}
                                </td>
                                <td
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); max-width: 200px;">
                                    {{ $row->keterangan ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fas fa-calendar-times"
                                        style="font-size: var(--text-3xl, 1.875rem); margin-bottom: 8px; opacity: 0.4;"></i>
                                    <p style="margin: 0;">Tidak ada rekaman presensi pada tanggal dan filter ini.</p>
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
    @endsection
