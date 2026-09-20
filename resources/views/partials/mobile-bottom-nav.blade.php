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
                'active' => request()->routeIs('dashboard.admin'),
            ],
            [
                'label' => 'User',
                'icon' => 'fa-users-gear',
                'href' => $href('dashboard.pengguna.index'),
                'active' => request()->routeIs('dashboard.pengguna.*'),
            ],
            [
                'label' => 'Presensi',
                'icon' => 'fa-fingerprint',
                'href' => $href('dashboard.presensi.index'),
                'active' => request()->routeIs('dashboard.presensi.*'),
                'prominent' => true,
            ],
            [
                'label' => 'Info',
                'icon' => 'fa-bullhorn',
                'href' => $href('dashboard.pengumuman.index'),
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
                'active' => request()->routeIs('dashboard.guru'),
            ],
            [
                'label' => 'Agenda',
                'icon' => 'fa-book-open',
                'href' => $href('dashboard.agenda-kbm.index'),
                'active' => request()->routeIs('dashboard.agenda-kbm.*'),
            ],
            [
                'label' => 'Mengajar',
                'icon' => 'fa-calendar-check',
                'href' => $href('dashboard.presensi-mengajar.index'),
                'active' => request()->routeIs('dashboard.presensi-mengajar.*'),
                'prominent' => true,
            ],
            [
                'label' => 'Kelas',
                'icon' => 'fa-clipboard-check',
                'href' => $href('dashboard.presensi.kelas'),
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
                'active' => request()->routeIs('dashboard.tendik'),
            ],
            [
                'label' => 'Info',
                'icon' => 'fa-bell',
                'href' => $href('dashboard.informasi.index'),
                'active' => request()->routeIs('dashboard.informasi.*'),
            ],
            [
                'label' => 'Aktivitas',
                'icon' => 'fa-clipboard-check',
                'href' => $href('dashboard.tendik.aktivitas.index'),
                'active' => request()->routeIs('dashboard.tendik.aktivitas.*'),
                'prominent' => true,
            ],
            [
                'label' => 'Laporan',
                'icon' => 'fa-file-lines',
                'href' => $href('dashboard.tendik.laporan.index'),
                'active' => request()->routeIs('dashboard.tendik.laporan.*') || request()->routeIs('dashboard.tendik.presensi.*'),
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
                'active' =>
                    request()->routeIs('dashboard.peserta-didik') || request()->routeIs('dashboard.peserta_didik'),
            ],
            [
                'label' => 'Identitas',
                'icon' => 'fa-id-card',
                'href' => '?tab=identitas',
                'active' => request('tab') === 'identitas',
            ],
            [
                'label' => 'Berkas',
                'icon' => 'fa-folder-open',
                'href' => '?tab=berkas',
                'active' => request('tab') === 'berkas',
                'prominent' => true,
            ],
            [
                'label' => 'Presensi',
                'icon' => 'fa-chart-column',
                'href' => $href('dashboard.peserta-didik.presensi.index'),
                'active' => request()->routeIs('dashboard.peserta-didik.presensi.*'),
            ],
            [
                'label' => 'FAQ',
                'icon' => 'fa-circle-question',
                'href' => '?tab=faq',
                'active' => request('tab') === 'faq',
            ],
        ],
    ];

    $items = $menus[$role] ?? $menus['peserta_didik'];
@endphp

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
