{{-- 7. Submenu Per-Bidang: Teknisi & Maintenance --}}
@if ($can('menu_teknisi'))
    <div class="dash-nav-nested-group {{ $isTeknisiActive ? 'open active-group' : '' }}">
        <button type="button" class="dash-nav-nested-toggle">
            <div class="dash-nav-nested-toggle-main">
                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-tools"></i></span>
                <span class="nav-label">Teknisi &amp; Sarpras</span>
            </div>
            <i class="fas fa-chevron-right nested-arrow-icon"></i>
        </button>
        <div class="dash-nav-nested-menu">
            <a href="{{ route('dashboard.tendik', ['bidang' => 'teknisi']) }}"
                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'teknisi' ? 'active' : '' }}">
                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard Teknisi</span>
            </a>
            @if ($can('menu_teknisi_wo') || $can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.work-order') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.work-order*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-wrench"></i></span>
                    <span class="nav-label">Work Order &amp; Perbaikan</span>
                </a>
            @endif
            @if ($can('menu_teknisi_pemeliharaan') || $can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.pemeliharaan') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.pemeliharaan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                    <span class="nav-label">Jadwal Pemeliharaan PM</span>
                </a>
            @endif
        </div>
    </div>
@endif
