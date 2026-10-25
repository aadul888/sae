@extends('layouts.dashboard')

@section('title', 'Manajemen Data — Tendik Aktif — SAE')
@section('dash_title', 'Tendik Aktif')

@section('content')
    @php
        $isAdmin = $isAdmin ?? (session('user')['role'] ?? (session('user')->role ?? '')) === 'admin';
    @endphp
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-badge text-primary me-2"></i> Manajemen Data — Tendik Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Direktori Tenaga Kependidikan (TU, Laboran, Perpustakaan, Staf) bersumber dari tabel <strong>GTK</strong>
                (Dapodik).
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: var(--text-base, 0.875rem);"
                title="Tarik Data Dapodik">
                <i class="fas fa-cloud-arrow-down"></i>
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Tendik Aktif</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['pns'] }}
                </div>
                <div class="dash-stat-label">PNS / ASN</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['non_pns'] }}
                </div>
                <div class="dash-stat-label">Non-PNS / Honorer</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);">
                <i class="fas fa-venus-mars"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['laki'] }} / {{ $summary['perempuan'] }}
                </div>
                <div class="dash-stat-label">Laki / Perempuan</div>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Tendik (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <!-- Per Page -->
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <!-- Filter Status Kepegawaian -->
                    <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status Kepegawaian</option>
                        @foreach ($filterStatus as $st)
                            <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>
                                {{ $st }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Gender -->
                    <select id="filterGender" name="gender" class="toolbar-filter-select"
                        style="height: 38px; min-width: 120px;">
                        <option value="">Semua Gender</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>

                    @if ($status || $gender || $q)
                        <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NUPTK / NIP..."
                        value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Konten Datatable Responsive Stack -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                            data-sort="nama" class="sortable-th">
                            Nama Tendik
                            @if ($sort === 'nama')
                                <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span style="opacity: 0.3;">▲▼</span>
                            @endif
                        </th>
                        <th style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                            data-sort="nuptk" class="sortable-th">
                            NUPTK / NIP
                            @if ($sort === 'nuptk')
                                <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span style="opacity: 0.3;">▲▼</span>
                            @endif
                        </th>
                        <th style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer;"
                            data-sort="jenis_kelamin" class="sortable-th">
                            L/P
                            @if ($sort === 'jenis_kelamin')
                                <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span style="opacity: 0.3;">▲▼</span>
                            @endif
                        </th>
                        <th style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                            data-sort="jabatan_ptk_id_str" class="sortable-th">
                            Tugas / Jabatan
                            @if ($sort === 'jabatan_ptk_id_str')
                                <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span style="opacity: 0.3;">▲▼</span>
                            @endif
                        </th>
                        <th style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left; cursor: pointer;"
                            data-sort="status_kepegawaian_id_str" class="sortable-th">
                            Status
                            @if ($sort === 'status_kepegawaian_id_str')
                                <span style="color: var(--primary);">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span style="opacity: 0.3;">▲▼</span>
                            @endif
                        </th>
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: left;">
                            Pendidikan
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $tendik)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                            <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);" data-label="Nama">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-sm, 0.8125rem); flex-shrink: 0;">
                                        <i class="fas fa-user-gear"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color);">{{ $tendik->nama }}</div>
                                        @if (!empty($tendik->nik))
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); font-family: monospace; color: var(--text-muted); margin-top: 1px;">
                                                NIK: <span class="copyable" data-copy="{{ $tendik->nik }}"
                                                    data-label="NIK"
                                                    title="Klik untuk salin NIK">{{ $tendik->nik }}</span>
                                            </div>
                                        @endif
                                        @if ($tendik->email)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 1px;">
                                                {{ $tendik->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px 14px; font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--primary);"
                                data-label="NUPTK/NIP">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($tendik->nuptk)
                                            <span class="copyable" data-copy="{{ $tendik->nuptk }}" data-label="NUPTK"
                                                title="Klik untuk salin NUPTK">{{ $tendik->nuptk }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if ($tendik->nip)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            <span class="copyable" data-copy="{{ $tendik->nip }}" data-label="NIP"
                                                title="Klik untuk salin NIP">NIP: {{ $tendik->nip }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); text-align: center;" data-label="L/P">
                                <span
                                    class="badge {{ $tendik->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $tendik->jenis_kelamin }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem);" data-label="Tugas">
                                <span class="badge badge-info"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 600; white-space: normal; line-height: 1.25; display: inline-block;">
                                    {{ $tendik->jabatan_ptk ?: ($tendik->jenis_ptk ?: 'Tenaga Kependidikan') }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem);" data-label="Status">
                                <span
                                    class="badge {{ str_contains(strtoupper($tendik->status_kepegawaian ?? ''), 'PNS') ? 'badge-primary' : 'badge-outline' }}"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $tendik->status_kepegawaian ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                data-label="Pendidikan">
                                <div class="cell-col-right">
                                    <div style="color: var(--text-color); font-weight: 500;">
                                        {{ $tendik->pendidikan_terakhir ?: '-' }}</div>
                                    @if ($tendik->bidang_studi_terakhir)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ $tendik->bidang_studi_terakhir }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 16px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    @if ($isAdmin)
                                        <button type="button" class="btn-icon btn-detail-tendik"
                                            data-id="{{ $tendik->ptk_id }}" title="Lihat Profil Lengkap">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-user-slash"
                                    style="font-size: var(--text-4xl, 2.25rem); opacity: 0.4; margin-bottom: 12px;"></i>
                                <p style="font-size: var(--text-base, 0.875rem); margin: 0;">Tidak ada data Tenaga Kependidikan yang sesuai
                                    dengan
                                    kriteria pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 3. Footer Paginasi Terpadu Datatable -->
        @if ($list->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $list])
            </div>
        @endif
    </div>

    <!-- Detail Tendik Modal -->
    <div id="modalDetailTendik" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 640px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="tendikModalTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                        Profil Tenaga Kependidikan
                    </h3>
                    <div id="tendikModalSubtitle" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">-
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeTendikModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalBodyTendik" style="font-size: var(--text-base, 0.875rem); color: var(--text-color); overflow-y: auto; flex: 1;">
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: var(--text-3xl, 1.875rem); color: var(--primary);"></i>
                    <p style="margin-top: 10px; color: var(--text-muted);">Memuat data...</p>
                </div>
            </div>
            <div
                style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); text-align: right;">
                <button type="button" class="btn btn-outline" style="padding: 7px 16px; font-size: var(--text-sm, 0.8125rem);"
                    onclick="closeTendikModal()">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if (file_exists(public_path('js/tendik-aktif.js')))
        <script src="{{ asset('js/tendik-aktif.js') }}?v={{ filemtime(public_path('js/tendik-aktif.js')) }}"></script>
    @endif
@endpush
