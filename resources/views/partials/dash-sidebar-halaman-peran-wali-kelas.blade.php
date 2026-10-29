{{-- 2. Peran: Wali Kelas --}}
@php
    $isAdminWaliKelasActive =
        request()->routeIs('dashboard.wali-kelas.*') ||
        request()->routeIs('dashboard.wali-kelas') ||
        (request()->routeIs('dashboard.tugas-tambahan.show') && request()->route('kode') === 'wali-kelas') ||
        request()->routeIs('dashboard.jadwal-kbm.manual*');
@endphp
<div class="dash-nav-group {{ $isAdminWaliKelasActive ? 'open active-group' : '' }}">
    <button type="button" class="dash-nav-toggle">
        <div class="dash-nav-toggle-main">
            <span class="nav-icon"><i class="fas fa-fw fa-user-tie"></i></span>
            <span class="nav-label">Wali Kelas</span>
        </div>
        <i class="fas fa-chevron-right arrow-icon"></i>
    </button>
    <div class="dash-nav-submenu">
        <a href="{{ route('dashboard.tugas-tambahan.show', ['kode' => 'wali-kelas']) }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.tugas-tambahan.show') && request()->route('kode') === 'wali-kelas' ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
            <span class="nav-label">Dashboard Wali Kelas</span>
        </a>
        <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.peserta-didik-aktif*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-circle-check"></i></span>
            <span class="nav-label">Peserta Didik Aktif</span>
        </a>
        <a href="{{ route('dashboard.wali-kelas.peserta-didik-tidak-aktif.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.peserta-didik-tidak-aktif*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-circle-xmark"></i></span>
            <span class="nav-label">Peserta Didik Tidak Aktif</span>
        </a>
        <a href="{{ route('dashboard.jadwal-kbm.manual.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.jadwal-kbm.manual*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-plus"></i></span>
            <span class="nav-label">Input Jadwal KBM</span>
        </a>
        <a href="{{ route('dashboard.wali-kelas.presensi.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.presensi*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-user"></i></span>
            <span class="nav-label">Presensi Kelas</span>
        </a>
        <a href="{{ route('dashboard.wali-kelas.agenda.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.agenda*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-open-reader"></i></span>
            <span class="nav-label">Agenda Kelas</span>
        </a>
        <a href="{{ route('dashboard.wali-kelas.validasi.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.validasi*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-check"></i></span>
            <span class="nav-label">Validasi Data</span>
        </a>
    </div>
</div>
