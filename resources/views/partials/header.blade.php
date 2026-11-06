<header class="site-header-wrap">
    @php
        $navLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
        $navLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
    @endphp
    <nav class="navbar">
        <a href="{{ url('/') }}" class="nav-brand"
            style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img id="navLogo" src="{{ $navLogoDark }}" data-dark="{{ $navLogoDark }}" data-light="{{ $navLogoLight }}"
                alt="SAE Logo" style="height: 38px; width: auto; object-fit: contain;"
                onerror="this.onerror=null; this.src='/img/logo-dark.png';">
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="{{ url('/') }}" class="nav-link {{ request()->is('/') ? 'active' : '' }}">Beranda</a>
            <a href="{{ route('berita.index') }}" class="nav-link {{ request()->routeIs('berita.*') ? 'active' : '' }}">Berita</a>
            <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">Tentang</a>
            <button type="button" class="btn-mega-dropdown" id="btnMegaLayanan" onclick="toggleMegaLayanan(event)">
                <span>Layanan</span>
                <i class="fas fa-chevron-down nav-chevron-icon" id="megaLayananChevron"></i>
            </button>
            <button type="button" class="btn-pwa-install btn" id="btnPwaInstallNav" onclick="installSaePwa()"
                style="display: none; padding: 0.45rem 0.9rem; font-size: var(--text-sm, 0.8125rem); background: rgba(16,185,129,0.14); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-lg, 12px); align-items: center; gap: 6px; cursor: pointer;"
                title="Instal Aplikasi SAE ke Perangkat">
                <i class="fas fa-download"></i> <span>Instal App</span>
            </button>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Ganti Tema"
                title="Ganti Mode Gelap / Terang">
                <i class="fas fa-moon"></i>
            </button>
            <a href="{{ url('/login') }}" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: var(--text-sm, 0.8125rem);">
                <i class="fas fa-right-to-bracket"></i> Masuk Portal
            </a>
        </div>
    </nav>

    <!-- Mega Dropdown Backdrop -->
    <div id="megaMenuBackdrop" class="mega-dropdown-backdrop" style="display: none;" onclick="closeMegaLayanan()"></div>

    <!-- Mega Dropdown Panel (Persis Tampilan Dropdown Disdik Jabar) -->
    <div id="megaMenuLayanan" class="mega-dropdown-panel" style="display: none;">
        <div class="mega-dropdown-inner">
            <!-- Header Panel -->
            <div class="mega-dropdown-header">
                <div class="mega-dropdown-title">Layanan</div>
                <button type="button" class="mega-dropdown-close" onclick="closeMegaLayanan()" aria-label="Tutup Dropdown">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Grid Kartu Aplikasi (4 Layanan Publik Utama) -->
            <div class="mega-dropdown-grid">
                <!-- 1. Presensi Live Scanner (Utama) -->
                <a href="{{ route('presensi.scan') }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(16, 185, 129, 0.14); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.25);">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Scanner Presensi</span>
                            <span class="badge-live-dot">LIVE</span>
                        </div>
                        <div class="mega-item-desc">
                            Absensi RFID &amp; visual live webcam realtime peserta didik dan GTK.
                        </div>
                    </div>
                </a>

                <!-- 2. Perpustakaan Digital (Kiosk Katalog Publik) -->
                <a href="{{ route('perpustakaan.index') }}" class="mega-item-card" target="_blank">
                    <div class="mega-item-icon" style="background: rgba(236, 72, 153, 0.14); color: #ec4899; border: 1px solid rgba(236, 72, 153, 0.25);">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Perpustakaan Digital</span>
                            <span class="badge-live-dot">KIOSK</span>
                        </div>
                        <div class="mega-item-desc">
                            Katalog buku publik, peminjaman mandiri &amp; gerbang kartu anggota digital.
                        </div>
                    </div>
                </a>

                <!-- 3. Terminal Kiosk e-Izin (Pos Gerbang) -->
                <a href="{{ route('kiosk.e-izin') }}" class="mega-item-card" target="_blank">
                    <div class="mega-item-icon" style="background: rgba(14, 165, 233, 0.14); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Terminal Kiosk e-Izin</span>
                            <span class="badge-live-dot">KIOSK</span>
                        </div>
                        <div class="mega-item-desc">
                            Pos gerbang mandiri verifikasi barcode, QR code &amp; tap kartu RFID izin keluar-masuk.
                        </div>
                    </div>
                </a>

                <!-- 4. Buku Tamu Digital (e-Tamu Mandiri) -->
                <a href="{{ route('tamu.public') }}" class="mega-item-card" target="_blank">
                    <div class="mega-item-icon" style="background: rgba(245, 158, 11, 0.14); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.25);">
                        <i class="fas fa-clipboard-user"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Buku Tamu (e-Tamu)</span>
                            <span class="badge-live-dot">MANDIRI</span>
                        </div>
                        <div class="mega-item-desc">
                            Pencatatan kunjungan mandiri tamu sekolah, foto webcam &amp; deteksi orang tua.
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</header>
