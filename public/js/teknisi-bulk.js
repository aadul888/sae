(function () {
    "use strict";
    if (window.__teknisiBulkInit) return;
    window.__teknisiBulkInit = true;

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute("content") : "";
    }

    function boxes() {
        return Array.prototype.slice.call(document.querySelectorAll(".teknisi-chk"));
    }

    function selectedIds() {
        return boxes().filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
    }

    function sync() {
        var list = boxes();
        var checked = selectedIds().length;
        var all = document.querySelector(".teknisi-chk-all");
        if (all) {
            all.checked = list.length > 0 && checked === list.length;
            all.indeterminate = checked > 0 && checked < list.length;
        }
    }

    function inject() {
        Array.prototype.forEach.call(document.querySelectorAll("table"), function (table) {
            var rows = table.querySelectorAll("tbody tr[data-bulk-id]");
            if (!rows.length) return;

            var headRow = table.querySelector("thead tr");
            if (headRow && !headRow.querySelector(".teknisi-chk-all")) {
                var th = document.createElement("th");
                th.style.cssText = "padding:12px 18px; width:36px; text-align:center;";
                th.innerHTML = '<input type="checkbox" class="teknisi-chk-all" title="Pilih semua" style="cursor:pointer; accent-color:var(--primary);">';
                headRow.insertBefore(th, headRow.firstChild);
            }

            Array.prototype.forEach.call(rows, function (tr) {
                if (tr.querySelector(".teknisi-chk")) return;
                var id = tr.getAttribute("data-bulk-id");
                if (!id) return;
                var td = document.createElement("td");
                td.style.cssText = "padding:14px 18px; text-align:center;";
                td.innerHTML = '<input type="checkbox" class="teknisi-chk" value="' + id + '" style="cursor:pointer; accent-color:var(--primary);">';
                tr.insertBefore(td, tr.firstChild);
            });
        });
        sync();
    }

    function submit(btn) {
        var ids = selectedIds();
        if (!ids.length) {
            if (window.Swal) {
                window.Swal.fire({ icon: "info", title: "Belum ada data dipilih", text: "Centang minimal satu data dulu." });
            } else {
                window.alert("Centang minimal satu data dulu.");
            }
            return;
        }

        var modul = btn.getAttribute("data-modul") || "";
        var endpoint = btn.getAttribute("data-endpoint");
        if (!endpoint) return;

        var go = function () {
            fetch(endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf(),
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ modul: modul, ids: ids }),
            })
                .then(function (r) { return r.json().catch(function () { return {}; }); })
                .then(function (res) {
                    var ok = res.status === "success";
                    var msg = res.message || "Selesai.";
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: ok ? "success" : "error",
                            title: ok ? "Berhasil" : "Gagal",
                            text: msg,
                        }).then(function () { window.location.reload(); });
                    } else {
                        window.alert(msg);
                        window.location.reload();
                    }
                })
                .catch(function () {
                    if (window.Swal) {
                        window.Swal.fire({ icon: "error", title: "Gagal", text: "Terjadi kesalahan jaringan." });
                    } else {
                        window.alert("Terjadi kesalahan jaringan.");
                    }
                });
        };

        var text = "Hapus " + ids.length + " data terpilih?";
        if (window.Swal) {
            window.Swal.fire({
                icon: "warning",
                title: "Konfirmasi",
                text: text,
                showCancelButton: true,
                confirmButtonText: "Ya, hapus",
                cancelButtonText: "Batal",
            }).then(function (r) { if (r.isConfirmed) go(); });
        } else if (window.confirm(text)) {
            go();
        }
    }

    document.addEventListener("change", function (e) {
        var t = e.target;
        if (!t || !t.classList) return;
        if (t.classList.contains("teknisi-chk-all")) {
            boxes().forEach(function (c) { c.checked = t.checked; });
            sync();
        } else if (t.classList.contains("teknisi-chk")) {
            sync();
        }
    });

    document.addEventListener("click", function (e) {
        var btn = e.target.closest && e.target.closest("[data-teknisi-bulk-delete]");
        if (btn) {
            e.preventDefault();
            submit(btn);
        }
    });

    // Re-inject setelah tabel diganti via AJAX (live search/filter, paginasi,
    // maupun polling realtime sae-realtime.js) agar checkbox tidak hilang.
    var pending = false;
    function scheduleInject() {
        if (pending) return;
        pending = true;
        setTimeout(function () {
            pending = false;
            inject();
        }, 40);
    }

    window.addEventListener("sae:tableRefreshed", scheduleInject);

    var orig = window.refreshLiveTable;
    if (typeof orig === "function") {
        window.refreshLiveTable = function () {
            var r = orig.apply(this, arguments);
            Promise.resolve(r).then(scheduleInject);
            return r;
        };
    }

    if (window.MutationObserver) {
        new MutationObserver(scheduleInject).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", inject);
    } else {
        inject();
    }
})();
