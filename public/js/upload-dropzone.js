/**
 * Upload Dropzone Global SAE
 * - Aktifkan drag & drop file untuk semua area bertanda [data-upload-dropzone], class dropzone, maupun id dropzone.
 * - Mencegah browser membuka/mengunduh file saat file di-drag & drop di luar area target (drop-out safety).
 * - Mencegah recursive double-trigger saat input file berada di dalam elemen dropzone.
 * - Tombol bertanda [data-upload-trigger] otomatis membuka dialog file picker.
 * - Menyediakan helper universal window.SAE.assignFilesToInput(input, fileList).
 */
(function () {
    "use strict";

    window.SAE = window.SAE || {};

    function findInput(zone) {
        if (!zone) return null;
        var direct = zone.querySelector ? zone.querySelector('input[type="file"]') : null;
        if (direct) return direct;
        var targetId = zone.getAttribute ? zone.getAttribute("data-upload-target") : null;
        if (targetId) return document.getElementById(targetId);
        return null;
    }

    function setActive(zone, on) {
        if (zone && zone.classList) zone.classList.toggle("upload-dropzone-active", !!on);
    }

    function isFileInput(el) {
        return el && el.matches && el.matches('input[type="file"]');
    }

    function assignFiles(input, fileList) {
        if (!input || !fileList || !fileList.length) return false;
        var count = input.multiple ? fileList.length : 1;
        try {
            var dt = new DataTransfer();
            for (var i = 0; i < count; i++) dt.items.add(fileList[i]);
            input.files = dt.files;
        } catch (err) {
            // Fallback: langsung set
            try {
                input.files = fileList;
            } catch (e2) {
                return false;
            }
        }
        input.dispatchEvent(new Event("change", { bubbles: true }));
        return true;
    }

    window.SAE.assignFilesToInput = assignFiles;

    var CUSTOM_ZONE =
        '[data-upload-dropzone], [data-upload-skip], .file-dropzone, [class*="dropzone" i], [id*="dropzone" i]';

    // 1. Cegah browser membuka file secara default saat di-drag ke seluruh jendela
    window.addEventListener(
        "dragover",
        function (e) {
            e.preventDefault();
        },
        false,
    );

    window.addEventListener(
        "drop",
        function (e) {
            var zone = e.target.closest
                ? e.target.closest(
                      '[data-upload-dropzone], .file-dropzone, [class*="dropzone" i], [id*="dropzone" i], [id*="previewcontainer" i]',
                  )
                : null;
            if (!zone && !isFileInput(e.target)) {
                e.preventDefault();
            }
        },
        false,
    );

    // 2. Cegah event bubbling dari input file agar tidak memicu click ganda pada parent dropzone
    document.addEventListener(
        "click",
        function (e) {
            if (e.target && e.target.tagName === "INPUT" && e.target.type === "file") {
                e.stopPropagation();
            }
        },
        true,
    );

    // 3. Tandai otomatis area unggah untuk input file biasa (tanpa dropzone khusus)
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
        // Fallback browser tanpa MutationObserver
    }

    // 4. Dragover universal
    document.addEventListener(
        "dragover",
        function (e) {
            var zone = e.target.closest
                ? e.target.closest(
                      '[data-upload-dropzone], .file-dropzone, [class*="dropzone" i], [id*="dropzone" i], [id*="previewcontainer" i]',
                  )
                : null;
            if (!zone && isFileInput(e.target)) zone = e.target;
            if (!zone) return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = "copy";
            if (zone !== e.target) setActive(zone, true);
        },
        false,
    );

    // 5. Dragleave universal
    document.addEventListener(
        "dragleave",
        function (e) {
            var zone = e.target.closest
                ? e.target.closest(
                      '[data-upload-dropzone], .file-dropzone, [class*="dropzone" i], [id*="dropzone" i], [id*="previewcontainer" i]',
                  )
                : null;
            if (zone) setActive(zone, false);
        },
        false,
    );

    // 6. Drop universal
    document.addEventListener(
        "drop",
        function (e) {
            var zone = e.target.closest
                ? e.target.closest(
                      '[data-upload-dropzone], .file-dropzone, [class*="dropzone" i], [id*="dropzone" i], [id*="previewcontainer" i]',
                  )
                : null;
            var directInput = isFileInput(e.target) ? e.target : null;
            if (!zone && !directInput) return;

            var input = directInput || findInput(zone);
            if (!input) return;

            e.preventDefault();
            e.stopPropagation();
            if (zone) setActive(zone, false);
            assignFiles(input, e.dataTransfer ? e.dataTransfer.files : null);
        },
        false,
    );

    // 7. Buka dialog file saat tombol pemicu diklik [data-upload-trigger]
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
