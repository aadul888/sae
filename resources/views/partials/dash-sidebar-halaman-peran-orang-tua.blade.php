{{-- 5. Peran: Orang Tua --}}
@php
    $isAdminOtActive =
        request()->routeIs('dashboard.orang-tua') ||
        request()->routeIs('dashboard.orang_tua') ||
        request()->routeIs('dashboard.orang-tua.kehadiran*') ||
        request()->routeIs('dashboard.orang-tua.izin*');
@endphp
<div class="dash-nav-group {{ $isAdminOtActive ? 'open active-group' : '' }}">
    <button type="button" class="dash-nav-toggle">
        <div class="dash-nav-toggle-main">
            <span class="nav-icon"><i class="fas fa-fw fa-people-roof"></i></span>
            <span class="nav-label">Orang Tua</span>
        </div>
        <i class="fas fa-chevron-right arrow-icon"></i>
    </button>
    <div class="dash-nav-submenu">
        <a href="{{ route('dashboard.orang-tua') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.orang-tua') || request()->routeIs('dashboard.orang_tua') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
            <span class="nav-label">Portal Orang Tua</span>
        </a>
        <a href="{{ route('dashboard.informasi.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.informasi*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-bullhorn"></i></span>
            <span class="nav-label">Pengumuman Sekolah</span>
        </a>
        <a href="{{ route('dashboard.orang-tua.kehadiran') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.orang-tua.kehadiran*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
            <span class="nav-label">Riwayat Kehadiran</span>
        </a>
        <a href="{{ route('dashboard.jadwal-pelajaran.index', ['view' => 'siswa']) }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.jadwal-pelajaran*') && request('view') === 'siswa' ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
            <span class="nav-label">Jadwal Pelajaran KBM</span>
        </a>
        <a href="{{ route('dashboard.orang-tua.izin') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.orang-tua.izin*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-ticket-alt"></i></span>
            <span class="nav-label">e-Izin &amp; Surat Sakit</span>
        </a>
        <a href="{{ route('dashboard.identitas.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.identitas*') || request()->routeIs('dashboard.peserta-didik.identitas*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card-clip"></i></span>
            <span class="nav-label">Identitas Lengkap Siswa</span>
        </a>
    </div>
</div>
