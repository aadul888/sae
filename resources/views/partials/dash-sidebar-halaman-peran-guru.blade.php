{{-- 1. Peran: Guru --}}
@php
    $isAdminGuruActive =
        request()->routeIs('dashboard.guru') ||
        request()->routeIs('dashboard.presensi-mengajar.*') ||
        request()->routeIs('dashboard.presensi-mengajar') ||
        request()->routeIs('dashboard.agenda-kbm.*') ||
        request()->routeIs('dashboard.agenda-kbm') ||
        request()->routeIs('dashboard.presensi.kelas*') ||
        (request()->routeIs('dashboard.jadwal-pelajaran*') && request('view') === 'guru');
@endphp
<div class="dash-nav-group {{ $isAdminGuruActive ? 'open active-group' : '' }}">
    <button type="button" class="dash-nav-toggle">
        <div class="dash-nav-toggle-main">
            <span class="nav-icon"><i class="fas fa-fw fa-chalkboard-user"></i></span>
            <span class="nav-label">Guru</span>
        </div>
        <i class="fas fa-chevron-right arrow-icon"></i>
    </button>
    <div class="dash-nav-submenu">
        <a href="{{ route('dashboard.guru') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.guru') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
            <span class="nav-label">Dashboard Guru</span>
        </a>
        <a href="{{ route('dashboard.jadwal-pelajaran.index', ['view' => 'guru']) }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.jadwal-pelajaran*') && request('view') === 'guru' ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
            <span class="nav-label">Jadwal Mengajar</span>
        </a>
        <a href="{{ route('dashboard.presensi-mengajar.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.presensi-mengajar*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
            <span class="nav-label">Presensi Mengajar</span>
        </a>
        <a href="{{ route('dashboard.presensi.kelas') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.presensi.kelas*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-users-viewfinder"></i></span>
            <span class="nav-label">Presensi Kelas</span>
        </a>
        <a href="{{ route('dashboard.agenda-kbm.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.agenda-kbm*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-open-reader"></i></span>
            <span class="nav-label">Jurnal &amp; Agenda KBM</span>
        </a>
    </div>
</div>
