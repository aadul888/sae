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
        <span class="nav-section-label">Menu Utama</span>

        @if ($role === 'admin')
            @if (\App\Models\RolePermission::canAccess($role, 'menu_dashboard'))
                <a href="{{ route('dashboard.admin') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i> <span>Dashboard Utama</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_pengguna'))
                <a href="{{ route('dashboard.pengguna.index') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.pengguna*') ? 'active' : '' }}">
                    <i class="fas fa-users-gear"></i> <span>Manajemen Pengguna</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_guru'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-users"></i> <span>Data Guru &amp; Tendik</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_siswa'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-user-graduate"></i> <span>Data Siswa &amp; Kelas</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_rfid'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-id-card"></i> <span>RFID &amp; Presensi Realtime</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_dapodik'))
                <a href="{{ route('dashboard.dapodik') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.dapodik*') ? 'active' : '' }}">
                    <i class="fas fa-cloud-arrow-down"></i> <span>Tarik Data Dapodik</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_update'))
                <a href="{{ route('dashboard.update') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.update*') ? 'active' : '' }}">
                    <i class="fas fa-arrows-rotate"></i> <span>Update Sistem</span>
                </a>
            @endif

            <span class="nav-section-label">Konfigurasi</span>

            @if (\App\Models\RolePermission::canAccess($role, 'menu_hak_akses'))
                <a href="{{ route('dashboard.hak-akses.index') }}"
                    class="dash-nav-link {{ request()->routeIs('dashboard.hak-akses*') ? 'active' : '' }}">
                    <i class="fas fa-shield-halved"></i> <span>Hak Akses &amp; Peran</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_pengumuman'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-bullhorn"></i> <span>Pengumuman &amp; Info</span>
                </a>
            @endif

            @if (\App\Models\RolePermission::canAccess($role, 'menu_pengaturan'))
                <a href="#" class="dash-nav-link">
                    <i class="fas fa-sliders"></i> <span>Pengaturan Sistem</span>
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
