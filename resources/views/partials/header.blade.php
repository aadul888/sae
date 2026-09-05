<header>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="nav-brand"
            style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #6366f1); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);">
                <i class="fa-solid fa-graduation-cap" style="color: #ffffff; font-size: 1.1rem;"></i>
            </div>
            <div style="display: flex; flex-direction: column;">
                <span style="font-weight: 900; font-size: 1.25rem; line-height: 1; letter-spacing: -0.5px; background: linear-gradient(135deg, #60a5fa, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">SAE</span>
                <span style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">Smart Apps Education</span>
            </div>
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
