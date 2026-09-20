@php
    $user = session('user');
    $userName = is_array($user)
        ? $user['name'] ?? ($user['nama'] ?? 'Pengguna')
        : $user->name ?? ($user->nama ?? 'Pengguna');
    $role = is_array($user) ? $user['role'] ?? 'peserta_didik' : $user->role ?? 'peserta_didik';

    // Evaluasi gabungan: hak role dasar + tugas tambahan aktif pengguna
    $can = fn(string $key, string $action = 'read') => \App\Models\RolePermission::canAccess(
        $user ?: $role,
        $key,
        $action,
    );

    // Safe route helper
    $href = fn(string $routeName, string $fallback = '#') => \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : $fallback;

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
                ->select('rtt.kode', 'rtt.nama', 'rtt.bidang', 'rtt.ekuivalensi_jam', 'ptt.rombel_id')
                ->get();
        }
    }

    // Izin tingkat peran dasar (Master/Manajemen data hanya tampil di grup utama jika diizinkan di level role)
    $canRole = function(string $key) use ($role, $can) {
        if ($role === 'admin') return true;
        if (!\Illuminate\Support\Facades\Schema::hasTable('role_permissions')) return false;
        $row = \App\Models\RolePermission::where('role', $role)->where('permission_key', $key)->first();
        return $row ? (bool) ($row->is_allowed && $row->can_read) : false;
    };

    // Master Data Submenus (Hanya jika diizinkan di tingkat role dasar atau admin)
    $hasMasterData =
        $canRole('menu_kompetensi_keahlian') ||
        $canRole('menu_rombel') ||
        $canRole('menu_pembelajaran') ||
        $canRole('menu_jadwal_kbm') ||
        $canRole('menu_kalender_pendidikan');

    // Manajemen Data Submenus (Hanya jika diizinkan di tingkat role dasar atau admin)
    $hasPesertaDidik = $canRole('menu_peserta_didik_aktif') || $canRole('menu_peserta_didik_tidak_aktif');
    $hasGuru = $canRole('menu_guru_aktif') || $canRole('menu_guru_tidak_aktif');
    $hasTendik = $canRole('menu_tendik_aktif') || $canRole('menu_tendik_tidak_aktif');
    $hasManajemenData =
        $hasPesertaDidik || $hasGuru || $hasTendik || $canRole('menu_berkas_peserta_didik') || $canRole('menu_perubahan_data');

    // Section: Utama
    $hasUtama = $can('menu_dashboard') || $can('menu_dapodik') || $hasMasterData || $hasManajemenData;

    // Section: Layanan Digital
    $hasLayananDigital =
        $can('menu_formulir') ||
        $can('menu_pengumuman') ||
        $can('menu_rfid') ||
        $can('menu_e_izin') ||
        $can('menu_poin') ||
        $can('menu_persuratan') ||
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
        $can('menu_presensi_peserta_didik');

    // Section: Portal Peserta Didik
    $hasPortalPesertaDidik =
        $can('menu_surat_izin_pd') ||
        $can('menu_riwayat_rfid') ||
        $can('menu_jadwal_pelajaran') ||
        $can('menu_rapor') ||
        $can('menu_validasi_berkas');

    // Section: Wali Kelas (Khusus Admin sebagai pengelola & Guru dengan tugas tambahan Wali Kelas)
    $isWaliOrAdmin = \App\Models\RolePermission::isWaliKelasOrAdmin($user);
    $hasWaliKelas =
        $isWaliOrAdmin &&
        ($can('menu_wali_kelas_aktif') || $can('menu_wali_kelas_tidak_aktif') || $can('menu_wali_kelas_presensi'));
    $waliKelasRombelName = in_array($role, ['guru', 'peserta_didik'], true)
        ? \App\Models\RolePermission::getWaliKelasRombel($user)
        : null;

    // Kumpulkan modul sistem tambahan yang aktif tapi belum ter-render pada template bawaan
    $allKnownModules = \App\Models\RolePermission::getAllSystemModules();
    $renderedMenuKeys = [
        'menu_dashboard',
        'menu_dapodik',
        'menu_kompetensi_keahlian',
        'menu_rombel',
        'menu_pembelajaran',
        'menu_kalender_pendidikan',
        'menu_peserta_didik_aktif',
        'menu_peserta_didik_tidak_aktif',
        'menu_wali_kelas_aktif',
        'menu_wali_kelas_tidak_aktif',
        'menu_wali_kelas_presensi',
        'menu_guru_aktif',
        'menu_guru_tidak_aktif',
        'menu_tendik_aktif',
        'menu_tendik_tidak_aktif',
        'menu_berkas_peserta_didik',
        'menu_perubahan_data',
        'menu_presensi_mengajar',
        'menu_agenda_kbm',
        'menu_presensi_peserta_didik',
        'menu_persuratan',
        'menu_kesiswaan',
        'menu_kepegawaian',
        'menu_aktivitas_tendik',
        'menu_laporan_tendik',
        'menu_inventaris',
        'menu_surat_izin_pd',
        'menu_riwayat_rfid',
        'menu_jadwal_pelajaran',
        'menu_rapor',
        'menu_validasi_berkas',
        'menu_formulir',
        'menu_pengumuman',
        'menu_rfid',
        'menu_e_izin',
        'menu_poin',
        'menu_kelulusan',
        'menu_pengguna',
        'menu_hak_akses',
        'menu_pengaturan',
        'menu_maintenance',
        'menu_update',
    ];
    $extraModules = [];
    foreach ($allKnownModules as $mKey => $mMeta) {
        if (!in_array($mKey, $renderedMenuKeys, true) && $can($mKey)) {
            $extraModules[] = $mMeta;
        }
    }
