@extends('layouts.dashboard')

@section('title', 'Monitoring — SAE')
@section('dash_title', 'Monitoring')

@section('content')
    @php
        $isFiltered = !empty($q) || !empty($bidang) || !empty($modul) || !empty($tipe) || !empty($startDate) || !empty($endDate) || ($crud !== 'all') || ($range !== 'today');
        $resetUrl = route('dashboard.aktivitas-database.index');

        $filterUrl = function ($c) use ($perPage, $bidang, $modul, $tipe, $startDate, $endDate, $range, $q) {
            $params = [];
            if ($c !== 'all') {
                $params['crud'] = $c;
            }
            if (!empty($bidang)) {
                $params['bidang'] = $bidang;
            }
            if (!empty($modul)) {
                $params['modul'] = $modul;
            }
            if (!empty($tipe)) {
                $params['tipe'] = $tipe;
            }
            if (!empty($startDate)) {
                $params['start_date'] = $startDate;
            }
            if (!empty($endDate)) {
                $params['end_date'] = $endDate;
            }
            if (!empty($range) && $range !== 'today') {
                $params['range'] = $range;
            }
            if (!empty($q)) {
                $params['q'] = $q;
            }
            if ($perPage != 25) {
                $params['perPage'] = $perPage;
            }
            return route('dashboard.aktivitas-database.index', $params);
        };

        $sortUrl = function (string $col) use ($sort, $sortDir, $crud, $bidang, $modul, $tipe, $startDate, $endDate, $range, $q, $perPage) {
            $dir = ($sort === $col && $sortDir === 'asc') ? 'desc' : 'asc';
            $params = [
                'sort' => $col,
                'sort_dir' => $dir,
            ];
            if ($crud !== 'all') {
                $params['crud'] = $crud;
            }
            if (!empty($bidang)) {
                $params['bidang'] = $bidang;
            }
            if (!empty($modul)) {
                $params['modul'] = $modul;
            }
            if (!empty($tipe)) {
                $params['tipe'] = $tipe;
            }
            if (!empty($startDate)) {
                $params['start_date'] = $startDate;
            }
            if (!empty($endDate)) {
                $params['end_date'] = $endDate;
            }
            if (!empty($range) && $range !== 'today') {
                $params['range'] = $range;
            }
            if (!empty($q)) {
                $params['q'] = $q;
            }
            if ($perPage != 25) {
                $params['perPage'] = $perPage;
            }
            return route('dashboard.aktivitas-database.index', $params);
        };

        $exportUrl = route('dashboard.aktivitas-database.export', array_filter([
            'crud' => $crud !== 'all' ? $crud : null,
            'bidang' => $bidang ?: null,
            'modul' => $modul ?: null,
            'tipe' => $tipe ?: null,
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
            'range' => $range ?: null,
            'q' => $q ?: null,
        ]));

        $crudTabs = [
            [
                'key' => 'all',
                'label' => 'Semua Transaksi',
                'icon' => 'fa-layer-group',
                'href' => $filterUrl('all'),
                'active' => $crud === 'all',
                'count' => $stats['total'],
            ],
            [
                'key' => 'create',
                'label' => 'CREATE',
                'icon' => 'fa-circle-plus',
                'href' => $filterUrl('create'),
                'active' => $crud === 'create',
                'count' => $stats['create'],
            ],
            [
                'key' => 'read',
                'label' => 'READ',
                'icon' => 'fa-eye',
                'href' => $filterUrl('read'),
                'active' => $crud === 'read',
                'count' => $stats['read'],
            ],
            [
                'key' => 'update',
                'label' => 'UPDATE',
                'icon' => 'fa-pen-to-square',
                'href' => $filterUrl('update'),
                'active' => $crud === 'update',
                'count' => $stats['update'],
            ],
            [
                'key' => 'delete',
                'label' => 'DELETE',
                'icon' => 'fa-trash-can',
                'href' => $filterUrl('delete'),
                'active' => $crud === 'delete',
                'count' => $stats['delete'],
            ],
        ];
    @endphp

    <!-- 1. Header Banner -->
    <div class="dash-banner">
        <div>
            <div style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-2); flex-wrap: wrap;">
                <h2 style="font-size: var(--text-2xl); font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-database text-primary me-2"></i> Monitoring
                </h2>
                <div id="liveIndicator"
                    style="display: inline-flex; align-items: center; gap: var(--space-2); padding: var(--space-2) var(--space-4); background: rgba(16, 185, 129, 0.12); color: var(--success); border-radius: var(--radius-pill); font-size: var(--text-xs); font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.25);">
                    <span style="width: 8px; height: 8px; border-radius: var(--radius-pill); background: var(--success); box-shadow: 0 0 8px var(--success); display: inline-block;"></span>
                    <span id="liveStatusText">LIVE MONITORING AKTIF</span>
                </div>
            </div>
            <p style="color: var(--text-muted); font-size: var(--text-base); margin: 0;">
                Pemantauan riwayat seluruh transaksi Create, Read, Update, dan Delete (CRUD) basis data aplikasi SAE secara realtime.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: var(--space-2); align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" id="btnToggleLive" onclick="toggleLiveMonitoring()"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); border-color: var(--success); color: var(--success);"
                title="Jeda / Lanjutkan Live Monitoring" aria-label="Jeda atau Lanjutkan Live Monitoring">
                <i class="fas fa-tower-broadcast" id="iconToggleLive"></i>
            </button>
            <a href="{{ $exportUrl }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); border-color: var(--success); color: var(--success);"
                title="Export Log ke Excel" aria-label="Export Log ke Excel">
                <i class="fas fa-file-excel"></i>
            </a>
            @if ($canDelete)
                <button type="button" class="btn btn-outline" onclick="openPurgeModal()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); border-color: var(--danger); color: var(--danger);"
                    title="Pembersihan Massal Log (Purge)" aria-label="Pembersihan Massal Log">
                    <i class="fas fa-broom"></i>
                </button>
            @endif
            <a href="{{ $resetUrl }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); color: var(--text-muted);"
                title="Segarkan / Reset Filter" aria-label="Segarkan / Reset Filter">
                <i class="fas fa-rotate"></i>
            </a>
        </div>
    </div>

    <!-- 2. Stat Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); margin-bottom: var(--space-9);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-database"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statTotal" style="font-size: var(--text-2xl);">
                    {{ number_format($stats['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Basis Data</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statToday" style="font-size: var(--text-2xl);">
                    {{ number_format($stats['today'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Aktivitas Hari Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);">
                <i class="fas fa-circle-plus"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statCreate" style="font-size: var(--text-2xl);">
                    {{ number_format($stats['create'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Transaksi CREATE</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary);">
                <i class="fas fa-eye"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statRead" style="font-size: var(--text-2xl);">
                    {{ number_format($stats['read'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Transaksi READ</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning);">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statUpdate" style="font-size: var(--text-2xl);">
                    {{ number_format($stats['update'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Transaksi UPDATE</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger);">
                <i class="fas fa-trash-can"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statDelete" style="font-size: var(--text-2xl);">
                    {{ number_format($stats['delete'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Transaksi DELETE</div>
            </div>
        </div>
    </div>

    <!-- 3. Visual Monitoring Report & Interactive Charts Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-9);">
        <!-- Chart 1: Laporan Tren Aktivitas 24 Jam (Line Chart) -->
        <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); margin-bottom: 0; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 700; color: var(--text-color); margin: 0;">
                        <i class="fas fa-chart-line text-primary me-2"></i> Tren Aktivitas Transaksi (24 Jam Hari Ini)
                    </h3>
                    <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 0 0;">
                        Fluktuasi frekuensi operasi CREATE, UPDATE, DELETE, dan READ per jam.
                    </p>
                </div>
                <span id="chartHourlyTimestamp"
                    style="font-size: var(--text-xs); color: var(--text-muted); background: var(--bg-hover); padding: var(--space-1) var(--space-3); border-radius: var(--radius-pill); border: 1px solid var(--border-color);">
                    {{ now()->format('H:i') }} WIB
                </span>
            </div>
            <div style="position: relative; height: 230px; width: 100%;">
                <canvas id="chartHourlyAktivitas"></canvas>
            </div>
        </div>

        <!-- Chart 2: Komposisi CRUD & Modul Teraktif -->
        <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); margin-bottom: 0; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 700; color: var(--text-color); margin: 0;">
                        <i class="fas fa-chart-pie text-accent me-2"></i> Proporsi Operasi & Modul Teraktif
                    </h3>
                    <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 0 0;">
                        Rasio operasi basis data dan beban kerja modul tersibuk.
                    </p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); align-items: center; height: 230px;">
                <div style="position: relative; height: 210px; width: 100%;">
                    <canvas id="chartCrudDistribution"></canvas>
                </div>
                <div style="position: relative; height: 210px; width: 100%;">
                    <canvas id="chartTopModules"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 3b. Trafik Penggunaan per Dimensi (Bidang, Menu, Fitur) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-9);">
        <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); margin-bottom: 0;">
            <h3 style="font-size: var(--text-base); font-weight: 700; color: var(--text-color); margin: 0;">
                <i class="fas fa-sitemap text-primary me-2"></i> Trafik per Bidang
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 var(--space-4) 0;">
                Distribusi aktivitas menurut bidang kerja.
            </p>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="chartBidangTraffic"></canvas>
            </div>
        </div>

        <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); margin-bottom: 0;">
            <h3 style="font-size: var(--text-base); font-weight: 700; color: var(--text-color); margin: 0;">
                <i class="fas fa-layer-group text-accent me-2"></i> Trafik per Menu
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 var(--space-4) 0;">
                Menu dashboard paling sering diakses.
            </p>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="chartMenuTraffic"></canvas>
            </div>
        </div>

        <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); margin-bottom: 0;">
            <h3 style="font-size: var(--text-base); font-weight: 700; color: var(--text-color); margin: 0;">
                <i class="fas fa-puzzle-piece text-success me-2"></i> Trafik per Fitur
            </h3>
            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 var(--space-4) 0;">
                Fitur tersibuk pada rentang terpilih.
            </p>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="chartFiturTraffic"></canvas>
            </div>
        </div>
    </div>

    <!-- 4. Navigation Tabs (Di Bawah Grafik, Di Atas Datatable) -->
    @include('partials.manajemen-data-tabs', ['tabs' => $crudTabs])

    <!-- 5. Unified Datatable Card -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 5.1 Toolbar Header (Entries, Preset Rentang, Filter Modul, Filter Tanggal & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <!-- Preset Rentang Cepat -->
                    <div class="btn-group" style="display: inline-flex; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-range-preset {{ ($range === 'today' || (empty($range) && $startDate === now()->toDateString() && $endDate === now()->toDateString())) ? 'btn-primary active' : 'btn-outline' }}"
                            data-range="today"
                            style="padding: var(--space-2) var(--space-4); font-size: var(--text-xs); height: 38px; border-radius: 0; border: none;">
                            Hari Ini
                        </button>
                        <button type="button" class="btn btn-range-preset {{ $range === '7days' ? 'btn-primary active' : 'btn-outline' }}"
                            data-range="7days"
                            style="padding: var(--space-2) var(--space-4); font-size: var(--text-xs); height: 38px; border-radius: 0; border: none; border-left: 1px solid var(--border-color);">
                            7 Hari
                        </button>
                        <button type="button" class="btn btn-range-preset {{ $range === '30days' ? 'btn-primary active' : 'btn-outline' }}"
                            data-range="30days"
                            style="padding: var(--space-2) var(--space-4); font-size: var(--text-xs); height: 38px; border-radius: 0; border: none; border-left: 1px solid var(--border-color);">
                            30 Hari
                        </button>
                        <button type="button" class="btn btn-range-preset {{ ($range === 'all' || empty($startDate) && empty($endDate) && $range !== 'today') ? 'btn-primary active' : 'btn-outline' }}"
                            data-range="all"
                            style="padding: var(--space-2) var(--space-4); font-size: var(--text-xs); height: 38px; border-radius: 0; border: none; border-left: 1px solid var(--border-color);">
                            Semua
                        </button>
                    </div>

                    <div class="toolbar-filter-item">
                        <select id="filterBidang" class="form-select"
                            style="height: 38px; font-size: var(--text-sm); border-radius: var(--radius-md); background: var(--bg-card); color: var(--text-color); border: 1px solid var(--border-color); padding: 0 var(--space-5);">
                            <option value="">Semua Bidang</option>
                            @foreach ($availableBidang as $b)
                                <option value="{{ $b }}" {{ ($bidang ?? '') === $b ? 'selected' : '' }}>
                                    {{ $b }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="toolbar-filter-item">
                        <select id="filterModul" class="form-select"
                            style="height: 38px; font-size: var(--text-sm); border-radius: var(--radius-md); background: var(--bg-card); color: var(--text-color); border: 1px solid var(--border-color); padding: 0 var(--space-5);">
                            <option value="">Semua Modul</option>
                            @foreach ($availableModules as $m)
                                <option value="{{ $m }}" {{ $modul === $m ? 'selected' : '' }}>
                                    {{ $m }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="toolbar-filter-item" style="display: inline-flex; align-items: center; gap: var(--space-2);">
                        <input type="date" id="filterStartDate" value="{{ $startDate }}"
                            style="height: 38px; font-size: var(--text-sm); border-radius: var(--radius-md); background: var(--bg-card); color: var(--text-color); border: 1px solid var(--border-color); padding: 0 var(--space-4);"
                            title="Tanggal Mulai">
                        <span style="color: var(--text-muted); font-size: var(--text-xs);">-</span>
                        <input type="date" id="filterEndDate" value="{{ $endDate }}"
                            style="height: 38px; font-size: var(--text-sm); border-radius: var(--radius-md); background: var(--bg-card); color: var(--text-color); border: 1px solid var(--border-color); padding: 0 var(--space-4);"
                            title="Tanggal Sampai">
                    </div>

                    @if ($isFiltered)
                        <a href="{{ $resetUrl }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                            title="Reset Semua Filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q"
                        placeholder="Cari aktivitas, admin, IP, payload..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 5.2 Konten Datatable Responsive Stack -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px;">
                            No
                        </th>
                        <th class="sortable-th {{ $sort === 'created_at' ? 'sorted' : '' }}"
                            style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <a href="{{ $sortUrl('created_at') }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-2);">
                                <span>Waktu</span>
                                <span class="sort-icon">{!! $sort === 'created_at' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </a>
                        </th>
                        <th class="sortable-th {{ $sort === 'admin_name' ? 'sorted' : '' }}"
                            style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <a href="{{ $sortUrl('admin_name') }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-2);">
                                <span>Pengguna</span>
                                <span class="sort-icon">{!! $sort === 'admin_name' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </a>
                        </th>
                        <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tipe Transaksi
                        </th>
                        <th class="sortable-th {{ $sort === 'modul' ? 'sorted' : '' }}"
                            style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <a href="{{ $sortUrl('modul') }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-2);">
                                <span>Bidang & Menu</span>
                                <span class="sort-icon">{!! $sort === 'modul' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </a>
                        </th>
                        <th class="sortable-th {{ $sort === 'aktivitas' ? 'sorted' : '' }}"
                            style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            <a href="{{ $sortUrl('aktivitas') }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-2);">
                                <span>Fitur & Bagian</span>
                                <span class="sort-icon">{!! $sort === 'aktivitas' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </a>
                        </th>
                        <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 100px;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $idx => $item)
                        @php
                            $badge = $item->crud_badge;
                            $konteks = $item->konteks;
                            $rowNum = ($list->currentPage() - 1) * $list->perPage() + $idx + 1;
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: var(--space-6) var(--space-8); font-size: var(--text-base); color: var(--text-muted);"
                                data-label="No">
                                {{ $rowNum }}
                            </td>
                            <td style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); color: var(--text-color);"
                                data-label="Waktu">
                                <div style="font-weight: 600;">
                                    {{ $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '-' }}
                                </div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                    {{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}
                                </div>
                            </td>
                            <td style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm);"
                                data-label="Pengguna">
                                <div style="font-weight: 600; color: var(--text-color);">
                                    {{ $item->admin_name ?: 'Administrator' }}
                                </div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted); font-family: var(--font-family-mono, monospace);">
                                    {{ $item->admin_username ? '@' . $item->admin_username : '-' }}
                                    @if ($item->ip_address)
                                        <span style="opacity: 0.7;">• {{ $item->ip_address }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: var(--space-6) var(--space-8);"
                                data-label="Tipe">
                                <span class="badge"
                                    style="background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; font-weight: 700; padding: var(--space-2) var(--space-4); border-radius: var(--radius-sm); font-size: var(--text-xs); display: inline-flex; align-items: center; gap: var(--space-2);">
                                    <i class="fas {{ $badge['icon'] }}"></i>
                                    <span>{{ $badge['label'] }}</span>
                                </span>
                            </td>
                            <td style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm);"
                                data-label="Bidang & Menu">
                                <div>
                                    <span class="badge"
                                        style="background: {{ $konteks['bidang_bg'] }}; color: {{ $konteks['bidang_color'] }}; font-weight: 700; padding: var(--space-2) var(--space-4); border-radius: var(--radius-sm); font-size: var(--text-xs); display: inline-flex; align-items: center; gap: var(--space-2);">
                                        <i class="fas {{ $konteks['bidang_icon'] }}"></i>
                                        <span>{{ $konteks['bidang'] }}</span>
                                    </span>
                                </div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1); display: inline-flex; align-items: center; gap: var(--space-1);">
                                    <i class="fas fa-layer-group" style="opacity: 0.6;"></i>
                                    <span>{{ $konteks['menu'] }}</span>
                                </div>
                            </td>
                            <td style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); max-width: 380px;"
                                data-label="Fitur & Bagian">
                                <div style="font-weight: 600; color: var(--text-color); margin-bottom: var(--space-1);">
                                    {{ $konteks['fitur'] }}
                                </div>
                                <div style="margin-top: var(--space-1); margin-bottom: var(--space-1);">
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-xs); padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); display: inline-flex; align-items: center; gap: var(--space-1); max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <i class="fas fa-bullseye text-primary"></i>
                                        <span>{{ $konteks['bagian'] }}</span>
                                    </span>
                                </div>
                                @if (!empty($item->keterangan))
                                    <div style="font-size: var(--text-xs); color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 340px;">
                                        {{ $item->keterangan }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: var(--space-6) var(--space-8); text-align: right;"
                                data-label="Aksi">
                                <div class="table-actions" style="justify-content: flex-end; display: inline-flex; gap: var(--space-2);">
                                    <button type="button" class="btn-icon" title="Lihat Rincian Transaksi"
                                        onclick="showDetail({{ $item->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if ($canDelete)
                                        <button type="button" class="btn-icon danger" title="Hapus Baris Log"
                                            onclick="deleteLog({{ $item->id }})">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                style="padding: var(--space-11); text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                <i class="fas fa-database mb-2" style="font-size: var(--text-3xl); opacity: 0.4;"></i>
                                <div style="font-weight: 600;">Tidak ada aktivitas database yang sesuai.</div>
                                <div style="font-size: var(--text-xs); margin-top: var(--space-2);">Coba sesuaikan kata kunci pencarian atau rentang filter Anda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 5.3 Footer Paginasi Terpadu Datatable -->
        <div class="datatable-footer" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; padding: var(--space-6) var(--space-8); border-top: 1px solid var(--border-color); gap: var(--space-4);">
            <div style="font-size: var(--text-sm); color: var(--text-muted);">
                @if ($list->total() > 0)
                    Menampilkan <strong>{{ $list->firstItem() }}</strong> sampai <strong>{{ $list->lastItem() }}</strong> dari <strong>{{ number_format($list->total(), 0, ',', '.') }}</strong> entri
                @else
                    Tidak ada entri untuk ditampilkan
                @endif
            </div>
            @if ($list->hasPages())
                <div>
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>
    </div>

    <!-- Payload JSON Data Grafik Interaktif -->
    <script id="monitoringChartPayload" type="application/json">
        {!! json_encode($chartData, JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@push('scripts')
    <script src="{{ asset('js/aktivitas-database.js') }}"></script>
@endpush
