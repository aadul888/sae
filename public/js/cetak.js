/**
 * SAE — Master Cetak & Print Layout Helper Script
 */
(function () {
    window.addEventListener('load', function () {
        if (document.body.hasAttribute('data-auto-print') || window.location.search.indexOf('autoprint=1') !== -1) {
            setTimeout(function () {
                window.print();
            }, 400);
        }
    });
})();
