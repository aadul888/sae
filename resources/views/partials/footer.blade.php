<footer class="footer">
    <div class="container"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <p style="margin: 0;">&copy; {{ date('Y') }} Sistem Aplikasi Edukasi (SAE). Platform Sistem Informasi &amp;
            Administrasi Digital Sekolah. &bull;
            <a href="{{ route('about') }}" class="nav-link" style="display: inline;">Tentang Aplikasi</a>
        </p>
        <span
            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); font-family: monospace;">v{{ $appVersion ?? '1.0.1' }}</span>
    </div>
</footer>
