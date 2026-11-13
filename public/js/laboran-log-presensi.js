// =========================================================================
// Laboran — Log Presensi: pratinjau snapshot foto masuk/pulang
// =========================================================================
// Pencarian, filter, paginasi, dan pengurutan ditangani oleh mesin global
// (dashboard-live-search.js + dashboard.js). Berkas ini hanya menangani
// modal snapshot agar halaman tidak perlu memuat presensi.js penuh.
(function () {
    "use strict";

    function closeSnapshot() {
        var modal = document.getElementById("modalFotoSnapshot");
        if (!modal) return;
        modal.style.display = "none";
        document.body.style.overflow = "";

        var img = document.getElementById("imgFotoSnapshot");
        if (img) img.src = "";
    }

    document.addEventListener("click", function (e) {
        var trigger = e.target.closest(".btn-preview-snapshot");
        if (trigger) {
            e.preventDefault();

            var url = trigger.getAttribute("data-url");
            var title = trigger.getAttribute("data-title") || "Foto Bukti Presensi";
            var modal = document.getElementById("modalFotoSnapshot");
            var img = document.getElementById("imgFotoSnapshot");
            var heading = document.getElementById("fotoSnapshotTitle");

            if (!url || !modal || !img) return;

            // Cache-busting: tanpa ini browser bisa menampilkan gambar lama.
            img.src = "";
            img.src = url + (url.indexOf("?") === -1 ? "?" : "&") + "t=" + Date.now();
            if (heading) heading.textContent = title;

            modal.style.display = "flex";
            document.body.style.overflow = "hidden";
            return;
        }

        if (e.target.closest("#btnCloseFotoModal")) {
            e.preventDefault();
            closeSnapshot();
            return;
        }

        var backdrop = document.getElementById("modalFotoSnapshot");
        if (backdrop && e.target === backdrop) {
            closeSnapshot();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeSnapshot();
    });
})();
