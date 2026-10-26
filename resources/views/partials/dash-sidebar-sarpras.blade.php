            {{-- 4. Submenu Per-Bidang: Sarana & Prasarana --}}
            @if ($can('menu_sarpras'))
                <div class="dash-nav-nested-group {{ $isSarprasActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-nested-toggle">
                        <div class="dash-nav-nested-toggle-main">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-building"></i></span>
                            <span class="nav-label">Sarpras &amp; Aset</span>
                        </div>
                        <i class="fas fa-chevron-right nested-arrow-icon"></i>
                    </button>
                    <div class="dash-nav-nested-menu">
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'sarpras']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'sarpras' ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Sarpras</span>
                        </a>
                        @if ($can('menu_sarpras_ruang') || $can('menu_sarpras'))
                            <a href="{{ route('dashboard.sarpras.ruang.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.ruang*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-door-open"></i></span>
                                <span class="nav-label">Ruang &amp; Gedung</span>
                            </a>
                        @endif
                        @if ($can('menu_inventaris') || $can('menu_sarpras'))
                            <a href="{{ route('dashboard.sarpras.aset.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.aset*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-boxes-stacked"></i></span>
                                <span class="nav-label">Inventaris &amp; Aset</span>
                            </a>
                        @endif
                        @if ($can('menu_sarpras_peminjaman') || $can('menu_sarpras'))
                            <a href="{{ route('dashboard.sarpras.peminjaman.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.peminjaman*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-hand-holding"></i></span>
                                <span class="nav-label">Peminjaman Sarpras</span>
                            </a>
                        @endif
                        @if ($can('menu_teknisi'))
                            <a href="{{ route('dashboard.teknisi.work-order') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.work-order*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-screwdriver-wrench"></i></span>
                                <span class="nav-label">Perbaikan &amp; Work Order</span>
                            </a>
                        @endif
                        @if ($can('menu_penjaga'))
                            <a href="{{ route('dashboard.penjaga.kebersihan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.penjaga.kebersihan*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-broom"></i></span>
                                <span class="nav-label">Kebersihan Fasilitas</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
