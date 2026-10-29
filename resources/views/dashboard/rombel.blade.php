@extends('layouts.dashboard')

@section('title',
    ($currentType ?? 'reguler') === 'matpel'
    ? 'Master Data — Rombel Matpel Pilihan — SAE'
    : 'Master Data
    — Rombel Kelas Reguler — SAE')
@section('dash_title',
    ($currentType ?? 'reguler') === 'matpel'
    ? 'Rombel Mata Pelajaran Pilihan'
    : 'Rombel Kelas
    Reguler')

@section('content')
    <div class="dash-banner">
        <div>
            <h2>
                <i
                    class="fas {{ ($currentType ?? 'reguler') === 'matpel' ? 'fa-book-open' : 'fa-users-rectangle' }} text-primary me-2"></i>
                Master Data — Rombel {{ ($currentType ?? 'reguler') === 'matpel' ? '(Matpel Pilihan)' : '(Kelas Reguler)' }}
            </h2>
            <p>
                {{ ($currentType ?? 'reguler') === 'matpel'
                    ? 'Data rombongan belajar mata pelajaran pilihan disinkronkan otomatis dari tabel Rombongan Belajar (Dapodik).'
                    : 'Data rombongan belajar kelas reguler disinkronkan otomatis dari tabel Rombongan Belajar (Dapodik).' }}
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
    <div class="dash-stat-grid">
        <div class="dash-stat-card">
            <div class="dash-stat-icon icon-primary">
                <i class="fas {{ ($currentType ?? 'reguler') === 'matpel' ? 'fa-book-open' : 'fa-chalkboard-user' }}"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ $summary['rombel'] }}
                </div>
                <div class="dash-stat-label">Total Rombel
                    {{ ($currentType ?? 'reguler') === 'matpel' ? 'Matpel' : 'Kelas' }}</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon icon-success">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ number_format($summary['peserta_didik'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon icon-accent">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ $summary['jurusan'] }}
                </div>
                <div class="dash-stat-label">Jurusan Terdaftar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon icon-warning">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ $summary['wali'] }}
                </div>
                <div class="dash-stat-label">
                    {{ ($currentType ?? 'reguler') === 'matpel' ? 'Wali / Pembina' : 'Wali Kelas Aktif' }}</div>
            </div>
        </div>
    </div>

    <!-- Tab Navigasi Sub-Modul Rombel (Di Atas Datatable) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.rombel.reguler') }}"
                class="periode-nav-tab {{ ($currentType ?? 'reguler') === 'reguler' ? 'active' : '' }}">
                <i class="fas fa-users-rectangle"></i> Kelas (Reguler)
            </a>
            <a href="{{ route('dashboard.rombel.matpel') }}"
                class="periode-nav-tab {{ ($currentType ?? 'reguler') === 'matpel' ? 'active' : '' }}">
                <i class="fas fa-book-open"></i> Matpel Pilihan
            </a>
        </div>
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-filter text-primary me-1"></i> Pilih Kategori Rombel:</label>
                <select class="periode-mobile-select form-select" onchange="if(this.value) window.location.href=this.value;"
                    style="font-size: var(--text-base, 0.875rem); height: 42px; border-radius: var(--radius-lg, 12px); width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                    <option value="{{ route('dashboard.rombel.reguler') }}"
                        {{ ($currentType ?? 'reguler') === 'reguler' ? 'selected' : '' }}>
                        Kelas (Reguler)
                    </option>
                    <option value="{{ route('dashboard.rombel.matpel') }}"
                        {{ ($currentType ?? 'reguler') === 'matpel' ? 'selected' : '' }}>
                        Matpel Pilihan
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Rombel (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
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

                    <select id="filterTingkat" name="tingkat" class="toolbar-filter-select"
                        style="height: 38px; min-width: 130px;">
                        <option value="">Semua Tingkat</option>
                        @foreach ($filterTingkat as $tk)
                            <option value="{{ $tk }}" {{ $tingkat === $tk ? 'selected' : '' }}>
                                {{ $tk }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterJurusan" name="jurusan" class="toolbar-filter-select" style="height: 38px;">
                        <option value="">Semua Jurusan</option>
                        @foreach ($filterJurusan as $j)
                            <option value="{{ $j }}" {{ $jurusan === $j ? 'selected' : '' }}>
                                {{ $j }}
                            </option>
                        @endforeach
                    </select>

                    @if ($q || $tingkat || $jurusan)
                        <a href="{{ route('dashboard.rombel.' . ($currentType ?? 'reguler')) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari nama rombel / wali / ruang..."
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
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama', 'Nama Rombel'],
                                ['tingkat', 'Tingkat'],
                                ['jurusan', 'Kompetensi Keahlian'],
                                ['wali_kelas', 'Wali Kelas'],
                                ['ruang', 'Ruang'],
                                ['total_peserta_didik', 'Jml Peserta Didik'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'total_peserta_didik' ? 'text-align: center;' : '' }}"
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
                            <td style="padding: 14px 18px; font-weight: 700; color: var(--primary); font-size: var(--text-base, 0.875rem);"
                                data-label="Rombel">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-sm, 0.8125rem); flex-shrink: 0;">
                                        <i class="fas fa-chalkboard-user"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;">{{ $item->nama }}</div>
                                        @if ($item->kurikulum)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                {{ Str::limit($item->kurikulum, 40) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem);" data-label="Tingkat">
                                <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                    {{ $item->tingkat ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                data-label="Jurusan">
                                {{ $item->jurusan ?: '-' }}
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-color);"
                                data-label="Wali">
                                @if ($item->wali_kelas)
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-user-tie"
                                            style="color: var(--primary); opacity: 0.7; font-size: var(--text-sm, 0.8125rem);"></i>
                                        <span>{{ $item->wali_kelas }}</span>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                data-label="Ruang">
                                {{ $item->ruang ?: '-' }}
                            </td>
                            <td style="padding: 14px 18px; text-align: center; font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--success, #10b981);"
                                data-label="Peserta Didik">
                                <span class="badge"
                                    style="background: rgba(16,185,129,0.12); color: var(--success, #10b981); font-size: var(--text-sm, 0.8125rem); padding: 3px 10px;">
                                    <i class="fas fa-user-graduate me-1"></i>
                                    {{ number_format($item->total_peserta_didik, 0, ',', '.') }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions">
                                    <button type="button" class="btn-icon" title="Lihat Daftar Peserta Didik"
                                        onclick="openPesertaDidikModal('{{ $item->rombongan_belajar_id }}', '{{ addslashes($item->nama) }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                <div>Belum ada data rombongan belajar yang sesuai.</div>
                                <div style="font-size: var(--text-sm, 0.8125rem); margin-top: 6px;">
                                    Silakan sinkronisasi data Dapodik terlebih dahulu melalui menu <strong>Tarik Data
                                        Dapodik</strong>.
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

    {{-- Detail Peserta Didik & Pembelajaran Modal --}}
    <div id="pesertaDidikModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 id="pesertaDidikModalTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                        Rincian Rombongan Belajar
                    </h3>
                    <div id="pesertaDidikModalSubtitle"
                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        -
                    </div>
                </div>
                <button type="button" onclick="closePesertaDidikModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Tab Buttons (Desktop) -->
            <div class="rombel-tab-buttons">
                <button type="button" id="tabBtnAnggota" class="btn btn-primary"
                    style="padding: 5px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);"
                    onclick="switchRombelModalTab('anggota')">
                    <i class="fas fa-user-graduate me-1"></i> Anggota Peserta Didik <span id="badgeAnggotaCount"
                        class="badge"
                        style="background: rgba(255,255,255,0.2); font-size: var(--text-xs, 0.75rem); margin-left: 4px;">0</span>
                </button>
                <button type="button" id="tabBtnPembelajaran" class="btn btn-outline"
                    style="padding: 5px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);"
                    onclick="switchRombelModalTab('pembelajaran')">
                    <i class="fas fa-book-bookmark me-1"></i> Mata Pelajaran &amp; Guru <span id="badgePembelajaranCount"
                        class="badge"
                        style="background: rgba(255,255,255,0.1); font-size: var(--text-xs, 0.75rem); margin-left: 4px;">0</span>
                </button>
            </div>

            <!-- Tab Select Dropdown (Mobile Responsive) -->
            <div class="rombel-tab-select-wrapper">
                <select id="rombelTabDropdown" class="toolbar-filter-select"
                    style="width: 100%; max-width: 100%; font-weight: 600;" onchange="switchRombelModalTab(this.value)">
                    <option value="anggota">👥 Anggota Peserta Didik</option>
                    <option value="pembelajaran">📚 Mata Pelajaran &amp; Guru</option>
                </select>
            </div>

            <div id="pesertaDidikLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat rincian rombel...</div>
            </div>

            <!-- Tab 1: Peserta Didik -->
            <div id="pesertaDidikTableWrapper"
                style="overflow-x: auto; overflow-y: auto; flex: 1; display: none; width: 100%; -webkit-overflow-scrolling: touch; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                <table class="table"
                    style="min-width: 600px; width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: var(--text-sm, 0.8125rem);">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); width: 40px; text-align: center;">
                                No</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Nama Peserta Didik
                            </th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">NISN / NIPD</th>
                            <th
                                style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); text-align: center;">
                                L/P</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Pendaftaran</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Tempat, Tgl Lahir
                            </th>
                        </tr>
                    </thead>
                    <tbody id="pesertaDidikTableBody">
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Pembelajaran -->
            <div id="pembelajaranTableWrapper"
                style="overflow-x: auto; overflow-y: auto; flex: 1; display: none; width: 100%; -webkit-overflow-scrolling: touch; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                <table class="table"
                    style="min-width: 600px; width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: var(--text-sm, 0.8125rem);">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); width: 40px; text-align: center;">
                                No</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Mata Pelajaran</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Guru Pengampu</th>
                            <th
                                style="padding: 10px 14px; font-weight: 700; color: var(--text-muted); text-align: center;">
                                Jam / Mg</th>
                            <th style="padding: 10px 14px; font-weight: 700; color: var(--text-muted);">Status Kurikulum
                            </th>
                        </tr>
                    </thead>
                    <tbody id="pembelajaranTableBody">
                    </tbody>
                </table>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div id="pesertaDidikModalFooterCount" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Total: 0 Peserta Didik
                </div>
                <button type="button" class="btn btn-outline" onclick="closePesertaDidikModal()"
                    style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);">Tutup</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/rombel.js') }}?v={{ filemtime(public_path('js/rombel.js')) }}"></script>
    @endpush
@endsection
