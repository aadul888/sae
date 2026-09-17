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
            <button type="button" class="btn-mega-dropdown" id="btnMegaLayanan" onclick="toggleMegaLayanan(event)">
                <span>Layanan</span>
                <i class="fas fa-chevron-down nav-chevron-icon" id="megaLayananChevron"></i>
            </button>
            <button type="button" class="btn-pwa-install btn" id="btnPwaInstallNav" onclick="installSaePwa()"
                style="display: none; padding: 0.45rem 0.9rem; font-size: 0.8rem; background: rgba(16,185,129,0.14); color: #10b981; border: 1px solid rgba(16,185,129,0.25); border-radius: 10px; align-items: center; gap: 6px; cursor: pointer;"
                title="Instal Aplikasi SAE ke Perangkat">
                <i class="fas fa-download"></i> <span>Instal App</span>
            </button>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Ganti Tema"
                title="Ganti Mode Gelap / Terang">
                <i class="fas fa-moon"></i>
            </button>
            <a href="{{ url('/login') }}" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
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

            <!-- Grid Kartu Aplikasi -->
            <div class="mega-dropdown-grid">
                <!-- 1. Presensi Live Scanner (Utama) -->
                <a href="{{ route('presensi.scan') }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(16, 185, 129, 0.14); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Terminal Scanner Presensi</span>
                            <span class="badge-live-dot">LIVE</span>
                        </div>
                        <div class="mega-item-desc">
                            Pemindaian absensi kartu RFID &amp; visual webcam live realtime peserta didik dan GTK.
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</header>
