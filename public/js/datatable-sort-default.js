/**
 * SAE — Default Datatable Header Sorting (global, tanpa markup modul).
 *
 * Tujuan: setiap <th> pada datatable bisa diklik untuk mengurutkan — walau
 * modul tidak menuliskan `class="sortable-th" data-sort="..."`.
 *
 * Cara kerja:
 * - Header yang SUDAH punya `data-sort` (server-side) tidak disentuh; handler
 *   global di `dashboard.js` / handler modul yang menanganinya.
 * - Header tanpa `data-sort` ditandai otomatis + diurutkan di sisi klien
 *   (tbody saat ini). TIDAK mengirim parameter `sort` ke server — sejumlah
 *   controller memakai `orderBy($sort)` tanpa allowlist, jadi mengirim nama
 *   kolom mentah berisiko error SQL.
 *
 * Batas (sadar): pengurutan klien hanya berlaku untuk baris halaman aktif.
 * Modul yang butuh urut server-side lintas halaman tetap menambahkan
 * `data-sort` + dukungan `sort`/`sort_dir` di controller.
 */
(function () {
    "use strict";

    // Label kolom yang tidak perlu diurutkan.
    var SKIP_LABELS = { "no": 1, "no.": 1, "#": 1, "aksi": 1, "action": 1, "opsi": 1, "option": 1 };

    function normText(el) {
        return (el && el.textContent ? el.textContent : "").replace(/\s+/g, " ").trim();
    }

    function toComparable(text) {
        var raw = (text || "").replace(/\s+/g, " ").trim();
        var cleaned = raw
            .replace(/[^\d.,\-]/g, "")
            .replace(/\.(?=\d{3}(\D|$))/g, "")
            .replace(",", ".");
        if (cleaned !== "" && /^-?\d+(\.\d+)?$/.test(cleaned)) {
            return { n: Number(cleaned), s: raw.toLowerCase() };
        }
        return { n: null, s: raw.toLowerCase() };
    }

    var HEAD_SELECTOR =
        "#tableDataContainer thead th, #tableMonitoringContainer thead th, " +
        ".datatable-card-wrapper thead th, .dash-table-card thead th";

    function enhance(root) {
        (root || document).querySelectorAll(HEAD_SELECTOR).forEach(function (th) {
            if (th.classList.contains("sortable-th")) return; // sudah ditangani modul
            if (th.classList.contains("module-th")) return; // hak-akses.js punya logika sendiri
            if (th.hasAttribute("data-sort")) return; // server-side, jangan campur handler
            if (th.hasAttribute("data-no-sort")) return;
            if (th.closest("[data-no-live-search], [data-no-ajax], [data-manual-filter], .modal-backdrop")) return;
            // Sudah punya atribut sort khusus modul (mis. data-belanja-sort).
            for (var i = 0; i < th.attributes.length; i++) {
                var an = th.attributes[i].name;
                if (an.length > 5 && an.indexOf("data-") === 0 && an.slice(-5) === "-sort") return;
            }

            var label = normText(th).toLowerCase();
            if (label === "" || SKIP_LABELS[label]) return;

            // Hanya tabel data (punya <tbody>) — hindari tabel matriks/layout.
            var tbl = th.closest("table");
            if (!tbl || !tbl.tBodies || !tbl.tBodies.length) return;

            var tr = th.parentElement;
            var idx = tr ? Array.prototype.indexOf.call(tr.children, th) : -1;
            if (idx < 0) return;

            th.classList.add("sortable-th");
            th.setAttribute("data-sae-col", String(idx));
            if (!th.querySelector(".sort-icon")) {
                var icon = document.createElement("span");
                icon.className = "sort-icon";
                icon.innerHTML = "&#9650;&#9660;";
                th.appendChild(icon);
            }
        });
    }

    function sortTable(th) {
        var table = th.closest("table");
        var tbody = table && table.tBodies && table.tBodies[0];
        if (!tbody) return;

        var col = parseInt(th.getAttribute("data-sae-col"), 10);
        if (isNaN(col)) return;
        var dir = th.getAttribute("data-sae-dir") === "asc" ? "desc" : "asc";

        // Bersihkan penanda kolom lain pada tabel ini.
        table.querySelectorAll("th.sortable-th").forEach(function (other) {
            if (other === th) return;
            other.removeAttribute("data-sae-dir");
            other.classList.remove("sorted");
            var ic = other.querySelector(".sort-icon");
            if (ic) ic.innerHTML = "&#9650;&#9660;";
        });
        th.setAttribute("data-sae-dir", dir);
        th.classList.add("sorted");
        var icon = th.querySelector(".sort-icon");
        if (icon) icon.innerHTML = dir === "asc" ? "&#9650;" : "&#9660;";

        var rows = Array.prototype.filter.call(tbody.rows, function (r) {
            return !(r.cells.length === 1 && r.cells[0].hasAttribute("colspan"));
        });

        var decorated = rows.map(function (r) {
            var cell = r.cells[col];
            var val = toComparable(cell ? cell.textContent : "");
            return { row: r, val: val };
        });

        decorated.sort(function (a, b) {
            var cmp;
            if (a.val.n !== null && b.val.n !== null) {
                cmp = a.val.n - b.val.n;
            } else {
                cmp = a.val.s.localeCompare(b.val.s, "id", { numeric: true, sensitivity: "base" });
            }
            return dir === "asc" ? cmp : -cmp;
        });

        var frag = document.createDocumentFragment();
        decorated.forEach(function (d) {
            frag.appendChild(d.row);
        });
        tbody.appendChild(frag);

        // Kembalikan baris placeholder "kosong" (colspan) ke akhir tabel.
        var emptyRow = Array.prototype.find.call(tbody.rows, function (r) {
            return r.cells.length === 1 && r.cells[0].hasAttribute("colspan");
        });
        if (emptyRow) tbody.appendChild(emptyRow);
    }

    // Fallback handler: hanya untuk header auto (tanpa data-sort milik modul).
    document.addEventListener("click", function (e) {
        var th = e.target.closest("th.sortable-th[data-sae-col]");
        if (!th) return;
        e.preventDefault();
        sortTable(th);
    });

    // Tandai setelah seluruh skrip modul terpasang (hindari rebutan binding langsung).
    window.addEventListener("load", function () {
        enhance(document);
    });

    // Tandai ulang setiap tabel di-refresh via window.refreshLiveTable().
    window.addEventListener("sae:tableRefreshed", function () {
        enhance(document);
    });

    // Hook utilitas (dipakai ulang modul lain / uji cepat).
    window.SAE = window.SAE || {};
    window.SAE.datatableSortDefault = {
        enhance: enhance,
        sortTable: sortTable,
        toComparable: toComparable,
    };
})();
