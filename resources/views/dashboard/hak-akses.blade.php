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
    @endif

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

                // 2. Client-side Search, Group Filter, Sorting & Pagination untuk Datatable Modul
                const liveSearchInput = document.getElementById('liveSearch');
                const clearSearchBtn = document.getElementById('clearSearch');
                const filterGroupSelect = document.getElementById('filterGroup');
                const perPageSelect = document.getElementById('perPageSelect');
                let allRows = Array.from(document.querySelectorAll('.module-row'));
                const noResultRow = document.getElementById('noSearchResultRow');
                const paginationWrap = document.getElementById('tablePaginationWrap');
                const totalBadge = document.getElementById('totalBadge');

                let currentPage = 1;
                let sortCol = 'no';
                let sortDir = 'asc';

                const sortRows = (rows) => {
                    return rows.sort((a, b) => {
                        let valA = '';
                        let valB = '';
                        if (sortCol === 'no') {
                            valA = parseInt(a.querySelector('.row-number')?.textContent || '0', 10);
                            valB = parseInt(b.querySelector('.row-number')?.textContent || '0', 10);
                            return sortDir === 'asc' ? valA - valB : valB - valA;
                        } else if (sortCol === 'modul') {
                            valA = a.dataset.modul || '';
                            valB = b.dataset.modul || '';
                        } else if (sortCol === 'kelompok') {
                            valA = a.dataset.kelompok || '';
                            valB = b.dataset.kelompok || '';
                        }
                        const cmp = valA.localeCompare(valB);
                        return sortDir === 'asc' ? cmp : -cmp;
                    });
                };

                const renderTable = () => {
                    const query = (liveSearchInput?.value || '').trim().toLowerCase();
                    const groupFilter = (filterGroupSelect?.value || '').trim();
                    const perPageVal = perPageSelect?.value || '25';
                    const perPage = perPageVal === 'all' ? Infinity : parseInt(perPageVal, 10);

                    // Filter baris
                    let matchedRows = allRows.filter(row => {
                        const name = row.dataset.name || '';
                        const group = row.dataset.group || '';
                        const matchQuery = !query || name.includes(query);
                        const matchGroup = !groupFilter || group === groupFilter;
                        return matchQuery && matchGroup;
                    });

                    matchedRows = sortRows(matchedRows);

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
                    const tbody = document.getElementById('tableBody');
                    matchedRows.forEach((row, i) => {
                        if (i >= startIdx && i < endIdx) {
                            row.style.display = '';
                            const noCell = row.querySelector('.row-number');
                            if (noCell) noCell.textContent = i + 1;
                            if (tbody) tbody.appendChild(row); // Re-order in DOM
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

                // Event listener klik header kolom untuk sorting modul
                document.querySelectorAll('.module-th[data-col]').forEach(th => {
                    th.addEventListener('click', () => {
                        const col = th.dataset.col;
                        if (sortCol === col) {
                            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                        } else {
                            sortCol = col;
                            sortDir = 'asc';
                        }

                        document.querySelectorAll('.module-th').forEach(t => {
                            t.classList.remove('sorted');
                            const icon = t.querySelector('.sort-icon');
                            if (icon) icon.innerHTML = '&#9650;&#9660;';
                        });

                        th.classList.add('sorted');
                        const curIcon = th.querySelector('.sort-icon');
                        if (curIcon) {
                            curIcon.innerHTML = sortDir === 'asc' ? '&#9650;' : '&#9660;';
                        }

                        renderTable();
                    });
                });

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

                // 2b. Handler Tab Tugas Tambahan (Search, Sorting, Pagination, Modal, Sinkronisasi Wali Kelas, Hapus)
                const liveSearchDuty = document.getElementById('liveSearchDuty');
                const clearSearchDuty = document.getElementById('clearSearchDuty');
                const perPageDuty = document.getElementById('perPageDuty');
                const dutyTableBody = document.getElementById('dutyTableBody');
                const dutyPaginationWrap = document.getElementById('dutyPaginationWrap');
                const totalDutyBadge = document.getElementById('totalDutyBadge');
                const noDutyResultRow = document.getElementById('noDutyResultRow');
                let allDutyRows = Array.from(document.querySelectorAll('.duty-row'));

                let currentDutyPage = 1;
                let sortDutyCol = 'no';
                let sortDutyDir = 'asc';

                const sortDutyRows = (rows) => {
                    return rows.sort((a, b) => {
                        let valA = '';
                        let valB = '';
                        if (sortDutyCol === 'no') {
                            valA = parseInt(a.querySelector('.duty-row-number')?.textContent || '0', 10);
                            valB = parseInt(b.querySelector('.duty-row-number')?.textContent || '0', 10);
                            return sortDutyDir === 'asc' ? valA - valB : valB - valA;
                        } else if (sortDutyCol === 'nama') {
                            valA = a.dataset.nama || '';
                            valB = b.dataset.nama || '';
                        } else if (sortDutyCol === 'tugas') {
                            valA = a.dataset.tugas || '';
                            valB = b.dataset.tugas || '';
                        } else if (sortDutyCol === 'bidang') {
                            valA = a.dataset.bidang || '';
                            valB = b.dataset.bidang || '';
                        } else if (sortDutyCol === 'jam') {
                            valA = parseFloat(a.dataset.jam || '0');
                            valB = parseFloat(b.dataset.jam || '0');
                            return sortDutyDir === 'asc' ? valA - valB : valB - valA;
                        }
                        const cmp = valA.localeCompare(valB);
                        return sortDutyDir === 'asc' ? cmp : -cmp;
                    });
                };

                const renderDutyTable = () => {
                    if (!dutyTableBody) return;

                    const q = (liveSearchDuty?.value || '').trim().toLowerCase();
                    const perPageVal = perPageDuty?.value || '25';
                    const perPage = perPageVal === 'all' ? Infinity : parseInt(perPageVal, 10);

                    let matched = allDutyRows.filter(r => {
                        const search = r.dataset.search || '';
                        return !q || search.includes(q);
                    });

                    matched = sortDutyRows(matched);

                    const totalMatched = matched.length;
                    if (totalDutyBadge) {
                        totalDutyBadge.textContent = `Total: ${totalMatched} Penugasan`;
                    }

                    if (totalMatched === 0) {
                        allDutyRows.forEach(r => r.style.display = 'none');
                        if (noDutyResultRow) noDutyResultRow.style.display = '';
                        if (dutyPaginationWrap) dutyPaginationWrap.innerHTML = '';
                        return;
                    }

                    if (noDutyResultRow) noDutyResultRow.style.display = 'none';

                    const totalPages = Math.ceil(totalMatched / perPage);
                    if (currentDutyPage > totalPages) currentDutyPage = Math.max(1, totalPages);

                    const startIdx = (currentDutyPage - 1) * perPage;
                    const endIdx = startIdx + perPage;

                    allDutyRows.forEach(r => r.style.display = 'none');

                    matched.forEach((row, i) => {
                        if (i >= startIdx && i < endIdx) {
                            row.style.display = '';
                            const noCell = row.querySelector('.duty-row-number');
                            if (noCell) noCell.textContent = i + 1;
                            dutyTableBody.appendChild(row);
                        }
                    });

                    if (totalPages <= 1) {
                        if (dutyPaginationWrap) dutyPaginationWrap.innerHTML = '';
                        return;
                    }

                    let pagHtml = '';
                    pagHtml +=
                        `<button type="button" class="page-btn ${currentDutyPage === 1 ? 'disabled' : ''}" data-page="${currentDutyPage - 1}" ${currentDutyPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

                    for (let p = 1; p <= totalPages; p++) {
                        if (p === 1 || p === totalPages || (p >= currentDutyPage - 2 && p <= currentDutyPage + 2)) {
                            pagHtml +=
                                `<button type="button" class="page-btn ${p === currentDutyPage ? 'current' : ''}" data-page="${p}">${p}</button>`;
                        } else if (p === currentDutyPage - 3 || p === currentDutyPage + 3) {
                            pagHtml += `<span class="page-info">&hellip;</span>`;
                        }
                    }

                    pagHtml +=
                        `<button type="button" class="page-btn ${currentDutyPage === totalPages ? 'disabled' : ''}" data-page="${currentDutyPage + 1}" ${currentDutyPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

                    if (dutyPaginationWrap) {
                        dutyPaginationWrap.innerHTML = pagHtml;
                        dutyPaginationWrap.querySelectorAll('.page-btn[data-page]').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const targetP = parseInt(btn.dataset.page, 10);
                                if (targetP >= 1 && targetP <= totalPages && targetP !==
                                    currentDutyPage) {
                                    currentDutyPage = targetP;
                                    renderDutyTable();
                                }
                            });
                        });
                    }
                };

                // Sorting klik header di tab Tugas Tambahan
                document.querySelectorAll('.duty-th[data-col]').forEach(th => {
                    th.addEventListener('click', () => {
                        const col = th.dataset.col;
                        if (sortDutyCol === col) {
                            sortDutyDir = sortDutyDir === 'asc' ? 'desc' : 'asc';
                        } else {
                            sortDutyCol = col;
                            sortDutyDir = 'asc';
                        }

                        document.querySelectorAll('.duty-th').forEach(t => {
                            t.classList.remove('sorted');
                            const icon = t.querySelector('.sort-icon');
                            if (icon) icon.innerHTML = '&#9650;&#9660;';
                        });

                        th.classList.add('sorted');
                        const curIcon = th.querySelector('.sort-icon');
                        if (curIcon) {
                            curIcon.innerHTML = sortDutyDir === 'asc' ? '&#9650;' : '&#9660;';
                        }

                        renderDutyTable();
                    });
                });

                if (liveSearchDuty) {
                    liveSearchDuty.addEventListener('input', () => {
                        const q = liveSearchDuty.value.trim().toLowerCase();
                        if (clearSearchDuty) clearSearchDuty.classList.toggle('visible', !!q);
                        currentDutyPage = 1;
                        renderDutyTable();
                    });
                }

                if (clearSearchDuty) {
                    clearSearchDuty.addEventListener('click', () => {
                        liveSearchDuty.value = '';
                        clearSearchDuty.classList.remove('visible');
                        currentDutyPage = 1;
                        renderDutyTable();
                    });
                }

                if (perPageDuty) {
                    perPageDuty.addEventListener('change', () => {
                        currentDutyPage = 1;
                        renderDutyTable();
                    });
                }

                renderDutyTable();

                // Modal Tambah Penugasan
                const dutyModal = document.getElementById('dutyModal');
                const btnOpenAssignModal = document.getElementById('btnOpenAssignModal');
                const btnCloseDutyModal = document.getElementById('btnCloseDutyModal');
                const btnCancelDutyModal = document.getElementById('btnCancelDutyModal');
                const dutyTugasSelect = document.getElementById('dutyTugasSelect');
                const dutyRombelWrap = document.getElementById('dutyRombelWrap');
                const formAddDuty = document.getElementById('formAddDuty');

                if (btnOpenAssignModal && dutyModal) {
                    btnOpenAssignModal.addEventListener('click', () => {
                        dutyModal.style.display = 'flex';
                    });
                }

                const closeDutyModal = () => {
                    if (dutyModal) {
                        dutyModal.style.display = 'none';
                        if (formAddDuty) formAddDuty.reset();
                        if (dutyRombelWrap) dutyRombelWrap.style.display = 'none';
                    }
                };

                if (btnCloseDutyModal) btnCloseDutyModal.addEventListener('click', closeDutyModal);
                if (btnCancelDutyModal) btnCancelDutyModal.addEventListener('click', closeDutyModal);

                if (dutyTugasSelect && dutyRombelWrap) {
                    dutyTugasSelect.addEventListener('change', () => {
                        const selectedOpt = dutyTugasSelect.options[dutyTugasSelect.selectedIndex];
                        const kode = selectedOpt ? selectedOpt.dataset.kode : '';
                        dutyRombelWrap.style.display = (kode === 'WALI_KELAS') ? 'block' : 'none';
                    });
                }

                if (formAddDuty) {
                    formAddDuty.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const formData = new FormData(formAddDuty);
                        const payload = Object.fromEntries(formData.entries());

                        try {
                            const res = await fetch(
                                '{{ route('dashboard.hak-akses.tugas-tambahan.store') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify(payload)
                                });
                            const data = await res.json();
                            if (data.status === 'success') {
                                showToast(data.message, 'success');
                                closeDutyModal();
                                setTimeout(() => window.location.reload(), 600);
                            } else {
                                throw new Error(data.message || 'Gagal menyimpan');
                            }
                        } catch (err) {
                            showToast(err.message, 'danger');
                        }
                    });
                }

                // Hapus Penugasan
                document.querySelectorAll('.btn-delete-duty').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const id = btn.dataset.id;
                        const name = btn.dataset.name;

                        let confirmed = false;
                        if (window.SAE && typeof window.SAE.confirm === 'function') {
                            confirmed = await window.SAE.confirm(
                                `Hapus penugasan tugas tambahan untuk ${name}?`,
                                'Hapus Penugasan',
                                'warning'
                            );
                        } else {
                            confirmed = confirm(`Hapus penugasan tugas tambahan untuk ${name}?`);
                        }

                        if (!confirmed) return;

                        try {
                            const res = await fetch(
                                `{{ url('/dashboard/hak-akses/tugas-tambahan') }}/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                });
                            const data = await res.json();
                            if (data.status === 'success') {
                                showToast(data.message, 'success');
                                setTimeout(() => window.location.reload(), 600);
                            } else {
                                throw new Error(data.message || 'Gagal menghapus');
                            }
                        } catch (err) {
                            showToast(err.message, 'danger');
                        }
                    });
                });

                // Sinkronkan Wali Kelas dari Dapodik
                const btnSyncWaliKelas = document.getElementById('btnSyncWaliKelas');
                if (btnSyncWaliKelas) {
                    btnSyncWaliKelas.addEventListener('click', async () => {
                        const originalHtml = btnSyncWaliKelas.innerHTML;
                        btnSyncWaliKelas.disabled = true;
                        btnSyncWaliKelas.innerHTML =
                            '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...';

                        try {
                            const res = await fetch(
                                '{{ route('dashboard.hak-akses.tugas-tambahan.sync-wali') }}', {
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
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                throw new Error(data.message || 'Gagal menyinkronkan');
                            }
                        } catch (err) {
                            showToast(err.message, 'danger');
                        } finally {
                            btnSyncWaliKelas.disabled = false;
                            btnSyncWaliKelas.innerHTML = originalHtml;
                        }
                    });
                }

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
