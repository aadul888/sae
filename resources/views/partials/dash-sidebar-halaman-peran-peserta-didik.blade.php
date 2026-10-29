{{-- 4. Peran: Peserta Didik --}}
@php
    $isAdminPdActive =
        request()->routeIs('dashboard.peserta-didik') ||
        request()->routeIs('dashboard.peserta_didik') ||
        request()->routeIs('dashboard.identitas*') ||
        request()->routeIs('dashboard.peserta-didik.identitas*') ||
        request()->routeIs('dashboard.peserta-didik.izin.*') ||
        request()->routeIs('dashboard.peserta-didik.izin') ||
        request()->routeIs('dashboard.peserta-didik.presensi.*') ||
        request()->routeIs('dashboard.peserta-didik.presensi') ||
        request()->routeIs('dashboard.berkas.*') ||
        request()->routeIs('dashboard.berkas') ||
        request()->routeIs('dashboard.validasi-berkas.*') ||
        (request()->routeIs('dashboard.jadwal-pelajaran*') && request('view') === 'siswa');
@endphp
<div class="dash-nav-group {{ $isAdminPdActive ? 'open active-group' : '' }}">
    <button type="button" class="dash-nav-toggle">
        <div class="dash-nav-toggle-main">
            <span class="nav-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
            <span class="nav-label">Peserta Didik</span>
        </div>
        <i class="fas fa-chevron-right arrow-icon"></i>
    </button>
    <div class="dash-nav-submenu">
        <a href="{{ route('dashboard.peserta-didik') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik') || request()->routeIs('dashboard.peserta_didik') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
            <span class="nav-label">Dashboard Peserta Didik</span>
        </a>
        <a href="{{ route('dashboard.identitas.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.identitas*') || request()->routeIs('dashboard.peserta-didik.identitas*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card-clip"></i></span>
            <span class="nav-label">Identitas Lengkap</span>
        </a>
        <a href="{{ route('dashboard.berkas.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.berkas*') || request()->routeIs('dashboard.validasi-berkas*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-folder-open"></i></span>
            <span class="nav-label">Validasi Berkas</span>
        </a>
        <a href="{{ route('dashboard.peserta-didik.izin.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik.izin*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-envelope-open-text"></i></span>
            <span class="nav-label">Surat Izin &amp; Sakit</span>
        </a>
        <a href="{{ route('dashboard.peserta-didik.presensi.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik.presensi*') || request()->routeIs('dashboard.presensi.riwayat-saya*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
            <span class="nav-label">Riwayat Presensi Harian</span>
        </a>
        <a href="{{ route('dashboard.jadwal-pelajaran.index', ['view' => 'siswa']) }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.jadwal-pelajaran*') && request('view') === 'siswa' ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
            <span class="nav-label">Jadwal Pelajaran</span>
        </a>
    </div>
</div>
