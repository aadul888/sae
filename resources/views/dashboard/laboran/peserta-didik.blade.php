@extends('layouts.dashboard')

@section('title', 'Data Peserta Didik — Laboratorium SAE')
@section('dash_title', 'Data Peserta Didik')

@section('content')
    @include('dashboard.laboran.partials.header-actions')

    @push('styles')
        <style>
            /* ---- Toolbar Data Peserta Didik: sejajar & rapi ---- */
            .pd-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: var(--space-6);
            }

            .pd-toolbar__filters {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: var(--space-4);
                min-width: 0;
            }

            /* Semua kontrol setinggi 38px agar sejajar sempurna */
            .pd-toolbar__control,
            .pd-toolbar__search {
                height: 38px;
                flex: 0 0 auto;
            }

            .pd-toolbar__control {
                width: auto;
                min-width: 140px;
                max-width: 220px;
            }

            .pd-toolbar__btn {
                width: 38px;
                height: 38px;
                min-width: 38px;
                padding: 0;
                flex: 0 0 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--radius-md, 8px);
            }

            .pd-toolbar__btn--success {
                border-color: var(--success, #10b981);
                color: var(--success, #10b981);
            }

            .pd-toolbar__actions {
                display: flex;
                align-items: center;
                gap: var(--space-4);
                flex: 0 0 auto;
            }

            .pd-toolbar__search {
                flex: 0 1 280px;
                min-width: 200px;
            }

            .pd-toolbar__search input {
                width: 100%;
                height: 38px;
            }

            /* Footer modal: tombol sejajar kanan, konsisten */
            .pd-modal-footer {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: wrap;
                gap: var(--space-5);
            }

            .pd-modal-footer .btn {
                height: 38px;
                padding: 0 var(--space-8);
                font-size: var(--text-sm, 0.8125rem);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: var(--space-3);
            }

            /* ---- Mobile: kontrol tumpuk rapi & tombol tetap sejajar ---- */
            @media (max-width: 768px) {
                .pd-toolbar {
                    flex-direction: column;
                    align-items: stretch;
                    gap: var(--space-5);
                }

                .pd-toolbar__filters {
                    width: 100%;
                    gap: var(--space-3);
                }

                /* Dua kolom di mobile; global dashboard.css sudah mengatur
                   select.toolbar-filter-select jadi 1fr 1fr — jangan ditimpa. */
                .pd-toolbar__filters .pd-toolbar__control {
                    width: auto;
                    max-width: 100%;
                }

                /* Baris tombol aksi rata kanan di mobile */
                .pd-toolbar__actions {
                    margin-left: auto;
                }

                .pd-toolbar__search {
                    flex: 1 1 100%;
                    width: 100%;
                    min-width: 0;
                }

                .pd-modal-footer {
                    flex-direction: row;
                    justify-content: stretch;
                }

                .pd-modal-footer .btn {
                    flex: 1 1 0;
                    padding: 0 var(--space-5);
                }
            }
        </style>
    @endpush

    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(99,102,241,0.12); color: var(--primary, #6366f1); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h2
                        style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Data Peserta Didik
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Direktori peserta didik pada jurusan <strong>{{ $context['jurusan_nama'] ?: ($context['jurusan_id'] ?: 'Anda') }}</strong>.
                        Data bersifat baca-saja &mdash; hanya detail biodata yang dapat dibuka.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.peserta-didik.cetak', request()->query()) }}" class="btn btn-outline"
                    title="Cetak Data Peserta Didik Jurusan (A4)" aria-label="Cetak Data Peserta Didik Jurusan">
                    <i class="fas fa-print"></i>
                </a>
                <button type="button" class="btn btn-outline" id="btnOpenExportPd"
                    title="Ekspor Data Peserta Didik ke Excel (.xlsx)"
                    aria-label="Ekspor Data Peserta Didik ke Excel"
                    style="border-color: var(--success, #10b981); color: var(--success, #10b981);">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
        </div>

        {{-- 2. Statistik sesuai tab --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            @if ($tab === 'aktif')
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary, #6366f1);"><i
                            class="fas fa-user-graduate"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Total Peserta Didik Aktif</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent, #06b6d4);"><i
                            class="fas fa-mars"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['laki'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Laki-Laki</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;"><i
                            class="fas fa-venus"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['perempuan'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Perempuan</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);"><i
                            class="fas fa-chalkboard-user"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['rombel'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Rombel Kelas</div>
                    </div>
                </div>
            @else
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary, #6366f1);"><i
                            class="fas fa-user-slash"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Total Tidak Aktif</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);"><i
                            class="fas fa-graduation-cap"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['alumni'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Alumni / Lulus</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);"><i
                            class="fas fa-right-from-bracket"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['mutasi'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Mutasi / Keluar</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent, #06b6d4);"><i
                            class="fas fa-chalkboard-user"></i></div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($stats['rombel'], 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Rombel Terakhir</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- 3. Navigasi Tab Aktif / Tidak Aktif --}}
        <div class="periode-nav-wrapper" style="margin-bottom: var(--space-7);">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.laboran.peserta-didik.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'aktif'])) }}"
                    class="periode-nav-tab {{ $tab === 'aktif' ? 'active' : '' }}">
                    <i class="fas fa-user-check"></i> Aktif ({{ number_format($aktifCount, 0, ',', '.') }})
                </a>
                <a href="{{ route('dashboard.laboran.peserta-didik.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'tidak_aktif'])) }}"
                    class="periode-nav-tab {{ $tab === 'tidak_aktif' ? 'active' : '' }}">
                    <i class="fas fa-user-slash"></i> Tidak Aktif ({{ number_format($tidakAktifCount, 0, ',', '.') }})
                </a>
            </div>
            <div class="periode-nav-mobile">
                <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                    <label><i class="fas fa-sliders text-primary me-1"></i> Pilih Status:</label>
                    <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;">
                        <option
                            value="{{ route('dashboard.laboran.peserta-didik.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'aktif'])) }}"
                            {{ $tab === 'aktif' ? 'selected' : '' }}>
                            Aktif ({{ number_format($aktifCount, 0, ',', '.') }})
                        </option>
                        <option
                            value="{{ route('dashboard.laboran.peserta-didik.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'tidak_aktif'])) }}"
                            {{ $tab === 'tidak_aktif' ? 'selected' : '' }}>
                            Tidak Aktif ({{ number_format($tidakAktifCount, 0, ',', '.') }})
                        </option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 4. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <div class="pd-toolbar">
                    <div class="pd-toolbar__filters">
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

                        <select name="rombel" id="filterRombel" class="toolbar-filter-select pd-toolbar__control"
                            title="Filter kelas / rombel">
                            <option value="">Semua Kelas</option>
                            @foreach ($filterRombel as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}
                                </option>
                            @endforeach
                        </select>

                        <select name="gender" id="filterGender" class="toolbar-filter-select pd-toolbar__control"
                            title="Filter jenis kelamin">
                            <option value="">Semua Gender</option>
                            <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                            <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                        </select>

                        <div class="pd-toolbar__actions">
                            @if ($q || $rombel || $gender)
                                <a href="{{ route('dashboard.laboran.peserta-didik.index', ['tab' => $tab]) }}"
                                    class="btn btn-outline pd-toolbar__btn" title="Reset filter"
                                    aria-label="Reset filter" data-live-reset>
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif

                            <button type="button" class="btn btn-outline pd-toolbar__btn pd-toolbar__btn--success"
                                id="btnOpenExportPdToolbar" title="Ekspor Data ke Excel (.xlsx)"
                                aria-label="Ekspor Data ke Excel">
                                <i class="fas fa-file-excel"></i>
                            </button>
                        </div>
                    </div>

                    <div class="live-search-wrap pd-toolbar__search">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q"
                            placeholder="Cari nama / NISN / NIPD / NIK..." value="{{ $q }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); ">
                            @php
                                $cols = $tab === 'aktif'
                                    ? [
                                        ['nama', 'Nama Lengkap', 'left'],
                                        ['nisn', 'NISN / NIPD', 'left'],
                                        ['jenis_kelamin', 'L/P', 'center'],
                                        ['nama_rombel', 'Rombel Kelas', 'left'],
                                        ['tingkat_pendidikan_id', 'Tingkat', 'center'],
                                        ['tanggal_lahir', 'Tempat, Tanggal Lahir', 'left'],
                                    ]
                                    : [
                                        ['nama', 'Nama Lengkap', 'left'],
                                        ['nisn', 'NISN / NIPD', 'left'],
                                        ['jenis_kelamin', 'L/P', 'center'],
                                        ['nama_rombel_terakhir', 'Rombel Terakhir', 'left'],
                                        ['status_keluar', 'Status Keluar', 'center'],
                                        ['tanggal_lahir', 'Tempat, Tanggal Lahir', 'left'],
                                    ];
                            @endphp
                            @foreach ($cols as [$key, $label, $align])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                    style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; {{ $align === 'center' ? 'text-align: center;' : '' }}"
                                    data-sort="{{ $key }}">
                                    {{ $label }}
                                    <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            @php
                                $isAktif = $tab === 'aktif';
                                $rowNama = $item->nama ?: '-';
                                $rowRombel = $isAktif ? $item->nama_rombel : $item->nama_rombel_terakhir;
                                $rowId = $isAktif
                                    ? ($item->peserta_didik_id ?? '')
                                    : ($item->peserta_didik_id ?: $item->id);
                                $ttl = trim(($item->tempat_lahir ?: '') . ($item->tanggal_lahir ? ', ' : ''));
                                $ttlDate = $item->tanggal_lahir
                                    ? date('d/m/Y', strtotime($item->tanggal_lahir))
                                    : '';
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Nama Lengkap"
                                    style="padding: var(--space-6) var(--space-7); font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                    <div style="display: flex; align-items: center; gap: var(--space-5);">
                                        @if (!empty($item->foto_url))
                                            <div
                                                style="width: 38px; height: 38px; border-radius: var(--radius-lg); overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0;">
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $rowNama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div
                                                style="width: 38px; height: 38px; border-radius: var(--radius-lg); background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.35); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-sm); flex-shrink: 0;">
                                                <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                            </div>
                                        @endif
                                        <div style="min-width: 0;">
                                            <div style="font-weight: var(--font-bold); color: var(--text-color);">
                                                {{ $rowNama }}</div>
                                            @if ($item->nik)
                                                <div
                                                    style="font-size: var(--text-xs); color: var(--text-muted); font-family: var(--font-family-mono); margin-top: 2px;">
                                                    <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                                        title="Klik untuk salin NIK">NIK: {{ $item->nik }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="NISN/NIPD"
                                    style="padding: var(--space-6) var(--space-7); font-family: var(--font-family-mono); font-size: var(--text-base); color: var(--primary);">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if ($item->nipd)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td data-label="L/P" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span
                                        class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                        style="font-size: var(--text-xs); padding: 2px 7px;">
                                        {{ $item->jenis_kelamin ?: '-' }}
                                    </span>
                                </td>
                                <td data-label="Rombel"
                                    style="padding: var(--space-6) var(--space-7); font-weight: var(--font-semibold); font-size: var(--text-base);">
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-sm); padding: 3px 8px;">{{ $rowRombel ?: '-' }}</span>
                                </td>
                                @if ($isAktif)
                                    <td data-label="Tingkat"
                                        style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-sm); color: var(--text-muted);">
                                        {{ $item->tingkat_pendidikan_id ?: '-' }}
                                    </td>
                                @else
                                    <td data-label="Status Keluar"
                                        style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <span
                                            class="badge {{ $item->status_keluar === 'Alumni' ? 'badge-success' : 'badge-warning' }}"
                                            style="font-size: var(--text-xs); padding: 2px 7px;">
                                            {{ $item->status_keluar ?: '-' }}
                                        </span>
                                        @if ($item->tahun_lulus)
                                            <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: 2px;">
                                                {{ $item->tahun_lulus }}
                                            </div>
                                        @endif
                                    </td>
                                @endif
                                <td data-label="TTL"
                                    style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); color: var(--text-muted);">
                                    {{ $ttl }}{{ $ttlDate ?: '-' }}
                                </td>
                                <td data-label="Aksi"
                                    style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-4);">
                                        <button type="button" class="btn-icon btn-detail-pd"
                                            data-id="{{ $rowId }}" data-tab="{{ $tab }}"
                                            data-nama="{{ $rowNama }}" title="Lihat Detail Biodata Peserta Didik"
                                            aria-label="Lihat Detail Biodata Peserta Didik">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    style="padding: var(--space-11); text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    <i class="fas fa-user-slash mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada data peserta didik yang cocok dengan filter.</div>
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
    </div>

    {{-- ===================== MODAL BIODATA PESERTA DIDIK ===================== --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0;">
                        <i class="fas fa-user-graduate text-primary" style="font-size: var(--text-xl);"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-color); margin: 0;">
                            Biodata Peserta Didik</h3>
                        <div id="bioRombel" style="font-size: var(--text-sm); color: var(--text-muted); margin-top: 2px;">-
                        </div>
                    </div>
                </div>
                <button type="button" id="btnCloseBiodataPd"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg);"
                    aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl);"></i>
                <div>Memuat biodata peserta didik...</div>
            </div>

            <div id="bioError" style="display: none; padding: 18px; border-radius: var(--radius-md, 8px); background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); color: var(--danger, #ef4444); font-size: var(--text-sm);">
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: var(--text-base);">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: var(--font-bold); color: var(--primary); font-size: var(--text-sm); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi &amp; Fisik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 150px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: var(--font-semibold); font-family: var(--font-family-mono); color: var(--primary);">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: var(--font-family-mono);">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Anak Keberapa</td>
                            <td id="bioAnak">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tinggi / Berat Badan</td>
                            <td id="bioFisik" style="font-weight: var(--font-semibold); color: var(--success, #10b981);">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kebutuhan Khusus</td>
                            <td id="bioKhusus">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: var(--font-bold); color: var(--primary); font-size: var(--text-sm); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Data Akademik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 150px;">Rombel / Kelas</td>
                            <td id="bioRombelAkademik">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jurusan</td>
                            <td id="bioJurusan">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Pendaftaran / Asal</td>
                            <td id="bioPendaftaran">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Masuk</td>
                            <td id="bioTglMasuk">-</td>
                        </tr>
                        <tr id="bioStatusKeluarRow" style="border-bottom: 1px solid var(--border-color); display: none;">
                            <td style="padding: 6px 0; color: var(--text-muted);">Status Keluar</td>
                            <td id="bioStatusKeluar">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: var(--font-bold); color: var(--primary); font-size: var(--text-sm); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users me-1"></i> Data Orang Tua &amp; Wali
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 150px;">Nama Ayah</td>
                            <td id="bioAyah">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Ibu</td>
                            <td id="bioIbu">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Wali</td>
                            <td id="bioWali">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: var(--font-bold); color: var(--primary); font-size: var(--text-sm); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 150px;">Kontak (HP/Email)</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>

                <div id="bioMapelSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                            <i class="fas fa-book-open text-primary me-1"></i> Mata Pelajaran di Kelas
                        </span>
                        <span id="bioJmlMapel" class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: var(--text-xs); padding: 2px 7px;">0
                            Mapel</span>
                    </div>
                    <div style="overflow-x: auto; max-height: 180px;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: var(--text-sm); margin-bottom: 0;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); ">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Guru Pengampu</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam</th>
                                </tr>
                            </thead>
                            <tbody id="bioMapelList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="pd-modal-footer" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline" id="btnCloseBiodataPdFooter">Tutup</button>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL EKSPOR EXCEL ===================== --}}
    <div id="exportPdModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 520px; width: 94%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl);">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); color: var(--text-color); margin: 0;">
                            Ekspor Data ke Excel
                        </h3>
                        <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: 2px;">
                            Berkas .xlsx sesuai filter kelas yang dipilih
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close-export-pd"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl); padding: 4px;"
                    title="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formExportPd" method="GET"
                action="{{ route('dashboard.laboran.peserta-didik.export') }}" data-no-ajax data-manual-filter>
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div
                    style="margin-bottom: 16px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); font-size: var(--text-sm); line-height: 1.45; color: var(--text-color);">
                    <div
                        style="font-weight: var(--font-bold); color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-circle-info"></i> Cakupan Data
                    </div>
                    <div>
                        Ekspor mengikuti cakupan jurusan <strong>{{ $context['jurusan_nama'] ?: ($context['jurusan_id'] ?: '-') }}</strong>
                        dan status <strong>{{ $tab === 'aktif' ? 'Peserta Didik Aktif' : 'Peserta Didik Tidak Aktif' }}</strong>.
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label for="exportRombelSelect"
                        style="display: block; font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 6px;">
                        Filter Kelas / Rombel
                    </label>
                    <select id="exportRombelSelect" name="rombel" class="form-select"
                        style="width: 100%; height: 40px;">
                        <option value="">Semua Kelas ({{ $filterRombel->count() }} rombel)</option>
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label for="exportGenderSelect"
                        style="display: block; font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 6px;">
                        Filter Jenis Kelamin
                    </label>
                    <select id="exportGenderSelect" name="gender" class="form-select"
                        style="width: 100%; height: 40px;">
                        <option value="">Semua Gender</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>

                @if ($q !== '')
                    <input type="hidden" name="q" value="{{ $q }}">
                @endif

                <div class="pd-modal-footer" style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline btn-close-export-pd">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Unduh Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.LABORAN_PD_SHOW_URL = @json(route('dashboard.laboran.peserta-didik.show', ['id' => '__ID__']));
        </script>
        <script
            src="{{ asset('js/laboran-peserta-didik.js') }}?v={{ file_exists(public_path('js/laboran-peserta-didik.js')) ? filemtime(public_path('js/laboran-peserta-didik.js')) : time() }}"></script>
    @endpush
@endsection
