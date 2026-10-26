            {{-- 3. Submenu Per-Bidang: Kepegawaian --}}
            @if ($can('menu_kepegawaian'))
                <div class="dash-nav-nested-group {{ $isKepegawaianActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-nested-toggle">
                        <div class="dash-nav-nested-toggle-main">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card-alt"></i></span>
                            <span class="nav-label">Kepegawaian</span>
                        </div>
                        <i class="fas fa-chevron-right nested-arrow-icon"></i>
                    </button>
                    <div class="dash-nav-nested-menu">
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'kepegawaian']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kepegawaian' ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Kepegawaian</span>
                        </a>
                        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'pegawai']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && (request()->query('tab') === 'pegawai' || request()->query('tab') === 'guru' || (!request()->query('tab') && !request()->routeIs('dashboard.tendik'))) ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i class="fas fa-fw fa-users"></i></span>
                            <span class="nav-label">Pegawai</span>
                        </a>
                        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'berkas']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && (request()->query('tab') === 'berkas' || request()->query('tab') === 'tendik') ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i class="fas fa-fw fa-folder-open"></i></span>
                            <span class="nav-label">Berkas Pegawai</span>
                        </a>
                        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && request()->query('tab') === 'kgb' ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i
                                    class="fas fa-fw fa-business-time"></i></span>
                            <span class="nav-label">KGB Tracker</span>
                        </a>
                        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'cuti']) }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && request()->query('tab') === 'cuti' ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i
                                    class="fas fa-fw fa-plane-departure"></i></span>
                            <span class="nav-label">Cuti &amp; Izin</span>
                        </a>
                    </div>
                </div>
            @endif
