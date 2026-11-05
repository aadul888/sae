@php
    $pengguna = session('user');
    $role = is_array($pengguna) ? ($pengguna['role'] ?? '') : ($pengguna->role ?? '');
    $bolehAkses = function (string $menu) use ($pengguna, $role) {
        if ($role === 'admin') return true;
        return \App\Models\RolePermission::canAccess($pengguna ?: $role, $menu, 'read');
    };

    $isDashboard = request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'persuratan';
    $isBuatSurat = request()->routeIs('dashboard.persuratan.keluar*') && (request('tab') === 'template' || request('tab') === 'autocrat');
    $isSuratMasuk = request()->routeIs('dashboard.persuratan.masuk*');
    $isSuratKeluar = request()->routeIs('dashboard.persuratan.keluar*') && request('tab') !== 'ekspedisi' && request('tab') !== 'template' && request('tab') !== 'autocrat';
    $isEkspedisi = request()->routeIs('dashboard.persuratan.keluar*') && request('tab') === 'ekspedisi';
    $isPengaturan = request()->routeIs('dashboard.persuratan.pengaturan*');

    $daftarTab = [
        [
            'izin'    => ['menu_persuratan'],
            'label'   => 'Dashboard',
            'ringkas' => 'Dashboard',
            'url'     => route('dashboard.tendik', ['bidang' => 'persuratan']),
            'icon'    => 'fas fa-gauge-high',
            'aktif'   => $isDashboard,
        ],
        [
            'izin'    => ['menu_surat_keluar', 'menu_persuratan'],
            'label'   => 'Buat Surat',
            'ringkas' => 'Buat Surat',
            'url'     => route('dashboard.persuratan.keluar.index', ['tab' => 'template']),
            'icon'    => 'fas fa-wand-magic-sparkles',
            'aktif'   => $isBuatSurat,
        ],
        [
            'izin'    => ['menu_surat_masuk', 'menu_persuratan'],
            'label'   => 'Surat Masuk',
            'ringkas' => 'Surat Masuk',
            'url'     => route('dashboard.persuratan.masuk.index'),
            'icon'    => 'fas fa-inbox',
            'aktif'   => $isSuratMasuk,
        ],
        [
            'izin'    => ['menu_surat_keluar', 'menu_persuratan'],
            'label'   => 'Surat Keluar',
            'ringkas' => 'Surat Keluar',
            'url'     => route('dashboard.persuratan.keluar.index'),
            'icon'    => 'fas fa-paper-plane',
            'aktif'   => $isSuratKeluar,
        ],
        [
            'izin'    => ['menu_surat_keluar', 'menu_persuratan'],
            'label'   => 'Buku Ekspedisi',
            'ringkas' => 'Ekspedisi',
            'url'     => route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']),
            'icon'    => 'fas fa-truck-fast',
            'aktif'   => $isEkspedisi,
        ],
        [
            'izin'    => ['menu_pengaturan_persuratan', 'menu_persuratan'],
            'label'   => 'Pengaturan & Arsip',
            'ringkas' => 'Pengaturan',
            'url'     => route('dashboard.persuratan.pengaturan.index'),
            'icon'    => 'fas fa-sliders',
            'aktif'   => $isPengaturan,
        ],
    ];

    $daftarTab = array_values(array_filter($daftarTab, function ($item) use ($bolehAkses) {
        foreach ($item['izin'] as $izin) {
            if ($bolehAkses($izin)) return true;
        }
        return false;
    }));
@endphp

@if (count($daftarTab) > 1)
    <div class="periode-nav-wrapper persuratan-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop" style="flex-wrap: wrap; gap: 6px;">
            @foreach ($daftarTab as $tab)
                <a href="{{ $tab['url'] }}"
                    class="periode-nav-tab {{ $tab['aktif'] ? 'active' : '' }}">
                    <i class="{{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-envelope-open-text text-primary me-1"></i> Modul Persuratan:</label>
                <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;"
                    aria-label="Pilih menu persuratan">
                    @foreach ($daftarTab as $tab)
                        <option value="{{ $tab['url'] }}" {{ $tab['aktif'] ? 'selected' : '' }}>{{ $tab['ringkas'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endif
