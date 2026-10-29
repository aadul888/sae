/**
 * Action bar header dashboard — normalisasi tombol aksi (print, import, excel,
 * tambah, dll) di seluruh halaman.
 *
 * Di layar kecil (<= 768px) semua `.dash-banner-actions` dipusatkan dan tombol
 * yang punya ikon berubah menjadi tombol ikon 38×38 tanpa teks. Teks dipindah
 * ke `title` + `aria-label` agar tooltip & pembaca layar tetap jelas.
 *
 * Tombol tanpa ikon (mis. tombol teks murni) TIDAK diubah menjadi kotak kosong:
 * diberi kelas `banner-actions__text` sehingga tetap tampil sebagai pill teks.
 *
 * Bila tombol ikon lebih dari 4, action bar disusun grid 4 kolom (4 x 2 untuk
 * 5–8 tombol, 4 x 3 untuk 9–12, dst).
 */
(function () {
    'use strict';

    var ICON_SELECTOR = 'i, svg, img';
    var ITEM_SELECTOR = '.dash-banner-actions > .btn, .dash-banner-actions > div > .btn';

    function textOf(el) {
        var clone = el.cloneNode(true);
        clone.querySelectorAll(ICON_SELECTOR).forEach(function (n) {
            n.remove();
        });
        return (clone.textContent || '').replace(/\s+/g, ' ').trim();
    }

    /** Ikon milik tombol itu sendiri (bukan ikon di dalam dropdown-nya). */
    function ownIcon(btn) {
        return btn.querySelector(':scope > i, :scope > svg, :scope > span > i, :scope > span > svg');
    }

    function markButton(btn) {
        var label = btn.getAttribute('title') || btn.getAttribute('aria-label') || textOf(btn);
        var icon = ownIcon(btn);

        btn.classList.toggle('banner-actions__icon', !!icon);
        btn.classList.toggle('banner-actions__text', !icon);

        if (icon) {
            // Ikon saja: pastikan tetap punya nama untuk tooltip & screen reader.
            if (label) {
                if (!btn.getAttribute('title')) btn.setAttribute('title', label);
                if (!btn.getAttribute('aria-label')) btn.setAttribute('aria-label', label);
            }
        }
    }

    function markBar(box) {
        var buttons = Array.prototype.slice.call(box.querySelectorAll(ITEM_SELECTOR));
        buttons.forEach(markButton);

        // Grid 4 kolom hanya bila tombol ikon lebih dari 4.
        var iconCount = buttons.filter(function (b) {
            return b.classList.contains('banner-actions__icon');
        }).length;
        box.classList.toggle('banner-actions--grid4', iconCount > 4);
    }

    function run(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('.dash-banner-actions').forEach(markBar);
    }

    function init() {
        run(document);

        // Modal / toolbar bisa menyisipkan action bar baru setelah render.
        if (window.MutationObserver) {
            var pending = null;
            var observer = new MutationObserver(function (mutations) {
                var need = mutations.some(function (m) {
                    return m.addedNodes && m.addedNodes.length > 0;
                });
                if (!need || pending) return;
                pending = window.requestAnimationFrame(function () {
                    pending = null;
                    run(document);
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.SAE = window.SAE || {};
    window.SAE.headerActions = { scan: run };
})();
