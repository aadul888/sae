@php
    $user = session('user');
    $userName = is_array($user)
        ? $user['name'] ?? ($user['nama'] ?? 'Pengguna')
        : $user->name ?? ($user->nama ?? 'Pengguna');
    $role = is_array($user) ? $user['role'] ?? 'peserta_didik' : $user->role ?? 'peserta_didik';

    // Evaluasi gabungan: hak role dasar + tugas tambahan aktif pengguna
    $can = fn(string $key) => \App\Models\RolePermission::canAccess($user ?: $role, $key);

    // Ambil daftar tugas tambahan aktif pengguna saat ini untuk badge/info sidebar
    $userDuties = [];
    if (in_array($role, ['guru', 'tendik'])) {
        $uId = is_array($user)
            ? $user['id'] ?? ($user['pengguna_id'] ?? null)
            : $user->id ?? ($user->pengguna_id ?? null);
        $pId = is_array($user) ? $user['ptk_id'] ?? null : $user->ptk_id ?? null;
        if ($uId || $pId) {
            $userDuties = \DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($uId, $pId) {
                    if ($uId) {
                        $q->where('ptt.user_id', $uId);
                    }
                    if ($pId) {
                        $q->orWhere('ptt.ptk_id', $pId);
                    }
                })
                ->select('rtt.nama', 'rtt.bidang', 'rtt.ekuivalensi_jam', 'ptt.rombel_id')
                ->get();
        }
    }

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

    // Section: Portal Peserta Didik
    $hasPortalPesertaDidik =
        $can('menu_riwayat_rfid') ||
        $can('menu_jadwal_pelajaran') ||
        $can('menu_rapor') ||
        $can('menu_validasi_berkas');

    // Proteksi ketat tingkat sistem: Peserta Didik hanya dapat melihat Dashboard & Portal Peserta Didik serta Layanan Siswa
    if ($role === 'peserta_didik') {
        $hasMasterData = false;
        $hasPesertaDidik = false;
        $hasGuru = false;
        $hasTendik = false;
        $hasManajemenData = false;
        $hasSistem = false;
        $hasPengaturan = false;
        $hasAkademikGuru = false;
        $hasUtama = $can('menu_dashboard');
    }
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
            <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-top: 2px;">
                <span class="dash-user-role role-{{ $role }}">{{ $role }}</span>
                @foreach ($userDuties as $duty)
                    <span class="badge badge-accent"
                        style="font-size: 0.62rem; padding: 1px 5px; border-radius: 4px; text-transform: none; max-width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                        title="{{ $duty->nama }}{{ $duty->ekuivalensi_jam ? ' (' . $duty->ekuivalensi_jam . ' Jam)' : '' }}">
                        {{ $duty->nama }}
                    </span>
                @endforeach
            </div>
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
                    'peserta_didik' => 'Dashboard Peserta Didik',
                    default => 'Dashboard',
                };
                $dashActive =
                    request()->routeIs('dashboard.' . $role) ||
                    ($role === 'peserta_didik' && (request()->routeIs('dashboard.peserta-didik') || request()->routeIs('dashboard.peserta_didik')));
            @endphp

            @if ($can('menu_dashboard'))
                <a href="{{ $dashRoute }}" class="dash-nav-link {{ $dashActive ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">{{ $dashLabel }}</span>
                </a>
            @endif

            @if ($can('menu_dapodik'))
                <a href="{{ route('dashboard.dapodik') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.dapodik*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-fw fa-cloud-arrow-down"></i></span>
                    <span class="nav-label">Tarik Data Dapodik</span>
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
                            <span class="nav-icon"><i class="fas fa-fw fa-cubes"></i></span>
                            <span class="nav-label">Master Data</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        @if ($can('menu_kompetensi_keahlian'))
                            <a href="{{ route('dashboard.kompetensi-keahlian.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.kompetensi-keahlian*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-laptop-code"></i></span>
                                <span class="nav-label">Keahlian</span>
                            </a>
                        @endif

                        {{-- Nested Submenu: Rombel --}}
                        @if ($can('menu_rombel'))
                            @php
                                $isRombelActive = request()->routeIs('dashboard.rombel*');
                            @endphp
                            <div class="dash-nav-nested-group {{ $isRombelActive ? 'open active-group' : '' }}">
                                <button type="button" class="dash-nav-nested-toggle">
                                    <div class="dash-nav-nested-toggle-main">
                                        <span class="nav-icon sub-icon"><i class="fas fa-fw fa-school"></i></span>
                                        <span class="nav-label">Rombel</span>
                                    </div>
                                    <i class="fas fa-chevron-right nested-arrow-icon"></i>
                                </button>
                                <div class="dash-nav-nested-menu">
                                    <a href="{{ route('dashboard.rombel.reguler') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.rombel.reguler') || (request()->routeIs('dashboard.rombel.index') && ($currentType ?? 'reguler') === 'reguler') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i
                                                class="fas fa-fw fa-users-rectangle"></i></span>
                                        <span class="nav-label">Kelas (Reguler)</span>
                                    </a>
                                    <a href="{{ route('dashboard.rombel.matpel') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.rombel.matpel') || (request()->routeIs('dashboard.rombel.index') && ($currentType ?? '') === 'matpel') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-open"></i></span>
                                        <span class="nav-label">Matpel Pilihan</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($can('menu_pembelajaran'))
                            <a href="{{ route('dashboard.pembelajaran.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.pembelajaran*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-bookmark"></i></span>
                                <span class="nav-label">Pembelajaran</span>
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
                            <span class="nav-icon"><i class="fas fa-fw fa-folder-tree"></i></span>
                            <span class="nav-label">Manajemen Data</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        {{-- Nested Submenu: Peserta Didik --}}
                        @if ($hasPesertaDidik)
                            <div class="dash-nav-nested-group {{ $isPesertaDidikActive ? 'open active-group' : '' }}">
                                <button type="button" class="dash-nav-nested-toggle">
                                    <div class="dash-nav-nested-toggle-main">
                                        <span class="nav-icon sub-icon"><i
                                                class="fas fa-fw fa-user-graduate"></i></span>
                                        <span class="nav-label">Peserta Didik</span>
                                    </div>
                                    <i class="fas fa-chevron-right nested-arrow-icon"></i>
                                </button>
                                <div class="dash-nav-nested-menu">
                                    @if ($can('menu_peserta_didik_aktif'))
                                        <a href="{{ route('dashboard.peserta-didik-aktif.index') }}"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.peserta-didik-aktif*') ? 'active' : '' }}">
                                            <span class="nav-icon nested-icon"><i
                                                    class="fas fa-fw fa-circle-check"></i></span>
                                            <span class="nav-label">Aktif</span>
                                        </a>
                                    @endif

                                    @if ($can('menu_peserta_didik_tidak_aktif'))
                                        <a href="#"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.peserta-didik-tidak-aktif*') ? 'active' : '' }}">
                                            <span class="nav-icon nested-icon"><i
                                                    class="fas fa-fw fa-circle-xmark"></i></span>
                                            <span class="nav-label">Tidak Aktif</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Nested Submenu: Guru --}}
                        @if ($hasGuru)
                            <div class="dash-nav-nested-group {{ $isGuruActive ? 'open active-group' : '' }}">
                                <button type="button" class="dash-nav-nested-toggle">
                                    <div class="dash-nav-nested-toggle-main">
                                        <span class="nav-icon sub-icon"><i
                                                class="fas fa-fw fa-chalkboard-user"></i></span>
                                        <span class="nav-label">Guru</span>
                                    </div>
                                    <i class="fas fa-chevron-right nested-arrow-icon"></i>
                                </button>
                                <div class="dash-nav-nested-menu">
                                    @if ($can('menu_guru_aktif'))
                                        <a href="{{ route('dashboard.guru-aktif.index') }}"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-aktif*') ? 'active' : '' }}">
                                            <span class="nav-icon nested-icon"><i
                                                    class="fas fa-fw fa-circle-check"></i></span>
                                            <span class="nav-label">Aktif</span>
                                        </a>
                                    @endif

                                    @if ($can('menu_guru_tidak_aktif'))
                                        <a href="#"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-tidak-aktif*') ? 'active' : '' }}">
                                            <span class="nav-icon nested-icon"><i
                                                    class="fas fa-fw fa-circle-xmark"></i></span>
                                            <span class="nav-label">Tidak Aktif</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Nested Submenu: Tendik --}}
                        @if ($hasTendik)
                            <div class="dash-nav-nested-group {{ $isTendikActive ? 'open active-group' : '' }}">
                                <button type="button" class="dash-nav-nested-toggle">
                                    <div class="dash-nav-nested-toggle-main">
                                        <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-badge"></i></span>
                                        <span class="nav-label">Tendik</span>
                                    </div>
                                    <i class="fas fa-chevron-right nested-arrow-icon"></i>
                                </button>
                                <div class="dash-nav-nested-menu">
                                    @if ($can('menu_tendik_aktif'))
                                        <a href="{{ route('dashboard.tendik-aktif.index') }}"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                                            <span class="nav-icon nested-icon"><i
                                                    class="fas fa-fw fa-circle-check"></i></span>
                                            <span class="nav-label">Aktif</span>
                                        </a>
                                    @endif

                                    @if ($can('menu_tendik_tidak_aktif'))
                                        <a href="#"
                                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-tidak-aktif*') ? 'active' : '' }}">
                                            <span class="nav-icon nested-icon"><i
                                                    class="fas fa-fw fa-circle-xmark"></i></span>
                                            <span class="nav-label">Tidak Aktif</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($can('menu_berkas_peserta_didik'))
                            <a href="#" class="dash-nav-sublink">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-folder-open"></i></span>
                                <span class="nav-label">Berkas Peserta Didik</span>
                            </a>
                        @endif

                        @if ($can('menu_perubahan_data'))
                            <a href="#" class="dash-nav-sublink">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-pen"></i></span>
                                <span class="nav-label">Perubahan Data</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- Layanan Akademik & Guru (Collapsible - Di Atas Layanan Digital) --}}
        @if ($hasAkademikGuru)
            @php
                $isAkademikActive =
                    request()->routeIs('dashboard.presensi-mengajar*') ||
                    request()->routeIs('dashboard.agenda-kbm*') ||
                    request()->routeIs('dashboard.penilaian*') ||
                    request()->routeIs('dashboard.presensi-peserta-didik*');
            @endphp
            <div class="dash-nav-group {{ $isAkademikActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-chalkboard-user"></i></span>
                        <span class="nav-label">Akademik Guru</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_presensi_mengajar'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                            <span class="nav-label">Presensi Mengajar</span>
                        </a>
                    @endif

                    @if ($can('menu_agenda_kbm'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-open-reader"></i></span>
                            <span class="nav-label">Jurnal &amp; Agenda KBM</span>
                        </a>
                    @endif

                    @if ($can('menu_penilaian'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-graduation-cap"></i></span>
                            <span class="nav-label">Penilaian Peserta Didik</span>
                        </a>
                    @endif

                    @if ($can('menu_presensi_peserta_didik'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-users-viewfinder"></i></span>
                            <span class="nav-label">Presensi Kelas</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Administrasi Tendik (Collapsible - Di Atas Layanan Digital) --}}
        @php
            $hasAdministrasiTendik =
                $can('menu_buku_tamu') ||
                $can('menu_inventaris') ||
                $can('menu_agenda') ||
                $can('menu_berkas_peserta_didik');
            $isAdministrasiTendikActive =
                request()->routeIs('dashboard.buku-tamu*') ||
                request()->routeIs('dashboard.inventaris*') ||
                request()->routeIs('dashboard.agenda*');
        @endphp
        @if ($hasAdministrasiTendik)
            <div class="dash-nav-group {{ $isAdministrasiTendikActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-id-badge"></i></span>
                        <span class="nav-label">Administrasi Tendik</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_buku_tamu'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-address-book"></i></span>
                            <span class="nav-label">Buku Tamu</span>
                        </a>
                    @endif

                    @if ($can('menu_inventaris'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-boxes-stacked"></i></span>
                            <span class="nav-label">Inventaris Sarpras</span>
                        </a>
                    @endif

                    @if ($can('menu_agenda'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
                            <span class="nav-label">Agenda Sekolah</span>
                        </a>
                    @endif

                    @if ($can('menu_berkas_peserta_didik'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-folder-open"></i></span>
                            <span class="nav-label">Berkas Peserta Didik</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Portal Peserta Didik (Collapsible - Di Atas Layanan Digital) --}}
        @if ($hasPortalPesertaDidik)
            @php
                $isPortalPesertaDidikActive =
                    request()->routeIs('dashboard.riwayat-rfid*') ||
                    request()->routeIs('dashboard.jadwal-pelajaran*') ||
                    request()->routeIs('dashboard.rapor*') ||
                    request()->routeIs('dashboard.validasi-berkas*');
            @endphp
            <div class="dash-nav-group {{ $isPortalPesertaDidikActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
                        <span class="nav-label">Portal Peserta Didik</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_riwayat_rfid'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card-clip"></i></span>
                            <span class="nav-label">Riwayat Presensi RFID</span>
                        </a>
                    @endif

                    @if ($can('menu_jadwal_pelajaran'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
                            <span class="nav-label">Jadwal Pelajaran</span>
                        </a>
                    @endif

                    @if ($can('menu_rapor'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                            <span class="nav-label">Transkrip &amp; Rapor</span>
                        </a>
                    @endif

                    @if ($can('menu_validasi_berkas'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-folder-open"></i></span>
                            <span class="nav-label">Validasi Berkas &amp; Ijazah</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Layanan Digital (Collapsible) --}}
        @if ($hasLayananDigital)
            <span class="nav-section-label">Layanan Digital</span>

            @php
                $isLayananDigitalActive =
                    request()->routeIs('dashboard.pengumuman*') ||
                    request()->routeIs('dashboard.informasi*') ||
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
                        <span class="nav-icon"><i class="fas fa-fw fa-globe"></i></span>
                        <span class="nav-label">Layanan Digital</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($role === 'admin' || \App\Models\RolePermission::can($role, 'menu_pengumuman', 'create'))
                        @if ($can('menu_pengumuman'))
                            <a href="{{ route('dashboard.pengumuman.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.pengumuman*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-bullhorn"></i></span>
                                <span class="nav-label">Pengumuman &amp; Broadcast</span>
                            </a>
                        @endif
                    @else
                        <a href="{{ route('dashboard.informasi.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.informasi*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-bullhorn"></i></span>
                            <span class="nav-label">Pengumuman &amp; Informasi</span>
                        </a>
                    @endif

                    @if ($can('menu_rfid'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card"></i></span>
                            <span class="nav-label">RFID &amp; Presensi</span>
                        </a>
                    @endif

                    @if ($can('menu_e_izin'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-file-signature"></i></span>
                            <span class="nav-label">E-Izin</span>
                        </a>
                    @endif

                    @if ($can('menu_poin'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-star-half-stroke"></i></span>
                            <span class="nav-label">Poin Pelanggaran</span>
                        </a>
                    @endif

                    @if ($can('menu_agenda'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
                            <span class="nav-label">Agenda Sekolah</span>
                        </a>
                    @endif

                    @if ($can('menu_buku_tamu'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-address-book"></i></span>
                            <span class="nav-label">Buku Tamu</span>
                        </a>
                    @endif

                    @if ($can('menu_inventaris'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-boxes-stacked"></i></span>
                            <span class="nav-label">Inventaris</span>
                        </a>
                    @endif

                    @if ($can('menu_kelulusan'))
                        <a href="#" class="dash-nav-sublink">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-graduation-cap"></i></span>
                            <span class="nav-label">Kelulusan</span>
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
                            <span class="nav-icon"><i class="fas fa-fw fa-sliders"></i></span>
                            <span class="nav-label">Pengaturan</span>
                        </div>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </button>
                    <div class="dash-nav-submenu">
                        @if ($can('menu_pengguna'))
                            <a href="{{ route('dashboard.pengguna.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.pengguna*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-users-gear"></i></span>
                                <span class="nav-label">Pengguna</span>
                            </a>
                        @endif

                        @if ($can('menu_hak_akses'))
                            <a href="{{ route('dashboard.hak-akses.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.hak-akses*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-shield-halved"></i></span>
                                <span class="nav-label">Hak Akses</span>
                            </a>
                        @endif

                        @if ($can('menu_pengaturan'))
                            <a href="{{ route('dashboard.identitas-sekolah.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.identitas-sekolah*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-school"></i></span>
                                <span class="nav-label">Identitas Sekolah</span>
                            </a>
                        @endif

                        @if ($can('menu_maintenance'))
                            <a href="{{ route('dashboard.maintenance.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.maintenance*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-server"></i></span>
                                <span class="nav-label">Arsip & Maintenance</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($can('menu_update'))
                <a href="{{ route('dashboard.update') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.update*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fas fa-fw fa-arrows-rotate"></i></span>
                    <span class="nav-label">Update Sistem</span>
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
