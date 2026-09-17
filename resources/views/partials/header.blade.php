<header>
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
            <a href="{{ url('/') }}#fitur" class="nav-link">Layanan</a>
            <a href="{{ url('/') }}#statistik" class="nav-link">Statistik</a>
            <a href="{{ url('/') }}#nisn" class="nav-link">Cek NISN</a>
            <a href="{{ route('presensi.scan') }}" class="nav-link"><i class="fas fa-qrcode me-1"></i> Presensi</a>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Ganti Tema"
                title="Ganti Mode Gelap / Terang">
                <i class="fas fa-moon"></i>
            </button>
            <a href="{{ url('/login') }}" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                <i class="fas fa-right-to-bracket"></i> Masuk Portal
            </a>
        </div>
    </nav>
</header>
