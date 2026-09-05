<footer class="footer">
    <div class="container"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <p style="margin: 0;">&copy; {{ date('Y') }} Sistem Aplikasi Edukasi (SAE). Platform Sistem Informasi &amp;
            Administrasi Digital Sekolah.</p>
        <span
            style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;">v{{ $appVersion ?? '1.0.1' }}</span>
    </div>
</footer>
