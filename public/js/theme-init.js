(function () {
    try {
        const savedTheme = localStorage.getItem('sae_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        if (savedTheme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        if (localStorage.getItem('sae_sidebar_mini') === '1' && window.innerWidth > 1024) {
            document.documentElement.classList.add('sidebar-mini');
        }
    } catch (e) {}
})();
