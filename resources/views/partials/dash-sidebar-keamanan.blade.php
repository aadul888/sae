{{-- 8. Submenu Per-Bidang: Keamanan & Satpam --}}
@if ($can('menu_keamanan'))
    <div class="dash-nav-nested-group {{ $isKeamananActive ? 'open active-group' : '' }}">
        <button type="button" class="dash-nav-nested-toggle">
            <div class="dash-nav-nested-toggle-main">
                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-shield-halved"></i></span>
                <span class="nav-label">Keamanan &amp; Pos Satpam</span>
            </div>
            <i class="fas fa-chevron-right nested-arrow-icon"></i>
        </button>
        <div class="dash-nav-nested-menu">
            {{-- 1. Dashboard Utama Pos Keamanan --}}
            <a href="{{ route('dashboard.tendik', ['bidang' => 'keamanan']) }}"
                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'keamanan' ? 'active' : '' }}">
                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard Keamanan</span>
            </a>

            {{-- 2. Buku Tamu Pos Depan --}}
            @if ($can('menu_keamanan_buku_tamu') || $can('menu_buku_tamu') || $can('menu_keamanan'))
                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.buku-tamu*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-open"></i></span>
                    <span class="nav-label">Buku Tamu</span>
                </a>
            @endif

            {{-- 3. Verifikasi e-Izin Gerbang Siswa --}}
            @if ($can('menu_keamanan_izin') || $can('menu_keamanan') || $can('menu_e_izin'))
                <a href="{{ route('dashboard.e-izin.index', ['tab' => 'security']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.e-izin*') || request()->routeIs('dashboard.keamanan.izin*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-door-open"></i></span>
                    <span class="nav-label">Verifikasi e-Izin</span>
                </a>
            @endif

            {{-- 4. Pencatatan Pelanggaran Siswa Pos Satpam --}}
            <a href="{{ route('dashboard.keamanan.pelanggaran.index') }}"
                class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.pelanggaran*') ? 'active' : '' }}">
                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-triangle-exclamation"></i></span>
                <span class="nav-label">Pelanggaran Siswa</span>
            </a>

            {{-- 5. Patroli Keliling & Insiden --}}
            @if ($can('menu_keamanan_patroli') || $can('menu_keamanan'))
                <a href="{{ route('dashboard.keamanan.patroli.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.patroli*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-person-walking-dashed-line-arrow-right"></i></span>
                    <span class="nav-label">Patroli &amp; Insiden</span>
                </a>
            @endif
        </div>
    </div>
@endif
