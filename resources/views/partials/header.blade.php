<header>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="nav-brand" style="text-decoration: none; display: flex; align-items: center; gap: 8px;">
            <img id="navLogo" src="{{ asset('img/logo-dark.png') }}" data-dark="{{ asset('img/logo-dark.png') }}" data-light="{{ asset('img/logo-light.png') }}" alt="SAE" class="nav-logo" onerror="this.style.display='none'; document.getElementById('navBrandText').style.display='flex';" style="height: 32px;">
            <div id="navBrandText" style="display: none; align-items: center; gap: 6px; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.5px; color: var(--text-color);">
                <span style="background: linear-gradient(135deg, #3b82f6, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">SAE</span>
            </div>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="{{ url('/') }}#fitur" class="nav-link">Layanan</a>
            <a href="{{ url('/') }}#statistik" class="nav-link">Statistik</a>
            <a href="{{ url('/') }}#nisn" class="nav-link">Cek NISN</a>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Ganti Tema" title="Ganti Mode Gelap / Terang">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="{{ url('/login') }}" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk SSO
            </a>
        </div>
    </nav>
</header>
