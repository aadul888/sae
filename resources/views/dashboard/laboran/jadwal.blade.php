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

        {{-- Modal Tambah / Ubah Jadwal Praktik --}}
        <div id="modalJadwal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 680px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalJadwalTitle"
                        style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                        Jadwalkan Praktik
                    </h3>
                    <button type="button" id="btnCloseModalJadwal"
                        style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                        title="Tutup">&times;</button>
                </div>

                <form id="formJadwal" method="POST" action="{{ route('dashboard.laboran.jadwal.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="jadwalMethod" value="POST">
                    <input type="hidden" name="jurusan_id" id="inputJadwalJurusan" value="{{ $context['jurusan_id'] ?? '' }}">
                    <input type="hidden" name="pembelajaran_id" id="inputPembelajaranId">
                    <input type="hidden" name="mata_pelajaran_id" id="inputMapelId">

                    {{-- Baris 1: Jenis lokasi + Lokasi --}}
                    <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Lokasi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis_lokasi" id="selectJenisLokasi" class="form-control" required>
                                @foreach (\App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI as $jKey => $jLabel)
                                    <option value="{{ $jKey }}">{{ $jLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                                <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                    Lokasi Praktik <span style="color: var(--danger, #ef4444);">*</span>
                                </label>
                                <a href="{{ route('dashboard.sarpras.ruang.index') }}" target="_blank"
                                    style="font-size: var(--text-xs); color: var(--primary); text-decoration: none;"
                                    title="Kelola Data Ruang, Lahan & Lapangan di Sarpras">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Data Sarpras
                                </a>
                            </div>
                            <select name="ruang_lab_nama" id="selectLokasi" class="form-control" required>
                                <option value="">-- Pilih Lokasi --</option>
                                @foreach ($lokasiByJenis as $jKey => $rows)
                                    @if ($rows->isNotEmpty())
                                        <optgroup label="{{ \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI[$jKey] ?? $jKey }}"
                                            data-jenis="{{ $jKey }}">
                                            @foreach ($rows as $r)
                                                <option value="{{ $r->nama_ruang }}" data-jenis="{{ $jKey }}">
                                                    {{ $r->nama_ruang }}{{ !empty($r->gedung) ? ' • ' . $r->gedung : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                <i class="fas fa-link me-1 text-primary"></i> Terintegrasi dengan <strong>Sarpras &amp;
                                    Aset</strong> (lab, bengkel, lahan, lapangan).
                            </div>
                        </div>
                    </div>

                    {{-- Baris 2: Kelas + KBM yang berlaku --}}
                    <div style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kelas / Rombel <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="rombel_id" id="selectRombel" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($allRombel as $rb)
                                <option value="{{ $rb->rombongan_belajar_id }}">
                                    {{ $rb->nama }}{{ !empty($rb->jurusan_id_str) ? ' — ' . $rb->jurusan_id_str : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pilihan Sesuai Jadwal KBM yang berlaku (Point 1) --}}
                    <div style="margin-bottom: var(--space-7); background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.2); border-radius: var(--radius-md); padding: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--primary);">
                            <i class="fas fa-calendar-check me-1"></i> Sesuaikan Jadwal KBM yang Berlaku
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(otomatis terisi)</span>
                        </label>
                        <select id="selectKbmSlot" class="form-control" disabled>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                        <div id="kbmSlotHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            Pilih slot jadwal KBM untuk mengisi mapel, jam pelajaran, dan guru pengampu secara otomatis.
                        </div>
                    </div>

                    {{-- Mapel --}}
                    <div style="margin-bottom: var(--space-7);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                            <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                Mata Pelajaran <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <label style="font-size: var(--text-xs); color: var(--text-muted); margin: 0; display: inline-flex; align-items: center; gap: var(--space-2); cursor: pointer;"
                                title="Aktifkan bila mata pelajaran tidak tersedia pada data kelas">
                                <input type="checkbox" id="chkMapelManual" style="cursor: pointer;">
                                Isi manual
                            </label>
                        </div>
                        <select id="selectMapel" class="form-control" disabled>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                        <input type="text" id="inputMapelManual" class="form-control" placeholder="Contoh: Praktik Kejuruan"
                            style="display: none;" disabled>
                    </div>

                    {{-- Pemberlakuan Jadwal (Bulanan atau Semester) --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Pemberlakuan Jadwal <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="pemberlakuan" id="selectPemberlakuan" class="form-control" required>
                                <option value="sekali">Sekali Saja (Satu Tanggal Sesi)</option>
                                <option value="bulanan">Bulanan (Rutin Setiap Minggu selama 1 Bulan)</option>
                                <option value="semester">Semester (Rutin Setiap Minggu selama 1 Semester)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Praktik / Tanggal Mulai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal" id="inputTanggal" class="form-control" required>
                            <div id="pemberlakuanHint" style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                                Hari dari tanggal ini akan dipakai bila memilih jadwal rutin mingguan.
                            </div>
                        </div>
                    </div>

                    {{-- Jam mulai + selesai --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jam Mulai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="time" name="jam_mulai" id="inputJamMulai" class="form-control" value="08:00" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jam Selesai <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="time" name="jam_selesai" id="inputJamSelesai" class="form-control" value="09:30" required>
                        </div>
                    </div>

                    {{-- Guru Pengampu: Bisa lebih dari 1 guru (Point 2) --}}
                    <div style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Guru Pengampu Utama <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="ptk_id" id="selectGuru" class="form-control" required>
                            <option value="">-- Pilih Guru Utama --</option>
                            @foreach ($allGtk as $g)
                                <option value="{{ $g->ptk_id }}">{{ $g->nama }}{{ !empty($g->nip) ? ' — ' . $g->nip : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-bottom: var(--space-7);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                            <label style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color); margin: 0;">
                                Guru Pendamping / Kolaborasi (Bisa &gt; 1 Guru)
                            </label>
                            <span style="font-size: var(--text-xs); color: var(--primary); font-weight: var(--font-semibold);">
                                <i class="fas fa-users me-1"></i> Tim Teaching
                            </span>
                        </div>
                        <div style="max-height: 140px; overflow-y: auto; padding: var(--space-4) var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md); background: rgba(255,255,255,0.01);">
                            @foreach ($allGtk as $g)
                                <label style="display: flex; align-items: center; gap: var(--space-3); padding: 4px 0; font-size: var(--text-sm); cursor: pointer;">
                                    <input type="checkbox" name="ptk_ids[]" value="{{ $g->ptk_id }}" class="chk-guru-pendamping" style="cursor: pointer;">
                                    <span>{{ $g->nama }}{{ !empty($g->nip) ? ' (' . $g->nip . ')' : '' }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-2);">
                            <i class="fas fa-shield-halved text-primary me-1"></i> Setiap guru yang dipilih akan otomatis dideteksi agar tidak bentrok dengan jadwal lain.
                        </div>
                    </div>

                    {{-- Baris 5: Topik (opsional) --}}
                    <div class="form-group" style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Topik / Materi Praktik
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                        </label>
                        <input type="text" name="topik_praktik" id="inputTopik" class="form-control"
                            placeholder="Contoh: Pengukuran pH tanah / Konfigurasi VLAN / Reaksi Redoks">
                    </div>

                    {{-- Baris 6: Alat & bahan --}}
                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kebutuhan Alat / Bahan Praktik
                            <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                        </label>
                        <textarea name="alat_bahan_digunakan" id="inputAlat" class="form-control" rows="2"
                            placeholder="Sebutkan alat, komputer, cangkul, bibit, bahan kimia, atau bahan yang diperlukan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalJadwal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Update Status & Laporan Kerusakan --}}
        <div id="modalStatus" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 480px; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-8);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                        Update Status Praktik
                    </h3>
                    <button type="button" id="btnCloseModalStatus"
                        style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                        title="Tutup">&times;</button>
                </div>

                <form id="formStatus" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_mode" value="status">
                    <p id="statusDesc" style="font-size: var(--text-base); color: var(--text-color); margin-bottom: var(--space-8);"></p>

                    <div class="form-group" style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Pilih Status <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="status" id="selectStatusLab" class="form-control" required>
                            @foreach (\App\Http\Controllers\LaboranJadwalController::STATUS_LIST as $sKey => $sLabel)
                                <option value="{{ $sKey }}">{{ $sLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Laporan Kerusakan / Insiden Pasca-Praktik
                        </label>
                        <textarea name="laporan_kerusakan" id="inputLaporanKerusakan" class="form-control" rows="2"
                            placeholder="Catat jika ada alat rusak, lahan terganggu, atau insiden lain..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalStatus">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Simpan Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
            <script
                src="{{ asset('js/laboran-jadwal.js') }}?v={{ file_exists(public_path('js/laboran-jadwal.js')) ? filemtime(public_path('js/laboran-jadwal.js')) : time() }}">
            </script>
        @endpush
    @endsection
