{{-- 3. Peran: Tendik (Dengan Sub-menu per Bidang) --}}
@php
    $isAdminTendikActive =
        (request()->routeIs('dashboard.tendik*') &&
            !request()->routeIs('dashboard.tendik-aktif*') &&
            !request()->routeIs('dashboard.tendik-tidak-aktif*')) ||
        request()->routeIs('dashboard.persuratan.*') ||
        request()->routeIs('dashboard.kesiswaan.*') ||
        request()->routeIs('dashboard.kepegawaian.*') ||
        request()->routeIs('dashboard.sarpras.*') ||
        request()->routeIs('dashboard.laboran.*') ||
        request()->routeIs('dashboard.perpustakaan.*') ||
        request()->routeIs('dashboard.teknisi.*') ||
        request()->routeIs('dashboard.keamanan.*') ||
        request()->routeIs('dashboard.penjaga.*') ||
        request()->routeIs('dashboard.piket.*');
@endphp
<div class="dash-nav-group {{ $isAdminTendikActive ? 'open active-group' : '' }}">
    <button type="button" class="dash-nav-toggle">
        <div class="dash-nav-toggle-main">
            <span class="nav-icon"><i class="fas fa-fw fa-id-badge"></i></span>
            <span class="nav-label">Tendik</span>
        </div>
        <i class="fas fa-chevron-right arrow-icon"></i>
    </button>
    <div class="dash-nav-submenu">
        <a href="{{ route('dashboard.tendik') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && (!request()->has('bidang') || request('bidang') === 'umum') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
            <span class="nav-label">Dashboard Tendik</span>
        </a>
        <a href="{{ route('dashboard.tendik.target.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.target*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-bullseye"></i></span>
            <span class="nav-label">Target &amp; Capaian</span>
        </a>
        <a href="{{ route('dashboard.tendik.aktivitas.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.aktivitas*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
            <span class="nav-label">Input Aktivitas</span>
        </a>
        <a href="{{ route('dashboard.tendik.laporan.index') }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik.laporan*') ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-file-lines"></i></span>
            <span class="nav-label">Laporan Kinerja</span>
        </a>

        {{-- Sub-menu: Kepala TAS --}}
        <a href="{{ route('dashboard.tendik', ['bidang' => 'kepala-tas']) }}"
            class="dash-nav-sublink {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'kepala-tas' ? 'active' : '' }}">
            <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-tie"></i></span>
            <span class="nav-label">Kepala TAS</span>
        </a>

        {{-- Sub-submenu: Persuratan --}}
        @php
            $isNestedPersuratanActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'persuratan') ||
                request()->routeIs('dashboard.persuratan.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedPersuratanActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-envelope-open-text"></i></span>
                    <span class="nav-label">Persuratan</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'persuratan']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'persuratan' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Persuratan</span>
                </a>
                <a href="{{ route('dashboard.persuratan.masuk.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.masuk*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-inbox"></i></span>
                    <span class="nav-label">Surat Masuk</span>
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') && request('tab') !== 'ekspedisi' && request('tab') !== 'template' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-paper-plane"></i></span>
                    <span class="nav-label">Surat Keluar</span>
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') && request('tab') === 'ekspedisi' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-truck-fast"></i></span>
                    <span class="nav-label">Buku Ekspedisi</span>
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'template']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.keluar*') && (request('tab') === 'template' || request('tab') === 'autocrat') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i
                            class="fas fa-fw fa-wand-magic-sparkles"></i></span>
                    <span class="nav-label">Autocrat</span>
                </a>
                <a href="{{ route('dashboard.persuratan.pengaturan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.persuratan.pengaturan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-sliders"></i></span>
                    <span class="nav-label">Pengaturan &amp; Arsip</span>
                </a>
            </div>
        </div>

        {{-- Sub-submenu: Kesiswaan --}}
        @php
            $isNestedKesiswaanActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'kesiswaan') ||
                request()->routeIs('dashboard.kesiswaan.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedKesiswaanActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-user-graduate"></i></span>
                    <span class="nav-label">Kesiswaan</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'kesiswaan']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'kesiswaan' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Kesiswaan</span>
                </a>
                <a href="{{ route('dashboard.kesiswaan.peserta-didik.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.peserta-didik*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-users"></i></span>
                    <span class="nav-label">Peserta Didik</span>
                </a>
                <a href="{{ route('dashboard.kesiswaan.buku-induk.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.buku-induk*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-address-book"></i></span>
                    <span class="nav-label">Buku Induk</span>
                </a>
                <a href="{{ route('dashboard.kesiswaan.administrasi.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.administrasi*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-bookmark"></i></span>
                    <span class="nav-label">Administrasi</span>
                </a>
                <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.kedisiplinan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-shield-halved"></i></span>
                    <span class="nav-label">Kedisiplinan</span>
                </a>
                <a href="{{ route('dashboard.kesiswaan.kegiatan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.kegiatan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-people-group"></i></span>
                    <span class="nav-label">Kegiatan Siswa</span>
                </a>
                <a href="{{ route('dashboard.kesiswaan.prestasi.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kesiswaan.prestasi*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-trophy"></i></span>
                    <span class="nav-label">Prestasi</span>
                </a>
            </div>
        </div>

        {{-- Sub-submenu: Kepegawaian --}}
        @php
            $isNestedKepegawaianActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'kepegawaian') ||
                request()->routeIs('dashboard.kepegawaian.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedKepegawaianActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-id-card-alt"></i></span>
                    <span class="nav-label">Kepegawaian</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'kepegawaian']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'kepegawaian' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Kepegawaian</span>
                </a>
                <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'pegawai']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && (request('tab') === 'pegawai' || !request('tab')) ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-users"></i></span>
                    <span class="nav-label">Pegawai</span>
                </a>
                <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'berkas']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && request('tab') === 'berkas' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-folder-open"></i></span>
                    <span class="nav-label">Berkas Pegawai</span>
                </a>
                <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && request('tab') === 'kgb' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-business-time"></i></span>
                    <span class="nav-label">KGB Tracker</span>
                </a>
                <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'cuti']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.kepegawaian.*') && request('tab') === 'cuti' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-plane-departure"></i></span>
                    <span class="nav-label">Cuti &amp; Izin</span>
                </a>
            </div>
        </div>

        {{-- Sub-submenu: Sarpras & Aset --}}
        @php
            $isNestedSarprasActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'sarpras') ||
                request()->routeIs('dashboard.sarpras.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedSarprasActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-building"></i></span>
                    <span class="nav-label">Sarpras &amp; Aset</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'sarpras']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'sarpras' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Sarpras</span>
                </a>
                <a href="{{ route('dashboard.sarpras.ruang.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.ruang*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-door-open"></i></span>
                    <span class="nav-label">Ruang &amp; Gedung</span>
                </a>
                <a href="{{ route('dashboard.sarpras.aset.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.aset*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-boxes-stacked"></i></span>
                    <span class="nav-label">Inventaris &amp; Aset</span>
                </a>
                <a href="{{ route('dashboard.sarpras.peminjaman.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.peminjaman*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-hand-holding"></i></span>
                    <span class="nav-label">Peminjaman Sarpras</span>
                </a>
                @if ($can('menu_teknisi'))
                    <a href="{{ route('dashboard.teknisi.work-order') }}"
                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.work-order*') ? 'active' : '' }}">
                        <span class="nav-icon nested-icon"><i
                                class="fas fa-fw fa-screwdriver-wrench"></i></span>
                        <span class="nav-label">Perbaikan &amp; Work Order</span>
                    </a>
                @endif
                @if ($can('menu_penjaga'))
                    <a href="{{ route('dashboard.penjaga.kebersihan.index') }}"
                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.penjaga.kebersihan*') ? 'active' : '' }}">
                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-broom"></i></span>
                        <span class="nav-label">Kebersihan Fasilitas</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Sub-submenu: Laboratorium --}}
        @php
            $isNestedLaboranActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'laboran') ||
                request()->routeIs('dashboard.laboran.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedLaboranActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-flask"></i></span>
                    <span class="nav-label">Laboratorium</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.laboran.dashboard') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.dashboard') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Laboratorium</span>
                </a>
                <a href="{{ route('dashboard.laboran.inventaris.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.inventaris*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-vials"></i></span>
                <a href="{{ route('dashboard.laboran.bhp.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.bhp*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-vials"></i></span>
                    <span class="nav-label">Bahan Habis Pakai</span>
                </a>
                    <span class="nav-label">Alat &amp; Bahan Lab</span>
                </a>
                <a href="{{ route('dashboard.laboran.jadwal.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.jadwal*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-calendar-days"></i></span>
                    <span class="nav-label">Jadwal &amp; Pemakaian</span>
                </a>

                <a href="{{ route('dashboard.laboran.kebutuhan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.kebutuhan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-list"></i></span>
                    <span class="nav-label">Analisis Kebutuhan</span>
                </a>
                <a href="{{ route('dashboard.laboran.perawatan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.perawatan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i
                            class="fas fa-fw fa-screwdriver-wrench"></i></span>
                    <span class="nav-label">Perawatan &amp; K3</span>
                </a>
                <a href="{{ route('dashboard.laboran.pemeriksaan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.pemeriksaan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-list-check"></i></span>
                    <span class="nav-label">Pemeriksaan Ceklist</span>
                </a>
                <a href="{{ route('dashboard.laboran.pengadaan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.pengadaan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i
                            class="fas fa-fw fa-file-invoice-dollar"></i></span>
                    <span class="nav-label">Usulan Pengadaan</span>
                </a>
                <a href="{{ route('dashboard.laboran.kerusakan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.kerusakan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i
                            class="fas fa-fw fa-triangle-exclamation"></i></span>
                    <span class="nav-label">Berita Acara Kerusakan</span>
                </a>
                <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.peminjaman-alat*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-hand-holding"></i></span>
                    <span class="nav-label">Peminjaman Alat</span>
                </a>
                <a href="{{ route('dashboard.laboran.laporan-lengkap.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.laporan-lengkap*') ? 'active' : '' }}"
                    title="Tupoksi #8 — Laporan Rekapitulasi">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-file-lines"></i></span>
                    <span class="nav-label">Laporan Lengkap</span>
                </a>
                <a href="{{ route('dashboard.laboran.jurnal.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.jurnal*') ? 'active' : '' }}"
                    title="Tupoksi #8 — Jurnal Kegiatan">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book"></i></span>
                    <span class="nav-label">Jurnal Kegiatan</span>
                </a>
                <a href="{{ route('dashboard.laboran.kebersihan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.kebersihan*') ? 'active' : '' }}"
                    title="Tupoksi #9 — Kebersihan Lingkungan Produktif">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-broom"></i></span>
                    <span class="nav-label">Kebersihan Jurusan</span>
                </a>
                <a href="{{ route('dashboard.laboran.arsip.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.arsip*') ? 'active' : '' }}"
                    title="Tupoksi #7 — Arsip Dokumen (Ordner)">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-box-archive"></i></span>
                    <span class="nav-label">Arsip Dokumen</span>
                </a>
            </div>
        </div>

        {{-- Sub-submenu: Perpustakaan --}}
        @include('partials.dash-sidebar-perpustakaan')

        {{-- Sub-submenu: Teknisi & IT --}}
        @php
            $isNestedTeknisiActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'teknisi') ||
                request()->routeIs('dashboard.teknisi.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedTeknisiActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-tools"></i></span>
                    <span class="nav-label">Teknisi &amp; IT</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'teknisi']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'teknisi' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Teknisi</span>
                </a>
                <a href="{{ route('dashboard.sarpras.ruang.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.ruang*') ? 'active' : '' }}"
                    title="Master pemetaan tanggung jawab ruang &amp; gedung (diinput Administrasi Sarpras)">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-door-open"></i></span>
                    <span class="nav-label">Ruang &amp; Gedung</span>
                </a>
                @if ($can('menu_inventaris') || $can('menu_sarpras'))
                    <a href="{{ route('dashboard.sarpras.aset.index') }}"
                        class="dash-nav-nested-link {{ request()->routeIs('dashboard.sarpras.aset*') ? 'active' : '' }}"
                        title="Inventaris &amp; aset sarpras sekolah">
                        <span class="nav-icon nested-icon"><i class="fas fa-fw fa-boxes-stacked"></i></span>
                        <span class="nav-label">Inventaris &amp; Aset</span>
                    </a>
                @endif
                <a href="{{ route('dashboard.teknisi.work-order') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.work-order*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-wrench"></i></span>
                    <span class="nav-label">Work Order &amp; Perbaikan</span>
                </a>
                <a href="{{ route('dashboard.teknisi.pemeliharaan') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.pemeliharaan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                    <span class="nav-label">Pemeliharaan Preventif</span>
                </a>
                <a href="{{ route('dashboard.teknisi.area-tugas.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.area-tugas*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-list"></i></span>
                    <span class="nav-label">Jadwal Area Tugas</span>
                </a>
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
                <a href="{{ route('dashboard.teknisi.jurnal-perbaikan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.jurnal-perbaikan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-screwdriver-wrench"></i></span>
                    <span class="nav-label">Jurnal Perbaikan Sarpras</span>
                </a>
                <a href="{{ route('dashboard.teknisi.supervisi.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.teknisi.supervisi*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                    <span class="nav-label">Supervisi Teknisi</span>
                </a>
                @include('partials.dash-sidebar-teknisi-modul', ['linkClass' => 'dash-nav-nested-link', 'iconClass' => 'nested-icon'])
            </div>
        </div>

        {{-- Sub-submenu: Keamanan & Tamu --}}
        @php
            $isNestedKeamananActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'keamanan') ||
                request()->routeIs('dashboard.keamanan.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedKeamananActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-shield-halved"></i></span>
                    <span class="nav-label">Keamanan &amp; Tamu</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'keamanan']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'keamanan' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Keamanan</span>
                </a>
                <a href="{{ route('dashboard.keamanan.izin.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.izin*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-shield-alt"></i></span>
                    <span class="nav-label">Verifikasi e-Izin Gerbang</span>
                </a>
                <a href="{{ route('dashboard.keamanan.presensi.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.presensi*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-id-card-alt"></i></span>
                    <span class="nav-label">Monitoring Presensi Siswa</span>
                </a>
                <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.buku-tamu*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-open"></i></span>
                    <span class="nav-label">Buku Tamu Pos Depan</span>
                </a>
                <a href="{{ route('dashboard.keamanan.patroli.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.keamanan.patroli*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-shield-virus"></i></span>
                    <span class="nav-label">Patroli &amp; Insiden</span>
                </a>
                <a href="{{ route('presensi.scan') }}" target="_blank" class="dash-nav-nested-link">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-qrcode"></i></span>
                    <span class="nav-label">Pos Scanner RFID</span>
                </a>
            </div>
        </div>

        {{-- Sub-submenu: Fasilitas & Penjaga --}}
        @php
            $isNestedPenjagaActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'penjaga') ||
                request()->routeIs('dashboard.penjaga.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedPenjagaActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-broom"></i></span>
                    <span class="nav-label">Fasilitas &amp; Penjaga</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'penjaga']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'penjaga' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Penjaga</span>
                </a>
                <a href="{{ route('dashboard.penjaga.kebersihan.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.penjaga.kebersihan*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-check"></i></span>
                    <span class="nav-label">Checklist Kebersihan</span>
                </a>
                <a href="{{ route('dashboard.penjaga.ronda.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.penjaga.ronda*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-moon"></i></span>
                    <span class="nav-label">Buku Jaga &amp; Ronda</span>
                </a>
            </div>
        </div>

        {{-- Sub-submenu: Guru Piket --}}
        @php
            $isNestedPiketActive =
                (request()->routeIs('dashboard.tendik') && request('bidang') === 'piket') ||
                request()->routeIs('dashboard.piket.*');
        @endphp
        <div class="dash-nav-nested-group {{ $isNestedPiketActive ? 'open active-group' : '' }}">
            <button type="button" class="dash-nav-nested-toggle">
                <div class="dash-nav-nested-toggle-main">
                    <span class="nav-icon sub-icon"><i class="fas fa-fw fa-clipboard-user"></i></span>
                    <span class="nav-label">Guru Piket</span>
                </div>
                <i class="fas fa-chevron-right nested-arrow-icon"></i>
            </button>
            <div class="dash-nav-nested-menu">
                <a href="{{ route('dashboard.tendik', ['bidang' => 'piket']) }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.tendik') && request('bidang') === 'piket' ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard Guru Piket</span>
                </a>
                <a href="{{ route('dashboard.presensi-mengajar.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.presensi-mengajar*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                    <span class="nav-label">Presensi Guru Mengajar</span>
                </a>
                <a href="{{ route('dashboard.presensi.kelas') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.presensi.kelas*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-users-viewfinder"></i></span>
                    <span class="nav-label">Presensi Kelas</span>
                </a>
                <a href="{{ route('dashboard.agenda-kbm.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.agenda-kbm*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book-open-reader"></i></span>
                    <span class="nav-label">Jurnal &amp; Agenda KBM</span>
                </a>
                <a href="{{ route('dashboard.piket.izin.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.piket.izin*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-ticket-alt"></i></span>
                    <span class="nav-label">e-Izin Keluar Masuk Siswa</span>
                </a>
                <a href="{{ route('dashboard.piket.jurnal.index') }}"
                    class="dash-nav-nested-link {{ request()->routeIs('dashboard.piket.jurnal*') ? 'active' : '' }}">
                    <span class="nav-icon nested-icon"><i class="fas fa-fw fa-clipboard-list"></i></span>
                    <span class="nav-label">Jurnal Guru Piket</span>
                </a>
            </div>
        </div>
    </div>
</div>
