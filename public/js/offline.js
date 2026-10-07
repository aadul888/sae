/**
 * SAE — Offline Page Connection Listener
 */
(function () {
    window.addEventListener('online', function () {
        const notice = document.getElementById('onlineStatusNotice');
        if (notice) {
            notice.style.display = 'inline-flex';
        }
        setTimeout(function () {
            window.location.reload();
        }, 1200);
    });
})();
