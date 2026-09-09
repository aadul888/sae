@php
    $user = session('user');
    $userName = is_array($user)
        ? $user['name'] ?? ($user['nama'] ?? 'Pengguna')
        : $user->name ?? ($user->nama ?? 'Pengguna');
    $role = is_array($user) ? $user['role'] ?? 'siswa' : $user->role ?? 'siswa';
@endphp

<aside class="dash-sidebar" id="dashSidebar">
    <!-- Brand -->
    <div class="dash-sidebar-header">
        <a href="{{ route('dashboard.' . $role) }}" class="brand"
            style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img id="dashLogo" src="{{ asset('img/logo-dark.png') }}" data-dark="{{ asset('img/logo-dark.png') }}"
                data-light="{{ asset('img/logo-light.png') }}" alt="SAE Logo"
                style="height: 36px; max-width: 140px; object-fit: contain;"
                onerror="this.onerror=null; this.src='/img/logo-dark.png';">
        </a>
        <span class="badge badge-primary"
            style="font-size: 0.68rem; padding: 2px 6px; font-family: monospace;">v{{ $appVersion ?? '1.0.1' }}</span>
    </div>

    <!-- User Profile Badge -->
    <div class="dash-sidebar-user">
        <div class="dash-user-avatar">
            @if ($role === 'admin')
                <i class="fas fa-user-shield"></i>
            @elseif($role === 'guru')
                <i class="fas fa-chalkboard-user"></i>
            @elseif($role === 'tendik')
                <i class="fas fa-id-badge"></i>
            @else
                <i class="fas fa-user-graduate"></i>
            @endif
        </div>
        <div class="dash-user-info">
            <div class="dash-user-name" title="{{ $userName }}">{{ $userName }}</div>
            <span class="dash-user-role role-{{ $role }}">{{ $role }}</span>
        </div>
    </div>

    <!-- Navigation List -->
    <div class="dash-sidebar-nav">
        <span class="nav-section-label">Utama</span>

        @if ($role === 'admin')
            @if (\App\Models\RolePermission::canAccess($role, 'menu_dashboard'))
                <a href="{{ route('dashboard.admin') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i> <span>Dashboard Utama</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_dapodik'))
                <a href="{{ route('dashboard.dapodik') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.dapodik*') ? 'active' : '' }}">
                    <i class="fas fa-cloud-arrow-down"></i> <span>Tarik Data Dapodik</span>
                </a>
            @endif

            {{-- Master Data (Collapsible) - Posisi di atas Manajemen Data --}}
            @php
                $isMasterDataActive =
                    request()->routeIs('dashboard.kompetensi-keahlian*') ||
                    request()->routeIs('dashboard.rombel*') ||
                    request()->routeIs('dashboard.pembelajaran*');
            @endphp
            @if (
                \App\Models\RolePermission::canAccess($role, 'menu_kompetensi_keahlian') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_rombel') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_pembelajaran'))
                <div class="dash-nav-group {{ $isMasterDataActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-toggle">
                        <div class="dash-nav-toggle-main">
                            <i class="fas fa-cubes"></i>
                            <span>Master Data</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        @if (\App\Models\RolePermission::canAccess($role, 'menu_kompetensi_keahlian'))
                            <a href="{{ route('dashboard.kompetensi-keahlian.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.kompetensi-keahlian*') ? 'active' : '' }}">
                                <i class="fas fa-laptop-code"></i> <span>Keahlian</span>
                            </a>
                        @endif

                        @if (\App\Models\RolePermission::canAccess($role, 'menu_rombel'))
                            <a href="{{ route('dashboard.rombel.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.rombel*') ? 'active' : '' }}">
                                <i class="fas fa-school"></i> <span>Rombel</span>
                            </a>
                        @endif

                        @if (\App\Models\RolePermission::canAccess($role, 'menu_pembelajaran'))
                            <a href="#" class="dash-nav-sublink">
                                <i class="fas fa-book-bookmark"></i> <span>Pembelajaran</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Manajemen Data (Collapsible) --}}
            @php
                $isSiswaActive =
                    request()->routeIs('dashboard.siswa-aktif*') || request()->routeIs('dashboard.siswa-tidak-aktif*');
                $isGuruActive =
                    request()->routeIs('dashboard.guru-aktif*') || request()->routeIs('dashboard.guru-tidak-aktif*');
                $isTendikActive =
                    request()->routeIs('dashboard.tendik-aktif*') ||
                    request()->routeIs('dashboard.tendik-tidak-aktif*');
                $isManajemenDataActive =
                    $isSiswaActive ||
                    $isGuruActive ||
                    $isTendikActive ||
                    request()->routeIs('dashboard.berkas-siswa*') ||
                    request()->routeIs('dashboard.perubahan-data*');
            @endphp
            @if (
                \App\Models\RolePermission::canAccess($role, 'menu_siswa_aktif') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_guru_aktif') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_tendik_aktif') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_berkas_siswa') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_perubahan_data') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_siswa_tidak_aktif') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_guru_tidak_aktif') ||
                    \App\Models\RolePermission::canAccess($role, 'menu_tendik_tidak_aktif'))
                <div class="dash-nav-group {{ $isManajemenDataActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-toggle">
                        <div class="dash-nav-toggle-main">
                            <i class="fas fa-folder-tree"></i>
                            <span>Manajemen Data</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        {{-- Nested Submenu: Siswa --}}
                        <div class="dash-nav-nested-group {{ $isSiswaActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-graduate"
                                        style="width: 14px; text-align: center; opacity: 0.8;"></i>
                                    <span>Siswa</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                @if (\App\Models\RolePermission::canAccess($role, 'menu_siswa_aktif'))
                                    <a href="{{ route('dashboard.siswa-aktif.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.siswa-aktif*') ? 'active' : '' }}">
                                        <i class="fas fa-circle-check"></i> <span>Aktif</span>
                                    </a>
                                @endif

                                @if (\App\Models\RolePermission::canAccess($role, 'menu_siswa_tidak_aktif'))
                                    <a href="#"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.siswa-tidak-aktif*') ? 'active' : '' }}">
                                        <i class="fas fa-circle-xmark"></i> <span>Tidak Aktif</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Nested Submenu: Guru --}}
                        <div class="dash-nav-nested-group {{ $isGuruActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-chalkboard-user"
                                        style="width: 14px; text-align: center; opacity: 0.8;"></i>
                                    <span>Guru</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                @if (\App\Models\RolePermission::canAccess($role, 'menu_guru_aktif'))
                                    <a href="{{ route('dashboard.guru-aktif.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-aktif*') ? 'active' : '' }}">
                                        <i class="fas fa-circle-check"></i> <span>Aktif</span>
                                    </a>
                                @endif

                                @if (\App\Models\RolePermission::canAccess($role, 'menu_guru_tidak_aktif'))
                                    <a href="#"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-tidak-aktif*') ? 'active' : '' }}">
                                        <i class="fas fa-circle-xmark"></i> <span>Tidak Aktif</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Nested Submenu: Tendik --}}
                        <div class="dash-nav-nested-group {{ $isTendikActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-id-badge"
                                        style="width: 14px; text-align: center; opacity: 0.8;"></i>
                                    <span>Tendik</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                @if (\App\Models\RolePermission::canAccess($role, 'menu_tendik_aktif'))
                                    <a href="{{ route('dashboard.tendik-aktif.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                                        <i class="fas fa-circle-check"></i> <span>Aktif</span>
                                    </a>
                                @endif

                                @if (\App\Models\RolePermission::canAccess($role, 'menu_tendik_tidak_aktif'))
                                    <a href="#"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-tidak-aktif*') ? 'active' : '' }}">
                                        <i class="fas fa-circle-xmark"></i> <span>Tidak Aktif</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if (\App\Models\RolePermission::canAccess($role, 'menu_berkas_siswa'))
                            <a href="#" class="dash-nav-sublink">
                                <i class="fas fa-folder-open"></i> <span>Berkas Siswa</span>
                            </a>
                        @endif

                        @if (\App\Models\RolePermission::canAccess($role, 'menu_perubahan_data'))
                            <a href="#" class="dash-nav-sublink">
                                <i class="fas fa-user-pen"></i> <span>Perubahan Data</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Layanan Digital (Collapsible) --}}
            <span class="nav-section-label">Layanan Digital</span>

            @php
                $isLayananDigitalActive =
                    request()->routeIs('dashboard.pengumuman*') ||
                    request()->routeIs('dashboard.rfid*') ||
                    request()->routeIs('dashboard.e-izin*') ||
                    request()->routeIs('dashboard.poin*') ||
                    request()->routeIs('dashboard.agenda*') ||
                    request()->routeIs('dashboard.buku-tamu*') ||
                    request()->routeIs('dashboard.inventaris*') ||
                    request()->routeIs('dashboard.kelulusan*');
            @endphp
            <div class="dash-nav-group {{ $isLayananDigitalActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <i class="fas fa-globe"></i>
                        <span>Layanan Digital</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if (\App\Models\RolePermission::canAccess($role, 'menu_pengumuman'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-bullhorn"></i> <span>Pengumuman</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_rfid'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-id-card"></i> <span>RFID &amp; Presensi</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_e_izin'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-file-signature"></i> <span>E-Izin</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_poin'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-star-half-stroke"></i> <span>Poin Pelanggaran</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_agenda'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-calendar-days"></i> <span>Agenda Sekolah</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_buku_tamu'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-address-book"></i> <span>Buku Tamu</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_inventaris'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-boxes-stacked"></i> <span>Inventaris</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_kelulusan'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-graduation-cap"></i> <span>Kelulusan</span>
                        </a>
                    @endif
                </div>
            </div>

            <span class="nav-section-label">Sistem</span>

            {{-- Konfigurasi & Pengaturan Sistem (Collapsible) --}}
            @php
                $isSistemGroupActive =
                    request()->routeIs('dashboard.pengguna*') || request()->routeIs('dashboard.hak-akses*');
            @endphp
            <div class="dash-nav-group {{ $isSistemGroupActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <i class="fas fa-sliders"></i>
                        <span>Pengaturan</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if (\App\Models\RolePermission::canAccess($role, 'menu_pengguna'))
                        <a href="{{ route('dashboard.pengguna.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.pengguna*') ? 'active' : '' }}">
                            <i class="fas fa-users-gear"></i> <span>Pengguna</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_hak_akses'))
                        <a href="{{ route('dashboard.hak-akses.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.hak-akses*') ? 'active' : '' }}">
                            <i class="fas fa-shield-halved"></i> <span>Hak Akses</span>
                        </a>
                    @endif

                    @if (\App\Models\RolePermission::canAccess($role, 'menu_pengaturan'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-gear"></i> <span>Identitas Sekolah</span>
                        </a>
                    @endif
                </div>
            </div>

            @if (\App\Models\RolePermission::canAccess($role, 'menu_update'))
                <a href="{{ route('dashboard.update') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.update*') ? 'active' : '' }}">
                    <i class="fas fa-arrows-rotate"></i> <span>Update Sistem</span>
                </a>
            @endif
        @elseif($role === 'guru')
            @if (\App\Models\RolePermission::canAccess($role, 'menu_dashboard'))
                <a href="{{ route('dashboard.guru') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.guru') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i> <span>Dashboard Guru</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_presensi_mengajar'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-calendar-check"></i> <span>Presensi Mengajar</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_agenda_kbm'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-book-open-reader"></i> <span>Jurnal &amp; Agenda KBM</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_penilaian'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-graduation-cap"></i> <span>Penilaian Siswa</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_presensi_siswa'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-users-viewfinder"></i> <span>Presensi Kelas Siswa</span>
                </a>
            @endif
        @elseif($role === 'tendik')
            @if (\App\Models\RolePermission::canAccess($role, 'menu_dashboard'))
                <a href="{{ route('dashboard.tendik') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.tendik') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i> <span>Dashboard Tendik</span>
                </a>
            @endif

            <a href="{{ route('dashboard.tendik-aktif.index') }}"
                class="dash-nav-link {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                <i class="fas fa-id-badge"></i> <span>Direktori Tendik</span>
            </a>

            <a href="{{ route('dashboard.guru-aktif.index') }}"
                class="dash-nav-link {{ request()->routeIs('dashboard.guru-aktif*') ? 'active' : '' }}">
                <i class="fas fa-chalkboard-user"></i> <span>Direktori Guru</span>
            </a>

            <a href="{{ route('dashboard.siswa-aktif.index') }}"
                class="dash-nav-link {{ request()->routeIs('dashboard.siswa-aktif*') ? 'active' : '' }}">
                <i class="fas fa-user-graduate"></i> <span>Direktori Siswa</span>
            </a>

            <a href="#" class="dash-nav-link">
                <i class="fas fa-file-invoice"></i> <span>Persuratan &amp; Dokumen</span>
            </a>

            <a href="#" class="dash-nav-link">
                <i class="fas fa-address-book"></i> <span>Buku Tamu</span>
            </a>
        @elseif($role === 'siswa')
            @if (\App\Models\RolePermission::canAccess($role, 'menu_dashboard'))
                <a href="{{ route('dashboard.siswa') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.siswa') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i> <span>Dashboard Siswa</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_riwayat_rfid'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-id-card-clip"></i> <span>Riwayat Presensi RFID</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_jadwal_pelajaran'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-calendar-days"></i> <span>Jadwal Pelajaran</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_rapor'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-file-lines"></i> <span>Transkrip &amp; Rapor</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_validasi_berkas'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-folder-open"></i> <span>Validasi Berkas &amp; Ijazah</span>
                </a>
            @endif
        @endif
    </div>

    <!-- Sidebar Footer (Quick Logout & Home) -->
    <div class="dash-sidebar-footer">
        <a href="{{ route('home') }}" class="dash-nav-link" style="margin-bottom: 6px;">
            <i class="fas fa-globe"></i> <span>Lihat Web Publik</span>
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="dash-nav-link"
                style="width: 100%; border: none; background: rgba(239, 68, 68, 0.1); color: #ef4444; cursor: pointer; text-align: left;">
                <i class="fas fa-right-from-bracket"></i> <span>Keluar Sistem</span>
            </button>
        </form>
    </div>
</aside>
<div class="dash-backdrop" id="dashBackdrop"></div>
