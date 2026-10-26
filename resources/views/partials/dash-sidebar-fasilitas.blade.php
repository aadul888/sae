{{-- 9. Submenu Per-Bidang: Fasilitas & Penjaga --}}
@if ($can('menu_penjaga'))
    <div class="dash-nav-nested-group {{ $isPenjagaActive ? 'open active-group' : '' }}">
        <button type="button" class="dash-nav-nested-toggle">
            <div class="dash-nav-nested-toggle-main">
                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-broom"></i></span>
                <span class="nav-label">Fasilitas &amp; Penjaga</span>
            </div>
            <i class="fas fa-chevron-right nested-arrow-icon"></i>
        </button>
        <div class="dash-nav-nested-menu">
            <a href="{{ route('dashboard.tendik', ['bidang' => 'penjaga']) }}"
                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'penjaga' ? 'active' : '' }}">
                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard Penjaga</span>
            </a>
            @if ($can('menu_penjaga_kebersihan') || $can('menu_penjaga'))
                <a href="{{ route('dashboard.penjaga.kebersihan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.penjaga.kebersihan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                    <span class="nav-label">Checklist Kebersihan</span>
                </a>
            @endif
            @if ($can('menu_penjaga_ronda') || $can('menu_penjaga'))
                <a href="{{ route('dashboard.penjaga.ronda.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.penjaga.ronda*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-moon"></i></span>
                    <span class="nav-label">Buku Jaga &amp; Ronda</span>
                </a>
            @endif
        </div>
    </div>
@endif
