@php
    $user = session('user');
    $userName = is_array($user)
        ? $user['name'] ?? ($user['nama'] ?? 'Pengguna')
        : $user->name ?? ($user->nama ?? 'Pengguna');
    $role = is_array($user) ? $user['role'] ?? 'peserta_didik' : $user->role ?? 'peserta_didik';

    $can = fn(string $key) => \App\Models\RolePermission::canAccess($role, $key);

    // Master Data Submenus
    $hasMasterData = $can('menu_kompetensi_keahlian') || $can('menu_rombel') || $can('menu_pembelajaran');

    // Manajemen Data Submenus
    $hasPesertaDidik = $can('menu_peserta_didik_aktif') || $can('menu_peserta_didik_tidak_aktif');
    $hasGuru = $can('menu_guru_aktif') || $can('menu_guru_tidak_aktif');
    $hasTendik = $can('menu_tendik_aktif') || $can('menu_tendik_tidak_aktif');
    $hasManajemenData =
        $hasPesertaDidik || $hasGuru || $hasTendik || $can('menu_berkas_peserta_didik') || $can('menu_perubahan_data');

    // Section: Utama
    $hasUtama = $can('menu_dashboard') || $can('menu_dapodik') || $hasMasterData || $hasManajemenData;

    // Section: Layanan Digital
    $hasLayananDigital =
        $can('menu_pengumuman') ||
        $can('menu_rfid') ||
        $can('menu_e_izin') ||
        $can('menu_poin') ||
        $can('menu_agenda') ||
        $can('menu_buku_tamu') ||
        $can('menu_inventaris') ||
        $can('menu_kelulusan');

    // Section: Sistem
    $hasPengaturan =
        $can('menu_pengguna') || $can('menu_hak_akses') || $can('menu_pengaturan') || $can('menu_maintenance');
    $hasSistem = $hasPengaturan || $can('menu_update');

    // Section: Akademik Guru
    $hasAkademikGuru =
        $can('menu_presensi_mengajar') ||
        $can('menu_agenda_kbm') ||
        $can('menu_penilaian') ||
        $can('menu_presensi_peserta_didik');

    // Section: Portal Siswa
    $hasPortalSiswa =
        $can('menu_riwayat_rfid') ||
        $can('menu_jadwal_pelajaran') ||
        $can('menu_rapor') ||
        $can('menu_validasi_berkas');
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
        @if ($hasUtama)
            <span class="nav-section-label">Utama</span>

            @php
                $dashRoute = match ($role) {
                    'admin' => route('dashboard.admin'),
                    'guru' => route('dashboard.guru'),
                    'tendik' => route('dashboard.tendik'),
                    'peserta_didik' => route('dashboard.peserta-didik'),
                    default => route('dashboard.admin'),
                };
                $dashLabel = match ($role) {
                    'admin' => 'Dashboard Utama',
                    'guru' => 'Dashboard Guru',
                    'tendik' => 'Dashboard Tendik',
                    'peserta_didik' => 'Dashboard Siswa',
                    default => 'Dashboard',
                };
                $dashActive =
                    request()->routeIs('dashboard.' . $role) ||
                    ($role === 'peserta_didik' && request()->routeIs('dashboard.peserta-didik*'));
            @endphp

            @if ($can('menu_dashboard'))
                <a href="{{ $dashRoute }}" class="dash-nav-link {{ $dashActive ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i> <span>{{ $dashLabel }}</span>
                </a>
            @endif

            @if ($can('menu_dapodik'))
                <a href="{{ route('dashboard.dapodik') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.dapodik*') ? 'active' : '' }}">
                    <i class="fas fa-cloud-arrow-down"></i> <span>Tarik Data Dapodik</span>
                </a>
            @endif

            {{-- Master Data (Collapsible) - Posisi di atas Manajemen Data --}}
            @if ($hasMasterData)
                @php
                    $isMasterDataActive =
                        request()->routeIs('dashboard.kompetensi-keahlian*') ||
                        request()->routeIs('dashboard.rombel*') ||
                        request()->routeIs('dashboard.pembelajaran*');
                @endphp
                <div class="dash-nav-group {{ $isMasterDataActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-toggle">
                        <div class="dash-nav-toggle-main">
                            <i class="fas fa-cubes"></i>
                            <span>Master Data</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        @if ($can('menu_kompetensi_keahlian'))
                            <a href="{{ route('dashboard.kompetensi-keahlian.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.kompetensi-keahlian*') ? 'active' : '' }}">
                                <i class="fas fa-laptop-code"></i> <span>Keahlian</span>
                            </a>
                        @endif

                        {{-- Nested Submenu: Rombel --}}
                        @if ($can('menu_rombel'))
                            @php
                                $isRombelActive = request()->routeIs('dashboard.rombel*');
                            @endphp
                            <div class="dash-nav-nested-group {{ $isRombelActive ? 'open active-group' : '' }}">
                                <button type="button" class="dash-nav-nested-toggle">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-school"
                                            style="width: 14px; text-align: center; opacity: 0.8;"></i>
                                        <span>Rombel</span>
                                    </div>
                                    <i class="fas fa-chevron-right nested-arrow-icon"></i>
                                </button>
                                <div class="dash-nav-nested-menu">
                                    <a href="{{ route('dashboard.rombel.reguler') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.rombel.reguler') || (request()->routeIs('dashboard.rombel.index') && ($currentType ?? 'reguler') === 'reguler') ? 'active' : '' }}">
                                        <i class="fas fa-users-rectangle"></i> <span>Kelas (Reguler)</span>
                                    </a>
                                    <a href="{{ route('dashboard.rombel.matpel') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.rombel.matpel') || (request()->routeIs('dashboard.rombel.index') && ($currentType ?? '') === 'matpel') ? 'active' : '' }}">
                                        <i class="fas fa-book-open"></i> <span>Matpel Pilihan</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($can('menu_pembelajaran'))
                            <a href="{{ route('dashboard.pembelajaran.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.pembelajaran*') ? 'active' : '' }}">
                                <i class="fas fa-book-bookmark"></i> <span>Pembelajaran</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Manajemen Data (Collapsible) --}}
            @if ($hasManajemenData)
                @php
                    $isPesertaDidikActive =
                        request()->routeIs('dashboard.peserta-didik-aktif*') ||
                        request()->routeIs('dashboard.peserta-didik-tidak-aktif*');
                    $isGuruActive =
                        request()->routeIs('dashboard.guru-aktif*') ||
                        request()->routeIs('dashboard.guru-tidak-aktif*');
                    $isTendikActive =
                        request()->routeIs('dashboard.tendik-aktif*') ||
                        request()->routeIs('dashboard.tendik-tidak-aktif*');
                    $isManajemenDataActive =
                        $isPesertaDidikActive ||
                        $isGuruActive ||
                        $isTendikActive ||
                        request()->routeIs('dashboard.berkas-peserta-didik*') ||
                        request()->routeIs('dashboard.perubahan-data*');
                @endphp
                <div class="dash-nav-group {{ $isManajemenDataActive ? 'open active-group' : '' }}">
                    <button type="button" class="dash-nav-toggle">
                        <div class="dash-nav-toggle-main">
                            <i class="fas fa-folder-tree"></i>
                            <span>Manajemen Data</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        {{-- Nested Submenu: Peserta Didik --}}
                        @if ($hasPesertaDidik)
                            <div class="dash-nav-nested-group {{ $isPesertaDidikActive ? 'open active-group' : '' }}">
                                <button type="button" class="dash-nav-nested-toggle">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-graduate"
                                            style="width: 14px; text-align: center; opacity: 0.8;"></i>
                                        <span>Peserta Didik</span>
                                    </div>
                                    <i class="fas fa-chevron-right nested-arrow-icon"></i>
                                </button>
                                <div class="dash-nav-nested-menu">
                                    @if ($can('menu_peserta_didik_aktif'))
                                        <a href="{{ route('dashboard.peserta-didik-aktif.index') }}"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.peserta-didik-aktif*') ? 'active' : '' }}">
                                            <i class="fas fa-circle-check"></i> <span>Aktif</span>
                                        </a>
                                    @endif

                                    @if ($can('menu_peserta_didik_tidak_aktif'))
                                        <a href="#"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.peserta-didik-tidak-aktif*') ? 'active' : '' }}">
                                            <i class="fas fa-circle-xmark"></i> <span>Tidak Aktif</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Nested Submenu: Guru --}}
                        @if ($hasGuru)
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
                                    @if ($can('menu_guru_aktif'))
                                        <a href="{{ route('dashboard.guru-aktif.index') }}"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-aktif*') ? 'active' : '' }}">
                                            <i class="fas fa-circle-check"></i> <span>Aktif</span>
                                        </a>
                                    @endif

                                    @if ($can('menu_guru_tidak_aktif'))
                                        <a href="#"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-tidak-aktif*') ? 'active' : '' }}">
                                            <i class="fas fa-circle-xmark"></i> <span>Tidak Aktif</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Nested Submenu: Tendik --}}
                        @if ($hasTendik)
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
                                    @if ($can('menu_tendik_aktif'))
                                        <a href="{{ route('dashboard.tendik-aktif.index') }}"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                                            <i class="fas fa-circle-check"></i> <span>Aktif</span>
                                        </a>
                                    @endif

                                    @if ($can('menu_tendik_tidak_aktif'))
                                        <a href="#"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-tidak-aktif*') ? 'active' : '' }}">
                                            <i class="fas fa-circle-xmark"></i> <span>Tidak Aktif</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($can('menu_berkas_peserta_didik'))
                            <a href="#" class="dash-nav-sublink">
                                <i class="fas fa-folder-open"></i> <span>Berkas Peserta Didik</span>
                            </a>
                        @endif

                        @if ($can('menu_perubahan_data'))
                            <a href="#" class="dash-nav-sublink">
                                <i class="fas fa-user-pen"></i> <span>Perubahan Data</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- Layanan Digital (Collapsible) --}}
        @if ($hasLayananDigital)
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
                    @if ($can('menu_pengumuman'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-bullhorn"></i> <span>Pengumuman</span>
                        </a>
                    @endif

                    @if ($can('menu_rfid'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-id-card"></i> <span>RFID &amp; Presensi</span>
                        </a>
                    @endif

                    @if ($can('menu_e_izin'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-file-signature"></i> <span>E-Izin</span>
                        </a>
                    @endif

                    @if ($can('menu_poin'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-star-half-stroke"></i> <span>Poin Pelanggaran</span>
                        </a>
                    @endif

                    @if ($can('menu_agenda'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-calendar-days"></i> <span>Agenda Sekolah</span>
                        </a>
                    @endif

                    @if ($can('menu_buku_tamu'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-address-book"></i> <span>Buku Tamu</span>
                        </a>
                    @endif

                    @if ($can('menu_inventaris'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-boxes-stacked"></i> <span>Inventaris</span>
                        </a>
                    @endif

                    @if ($can('menu_kelulusan'))
                        <a href="#" class="dash-nav-sublink">
                            <i class="fas fa-graduation-cap"></i> <span>Kelulusan</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Konfigurasi & Pengaturan Sistem (Collapsible) --}}
        @if ($hasSistem)
            <span class="nav-section-label">Sistem</span>

            @if ($hasPengaturan)
                @php
                    $isSistemGroupActive =
                        request()->routeIs('dashboard.pengguna*') ||
                        request()->routeIs('dashboard.hak-akses*') ||
                        request()->routeIs('dashboard.identitas-sekolah*') ||
                        request()->routeIs('dashboard.maintenance*');
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
                        @if ($can('menu_pengguna'))
                            <a href="{{ route('dashboard.pengguna.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.pengguna*') ? 'active' : '' }}">
                                <i class="fas fa-users-gear"></i> <span>Pengguna</span>
                            </a>
                        @endif

                        @if ($can('menu_hak_akses'))
                            <a href="{{ route('dashboard.hak-akses.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.hak-akses*') ? 'active' : '' }}">
                                <i class="fas fa-shield-halved"></i> <span>Hak Akses</span>
                            </a>
                        @endif

                        @if ($can('menu_pengaturan'))
                            <a href="{{ route('dashboard.identitas-sekolah.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.identitas-sekolah*') ? 'active' : '' }}">
                                <i class="fas fa-school"></i> <span>Identitas Sekolah</span>
                            </a>
                        @endif

                        @if ($can('menu_maintenance'))
                            <a href="{{ route('dashboard.maintenance.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.maintenance*') ? 'active' : '' }}">
                                <i class="fas fa-server"></i> <span>Arsip & Maintenance</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($can('menu_update'))
                <a href="{{ route('dashboard.update') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.update*') ? 'active' : '' }}">
                    <i class="fas fa-arrows-rotate"></i> <span>Update Sistem</span>
                </a>
            @endif
        @endif

        {{-- Layanan Akademik & Guru (Khusus Guru / peran yang diizinkan) --}}
        @if ($hasAkademikGuru)
            <span class="nav-section-label">Akademik Guru</span>

            @if ($can('menu_presensi_mengajar'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-calendar-check"></i> <span>Presensi Mengajar</span>
                </a>
            @endif

            @if ($can('menu_agenda_kbm'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-book-open-reader"></i> <span>Jurnal &amp; Agenda KBM</span>
                </a>
            @endif

            @if ($can('menu_penilaian'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-graduation-cap"></i> <span>Penilaian Siswa</span>
                </a>
            @endif

            @if ($can('menu_presensi_peserta_didik'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-users-viewfinder"></i> <span>Presensi Kelas</span>
                </a>
            @endif
        @endif

        {{-- Portal Peserta Didik (Khusus Peserta Didik / peran yang diizinkan) --}}
        @if ($hasPortalSiswa)
            <span class="nav-section-label">Portal Siswa</span>

            @if ($can('menu_riwayat_rfid'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-id-card-clip"></i> <span>Riwayat Presensi RFID</span>
                </a>
            @endif

            @if ($can('menu_jadwal_pelajaran'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-calendar-days"></i> <span>Jadwal Pelajaran</span>
                </a>
            @endif

            @if ($can('menu_rapor'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-file-lines"></i> <span>Transkrip &amp; Rapor</span>
                </a>
            @endif

            @if ($can('menu_validasi_berkas'))
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
