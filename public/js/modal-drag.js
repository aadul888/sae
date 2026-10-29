/**
 * Modal global SAE: geser (drag) + responsif mobile.
 *
 * • Desktop: seret header modal (handle) untuk memindahkan kotak modal.
 *   Posisi disimpan sebagai transform pada kotak, bukan pada backdrop.
 * • Mobile (≤768px): modal full-width, tinggi maksimum 92vh, isi bisa
 *   digulir; drag dinonaktifkan agar scroll tetap terasa wajar.
 * • Otomatis berlaku untuk semua `.modal-backdrop` (card / modal-card).
 */
(function () {
    'use strict';

    var MOBILE_MAX = 768;
    var openModal = null; // kotak modal yang sedang di-drag

    function isMobile() {
        return window.innerWidth <= MOBILE_MAX;
    }

    function getBox(backdrop) {
        return backdrop.querySelector(':scope > .card, :scope > .modal-card');
    }

    function getHandle(box) {
        return box.querySelector(':scope > div, :scope > header');
    }

    function reset(box) {
        box.style.transform = '';
        box.dataset.dragX = '0';
        box.dataset.dragY = '0';
        box.style.cursor = '';
    }

    /* ---------- Drag ---------- */
    document.addEventListener('pointerdown', function (e) {
        if (isMobile() || e.button !== 0) return;

        var handle = e.target.closest('.modal-backdrop > .card > div, .modal-backdrop > .card > header, .modal-backdrop > .modal-card > div');
        if (!handle) return;
        // Jangan seret saat menekan tombol / input di dalam header
        if (e.target.closest('button, a, input, select, textarea')) return;

        var backdrop = handle.closest('.modal-backdrop');
        var box = getBox(backdrop);
        if (!box) return;

        var startX = e.clientX;
        var startY = e.clientY;
        var originX = parseFloat(box.dataset.dragX || '0');
        var originY = parseFloat(box.dataset.dragY || '0');

        openModal = { box: box, startX: startX, startY: startY, originX: originX, originY: originY };

        try { handle.setPointerCapture(e.pointerId); } catch (_) {}
        document.body.style.userSelect = 'none';
        e.preventDefault();
    });

    document.addEventListener('pointermove', function (e) {
        if (!openModal) return;
        var dx = e.clientX - openModal.startX;
        var dy = e.clientY - openModal.startY;

        // Batasi agar kotak tidak hilang keluar layar
        var maxX = window.innerWidth * 0.5;
        var maxY = window.innerHeight * 0.5;
        var x = Math.max(-maxX, Math.min(maxX, openModal.originX + dx));
        var y = Math.max(-maxY, Math.min(maxY, openModal.originY + dy));

        openModal.box.style.transform = 'translate(' + x + 'px,' + y + 'px)';
        openModal.box.dataset.dragX = String(x);
        openModal.box.dataset.dragY = String(y);
    });

    function endDrag() {
        if (!openModal) return;
        openModal = null;
        document.body.style.userSelect = '';
    }

    document.addEventListener('pointerup', endDrag);
    document.addEventListener('pointercancel', endDrag);

    /* ---------- Reset posisi saat modal dibuka/ditutup ---------- */
    // Fungsi global kecil untuk dipakai skrip pengelola modal mana pun.
    window.SAE = window.SAE || {};
    window.SAE.modal = {
        reset: function (id) {
            var el = typeof id === 'string' ? document.getElementById(id) : id;
            var box = el && getBox(el);
            if (box) reset(box);
        },
        resetAll: function () {
            document.querySelectorAll('.modal-backdrop').forEach(function (b) {
                var box = getBox(b);
                if (box) reset(box);
            });
        }
    };

    // Jika pengelola modal hanya mengubah `display`, posisi lama akan tetap.
    // Pantau perubahan display dan reset saat modal berubah dari tertutup → terbuka
    // atau terbuka → tertutup, supaya posisi selalu kembali ke tengah.
    new MutationObserver(function (muts) {
        muts.forEach(function (m) {
            var el = m.target;
            if (!el.classList || !el.classList.contains('modal-backdrop')) return;
            var box = getBox(el);
            if (!box) return;
            var shown = el.style.display && el.style.display !== 'none';
            if (shown !== (el.dataset.saeShown === '1')) {
                el.dataset.saeShown = shown ? '1' : '0';
                reset(box);
            }
        });
    }).observe(document.body, { attributes: true, attributeFilter: ['style', 'class'], subtree: true });
})();
