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
        data-remove-module-url="{{ route('dashboard.hak-akses.remove-module') }}"
        data-store-duty-url="{{ route('dashboard.hak-akses.tugas-tambahan.store') }}"
        data-destroy-duty-base-url="{{ url('/dashboard/hak-akses/tugas-tambahan') }}"
        data-sync-wali-url="{{ route('dashboard.hak-akses.tugas-tambahan.sync-wali') }}">

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
                <i class="fas fa-rotate-left me-1"></i> Reset
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
        <a href="{{ route('dashboard.hak-akses.index', ['role' => 'tugas_tambahan']) }}"
            class="btn {{ $activeRole === 'tugas_tambahan' ? 'btn-primary' : 'btn-outline' }}"
            style="{{ $activeRole === 'tugas_tambahan' ? '' : 'border-color: rgba(99, 102, 241, 0.4); color: var(--primary);' }}">
            <i class="fas fa-briefcase me-1"></i> Tugas Tambahan ({{ $counts['tugas_tambahan'] ?? 0 }})
        </a>
    </div>

    <!-- Role Switcher (Mobile Custom Dropdown) -->
    <div class="dash-mobile-tab-select-wrap">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label">
                    <i
                        class="fas {{ $activeRole === 'admin' ? 'fa-user-shield' : ($activeRole === 'guru' ? 'fa-chalkboard-user' : ($activeRole === 'tendik' ? 'fa-id-badge' : ($activeRole === 'tugas_tambahan' ? 'fa-briefcase' : 'fa-user-graduate'))) }} text-primary me-2"></i>
                    <span>{{ $activeRole === 'admin' ? 'Administrator' : ($activeRole === 'guru' ? 'Guru' : ($activeRole === 'tendik' ? 'Tenaga Kependidikan' : ($activeRole === 'tugas_tambahan' ? 'Tugas Tambahan' : 'Peserta Didik'))) }}</span>
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
                <a href="{{ route('dashboard.hak-akses.index', ['role' => 'tugas_tambahan']) }}"
                    class="custom-dropdown-item {{ $activeRole === 'tugas_tambahan' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-briefcase text-primary me-2"></i>
                        <span>Tugas Tambahan</span>
                    </div>
                    <span
                        class="badge {{ $activeRole === 'tugas_tambahan' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ $counts['tugas_tambahan'] ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>

    @if ($activeRole === 'tugas_tambahan')
        <!-- Card: Katalog Master Bidang Tugas Tambahan & Hak Akses Otomatis -->
        <div class="card" style="padding: 20px 24px; margin-bottom: 24px; border-radius: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-sitemap text-primary"></i> Katalog Master Bidang Tugas Tambahan &amp; Hak Akses Otomatis
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 4px 0 0 0;">
                        Daftar 22 tugas tambahan Dapodik beserta bidang penugasan dan hak akses modul sistem yang otomatis terbuka saat personel ditugaskan.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="filterDutyCatalog" style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Kelompok:</label>
                    <select id="filterDutyCatalog" class="per-page-select" style="min-width: 140px; font-size: 0.8rem;">
                        <option value="">Semua ({{ count($refTugasList) }})</option>
                        <option value="tendik">Tendik ({{ collect($refTugasList)->where('kelompok', 'tendik')->count() }} Bidang)</option>
                        <option value="guru">Guru ({{ collect($refTugasList)->where('kelompok', 'guru')->count() }} Tugas)</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive-stack" style="max-height: 380px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead style="position: sticky; top: 0; background: var(--card-bg, #1e293b); z-index: 2;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tugas Tambahan &amp; Kode</th>
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Bidang</th>
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px; text-align: center;">Kelompok</th>
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 85px; text-align: center;">Beban Jam</th>
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Modul Izin Terbuka Otomatis</th>
                            <th style="padding: 10px 14px; font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">Personel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($refTugasList as $cIdx => $rt)
                            <tr class="duty-catalog-row" data-group="{{ $rt->kelompok }}" style="border-bottom: 1px solid var(--border-color); font-size: 0.82rem;">
                                <td style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-weight: 600;">{{ $cIdx + 1 }}</td>
                                <td style="padding: 10px 14px;">
                                    <div style="font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                        <i class="fas {{ $rt->icon }} text-primary"></i>
                                        <span>{{ $rt->nama }}</span>
                                    </div>
                                    <span style="font-size: 0.7rem; font-family: monospace; color: var(--text-muted);">{{ $rt->kode }}</span>
                                </td>
                                <td style="padding: 10px 14px;">
                                    <span class="badge badge-accent" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 600;">
                                        {{ $rt->bidang }}
                                    </span>
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <span class="badge {{ $rt->kelompok === 'guru' ? 'badge-primary' : 'badge-info' }}" style="font-size: 0.65rem; padding: 2px 6px; text-transform: uppercase;">
                                        {{ $rt->kelompok }}
                                    </span>
                                </td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 600;">
                                    {{ $rt->ekuivalensi_jam ? $rt->ekuivalensi_jam . ' Jam' : '-' }}
                                </td>
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        @forelse ($rt->granted_perms as $gKey)
                                            @php
                                                $modConfig = \App\Models\RolePermission::getPermissionConfig($gKey);
                                                $modLabel = $modConfig['label'] ?? str_replace('menu_', '', $gKey);
                                            @endphp
                                            <span class="badge badge-outline" style="font-size: 0.68rem; padding: 2px 6px; background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.25); color: var(--text-color);" title="{{ $gKey }}">
                                                <i class="fas {{ $modConfig['icon'] ?? 'fa-cube' }} me-1 text-primary" style="font-size: 0.65rem;"></i>{{ $modLabel }}
                                            </span>
                                        @empty
                                            <span style="color: var(--text-muted); font-size: 0.72rem; font-style: italic;">Mengikuti izin peran dasar</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <span class="badge {{ $rt->assigned_count > 0 ? 'badge-success' : 'badge-outline' }}" style="font-size: 0.72rem; padding: 2px 8px;">
                                        {{ $rt->assigned_count }} Orang
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Penugasan Personel PTK -->
        <div style="margin-bottom: 12px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-users-gear text-primary"></i> Penugasan Personel PTK Aktif
            </h3>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 4px 0 0 0;">
                Penetapan guru &amp; tendik ke dalam tugas tambahan dan kelas perwalian aktif.
            </p>
        </div>

        <!-- Tampilan Datatable Khusus Tab Tugas Tambahan -->
        <div class="toolbar-row">
            <div class="toolbar-entries" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <label for="perPageDuty" style="margin: 0;">Tampilkan</label>
                <select id="perPageDuty" class="per-page-select">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="all">Semua</option>
                </select>
                <span>entri</span>

                <button type="button" id="btnSyncWaliKelas" class="btn btn-primary"
                    style="padding: 7px 14px; font-size: 0.8rem; margin-left: 8px;">
                    <i class="fas fa-arrows-rotate me-1"></i> Sinkronkan Wali Kelas (Dapodik)
                </button>
                <button type="button" id="btnOpenAssignModal" class="btn btn-outline"
                    style="padding: 7px 14px; font-size: 0.8rem;">
                    <i class="fas fa-plus me-1"></i> Tambah Penugasan
                </button>
                <span class="badge badge-outline" id="totalDutyBadge" style="margin-left: 6px; font-size: 0.73rem;">
                    Total: {{ count($tugasTambahanList) }} Penugasan
                </span>
            </div>
            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearchDuty" placeholder="Cari nama guru / tendik / tugas..."
                    autocomplete="off">
                <button type="button" id="clearSearchDuty" class="clear-search" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table" id="dutyTable" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead class="sticky-table-header">
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th class="duty-th sortable-th" data-col="no"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center; cursor: pointer;">
                            No <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="duty-th sortable-th" data-col="nama"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Nama Personel <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="duty-th sortable-th" data-col="tugas"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Tugas Tambahan <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="duty-th sortable-th" data-col="bidang"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Bidang & Kelompok <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="duty-th sortable-th" data-col="jam"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer;">
                            Beban Jam <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 100px;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody id="dutyTableBody">
                    @forelse ($tugasTambahanList as $idx => $d)
                        <tr class="duty-row" data-nama="{{ strtolower($d->ptk_nama) }}"
                            data-tugas="{{ strtolower($d->tugas_nama) }}"
                            data-bidang="{{ strtolower($d->tugas_bidang . ' ' . $d->tugas_kelompok) }}"
                            data-jam="{{ (float) $d->ekuivalensi_jam }}"
                            data-search="{{ strtolower($d->ptk_nama . ' ' . $d->tugas_nama . ' ' . $d->tugas_bidang . ' ' . ($d->rombel_nama ?? '')) }}"
                            style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="duty-row-number"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-muted); text-align: center; font-size: 0.84rem;">
                                {{ $idx + 1 }}
                            </td>
                            <td
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.88rem;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: 50%; background: rgba(99, 102, 241, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.82rem;">
                                        <i
                                            class="fas {{ $d->tugas_kelompok === 'guru' ? 'fa-chalkboard-user' : 'fa-id-badge' }}"></i>
                                    </div>
                                    <div>
                                        <div>{{ $d->ptk_nama }}</div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">NIP:
                                            {{ $d->ptk_nip }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.85rem;">
                                <div
                                    style="font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                    <i class="fas {{ $d->tugas_icon }} text-primary"></i>
                                    <span>{{ $d->tugas_nama }}</span>
                                </div>
                                @if ($d->rombel_nama)
                                    <span class="badge badge-accent"
                                        style="font-size: 0.68rem; margin-top: 4px; padding: 2px 6px;">
                                        Kelas: {{ $d->rombel_nama }}
                                    </span>
                                @endif
                                @if ($d->keterangan)
                                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                        {{ $d->keterangan }}</div>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem;">
                                <div style="font-weight: 600; color: var(--text-color);">{{ $d->tugas_bidang ?: '-' }}
                                </div>
                                <span class="badge {{ $d->tugas_kelompok === 'guru' ? 'badge-primary' : 'badge-info' }}"
                                    style="font-size: 0.65rem; padding: 2px 6px; text-transform: uppercase;">
                                    {{ $d->tugas_kelompok }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: center; font-size: 0.86rem; font-weight: 700;">
                                @if ($d->ekuivalensi_jam > 0)
                                    <span style="color: #10b981;">{{ $d->ekuivalensi_jam }} Jam</span>
                                @else
                                    <span style="color: var(--text-muted); font-weight: normal;">Reguler</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; text-align: right;">
                                <button type="button" class="btn-icon danger btn-delete-duty"
                                    data-id="{{ $d->id }}"
                                    data-name="{{ $d->ptk_nama }} ({{ $d->tugas_nama }})" title="Hapus Penugasan">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data penugasan tugas tambahan. Klik "Sinkronkan Wali Kelas" atau "Tambah
                                    Penugasan".</div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noDutyResultRow" style="display: none;">
                        <td colspan="6"
                            style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-magnifying-glass mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                            <div>Tidak ada data penugasan yang sesuai pencarian.</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer Khusus Tugas Tambahan -->
        <div id="dutyPaginationWrap" class="custom-pagination"></div>

        <!-- Modal Tambah Penugasan -->
        <div id="dutyModal" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 520px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--card-bg, #1e293b);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <h3
                        style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-briefcase text-primary"></i> Tambah Penugasan Tugas Tambahan
                    </h3>
                    <button type="button" id="btnCloseDutyModal"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="formAddDuty">
                    <div class="form-group-compact" style="margin-bottom: 14px;">
                        <label
                            style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block;">
                            Pilih Personel (PTK) <span style="color: #ef4444;">*</span>
                        </label>
                        <select name="ptk_id" id="dutyPtkSelect" required
                            style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="">-- Pilih Guru / Tendik --</option>
                            @foreach ($ptkList as $ptk)
                                <option value="{{ $ptk->ptk_id }}">{{ $ptk->nama }} ({{ $ptk->jenis_ptk_id_str }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact" style="margin-bottom: 14px;">
                        <label
                            style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block;">
                            Tugas Tambahan <span style="color: #ef4444;">*</span>
                        </label>
                        <select name="tugas_tambahan_id" id="dutyTugasSelect" required
                            style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="">-- Pilih Tugas Tambahan --</option>
                            @foreach ($refTugasList as $rt)
                                <option value="{{ $rt->id }}" data-kode="{{ $rt->kode }}">{{ $rt->nama }}
                                    ({{ ucfirst($rt->kelompok) }} &bull;
                                    {{ $rt->ekuivalensi_jam ? $rt->ekuivalensi_jam . ' Jam' : 'Reguler' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact" id="dutyRombelWrap" style="margin-bottom: 14px; display: none;">
                        <label
                            style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block;">
                            Khusus Wali Kelas: Pilih Rombel <span style="color: #ef4444;">*</span>
                        </label>
                        <select name="rombel_id" id="dutyRombelSelect"
                            style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <option value="">-- Pilih Kelas Reguler --</option>
                            @foreach ($rombelList as $rb)
                                <option value="{{ $rb->rombongan_belajar_id }}">{{ $rb->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group-compact" style="margin-bottom: 20px;">
                        <label
                            style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: block;">
                            Keterangan / Nomor SK (Opsional)
                        </label>
                        <input type="text" name="keterangan" placeholder="Contoh: SK Kepala Sekolah No 001/..."
                            style="width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" id="btnCancelDutyModal" class="btn btn-outline"
                            style="padding: 9px 18px; font-size: 0.85rem; border-radius: 8px;">Batal</button>
                        <button type="submit" id="btnSubmitDuty" class="btn btn-primary"
                            style="padding: 9px 20px; font-size: 0.85rem; border-radius: 8px; font-weight: 600;">Simpan
                            Penugasan</button>
                    </div>
                </form>
            </div>
        </div>
    @else
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

                <button type="button" id="btnOpenAddModule" class="btn btn-primary"
                    style="padding: 7px 14px; font-size: 0.8rem; margin-left: 8px;">
                    <i class="fas fa-plus me-1"></i> Tambah Modul ke Peran
                </button>

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
                        <th class="module-th sortable-th" data-col="no"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center; cursor: pointer;">
                            No <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="module-th sortable-th" data-col="modul"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Modul <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
                        <th class="module-th sortable-th" data-col="kelompok"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px; cursor: pointer;">
                            Kelompok <span class="sort-icon">&#9650;&#9660;</span>
                        </th>
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
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 65px;">
                            Kelola
                        </th>
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
                                        style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                        onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'"
                                        onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'">
                                        <i class="fas fa-trash-can" style="font-size: 0.82rem;"></i>
                                    </button>
                                @else
                                    <span style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-muted); opacity: 0.4;"
                                        title="Modul ini dikunci sistem">
                                        <i class="fas fa-lock" style="font-size: 0.8rem;"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Tidak ada modul yang tersedia untuk peran ini.</div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noSearchResultRow" style="display: none;">
                        <td colspan="5"
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

        <!-- Modal Tambah Modul ke Peran -->
        <div id="modalAddModule" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="max-width: 520px; width: 92%; margin: 0; border-radius: 14px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--card-bg, #1e293b);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <h3
                        style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus-circle text-primary"></i> Tambah Modul ke Peran {{ $roles[$activeRole]['name'] ?? ucfirst($activeRole) }}
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
                        <select name="permission_key" id="selectAddModule" required
                            style="width: 100%; height: 42px; padding: 0 12px; border: 1px solid rgba(255,255,255,0.15); background-color: #1e293b !important; color: #f8fafc !important; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box;">
                            <option value="" style="background-color: #1e293b; color: #94a3b8;">-- Pilih Modul untuk Ditambahkan --</option>
                            @foreach ($availableModulesToAdd as $mKey => $mVal)
                                <option value="{{ $mVal['key'] }}" style="background-color: #1e293b; color: #f8fafc; padding: 8px 12px;">{{ $mVal['label'] }}</option>
                            @endforeach
                            <option value="__NEW_CUSTOM_MODULE__" style="background-color: #0f172a; color: #38bdf8; font-weight: 600; padding: 8px 12px;">+ Daftarkan Modul Baru / Mendatang...</option>
                        </select>

                        <!-- Input Dinamis jika Mendaftarkan Modul Baru / Mendatang -->
                        <div id="customModuleFields" style="display: none; flex-direction: column; gap: 8px; margin-top: 14px; padding: 14px; background: rgba(56, 189, 248, 0.06); border: 1px dashed rgba(56, 189, 248, 0.35); border-radius: 10px;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: #38bdf8; display: block;">
                                <i class="fas fa-sparkles me-1"></i> Nama Modul Baru / Mendatang <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="inputCustomModuleName" name="custom_name" placeholder="Contoh: Perpustakaan Digital, Bimbingan Konseling, Keuangan"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid rgba(255,255,255,0.18); background-color: #1e293b; color: #f8fafc; border-radius: 8px; font-size: 0.85rem; box-sizing: border-box;">
                            <p style="font-size: 0.72rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Sistem akan otomatis membuat izin RBAC dan merefleksikannya di matriks hak akses serta sidebar.
                            </p>
                        </div>

                        <p style="font-size: 0.76rem; color: var(--text-muted); margin-top: 8px; margin-bottom: 0;">
                            Modul yang ditambahkan akan otomatis diaktifkan untuk peran ini dan dapat diatur hak akses CRUD serta visibilitasnya di sidebar.
                        </p>
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" id="btnCancelAddModule" class="btn btn-outline"
                            style="padding: 9px 18px; font-size: 0.85rem; border-radius: 8px;">Batal</button>
                        <button type="submit" id="btnSubmitAddModule" class="btn btn-primary"
                            style="padding: 9px 20px; font-size: 0.85rem; border-radius: 8px; font-weight: 600;">
                            <i class="fas fa-plus me-1"></i> Tambahkan
                        </button>
                    </div>
                </form>
            </div>
    @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hak-akses.js') }}"></script>
@endpush

