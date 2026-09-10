@extends('layouts.dashboard')

@section('title', 'Hak Akses & Peran — SAE')
@section('dash_title', 'Hak Akses & Peran')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-shield-halved text-primary me-2"></i> Pengaturan Hak Akses &amp; CRUD
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Kelola matriks izin modul dan aksi operasional (Tambah, Lihat, Ubah, Hapus) untuk setiap peran pengguna.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" id="btnSyncModules" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.82rem;">
                <i class="fas fa-arrows-rotate me-1"></i> Sinkronkan Modul
            </button>
            <button type="button" id="btnResetDefault" class="btn btn-outline"
                style="padding: 8px 16px; font-size: 0.82rem; border-color: rgba(239, 68, 68, 0.4); color: #ef4444;">
                <i class="fas fa-rotate-left me-1"></i> Reset Bawaan
            </button>
        </div>
    </div>

    <!-- Role Switcher Tabs (Desktop) -->
    <div class="dash-tabs-nav dash-desktop-tabs">
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'admin']) }}"
            class="btn {{ $activeRole === 'admin' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-user-shield me-1"></i> Administrator ({{ $counts['admin'] }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'guru']) }}"
            class="btn {{ $activeRole === 'guru' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-chalkboard-user me-1"></i> Guru ({{ $counts['guru'] }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'tendik']) }}"
            class="btn {{ $activeRole === 'tendik' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-id-badge me-1"></i> Tendik ({{ $counts['tendik'] ?? 0 }})
        </a>
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'peserta_didik']) }}"
            class="btn {{ $activeRole === 'peserta_didik' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-user-graduate me-1"></i> Peserta Didik ({{ $counts['peserta_didik'] ?? 0 }})
        </a>
    </div>

    <!-- Role Switcher (Mobile Custom Dropdown) -->
    <div class="dash-mobile-tab-select-wrap">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label">
                    <i
                        class="fas {{ $activeRole === 'admin' ? 'fa-user-shield' : ($activeRole === 'guru' ? 'fa-chalkboard-user' : ($activeRole === 'tendik' ? 'fa-id-badge' : 'fa-user-graduate')) }} text-primary me-2"></i>
                    <span>{{ $activeRole === 'admin' ? 'Administrator' : ($activeRole === 'guru' ? 'Guru' : ($activeRole === 'tendik' ? 'Tenaga Kependidikan' : 'Peserta Didik')) }}</span>
                    <span class="badge badge-primary badge-sm ms-2">{{ $counts[$activeRole] ?? 0 }}</span>
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" role="listbox">
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'admin']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'admin' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-user-shield text-primary me-2"></i>
                        <span>Administrator</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'admin' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['admin'] }}</span>
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'guru']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'guru' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-chalkboard-user text-primary me-2"></i>
                        <span>Guru</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'guru' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['guru'] }}</span>
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'tendik']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'tendik' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-id-badge text-primary me-2"></i>
                        <span>Tenaga Kependidikan</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'tendik' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['tendik'] ?? 0 }}</span>
                </a>
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'peserta_didik']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'peserta_didik' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-user-graduate text-primary me-2"></i>
                        <span>Peserta Didik</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'peserta_didik' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['peserta_didik'] ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Toolbar Datatable -->
    <div class="toolbar-row">
        <div class="toolbar-entries" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="all">Semua</option>
            </select>
            <span>entri</span>

            <label for="filterGroup" style="margin: 0 0 0 10px;">Kelompok:</label>
            <select id="filterGroup" class="per-page-select" style="min-width: 140px;">
                <option value="">Semua Kelompok</option>
                @php
                    $availableGroups = collect($tableModules)->pluck('group')->unique()->values();
                @endphp
                @foreach ($availableGroups as $g)
                    <option value="{{ $g }}">{{ $g }}</option>
                @endforeach
            </select>

            <span class="badge badge-outline" id="totalBadge" style="margin-left: 6px; font-size: 0.73rem;">
                Total: {{ count($tableModules) }} Modul
            </span>
        </div>
        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="Cari nama modul..." autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Datatable Table Card -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" id="hakAksesTable" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead class="sticky-table-header">
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">
                        No</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Modul</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">
                        Kelompok</th>
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; min-width: 270px;">
                        <div style="display: inline-flex; align-items: center; gap: 14px;">
                            <span>Aksi:</span>
                            <span style="color: #3b82f6; font-size: 0.73rem;" title="Tambah (Create)"><i
                                    class="fas fa-plus-circle me-1"></i>Tambah</span>
                            <span style="color: #10b981; font-size: 0.73rem;" title="Lihat (Read)"><i
                                    class="fas fa-eye me-1"></i>Lihat</span>
                            <span style="color: #f59e0b; font-size: 0.73rem;" title="Ubah (Update)"><i
                                    class="fas fa-pen-to-square me-1"></i>Ubah</span>
                            <span style="color: #ef4444; font-size: 0.73rem;" title="Hapus (Delete)"><i
                                    class="fas fa-trash me-1"></i>Hapus</span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse ($tableModules as $index => $item)
                    <tr class="module-row" data-name="{{ strtolower($item['label'] . ' ' . $item['key']) }}"
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
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span>{{ $item['label'] }}</span>
                                    @if ($item['is_locked'])
                                        <span class="badge badge-warning"
                                            style="font-size: 0.65rem; padding: 2px 6px;">Kunci Sistem</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 18px; font-size: 0.82rem;" data-label="Kelompok">
                            <span class="badge badge-primary"
                                style="font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                {{ $item['group'] }}
                            </span>
                        </td>
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
                                            data-key="{{ $item['key'] }}" data-action="delete" data-color="#ef4444"
                                            {{ $item['can_delete'] ? 'checked' : '' }}
                                            {{ $item['is_locked'] ? 'disabled' : '' }}
                                            style="opacity: 0; width: 0; height: 0;">
                                        <span class="slider-toggle-crud"
                                            style="position: absolute; cursor: {{ $item['is_locked'] ? 'not-allowed' : 'pointer' }}; inset: 0; background-color: {{ $item['can_delete'] ? '#ef4444' : '#64748b' }}; transition: .3s; border-radius: 20px;"></span>
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Tidak ada modul yang tersedia untuk peran ini.</div>
                        </td>
                    </tr>
                @endforelse
                <tr id="noSearchResultRow" style="display: none;">
                    <td colspan="4"
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

    @push('scripts')
        <style>
            .sticky-table-header th {
                position: sticky;
                top: 72px;
                z-index: 10;
                background: var(--card-bg, #111827);
            }

            @media (max-width: 768px) {
                .sticky-table-header th {
                    top: 64px;
                }
            }

            .slider-toggle-crud:before {
                position: absolute;
                content: "";
                height: 14px;
                width: 14px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s;
                border-radius: 50%;
            }

            input:checked+.slider-toggle-crud:before {
                transform: translateX(16px);
            }

            input:disabled+.slider-toggle-crud {
                opacity: 0.5;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                // Unified Alert/Toast (Style Tunggal Mengikuti Standar Aplikasi)
                const showToast = (title, icon = 'success') => {
                    const type = (icon === 'error' || icon === 'danger') ? 'danger' : (icon === 'warning' ?
                        'warning' : 'success');
                    if (window.SAE && typeof window.SAE.toast === 'function') {
                        window.SAE.toast(title, type);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: type === 'danger' ? 'error' : type,
                            title: title,
                            showConfirmButton: false,
                            timer: 1600
                        });
                    }
                };

                // 1. Toggle 4 Aksi (Tambah, Lihat, Ubah, Hapus) di Kolom Aksi
                document.querySelectorAll('.crud-toggle').forEach(toggle => {
                    toggle.addEventListener('change', async (e) => {
                        const target = e.target;
                        const role = target.dataset.role;
                        const key = target.dataset.key;
                        const action = target.dataset.action;
                        const color = target.dataset.color || '#10b981';
                        const isAllowed = target.checked;
                        const slider = target.nextElementSibling;
                        const row = target.closest('tr');

                        // Optimistic UI Update
                        slider.style.backgroundColor = isAllowed ? color : '#64748b';

                        try {
                            const res = await fetch('{{ route('dashboard.hak-akses.toggle') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    role: role,
                                    permission_key: key,
                                    action: action,
                                    is_allowed: isAllowed
                                })
                            });

                            const data = await res.json();
                            if (data.status === 'success') {
                                showToast(data.message || 'Izin berhasil diperbarui.');

                                // Sinkronkan switch UI antar aksi jika read mati / create nyala
                                if (data.data) {
                                    ['create', 'read', 'update', 'delete'].forEach(act => {
                                        const input = row.querySelector(
                                            `.crud-toggle[data-action="${act}"]`);
                                        if (input && typeof data.data['can_' + act] !==
                                            'undefined') {
                                            input.checked = data.data['can_' + act];
                                            const s = input.nextElementSibling;
                                            if (s) {
                                                s.style.backgroundColor = input.checked ? (
                                                        input.dataset.color || '#10b981') :
                                                    '#64748b';
                                            }
                                        }
                                    });
                                }
                            } else {
                                throw new Error(data.message || 'Gagal mengubah izin ' + action);
                            }
                        } catch (err) {
                            // Revert UI jika gagal
                            target.checked = !isAllowed;
                            slider.style.backgroundColor = !isAllowed ? color : '#64748b';
                            showToast(err.message || 'Gagal mengubah izin ' + action, 'danger');
                        }
                    });
                });

                // 2. Client-side Search, Group Filter, & Pagination untuk Datatable
                const liveSearchInput = document.getElementById('liveSearch');
                const clearSearchBtn = document.getElementById('clearSearch');
                const filterGroupSelect = document.getElementById('filterGroup');
                const perPageSelect = document.getElementById('perPageSelect');
                const allRows = Array.from(document.querySelectorAll('.module-row'));
                const noResultRow = document.getElementById('noSearchResultRow');
                const paginationWrap = document.getElementById('tablePaginationWrap');
                const totalBadge = document.getElementById('totalBadge');

                let currentPage = 1;

                const renderTable = () => {
                    const query = (liveSearchInput?.value || '').trim().toLowerCase();
                    const groupFilter = (filterGroupSelect?.value || '').trim();
                    const perPageVal = perPageSelect?.value || '25';
                    const perPage = perPageVal === 'all' ? Infinity : parseInt(perPageVal, 10);

                    // Filter baris
                    const matchedRows = allRows.filter(row => {
                        const name = row.dataset.name || '';
                        const group = row.dataset.group || '';
                        const matchQuery = !query || name.includes(query);
                        const matchGroup = !groupFilter || group === groupFilter;
                        return matchQuery && matchGroup;
                    });

                    const totalMatched = matchedRows.length;
                    if (totalBadge) {
                        totalBadge.textContent = `Total: ${totalMatched} Modul`;
                    }

                    if (totalMatched === 0) {
                        allRows.forEach(r => r.style.display = 'none');
                        if (noResultRow) noResultRow.style.display = '';
                        if (paginationWrap) paginationWrap.innerHTML = '';
                        return;
                    }

                    if (noResultRow) noResultRow.style.display = 'none';

                    // Hitung pagination
                    const totalPages = Math.ceil(totalMatched / perPage);
                    if (currentPage > totalPages) currentPage = Math.max(1, totalPages);

                    const startIdx = (currentPage - 1) * perPage;
                    const endIdx = startIdx + perPage;

                    // Sembunyikan semua dulu
                    allRows.forEach(r => r.style.display = 'none');

                    // Tampilkan hanya baris halaman aktif dan update nomor
                    matchedRows.forEach((row, i) => {
                        if (i >= startIdx && i < endIdx) {
                            row.style.display = '';
                            const noCell = row.querySelector('.row-number');
                            if (noCell) noCell.textContent = i + 1;
                        }
                    });

                    // Render pagination buttons
                    if (totalPages <= 1) {
                        if (paginationWrap) paginationWrap.innerHTML = '';
                        return;
                    }

                    let pagHtml = '';
                    pagHtml +=
                        `<button type="button" class="page-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

                    for (let p = 1; p <= totalPages; p++) {
                        if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
                            pagHtml +=
                                `<button type="button" class="page-btn ${p === currentPage ? 'current' : ''}" data-page="${p}">${p}</button>`;
                        } else if (p === currentPage - 3 || p === currentPage + 3) {
                            pagHtml += `<span class="page-info">&hellip;</span>`;
                        }
                    }

                    pagHtml +=
                        `<button type="button" class="page-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

                    if (paginationWrap) {
                        paginationWrap.innerHTML = pagHtml;
                        paginationWrap.querySelectorAll('.page-btn[data-page]').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const targetP = parseInt(btn.dataset.page, 10);
                                if (targetP >= 1 && targetP <= totalPages && targetP !==
                                    currentPage) {
                                    currentPage = targetP;
                                    renderTable();
                                }
                            });
                        });
                    }
                };

                if (liveSearchInput) {
                    liveSearchInput.addEventListener('input', () => {
                        if (clearSearchBtn) {
                            clearSearchBtn.classList.toggle('visible', !!liveSearchInput.value);
                        }
                        currentPage = 1;
                        renderTable();
                    });
                }

                if (clearSearchBtn) {
                    clearSearchBtn.addEventListener('click', () => {
                        liveSearchInput.value = '';
                        clearSearchBtn.classList.remove('visible');
                        currentPage = 1;
                        renderTable();
                    });
                }

                if (filterGroupSelect) {
                    filterGroupSelect.addEventListener('change', () => {
                        currentPage = 1;
                        renderTable();
                    });
                }

                if (perPageSelect) {
                    perPageSelect.addEventListener('change', () => {
                        currentPage = 1;
                        renderTable();
                    });
                }

                // Inisialisasi awal render tabel
                renderTable();

                // 3. Tombol Sinkronisasi Modul Baru Otomatis
                const btnSync = document.getElementById('btnSyncModules');
                if (btnSync) {
                    btnSync.addEventListener('click', async () => {
                        const originalHtml = btnSync.innerHTML;
                        btnSync.disabled = true;
                        btnSync.innerHTML =
                            '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...';

                        try {
                            const res = await fetch('{{ route('dashboard.hak-akses.sync') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            });
                            const data = await res.json();
                            if (data.status === 'success') {
                                showToast(data.message, 'success');
                                if (data.count > 0) {
                                    setTimeout(() => window.location.reload(), 800);
                                }
                            } else {
                                throw new Error(data.message || 'Gagal menyinkronkan modul');
                            }
                        } catch (err) {
                            showToast(err.message || 'Terjadi kesalahan sinkronisasi', 'danger');
                        } finally {
                            btnSync.disabled = false;
                            btnSync.innerHTML = originalHtml;
                        }
                    });
                }

                // 4. Tombol Reset Bawaan
                const btnReset = document.getElementById('btnResetDefault');
                if (btnReset) {
                    btnReset.addEventListener('click', async () => {
                        let confirmed = false;
                        if (window.SAE && typeof window.SAE.confirm === 'function') {
                            confirmed = await window.SAE.confirm(
                                'Seluruh izin peran {{ ucfirst($activeRole) }} akan dikembalikan ke pengaturan default sistem.',
                                'Reset ke Bawaan?',
                                'warning'
                            );
                        } else {
                            confirmed = confirm(
                                'Reset izin peran {{ $activeRole }} ke pengaturan default?');
                        }

                        if (!confirmed) return;

                        try {
                            const res = await fetch('{{ route('dashboard.hak-akses.reset') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    role: '{{ $activeRole }}'
                                })
                            });
                            const data = await res.json();
                            if (data.status === 'success') {
                                showToast(data.message, 'success');
                                setTimeout(() => window.location.reload(), 600);
                            } else {
                                throw new Error(data.message || 'Gagal mereset izin');
                            }
                        } catch (err) {
                            showToast(err.message || 'Gagal mereset izin', 'danger');
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
