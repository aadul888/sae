            {{-- 1. Submenu Per-Bidang: Persuratan --}}
            @if (
                $can('menu_persuratan') ||
                    $can('menu_surat_masuk') ||
                    $can('menu_surat_keluar') ||
                    $can('menu_pengaturan_persuratan'))
                <div class="dash-nav-nested-group {{ $isPersuratanActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-nested-toggle">
                        <div class="dash-nav-nested-toggle-main">
                            <span class="nav-icon sub-icon"><i
                                    class="fas fa-fw fa-envelope-open-text"></i></span>
                            <span class="nav-label">Persuratan</span>
                        </div>
                        <i class="fas fa-chevron-right nested-arrow-icon"></i>
                    </button>
                    <div class="dash-nav-nested-menu">
                        @if ($can('menu_persuratan'))
                            <a href="{{ route('dashboard.tendik', ['bidang' => 'persuratan']) }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'persuratan' ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-gauge-high"></i></span>
                                <span class="nav-label">Dashboard Persuratan</span>
                            </a>
                        @endif
                        @if ($can('menu_surat_masuk') || $can('menu_persuratan'))
                            <a href="{{ route('dashboard.persuratan.masuk.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.masuk*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-inbox"></i></span>
                                <span class="nav-label">Surat Masuk</span>
                            </a>
                        @endif
                        @if ($can('menu_surat_keluar') || $can('menu_persuratan'))
                            <a href="{{ route('dashboard.persuratan.keluar.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') && request('tab') !== 'ekspedisi' && request('tab') !== 'template' ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-paper-plane"></i></span>
                                <span class="nav-label">Surat Keluar</span>
                            </a>
                            <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']) }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') && request('tab') === 'ekspedisi' ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-truck-fast"></i></span>
                                <span class="nav-label">Buku Ekspedisi</span>
                            </a>
                            <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'template']) }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') && (request('tab') === 'template' || request('tab') === 'autocrat') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-wand-magic-sparkles"></i></span>
                                <span class="nav-label">Autocrat</span>
                            </a>
                        @endif
                        @if ($can('menu_pengaturan_persuratan') || $can('menu_persuratan'))
                            <a href="{{ route('dashboard.persuratan.pengaturan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.pengaturan*') ? 'active' : '' }}">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-sliders"></i></span>
                                <span class="nav-label">Pengaturan &amp; Arsip HDD</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
