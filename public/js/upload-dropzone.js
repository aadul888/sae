/**
 * Upload Dropzone Global
 * - Aktifkan drag & drop file untuk semua area bertanda [data-upload-dropzone].
 * - Tombol bertanda [data-upload-trigger] otomatis membuka dialog file.
 * - Input file biasa (tampil) tetap bisa menerima drop.
 * Tidak mengganggu dropzone khusus yang sudah punya skrip sendiri (gtk-foto, identitas-sekolah, dll).
 */
(function () {
    "use strict";

    function findInput(zone) {
        return zone ? zone.querySelector('input[type="file"]') : null;
    }

    function setActive(zone, on) {
        if (zone && zone.classList) zone.classList.toggle("upload-dropzone-active", !!on);
    }

    function isFileInput(el) {
        return el && el.matches && el.matches('input[type="file"]');
    }

    function assignFiles(input, fileList) {
        if (!fileList || !fileList.length) return false;
        var count = input.multiple ? fileList.length : 1;
        try {
            var dt = new DataTransfer();
            for (var i = 0; i < count; i++) dt.items.add(fileList[i]);
            input.files = dt.files;
        } catch (err) {
            // Fallback: langsung set (beberapa browser lama menolak FileList read-only)
            try { input.files = fileList; } catch (e2) { return false; }
        }
        input.dispatchEvent(new Event("change", { bubbles: true }));
        return true;
    }

    var CUSTOM_ZONE =
        '[data-upload-dropzone], [data-upload-skip], .file-dropzone, [class*="dropzone" i], [id*="dropzone" i]';

    // Tandai otomatis area unggah untuk input file biasa (tanpa dropzone khusus).
    function decorate() {
        document.querySelectorAll('input[type="file"]').forEach(function (input) {
            if (input.dataset.uploadEnhanced) return;
            input.dataset.uploadEnhanced = "1";
            if (input.closest(CUSTOM_ZONE)) return;

            var host = input.parentElement;
            if (!host || host.hasAttribute("data-upload-dropzone")) return;
            host.setAttribute("data-upload-dropzone", "1");
            host.classList.add("upload-dropzone-host");
        });
    }

    document.addEventListener("DOMContentLoaded", decorate);
    if (document.readyState !== "loading") decorate();
    try {
        new MutationObserver(decorate).observe(document.documentElement, {
            childList: true,
            subtree: true,
        });
    } catch (e) {
        // Browser tanpa MutationObserver: input dinamis ditangani langsung saat drop.
    }

    document.addEventListener(
        "dragover",
        function (e) {
            var zone = e.target.closest ? e.target.closest("[data-upload-dropzone]") : null;
            if (!zone && isFileInput(e.target)) zone = e.target;
            if (!zone) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = "copy";
            if (zone !== e.target) setActive(zone, true);
        },
        false,
    );

    document.addEventListener(
        "dragleave",
        function (e) {
            var zone = e.target.closest ? e.target.closest("[data-upload-dropzone]") : null;
            if (zone) setActive(zone, false);
        },
        false,
    );

    document.addEventListener(
        "drop",
        function (e) {
            var zone = e.target.closest ? e.target.closest("[data-upload-dropzone]") : null;
            var directInput = isFileInput(e.target) ? e.target : null;
            if (!zone && !directInput) return;

            var input = directInput || findInput(zone);
            if (!input) return;

            e.preventDefault();
            if (zone) setActive(zone, false);
            assignFiles(input, e.dataTransfer ? e.dataTransfer.files : null);
        },
        false,
    );

    // Buka dialog file saat tombol pemicu diklik (input boleh display:none).
    document.addEventListener(
        "click",
        function (e) {
            var trigger = e.target.closest ? e.target.closest("[data-upload-trigger]") : null;
            if (!trigger) return;

            var targetId = trigger.getAttribute("data-upload-target");
            var input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                var zone = trigger.closest("[data-upload-dropzone]");
                input = zone ? findInput(zone) : null;
            }
            if (!input) {
                var scope = trigger.closest(".card, .modal-backdrop, form") || document;
                input = scope.querySelector('input[type="file"]');
            }
            if (!input) return;

            e.preventDefault();
            e.stopPropagation();
            input.click();
        },
        true,
    );
})();
