@php
    $user = session('user');
    $role = is_array($user) ? $user['role'] ?? 'peserta_didik' : $user->role ?? 'peserta_didik';
    $role = str_replace('-', '_', $role);

    $href = fn($route, $fallback = '#') => \Illuminate\Support\Facades\Route::has($route) ? route($route) : $fallback;

    $menus = [
        'admin' => [
            [
                'label' => 'Home',
                'icon' => 'fa-house',
                'href' => $href('dashboard.admin'),
                'permission' => 'menu_dashboard',
                'active' => request()->routeIs('dashboard.admin'),
            ],
            [
                'label' => 'User',
                'icon' => 'fa-users-gear',
                'href' => $href('dashboard.pengguna.index'),
                'permission' => 'menu_pengguna',
                'active' => request()->routeIs('dashboard.pengguna.*'),
            ],
            [
                'label' => 'Presensi',
                'icon' => 'fa-fingerprint',
                'href' => $href('dashboard.presensi.index'),
                'permission' => 'menu_rfid',
                'active' => request()->routeIs('dashboard.presensi.*'),
                'prominent' => true,
            ],
            [
                'label' => 'Info',
                'icon' => 'fa-bullhorn',
                'href' => $href('dashboard.pengumuman.index'),
                'permission' => 'menu_pengumuman',
                'active' => request()->routeIs('dashboard.pengumuman.*'),
            ],
            [
                'label' => 'Profil',
                'icon' => 'fa-user-shield',
                'href' => $href('dashboard.profile'),
                'active' => request()->routeIs('dashboard.profile'),
            ],
        ],
        'guru' => [
            [
                'label' => 'Home',
                'icon' => 'fa-house',
                'href' => $href('dashboard.guru'),
                'permission' => 'menu_dashboard',
                'active' => request()->routeIs('dashboard.guru'),
            ],
            [
                'label' => 'Agenda',
                'icon' => 'fa-book-open',
                'href' => $href('dashboard.agenda-kbm.index'),
                'permission' => 'menu_agenda_kbm',
                'active' => request()->routeIs('dashboard.agenda-kbm.*'),
            ],
            [
                'label' => 'Mengajar',
                'icon' => 'fa-calendar-check',
                'href' => $href('dashboard.presensi-mengajar.index'),
                'permission' => 'menu_presensi_mengajar',
                'active' => request()->routeIs('dashboard.presensi-mengajar.*'),
                'prominent' => true,
            ],
            [
                'label' => 'Kelas',
                'icon' => 'fa-clipboard-check',
                'href' => $href('dashboard.presensi.kelas'),
                'permission' => 'menu_wali_kelas_presensi',
                'active' => request()->routeIs('dashboard.presensi.kelas'),
            ],
            [
                'label' => 'Profil',
                'icon' => 'fa-user',
                'href' => $href('dashboard.profile'),
                'active' => request()->routeIs('dashboard.profile'),
            ],
        ],
        'tendik' => [
            [
                'label' => 'Home',
                'icon' => 'fa-house',
                'href' => $href('dashboard.tendik'),
                'permission' => 'menu_dashboard',
                'active' => request()->routeIs('dashboard.tendik'),
            ],
            [
                'label' => 'Info',
                'icon' => 'fa-bullhorn',
                'href' => $href('dashboard.informasi.index'),
                'active' => request()->routeIs('dashboard.informasi.*'),
            ],
            [
                'label' => 'Aktivitas',
                'icon' => 'fa-clipboard-check',
                'href' => $href('dashboard.tendik.aktivitas.index'),
                'permission' => 'menu_aktivitas_tendik',
                'active' => request()->routeIs('dashboard.tendik.aktivitas.*'),
                'prominent' => true,
            ],
            [
                'label' => 'Laporan',
                'icon' => 'fa-file-lines',
                'href' => $href('dashboard.tendik.laporan.index'),
                'permission' => 'menu_laporan_tendik',
                'active' => request()->routeIs('dashboard.tendik.laporan.*'),
            ],
            [
                'label' => 'Profil',
                'icon' => 'fa-user',
                'href' => $href('dashboard.profile'),
                'active' => request()->routeIs('dashboard.profile'),
            ],
        ],
        'peserta_didik' => [
            [
                'label' => 'Home',
                'icon' => 'fa-house',
                'href' => $href('dashboard.peserta-didik'),
                'permission' => 'menu_dashboard',
                'active' =>
                    request()->routeIs('dashboard.peserta-didik') || request()->routeIs('dashboard.peserta_didik'),
            ],
            [
                'label' => 'Identitas',
                'icon' => 'fa-id-card',
                'href' => '?tab=identitas',
                'permission' => 'menu_dashboard',
                'active' => request('tab') === 'identitas',
            ],
            [
                'label' => 'Berkas',
                'icon' => 'fa-folder-open',
                'href' => '?tab=berkas',
                'permission' => 'menu_validasi_berkas',
                'active' => request('tab') === 'berkas',
                'prominent' => true,
            ],
            [
                'label' => 'Presensi',
                'icon' => 'fa-chart-column',
                'href' => $href('dashboard.peserta-didik.presensi.index'),
                'permission' => 'menu_riwayat_rfid',
                'active' => request()->routeIs('dashboard.peserta-didik.presensi.*'),
            ],
            [
                'label' => 'FAQ',
                'icon' => 'fa-circle-question',
                'href' => '?tab=faq',
                'permission' => 'menu_dashboard',
                'active' => request('tab') === 'faq',
            ],
        ],
    ];

    $items = $menus[$role] ?? $menus['peserta_didik'];
    $can = fn($key) => empty($key) || \App\Models\RolePermission::canAccess($user ?: $role, $key);
    $items = array_filter($items, function($item) use ($can) {
        return empty($item['permission']) || $can($item['permission']);
    });
@endphp

@if (!empty($items) && $can('menu_dashboard'))
<nav class="mobile-bottom-nav">
    @foreach ($items as $item)
        <a href="{{ $item['href'] }}"
            class="mobile-nav-item {{ !empty($item['prominent']) ? 'item-prominent' : '' }} {{ !empty($item['active']) ? 'active' : '' }}">
            <div class="{{ !empty($item['prominent']) ? 'prominent-btn' : 'mobile-nav-icon' }}">
                <i class="fas {{ $item['icon'] }}"></i>
            </div>
            <span class="mobile-nav-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
@endif
