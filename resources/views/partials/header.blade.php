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

            <!-- Grid Kartu Aplikasi (Grid 4x2) -->
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

                <!-- 2. Terminal Kiosk e-Izin (Pos Gerbang) -->
                <a href="{{ route('kiosk.e-izin') }}" class="mega-item-card" target="_blank">
                    <div class="mega-item-icon" style="background: rgba(14, 165, 233, 0.14); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.25);">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Kiosk e-Izin</span>
                            <span class="badge-live-dot">KIOSK</span>
                        </div>
                        <div class="mega-item-desc">
                            Verifikasi barcode, QR code &amp; tap kartu RFID izin keluar-masuk gerbang.
                        </div>
                    </div>
                </a>

                <!-- 3. Perpustakaan Digital (Kiosk Katalog Publik) -->
                <a href="{{ route('perpustakaan.index') }}" class="mega-item-card" target="_blank">
                    <div class="mega-item-icon" style="background: rgba(236, 72, 153, 0.14); color: #ec4899; border: 1px solid rgba(236, 72, 153, 0.25);">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Perpustakaan</span>
                            <span class="badge-live-dot">KIOSK</span>
                        </div>
                        <div class="mega-item-desc">
                            Katalog buku publik, peminjaman mandiri &amp; gerbang kartu anggota digital.
                        </div>
                    </div>
                </a>

                <!-- 4. Portal Orang Tua -->
                <a href="{{ route('login', ['tab' => 'ortu']) }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(16, 185, 129, 0.14); color: var(--success, #10b981); border: 1px solid rgba(16, 185, 129, 0.25);">
                        <i class="fas fa-people-roof"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Portal Orang Tua</span>
                            <span class="badge-live-dot">PORTAL</span>
                        </div>
                        <div class="mega-item-desc">
                            Pantau absensi harian dan status izin anak sekolah secara langsung.
                        </div>
                    </div>
                </a>

                <!-- 5. Pengecekan Data Siswa (NISN) -->
                <a href="{{ url('/#nisn') }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(59, 130, 246, 0.14); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.25);">
                        <i class="fas fa-id-card-clip"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Pengecekan NISN</span>
                            <span class="badge-live-dot">VERIFIKASI</span>
                        </div>
                        <div class="mega-item-desc">
                            Verifikasi keaktifan resmi status peserta didik lewat 10 digit NISN.
                        </div>
                    </div>
                </a>

                <!-- 6. Kelulusan & Legalisir SKL -->
                <a href="{{ url('/#fitur') }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(245, 158, 11, 0.14); color: var(--warning, #f59e0b); border: 1px solid rgba(245, 158, 11, 0.25);">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Kelulusan &amp; SKL</span>
                            <span class="badge-live-dot">SKL</span>
                        </div>
                        <div class="mega-item-desc">
                            Pengumuman online, unduh SKL bertanda tangan QR &amp; validasi berkas.
                        </div>
                    </div>
                </a>

                <!-- 7. Agenda & Akademik -->
                <a href="{{ url('/#fitur') }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(139, 92, 246, 0.14); color: var(--purple, #8b5cf6); border: 1px solid rgba(139, 92, 246, 0.25);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Agenda Akademik</span>
                            <span class="badge-live-dot">AGENDA</span>
                        </div>
                        <div class="mega-item-desc">
                            Jadwal pelajaran kelas mingguan, kalender kegiatan &amp; event sekolah.
                        </div>
                    </div>
                </a>

                <!-- 8. Masuk Portal SAE -->
                <a href="{{ route('login') }}" class="mega-item-card">
                    <div class="mega-item-icon" style="background: rgba(99, 102, 241, 0.14); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.25);">
                        <i class="fas fa-right-to-bracket"></i>
                    </div>
                    <div class="mega-item-text">
                        <div class="mega-item-title">
                            <span>Masuk Portal</span>
                            <span class="badge-live-dot">SSO</span>
                        </div>
                        <div class="mega-item-desc">
                            Akses terpusat untuk Siswa, Guru, Wali Kelas, Satpam, dan Pengurus.
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</header>
