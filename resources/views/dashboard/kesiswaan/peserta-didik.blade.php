@extends('layouts.dashboard')

@section('title', 'Peserta Didik (Aktif, Tidak Aktif, Berkas, Usulan & Alumni) — SAE')
@section('dash_title', 'Peserta Didik')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-users-viewfinder"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Peserta Didik
                </h2>
                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Direktori lengkap siswa aktif, riwayat nonaktif, verifikasi berkas fisik, usulan revisi data, dan arsip
                    alumni.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenUsulanModal"
                    title="Ajukan Usulan Perubahan Data Siswa"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['total_aktif'] ?? 0) }}
                    </div>
                    <div class="dash-stat-label">Total Siswa Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-mars"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['aktif_laki'] ?? 0) }}
                    </div>
                    <div class="dash-stat-label">Laki-Laki (L)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                    <i class="fas fa-venus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['aktif_perempuan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Perempuan (P)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">{{ $stats['total_rombel'] ?? 0 }}</div>
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
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                        {{ number_format($stats['total_tidak_aktif'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Tidak Aktif</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                    <i class="fas fa-right-from-bracket"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($stats['ta_mutasi'] ?? 0) }}
                    </div>
                    <div class="dash-stat-label">Mutasi / Pindah</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['ta_dikeluarkan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Dikeluarkan (DO)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(148,163,184,0.15); color: #94a3b8;">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format($stats['berkas_menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Validasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                        {{ number_format($stats['berkas_ditolak'] ?? 0) }}</div>
                    <div class="dash-stat-label">Ada Berkas Ditolak</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(148,163,184,0.15); color: #94a3b8;">
                    <i class="fas fa-folder-minus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['berkas_belum_lengkap'] ?? 0) }}</div>
                    <div class="dash-stat-label">Belum Lengkap</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format($stats['usulan_menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Review</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        {{ number_format($stats['usulan_disetujui'] ?? 0) }}</div>
                    <div class="dash-stat-label">Disetujui (Siap Dapodik)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                    <i class="fas fa-cloud-arrow-up"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #3b82f6;">
                        {{ number_format($stats['usulan_dapodik'] ?? 0) }}</div>
                    <div class="dash-stat-label">Selesai di Dapodik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['total_alumni'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Alumni</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-mars"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['alumni_laki'] ?? 0) }}</div>
                    <div class="dash-stat-label">Alumni Laki-Laki</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                    <i class="fas fa-venus"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['alumni_perempuan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Alumni Perempuan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">{{ $stats['alumni_angkatan'] ?? 0 }}</div>
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
                <i class="fas fa-folder-open"></i> Validasi
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
                    style="font-size: 0.85rem; height: 40px; border-radius: 8px;">
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
                        📁 Validasi
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

    <!-- 4. Toolbar & Filter Standar SAE -->
    <div class="card" style="padding: 16px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" class="per-page-select">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                {{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                @if ($activeTab === 'aktif')
                    <select id="filterRombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel</option>
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                {{ $r }}</option>
                        @endforeach
                    </select>
                    <select id="filterGender" class="toolbar-filter-select">
                        <option value="">Semua L/P</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                @elseif ($activeTab === 'tidak_aktif')
                    <select id="filterRombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel Terakhir</option>
                        @foreach ($filterRombelTidakAktif as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                {{ $r }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status Keluar</option>
                        @foreach (['Mutasi', 'Dikeluarkan', 'Mengundurkan Diri', 'Putus Sekolah', 'Wafat', 'Alumni'] as $st)
                            <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>
                                {{ $st }}</option>
                        @endforeach
                    </select>
                    <select id="filterGender" class="toolbar-filter-select">
                        <option value="">Semua L/P</option>
                        <option value="L" {{ $gender === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ $gender === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                @elseif ($activeTab === 'berkas')
                    <select id="filterRombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel</option>
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                {{ $r }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status Validasi</option>
                        <option value="lengkap" {{ $status === 'lengkap' ? 'selected' : '' }}>Berkas Lengkap &amp; Valid
                        </option>
                        <option value="belum_lengkap" {{ $status === 'belum_lengkap' ? 'selected' : '' }}>Belum Lengkap
                        </option>
                        <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Validasi</option>
                        <option value="tidak_valid" {{ $status === 'tidak_valid' ? 'selected' : '' }}>Ada Berkas Ditolak
                        </option>
                    </select>
                @elseif ($activeTab === 'usulan')
                    <select id="filterRombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel</option>
                        @foreach ($filterRombel as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                {{ $r }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="toolbar-filter-select">
                        <option value="">Semua Status Usulan</option>
                        <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Review</option>
                        <option value="disetujui" {{ $status === 'disetujui' ? 'selected' : '' }}>Disetujui (Siap Dapodik)
                        </option>
                        <option value="sudah_ke_dapodik" {{ $status === 'sudah_ke_dapodik' ? 'selected' : '' }}>Selesai di
                            Dapodik</option>
                        <option value="ditolak" {{ $status === 'ditolak' ? 'selected' : '' }}>Ditolak Kesiswaan</option>
                    </select>
                @elseif ($activeTab === 'alumni')
                    <select id="filterRombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel Terakhir</option>
                        @foreach ($filterRombelTidakAktif as $r)
                            <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>
                                {{ $r }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="filterTahunLulus" value="{{ $tahunLulus }}"
                        placeholder="Tahun Lulus (Contoh: 2026)" class="form-control"
                        style="width: 170px; height: 38px; border-radius: 8px; font-size: 0.84rem;">
                @endif

                @if (!empty($q) || !empty($rombel) || !empty($gender) || !empty($status) || !empty($tahunLulus))
                    <a href="{{ route('dashboard.kesiswaan.peserta-didik.index', ['tab' => $activeTab]) }}"
                        class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>

            <div class="live-search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearch" placeholder="Cari nama / NISN / NIPD / NIK..."
                    value="{{ $q ?? '' }}" autocomplete="off">
                <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 5. Content Sesuai Tab -->
    @if ($activeTab === 'aktif')
        <!-- TAB 1: SISWA AKTIF (Format Baku Selaras Modul Administrator) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $cols = [
                                ['nama', 'Nama Lengkap'],
                                ['nisn', 'NISN / NIPD'],
                                ['jenis_kelamin', 'L/P'],
                                ['nama_rombel', 'Rombel Kelas'],
                                ['tingkat_pendidikan_id', 'Tingkat'],
                            ];
                        @endphp
                        @foreach ($cols as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tempat, Tanggal Lahir
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktifList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama"
                                style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Nama Lengkap">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                            @if ($canUpdate || $canCreate) onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url }}', '{{ $item->foto_size ?? '' }}')"
                                            title="Klik untuk melihat / mengubah pasfoto peserta didik"
                                            @else
                                            onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                            title="Pasfoto {{ $item->nama }}" @endif
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                            @if ($canUpdate || $canCreate) onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '', '')"
                                            title="Klik untuk mengunggah pasfoto peserta didik"
                                            @else
                                            onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                            title="Belum ada pasfoto" @endif
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row"
                                            style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama"
                                                onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer;">{{ $item->nama }}</span>
                                            @if (!empty($item->is_koordinator))
                                                <span class="badge badge-koordinator"
                                                    id="badgeKoordinator_{{ $item->peserta_didik_id }}"
                                                    style="background: rgba(99,102,241,0.15); color: #6366f1; border: 1px solid rgba(99,102,241,0.3); font-size: 0.68rem; padding: 2px 7px; border-radius: 4px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"
                                                    title="Peserta didik ini ditunjuk sebagai Koordinator Kelas">
                                                    <i class="fas fa-crown" style="color: #f59e0b;"></i> Koordinator
                                                </span>
                                            @else
                                                <span class="badge badge-koordinator"
                                                    id="badgeKoordinator_{{ $item->peserta_didik_id }}"
                                                    style="display: none; background: rgba(99,102,241,0.15); color: #6366f1; border: 1px solid rgba(99,102,241,0.3); font-size: 0.68rem; padding: 2px 7px; border-radius: 4px; font-weight: 700; align-items: center; gap: 4px;"
                                                    title="Peserta didik ini ditunjuk sebagai Koordinator Kelas">
                                                    <i class="fas fa-crown" style="color: #f59e0b;"></i> Koordinator
                                                </span>
                                            @endif
                                        </div>
                                        @if (!empty($item->nik))
                                            <div
                                                style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                                    title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn"
                                style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                    style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                                data-label="Rombel">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->nama_rombel ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-tingkat"
                                style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="Tingkat">
                                {{ $item->tingkat_pendidikan_id ?: '-' }}
                            </td>
                            <td class="cell-pd-ttl"
                                style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="TTL">
                                <div class="cell-col-right" style="color: var(--text-muted);">
                                    {{ $item->tempat_lahir ? $item->tempat_lahir . ', ' : '' }}{{ $item->tanggal_lahir ? date('d/m/Y', strtotime($item->tanggal_lahir)) : '-' }}
                                </div>
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions"
                                    style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    @if ($canUpdate || $canCreate)
                                        <button type="button" class="btn-icon"
                                            title="{{ !empty($item->foto_url) ? 'Ganti / Lihat Pasfoto Peserta Didik' : 'Unggah Pasfoto Peserta Didik (PNG)' }}"
                                            onclick="openUploadFotoModal('{{ $item->peserta_didik_id }}', '{{ addslashes($item->nama) }}', '{{ $item->nisn ?? '' }}', '{{ $item->foto_url ?? '' }}', '{{ $item->foto_size ?? '' }}')">
                                            <i class="fas fa-camera"
                                                style="{{ !empty($item->foto_url) ? 'color: #10b981;' : '' }}"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                        onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                    @if (!empty($item->nisn))
                                        <button type="button" class="btn-icon"
                                            title="Pratinjau / Cetak Kartu Pelajar Digital"
                                            onclick="openKartuPelajarModal('{{ $item->nisn }}')">
                                            <i class="fas fa-address-card" style="color: #0284c7;"></i>
                                        </button>
                                    @endif
                                    @if ($canUpdate)
                                        <button type="button"
                                            class="btn-icon btn-toggle-koordinator {{ !empty($item->is_koordinator) ? 'is-active' : '' }}"
                                            id="btnKoordinator_{{ $item->peserta_didik_id }}"
                                            data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                            data-rombel="{{ $item->nama_rombel ?: '-' }}"
                                            data-status="{{ !empty($item->is_koordinator) ? '1' : '0' }}"
                                            title="{{ !empty($item->is_koordinator) ? 'Cabut Wewenang Koordinator Kelas' : 'Tunjuk sebagai Koordinator Kelas (Asisten Wali Kelas)' }}">
                                            <i class="fas fa-crown"
                                                style="{{ !empty($item->is_koordinator) ? 'color: #f59e0b; opacity: 1;' : '' }}"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data peserta didik aktif yang cocok.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($aktifList->hasPages())
            <div class="custom-pagination">
                @if ($aktifList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $aktifList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $aktifList->currentPage();
                    $last = $aktifList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $aktifList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $aktifList->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $aktifList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($aktifList->hasMorePages())
                    <a href="{{ $aktifList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @elseif ($activeTab === 'tidak_aktif')
        <!-- TAB 2: SISWA TIDAK AKTIF (Format Baku Selaras Modul Administrator) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $colsTa = [
                                ['nama', 'Nama Lengkap'],
                                ['nisn', 'NISN / NIPD'],
                                ['jenis_kelamin', 'L/P'],
                                ['nama_rombel_terakhir', 'Rombel Terakhir'],
                                ['tingkat_pendidikan_terakhir', 'Tingkat'],
                                ['tahun_lulus', 'Status & Tanggal'],
                            ];
                        @endphp
                        @foreach ($colsTa as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kontak &amp; Alamat
                        </th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tidakAktifList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama"
                                style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Nama">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                            onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')"
                                            title="Klik untuk memperbesar pasfoto"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(239,68,68,0.08); border: 1.5px dashed rgba(239,68,68,0.4); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.05rem; flex-shrink: 0;">
                                            <i class="fas fa-user-slash" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row"
                                            style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama"
                                                onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer;">{{ $item->nama }}</span>
                                            @if ($item->status_keluar === 'Alumni')
                                                <span class="badge"
                                                    style="font-size: 0.62rem; background: rgba(16,185,129,0.12); color: #10b981; padding: 2px 6px; white-space: nowrap;">
                                                    <i class="fas fa-graduation-cap me-1"></i>Alumni
                                                </span>
                                            @else
                                                <span class="badge"
                                                    style="font-size: 0.62rem; background: rgba(245,158,11,0.12); color: #f59e0b; padding: 2px 6px; white-space: nowrap;">
                                                    <i
                                                        class="fas fa-right-from-bracket me-1"></i>{{ $item->status_keluar ?: 'Tidak Aktif' }}
                                                </span>
                                            @endif
                                        </div>
                                        @if (!empty($item->nik))
                                            <div
                                                style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                                    title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn"
                                style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                    style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                                data-label="Rombel Terakhir">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->rombel_terakhir ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-tingkat"
                                style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="Tingkat">
                                {{ $item->tingkat_pendidikan_terakhir ?: '-' }}
                            </td>
                            <td class="cell-pd-ttl"
                                style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="Status & Tanggal">
                                <div class="cell-col-right">
                                    <div style="font-weight: 600; color: var(--text-color);">
                                        {{ $item->status_keluar === 'Alumni' ? 'Lulus ' . ($item->tahun_lulus ?: '-') : ($item->status_keluar ?: 'Keluar') }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        {{ $item->tanggal_keluar ? \Carbon\Carbon::parse($item->tanggal_keluar)->format('d/m/Y') : '-' }}
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted); max-width: 220px;"
                                data-label="Kontak & Alamat">
                                <div class="cell-col-right">
                                    @if ($item->nomor_telepon_seluler)
                                        <div><i class="fas fa-phone-alt me-1 text-primary"
                                                style="font-size: 0.72rem;"></i>
                                            <span class="copyable" data-copy="{{ $item->nomor_telepon_seluler }}"
                                                data-label="No HP"
                                                title="Klik untuk salin No HP">{{ $item->nomor_telepon_seluler }}</span>
                                        </div>
                                    @endif
                                    @if ($item->alamat_jalan)
                                        <div style="font-size: 0.74rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"
                                            title="{{ $item->alamat_jalan }}">
                                            <i class="fas fa-map-marker-alt me-1"
                                                style="font-size: 0.7rem;"></i>{{ $item->alamat_jalan }}
                                        </div>
                                    @endif
                                    @if (!$item->nomor_telepon_seluler && !$item->alamat_jalan)
                                        -
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions"
                                    style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    @if (!empty($item->foto_url))
                                        <button type="button" class="btn-icon" title="Lihat Pasfoto"
                                            onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')">
                                            <i class="fas fa-image" style="color: #10b981;"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn-icon" title="Lihat Biodata Lengkap"
                                        onclick="openBiodataPesertaDidikModal('{{ $item->peserta_didik_id }}')">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-user-slash mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data siswa tidak aktif yang cocok.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tidakAktifList->hasPages())
            <div class="custom-pagination">
                @if ($tidakAktifList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $tidakAktifList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $tidakAktifList->currentPage();
                    $last = $tidakAktifList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $tidakAktifList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $tidakAktifList->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $tidakAktifList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($tidakAktifList->hasMorePages())
                    <a href="{{ $tidakAktifList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @elseif ($activeTab === 'berkas')
        <!-- TAB 3: VERIFIKASI BERKAS FISIK & DIGITAL -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $colsBerkas = [['nama', 'Nama Siswa'], ['nisn', 'NISN / NIPD'], ['nama_rombel', 'Rombel']];
                        @endphp
                        @foreach ($colsBerkas as [$key, $label])
                            <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Akta Lahir</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Kartu Keluarga</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Ijazah SMP</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            KTP Ortu</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            KIP / PIP</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($berkasList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama"
                                style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Nama Siswa">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}"
                                            role="button" tabindex="0"
                                            title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}"
                                            role="button" tabindex="0"
                                            title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row"
                                            style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}"
                                                role="button" tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
                                        </div>
                                        @if (!empty($item->nik))
                                            <div
                                                style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                                    title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn"
                                style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-rombel"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem; order: 3;"
                                data-label="Rombel">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->rombel_nama ?: '-' }}
                                </span>
                            </td>
                            @php
                                $aktaF = $item->files['akta_kelahiran'] ?? null;
                                $kkF = $item->files['kartu_keluarga'] ?? null;
                                $ijzF = $item->files['ijazah_smp'] ?? null;
                                $ktpF = $item->files['ktp_orang_tua'] ?? null;
                                $kipF = $item->files['kip_pip'] ?? null;
                            @endphp
                            <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 5;"
                                data-label="Akta Lahir">
                                @if ($aktaF)
                                    @if ($aktaF->status === 'valid')
                                        <span class="badge badge-success" title="Valid / Sesuai"><i
                                                class="fas fa-circle-check"></i> Valid</span>
                                    @elseif ($aktaF->status === 'tidak_valid')
                                        <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                class="fas fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                class="fas fa-clock"></i> PDF</span>
                                    @endif
                                @elseif ($item->akta_kelahiran)
                                    <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                            class="fas fa-check"></i></span>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 6;"
                                data-label="Kartu Keluarga">
                                @if ($kkF)
                                    @if ($kkF->status === 'valid')
                                        <span class="badge badge-success" title="Valid / Sesuai"><i
                                                class="fas fa-circle-check"></i> Valid</span>
                                    @elseif ($kkF->status === 'tidak_valid')
                                        <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                class="fas fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                class="fas fa-clock"></i> PDF</span>
                                    @endif
                                @elseif ($item->kartu_keluarga)
                                    <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                            class="fas fa-check"></i></span>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 7;"
                                data-label="Ijazah SMP">
                                @if ($ijzF)
                                    @if ($ijzF->status === 'valid')
                                        <span class="badge badge-success" title="Valid / Sesuai"><i
                                                class="fas fa-circle-check"></i> Valid</span>
                                    @elseif ($ijzF->status === 'tidak_valid')
                                        <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                class="fas fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                class="fas fa-clock"></i> PDF</span>
                                    @endif
                                @elseif ($item->ijazah_smp)
                                    <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                            class="fas fa-check"></i></span>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 8;"
                                data-label="KTP Ortu">
                                @if ($ktpF)
                                    @if ($ktpF->status === 'valid')
                                        <span class="badge badge-success" title="Valid / Sesuai"><i
                                                class="fas fa-circle-check"></i> Valid</span>
                                    @elseif ($ktpF->status === 'tidak_valid')
                                        <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                class="fas fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                class="fas fa-clock"></i> PDF</span>
                                    @endif
                                @elseif ($item->ktp_orang_tua)
                                    <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                            class="fas fa-check"></i></span>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-pd-berkas-item" style="padding: 14px 18px; text-align: center; order: 9;"
                                data-label="KIP / PIP">
                                @if ($kipF)
                                    @if ($kipF->status === 'valid')
                                        <span class="badge badge-success" title="Valid / Sesuai"><i
                                                class="fas fa-circle-check"></i> Valid</span>
                                    @elseif ($kipF->status === 'tidak_valid')
                                        <span class="badge badge-danger" title="Tidak Valid / Ditolak"><i
                                                class="fas fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="badge badge-warning" title="Menunggu Verifikasi"><i
                                                class="fas fa-clock"></i> PDF</span>
                                    @endif
                                @elseif ($item->kip_pip)
                                    <span class="badge badge-success" title="Fisik Terverifikasi"><i
                                            class="fas fa-check"></i></span>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right; order: 10 !important;"
                                data-label="Aksi">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <button type="button" class="btn btn-outline btn-sm btn-validasi-berkas-detail"
                                        data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                        title="Validasi Dokumen Siswa"
                                        style="padding: 6px 14px; font-size: 0.78rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-check-double text-primary"></i> Validasi
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data berkas siswa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($berkasList->hasPages())
            <div class="custom-pagination">
                @if ($berkasList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $berkasList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $berkasList->currentPage();
                    $last = $berkasList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $berkasList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $berkasList->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $berkasList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($berkasList->hasMorePages())
                    <a href="{{ $berkasList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @elseif ($activeTab === 'usulan')
        <!-- TAB 4: USULAN PERUBAHAN DATA SISWA -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Nama Siswa</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Rombel</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kolom Data</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Perbandingan (Lama vs Baru)</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Alasan Perubahan</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Berkas Pendukung</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Status Verifikasi &amp; Dapodik</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                            Aksi Pengelolaan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usulanList as $item)
                        @php
                            $usulanPdId = $item->peserta_didik_id ?: $item->siswa?->peserta_didik_id ?? '';
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama"
                                style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Nama Siswa">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $usulanPdId }}" role="button"
                                            tabindex="0"
                                            title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->siswa?->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $usulanPdId }}"
                                            role="button" tabindex="0"
                                            title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row"
                                            style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $usulanPdId }}" role="button"
                                                tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->siswa?->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}</span>
                                        </div>
                                        <div
                                            style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                            @if ($item->siswa?->nisn)
                                                <span class="copyable" data-copy="{{ $item->siswa->nisn }}"
                                                    data-label="NISN" title="Klik untuk salin NISN">
                                                    NISN: {{ $item->siswa->nisn }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-rombel"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                                data-label="Rombel">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->siswa?->nama_rombel ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px;" data-label="Kolom Data">
                                <span class="badge badge-outline"
                                    style="font-family: monospace; font-size: 0.76rem; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $item->kolom_perubahan) }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px;" data-label="Perbandingan Nilai">
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 3px;">
                                    Lama: <span
                                        style="text-decoration: line-through; opacity: 0.85;">{{ $item->nilai_lama ?: '(Kosong)' }}</span>
                                </div>
                                <div style="font-size: 0.84rem; font-weight: 700; color: #10b981;">
                                    Baru: {{ $item->nilai_baru }}
                                </div>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);"
                                data-label="Alasan">
                                {{ $item->alasan }}
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Berkas Pendukung">
                                <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                    <span
                                        class="badge {{ ($item->kk_status ?? '') === 'valid' ? 'badge-success' : 'badge-outline' }}"
                                        style="font-size: 0.65rem; padding: 2px 6px;">
                                        KK: {{ ($item->kk_status ?? '') === 'valid' ? 'Valid' : 'Belum Valid' }}
                                    </span>
                                    <span
                                        class="badge {{ ($item->ijazah_status ?? '') === 'valid' ? 'badge-success' : 'badge-outline' }}"
                                        style="font-size: 0.65rem; padding: 2px 6px;">
                                        Ijazah: {{ ($item->ijazah_status ?? '') === 'valid' ? 'Valid' : 'Belum Valid' }}
                                    </span>
                                    @if (!empty($item->berkas_bukti_path))
                                        <button type="button" class="btn btn-outline btn-sm btn-preview-pdf-inline"
                                            data-url="{{ asset('storage/' . ltrim($item->berkas_bukti_path, '/')) }}"
                                            data-title="Bukti Usulan: {{ $item->kolom_perubahan }} - {{ $item->siswa?->nama }}"
                                            style="padding: 2px 8px; font-size: 0.7rem; border-radius: 4px; margin-top: 2px;">
                                            <i class="fas fa-file-pdf text-danger me-1"></i> Bukti PDF
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px;" data-label="Status">
                                @php
                                    $bColor = match ($item->status) {
                                        'disetujui' => 'badge-success',
                                        'sudah_ke_dapodik' => 'badge-primary',
                                        'ditolak' => 'badge-danger',
                                        default => 'badge-warning',
                                    };
                                    $bLabel = match ($item->status) {
                                        'disetujui' => 'Disetujui (Siap Dapodik)',
                                        'sudah_ke_dapodik' => 'Selesai di Dapodik',
                                        'ditolak' => 'Ditolak Kesiswaan',
                                        default => 'Menunggu Review',
                                    };
                                @endphp
                                <span class="badge {{ $bColor }}"
                                    style="font-size: 0.72rem; padding: 3px 8px;">{{ $bLabel }}</span>
                            </td>
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions"
                                    style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    <button type="button" class="btn-icon btn-kelola-usulan-modal"
                                        data-id="{{ $item->id }}" data-nama="{{ $item->siswa?->nama }}"
                                        title="Kelola &amp; Verifikasi Usulan Data"
                                        style="width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(99,102,241,0.12); color: var(--primary); border: 1px solid rgba(99,102,241,0.25); cursor: pointer;">
                                        <i class="fas fa-file-pen"></i>
                                    </button>
                                    @if ($item->status === 'disetujui' && $canUpdate)
                                        <button type="button" class="btn-icon btn-mark-dapodik"
                                            data-id="{{ $item->id }}" data-nama="{{ $item->siswa?->nama }}"
                                            data-kolom="{{ $item->kolom_perubahan }}"
                                            title="Tandai Selesai Di-update ke Dapodik"
                                            style="width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.25); cursor: pointer;">
                                            <i class="fas fa-cloud-arrow-up"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('dashboard.identitas.index', ['peserta_didik_id' => $usulanPdId]) }}"
                                        target="_blank" class="btn-icon" title="Lihat Formulir Lengkap Siswa"
                                        style="width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(6,182,212,0.12); color: var(--accent); border: 1px solid rgba(6,182,212,0.25);">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-file-circle-question mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada usulan perubahan data siswa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usulanList->hasPages())
            <div class="custom-pagination">
                @if ($usulanList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $usulanList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $usulanList->currentPage();
                    $last = $usulanList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $usulanList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $usulanList->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $usulanList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($usulanList->hasMorePages())
                    <a href="{{ $usulanList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @elseif ($activeTab === 'alumni')
        <!-- TAB 5: ALUMNI (DIALIHKAN SETELAH USULAN PERUBAHAN) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Nama Alumni</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            NISN / NIPD</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            L/P</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Rombel Terakhir</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                            Tahun Lulus</th>
                        <th
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alumniList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-pd-nama"
                                style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;"
                                data-label="Nama Alumni">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" data-biodata-id="{{ $item->peserta_didik_id }}"
                                            role="button" tabindex="0"
                                            title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); cursor: pointer; transition: all 0.2s ease;">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" data-biodata-id="{{ $item->peserta_didik_id }}"
                                            role="button" tabindex="0"
                                            title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                            style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0; cursor: pointer; transition: all 0.2s ease;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row"
                                            style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" data-biodata-id="{{ $item->peserta_didik_id }}"
                                                role="button" tabindex="0"
                                                title="Klik untuk melihat biodata lengkap {{ $item->nama }}"
                                                style="font-weight: 700; color: var(--text-color); cursor: pointer; transition: color 0.2s ease;">{{ $item->nama }}</span>
                                        </div>
                                        @if (!empty($item->nik))
                                            <div
                                                style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                <span class="copyable" data-copy="{{ $item->nik }}" data-label="NIK"
                                                    title="Klik untuk salin NIK">
                                                    NIK: {{ $item->nik }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-pd-nisn"
                                style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);"
                                data-label="NISN/NIPD">
                                <div class="cell-col-right">
                                    <div>
                                        @if ($item->nisn)
                                            <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                    @if (!empty($item->nipd))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="L/P">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                    style="font-size: 0.72rem; padding: 2px 7px;">
                                    {{ $item->jenis_kelamin ?: '-' }}
                                </span>
                            </td>
                            <td class="cell-pd-rombel"
                                style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: 0.84rem;"
                                data-label="Rombel Terakhir">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $item->rombel_terakhir ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; text-align: center;" data-label="Tahun Lulus">
                                <span class="badge badge-primary"
                                    style="font-weight: 700; font-size: 0.8rem; padding: 3px 8px;">
                                    {{ $item->tahun_lulus ?: ($item->tanggal_keluar ? date('Y', strtotime($item->tanggal_keluar)) : '-') }}
                                </span>
                            </td>
                            <td style="padding: 14px 18px; font-size: 0.85rem; color: var(--text-muted);"
                                data-label="Keterangan">
                                {{ $item->alasan_keluar ?: 'Alumni / Tamat Belajar' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                <i class="fas fa-graduation-cap mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                                <div>Belum ada data arsip alumni.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alumniList->hasPages())
            <div class="custom-pagination">
                @if ($alumniList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $alumniList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $alumniList->currentPage();
                    $last = $alumniList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $alumniList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $alumniList->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $alumniList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($alumniList->hasMorePages())
                    <a href="{{ $alumniList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- MODAL 1: DETAIL BIODATA LENGKAP PESERTA DIDIK (Persis seperti Manajemen Data) -->
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Peserta Didik</h3>
                        <div id="bioRombel" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#biodataModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat biodata peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi &amp; Fisik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">
                                -
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: monospace;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Anak Keberapa</td>
                            <td id="bioAnak">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tinggi / Berat Badan</td>
                            <td id="bioFisik" style="font-weight: 600; color: #10b981;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kebutuhan Khusus</td>
                            <td id="bioKhusus">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Data Akademik &amp; Pendaftaran
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Pendaftaran / Asal</td>
                            <td id="bioPendaftaran">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Masuk</td>
                            <td id="bioTglMasuk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">ID Registrasi / Anggota</td>
                            <td id="bioRegId"
                                style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users me-1"></i> Data Orang Tua &amp; Wali
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Nama Ayah</td>
                            <td id="bioAyah">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Ibu</td>
                            <td id="bioIbu">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Wali</td>
                            <td id="bioWali">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Kontak (HP/Email)</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>

                <div id="bioMapelSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: 0.85rem;"><i
                                class="fas fa-book-open text-primary me-1"></i> Mata Pelajaran di Kelas</span>
                        <span id="bioJmlMapel" class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem; padding: 2px 7px;">0
                            Mapel</span>
                    </div>
                    <div style="overflow-x: auto; max-height: 180px;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: 0.80rem; margin-bottom: 0;">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Guru Pengampu</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam</th>
                                </tr>
                            </thead>
                            <tbody id="bioMapelList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-modal" data-target="#biodataModal"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: AJUKAN USULAN PERUBAHAN DATA (Clean Global SAE Modal Style) -->
    <div id="modalUsulan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Ajukan Usulan
                    Perubahan Data</h3>
                <button type="button" class="close-modal" data-target="#modalUsulan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="formUsulan" method="POST"
                action="{{ route('dashboard.kesiswaan.peserta-didik.usulan.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                        Siswa <span class="text-danger">*</span></label>
                    <input type="search" id="usulanSiswaLookup" class="form-control"
                        placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                        data-people-target="#usulanSiswaId" data-people-require-selection="true" required
                        style="width: 100%; border-radius: 8px;">
                    <input type="hidden" id="usulanSiswaId" name="peserta_didik_id">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kolom
                        yang Ingin Diubah <span class="text-danger">*</span></label>
                    <select name="kolom_perubahan" class="form-control" required
                        style="width: 100%; border-radius: 8px;">
                        <option value="nama">Nama Lengkap</option>
                        <option value="nisn">NISN</option>
                        <option value="nik">NIK</option>
                        <option value="tempat_lahir">Tempat Lahir</option>
                        <option value="tanggal_lahir">Tanggal Lahir</option>
                        <option value="nama_ibu">Nama Ibu Kandung</option>
                        <option value="nama_ayah">Nama Ayah</option>
                        <option value="alamat_jalan">Alamat</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Nilai
                        Baru yang Benar <span class="text-danger">*</span></label>
                    <input type="text" name="nilai_baru" class="form-control"
                        placeholder="Tuliskan data yang benar sesuai dokumen resmi" required
                        style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan
                        Perubahan <span class="text-danger">*</span></label>
                    <input type="text" name="alasan" class="form-control"
                        placeholder="Contoh: Menyesuaikan Akta Kelahiran asli" required
                        style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Unggah
                        Berkas Bukti (PDF / Foto Akta / KK)</label>
                    <input type="file" name="berkas_bukti" class="form-control" accept=".pdf,.jpg,.jpeg,.png"
                        style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalUsulan"
                        style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i
                            class="fas fa-paper-plane"></i> Kirim Usulan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: VERIFIKASI BERKAS DIGITAL SISWA (PDF ONLY, 2 STATUS) -->
    <div id="modalVerifikasiBerkasDigital" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 id="modalVbNama"
                            style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Validasi Berkas Persyaratan Siswa</h3>
                        <div id="modalVbInfo" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            Memuat data siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalVbLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat daftar berkas digital siswa...</div>
            </div>

            <div id="modalVbContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
                <!-- Diisi secara dinamis via JavaScript -->
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    <i class="fas fa-circle-info text-primary me-1"></i>
                    Status berkas hanya 2 pilihan: <strong>Valid / Sesuai</strong> atau <strong>Tidak Valid / Tidak
                        Sesuai</strong>.
                </div>
                <button type="button" class="btn btn-outline close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="border-radius: 8px; padding: 7px 16px; font-size: 0.84rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: DETAIL LENGKAP & PENGELOLAAN USULAN PERUBAHAN DATA (FIELD ASAL VS PENYESUAIAN) -->
    <div id="modalPengelolaanUsulan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                        <i class="fas fa-file-pen"></i>
                    </div>
                    <div>
                        <h3 id="modalUsulanNama"
                            style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Pengelolaan Usulan Perubahan Data</h3>
                        <div id="modalUsulanSubtitle"
                            style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            Memuat data usulan siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalPengelolaanUsulan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalUsulanLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat rincian usulan perubahan data...</div>
            </div>

            <div id="modalUsulanContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
                <!-- Diisi secara dinamis via JavaScript -->
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    <i class="fas fa-shield-halved text-success me-1"></i>
                    Verifikasi keputusan perubahan data resmi untuk sinkronisasi Dapodik.
                </div>
                <button type="button" class="btn btn-outline close-modal" data-target="#modalPengelolaanUsulan"
                    style="border-radius: 8px; padding: 7px 16px; font-size: 0.84rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: VIEWER DOKUMEN PDF TERPADU (TANPA MEMBUKA TAB BARU) -->
    <div id="modalPdfViewer" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); z-index: 100005 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 960px; width: 96%; height: 90vh; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 18px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 8px; background: rgba(239,68,68,0.12); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 id="pdfViewerTitle"
                            style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Pratinjau Dokumen PDF</h3>
                        <div id="pdfViewerSubtitle"
                            style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Review berkas langsung di dalam sistem tanpa membuka tab baru
                        </div>
                    </div>
                </div>
                <button type="button" class="close-pdf-viewer"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div
                style="flex: 1; min-height: 0; border-radius: 10px; overflow: hidden; background: #0f172a; border: 1px solid var(--border-color); position: relative;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-pdf-viewer"
                    style="border-radius: 8px; padding: 7px 18px; font-size: 0.82rem;">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Unggah Pasfoto Peserta Didik (Sesuai Modul Administrator) --}}
    @include('partials.modal-upload-foto-pd')

    {{-- Modal Pratinjau Kartu Pelajar Digital (Sesuai Modul Administrator) --}}
    @include('kartu-pelajar.modal-preview')

    {{-- Modal Preview Pasfoto Siswa Tidak Aktif --}}
    <div id="photoPreviewModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);"
        onclick="if(typeof closePhotoPreviewModal === 'function') closePhotoPreviewModal();">
        <div class="card"
            style="max-width: 420px; width: 90%; text-align: center; border-radius: 16px; padding: 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);"
            onclick="event.stopPropagation();">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h4 id="photoPreviewName"
                    style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0;">Pasfoto</h4>
                <button type="button"
                    onclick="if(typeof closePhotoPreviewModal === 'function') closePhotoPreviewModal();"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div
                style="width: 100%; max-height: 480px; overflow: hidden; border-radius: 12px; border: 1px solid var(--border-color); background: #000; margin-bottom: 12px;">
                <img id="photoPreviewImg" src="" alt="Pasfoto"
                    style="width: 100%; height: auto; max-height: 480px; object-fit: contain;">
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
@endpush

@push('scripts')
    <script
        src="{{ asset('js/peserta-didik-aktif.js') }}?v={{ file_exists(public_path('js/peserta-didik-aktif.js')) ? filemtime(public_path('js/peserta-didik-aktif.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kesiswaan-peserta-didik.js') }}?v={{ file_exists(public_path('js/kesiswaan-peserta-didik.js')) ? filemtime(public_path('js/kesiswaan-peserta-didik.js')) : time() }}">
    </script>
@endpush
