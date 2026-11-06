@extends('layouts.dashboard')

@section('title', 'Pengumuman, Broadcast & Berita — SAE')
@section('dash_title', 'Pengumuman & Berita')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-bullhorn text-primary me-2"></i> Pengumuman, Broadcast &amp; Berita
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                Kelola notifikasi internal, teks berjalan portal publik, dan publikasi warta berita sekolah secara terpusat.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('berita.index') }}" target="_blank" class="btn btn-outline"
                style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); text-decoration: none;"
                title="Buka Portal Berita Publik">
                <i class="fas fa-globe text-primary"></i> <span>Portal Berita</span>
            </a>
            <a href="{{ route('dashboard.informasi.index') }}" class="btn btn-outline"
                style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); text-decoration: none;"
                title="Tampilan Feed Pengguna">
                <i class="fas fa-bell text-warning"></i> <span>Feed Notif</span>
            </a>
            @if ($canCreate)
                @if ($activeTab === 'berita')
                    <button type="button" class="btn btn-primary" id="btnOpenBeritaModal"
                        style="height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                        <i class="fas fa-plus"></i> <span>Tulis Berita</span>
                    </button>
                @elseif ($activeTab === 'broadcast')
                    <button type="button" class="btn btn-primary" id="btnOpenBroadcastModal"
                        style="height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                        <i class="fas fa-plus"></i> <span>Buat Broadcast</span>
                    </button>
                @else
                    <button type="button" class="btn btn-primary" id="btnOpenCreateModal"
                        style="height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                        <i class="fas fa-plus"></i> <span>Buat Pengumuman</span>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['total_pengumuman'] }}</div>
                <div class="dash-stat-label">Pengumuman ({{ $summary['aktif_pengumuman'] }} Aktif)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent);">
                <i class="fas fa-tower-broadcast"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['total_broadcast'] }}</div>
                <div class="dash-stat-label">Info Broadcast ({{ $summary['aktif_broadcast'] }} Tayang)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $summary['total_berita'] }}</div>
                <div class="dash-stat-label">Berita Publik ({{ $summary['aktif_berita'] }} Tayang)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ $summary['aktif_pengumuman'] + $summary['aktif_broadcast'] + $summary['aktif_berita'] }}
                </div>
                <div class="dash-stat-label">Total Tayang Aktif</div>
            </div>
        </div>
    </div>

    <!-- 3 Navigasi Tab Baku SAE (Pengumuman | Info Broadcast | Berita) -->
    <div style="display: flex; gap: var(--space-2); margin-bottom: var(--space-4); border-bottom: 2px solid var(--border-color); overflow-x: auto; scrollbar-width: none;">
        <a href="{{ route('dashboard.pengumuman.index', ['tab' => 'pengumuman']) }}"
            style="padding: 10px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; text-decoration: none; border-bottom: 2px solid {{ $activeTab === 'pengumuman' ? 'var(--primary)' : 'transparent' }}; margin-bottom: -2px; color: {{ $activeTab === 'pengumuman' ? 'var(--primary)' : 'var(--text-muted)' }}; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
            <i class="fas fa-bullhorn"></i>
            <span>Pengumuman</span>
            <span class="badge {{ $activeTab === 'pengumuman' ? 'badge-primary' : 'badge-outline' }}" style="font-size: var(--text-2xs); padding: 2px 7px;">
                {{ $summary['total_pengumuman'] }}
            </span>
        </a>

        <a href="{{ route('dashboard.pengumuman.index', ['tab' => 'broadcast']) }}"
            style="padding: 10px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; text-decoration: none; border-bottom: 2px solid {{ $activeTab === 'broadcast' ? 'var(--accent)' : 'transparent' }}; margin-bottom: -2px; color: {{ $activeTab === 'broadcast' ? 'var(--accent)' : 'var(--text-muted)' }}; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
            <i class="fas fa-tower-broadcast"></i>
            <span>Info Broadcast</span>
            <span class="badge {{ $activeTab === 'broadcast' ? 'badge-accent' : 'badge-outline' }}" style="font-size: var(--text-2xs); padding: 2px 7px;">
                {{ $summary['total_broadcast'] }}
            </span>
        </a>

        <a href="{{ route('dashboard.pengumuman.index', ['tab' => 'berita']) }}"
            style="padding: 10px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; text-decoration: none; border-bottom: 2px solid {{ $activeTab === 'berita' ? 'var(--warning, #f59e0b)' : 'transparent' }}; margin-bottom: -2px; color: {{ $activeTab === 'berita' ? 'var(--warning, #f59e0b)' : 'var(--text-muted)' }}; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
            <i class="fas fa-newspaper"></i>
            <span>Berita</span>
            <span class="badge {{ $activeTab === 'berita' ? 'badge-warning' : 'badge-outline' }}" style="font-size: var(--text-2xs); padding: 2px 7px;">
                {{ $summary['total_berita'] }}
            </span>
        </a>
    </div>

    <!-- Unified Datatable Card Wrapper -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- Toolbar Datatable Baku SAE -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if ($activeTab === 'berita')
                        <select id="filterKategoriSelect" name="kategori" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Kategori</option>
                            @foreach ($kategoriList as $kat)
                                <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>
                                    {{ $kat }}
                                </option>
                            @endforeach
                        </select>
                    @elseif ($activeTab === 'pengumuman')
                        <select id="filterTargetPeranSelect" name="target_peran" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Sasaran Peran</option>
                            <option value="semua" {{ $targetPeranFilter === 'semua' ? 'selected' : '' }}>👥 Semua Pengguna</option>
                            <option value="guru" {{ $targetPeranFilter === 'guru' ? 'selected' : '' }}>👨‍🏫 Khusus Guru</option>
                            <option value="tendik" {{ $targetPeranFilter === 'tendik' ? 'selected' : '' }}>🪪 Khusus Tendik</option>
                            <option value="peserta_didik" {{ $targetPeranFilter === 'peserta_didik' ? 'selected' : '' }}>🎓 Khusus Peserta Didik</option>
                            <option value="admin" {{ $targetPeranFilter === 'admin' ? 'selected' : '' }}>🛡️ Khusus Administrator</option>
                        </select>
                    @endif

                    <select id="filterStatusSelect" name="status" class="toolbar-filter-select" style="min-width: 120px; height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $statusFilter === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $statusFilter === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    @if ($q || $targetFilter || $targetPeranFilter || $kategoriFilter || $statusFilter !== '')
                        <a href="{{ route('dashboard.pengumuman.index', ['tab' => $activeTab]) }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari data {{ $activeTab }}..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Responsif Sesuai Tab Aktif -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            @if ($activeTab === 'berita')
                <!-- TABEL BERITA -->
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 80px; text-align: center;">Sampul</th>
                            <th class="sortable-th {{ $sort === 'judul' ? 'sorted' : '' }}" data-sort="judul"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Judul &amp; Ringkasan Berita
                                <span class="sort-icon">{!! $sort === 'judul' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'kategori' ? 'sorted' : '' }}" data-sort="kategori"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px; cursor: pointer;">
                                Kategori
                                <span class="sort-icon">{!! $sort === 'kategori' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'views' ? 'sorted' : '' }}" data-sort="views"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px; cursor: pointer;">
                                Tayangan
                                <span class="sort-icon">{!! $sort === 'views' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'is_active' ? 'sorted' : '' }}" data-sort="is_active"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px; cursor: pointer;">
                                Status
                                <span class="sort-icon">{!! $sort === 'is_active' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'created_at' ? 'sorted' : '' }}" data-sort="created_at"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; cursor: pointer;">
                                Tanggal
                                <span class="sort-icon">{!! $sort === 'created_at' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $index => $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    {{ $list->firstItem() + $index }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->gambar)
                                        <img src="{{ asset($item->gambar) }}" alt="Sampul"
                                            style="width: 50px; height: 38px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    @else
                                        <div style="width: 50px; height: 38px; display: inline-flex; align-items: center; justify-content: center; background: rgba(99,102,241,0.1); border-radius: var(--radius-sm); color: var(--primary); font-size: var(--text-xs);">
                                            <i class="fas fa-newspaper"></i>
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base);">
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;">
                                        <a href="{{ route('berita.show', $item->slug) }}" target="_blank" style="color: var(--text-color); text-decoration: none;">
                                            {{ $item->judul }} <i class="fas fa-arrow-up-right-from-square" style="font-size: var(--text-2xs); opacity: 0.6;"></i>
                                        </a>
                                    </div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted); line-height: 1.4; max-width: 500px;">
                                        {{ Str::limit($item->excerpt, 110) }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span class="badge badge-accent" style="font-size: var(--text-2xs); padding: 3px 8px;">
                                        {{ $item->kategori }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs); padding: 3px 7px;">
                                        <i class="fas fa-eye me-1 text-primary"></i> {{ number_format($item->views) }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($canUpdate)
                                        <label class="switch-container" style="position: relative; display: inline-block; width: 38px; height: 20px; margin: 0; cursor: pointer;">
                                            <input type="checkbox" class="toggle-berita-status" data-id="{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }} style="opacity: 0; width: 0; height: 0;">
                                            <span class="slider-toggle-crud" style="position: absolute; inset: 0; background-color: {{ $item->is_active ? 'var(--success, #10b981)' : '#64748b' }}; transition: .3s; border-radius: var(--radius-2xl);"></span>
                                        </label>
                                    @else
                                        <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-outline' }}">
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-xs); color: var(--text-muted);">
                                    <div>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</div>
                                    <div style="opacity: 0.85;"><i class="fas fa-user-pen me-1"></i> {{ $item->penulis_nama ?: 'Redaksi' }}</div>
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <div class="table-actions" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <a href="{{ route('berita.show', $item->slug) }}" target="_blank" class="btn-icon" title="Lihat di Web Publik">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-berita" data-item='@json($item)' title="Ubah Berita">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.pengumuman.berita.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->judul }}" style="margin: 0; display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus Berita">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 36px; text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    <i class="fas fa-newspaper mb-2" style="font-size: var(--text-3xl); opacity: 0.4;"></i>
                                    <div>Belum ada data berita yang sesuai. Klik <strong>"Tulis Berita"</strong> untuk menambahkan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif ($activeTab === 'broadcast')
                <!-- TABEL INFO BROADCAST (TEKS BERJALAN) -->
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                            <th class="sortable-th {{ $sort === 'judul' ? 'sorted' : '' }}" data-sort="judul"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Judul &amp; Teks Berjalan Running Text
                                <span class="sort-icon">{!! $sort === 'judul' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Lokasi Tampil</th>
                            <th class="sortable-th {{ $sort === 'is_active' ? 'sorted' : '' }}" data-sort="is_active"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px; cursor: pointer;">
                                Status
                                <span class="sort-icon">{!! $sort === 'is_active' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'created_at' ? 'sorted' : '' }}" data-sort="created_at"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px; cursor: pointer;">
                                Tanggal
                                <span class="sort-icon">{!! $sort === 'created_at' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $index => $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    {{ $list->firstItem() + $index }}
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base);">
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;">
                                        {{ $item->judul }}
                                    </div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted); line-height: 1.4; max-width: 580px; font-family: monospace;">
                                        ● {{ $item->isi }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span class="badge badge-accent" style="font-size: var(--text-xs); padding: 4px 8px;">
                                        <i class="fas fa-globe me-1"></i> Running Text Publik
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($canUpdate)
                                        <label class="switch-container" style="position: relative; display: inline-block; width: 38px; height: 20px; margin: 0; cursor: pointer;">
                                            <input type="checkbox" class="toggle-pengumuman-status" data-id="{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }} style="opacity: 0; width: 0; height: 0;">
                                            <span class="slider-toggle-crud" style="position: absolute; inset: 0; background-color: {{ $item->is_active ? 'var(--success, #10b981)' : '#64748b' }}; transition: .3s; border-radius: var(--radius-2xl);"></span>
                                        </label>
                                    @else
                                        <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-outline' }}">
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-xs); color: var(--text-muted);">
                                    <div>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</div>
                                    <div style="opacity: 0.85;"><i class="fas fa-user-pen me-1"></i> {{ $item->penulis_nama ?: 'Administrator' }}</div>
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <div class="table-actions" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <button type="button" class="btn-icon btn-preview-pengumuman" data-item='@json($item)' title="Lihat Pratinjau">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-broadcast" data-item='@json($item)' title="Ubah Broadcast">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.pengumuman.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->judul }}" style="margin: 0; display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus Broadcast">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 36px; text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    <i class="fas fa-tower-broadcast mb-2" style="font-size: var(--text-3xl); opacity: 0.4;"></i>
                                    <div>Belum ada teks broadcast berjalan. Klik <strong>"Buat Broadcast"</strong> untuk menambahkan running text portal.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <!-- TABEL PENGUMUMAN (LONCENG / PERAN) -->
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                            <th class="sortable-th {{ $sort === 'judul' ? 'sorted' : '' }}" data-sort="judul"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Judul &amp; Pesan Pengumuman
                                <span class="sort-icon">{!! $sort === 'judul' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'target_peran' ? 'sorted' : '' }}" data-sort="target_peran"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px; cursor: pointer;">
                                Ditujukan Kepada
                                <span class="sort-icon">{!! $sort === 'target_peran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Pembaca</th>
                            <th class="sortable-th {{ $sort === 'is_active' ? 'sorted' : '' }}" data-sort="is_active"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px; cursor: pointer;">
                                Status
                                <span class="sort-icon">{!! $sort === 'is_active' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'created_at' ? 'sorted' : '' }}" data-sort="created_at"
                                style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px; cursor: pointer;">
                                Tanggal
                                <span class="sort-icon">{!! $sort === 'created_at' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th style="padding: 12px 16px; font-size: var(--text-sm); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $index => $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    {{ $list->firstItem() + $index }}
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base);">
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px;">
                                        {{ $item->judul }}
                                    </div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted); line-height: 1.4; max-width: 520px;">
                                        {{ Str::limit($item->isi, 130) }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-xs);">
                                    @if ($item->target_peran === 'guru')
                                        <span class="badge badge-primary" style="padding: 4px 8px;">
                                            <i class="fas fa-chalkboard-user me-1"></i> Guru
                                        </span>
                                    @elseif($item->target_peran === 'tendik')
                                        <span class="badge badge-accent" style="padding: 4px 8px;">
                                            <i class="fas fa-id-badge me-1"></i> Tendik
                                        </span>
                                    @elseif($item->target_peran === 'peserta_didik')
                                        <span class="badge badge-success" style="padding: 4px 8px;">
                                            <i class="fas fa-user-graduate me-1"></i> Siswa
                                        </span>
                                    @elseif($item->target_peran === 'admin')
                                        <span class="badge badge-danger" style="padding: 4px 8px;">
                                            <i class="fas fa-user-shield me-1"></i> Admin
                                        </span>
                                    @else
                                        <span class="badge badge-outline" style="padding: 4px 8px;">
                                            <i class="fas fa-users me-1"></i> Semua Pengguna
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-xs); padding: 3px 8px;"
                                        title="Telah dibaca oleh {{ $item->jumlah_pembaca }} pengguna">
                                        <i class="fas fa-eye me-1 text-primary"></i> {{ $item->jumlah_pembaca }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($canUpdate)
                                        <label class="switch-container" style="position: relative; display: inline-block; width: 38px; height: 20px; margin: 0; cursor: pointer;">
                                            <input type="checkbox" class="toggle-pengumuman-status" data-id="{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }} style="opacity: 0; width: 0; height: 0;">
                                            <span class="slider-toggle-crud" style="position: absolute; inset: 0; background-color: {{ $item->is_active ? 'var(--success, #10b981)' : '#64748b' }}; transition: .3s; border-radius: var(--radius-2xl);"></span>
                                        </label>
                                    @else
                                        <span class="badge {{ $item->is_active ? 'badge-success' : 'badge-outline' }}">
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-xs); color: var(--text-muted);">
                                    <div>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}</div>
                                    <div style="opacity: 0.85;"><i class="fas fa-user-pen me-1"></i> {{ $item->penulis_nama ?: 'Administrator' }}</div>
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <div class="table-actions" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <button type="button" class="btn-icon btn-preview-pengumuman" data-item='@json($item)' title="Lihat Pratinjau">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-pengumuman" data-item='@json($item)' title="Ubah Pengumuman">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.pengumuman.destroy', $item->id) }}" method="POST" data-confirm="delete" data-name="{{ $item->judul }}" style="margin: 0; display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus Pengumuman">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 36px; text-align: center; color: var(--text-muted); font-size: var(--text-base);">
                                    <i class="fas fa-bullhorn mb-2" style="font-size: var(--text-3xl); opacity: 0.4;"></i>
                                    <div>Belum ada data pengumuman. Klik <strong>"Buat Pengumuman"</strong> untuk memulai.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Footer Paginasi Datatable -->
        @if ($list->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $list])
            </div>
        @endif
    </div>

    <!-- Modal Form Create/Edit Pengumuman -->
    <div id="pengumumanModal" class="modal-backdrop">
        <div class="card" style="max-width: 560px; width: 92%; margin: auto; padding: 22px; border-radius: var(--radius-xl); box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalTitle" style="font-size: var(--text-lg); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-bullhorn text-primary me-2"></i> Buat Pengumuman Baru
                </h3>
                <button type="button" id="btnCloseModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="pengumumanForm" action="{{ route('dashboard.pengumuman.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>
                <input type="hidden" name="tipe" value="pengumuman">
                <input type="hidden" name="target" value="pengguna">

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Judul Pengumuman <span style="color: var(--danger, #ef4444);">*</span></label>
                    <input type="text" name="judul" id="inputJudul" required placeholder="Contoh: Jadwal Ujian Akhir Semester" maxlength="255">
                </div>

                <div class="modal-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group-compact">
                        <label>Penulis / Penerbit</label>
                        <input type="text" name="penulis_nama" id="inputPenulisNama" placeholder="Contoh: Kurikulum / Humas" maxlength="100">
                    </div>

                    <div class="form-group-compact">
                        <label>Ditujukan Kepada</label>
                        <select name="target_peran" id="inputTargetPeran">
                            <option value="semua">Semua Pengguna</option>
                            <option value="guru">Khusus Guru</option>
                            <option value="tendik">Khusus Tendik</option>
                            <option value="peserta_didik">Khusus Peserta Didik</option>
                            <option value="admin">Khusus Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Isi Pesan Pengumuman <span style="color: var(--danger, #ef4444);">*</span></label>
                    <textarea name="isi" id="inputIsi" required rows="4" placeholder="Tuliskan isi pengumuman secara jelas..."
                        style="width: 100%; padding: 10px 12px; font-size: var(--text-base); border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.5;"></textarea>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding: 10px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                    <input type="checkbox" name="is_active" id="inputIsActive" value="1" checked style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="inputIsActive" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-color); cursor: pointer; margin: 0;">
                        Aktifkan pengumuman ini segera
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnCancelModal" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Create/Edit Broadcast (Running Text) -->
    <div id="broadcastModal" class="modal-backdrop">
        <div class="card" style="max-width: 560px; width: 92%; margin: auto; padding: 22px; border-radius: var(--radius-xl); box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="broadcastModalTitle" style="font-size: var(--text-lg); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-tower-broadcast text-accent me-2"></i> Buat Info Broadcast Baru
                </h3>
                <button type="button" id="btnCloseBroadcastModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="broadcastForm" action="{{ route('dashboard.pengumuman.store') }}" method="POST">
                @csrf
                <div id="broadcastMethodField"></div>
                <input type="hidden" name="tipe" value="broadcast">
                <input type="hidden" name="target" value="publik">
                <input type="hidden" name="target_peran" value="semua">

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Label / Judul Broadcast <span style="color: var(--danger, #ef4444);">*</span></label>
                    <input type="text" name="judul" id="inputBroadcastJudul" required placeholder="Contoh: Info PPDB 2026 / Jadwal Libur" maxlength="255">
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Penulis / Penerbit</label>
                    <input type="text" name="penulis_nama" id="inputBroadcastPenulis" placeholder="Contoh: Administrator / Humas" maxlength="100">
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Teks Berjalan Running Text <span style="color: var(--danger, #ef4444);">*</span></label>
                    <textarea name="isi" id="inputBroadcastIsi" required rows="3" placeholder="Contoh: Pendaftaran Ujian Sekolah Tahun Ajaran 2026/2027 telah dibuka..."
                        style="width: 100%; padding: 10px 12px; font-size: var(--text-base); border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.5;"></textarea>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: 4px;">
                        Teks ini akan tayang langsung pada ticker teks berjalan portal beranda utama.
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding: 10px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                    <input type="checkbox" name="is_active" id="inputBroadcastIsActive" value="1" checked style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="inputBroadcastIsActive" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-color); cursor: pointer; margin: 0;">
                        Tayangkan teks berjalan ini sekarang
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnCancelBroadcastModal" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Create/Edit Berita (Artikel Publik) -->
    <div id="beritaModal" class="modal-backdrop">
        <div class="card" style="max-width: 640px; width: 92%; margin: auto; padding: 22px; border-radius: var(--radius-xl); box-shadow: 0 16px 40px rgba(0,0,0,0.4); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="beritaModalTitle" style="font-size: var(--text-lg); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-newspaper text-warning me-2"></i> Tulis Berita Baru
                </h3>
                <button type="button" id="btnCloseBeritaModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="beritaForm" action="{{ route('dashboard.pengumuman.berita.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="beritaMethodField"></div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Judul Berita <span style="color: var(--danger, #ef4444);">*</span></label>
                    <input type="text" name="judul" id="inputBeritaJudul" required placeholder="Contoh: Siswa Raih Medali Emas Olimpiade Riset" maxlength="255">
                </div>

                <div class="modal-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group-compact">
                        <label>Kategori Berita <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select name="kategori" id="inputBeritaKategori" required>
                            <option value="Akademik">Akademik</option>
                            <option value="Prestasi">Prestasi</option>
                            <option value="Kegiatan">Kegiatan</option>
                            <option value="Pengumuman">Pengumuman</option>
                            <option value="Umum">Umum</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label>Penulis / Redaksi</label>
                        <input type="text" name="penulis_nama" id="inputBeritaPenulis" placeholder="Contoh: Humas Sekolah / Redaksi" maxlength="100">
                    </div>
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Foto Sampul (Opsional)</label>
                    <input type="file" name="gambar" id="inputBeritaGambar" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                    <div id="beritaCurrentGambarWrap" style="display: none; margin-top: 8px; align-items: center; gap: 10px;">
                        <img id="beritaCurrentGambar" src="" alt="Sampul" style="width: 70px; height: 50px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <label style="font-size: var(--text-xs); color: var(--danger, #ef4444); cursor: pointer; display: inline-flex; align-items: center; gap: 4px; margin: 0;">
                            <input type="checkbox" name="hapus_gambar" value="1"> Hapus foto sampul saat ini
                        </label>
                    </div>
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Ringkasan / Lead (Opsional)</label>
                    <textarea name="ringkasan" id="inputBeritaRingkasan" rows="2" placeholder="Tuliskan 1-2 kalimat ringkasan yang menarik pembaca..."
                        style="width: 100%; padding: 10px 12px; font-size: var(--text-base); border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.5;"></textarea>
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Isi Lengkap Berita <span style="color: var(--danger, #ef4444);">*</span></label>
                    <textarea name="isi" id="inputBeritaIsi" required rows="6" placeholder="Tuliskan isi berita secara lengkap dan runtut..."
                        style="width: 100%; padding: 10px 12px; font-size: var(--text-base); border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.6;"></textarea>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding: 10px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                    <input type="checkbox" name="is_active" id="inputBeritaIsActive" value="1" checked style="width: 16px; height: 16px; cursor: pointer;">
                    <label for="inputBeritaIsActive" style="font-size: var(--text-sm); font-weight: 600; color: var(--text-color); cursor: pointer; margin: 0;">
                        Publikasikan berita ini di halaman utama &amp; portal publik
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnCancelBeritaModal" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">Terbitkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Pengumuman / Broadcast -->
    <div id="previewPengumumanModal" class="modal-backdrop">
        <div class="card" style="max-width: 580px; width: 92%; margin: auto; padding: 24px; border-radius: var(--radius-xl); box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <div>
                    <span id="previewTargetBadge" class="badge badge-primary" style="font-size: var(--text-xs); margin-bottom: 6px;"></span>
                    <span id="previewPeranBadge" class="badge badge-outline" style="font-size: var(--text-xs); margin-bottom: 6px;"></span>
                    <h3 id="previewJudul" style="font-size: var(--text-xl); font-weight: 800; color: var(--text-color); margin: 6px 0 0 0; line-height: 1.3;"></h3>
                </div>
                <button type="button" id="btnClosePreviewModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; font-size: var(--text-sm); color: var(--text-muted); margin-bottom: 16px;">
                <div><i class="fas fa-user-pen me-1 text-primary"></i> <span id="previewPenulis"></span></div>
                <span>&bull;</span>
                <div><i class="fas fa-clock me-1"></i> <span id="previewTanggal"></span></div>
            </div>

            <div id="previewIsi" style="font-size: var(--text-base); line-height: 1.6; color: var(--text-color); background: rgba(255,255,255,0.02); padding: 14px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px; white-space: pre-line;"></div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" id="btnClosePreviewBtn" class="btn btn-outline">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/pengumuman.js') }}?v={{ file_exists(public_path('js/pengumuman.js')) ? filemtime(public_path('js/pengumuman.js')) : time() }}"></script>
    @endpush
@endsection
