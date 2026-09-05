<header>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="nav-brand"
            style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="{{ asset('img/sae-logo.png') }}" alt="SAE Logo" style="height: 38px; width: auto; object-fit: contain;">
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="{{ url('/') }}#fitur" class="nav-link">Layanan</a>
            <a href="{{ url('/') }}#statistik" class="nav-link">Statistik</a>
            <a href="{{ url('/') }}#nisn" class="nav-link">Cek NISN</a>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Ganti Tema"
                title="Ganti Mode Gelap / Terang">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="{{ url('/login') }}" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size: 0.8rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk SSO
            </a>
        </div>
    </nav>
</header>
