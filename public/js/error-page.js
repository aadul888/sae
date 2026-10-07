/**
 * SAE - Halaman error (419, 500): sinkron tema + aksi tombol.
 */
(function () {
    try {
        var t = localStorage.getItem('sae_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
        else document.documentElement.removeAttribute('data-theme');
    } catch (e) {}

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-err-action]');
        if (!btn) return;

        var action = btn.getAttribute('data-err-action');
        if (action === 'reload') {
            window.location.reload();
        } else if (action === 'back') {
            history.back();
        } else if (action === 'back-refresh') {
            // Muat ulang halaman asal lewat GET agar token CSRF baru terbentuk.
            var fallback = btn.getAttribute('data-fallback') || '/';
            var ref = document.referrer;
            if (ref && ref.indexOf(window.location.origin) === 0 && ref !== window.location.href) {
                window.location.href = ref;
            } else {
                window.location.href = fallback;
            }
        }
    });
})();
