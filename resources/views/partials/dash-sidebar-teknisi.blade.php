{{-- 7. Submenu Per-Bidang: Teknisi & Maintenance --}}
@if ($can('menu_teknisi'))
    <div class="dash-nav-nested-group {{ $isTeknisiActive ? 'open active-group' : '' }}">
        <button type="button" class="dash-nav-nested-toggle">
            <div class="dash-nav-nested-toggle-main">
                <span class="nav-icon sub-icon"><i class="fas fa-fw fa-tools"></i></span>
                <span class="nav-label">Teknisi &amp; Sarpras</span>
            </div>
            <i class="fas fa-chevron-right nested-arrow-icon"></i>
        </button>
        <div class="dash-nav-nested-menu">
            @php $grupJudul = 'padding: 8px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;'; @endphp
            <a href="{{ route('dashboard.tendik', ['bidang' => 'teknisi']) }}"
                class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'teknisi' ? 'active' : '' }}">
                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard Teknisi</span>
            </a>

            {{-- ==== KELOMPOK: MASTER & REFERENSI ==== --}}
            <div style="{{ $grupJudul }}">Master &amp; Referensi</div>
            @if ($can('menu_sarpras_ruang') || $can('menu_sarpras') || $can('menu_teknisi'))
                <a href="{{ route('dashboard.sarpras.ruang.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.ruang*') ? 'active' : '' }}"
                    title="Master pemetaan tanggung jawab ruang &amp; gedung (diinput Administrasi Sarpras)">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-door-open"></i></span>
                    <span class="nav-label">Ruang &amp; Gedung</span>
                </a>
            @endif
            @if ($can('menu_inventaris') || $can('menu_sarpras'))
                <a href="{{ route('dashboard.sarpras.aset.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.aset*') ? 'active' : '' }}"
                    title="Inventaris &amp; aset sarpras sekolah">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-boxes-stacked"></i></span>
                    <span class="nav-label">Inventaris &amp; Aset</span>
                </a>
            @endif
            @include('partials.dash-sidebar-teknisi-modul', ['linkClass' => 'dash-nav-nested-link', 'iconClass' => 'nested-icon', 'grup' => 'master', 'judul' => false])

            {{-- ==== KELOMPOK: PERENCANAAN TUGAS ==== --}}
            <div style="{{ $grupJudul }}">Perencanaan Tugas</div>
            @if ($can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.area-tugas.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.area-tugas*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-list"></i></span>
                    <span class="nav-label">Jadwal Area Tugas</span>
                </a>
            @endif

            {{-- ==== KELOMPOK: PELAKSANAAN HARIAN ==== --}}
            <div style="{{ $grupJudul }}">Pelaksanaan Harian</div>
            @if ($can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.kebersihan-ruang.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.kebersihan-ruang*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-broom"></i></span>
                    <span class="nav-label">Kebersihan Ruang</span>
                </a>
                <a href="{{ route('dashboard.teknisi.kebersihan-rapat.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.kebersihan-rapat*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-people-roof"></i></span>
                    <span class="nav-label">Kebersihan Ruang Rapat</span>
                </a>
                <a href="{{ route('dashboard.teknisi.sarana-rapat.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.sarana-rapat*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-chair"></i></span>
                    <span class="nav-label">Sarana Kegiatan Rapat</span>
                </a>
            @endif

            {{-- ==== KELOMPOK: PERBAIKAN & PEMELIHARAAN ==== --}}
            <div style="{{ $grupJudul }}">Perbaikan &amp; Pemeliharaan</div>
            @if ($can('menu_teknisi_wo') || $can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.work-order') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.work-order*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-wrench"></i></span>
                    <span class="nav-label">Work Order &amp; Perbaikan</span>
                </a>
            @endif
            @if ($can('menu_teknisi_pemeliharaan') || $can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.pemeliharaan') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.pemeliharaan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                    <span class="nav-label">Pemeliharaan Preventif</span>
                </a>
            @endif
            @if ($can('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.jurnal-perbaikan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.jurnal-perbaikan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-screwdriver-wrench"></i></span>
                    <span class="nav-label">Jurnal Perbaikan Sarpras</span>
                </a>
            @endif

            {{-- ==== KELOMPOK: INSPEKSI & MODUL LAPANGAN ==== --}}
            @if ($can('menu_teknisi'))
                @include('partials.dash-sidebar-teknisi-modul', ['linkClass' => 'dash-nav-nested-link', 'iconClass' => 'nested-icon', 'grup' => ['inspeksi', 'tindak']])
            @endif
        </div>
    </div>
@endif
