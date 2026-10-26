            {{-- 6. Submenu Per-Bidang: Perpustakaan --}}
            @if ($can('menu_perpustakaan'))
                <div class="dash-nav-nested-group {{ $isPerpustakaanActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-nested-toggle">
                        <div class="dash-nav-nested-toggle-main">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-open"></i></span>
                            <span class="nav-label">Perpustakaan</span>
                        </div>
                        <i class="fas fa-chevron-right nested-arrow-icon"></i>
                    </button>
                    <div class="dash-nav-nested-menu">
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'perpustakaan']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'perpustakaan' ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Perpustakaan</span>
                        </a>
                        @if ($can('menu_perpustakaan_koleksi') || $can('menu_perpustakaan'))
                            <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.perpustakaan.koleksi*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book"></i></span>
                                <span class="nav-label">Katalog &amp; Koleksi Buku</span>
                            </a>
                        @endif
                        @if ($can('menu_perpustakaan_sirkulasi') || $can('menu_perpustakaan'))
                            <a href="{{ route('dashboard.perpustakaan.sirkulasi') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.perpustakaan.sirkulasi*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-exchange-alt"></i></span>
                                <span class="nav-label">Sirkulasi &amp; Denda</span>
                            </a>
                        @endif
                        @if ($can('menu_perpustakaan_kunjungan') || $can('menu_perpustakaan'))
                            <a href="{{ route('dashboard.perpustakaan.kunjungan') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.perpustakaan.kunjungan*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-user-check"></i></span>
                                <span class="nav-label">Buku Kunjungan Harian</span>
                            </a>
                        @endif
                    </div>
                    <span class="nav-label">Buku Kunjungan Harian</span>
                    </a>
                </div>
        </div>
@endif
