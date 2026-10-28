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
                        <a href="{{ route('dashboard.laboran.dashboard') }}"
                            class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.dashboard') ? 'active' : '' }}">
                            <span class="nav-icon nested-icon"><i class="fas fa-fw fa-gauge-high"></i></span>
                            <span class="nav-label">Dashboard Laboratorium</span>
                        </a>

                        {{-- ==== KELOMPOK: ADMINISTRASI & PERENCANAAN ==== --}}
                        <div
                            style="padding: 6px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;">
                            Administrasi &amp; Perencanaan
                        </div>
                        @if ($can('menu_laboran_peserta_didik') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.peserta-didik.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.peserta-didik*') ? 'active' : '' }}"
                                title="Data peserta didik pada jurusan Anda (baca-saja)">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-user-graduate"></i></span>
                                <span class="nav-label">Data Peserta Didik</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_kebutuhan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.kebutuhan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.kebutuhan*') ? 'active' : '' }}"
                                title="Tupoksi #6 Ã¢â‚¬â€ Pendataan Kebutuhan Alat &amp; Bahan">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-clipboard-list"></i></span>
                                <span class="nav-label">Analisis &amp; Kebutuhan</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_pengadaan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.pengadaan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.pengadaan*') ? 'active' : '' }}"
                                title="Tupoksi #6 Ã¢â‚¬â€ Daftar Usulan Pengadaan">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-file-invoice-dollar"></i></span>
                                <span class="nav-label">Usulan Pengadaan</span>
                            </a>
                        @endif

                        {{-- ==== KELOMPOK: LAYANAN PRAKTIK ==== --}}
                        <div
                            style="padding: 8px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;">
                            Layanan Praktik
                        </div>
                        @if ($can('menu_laboran_jadwal') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.jadwal.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.jadwal*') ? 'active' : '' }}"
                                title="Tupoksi #1 Ã¢â‚¬â€ Jadwal Penggunaan Lab/Bengkel (Mingguan / Semester) + Cetak">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-calendar-days"></i></span>
                                <span class="nav-label">Jadwal &amp; Pemakaian Lokasi</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_peminjaman') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.peminjaman-alat*') ? 'active' : '' }}"
                                title="Tupoksi #7 Ã¢â‚¬â€ Kartu Peminjaman Alat">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-hand-holding"></i></span>
                                <span class="nav-label">Peminjaman Alat</span>
                            </a>
                        @endif

                        {{-- ==== KELOMPOK: PEMELIHARAAN & INVENTARIS ==== --}}
                        <div
                            style="padding: 8px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;">
                            Pemeliharaan &amp; Inventaris
                        </div>
                        @if ($can('menu_laboran_inventaris') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.inventaris.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.inventaris*') ? 'active' : '' }}"
                                title="Tupoksi #4 - Buku Induk Inventaris Alat Praktik">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-microscope"></i></span>
                                <span class="nav-label">Inventaris Alat</span>
                            </a>
                            <a href="{{ route('dashboard.laboran.bhp.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.bhp*') ? 'active' : '' }}"
                                title="Tupoksi #4 - Buku Bahan Habis Pakai">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-vials"></i></span>
                                <span class="nav-label">Bahan Habis Pakai</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_perawatan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.perawatan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.perawatan*') ? 'active' : '' }}"
                                title="Tupoksi #3 Ã¢â‚¬â€ Kartu Perawatan &amp; Penataan K3">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-screwdriver-wrench"></i></span>
                                <span class="nav-label">Perawatan &amp; K3</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_pemeriksaan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.pemeriksaan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.pemeriksaan*') ? 'active' : '' }}"
                                title="Tupoksi #5 Ã¢â‚¬â€ Form Checklist Sebelum/Sesudah Pakai">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-list-check"></i></span>
                                <span class="nav-label">Pemeriksaan Ceklist</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_kebersihan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.kebersihan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.kebersihan*') ? 'active' : '' }}"
                                title="Tupoksi #9 Ã¢â‚¬â€ Dokumentasi Kebersihan Lingkungan Produktif">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-broom"></i></span>
                                <span class="nav-label">Kebersihan Jurusan</span>
                            </a>
                        @endif

                        {{-- ==== KELOMPOK: PELAPORAN & ARSIP ==== --}}
                        <div
                            style="padding: 8px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;">
                            Pelaporan &amp; Arsip
                        </div>
                        @if ($can('menu_laboran_kerusakan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.kerusakan.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.kerusakan*') ? 'active' : '' }}"
                                title="Tupoksi #7 Ã¢â‚¬â€ Ordner Berita Acara Kerusakan Alat">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-triangle-exclamation"></i></span>
                                <span class="nav-label">Berita Acara Kerusakan</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_jurnal') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.jurnal.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.jurnal*') ? 'active' : '' }}"
                                title="Tupoksi #8 Ã¢â‚¬â€ Jurnal Kegiatan Harian">
                                <span class="nav-icon nested-icon"><i class="fas fa-fw fa-book"></i></span>
                                <span class="nav-label">Jurnal Kegiatan</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_laporan') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.laporan-lengkap.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.laporan-lengkap*') ? 'active' : '' }}"
                                title="Tupoksi #8 Ã¢â‚¬â€ Laporan Rekapitulasi Penggunaan &amp; Kerusakan">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-file-lines"></i></span>
                                <span class="nav-label">Laporan Lengkap</span>
                            </a>
                        @endif
                        @if ($can('menu_laboran_arsip') || $can('menu_laboran'))
                            <a href="{{ route('dashboard.laboran.arsip.index') }}"
                                class="dash-nav-nested-link {{ request()->routeIs('dashboard.laboran.arsip*') ? 'active' : '' }}"
                                title="Tupoksi #7 Ã¢â‚¬â€ Arsip Dokumen (Ordner Digital)">
                                <span class="nav-icon nested-icon"><i
                                        class="fas fa-fw fa-box-archive"></i></span>
                                <span class="nav-label">Arsip Dokumen</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

