(function () {
    try {
        const savedTheme = localStorage.getItem('sae_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        if (savedTheme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    } catch (e) {}
})();
