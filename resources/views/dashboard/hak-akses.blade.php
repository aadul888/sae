@extends('layouts.dashboard')

@section('title', 'Hak Akses & Peran — SAE')
@section('dash_title', 'Hak Akses & Peran')

@section('content')
    <div id="hakAksesContainer"
        data-active-role="{{ $activeRole }}"
        data-role-name="{{ $roles[$activeRole]['name'] ?? ucfirst($activeRole) }}"
        data-toggle-url="{{ route('dashboard.hak-akses.toggle') }}"
        data-sync-url="{{ route('dashboard.hak-akses.sync') }}"
        data-reset-url="{{ route('dashboard.hak-akses.reset') }}"
        data-add-module-url="{{ route('dashboard.hak-akses.add-module') }}"
        data-remove-module-url="{{ route('dashboard.hak-akses.remove-module') }}">

        <!-- Banner Header -->
        <div class="dash-banner">
            <div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                    <i class="fas fa-shield-halved text-primary me-2"></i> Pengaturan Hak Akses &amp; Peran
                </h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                    @if ($activeRole === 'global')
                        Kelola matriks izin modul dan aksi operasional CRUD pengguna dalam satu datatable global terpadu.
                    @else
                        Kelola matriks hak akses modul dan aksi operasional CRUD untuk peran <strong>{{ $roles[$activeRole]['name'] ?? ucfirst($activeRole) }}</strong>.
                    @endif
                </p>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" id="btnSyncModules" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.82rem;">
                    <i class="fas fa-arrows-rotate me-1"></i> Sinkronkan Modul
                </button>
                <button type="button" id="btnResetDefault" class="btn btn-outline"
                    style="padding: 8px 16px; font-size: 0.82rem; border-color: rgba(239, 68, 68, 0.4); color: #ef4444;">
                    <i class="fas fa-rotate-left me-1"></i>
                    @if ($activeRole === 'global')
                        Reset Default (Semua Peran)
                    @else
                        Reset Default ({{ $roles[$activeRole]['name'] ?? ucfirst($activeRole) }})
                    @endif
                </button>
            </div>
        </div>

        <!-- Tab Navigasi Baku SAE (.periode-nav-wrapper) -->
        <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'global']) }}"
                    class="periode-nav-tab {{ $activeRole === 'global' ? 'active' : '' }}">
                    <i class="fas fa-globe"></i> Semua Peran (Global)
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'admin']) }}"
                    class="periode-nav-tab {{ $activeRole === 'admin' ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i> Administrator ({{ $counts['admin'] }})
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'guru']) }}"
                    class="periode-nav-tab {{ $activeRole === 'guru' ? 'active' : '' }}">
                    <i class="fas fa-chalkboard-user"></i> Guru ({{ $counts['guru'] }})
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'tendik']) }}"
                    class="periode-nav-tab {{ $activeRole === 'tendik' ? 'active' : '' }}">
                    <i class="fas fa-id-badge"></i> Tendik ({{ $counts['tendik'] }})
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'peserta_didik']) }}"
                    class="periode-nav-tab {{ $activeRole === 'peserta_didik' ? 'active' : '' }}">
                    <i class="fas fa-user-graduate"></i> Peserta Didik ({{ $counts['peserta_didik'] }})
                </a>
            </div>
        </div>

        <!-- Toolbar Header Datatable Baku -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="all">Semua</option>
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterGroup" class="toolbar-filter-select" style="min-width: 150px;">
                        <option value="">Semua Kelompok</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </select>

                    @if ($activeRole !== 'global')
                        <button type="button" id="btnOpenAddModule" class="btn btn-primary"
                            style="padding: 7px 14px; font-size: 0.82rem;">
                            <i class="fas fa-plus me-1"></i> Tambah Modul ke Peran
                        </button>
                    @endif

                    <span class="badge badge-outline" id="totalBadge" style="font-size: 0.74rem;">
                        Total: {{ count($tableModules) }} Modul
                    </span>
                </div>

                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" placeholder="Cari nama modul / fitur..." autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Container Datatable -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" id="hakAksesTable" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th class="module-th sortable-th" data-col="no"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center; cursor: pointer;">
                            No <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="module-th sortable-th" data-col="modul"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Modul &amp; Fitur Sistem <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="module-th sortable-th" data-col="kelompok"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px; cursor: pointer;">
                            Kelompok <span class="sort-icon">&#9650;&#9660;</span>
                        </th>

                        @if ($activeRole === 'global')
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: #6366f1; text-transform: uppercase; text-align: center; width: 110px;">
                                <i class="fas fa-user-shield me-1"></i> Admin
                            </th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: #10b981; text-transform: uppercase; text-align: center; width: 110px;">
                                <i class="fas fa-chalkboard-user me-1"></i> Guru
                            </th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: #0ea5e9; text-transform: uppercase; text-align: center; width: 110px;">
                                <i class="fas fa-id-badge me-1"></i> Tendik
                            </th>
                            <th style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: #f59e0b; text-transform: uppercase; text-align: center; width: 110px;">
                                <i class="fas fa-user-graduate me-1"></i> Siswa
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 90px;">
                                Izin CRUD
                            </th>
                        @else
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; min-width: 270px;">
                                <div style="display: inline-flex; align-items: center; gap: 14px;">
                                    <span>Aksi:</span>
                                    <span style="color: #3b82f6; font-size: 0.73rem;" title="Tambah (Create)"><i class="fas fa-plus-circle me-1"></i>Tambah</span>
                                    <span style="color: #10b981; font-size: 0.73rem;" title="Lihat (Read)"><i class="fas fa-eye me-1"></i>Lihat</span>
                                    <span style="color: #f59e0b; font-size: 0.73rem;" title="Ubah (Update)"><i class="fas fa-pen-to-square me-1"></i>Ubah</span>
                                    <span style="color: #ef4444; font-size: 0.73rem;" title="Hapus (Delete)"><i class="fas fa-trash me-1"></i>Hapus</span>
                                </div>
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 75px;">
                                Kelola
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @forelse ($tableModules as $index => $item)
                        <tr class="module-row" data-modul="{{ strtolower($item['label']) }}"
                            data-kelompok="{{ strtolower($item['group']) }}"
                            data-name="{{ strtolower($item['label'] . ' ' . $item['key']) }}"
                            data-group="{{ $item['group'] }}"
                            style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="row-number"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-muted); text-align: center; font-size: 0.84rem;"
                                data-label="No">
                                {{ $index + 1 }}
                            </td>
                            <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Modul">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 34px; height: 34px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                                        <i class="fas {{ $item['icon'] }}"></i>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <span style="font-weight: 700;">{{ $item['label'] }}</span>
                                        <span style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">{{ $item['key'] }}</span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Kelompok">
                                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                    {{ $item['group'] }}
                                </span>
                            </td>

                            @if ($activeRole === 'global')
                                @php
                                    $rolesMap = [
                                        'admin' => ['label' => 'Admin', 'color' => '#6366f1'],
                                        'guru' => ['label' => 'Guru', 'color' => '#10b981'],
                                        'tendik' => ['label' => 'Tendik', 'color' => '#0ea5e9'],
                                        'peserta_didik' => ['label' => 'Siswa', 'color' => '#f59e0b'],
                                    ];
                                @endphp
                                @foreach ($rolesMap as $rKey => $rMeta)
                                    @php
                                        $rData = $item['roles'][$rKey] ?? ['is_allowed' => false, 'is_locked' => false];
                                        $isAllowed = (bool) ($rData['is_allowed'] ?? false);
                                        $isLocked = (bool) ($rData['is_locked'] ?? false);
                                    @endphp
                                    <td style="padding: 14px 14px; text-align: center;" data-label="{{ $rMeta['label'] }}">
                                        <label class="switch-container"
                                            style="position: relative; display: inline-block; width: 38px; height: 21px; margin: 0; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; vertical-align: middle;">
                                            <input type="checkbox" class="role-quick-toggle"
                                                data-role="{{ $rKey }}"
                                                data-key="{{ $item['key'] }}"
                                                data-action="read"
                                                data-color="{{ $rMeta['color'] }}"
                                                {{ $isAllowed ? 'checked' : '' }}
                                                {{ $isLocked ? 'disabled' : '' }}
                                                style="opacity: 0; width: 0; height: 0;">
                                            <span class="slider-toggle-crud"
                                                style="position: absolute; inset: 0; background-color: {{ $isAllowed ? $rMeta['color'] : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                        </label>
                                    </td>
                                @endforeach

                                <td style="padding: 14px 18px; text-align: center;" data-label="Izin CRUD">
                                    <button type="button" class="btn-icon btn-open-crud-modal"
                                        title="Konfigurasi Izin Granular CRUD (Tambah, Baca, Ubah, Hapus)"
                                        data-module="{{ json_encode($item, JSON_UNESCAPED_UNICODE) }}"
                                        style="background: rgba(99, 102, 241, 0.12); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.25); width: 34px; height: 34px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;">
                                        <i class="fas fa-sliders" style="font-size: 0.85rem;"></i>
                                    </button>
                                </td>
                            @else
                                <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: inline-flex; align-items: center; gap: 16px; justify-content: flex-end;">
                                        <!-- Tambah (Create) -->
                                        <div style="display: flex; align-items: center; gap: 6px;" title="Tambah (Create)">
                                            <i class="fas fa-plus-circle" style="color: #3b82f6; font-size: 1rem;"></i>
                                            <label class="switch-container"
                                                style="position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }};">
                                                <input type="checkbox" class="crud-toggle" data-role="{{ $activeRole }}"
                                                    data-key="{{ $item['key'] }}" data-action="create" data-color="#3b82f6"
                                                    {{ $item['can_create'] ? 'checked' : '' }}
                                                    {{ $item['is_locked'] ? 'disabled' : '' }}
                                                    style="opacity: 0; width: 0; height: 0;">
                                                <span class="slider-toggle-crud"
                                                    style="position: absolute; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }}; inset: 0; background-color: {{ $item['can_create'] ? '#3b82f6' : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                            </label>
                                        </div>

                                        <!-- Lihat (Read) -->
                                        <div style="display: flex; align-items: center; gap: 6px;" title="Lihat (Read)">
                                            <i class="fas fa-eye" style="color: #10b981; font-size: 1rem;"></i>
                                            <label class="switch-container"
                                                style="position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }};">
                                                <input type="checkbox" class="crud-toggle" data-role="{{ $activeRole }}"
                                                    data-key="{{ $item['key'] }}" data-action="read" data-color="#10b981"
                                                    {{ $item['can_read'] ? 'checked' : '' }}
                                                    {{ $item['is_locked'] ? 'disabled' : '' }}
                                                    style="opacity: 0; width: 0; height: 0;">
                                                <span class="slider-toggle-crud"
                                                    style="position: absolute; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }}; inset: 0; background-color: {{ $item['can_read'] ? '#10b981' : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                            </label>
                                        </div>

                                        <!-- Ubah (Update) -->
                                        <div style="display: flex; align-items: center; gap: 6px;" title="Ubah (Update)">
                                            <i class="fas fa-pen-to-square" style="color: #f59e0b; font-size: 1rem;"></i>
                                            <label class="switch-container"
                                                style="position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }};">
                                                <input type="checkbox" class="crud-toggle" data-role="{{ $activeRole }}"
                                                    data-key="{{ $item['key'] }}" data-action="update" data-color="#f59e0b"
                                                    {{ $item['can_update'] ? 'checked' : '' }}
                                                    {{ $item['is_locked'] ? 'disabled' : '' }}
                                                    style="opacity: 0; width: 0; height: 0;">
                                                <span class="slider-toggle-crud"
                                                    style="position: absolute; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }}; inset: 0; background-color: {{ $item['can_update'] ? '#f59e0b' : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                            </label>
                                        </div>

                                        <!-- Hapus (Delete) -->
                                        <div style="display: flex; align-items: center; gap: 6px;" title="Hapus (Delete)">
                                            <i class="fas fa-trash" style="color: #ef4444; font-size: 1rem;"></i>
                                            <label class="switch-container"
                                                style="position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }};">
                                                <input type="checkbox" class="crud-toggle" data-role="{{ $activeRole }}"
                                                    data-key="{{ $item['key'] }}" data-action="delete"
                                                    data-color="#ef4444" {{ $item['can_delete'] ? 'checked' : '' }}
                                                    {{ $item['is_locked'] ? 'disabled' : '' }}
                                                    style="opacity: 0; width: 0; height: 0;">
                                                <span class="slider-toggle-crud"
                                                    style="position: absolute; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }}; inset: 0; background-color: {{ $item['can_delete'] ? '#ef4444' : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px; text-align: center;" data-label="Kelola">
                                    @if (!$item['is_locked'])
                                        <button type="button" class="btn-icon btn-remove-module"
                                            data-key="{{ $item['key'] }}" data-name="{{ $item['label'] }}"
                                            title="Hapus Modul {{ $item['label'] }} dari Peran {{ $roles[$activeRole]['name'] ?? ucfirst($activeRole) }}"
                                            style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;">
                                            <i class="fas fa-trash-can" style="font-size: 0.82rem;"></i>
                                        </button>
                                    @else
                                        <span style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-muted); opacity: 0.4;"
                                            title="Modul ini dikunci sistem">
                                            <i class="fas fa-lock" style="font-size: 0.8rem;"></i>
                                        </span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $activeRole === 'global' ? 8 : 5 }}"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Tidak ada modul yang tersedia untuk peran ini.</div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noSearchResultRow" style="display: none;">
                        <td colspan="{{ $activeRole === 'global' ? 8 : 5 }}"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-magnifying-glass mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Tidak ada modul yang cocok dengan filter pencarian.</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div id="tablePaginationWrap" class="custom-pagination"></div>

        <!-- Modal Granular CRUD (Untuk Tampilan Global) -->
        <div id="modalGranularCrud" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 620px; width: 94%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 10px 35px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--card-bg, #1e293b);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <div>
                        <h3 id="modalCrudTitle" style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Detail Izin CRUD
                        </h3>
                        <span id="modalCrudSubtitle" style="font-size: 0.74rem; font-family: monospace; color: var(--primary);"></span>
                    </div>
                    <button type="button" id="btnCloseCrudModal"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="modalCrudContent" style="display: flex; flex-direction: column; gap: 12px; max-height: 60vh; overflow-y: auto; padding-right: 4px;">
                    <!-- Rendered dynamically by JS -->
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" id="btnDoneCrudModal" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.84rem;">
                        Selesai
                    </button>
                </div>
            </div>
        </div>

        @if ($activeRole !== 'global')
            <!-- Modal Tambah Modul ke Peran -->
            <div id="modalAddModule" class="modal-backdrop"
                style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div class="card"
                    style="max-width: 520px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--card-bg, #1e293b);">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                        <h3
                            style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus-circle text-primary"></i> Tambah Modul ke Peran {{ ucfirst(str_replace('_', ' ', $activeRole)) }}
                        </h3>
                        <button type="button" id="btnCloseAddModule"
                            style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form id="formAddModule">
                        <input type="hidden" name="role" value="{{ $activeRole }}">
                        <div class="form-group-compact" style="margin-bottom: 18px;">
                            <label
                                style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block;">
                                Pilih Modul Sistem <span style="color: #ef4444;">*</span>
                            </label>
                            <div style="position: relative; margin-bottom: 8px;">
                                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted); pointer-events: none;"></i>
                                <input type="text" id="filterModuleOptions" placeholder="Ketik untuk mencari modul..."
                                    style="width: 100%; height: 36px; padding: 0 12px 0 34px; border: 1px solid rgba(255,255,255,0.15); background-color: #0f172a; color: #f8fafc; border-radius: 6px; font-size: 0.82rem; box-sizing: border-box;" autocomplete="off">
                            </div>
                            <select name="permission_key" id="selectAddModule" required
                                style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid rgba(255,255,255,0.15); background-color: #1e293b !important; color: #f8fafc !important; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box;">
                                <option value="" style="background-color: #1e293b; color: #94a3b8;">-- Pilih Modul untuk Ditambahkan --</option>
                                @forelse ($availableModulesToAdd as $groupName => $groupModules)
                                    <optgroup label="📂 {{ $groupName }}" style="color: #94a3b8; background-color: #0f172a; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        @foreach ($groupModules as $mVal)
                                            <option value="{{ $mVal['key'] }}" style="background-color: #1e293b; color: #f8fafc; padding: 8px 12px;">{{ $mVal['label'] }} ({{ $mVal['key'] }})</option>
                                        @endforeach
                                    </optgroup>
                                @empty
                                    <option disabled style="background-color: #1e293b; color: #94a3b8;">Semua modul sistem sudah aktif untuk peran ini</option>
                                @endforelse
                                <optgroup label="─────────────────────" style="color: #334155; background-color: #0f172a;"></optgroup>
                                <option value="__NEW_CUSTOM_MODULE__" style="background-color: #0f172a; color: #38bdf8; font-weight: 600; padding: 8px 12px;">+ Daftarkan Modul Baru / Mendatang...</option>
                            </select>

                            <!-- Input Dinamis jika Mendaftarkan Modul Baru / Mendatang -->
                            <div id="customModuleFields" style="display: none; flex-direction: column; gap: 8px; margin-top: 14px; padding: 14px; background: rgba(56, 189, 248, 0.06); border: 1px dashed rgba(56, 189, 248, 0.35); border-radius: 10px;">
                                <label style="font-size: 0.8rem; font-weight: 600; color: #38bdf8; display: block;">
                                    <i class="fas fa-sparkles me-1"></i> Nama Modul Baru / Mendatang <span style="color: #ef4444;">*</span>
                                </label>
                                <input type="text" id="inputCustomModuleName" name="custom_name" placeholder="Contoh: Perpustakaan Digital, Bimbingan Konseling"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid rgba(255,255,255,0.18); background-color: #1e293b; color: #f8fafc; border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                                <p style="font-size: 0.72rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                    Sistem akan otomatis membuat izin RBAC dan merefleksikannya di matriks hak akses serta sidebar.
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                            <button type="button" id="btnCancelAddModule" class="btn btn-outline"
                                style="padding: 9px 18px; font-size: 0.85rem; border-radius: 8px;">Batal</button>
                            <button type="submit" id="btnSubmitAddModule" class="btn btn-primary"
                                style="padding: 9px 20px; font-size: 0.85rem; border-radius: 8px; font-weight: 600;">
                                <i class="fas fa-plus me-1"></i> Tambahkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hak-akses.js') }}"></script>
@endpush
