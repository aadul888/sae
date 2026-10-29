/**
 * Menu Cepat 4x2 — normalisasi grid "akses cepat / aksi cepat".
 *
 * Aturan baku:
 *   • Grid aksi cepat SELALU 4 kolom (4 x 2 = 8 tile per blok).
 *   • Bila jumlah menu <= 8, semuanya tampil di grid.
 *   • Bila jumlah menu > 8, grid menampilkan 7 menu pertama + 1 tile
 *     "Lainnya". Tile itu membuka modal berisi sisa menu yang dikelompokkan
 *     per kategori (Laporan & Arsip, Kinerja & Tupoksi, Layanan & Operasional,
 *     dst).
 *
 * Kategori ditentukan dari `data-quick-group` (bila ada) atau dicocokkan dari
 * judul/URL menu. Semua penamaan kategori ada di konstanta GROUPS di bawah.
 *
 * API: window.SAE.quickMenu.scan()
 */
(function () {
    'use strict';

    var GRID_SELECTOR = '.kepegawaian-quick-grid';
    var MAX_TILES = 8; // 4 kolom x 2 baris
    var VISIBLE_TILES = MAX_TILES - 1; // 1 slot disisakan untuk "Lainnya"

    /**
     * Urutan di sini = urutan prioritas pencocokan & urutan tampil di modal.
     * `keys` dicocokkan (substring) terhadap data-quick-group + title + href +
     * label menu. Grup tanpa `keys` adalah penampung terakhir.
     */
    var GROUPS = [
        {
            id: 'laporan',
            label: 'Laporan & Arsip',
            icon: 'fa-file-lines',
            keys: ['laporan', 'rekap', 'cetak', 'print', 'arsip', 'tahunan', 'pustaka', 'ekspor', 'export']
        },
        {
            id: 'kinerja',
            label: 'Kinerja & Tupoksi',
            icon: 'fa-bullseye',
            keys: ['target', 'capaian', 'aktivitas', 'kinerja', 'tupoksi', 'supervisi', 'indikator']
        },
        {
            id: 'layanan',
            label: 'Layanan & Operasional',
            icon: 'fa-layer-group',
            keys: []
        }
    ];

    var seq = 0;

    /* ---------------------------------------------------------------- util */

    function labelOf(el) {
        var label = el.querySelector('.kepegawaian-quick-label');
        var text = label ? label.textContent : el.textContent;
        return (text || '').replace(/\s+/g, ' ').trim();
    }

    function groupOf(el) {
        var explicit = (el.getAttribute('data-quick-group') || '').trim().toLowerCase();
        if (explicit) {
            for (var i = 0; i < GROUPS.length; i++) {
                if (GROUPS[i].id === explicit) return GROUPS[i];
            }
        }

        var hay = [
            el.getAttribute('title') || '',
            el.getAttribute('href') || '',
            labelOf(el)
        ]
            .join(' ')
            .toLowerCase();

        for (var g = 0; g < GROUPS.length; g++) {
            var keys = GROUPS[g].keys;
            for (var k = 0; k < keys.length; k++) {
                if (hay.indexOf(keys[k]) !== -1) return GROUPS[g];
            }
        }

        return GROUPS[GROUPS.length - 1];
    }

    function itemsOf(grid) {
        return Array.prototype.filter.call(grid.children, function (el) {
            return el.classList && el.classList.contains('kepegawaian-quick-btn');
        });
    }

    function makeMoreTile(count) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'kepegawaian-quick-btn kepegawaian-quick-btn--more';
        btn.setAttribute('aria-haspopup', 'dialog');
        btn.setAttribute(
            'title',
            'Menu lainnya (' + count + ' menu, dikelompokkan per kategori)'
        );
        btn.setAttribute('aria-label', 'Menu lainnya, ' + count + ' menu');
        btn.innerHTML =
            '<div class="kepegawaian-quick-icon kepegawaian-quick-icon--more">' +
            '<i class="fas fa-grip" aria-hidden="true"></i>' +
            '</div>' +
            '<span class="kepegawaian-quick-label">Lainnya</span>';
        return btn;
    }

    /* --------------------------------------------------------------- modal */

    function buildModal(grid, overflow) {
        var id = 'quickMenuModal' + ++seq;
        var heading =
            grid.getAttribute('data-quick-title') ||
            document.title.split('—')[0].trim() ||
            'Menu Cepat';

        // Kelompokkan sisa menu sesuai kategori.
        var order = [];
        var buckets = {};
        overflow.forEach(function (el) {
            var g = groupOf(el);
            if (!buckets[g.id]) {
                buckets[g.id] = { meta: g, items: [] };
                order.push(g.id);
            }
            buckets[g.id].items.push(el);
        });

        // Urutkan grup sesuai urutan GROUPS.
        order.sort(function (a, b) {
            var ia = GROUPS.findIndex(function (g) {
                return g.id === a;
            });
            var ib = GROUPS.findIndex(function (g) {
                return g.id === b;
            });
            return ia - ib;
        });

        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop quick-menu-backdrop';
        backdrop.id = id;
        backdrop.style.display = 'none';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        backdrop.setAttribute('aria-label', 'Menu lainnya — ' + heading);

        var groupsHtml = order
            .map(function (gid) {
                var bucket = buckets[gid];
                return (
                    '<section class="quick-menu-group">' +
                    '<h4 class="quick-menu-group-title">' +
                    '<i class="fas ' +
                    bucket.meta.icon +
                    '" aria-hidden="true"></i> ' +
                    bucket.meta.label +
                    ' <span class="quick-menu-group-count">' +
                    bucket.items.length +
                    '</span>' +
                    '</h4>' +
                    '<div class="quick-menu-grid" data-quick-bucket="' +
                    bucket.meta.id +
                    '"></div>' +
                    '</section>'
                );
            })
            .join('');

        backdrop.innerHTML =
            '<div class="card quick-menu-card">' +
            '<div class="quick-menu-head">' +
            '<div class="quick-menu-head-text">' +
            '<h3 class="quick-menu-title">' +
            '<i class="fas fa-grip" aria-hidden="true"></i> Menu Lainnya</h3>' +
            '<p class="quick-menu-subtitle">' +
            overflow.length +
            ' menu tambahan untuk ' +
            heading +
            ', dikelompokkan per kategori.</p>' +
            '</div>' +
            '<button type="button" class="btn btn-outline quick-menu-close" data-close="' +
            id +
            '" title="Tutup" aria-label="Tutup">' +
            '<i class="fas fa-xmark" aria-hidden="true"></i>' +
            '</button>' +
            '</div>' +
            '<div class="quick-menu-body">' +
            groupsHtml +
            '</div>' +
            '</div>';

        // Pindahkan node menu asli (bukan klon) ke grid kategorinya.
        order.forEach(function (gid) {
            var holder = backdrop.querySelector(
                '[data-quick-bucket="' + gid + '"]'
            );
            buckets[gid].items.forEach(function (el) {
                el.classList.add('quick-menu-item');
                el.style.display = ''; // tampilkan kembali di dalam modal
                holder.appendChild(el);
            });
        });

        document.body.appendChild(backdrop);
        return { id: id, backdrop: backdrop };
    }

    function openModal(backdrop) {
        backdrop.style.display = 'flex';
        var body = backdrop.querySelector('.quick-menu-body');
        if (body) body.scrollTop = 0;
        var first = backdrop.querySelector('.quick-menu-close');
        if (first) {
            try {
                first.focus({ preventScroll: true });
            } catch (_) {}
        }
    }

    /* ---------------------------------------------------------- normalizer */

    /**
     * Gabungkan grid aksi cepat yang bersebelahan (mis. Teknisi punya dua blok
     * 6 menu) agar tetap satu blok 4x2 — bukan dua blok berisi 6 + 6.
     */
    function mergeAdjacentGrids() {
        var grids = Array.prototype.slice.call(
            document.querySelectorAll(GRID_SELECTOR)
        );
        grids.forEach(function (grid) {
            if (grid.dataset.quickMenuMerged === '1') return;
            grid.dataset.quickMenuMerged = '1';

            var next = grid.nextElementSibling;
            while (next && next.matches(GRID_SELECTOR)) {
                var moving = itemsOf(next);
                var follow = next.nextElementSibling;
                moving.forEach(function (el) {
                    grid.appendChild(el);
                });
                next.remove();
                next = follow;
            }
        });
    }

    function normalize(grid) {
        if (grid.dataset.quickMenuReady === '1') return 'skip';
        grid.dataset.quickMenuReady = '1';

        var items = itemsOf(grid);
        if (items.length <= MAX_TILES) return 'ok';

        var overflow = items.slice(VISIBLE_TILES);

        // Sembunyikan dulu supaya tidak berkedip sebelum dipindah ke modal.
        overflow.forEach(function (el) {
            el.style.display = 'none';
        });

        var modal = buildModal(grid, overflow);

        var more = makeMoreTile(overflow.length);
        more.addEventListener('click', function () {
            openModal(modal.backdrop);
        });
        grid.appendChild(more);

        return 'collapsed';
    }

    function run(root) {
        var scope = root && root.querySelectorAll ? root : document;
        if (scope === document) mergeAdjacentGrids();

        var grids = Array.prototype.slice.call(
            scope.querySelectorAll(GRID_SELECTOR)
        );
        if (scope.matches && scope.matches(GRID_SELECTOR)) grids.unshift(scope);

        var stats = { ok: 0, collapsed: 0, skip: 0 };
        grids.forEach(function (grid) {
            stats[normalize(grid)]++;
        });
        return stats;
    }

    function init() {
        run(document);

        // Section dashboard bisa disisipkan setelah render (tab bidang, dsb).
        if (window.MutationObserver) {
            var pending = null;
            var observer = new MutationObserver(function (mutations) {
                var newGrid = mutations.some(function (m) {
                    if (!m.addedNodes) return false;
                    return Array.prototype.some.call(m.addedNodes, function (n) {
                        return (
                            n.nodeType === 1 &&
                            (n.matches(GRID_SELECTOR) ||
                                (n.querySelector &&
                                    n.querySelector(GRID_SELECTOR)))
                        );
                    });
                });
                if (!newGrid || pending) return;
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
    window.SAE.quickMenu = { scan: run, groups: GROUPS };
})();
