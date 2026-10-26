            {{-- 2. Submenu Per-Bidang: Kesiswaan --}}
            @if (
                $can('menu_kesiswaan') ||
                    $can('menu_kesiswaan_peserta_didik') ||
                    $can('menu_kesiswaan_administrasi') ||
                    $can('menu_buku_induk') ||
                    $can('menu_kesiswaan_kedisiplinan') ||
                    $can('menu_kesiswaan_kegiatan') ||
                    $can('menu_kesiswaan_prestasi'))
                <div class="dash-nav-nested-group {{ $isKesiswaanActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-nested-toggle">
                        <div class="dash-nav-nested-toggle-main">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
                            <span class="nav-label">Kesiswaan</span>
                        </div>
                        <i class="fas fa-chevron-right nested-arrow-icon"></i>
                    </button>
                    <div class="dash-nav-nested-menu">
                        @if ($can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.tendik', ['bidang' => 'kesiswaan']) }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kesiswaan' ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-gauge-high"></i></span>
                                <span class="nav-label">Dashboard Kesiswaan</span>
                            </a>
                        @endif
                        @if ($can('menu_kesiswaan_peserta_didik') || $can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.kesiswaan.peserta-didik.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.peserta-didik.*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-users"></i></span>
                                <span class="nav-label">Peserta Didik</span>
                            </a>
                        @endif
                        @if ($can('menu_buku_induk') || $can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.kesiswaan.buku-induk.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.buku-induk.*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-address-book"></i></span>
                                <span class="nav-label">Buku Induk</span>
                            </a>
                        @endif
                        @if ($can('menu_kesiswaan_administrasi') || $can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.kesiswaan.administrasi.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.administrasi.*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-book-bookmark"></i></span>
                                <span class="nav-label">Administrasi</span>
                            </a>
                        @endif
                        @if ($can('menu_kesiswaan_kedisiplinan') || $can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.kedisiplinan.*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-shield-halved"></i></span>
                                <span class="nav-label">Kedisiplinan</span>
                            </a>
                        @endif
                        @if ($can('menu_kesiswaan_kegiatan') || $can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.kesiswaan.kegiatan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.kegiatan.*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-people-group"></i></span>
                                <span class="nav-label">Kegiatan Siswa</span>
                            </a>
                        @endif
                        @if ($can('menu_kesiswaan_prestasi') || $can('menu_kesiswaan'))
                            <a href="{{ route('dashboard.kesiswaan.prestasi.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.prestasi.*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-trophy"></i></span>
                                <span class="nav-label">Prestasi</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
