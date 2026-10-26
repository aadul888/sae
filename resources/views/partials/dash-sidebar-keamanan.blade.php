{{-- 8. Submenu Per-Bidang: Keamanan & Satpam --}}
@if ($can('menu_keamanan'))
    <div class="dash-nav-nested-group {{ $isKeamananActive ? 'open active-group' : '' }}">
        <button type="button" class="dash-nav-nested-toggle">
            <div class="dash-nav-nested-toggle-main">
                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-shield-halved"></i></span>
                <span class="nav-label">Keamanan &amp; Tamu</span>
            </div>
            <i class="fas fa-chevron-right nested-arrow-icon"></i>
        </button>
        <div class="dash-nav-nested-menu">
            <a href="{{ route('dashboard.tendik', ['bidang' => 'keamanan']) }}"
                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'keamanan' ? 'active' : '' }}">
                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard Keamanan</span>
            </a>
            @if ($can('menu_keamanan_izin') || $can('menu_keamanan'))
                <a href="{{ route('dashboard.keamanan.izin.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.izin*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-shield-alt"></i></span>
                    <span class="nav-label">Verifikasi e-Izin Gerbang</span>
                </a>
            @endif
            @if ($can('menu_keamanan_presensi') || $can('menu_keamanan'))
                <a href="{{ route('dashboard.keamanan.presensi.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.presensi*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-id-card-alt"></i></span>
                    <span class="nav-label">Monitoring Presensi Siswa</span>
                </a>
            @endif
            @if ($can('menu_keamanan_buku_tamu') || $can('menu_buku_tamu') || $can('menu_keamanan'))
                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.buku-tamu*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-open"></i></span>
                    <span class="nav-label">Buku Tamu Pos Depan</span>
                </a>
            @endif
            @if ($can('menu_keamanan_patroli') || $can('menu_keamanan'))
                <a href="{{ route('dashboard.keamanan.patroli.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.patroli*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-shield-virus"></i></span>
                    <span class="nav-label">Patroli &amp; Insiden</span>
                </a>
            @endif
            @if ($can('menu_keamanan_scan') || $can('menu_keamanan'))
                <a href="{{ route('presensi.scan') }}" target="_blank" class="dash-nav-nested-link">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-qrcode"></i></span>
                    <span class="nav-label">Pos Scanner RFID</span>
                </a>
            @endif
        </div>
    </div>
@endif
