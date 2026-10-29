{{-- Partial: Tab navigasi sub-modul Perpustakaan.
     Urutan tab mengikuti alur kerja pustakawan: siapkan koleksi -> daftarkan anggota ->
     layani sirkulasi -> catat kunjungan -> susun laporan. --}}
@php
    $penggunaPerpus = session('user');
    $bolehTabPerpus = fn(string $key) => \App\Models\RolePermission::canAccess($penggunaPerpus, 'menu_perpustakaan', 'read') ||
        \App\Models\RolePermission::canAccess($penggunaPerpus, $key, 'read');

    $tabPerpus = [
        [
            'label' => 'Katalog & Koleksi',
            'ringkas' => 'Katalog & Koleksi',
            'icon' => 'fas fa-book',
            'url' => route('dashboard.perpustakaan.koleksi.index'),
            'aktif' => request()->routeIs('dashboard.perpustakaan.koleksi*') || request()->routeIs('dashboard.perpustakaan.import*'),
            'izin' => 'menu_perpustakaan_koleksi',
        ],
        [
            'label' => 'Anggota & Kartu',
            'ringkas' => 'Anggota & Kartu',
            'icon' => 'fas fa-id-card',
            'url' => route('dashboard.perpustakaan.anggota.index'),
            'aktif' => request()->routeIs('dashboard.perpustakaan.anggota*'),
            'izin' => 'menu_perpustakaan_anggota',
        ],
        [
            'label' => 'Sirkulasi & Denda',
            'ringkas' => 'Sirkulasi & Denda',
            'icon' => 'fas fa-exchange-alt',
            'url' => route('dashboard.perpustakaan.sirkulasi'),
            'aktif' => request()->routeIs('dashboard.perpustakaan.sirkulasi*'),
            'izin' => 'menu_perpustakaan_sirkulasi',
        ],
        [
            'label' => 'Buku Kunjungan',
            'ringkas' => 'Buku Kunjungan',
            'icon' => 'fas fa-user-check',
            'url' => route('dashboard.perpustakaan.kunjungan'),
            'aktif' => request()->routeIs('dashboard.perpustakaan.kunjungan*'),
            'izin' => 'menu_perpustakaan_kunjungan',
        ],
        [
            'label' => 'Laporan & Rekap',
            'ringkas' => 'Laporan & Rekap',
            'icon' => 'fas fa-chart-line',
            'url' => route('dashboard.perpustakaan.laporan.index'),
            'aktif' => request()->routeIs('dashboard.perpustakaan.laporan*'),
            'izin' => 'menu_perpustakaan_laporan',
        ],
    ];

    $tabPerpus = array_values(array_filter($tabPerpus, fn(array $tab) => $bolehTabPerpus($tab['izin'])));
@endphp

@if (count($tabPerpus) > 1)
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop" style="flex-wrap: wrap; gap: 6px;">
            @foreach ($tabPerpus as $tab)
                <a href="{{ $tab['url'] }}"
                    class="periode-nav-tab {{ $tab['aktif'] ? 'active' : '' }}">
                    <i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-book-open text-primary me-1"></i> Menu Pengelolaan Perpustakaan:</label>
                <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;"
                    aria-label="Pilih menu pengelolaan perpustakaan">
                    @foreach ($tabPerpus as $tab)
                        <option value="{{ $tab['url'] }}" {{ $tab['aktif'] ? 'selected' : '' }}>
                            {{ $tab['ringkas'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endif
