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
            @include('partials.dash-sidebar-teknisi-menu', [
                'linkClass' => 'dash-nav-nested-link',
                'iconClass' => 'nested-icon',
            ])
        </div>
    </div>
@endif
