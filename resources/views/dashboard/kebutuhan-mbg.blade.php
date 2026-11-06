@extends('layouts.dashboard')

@section('title', 'Data Kebutuhan MBG — SAE (Sistem Aplikasi Edukasi)')
@section('dash_title', 'Data Kebutuhan MBG')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: var(--space-6);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59, 130, 246, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl); flex-shrink: 0;">
                <i class="fas fa-utensils"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl); font-weight: 800; color: var(--text-main); margin: 0 0 2px 0;">
                    Data Kebutuhan MBG
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm); margin: 0;">
                    Penarikan data penerima manfaat Makan Bergizi Gratis (MBG) sesuai template resmi 6-Sheet.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.kebutuhan-mbg.index', ['tab' => $tab]) }}"
                class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                title="Muat Ulang Data"
                aria-label="Muat Ulang Data">
                <i class="fas fa-rotate"></i>
            </a>

            @if ($canExport)
                <a href="{{ route('dashboard.kebutuhan-mbg.export') }}"
                    class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                    title="Tarik &amp; Unduh File Excel MBG Lengkap (6 Sheet)"
                    aria-label="Tarik &amp; Unduh File Excel MBG Lengkap (6 Sheet)">
                    <i class="fas fa-file-arrow-down"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: var(--space-6) var(--space-8); border-radius: var(--radius-lg); margin-bottom: var(--space-8); font-size: var(--text-sm); display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-circle-check"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger); padding: var(--space-6) var(--space-8); border-radius: var(--radius-lg); margin-bottom: var(--space-8); font-size: var(--text-sm); display: flex; align-items: center; gap: var(--space-4);">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Ringkasan Penerima MBG (Tepat 4 Card Baku SAE) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.12); color: var(--primary);">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_penerima'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Penerima MBG</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--success);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? (($stats['kelas_10'] ?? 0) + ($stats['kelas_11'] ?? 0) + ($stats['kelas_12'] ?? 0))) }}</div>
                <div class="dash-stat-label">Total Siswa (Peserta Didik)</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['gtk_total'] ?? 0) }}</div>
                <div class="dash-stat-label">Guru &amp; Tendik (GTK)</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.12); color: var(--purple);">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-base); font-weight: 800;">
                    X: {{ number_format($stats['kelas_10'] ?? 0) }} &bull; XI: {{ number_format($stats['kelas_11'] ?? 0) }} &bull; XII: {{ number_format($stats['kelas_12'] ?? 0) }}
                </div>
                <div class="dash-stat-label">Rincian Siswa Per Tingkat</div>
            </div>
        </div>
    </div>

    <!-- 4. Tab Navigasi Baku (Sesuai Sheet Template MBG) -->
    <div class="periode-nav-wrapper" style="margin-bottom: var(--space-8);">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kebutuhan-mbg.index', ['tab' => 'guru_tendik', 'per_page' => $perPage]) }}"
                class="periode-nav-tab {{ $tab === 'guru_tendik' ? 'active' : '' }}">
                <i class="fas fa-chalkboard-user"></i> Guru &amp; Tendik
                <span style="opacity: 0.75; font-size: var(--text-2xs); margin-left: var(--space-4);">({{ $stats['gtk_total'] ?? 0 }})</span>
            </a>
            <a href="{{ route('dashboard.kebutuhan-mbg.index', ['tab' => 'kelas_10', 'per_page' => $perPage]) }}"
                class="periode-nav-tab {{ $tab === 'kelas_10' ? 'active' : '' }}">
                <i class="fas fa-user-group"></i> Kelas 10
                <span style="opacity: 0.75; font-size: var(--text-2xs); margin-left: var(--space-4);">({{ $stats['kelas_10'] ?? 0 }})</span>
            </a>
            <a href="{{ route('dashboard.kebutuhan-mbg.index', ['tab' => 'kelas_11', 'per_page' => $perPage]) }}"
                class="periode-nav-tab {{ $tab === 'kelas_11' ? 'active' : '' }}">
                <i class="fas fa-user-group"></i> Kelas 11
                <span style="opacity: 0.75; font-size: var(--text-2xs); margin-left: var(--space-4);">({{ $stats['kelas_11'] ?? 0 }})</span>
            </a>
            <a href="{{ route('dashboard.kebutuhan-mbg.index', ['tab' => 'kelas_12', 'per_page' => $perPage]) }}"
                class="periode-nav-tab {{ $tab === 'kelas_12' ? 'active' : '' }}">
                <i class="fas fa-user-group"></i> Kelas 12
                <span style="opacity: 0.75; font-size: var(--text-2xs); margin-left: var(--space-4);">({{ $stats['kelas_12'] ?? 0 }})</span>
            </a>
        </div>
    </div>

    <!-- 5. Toolbar Tabel (Entries, Filter JK, Reset & Live Search) -->
    <div class="card" style="padding: var(--space-8); margin-bottom: var(--space-8);">
        <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select" data-tab="{{ $tab }}">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <select id="filterJk" class="toolbar-filter-select" data-tab="{{ $tab }}" style="padding: 6px 12px; font-size: var(--text-sm); border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main);">
                    <option value="">Semua Jenis Kelamin</option>
                    <option value="392" {{ $jk === '392' ? 'selected' : '' }}>Laki-laki (392)</option>
                    <option value="64" {{ $jk === '64' ? 'selected' : '' }}>Perempuan (64)</option>
                </select>

                @if (!empty($q) || !empty($jk))
                    <a href="{{ route('dashboard.kebutuhan-mbg.index', ['tab' => $tab, 'per_page' => $perPage]) }}"
                        class="btn btn-outline" style="padding: 6px 12px; font-size: var(--text-sm); border-radius: var(--radius-md);"
                        title="Reset Filter" aria-label="Reset Filter">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                @endif
            </div>

            <div style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" placeholder="Cari nama, NIK, NISN..." value="{{ $q ?? '' }}" autocomplete="off" data-tab="{{ $tab }}">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @if ($canExport)
                    <a href="{{ route('dashboard.kebutuhan-mbg.export') }}"
                        class="btn btn-primary"
                        style="padding: 7px 14px; font-size: var(--text-sm); border-radius: var(--radius-md); display: inline-flex; align-items: center; gap: var(--space-4);"
                        title="Unduh format resmi Excel MBG">
                        <i class="fas fa-file-excel"></i>
                        <span>Tarik Excel MBG</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- 6. Datatable Sesuai Format 8 Kolom MBG -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: var(--space-10);">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 50px;">No</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NIK</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Penerima</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Tanggal Lahir</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Jenis Kelamin</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Orang Tua</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Posisi</th>
                    <th style="padding: 12px 14px; font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Status Menerima</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs); color: var(--text-muted);">
                            {{ $list->firstItem() + $index }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-family: monospace;">
                            {{ $item->nisn }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-family: monospace;">
                            {{ $item->nik }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); font-weight: 600; color: var(--text-main);">
                            {{ $item->nama }}
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); text-align: center;">
                            {{ $item->tanggal_lahir }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);">
                            <span class="badge" style="background: {{ $item->jenis_kelamin_id === 392 ? 'rgba(59, 130, 246, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $item->jenis_kelamin_id === 392 ? 'var(--primary)' : 'var(--danger)' }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                                {{ $item->jenis_kelamin }} ({{ $item->jenis_kelamin_id }})
                            </span>
                        </td>
                        <td style="padding: 12px 14px; font-size: var(--text-sm); color: var(--text-main);">
                            {{ $item->nama_orang_tua }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);">
                            @php
                                $badgeBg = match ($item->posisi) {
                                    1 => 'rgba(16, 185, 129, 0.15)',
                                    2 => 'rgba(59, 130, 246, 0.15)',
                                    3 => 'rgba(245, 158, 11, 0.15)',
                                    default => 'rgba(107, 114, 128, 0.15)',
                                };
                                $badgeColor = match ($item->posisi) {
                                    1 => 'var(--success)',
                                    2 => 'var(--primary)',
                                    3 => 'var(--warning)',
                                    default => 'var(--text-muted)',
                                };
                            @endphp
                            <span class="badge" style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; padding: 3px 8px; border-radius: var(--radius-pill);">
                                {{ $item->posisi_label }} (ID: {{ $item->posisi }})
                            </span>
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: var(--text-xs);">
                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 3px 8px; border-radius: var(--radius-pill);">
                                <i class="fas fa-check" style="font-size: var(--text-2xs); margin-right: 3px;"></i> true
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: var(--space-12) var(--space-8); text-align: center; color: var(--text-muted);">
                            <i class="fas fa-folder-open" style="font-size: var(--text-2xl); opacity: 0.4; margin-bottom: var(--space-4); display: block;"></i>
                            <div style="font-size: var(--text-base); font-weight: 600; color: var(--text-main); margin-bottom: var(--space-2);">Tidak ada data penerima MBG</div>
                            <div style="font-size: var(--text-sm);">Coba periksa kembali kata kunci pencarian atau filter yang diterapkan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 7. Custom Pagination Resmi SAE -->
    @if ($list->hasPages())
        <div class="custom-pagination" style="margin-bottom: var(--space-10);">
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
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif

            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Berikutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/kebutuhan-mbg.js') }}"></script>
@endpush
