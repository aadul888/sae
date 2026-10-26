@extends('layouts.dashboard')

@section('title', 'Jadwal Praktik & Penggunaan Lokasi — Laboratorium SAE')
@section('dash_title', 'Jadwal Praktik')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Buttons --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Jadwal Praktik &amp; Penggunaan Lokasi
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Penjadwalan guru untuk praktik di laboratorium, bengkel, lahan/kebun, dan lapangan sekolah.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge"
                    style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] ?? 'fa-graduation-cap' }}"></i> {{ $scopeBadge['label'] ?? 'Semua Jurusan' }}
                </span>
                @if ($canRead)
                    <a href="{{ route('dashboard.laboran.laporan.index') }}" class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                        title="Laporan Penggunaan Lokasi Praktik (Bulan, Triwulan, Semester, Tahun)">
                        <i class="fas fa-file-lines"></i>
                    </a>
                @endif
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenModalJadwal"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Jadwalkan Praktik Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Sesi Terjadwal</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statHariIni, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Praktik Hari Ini</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBerlangsung, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Sedang Berlangsung</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statSelesai, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Praktik Selesai</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.jadwal.index') }}" id="filterForm" style="margin: 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Jenis Lokasi">
                                <option value="">Semua Jenis Lokasi</option>
                                @foreach ($jenisLokasiLabels ?? \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI as $jKey => $jLabel)
                                    <option value="{{ $jKey }}" {{ $jenisFilter === $jKey ? 'selected' : '' }}>
                                        {{ $jLabel }}</option>
                                @endforeach
                            </select>

                            <select name="lab" id="filterLab" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Lokasi Praktik">
                                <option value="">Semua Lokasi</option>
                                @foreach ($daftarLab as $lab)
                                    <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>
                                        {{ $lab }}</option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Status Sesi">
                                <option value="">Semua Status</option>
                                @foreach (\App\Http\Controllers\LaboranJadwalController::STATUS_LIST as $sKey => $sLabel)
                                    <option value="{{ $sKey }}" {{ $statusFilter === $sKey ? 'selected' : '' }}>
                                        {{ $sLabel }}</option>
                                @endforeach
                            </select>

                            <input type="date" name="tanggal" id="filterTanggal" value="{{ $tanggalFilter }}"
                                class="form-control"
                                style="height: 38px; font-size: var(--text-sm); padding: 0 var(--space-5); border-radius: var(--radius-md);"
                                title="Filter Tanggal Praktik">

                            @if ($search || $labFilter || $jenisFilter || $statusFilter || $tanggalFilter)
                                <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari mapel, topik, guru, kelas, atau lokasi..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                No</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Lokasi &amp; Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Mapel &amp; Topik</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Guru &amp; Kelas</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Waktu Sesi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Status</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            @php
                                $jenisKey = $item->jenis_lokasi ?: 'lainnya';
                                $jenisLabel = \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI[$jenisKey] ?? 'Lokasi Lainnya';
                                $jenisIcon = match ($jenisKey) {
                                    'lab' => 'fa-flask',
                                    'bengkel' => 'fa-screwdriver-wrench',
                                    'lahan' => 'fa-seedling',
                                    'lapangan' => 'fa-futbol',
                                    default => 'fa-location-dot',
                                };
                                $jenisColor = match ($jenisKey) {
                                    'lab' => 'var(--primary, #3b82f6)',
                                    'bengkel' => 'var(--warning, #f59e0b)',
                                    'lahan' => 'var(--success, #10b981)',
                                    'lapangan' => 'var(--purple, #8b5cf6)',
                                    default => 'var(--text-muted)',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-7) var(--space-8); font-size: var(--text-base); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Lokasi & Tanggal" style="padding: var(--space-7) var(--space-8);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $item->ruang_lab_nama }}
                                    </div>
                                    <div style="font-size: var(--text-xs); color: {{ $jenisColor }}; font-weight: var(--font-semibold); margin-top: var(--space-1);">
                                        <i class="fas {{ $jenisIcon }} me-1"></i>{{ $jenisLabel }}
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                                        <i class="fas fa-calendar-day text-primary me-1"></i>
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                    </div>
                                </td>
                                <td data-label="Mapel & Topik" style="padding: var(--space-7) var(--space-8);">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $item->mata_pelajaran }}
                                    </div>
                                    @if (!empty($item->topik_praktik))
                                        <div style="font-size: var(--text-sm); color: var(--primary); font-weight: var(--font-medium);">
                                            {{ $item->topik_praktik }}
                                        </div>
                                    @else
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); font-style: italic;">
                                            Topik belum diisi
                                        </div>
                                    @endif
                                    @if (!empty($item->alat_bahan_digunakan))
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                                            <i class="fas fa-toolbox me-1"></i>{{ \Illuminate\Support\Str::limit($item->alat_bahan_digunakan, 60) }}
                                        </div>
                                    @endif
                                    @if (!empty($item->laporan_kerusakan))
                                        <div style="font-size: var(--text-xs); color: var(--danger, #ef4444); margin-top: var(--space-1);">
                                            <i class="fas fa-circle-exclamation me-1"></i>
                                            {{ \Illuminate\Support\Str::limit($item->laporan_kerusakan, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Guru & Kelas" style="padding: var(--space-7) var(--space-8); font-size: var(--text-base);">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color);">
                                        {{ $item->guru_nama ?: '-' }}
                                    </div>
                                    @if (!empty($item->guru_pendamping))
                                        <div style="font-size: var(--text-xs); color: var(--primary); margin-top: 2px;" title="Guru Pendamping: {{ $item->guru_pendamping }}">
                                            <i class="fas fa-users me-1"></i>+ {{ $item->guru_pendamping }}
                                        </div>
                                    @endif
                                    <div style="margin-top: var(--space-2); display: flex; gap: var(--space-2); flex-wrap: wrap;">
                                        <span class="badge badge-outline" style="font-size: var(--text-xs);">
                                            {{ $item->rombel_nama ?: 'Seluruh Siswa' }}
                                        </span>
                                        @if (!empty($item->pemberlakuan) && $item->pemberlakuan !== 'sekali')
                                            <span class="badge badge-primary" style="font-size: var(--text-2xs);" title="Penjadwalan Berkala">
                                                <i class="fas fa-repeat me-1"></i>{{ ucfirst($item->pemberlakuan) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Waktu" style="padding: var(--space-7) var(--space-8); text-align: center; font-size: var(--text-base); font-family: var(--font-family-mono);">
                                    {{ substr((string) $item->jam_mulai, 0, 5) }} - {{ substr((string) $item->jam_selesai, 0, 5) }}
                                </td>
                                <td data-label="Status" style="padding: var(--space-7) var(--space-8); text-align: center;">
                                    @if ($item->status === 'dijadwalkan')
                                        <span class="badge badge-info">Dijadwalkan</span>
                                    @elseif ($item->status === 'berlangsung')
                                        <span class="badge badge-warning">Berlangsung</span>
                                    @elseif ($item->status === 'selesai')
                                        <span class="badge badge-success">Selesai</span>
                                    @else
                                        <span class="badge badge-danger">Batal</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-7) var(--space-8); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-jadwal"
                                                data-id="{{ $item->id }}"
                                                data-lokasi="{{ $item->ruang_lab_nama }}"
                                                data-jenis="{{ $jenisKey }}"
                                                data-rombel-id="{{ $item->rombel_id }}"
                                                data-rombel-nama="{{ $item->rombel_nama }}"
                                                data-pembelajaran-id="{{ $item->pembelajaran_id }}"
                                                data-mapel="{{ $item->mata_pelajaran }}"
                                                data-ptk-id="{{ $item->ptk_id }}"
                                                data-ptk-ids="{{ $item->ptk_ids }}"
                                                data-pendamping="{{ $item->guru_pendamping }}"
                                                data-pemberlakuan="{{ $item->pemberlakuan ?? 'sekali' }}"
                                                data-guru="{{ $item->guru_nama }}"
                                                data-topik="{{ $item->topik_praktik }}"
                                                data-tanggal="{{ $item->tanggal }}"
                                                data-jam-mulai="{{ substr((string) $item->jam_mulai, 0, 5) }}"
                                                data-jam-selesai="{{ substr((string) $item->jam_selesai, 0, 5) }}"
                                                data-alat="{{ $item->alat_bahan_digunakan }}"
                                                data-status="{{ $item->status }}"
                                                title="Ubah Detail Jadwal">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-status-lab"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                data-status="{{ $item->status }}"
                                                data-laporan="{{ $item->laporan_kerusakan }}" title="Update Status Sesi">
                                                <i class="fas fa-sliders" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-jadwal"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                title="Hapus Jadwal" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada jadwal praktik yang cocok dengan filter.</div>
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

        @include('dashboard.laboran.partials.jadwal-modal-form')

        @include('dashboard.laboran.partials.jadwal-modal-status')
    </div>

    @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script
                src="{{ asset('js/laboran-jadwal.js') }}?v={{ file_exists(public_path('js/laboran-jadwal.js')) ? filemtime(public_path('js/laboran-jadwal.js')) : time() }}">
            </script>
        @endpush
    @endsection