@endphp

<aside class="dash-sidebar" id="dashSidebar">
    <!-- Brand -->
    <div class="dash-sidebar-header">
        @php
            $dashLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
            $dashLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
        @endphp
        <a href="{{ route('dashboard.' . $role) }}" class="brand"
            style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img id="dashLogo" src="{{ $dashLogoDark }}" data-dark="{{ $dashLogoDark }}" data-light="{{ $dashLogoLight }}"
                alt="SAE Logo" style="height: 36px; max-width: 140px; object-fit: contain;"
                onerror="this.onerror=null; this.src='/img/logo-dark.png';">
        </a>
        <span class="badge badge-primary"
            style="font-size: 0.68rem; padding: 2px 6px; font-family: monospace;">v{{ $appVersion ?? '1.0.1' }}</span>
    </div>

    @php
        $userFoto = is_array($user) ? $user['foto_url'] ?? null : $user->foto_url ?? null;
    @endphp
    <!-- User Profile Badge -->
    <div class="dash-sidebar-user">
        <a href="{{ route('dashboard.profile') }}" title="Lihat Profil Saya"
            style="text-decoration: none; flex-shrink: 0; display: block;">
            @if ($userFoto)
                <img src="{{ $userFoto }}" alt="{{ $userName }}" class="dash-sidebar-avatar-img"
                    onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <div class="dash-sidebar-user-avatar" style="display: none;">
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
            @else
                <div class="dash-sidebar-user-avatar">
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
            @endif
        </a>
        <div class="dash-user-info">
            <a href="{{ route('dashboard.profile') }}" class="dash-user-name" title="{{ $userName }}"
                style="text-decoration: none; color: inherit; display: block;">{{ $userName }}</a>
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
                    ($role === 'tendik'
                        ? (request()->routeIs('dashboard.tendik') && !request()->has('bidang'))
                        : request()->routeIs('dashboard.' . $role)) ||
                    ($role === 'peserta_didik' &&
                        (request()->routeIs('dashboard.peserta-didik') ||
                            request()->routeIs('dashboard.peserta_didik')));
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
                        request()->routeIs('dashboard.kompetensi-keahlian.*') ||
                        request()->routeIs('dashboard.kompetensi-keahlian') ||
                        request()->routeIs('dashboard.rombel.*') ||
                        request()->routeIs('dashboard.rombel') ||
                        request()->routeIs('dashboard.pembelajaran.*') ||
                        request()->routeIs('dashboard.pembelajaran') ||
                        request()->routeIs('dashboard.jadwal-kbm.*') ||
                        request()->routeIs('dashboard.jadwal-kbm') ||
                        request()->routeIs('dashboard.kalender-pendidikan.*') ||
                        request()->routeIs('dashboard.kalender-pendidikan');
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

                        @if ($can('menu_jadwal_kbm'))
                            <a href="{{ route('dashboard.jadwal-kbm.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.jadwal-kbm*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-alt"></i></span>
                                <span class="nav-label">Jadwal KBM</span>
                            </a>
                        @endif

                        @if ($can('menu_kalender_pendidikan'))
                            <a href="{{ route('dashboard.kalender-pendidikan.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.kalender-pendidikan*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
                                <span class="nav-label">Kalender Pendidikan</span>
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
                                        <a href="{{ route('dashboard.peserta-didik-tidak-aktif.index') }}"
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

        {{-- Layanan Guru (Tugas Pokok Guru) --}}
        @php
            $hasAkademik = $can('menu_presensi_mengajar') || $can('menu_agenda_kbm') || $role === 'admin';
            $isAkademikActive =
                request()->routeIs('dashboard.guru') ||
                request()->routeIs('dashboard.presensi-mengajar.*') ||
                request()->routeIs('dashboard.presensi-mengajar') ||
                request()->routeIs('dashboard.agenda-kbm.*') ||
                request()->routeIs('dashboard.agenda-kbm');
        @endphp
        @if ($hasAkademik)
            <div class="dash-nav-group {{ $isAkademikActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-chalkboard-user"></i></span>
                        <span class="nav-label">Guru</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_dashboard') || $role === 'admin')
                        <a href="{{ route('dashboard.guru') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.guru') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard</span>
                        </a>
                    @endif

                    @if ($can('menu_presensi_mengajar'))
                        <a href="{{ route('dashboard.presensi-mengajar.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.presensi-mengajar*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                            <span class="nav-label">Presensi Mengajar</span>
                        </a>
                    @endif

                    @if ($can('menu_agenda_kbm'))
                        <a href="{{ route('dashboard.agenda-kbm.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.agenda-kbm*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-open-reader"></i></span>
                            <span class="nav-label">Jurnal &amp; Agenda KBM</span>
                        </a>
                    @endif

                </div>
            </div>
        @endif

        {{-- Tugas Tambahan / Modul: Tendik (Superadmin) atau Kepala TAS (Guru/Tendik yang bertugas Koordinator) --}}
        @php
            $hasKepalaTasDuty = collect($userDuties)->contains('kode', 'KEPALA_TAS') || ($role === 'admin');
            $hasKepalaTas = $hasKepalaTasDuty && (
                $can('menu_kepala_tas') ||
                $can('menu_persuratan') ||
                $can('menu_surat_masuk') ||
                $can('menu_surat_keluar') ||
                $can('menu_pengaturan_persuratan') ||
                $can('menu_kesiswaan') ||
                $can('menu_kepegawaian') ||
                $can('menu_sarpras') ||
                $can('menu_laboran') ||
                $can('menu_perpustakaan') ||
                $can('menu_teknisi') ||
                $can('menu_keamanan') ||
                $can('menu_penjaga') ||
                $can('menu_piket') ||
                $can('menu_aktivitas_tendik') ||
                $can('menu_laporan_tendik')
            );

            $isPersuratanActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'persuratan') || request()->routeIs('dashboard.persuratan.*') || request()->routeIs('dashboard.persuratan') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'persuratan') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'persuratan');
            $isKesiswaanActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kesiswaan') || request()->routeIs('dashboard.kesiswaan.*') || request()->routeIs('dashboard.kesiswaan') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'kesiswaan') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'kesiswaan');
            $isKepegawaianActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kepegawaian') || request()->routeIs('dashboard.kepegawaian.*') || request()->routeIs('dashboard.kepegawaian') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'kepegawaian') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'kepegawaian');
            $isSarprasActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'sarpras') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'sarpras') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'sarpras');
            $isLaboranActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'laboran') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'laboran') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'laboran');
            $isPerpustakaanActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'perpustakaan') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'perpustakaan') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'perpustakaan');
            $isTeknisiActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'teknisi') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'teknisi') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'teknisi');
            $isKeamananActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'keamanan') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'keamanan') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'keamanan');
            $isPenjagaActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'penjaga') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'penjaga') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'penjaga');
            $isPiketActive = (request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'piket') || (request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'piket') || (request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'piket');

            $isKepalaTasActive =
                ($role === 'admin' && request()->routeIs('dashboard.tendik*')) ||
                request()->routeIs('dashboard.tendik') ||
                request()->routeIs('dashboard.tendik.aktivitas.*') ||
                request()->routeIs('dashboard.tendik.aktivitas') ||
                request()->routeIs('dashboard.tendik.laporan.*') ||
                request()->routeIs('dashboard.tendik.laporan') ||
                $isPersuratanActive ||
                $isKesiswaanActive ||
                $isKepegawaianActive ||
                $isSarprasActive ||
                $isLaboranActive ||
                $isPerpustakaanActive ||
                $isTeknisiActive ||
                $isKeamananActive ||
                $isPenjagaActive ||
                $isPiketActive;
        @endphp
        @if ($hasKepalaTas)
            <div class="dash-nav-group {{ $isKepalaTasActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw {{ $role === 'admin' ? 'fa-id-badge' : 'fa-user-tie' }}"></i></span>
                        <span class="nav-label">{{ $role === 'admin' ? 'Tendik' : 'Kepala TAS' }}</span>
                        @if ($role !== 'admin')
                            <span class="badge badge-primary"
                                style="font-size: 0.65rem; padding: 2px 6px; margin-left: 6px; border-radius: 4px; font-weight: 700;">
                                Koordinator
                            </span>
                        @endif
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_kepala_tas'))
                        <a href="{{ $role === 'admin' ? route('dashboard.tendik') : route('dashboard.tendik', ['bidang' => 'kepala-tas']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && ($role === 'admin' ? (!request()->has('bidang') || request()->query('bidang') === 'umum' || request()->query('bidang') === 'kepala-tas') : request()->query('bidang') === 'kepala-tas') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">{{ $role === 'admin' ? 'Dashboard' : 'Dashboard Kepala TAS' }}</span>
                        </a>
                    @endif

                    {{-- 1. Submenu Per-Bidang: Persuratan --}}
                    @if ($can('menu_persuratan') || $can('menu_surat_masuk') || $can('menu_surat_keluar') || $can('menu_pengaturan_persuratan'))
                        <div class="dash-nav-nested-group {{ $isPersuratanActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div class="dash-nav-nested-toggle-main">
                                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-envelope-open-text"></i></span>
                                    <span class="nav-label">Persuratan</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                @if ($can('menu_persuratan'))
                                    <a href="{{ route('dashboard.tendik', ['bidang' => 'persuratan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'persuratan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
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
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-paper-plane"></i></span>
                                        <span class="nav-label">Surat Keluar</span>
                                    </a>
                                @endif
                                @if ($can('menu_pengaturan_persuratan') || $can('menu_persuratan'))
                                    <a href="{{ route('dashboard.persuratan.pengaturan.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.pengaturan*') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-sliders"></i></span>
                                        <span class="nav-label">Pengaturan &amp; Arsip HDD</span>
                                    </a>
                                @endif
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'persuratan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'persuratan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'persuratan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'persuratan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 2. Submenu Per-Bidang: Kesiswaan --}}
                    @if ($can('menu_kesiswaan'))
                        <div class="dash-nav-nested-group {{ $isKesiswaanActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div class="dash-nav-nested-toggle-main">
                                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
                                    <span class="nav-label">Kesiswaan</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                <a href="{{ route('dashboard.tendik', ['bidang' => 'kesiswaan']) }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kesiswaan' ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                                    <span class="nav-label">Dashboard Kesiswaan</span>
                                </a>
                                <a href="{{ $href('dashboard.kesiswaan.index') }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.*') ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-bookmark"></i></span>
                                    <span class="nav-label">Buku Klaper &amp; Kesiswaan</span>
                                </a>
                                @if ($can('menu_peserta_didik_aktif'))
                                    <a href="{{ $href('dashboard.peserta-didik-aktif.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.peserta-didik-aktif*') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-user-check"></i></span>
                                        <span class="nav-label">Buku Induk Siswa</span>
                                    </a>
                                @endif
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'kesiswaan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'kesiswaan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'kesiswaan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'kesiswaan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                <a href="{{ $href('dashboard.kepegawaian.index') }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-id-card-clip"></i></span>
                                    <span class="nav-label">Kepegawaian GTK &amp; KGB</span>
                                </a>
                                @if ($can('menu_tendik_aktif'))
                                    <a href="{{ $href('dashboard.tendik-aktif.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-id-badge"></i></span>
                                        <span class="nav-label">Data Tendik</span>
                                    </a>
                                @endif
                                @if ($can('menu_guru_aktif'))
                                    <a href="{{ $href('dashboard.guru-aktif.index') }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.guru-aktif*') ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-chalkboard-user"></i></span>
                                        <span class="nav-label">Data Guru</span>
                                    </a>
                                @endif
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'kepegawaian']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'kepegawaian' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'kepegawaian']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'kepegawaian' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                @if ($can('menu_inventaris'))
                                    <a href="#" class="dash-nav-nested-link">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-boxes-stacked"></i></span>
                                        <span class="nav-label">Inventaris Sarpras</span>
                                    </a>
                                @endif
                                @if ($can('menu_rombel'))
                                    <a href="{{ route('dashboard.rombel.index') }}" class="dash-nav-nested-link">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-door-open"></i></span>
                                        <span class="nav-label">Pemetaan Ruang</span>
                                    </a>
                                @endif
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'sarpras']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'sarpras' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'sarpras']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'sarpras' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 5. Submenu Per-Bidang: Laboratorium --}}
                    @if ($can('menu_laboran'))
                        <div class="dash-nav-nested-group {{ $isLaboranActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div class="dash-nav-nested-toggle-main">
                                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-flask"></i></span>
                                    <span class="nav-label">Laboratorium</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                <a href="{{ route('dashboard.tendik', ['bidang' => 'laboran']) }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'laboran' ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                                    <span class="nav-label">Dashboard Laboratorium</span>
                                </a>
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'laboran']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'laboran' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'laboran']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'laboran' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'perpustakaan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'perpustakaan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'perpustakaan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'perpustakaan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 7. Submenu Per-Bidang: Teknisi IT --}}
                    @if ($can('menu_teknisi'))
                        <div class="dash-nav-nested-group {{ $isTeknisiActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div class="dash-nav-nested-toggle-main">
                                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-network-wired"></i></span>
                                    <span class="nav-label">Teknisi IT</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                <a href="{{ route('dashboard.tendik', ['bidang' => 'teknisi']) }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'teknisi' ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                                    <span class="nav-label">Dashboard Teknisi IT</span>
                                </a>
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'teknisi']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'teknisi' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'teknisi']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'teknisi' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

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
                                <a href="{{ route('presensi.scan') }}" target="_blank" class="dash-nav-nested-link">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-qrcode"></i></span>
                                    <span class="nav-label">Pos Scanner RFID</span>
                                </a>
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'keamanan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'keamanan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'keamanan']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'keamanan' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 9. Submenu Per-Bidang: Fasilitas & Penjaga --}}
                    @if ($can('menu_penjaga'))
                        <div class="dash-nav-nested-group {{ $isPenjagaActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div class="dash-nav-nested-toggle-main">
                                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-broom"></i></span>
                                    <span class="nav-label">Fasilitas &amp; Penjaga</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                <a href="{{ route('dashboard.tendik', ['bidang' => 'penjaga']) }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'penjaga' ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                                    <span class="nav-label">Dashboard Penjaga</span>
                                </a>
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'penjaga']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'penjaga' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'penjaga']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'penjaga' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 10. Submenu Per-Bidang: Piket Sekolah --}}
                    @if ($can('menu_piket'))
                        <div class="dash-nav-nested-group {{ $isPiketActive ? 'open active-group' : '' }}">
                            <button type="button" class="dash-nav-nested-toggle">
                                <div class="dash-nav-nested-toggle-main">
                                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-user"></i></span>
                                    <span class="nav-label">Piket Sekolah</span>
                                </div>
                                <i class="fas fa-chevron-right nested-arrow-icon"></i>
                            </button>
                            <div class="dash-nav-nested-menu">
                                <a href="{{ route('dashboard.tendik', ['bidang' => 'piket']) }}"
                                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'piket' ? 'active' : '' }}">
                                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                                    <span class="nav-label">Dashboard Guru Piket</span>
                                </a>
                                @if ($can('menu_aktivitas_tendik'))
                                    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'piket']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.aktivitas*') && request()->query('bidang') === 'piket' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                                        <span class="nav-label">Aktivitas Harian</span>
                                    </a>
                                @endif
                                @if ($can('menu_laporan_tendik'))
                                    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'piket']) }}"
                                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik.laporan*') && request()->query('bidang') === 'piket' ? 'active' : '' }}">
                                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                                        <span class="nav-label">Laporan Kinerja</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Rekap Aktivitas Harian & Laporan Kinerja Universal (Paling Bawah) --}}
                    @if ($can('menu_aktivitas_tendik'))
                        <a href="{{ route('dashboard.tendik.aktivitas.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.aktivitas.*') && !request()->has('bidang') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                            <span class="nav-label">Aktivitas Harian</span>
                        </a>
                    @endif
                    @if ($can('menu_laporan_tendik'))
                        <a href="{{ route('dashboard.tendik.laporan.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.laporan.*') && !request()->has('bidang') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                            <span class="nav-label">Laporan Kinerja</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Layanan Tendik (Tugas Pokok Tendik & Bidang Tugasnya) --}}
        @php
            $isTendikRole = $role === 'tendik';
            $tendikDutyList = collect($userDuties)->filter(function($d) {
                return in_array($d->kode, [
                    'STAF_PERSURATAN', 'STAF_KESISWAAN', 'STAF_KEPEGAWAIAN', 'STAF_SARPRAS',
                    'LABORAN', 'PUSTAKAWAN', 'TEKNISI_IT', 'SATPAM', 'PENJAGA_SEKOLAH'
                ]);
            });
            $primaryTendikDutyName = $tendikDutyList->first()?->nama;

            $hasTendikSection = $isTendikRole && !$hasKepalaTas && (
                ($tendikDutyList->contains('kode', 'STAF_PERSURATAN') && ($can('menu_persuratan') || $can('menu_surat_masuk') || $can('menu_surat_keluar') || $can('menu_pengaturan_persuratan'))) ||
                ($tendikDutyList->contains('kode', 'STAF_KESISWAAN') && $can('menu_kesiswaan')) ||
                ($tendikDutyList->contains('kode', 'STAF_KEPEGAWAIAN') && $can('menu_kepegawaian')) ||
                ($tendikDutyList->contains('kode', 'STAF_SARPRAS') && $can('menu_sarpras')) ||
                ($tendikDutyList->contains('kode', 'PUSTAKAWAN') && $can('menu_perpustakaan')) ||
                ($tendikDutyList->contains('kode', 'LABORAN') && $can('menu_laboran')) ||
                (($tendikDutyList->contains('kode', 'SATPAM') || $tendikDutyList->contains('kode', 'PENJAGA_SEKOLAH')) && $can('menu_keamanan')) ||
                ($tendikDutyList->isEmpty() && $can('menu_tendik_aktif')) ||
                $can('menu_aktivitas_tendik') ||
                $can('menu_laporan_tendik')
            );

            $isTendikActive =
                (request()->routeIs('dashboard.tendik') && request()->has('bidang')) ||
                request()->routeIs('dashboard.tendik.aktivitas.*') ||
                request()->routeIs('dashboard.tendik.aktivitas') ||
                request()->routeIs('dashboard.tendik.laporan.*') ||
                request()->routeIs('dashboard.tendik.laporan') ||
                request()->routeIs('dashboard.persuratan.*') ||
                request()->routeIs('dashboard.persuratan') ||
                request()->routeIs('dashboard.kesiswaan.*') ||
                request()->routeIs('dashboard.kesiswaan') ||
                request()->routeIs('dashboard.kepegawaian.*') ||
                request()->routeIs('dashboard.kepegawaian');
        @endphp
        @if ($hasTendikSection)
            <div class="dash-nav-group {{ $isTendikActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-id-badge"></i></span>
                        <span class="nav-label">Tendik</span>
                        @if ($primaryTendikDutyName)
                            <span class="badge badge-primary"
                                style="font-size: 0.65rem; padding: 2px 6px; margin-left: 6px; border-radius: 4px; font-weight: 700; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                title="{{ $primaryTendikDutyName }}">
                                {{ $primaryTendikDutyName }}
                            </span>
                        @endif
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    {{-- 1. Menu Khusus Bidang Tugas Tambahan (Hanya tampil bagi yang memiliki penugasan dan diizinkan) --}}
                    {{-- Bidang Persuratan --}}
                    @if ($tendikDutyList->contains('kode', 'STAF_PERSURATAN') && ($can('menu_persuratan') || $can('menu_surat_masuk') || $can('menu_surat_keluar') || $can('menu_pengaturan_persuratan')))
                        @if ($can('menu_persuratan'))
                            <a href="{{ route('dashboard.tendik', ['bidang' => 'persuratan']) }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'persuratan' ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                                <span class="nav-label">Dashboard Persuratan</span>
                            </a>
                        @endif
                        @if ($can('menu_surat_masuk') || $can('menu_persuratan'))
                            <a href="{{ route('dashboard.persuratan.masuk.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.persuratan.masuk*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-inbox"></i></span>
                                <span class="nav-label">Surat Masuk</span>
                            </a>
                        @endif
                        @if ($can('menu_surat_keluar') || $can('menu_persuratan'))
                            <a href="{{ route('dashboard.persuratan.keluar.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.persuratan.keluar*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-paper-plane"></i></span>
                                <span class="nav-label">Surat Keluar</span>
                            </a>
                        @endif
                        @if ($can('menu_pengaturan_persuratan') || $can('menu_persuratan'))
                            <a href="{{ route('dashboard.persuratan.pengaturan.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.persuratan.pengaturan*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-sliders"></i></span>
                                <span class="nav-label">Pengaturan &amp; Arsip HDD</span>
                            </a>
                        @endif
                    @endif

                    {{-- Bidang Kesiswaan --}}
                    @if ($tendikDutyList->contains('kode', 'STAF_KESISWAAN') && $can('menu_kesiswaan'))
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'kesiswaan']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kesiswaan' ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Kesiswaan</span>
                        </a>
                        <a href="{{ $href('dashboard.kesiswaan.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.kesiswaan.*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
                            <span class="nav-label">Buku Klaper &amp; Kesiswaan</span>
                        </a>
                        @if ($can('menu_peserta_didik_aktif'))
                            <a href="{{ $href('dashboard.peserta-didik-aktif.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik-aktif*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-check"></i></span>
                                <span class="nav-label">Buku Induk Siswa</span>
                            </a>
                        @endif
                    @endif

                    {{-- Bidang Kepegawaian --}}
                    @if ($tendikDutyList->contains('kode', 'STAF_KEPEGAWAIAN') && $can('menu_kepegawaian'))
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'kepegawaian']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'kepegawaian' ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Kepegawaian</span>
                        </a>
                        <a href="{{ $href('dashboard.kepegawaian.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.kepegawaian.*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card-alt"></i></span>
                            <span class="nav-label">Kepegawaian GTK &amp; KGB</span>
                        </a>
                        @if ($can('menu_tendik_aktif'))
                            <a href="{{ $href('dashboard.tendik-aktif.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-badge"></i></span>
                                <span class="nav-label">Data Tendik</span>
                            </a>
                        @endif
                        @if ($can('menu_guru_aktif'))
                            <a href="{{ $href('dashboard.guru-aktif.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.guru-aktif*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-chalkboard-user"></i></span>
                                <span class="nav-label">Data Guru</span>
                            </a>
                        @endif
                    @endif

                    {{-- Bidang Sarpras --}}
                    @if ($tendikDutyList->contains('kode', 'STAF_SARPRAS') && $can('menu_sarpras'))
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'sarpras']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'sarpras' ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Sarpras</span>
                        </a>
                        @if ($can('menu_rombel'))
                            <a href="{{ route('dashboard.rombel.index') }}" class="dash-nav-sublink">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-door-open"></i></span>
                                <span class="nav-label">Pemetaan Ruang</span>
                            </a>
                        @endif
                    @endif

                    {{-- Bidang Perpustakaan --}}
                    @if ($tendikDutyList->contains('kode', 'PUSTAKAWAN') && $can('menu_perpustakaan'))
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'perpustakaan']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'perpustakaan' ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Perpustakaan</span>
                        </a>
                    @endif

                    {{-- Bidang Laboratorium --}}
                    @if ($tendikDutyList->contains('kode', 'LABORAN') && $can('menu_laboran'))
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'laboran']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'laboran' ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Laboratorium</span>
                        </a>
                    @endif

                    {{-- Bidang Keamanan / Satpam / Penjaga --}}
                    @if (($tendikDutyList->contains('kode', 'SATPAM') || $tendikDutyList->contains('kode', 'PENJAGA_SEKOLAH')) && $can('menu_keamanan'))
                        <a href="{{ route('dashboard.tendik', ['bidang' => 'keamanan']) }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'keamanan' ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Keamanan</span>
                        </a>
                        @if ($can('menu_rfid'))
                            <a href="{{ route('presensi.scan') }}" target="_blank" class="dash-nav-sublink">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-qrcode"></i></span>
                                <span class="nav-label">Pos Scanner RFID</span>
                            </a>
                        @endif
                    @endif

                    {{-- Tendik Umum (Belum Memiliki Tugas Tambahan Khusus) --}}
                    @if ($tendikDutyList->isEmpty())
                        @if ($can('menu_tendik_aktif'))
                            <a href="{{ $href('dashboard.tendik-aktif.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik-aktif*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-badge"></i></span>
                                <span class="nav-label">Data Tendik</span>
                            </a>
                        @endif
                    @endif


                    {{-- Aktivitas Harian & Laporan Kinerja Universal (Paling Bawah) --}}
                    @if ($can('menu_aktivitas_tendik'))
                        <a href="{{ route('dashboard.tendik.aktivitas.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.aktivitas.*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                            <span class="nav-label">Input Aktivitas</span>
                        </a>
                    @endif
                    @if ($can('menu_laporan_tendik'))
                        <a href="{{ route('dashboard.tendik.laporan.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.laporan.*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                            <span class="nav-label">Laporan Kinerja</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Tugas Tambahan: Guru Piket (Jika Guru bertugas Piket) --}}
        @php
            $hasGuruPiketDuty = collect($userDuties)->contains('kode', 'GURU_PIKET');
            $hasGuruPiket = $hasGuruPiketDuty && (
                $can('menu_piket') ||
                $can('menu_presensi_mengajar') ||
                $can('menu_agenda_kbm') ||
                $can('menu_e_izin') ||
                $can('menu_buku_tamu')
            );
            $isPiketActive =
                (request()->routeIs('dashboard.presensi-mengajar.*') && $hasGuruPiket) ||
                request()->routeIs('dashboard.peserta-didik.izin.*');
        @endphp
        @if ($hasGuruPiket)
            <div class="dash-nav-group {{ $isPiketActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-clipboard-user"></i></span>
                        <span class="nav-label">Guru Piket</span>
                        <span class="badge badge-primary"
                            style="font-size: 0.65rem; padding: 2px 6px; margin-left: 6px; border-radius: 4px; font-weight: 700;">
                            Piket
                        </span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_presensi_mengajar') || $can('menu_piket'))
                        <a href="{{ $href('dashboard.presensi-mengajar.index') }}" class="dash-nav-sublink {{ request()->routeIs('dashboard.presensi-mengajar*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                            <span class="nav-label">Presensi Guru Mengajar</span>
                        </a>
                    @endif
                    @if ($can('menu_agenda_kbm') || $can('menu_piket'))
                        <a href="{{ $href('dashboard.agenda-kbm.index') }}" class="dash-nav-sublink {{ request()->routeIs('dashboard.agenda-kbm*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-book-open-reader"></i></span>
                            <span class="nav-label">Jurnal &amp; Agenda KBM</span>
                        </a>
                    @endif
                    @if ($can('menu_e_izin') || $can('menu_piket'))
                        <a href="{{ $href('dashboard.peserta-didik.izin.index') }}" class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik.izin*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-envelope-open-text"></i></span>
                            <span class="nav-label">e-Izin Keluar Masuk Siswa</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Layanan Peserta Didik (Collapsible - Di Atas Layanan Digital) --}}
        @if ($hasPortalPesertaDidik)
            @php
                $isPortalPesertaDidikActive =
                    request()->routeIs('dashboard.peserta-didik') ||
                    request()->routeIs('dashboard.peserta_didik') ||
                    request()->routeIs('dashboard.peserta-didik.izin.*') ||
                    request()->routeIs('dashboard.peserta-didik.izin') ||
                    request()->routeIs('dashboard.peserta-didik.presensi.*') ||
                    request()->routeIs('dashboard.peserta-didik.presensi') ||
                    request()->routeIs('dashboard.presensi.riwayat-saya*') ||
                    request()->routeIs('dashboard.riwayat-rfid.*') ||
                    request()->routeIs('dashboard.riwayat-rfid') ||
                    request()->routeIs('dashboard.jadwal-pelajaran.*') ||
                    request()->routeIs('dashboard.jadwal-pelajaran') ||
                    request()->routeIs('dashboard.rapor.*') ||
                    request()->routeIs('dashboard.rapor') ||
                    request()->routeIs('dashboard.validasi-berkas.*') ||
                    request()->routeIs('dashboard.validasi-berkas');
            @endphp
            <div class="dash-nav-group {{ $isPortalPesertaDidikActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
                        <span class="nav-label">Peserta Didik</span>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_dashboard') || $role === 'admin')
                        <a href="{{ route('dashboard.peserta-didik') }}"
                            class="dash-nav-sublink {{ (request()->routeIs('dashboard.peserta-didik') || request()->routeIs('dashboard.peserta_didik')) ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard</span>
                        </a>
                    @endif

                    @if ($can('menu_surat_izin_pd'))
                        <a href="{{ route('dashboard.peserta-didik.izin.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik.izin*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-envelope-open-text"></i></span>
                            <span class="nav-label">Surat Izin &amp; Sakit</span>
                        </a>
                    @endif

                    @if ($can('menu_riwayat_rfid'))
                        <a href="{{ route('dashboard.peserta-didik.presensi.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.peserta-didik.presensi*') || request()->routeIs('dashboard.presensi.riwayat-saya*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                            <span class="nav-label">Riwayat Presensi Harian</span>
                        </a>
                    @endif

                    @if ($can('menu_jadwal_pelajaran') || $can('menu_jadwal_kbm'))
                        <a href="{{ route('dashboard.jadwal-kbm.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.jadwal-kbm*') ? 'active' : '' }}">
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

        {{-- Modul Khusus: Wali Kelas / Koordinator (Hanya untuk Admin & Guru dengan Tugas Tambahan Wali Kelas atau Siswa Koordinator) --}}
        @if ($hasWaliKelas)
            @php
                $isWaliKelasActive =
                    request()->routeIs('dashboard.wali-kelas.peserta-didik-aktif*') ||
                    request()->routeIs('dashboard.wali-kelas.peserta-didik-tidak-aktif*') ||
                    request()->routeIs('dashboard.wali-kelas.*') ||
                    request()->routeIs('dashboard.wali-kelas');
            @endphp
            <div class="dash-nav-group {{ $isWaliKelasActive ? 'open active-group' : '' }}">
                <button type="button" class="dash-nav-toggle">
                    <div class="dash-nav-toggle-main">
                        <span class="nav-icon"><i
                                class="fas fa-fw {{ $role === 'peserta_didik' ? 'fa-crown text-warning' : 'fa-chalkboard-user' }}"></i></span>
                        <span class="nav-label">{{ $role === 'peserta_didik' ? 'Koordinator' : 'Wali Kelas' }}</span>
                        @if ($waliKelasRombelName)
                            <span class="badge badge-primary"
                                style="font-size: 0.68rem; padding: 2px 6px; margin-left: 6px; border-radius: 4px; font-weight: 700;">
                                {{ $waliKelasRombelName }}
                            </span>
                        @endif
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </button>
                <div class="dash-nav-submenu">
                    @if ($can('menu_wali_kelas_aktif'))
                        <a href="{{ route('dashboard.wali-kelas.peserta-didik-aktif.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.peserta-didik-aktif*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-circle-check"></i></span>
                            <span class="nav-label">Peserta Didik Aktif</span>
                        </a>
                    @endif

                    @if ($can('menu_wali_kelas_tidak_aktif'))
                        <a href="{{ route('dashboard.wali-kelas.peserta-didik-tidak-aktif.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.peserta-didik-tidak-aktif*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-circle-xmark"></i></span>
                            <span class="nav-label">Peserta Didik Tidak Aktif</span>
                        </a>
                    @endif

                    @if ($can('menu_wali_kelas_presensi'))
                        <a href="{{ route('dashboard.wali-kelas.presensi.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.wali-kelas.presensi*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-user"></i></span>
                            <span class="nav-label">Presensi Kelas</span>
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
                    request()->routeIs('dashboard.formulir.*') ||
                    request()->routeIs('dashboard.formulir') ||
                    request()->routeIs('dashboard.pengumuman.*') ||
                    request()->routeIs('dashboard.pengumuman') ||
                    request()->routeIs('dashboard.informasi.*') ||
                    request()->routeIs('dashboard.informasi') ||
                    (request()->routeIs('dashboard.presensi.*') &&
                        !request()->routeIs('dashboard.presensi.riwayat-saya*') &&
                        !request()->routeIs('dashboard.presensi.kelas*') &&
                        !request()->routeIs('dashboard.presensi-mengajar.*') &&
                        !request()->routeIs('dashboard.presensi-mengajar')) ||
                    request()->routeIs('dashboard.rfid.*') ||
                    request()->routeIs('dashboard.rfid') ||
                    request()->routeIs('dashboard.e-izin.*') ||
                    request()->routeIs('dashboard.e-izin') ||
                    request()->routeIs('dashboard.poin.*') ||
                    request()->routeIs('dashboard.poin') ||
                    (request()->routeIs('dashboard.agenda.*') &&
                        !request()->routeIs('dashboard.agenda-kbm.*') &&
                        !request()->routeIs('dashboard.agenda-kbm')) ||
                    request()->routeIs('dashboard.persuratan.*') ||
                    request()->routeIs('dashboard.persuratan') ||
                    request()->routeIs('dashboard.buku-tamu.*') ||
                    request()->routeIs('dashboard.buku-tamu') ||
                    request()->routeIs('dashboard.inventaris.*') ||
                    request()->routeIs('dashboard.inventaris') ||
                    request()->routeIs('dashboard.kelulusan.*') ||
                    request()->routeIs('dashboard.kelulusan');
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
                    @if ($can('menu_formulir'))
                        <a href="{{ route('dashboard.formulir.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.formulir*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-list"></i></span>
                            <span class="nav-label">Formulir &amp; Survei</span>
                        </a>
                    @endif

                    @if ($can('menu_pengumuman'))
                        @if ($role === 'admin' || \App\Models\RolePermission::can($role, 'menu_pengumuman', 'create'))
                            <a href="{{ route('dashboard.pengumuman.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.pengumuman*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-bullhorn"></i></span>
                                <span class="nav-label">Pengumuman &amp; Broadcast</span>
                            </a>
                        @else
                            <a href="{{ route('dashboard.informasi.index') }}"
                                class="dash-nav-sublink {{ request()->routeIs('dashboard.informasi*') ? 'active' : '' }}">
                                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-bullhorn"></i></span>
                                <span class="nav-label">Pengumuman &amp; Informasi</span>
                            </a>
                        @endif
                    @endif

                    @if ($can('menu_rfid'))
                        <a href="{{ route('dashboard.presensi.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.presensi.*') && !request()->routeIs('dashboard.presensi.kelas*') && !request()->routeIs('dashboard.presensi.riwayat-saya*') && !request()->routeIs('dashboard.presensi-mengajar.*') ? 'active' : '' }}">
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

                    @if ($can('menu_persuratan'))
                        <a href="{{ route('dashboard.persuratan.masuk.index') }}"
                            class="dash-nav-sublink {{ request()->routeIs('dashboard.persuratan*') ? 'active' : '' }}">
                            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-envelope-open-text"></i></span>
                            <span class="nav-label">Persuratan &amp; Arsip</span>
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
                        request()->routeIs('dashboard.pengguna.*') ||
                        request()->routeIs('dashboard.pengguna') ||
                        request()->routeIs('dashboard.hak-akses.*') ||
                        request()->routeIs('dashboard.hak-akses') ||
                        request()->routeIs('dashboard.identitas-sekolah.*') ||
                        request()->routeIs('dashboard.identitas-sekolah') ||
                        request()->routeIs('dashboard.maintenance.*') ||
                        request()->routeIs('dashboard.maintenance');
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
        <button type="button" class="dash-nav-link btn-pwa-install" onclick="installSaePwa()"
            style="display: none; width: 100%; border: 1px solid rgba(16,185,129,0.25); background: rgba(16,185,129,0.12); color: #10b981; cursor: pointer; text-align: left; margin-bottom: 6px; border-radius: 8px;">
            <i class="fas fa-download"></i> <span>Instal Aplikasi SAE</span>
        </button>
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
