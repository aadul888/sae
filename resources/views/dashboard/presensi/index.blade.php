@extends('layouts.dashboard')

@section('title', 'Presensi Peserta Didik — SAE')
@section('dash_title', 'Presensi Peserta Didik')

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    <!-- Dash Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-card text-primary me-2"></i> Presensi &amp; RFID Realtime
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Monitoring kehadiran peserta didik otomatis menggunakan kartu RFID fisik, QR Code digital, dan verifikasi
                kamera live terminal.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi.scan') }}" target="_blank" class="btn btn-primary"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Buka Terminal Kiosk Pemindai">
                <i class="fas fa-desktop"></i>
            </a>
        </div>
    </div>

    @if ($isLiburKalender)
        <div class="card"
            style="margin-bottom: 20px; border-left: 4px solid var(--danger); background: rgba(239, 68, 68, 0.08); padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="font-size: var(--text-3xl, 1.875rem); color: var(--danger);"><i class="fas fa-umbrella-beach"></i></div>
                <div>
                    <h4 style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                        Hari Libur Akademik — {{ $agendaHariIni ? $agendaHariIni->nama_kegiatan : 'Kalender Pendidikan' }}
                    </h4>
                    <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0;">
                        Berdasarkan sinkronisasi Kalender Pendidikan, tanggal
                        {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }} adalah hari libur peserta didik.
                        Presensi harian terminal dinonaktifkan secara otomatis.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($isDaringKalender)
        <div class="card"
            style="margin-bottom: 20px; border-left: 4px solid var(--primary); background: rgba(59, 130, 246, 0.08); padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="font-size: var(--text-3xl, 1.875rem); color: var(--primary);"><i class="fas fa-laptop-house"></i></div>
                <div>
                    <h4 style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                        Pembelajaran Daring (PJJ) —
                        {{ $agendaHariIni ? $agendaHariIni->nama_kegiatan : 'Jadwal PJJ / Belajar Rumah' }}
                    </h4>
                    <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0;">
                        Berdasarkan sinkronisasi Kalender Pendidikan, tanggal
                        {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }} dijadwalkan Pembelajaran Jarak Jauh
                        (Daring/PJJ). Terminal gerbang sekolah dinonaktifkan.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary Stats Grid (4x2 Sejajar Rapi) -->
    <div class="dash-stat-grid presensi-stat-grid-4x2">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($totalSiswa) }}</div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($countHadir) }}</div>
                <div class="dash-stat-label">Tepat Waktu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($countTerlambat) }}</div>
                <div class="dash-stat-label">Terlambat</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($countIzin) }}</div>
                <div class="dash-stat-label">Izin</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139,92,246,0.15); color: var(--purple);">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($countSakit) }}</div>
                <div class="dash-stat-label">Sakit</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($countAlpha) }}</div>
                <div class="dash-stat-label">Alpha</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(100,116,139,0.15); color: var(--text-muted);">
                <i class="fas fa-hourglass-start"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($countBelumAbsen) }}</div>
                <div class="dash-stat-label">Belum Presensi</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ $persenHadir }}%</div>
                <div class="dash-stat-label">Tingkat Hadir</div>
            </div>
        </div>
    </div>

    <!-- Desktop Navigation Tabs -->
    <div class="dash-desktop-tabs presensi-tabs">
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'log' ? 'active' : '' }}" data-tab="log">
            <i class="fas fa-list-check"></i> Log Presensi Hari Ini
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'rekap' ? 'active' : '' }}" data-tab="rekap">
            <i class="fas fa-chart-column"></i> Rekapitulasi &amp; Laporan
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'rfid' ? 'active' : '' }}" data-tab="rfid">
            <i class="fas fa-id-card"></i> Manajemen Kartu RFID
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'izin' ? 'active' : '' }}" data-tab="izin">
            <i class="fas fa-envelope-open-text"></i> Pengajuan E-Izin
            @if ($izinPending->count() > 0)
                <span class="badge badge-danger"
                    style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px;">{{ $izinPending->count() }}</span>
            @endif
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'pengaturan' ? 'active' : '' }}"
            data-tab="pengaturan">
            <i class="fas fa-sliders"></i> Pengaturan Jam &amp; Jadwal
        </button>
    </div>

    <!-- Mobile Custom Dropdown Selector (Tab Presensi) -->
    <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" id="presensiTabDropdownTrigger"
                aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label" id="presensiTabDropdownLabel">
                    @if ($activeTab === 'rekap')
                        <i class="fas fa-chart-column text-primary me-2"></i>
                        <span>Rekapitulasi &amp; Laporan</span>
                    @elseif ($activeTab === 'rfid')
                        <i class="fas fa-id-card text-primary me-2"></i>
                        <span>Manajemen Kartu RFID</span>
                    @elseif ($activeTab === 'izin')
                        <i class="fas fa-envelope-open-text text-primary me-2"></i>
                        <span>Pengajuan E-Izin</span>
                        @if ($izinPending->count() > 0)
                            <span class="badge badge-danger badge-sm ms-2">{{ $izinPending->count() }}</span>
                        @endif
                    @elseif ($activeTab === 'pengaturan')
                        <i class="fas fa-sliders text-primary me-2"></i>
                        <span>Pengaturan Jam &amp; Jadwal</span>
                    @else
                        <i class="fas fa-list-check text-primary me-2"></i>
                        <span>Log Presensi Hari Ini</span>
                    @endif
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" role="listbox">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'log']) }}" data-tab="log"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'log' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        <span>Log Presensi Hari Ini</span>
                    </div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'rekap']) }}" data-tab="rekap"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'rekap' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-chart-column text-primary me-2"></i>
                        <span>Rekapitulasi &amp; Laporan</span>
                    </div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'rfid']) }}" data-tab="rfid"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'rfid' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-id-card text-primary me-2"></i>
                        <span>Manajemen Kartu RFID</span>
                    </div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'izin']) }}" data-tab="izin"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'izin' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-envelope-open-text text-primary me-2"></i>
                        <span>Pengajuan E-Izin</span>
                    </div>
                    @if ($izinPending->count() > 0)
                        <span class="badge badge-danger badge-sm">{{ $izinPending->count() }}</span>
                    @endif
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'pengaturan']) }}" data-tab="pengaturan"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'pengaturan' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-sliders text-primary me-2"></i>
                        <span>Pengaturan Jam &amp; Jadwal</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- TAB 1: Log Presensi Hari Ini -->
    @include('dashboard.presensi.partials.tab-log')
    @include('dashboard.presensi.partials.tab-rekap')
    @include('dashboard.presensi.partials.tab-rfid')
    @include('dashboard.presensi.partials.tab-izin')
    @include('dashboard.presensi.partials.tab-pengaturan')

    @include('dashboard.presensi.partials.modals')
@endsection

@push('scripts')
    <script
        src="{{ asset('js/presensi.js') }}?v={{ file_exists(public_path('js/presensi.js')) ? filemtime(public_path('js/presensi.js')) : time() }}">
    </script>
@endpush
