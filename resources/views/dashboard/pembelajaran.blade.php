@extends('layouts.dashboard')

@section('title', 'Master Data — Pembelajaran — SAE')
@section('dash_title', 'Pembelajaran & Mata Pelajaran')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-book-bookmark text-primary me-2"></i> Master Data — Pembelajaran
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Struktur kurikulum, alokasi jam mengajar, dan penugasan guru pengampu per rombel dari Dapodik.
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
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['total'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Pembelajaran</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Rombel Terjadwal</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['guru'] }}
                </div>
                <div class="dash-stat-label">Guru Pengampu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['jam'], 0, ',', '.') }} JP
                </div>
                <div class="dash-stat-label">Total Jam / Minggu</div>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Pembelajaran (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
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

                    <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Rombel</option>
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterGuru" name="guru" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Guru Pengampu</option>
                        @foreach ($filterGuru as $g)
                            <option value="{{ $g }}" {{ $guru === $g ? 'selected' : '' }}>{{ $g }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Status Kurikulum</option>
                        @foreach ($filterStatus as $st)
                            <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>
                                {{ $st }}
                            </option>
                        @endforeach
                    </select>

                    @if ($q || $rombel || $guru || $status)
                        <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari mapel / guru / rombel..."
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
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama_mata_pelajaran', 'Mata Pelajaran'],
                                ['nama_rombel', 'Rombel / Kelas'],
                                ['nama_guru', 'Guru Pengampu'],
                                ['jam_mengajar_per_minggu', 'Jam / Mg'],
                                ['status_di_kurikulum_str', 'Status Kurikulum'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jam_mengajar_per_minggu' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                data-label="Mapel">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-sm, 0.8125rem); flex-shrink: 0;">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color);">
                                            {{ $item->nama_mata_pelajaran }}</div>
                                        @if ($item->mata_pelajaran_id)
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                                <span class="copyable" data-copy="{{ $item->mata_pelajaran_id }}"
                                                    data-label="ID Mapel" title="Klik untuk salin ID Mapel">
                                                    ID: {{ $item->mata_pelajaran_id }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);" data-label="Kelas">
                                <div
                                    style="display: flex; flex-direction: column; align-items: flex-end; text-align: right;">
                                    <div
                                        style="font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                        <span>{{ $item->nama_rombel ?: '-' }}</span>
                                        @if (!empty($item->is_pilihan))
                                            <span class="badge"
                                                style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; font-weight: 700;"
                                                title="Matapelajaran Pilihan (Tergabung via Ruang)">Pilihan</span>
                                        @endif
                                    </div>
                                    <div style="display: flex; gap: 4px; margin-top: 2px;">
                                        @if ($item->tingkat)
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;">{{ $item->tingkat }}</span>
                                        @endif
                                        @if (!empty($item->ruang))
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px; color: var(--text-muted);"
                                                title="Ruang Kelas: {{ $item->ruang }}">
                                                <i class="fas fa-door-open me-1"></i>{{ $item->ruang }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);" data-label="Guru">
                                <div
                                    style="display: flex; flex-direction: column; align-items: flex-end; text-align: right;">
                                    @if ($item->nama_guru)
                                        <div
                                            style="display: flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                            <span
                                                class="badge {{ $item->guru_gender === 'P' ? 'badge-danger' : 'badge-primary' }}"
                                                style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">
                                                {{ $item->guru_gender ?: 'PTK' }}
                                            </span>
                                            <span
                                                style="font-weight: 600; color: var(--text-color);">{{ $item->nama_guru }}</span>
                                        </div>
                                        @if ($item->nuptk || $item->nip)
                                            <div
                                                style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                @if ($item->nuptk)
                                                    <span class="copyable" data-copy="{{ $item->nuptk }}"
                                                        data-label="NUPTK" title="Klik untuk salin NUPTK">
                                                        NUPTK: {{ $item->nuptk }}
                                                    </span>
                                                @else
                                                    <span class="copyable" data-copy="{{ $item->nip }}"
                                                        data-label="NIP" title="Klik untuk salin NIP">
                                                        NIP: {{ $item->nip }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">Belum Ditugaskan</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Jam">
                                <span class="badge"
                                    style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); font-size: var(--text-sm, 0.8125rem); padding: 3px 10px; font-weight: 700;">
                                    {{ $item->jam_mengajar_per_minggu ?: 0 }} JP
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem);" data-label="Kurikulum">
                                <span class="badge badge-outline"
                                    style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle;"
                                    title="{{ $item->status_di_kurikulum_str ?: 'Wajib' }}">
                                    {{ $item->status_di_kurikulum_str ?: 'Wajib' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    <button type="button" class="btn-icon" title="Lihat Detail Pembelajaran"
                                        onclick="openPembelajaranModal('{{ $item->pembelajaran_id }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                <div>Belum ada data pembelajaran yang cocok.</div>
                                <div style="font-size: var(--text-sm, 0.8125rem); margin-top: 6px;">
                                    Sinkronisasi data Dapodik untuk mengisi tabel pembelajaran.
                                </div>
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

    {{-- Modal Detail Pembelajaran --}}
    <div id="pembelajaranModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 600px; width: 92%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="pemModalTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">Detail
                        Pembelajaran</h3>
                    <div id="pemModalSubtitle" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">-
                    </div>
                </div>
                <button type="button" onclick="closePembelajaranModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="pemModalLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat detail pembelajaran...</div>
            </div>

            <div id="pemModalContent" style="overflow-y: auto; flex: 1; display: none; font-size: var(--text-base, 0.875rem);">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted); width: 150px;">Mata Pelajaran</td>
                        <td id="pemMapel" style="font-weight: 700;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">ID Mapel / Pembelajaran</td>
                        <td id="pemIdMapel" style="font-family: monospace; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            -</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Rombongan Belajar</td>
                        <td id="pemRombel" style="font-weight: 600; color: var(--primary);">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Tingkat / Jurusan</td>
                        <td id="pemTingkat">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Kurikulum</td>
                        <td id="pemKurikulum">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Guru Pengampu</td>
                        <td id="pemGuru" style="font-weight: 600;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NUPTK / NIP Guru</td>
                        <td id="pemGuruNip" style="font-family: monospace;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Kontak / Status Guru</td>
                        <td id="pemGuruKontak">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Alokasi Jam Mengajar</td>
                        <td id="pemJam">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status di Kurikulum</td>
                        <td id="pemStatusKur">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Wali Kelas</td>
                        <td id="pemWali">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: var(--text-muted);">Ruang Belajar</td>
                        <td id="pemRuang">-</td>
                    </tr>
                </table>
            </div>

            <div
                style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); text-align: right;">
                <button type="button" class="btn btn-outline" style="padding: 7px 16px; font-size: var(--text-sm, 0.8125rem);"
                    onclick="closePembelajaranModal()">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/pembelajaran.js') }}?v={{ file_exists(public_path('js/pembelajaran.js')) ? filemtime(public_path('js/pembelajaran.js')) : time() }}">
    </script>
@endpush
