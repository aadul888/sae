@extends('layouts.dashboard')

@section('title', 'Peserta Didik (Aktif, Tidak Aktif, Berkas, Usulan & Alumni) — SAE')
@section('dash_title', 'Peserta Didik')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-users-viewfinder"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Peserta Didik
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Direktori lengkap siswa aktif, riwayat nonaktif, verifikasi berkas fisik, usulan revisi data, dan arsip
                    alumni.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenUsulanModal"
                    title="Ajukan Usulan Perubahan Data Siswa"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-file-pen"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE (Disesuaikan Dinamis per Tab Terbuka - 4 Card Rapi) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        @if ($activeTab === 'aktif')
            {{-- Rekap 4 Card untuk Tab Siswa Aktif --}}
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['total_aktif'] ?? 0) }}
                    </div>
                    <div class="dash-stat-label">Total Siswa Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-mars"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['aktif_laki'] ?? 0) }}
                    </div>
                    <div class="dash-stat-label">Laki-Laki (L)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                    <i class="fas fa-venus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['aktif_perempuan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Perempuan (P)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ $stats['total_rombel'] ?? 0 }}</div>
                    <div class="dash-stat-label">Total Rombel Kelas</div>
                </div>
            </div>
        @elseif ($activeTab === 'tidak_aktif')
            {{-- Rekap 4 Card untuk Tab Siswa Tidak Aktif --}}
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-user-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ef4444;">
                        {{ number_format($stats['total_tidak_aktif'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Tidak Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-right-from-bracket"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['ta_mutasi'] ?? 0) }}
                    </div>
                    <div class="dash-stat-label">Mutasi / Pindah</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['ta_dikeluarkan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Dikeluarkan (DO)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(148,163,184,0.15); color: #94a3b8;">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['ta_mengundurkan_lainnya'] ?? 0) }}</div>
                    <div class="dash-stat-label">Mengundurkan Diri &amp; Lainnya</div>
                </div>
            </div>
        @elseif ($activeTab === 'berkas')
            {{-- Rekap 4 Card untuk Tab Validasi Berkas --}}
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                        {{ number_format($stats['berkas_menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Validasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ef4444;">
                        {{ number_format($stats['berkas_ditolak'] ?? 0) }}</div>
                    <div class="dash-stat-label">Ada Berkas Ditolak</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(148,163,184,0.15); color: #94a3b8;">
                    <i class="fas fa-folder-minus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['berkas_belum_lengkap'] ?? 0) }}</div>
                    <div class="dash-stat-label">Belum Lengkap</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                        {{ number_format($stats['berkas_lengkap'] ?? 0) }}</div>
                    <div class="dash-stat-label">Lengkap &amp; Valid</div>
                </div>
            </div>
        @elseif ($activeTab === 'usulan')
            {{-- Rekap 4 Card untuk Tab Usulan Perubahan Data --}}
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                        {{ number_format($stats['usulan_menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Review</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                        {{ number_format($stats['usulan_disetujui'] ?? 0) }}</div>
                    <div class="dash-stat-label">Disetujui (Siap Dapodik)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                    <i class="fas fa-cloud-arrow-up"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #3b82f6;">
                        {{ number_format($stats['usulan_dapodik'] ?? 0) }}</div>
                    <div class="dash-stat-label">Selesai di Dapodik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ef4444;">
                        {{ number_format($stats['usulan_ditolak'] ?? 0) }}</div>
                    <div class="dash-stat-label">Ditolak Kesiswaan</div>
                </div>
            </div>
        @elseif ($activeTab === 'alumni')
            {{-- Rekap 4 Card untuk Tab Alumni --}}
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #2563eb;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['total_alumni'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Alumni</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-mars"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['alumni_laki'] ?? 0) }}</div>
                    <div class="dash-stat-label">Alumni Laki-Laki</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                    <i class="fas fa-venus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($stats['alumni_perempuan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Alumni Perempuan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ $stats['alumni_angkatan'] ?? 0 }}</div>
                    <div class="dash-stat-label">Total Angkatan Kelulusan</div>
                </div>
            </div>
        @endif
    </div>

    <!-- 3. Navigation Tabs Wrapper (Urutan: Aktif, Tidak Aktif, Berkas Fisik, Usulan Perubahan, Alumni) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'aktif']) }}"
                class="periode-nav-tab {{ $activeTab === 'aktif' ? 'active' : '' }}">
                <i class="fas fa-user-check"></i> Aktif
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'tidak_aktif']) }}"
                class="periode-nav-tab {{ $activeTab === 'tidak_aktif' ? 'active' : '' }}">
                <i class="fas fa-user-xmark"></i> Tidak Aktif
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'berkas']) }}"
                class="periode-nav-tab {{ $activeTab === 'berkas' ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Berkas
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']) }}"
                class="periode-nav-tab {{ $activeTab === 'usulan' ? 'active' : '' }}">
                <i class="fas fa-file-pen"></i> Usulan Perubahan
            </a>
            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'alumni']) }}"
                class="periode-nav-tab {{ $activeTab === 'alumni' ? 'active' : '' }}">
                <i class="fas fa-graduation-cap"></i> Alumni
            </a>
        </div>

        {{-- Mobile Responsive Dropdown Switcher --}}
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-layer-group text-primary me-1"></i> Pilih Kategori Data Siswa:</label>
                <select class="periode-mobile-select form-select"
                    onchange="if(this.value) window.location.href=this.value;"
                    style="font-size: var(--text-base, 0.875rem); height: 40px; border-radius: var(--radius-md, 8px);">
                    <option value="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'aktif']) }}"
                        {{ $activeTab === 'aktif' ? 'selected' : '' }}>
                        👤 Siswa Aktif ({{ number_format($stats['total_aktif'] ?? 0) }})
                    </option>
                    <option value="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'tidak_aktif']) }}"
                        {{ $activeTab === 'tidak_aktif' ? 'selected' : '' }}>
                        🚫 Siswa Tidak Aktif ({{ number_format($stats['total_tidak_aktif'] ?? 0) }})
                    </option>
                    <option value="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'berkas']) }}"
                        {{ $activeTab === 'berkas' ? 'selected' : '' }}>
                        📁 Berkas
                    </option>
                    <option value="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']) }}"
                        {{ $activeTab === 'usulan' ? 'selected' : '' }}>
                        📝 Usulan Perubahan Data
                        {{ ($stats['usulan_menunggu'] ?? 0) > 0 ? '(' . $stats['usulan_menunggu'] . ' Menunggu)' : '' }}
                    </option>
                    <option value="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'alumni']) }}"
                        {{ $activeTab === 'alumni' ? 'selected' : '' }}>
                        🎓 Data Alumni ({{ number_format($stats['total_alumni'] ?? 0) }})
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- Unified Datatable Peserta Didik Kesiswaan (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header Datatable Baku SAE (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if ($activeTab === 'aktif')
                        <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel</option>
                            @foreach ($filterRombel as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                        <select id="filterGender" name="gender" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua L/P</option>
                            <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    @elseif ($activeTab === 'tidak_aktif')
                        <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel Terakhir</option>
                            @foreach ($filterRombelTidakAktif as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                        <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Status Keluar</option>
                            @foreach (['Mutasi', 'Dikeluarkan', 'Mengundurkan Diri', 'Putus Sekolah', 'Wafat', 'Alumni'] as $st)
                                <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>
                                    {{ $st }}</option>
                            @endforeach
                        </select>
                        <select id="filterGender" name="gender" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua L/P</option>
                            <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    @elseif ($activeTab === 'berkas')
                        <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel</option>
                            @foreach ($filterRombel as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                        <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Status Validasi</option>
                            <option value="lengkap" {{ $status === 'lengkap' ? 'selected' : '' }}>Berkas Lengkap &amp;
                                Valid
                            </option>
                            <option value="belum_lengkap" {{ $status === 'belum_lengkap' ? 'selected' : '' }}>Belum
                                Lengkap
                            </option>
                            <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Validasi
                            </option>
                            <option value="tidak_valid" {{ $status === 'tidak_valid' ? 'selected' : '' }}>Ada Berkas
                                Ditolak
                            </option>
                        </select>
                    @elseif ($activeTab === 'usulan')
                        <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel</option>
                            @foreach ($filterRombel as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                        <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Status Usulan</option>
                            <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Review
                            </option>
                            <option value="disetujui" {{ $status === 'disetujui' ? 'selected' : '' }}>Disetujui (Siap
                                Dapodik)
                            </option>
                            <option value="sudah_ke_dapodik" {{ $status === 'sudah_ke_dapodik' ? 'selected' : '' }}>
                                Selesai di
                                Dapodik</option>
                            <option value="ditolak" {{ $status === 'ditolak' ? 'selected' : '' }}>Ditolak Kesiswaan
                            </option>
                        </select>
                    @elseif ($activeTab === 'alumni')
                        <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Rombel Terakhir</option>
                            @foreach ($filterRombelTidakAktif as $r)
                                <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="filterTahunLulus" name="tahun_lulus" value="{{ $tahunLulus }}"
                            placeholder="Tahun Lulus (Contoh: 2026)" class="form-control"
                            style="width: 170px; height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    @endif

                    @if (!empty($q) || !empty($rombel) || !empty($gender) || !empty($status) || !empty($tahunLulus))
                        <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => $activeTab]) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NISN / NIPD / NIK..."
                        value="{{ $q ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Content Sesuai Tab -->
        @if ($activeTab === 'aktif')
            @include('dashboard.kesiswaan.partials.tab-aktif')
        @elseif ($activeTab === 'tidak_aktif')
            @include('dashboard.kesiswaan.partials.tab-tidak-aktif')
        @elseif ($activeTab === 'berkas')
            @include('dashboard.kesiswaan.partials.tab-berkas')
        @elseif ($activeTab === 'usulan')
            @include('dashboard.kesiswaan.partials.tab-usulan')
        @elseif ($activeTab === 'alumni')
            @include('dashboard.kesiswaan.partials.tab-alumni')
        @endif
    </div>

    @include('dashboard.kesiswaan.partials.modals')
@endsection
