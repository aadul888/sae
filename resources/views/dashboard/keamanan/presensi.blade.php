@extends('layouts.dashboard')

@section('title', 'Monitoring Presensi Siswa Gerbang - Keamanan')

@section('content')
<div class="content-wrapper">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-id-card-alt"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Monitoring Presensi Siswa Gerbang
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Pemantauan kehadiran dan keluar-masuk murid secara realtime di pos keamanan/gerbang sekolah.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.keamanan.presensi.index') }}" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;" title="Refresh Data">
                <i class="fas fa-sync"></i>
                <span>Refresh Data</span>
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

    {{-- Filter Bar --}}
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.keamanan.presensi.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama siswa, NISN, atau kelas..." style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%;">
            </div>
            <div style="min-width: 150px;">
                <select name="status" class="form-control" style="width: 100%;">
                    <option value="">Semua Status</option>
                    <option value="H" {{ $status === 'H' ? 'selected' : '' }}>H - Hadir</option>
                    <option value="T" {{ $status === 'T' ? 'selected' : '' }}>T - Terlambat</option>
                    <option value="I" {{ $status === 'I' ? 'selected' : '' }}>I - Izin</option>
                    <option value="S" {{ $status === 'S' ? 'selected' : '' }}>S - Sakit</option>
                    <option value="D" {{ $status === 'D' ? 'selected' : '' }}>D - Dispensasi</option>
                    <option value="A" {{ $status === 'A' ? 'selected' : '' }}>A - Alpha</option>
                </select>
            </div>
            <div style="min-width: 180px;">
                <select name="rombel_id" class="form-control" style="width: 100%;">
                    <option value="">Semua Rombel/Kelas</option>
                    @foreach ($rombelList as $r)
                        <option value="{{ $r->rombongan_belajar_id }}" {{ $rombelId == $r->rombongan_belajar_id ? 'selected' : '' }}>
                            {{ $r->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('dashboard.keamanan.presensi.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
        </form>
    </div>

    {{-- Datatable Container --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Siswa</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kelas / Rombel</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jam Masuk</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jam Pulang</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Metode Scan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px;">
                            <div style="font-weight: 600; color: var(--text-heading);">{{ $row->nama_siswa ?? '-' }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">NISN: {{ $row->pd_nisn ?? $row->nisn ?? '-' }}</div>
                        </td>
                        <td style="padding: 12px 18px;">
                            <span style="font-weight: 600; color: var(--text-heading);">{{ $row->nama_rombel ?? '-' }}</span>
                        </td>
                        <td style="padding: 12px 18px;">
                            @if ($row->status === 'H')
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Hadir</span>
                            @elseif ($row->status === 'T')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                                    Terlambat {{ $row->menit_terlambat ? "({$row->menit_terlambat}m)" : '' }}
                                </span>
                            @elseif ($row->status === 'I')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Izin</span>
                            @elseif ($row->status === 'S')
                                <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3);">Sakit</span>
                            @elseif ($row->status === 'D')
                                <span class="badge" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.3);">Dispensasi</span>
                            @else
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Alpha</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.85rem;">
                            @if ($row->jam_masuk)
                                <span style="font-weight: 600; color: var(--text-heading);">{{ substr($row->jam_masuk, 0, 5) }}</span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.85rem;">
                            @if ($row->jam_pulang)
                                <span style="font-weight: 600; color: var(--text-heading);">{{ substr($row->jam_pulang, 0, 5) }}</span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.8rem; color: var(--text-muted);">
                            {{ strtoupper($row->metode_masuk ?? '-') }}
                        </td>
                        <td style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-muted); max-width: 200px;">
                            {{ $row->keterangan ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.4;"></i>
                            <p style="margin: 0;">Tidak ada rekaman presensi pada tanggal dan filter ini.</p>
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
@endsection
