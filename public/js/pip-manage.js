/**
 * SAE - Modul Usulan & Riwayat PIP (Frontend)
 * Aksi memakai AJAX + window.refreshLiveTable (datatable global), tanpa reload halaman.
 */
document.addEventListener("DOMContentLoaded", function () {
    const CFG = window.PIP_CONFIG || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    const tpl = (t, id) => t.replace("__ID__", encodeURIComponent(id));

    // ---------------------------------------------------------------------
    // Util
    // ---------------------------------------------------------------------
    async function post(url, payload, isForm = false) {
        const headers = { "X-CSRF-TOKEN": csrf, "X-Requested-With": "XMLHttpRequest", Accept: "application/json" };
        if (!isForm) headers["Content-Type"] = "application/json";
        const res = await fetch(url, { method: "POST", headers, body: isForm ? payload : JSON.stringify(payload || {}) });
        let data = {};
        try { data = await res.json(); } catch (e) { /* non-JSON */ }
        if (!res.ok && !data.message) {
            data.message = data.errors ? Object.values(data.errors).flat().join(" ") : `Terjadi kesalahan (HTTP ${res.status}).`;
        }
        if (data.errors) data.message = Object.values(data.errors).flat().join(" ");
        return { ok: res.ok && data.success !== false, data };
    }

    function toast(msg, type = "success") {
        if (window.SAE && typeof window.SAE.toast === "function") window.SAE.toast(msg, type);
        else Swal.fire({ toast: true, position: "top-end", icon: type, title: msg, timer: 2200, showConfirmButton: false });
    }

    function refresh() {
        if (typeof window.refreshLiveTable === "function") window.refreshLiveTable(window.location.href);
        else window.location.reload();
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) { m.style.display = "flex"; document.body.style.overflow = "hidden"; }
        return m;
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) { m.style.display = "none"; document.body.style.overflow = ""; }
    }

    // ---------------------------------------------------------------------
    // Toolbar: search / filter / perPage (pola global)
    // ---------------------------------------------------------------------
    const searchInput = document.getElementById("searchPip");
    const clearBtn = document.getElementById("clearSearchPip");
    let timer = null;

    function applyParams(changes) {
        const url = new URL(window.location.href);
        Object.entries(changes).forEach(([k, v]) => (v ? url.searchParams.set(k, v) : url.searchParams.delete(k)));
        const q = document.getElementById("searchPip");
        if (q && q.value.trim()) url.searchParams.set("q", q.value.trim()); else url.searchParams.delete("q");
        url.searchParams.delete("usulan_page");
        url.searchParams.delete("kuota_page");
        url.searchParams.delete("riwayat_page");
        url.searchParams.delete("page");
        history.replaceState(null, "", url.toString());
        if (typeof window.refreshLiveTable === "function") window.refreshLiveTable(url.toString());
        else window.location.href = url.toString();
    }

    document.addEventListener("input", function (e) {
        if (e.target.id !== "searchPip") return;
        clearBtn?.classList.toggle("visible", e.target.value.trim().length > 0);
        clearTimeout(timer);
        timer = setTimeout(() => applyParams({}), 350);
    });
    document.addEventListener("keydown", function (e) {
        if (e.target.id === "searchPip" && e.key === "Enter") { e.preventDefault(); clearTimeout(timer); applyParams({}); }
    });
    document.addEventListener("click", function (e) {
        if (e.target.closest("#clearSearchPip")) {
            const q = document.getElementById("searchPip");
            if (q) q.value = "";
            document.getElementById("clearSearchPip")?.classList.remove("visible");
            applyParams({});
        }
    });
    document.addEventListener("change", function (e) {
        const map = { perPageSelect: "perPage", filterRombel: "rombel_id", filterStatusVerif: "status_verifikasi", filterTahap: "tahap", filterJenisSk: "jenis_sk" };
        const key = map[e.target.id];
        if (key) { e.stopImmediatePropagation(); applyParams({ [key]: e.target.value }); }
    }, true);

    // ---------------------------------------------------------------------
    // Tambah Usulan + autocomplete
    // ---------------------------------------------------------------------
    window.openTambahUsulan = function () {
        const f = document.getElementById("formTambahUsulan");
        if (!f) return;
        f.reset();
        document.getElementById("pipPdId").value = "";
        document.getElementById("pipPilihInfo").textContent = "Belum ada peserta didik dipilih.";
        document.getElementById("pipSubmitUsulan").disabled = true;
        openModal("modalTambahUsulan");
        document.getElementById("pipCari")?.focus();
    };
    window.closeTambahUsulan = () => closeModal("modalTambahUsulan");

    function bindAutocomplete({ input, list, onPick, extra }) {
        const inp = document.getElementById(input);
        const lst = document.getElementById(list);
        if (!inp || !lst) return;
        let t = null;
        inp.addEventListener("input", function () {
            clearTimeout(t);
            const q = this.value.trim();
            if (q.length < 2) { lst.style.display = "none"; return; }
            t = setTimeout(async () => {
                try {
                    const params = new URLSearchParams({ q, tahun: CFG.tahun });
                    if (CFG.rombelId) params.set("rombel_id", CFG.rombelId);
                    if (extra) Object.entries(extra).forEach(([k, v]) => params.set(k, v));
                    const res = await fetch(`${CFG.routes.cariSiswa}?${params}`, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } });
                    const rows = await res.json();
                    lst.innerHTML = rows.length
                        ? rows.map((r) => `<button type="button" class="kp-ac-item" data-json='${esc(JSON.stringify(r))}'>${esc(r.nama)}<small>${esc(r.nama_rombel || "-")} &bull; NISN: ${esc(r.nisn || "-")}</small></button>`).join("")
                        : '<div class="kp-ac-item" style="cursor:default;">Tidak ditemukan / sudah diusulkan.</div>';
                    lst.style.display = "block";
                } catch (e) { lst.style.display = "none"; }
            }, 300);
        });
        lst.addEventListener("click", function (e) {
            const btn = e.target.closest(".kp-ac-item[data-json]");
            if (!btn) return;
            onPick(JSON.parse(btn.dataset.json), inp);
            lst.style.display = "none";
        });
        document.addEventListener("click", function (e) {
            if (!e.target.closest(".kp-ac-wrap")) lst.style.display = "none";
        });
    }

    bindAutocomplete({
        input: "pipCari", list: "pipAcList",
        onPick: (r, inp) => {
            document.getElementById("pipPdId").value = r.peserta_didik_id;
            inp.value = `${r.nama} (${r.nama_rombel || "-"})`;
            document.getElementById("pipPilihInfo").innerHTML = `Dipilih: <strong>${esc(r.nama)}</strong> &bull; ${esc(r.nama_rombel || "-")}`;
            document.getElementById("pipSubmitUsulan").disabled = false;
        },
    });
    document.getElementById("pipCari")?.addEventListener("input", function () {
        document.getElementById("pipPdId").value = "";
        document.getElementById("pipSubmitUsulan").disabled = true;
    });

    document.getElementById("formTambahUsulan")?.addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = document.getElementById("pipSubmitUsulan");
        const old = btn.innerHTML;
        const fd = new FormData(this);
        const payload = {
            peserta_didik_id: fd.get("peserta_didik_id"),
            tahun: fd.get("tahun"),
            keterangan: fd.get("keterangan"),
            alasan_usulan: fd.getAll("alasan_usulan[]"),
        };
        if (!payload.alasan_usulan.length) {
            Swal.fire("Belum Lengkap", "Pilih minimal satu kriteria / alasan usulan.", "warning");
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        try {
            const { ok, data } = await post(CFG.routes.storeUsulan, payload);
            if (ok) { closeTambahUsulan(); toast(data.message); refresh(); }
            else Swal.fire("Gagal", data.message, "error");
        } catch (err) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
        btn.disabled = false;
        btn.innerHTML = old;
    });

    // ---------------------------------------------------------------------
    // Prioritas / ranking (Khusus Wali Kelas)
    // ---------------------------------------------------------------------
    window.pipMovePrioritas = async function (id, direction) {
        try {
            const { ok, data } = await post(CFG.routes.prioritas, { id, direction });
            if (ok) refresh(); else Swal.fire("Gagal", data.message, "error");
        } catch (e) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    };

    document.addEventListener("change", async function (e) {
        const el = e.target;
        if (!el.classList?.contains("pip-rank-input")) return;
        const newRank = parseInt(el.value, 10);
        if (!newRank || newRank < 1) { el.value = el.dataset.current; return; }
        try {
            const { ok, data } = await post(CFG.routes.prioritas, { id: el.dataset.id, new_rank: newRank });
            if (ok) refresh(); else { el.value = el.dataset.current; Swal.fire("Gagal", data.message, "error"); }
        } catch (err) { el.value = el.dataset.current; Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    });

    document.addEventListener("keydown", function (e) {
        if (e.target.classList?.contains("pip-rank-input") && e.key === "Enter") {
            e.preventDefault();
            e.target.blur();
        }
    });

    // ---------------------------------------------------------------------
    // Hapus usulan
    // ---------------------------------------------------------------------
    window.pipHapusUsulan = async function (id, nama) {
        const r = await Swal.fire({
            title: "Hapus Usulan?", text: `Usulan PIP untuk ${nama} akan dihapus dan urutan prioritas disusun ulang.`,
            icon: "warning", showCancelButton: true, confirmButtonColor: "#ef4444", cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus!", cancelButtonText: "Batal",
        });
        if (!r.isConfirmed) return;
        try {
            const { ok, data } = await post(tpl(CFG.urlTemplates.destroyUsulan, id), {});
            if (ok) { toast(data.message); refresh(); } else Swal.fire("Gagal", data.message, "error");
        } catch (e) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    };

    // ---------------------------------------------------------------------
    // Verifikasi admin (single + bulk)
    // ---------------------------------------------------------------------
    document.addEventListener("change", async function (e) {
        const el = e.target;
        if (!el.classList?.contains("pip-verif-select")) return;
        const prev = el.dataset.current;
        el.disabled = true;
        try {
            const { ok, data } = await post(tpl(CFG.urlTemplates.verifikasi, el.dataset.id), { status_verifikasi: el.value });
            el.disabled = false;
            if (ok) { el.dataset.current = el.value; toast(data.message); refresh(); }
            else { el.value = prev; Swal.fire("Gagal", data.message, "error"); }
        } catch (err) { el.disabled = false; el.value = prev; Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    });

    function selectedIds() {
        return Array.from(document.querySelectorAll(".check-pip-row:checked")).map((c) => c.value);
    }
    function updateBulkBar() {
        const ids = selectedIds();
        const bar = document.getElementById("bulkActionBar");
        const cnt = document.getElementById("bulkSelectedCount");
        if (bar && cnt) { bar.style.display = ids.length ? "flex" : "none"; cnt.textContent = ids.length; }
        const all = document.getElementById("checkAllPip");
        const boxes = document.querySelectorAll(".check-pip-row");
        if (all) {
            all.checked = boxes.length > 0 && ids.length === boxes.length;
            all.indeterminate = ids.length > 0 && ids.length < boxes.length;
        }
    }
    document.addEventListener("change", function (e) {
        if (e.target.id === "checkAllPip") {
            document.querySelectorAll(".check-pip-row").forEach((c) => (c.checked = e.target.checked));
            updateBulkBar();
        } else if (e.target.classList?.contains("check-pip-row")) updateBulkBar();
    });
    window.addEventListener("sae:tableRefreshed", updateBulkBar);

    window.pipBulkVerifikasi = async function (action) {
        const ids = selectedIds();
        if (!ids.length) return;
        try {
            const { ok, data } = await post(CFG.routes.bulkVerifikasi, { ids, action });
            if (ok) { toast(data.message); refresh(); setTimeout(updateBulkBar, 600); }
            else Swal.fire("Gagal", data.message, "error");
        } catch (e) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    };

    // ---------------------------------------------------------------------
    // Kuota per kelas (admin) — 1 Tombol Simpan Tunggal & Distribusi Proporsional
    // ---------------------------------------------------------------------
    window.pipSimpanSemuaKuota = async function () {
        const inputs = document.querySelectorAll(".pip-kuota-input");
        if (!inputs.length) return;

        const kuotaMap = {};
        inputs.forEach((inp) => {
            const rombelId = inp.dataset.rombel;
            if (rombelId) {
                kuotaMap[rombelId] = parseInt(inp.value, 10) || 0;
            }
        });

        const btn = document.getElementById("btnSimpanSemuaKuota");
        const oldHtml = btn ? btn.innerHTML : "";
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        }

        try {
            const { ok, data } = await post(CFG.routes.kuota, {
                mode: "batch",
                kuota_map: kuotaMap,
                tahun: CFG.tahun,
            });
            if (ok) {
                toast(data.message);
                refresh();
            } else {
                Swal.fire("Gagal", data.message, "error");
            }
        } catch (e) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    document.addEventListener("keydown", function (e) {
        if (e.target.classList?.contains("pip-kuota-input") && e.key === "Enter") {
            e.preventDefault();
            pipSimpanSemuaKuota();
        }
    });

    window.pipSetTotalKuotaProporsional = async function () {
        const r = await Swal.fire({
            title: "Hitung Kuota Proporsional",
            text: "Masukkan total pagu kuota PIP sekolah. Sistem akan membagikannya secara adil berdasarkan persentase jumlah peserta didik aktif di setiap kelas.",
            input: "number",
            inputValue: 120,
            inputAttributes: { min: 1, max: 10000, step: 1 },
            showCancelButton: true,
            confirmButtonText: "Bagikan Proporsional",
            cancelButtonText: "Batal",
            confirmButtonColor: "#4f46e5",
        });
        if (!r.isConfirmed || !r.value) return;
        const total = parseInt(r.value, 10);
        if (isNaN(total) || total <= 0) {
            Swal.fire("Peringatan", "Harap masukkan angka total kuota yang valid.", "warning");
            return;
        }
        try {
            const { ok, data } = await post(CFG.routes.kuota, {
                mode: "distribute_proportional",
                total_kuota: total,
                tahun: CFG.tahun,
            });
            if (ok) {
                toast(data.message);
                refresh();
            } else {
                Swal.fire("Gagal", data.message, "error");
            }
        } catch (e) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
    };
    window.pipSetTotalKuota = window.pipSetTotalKuotaProporsional;

    window.pipSetKuotaSemua = async function () {
        const r = await Swal.fire({
            title: "Samakan Kuota per Kelas",
            text: "Tentukan alokasi kuota yang sama persis untuk setiap kelas (misal: 5 siswa per kelas).",
            input: "number",
            inputValue: 5,
            inputAttributes: { min: 0, max: 100 },
            showCancelButton: true,
            confirmButtonText: "Terapkan",
            cancelButtonText: "Batal",
        });
        if (!r.isConfirmed) return;
        try {
            const { ok, data } = await post(CFG.routes.kuota, { set_all: true, kuota: parseInt(r.value, 10) || 0, tahun: CFG.tahun });
            if (ok) { toast(data.message); refresh(); } else Swal.fire("Gagal", data.message, "error");
        } catch (e) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    };

    // ---------------------------------------------------------------------
    // Riwayat: import excel, tambah manual, hapus
    // ---------------------------------------------------------------------
    window.pipOnJenisSkChange = function (val) {
        const hint = document.getElementById("pipJenisSkHint");
        if (!hint) return;
        if (val === "nominasi") {
            hint.innerHTML = '<i class="fas fa-filter text-warning me-1"></i> SK Nominasi: Aktivasi rekening siswa baru/penerima baru. Siswa yang sudah ada pada SK Pemberian otomatis dilewati.';
        } else {
            hint.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> SK Pemberian: Menyimpan data penerima resmi beserta status pencairan dana.';
        }
    };

    window.openImportRiwayat = () => {
        document.getElementById("formImportRiwayat")?.reset();
        if (window.pipOnJenisSkChange) window.pipOnJenisSkChange("pemberian");
        openModal("modalImportRiwayat");
    };
    window.closeImportRiwayat = () => closeModal("modalImportRiwayat");
    window.openTambahRiwayat = () => { document.getElementById("formTambahRiwayat")?.reset(); document.getElementById("rwPdId").value = ""; openModal("modalTambahRiwayat"); };
    window.closeTambahRiwayat = () => closeModal("modalTambahRiwayat");

    document.getElementById("formImportRiwayat")?.addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = document.getElementById("pipSubmitImport");
        const old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
        try {
            const { ok, data } = await post(CFG.routes.importRiwayat, new FormData(this), true);
            if (ok) { closeImportRiwayat(); Swal.fire({ icon: "success", title: "Import Selesai", text: data.message }); refresh(); }
            else Swal.fire("Gagal", data.message, "error");
        } catch (err) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
        btn.disabled = false;
        btn.innerHTML = old;
    });

    bindAutocomplete({
        input: "rwCari", list: "rwAcList",
        extra: { all_years: 1 },
        onPick: (r, inp) => {
            document.getElementById("rwPdId").value = r.peserta_didik_id;
            document.getElementById("rwNama").value = r.nama;
            document.getElementById("rwNisn").value = r.nisn || "";
            document.getElementById("rwRombel").value = r.nama_rombel || "";
            inp.value = r.nama;
        },
    });

    document.getElementById("formTambahRiwayat")?.addEventListener("submit", async function (e) {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(this).entries());
        try {
            const { ok, data } = await post(CFG.routes.storeRiwayat, payload);
            if (ok) { closeTambahRiwayat(); toast(data.message); refresh(); } else Swal.fire("Gagal", data.message, "error");
        } catch (err) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    });

    window.pipHapusRiwayat = async function (id, nama) {
        const r = await Swal.fire({
            title: "Hapus Riwayat?", text: `Catatan riwayat PIP ${nama} akan dihapus permanen.`, icon: "warning",
            showCancelButton: true, confirmButtonColor: "#ef4444", cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus!", cancelButtonText: "Batal",
        });
        if (!r.isConfirmed) return;
        try {
            const { ok, data } = await post(tpl(CFG.urlTemplates.destroyRiwayat, id), {});
            if (ok) { toast(data.message); refresh(); } else Swal.fire("Gagal", data.message, "error");
        } catch (e) { Swal.fire("Error", "Gagal menghubungi server.", "error"); }
    };

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") ["modalTambahUsulan", "modalImportRiwayat", "modalTambahRiwayat"].forEach(closeModal);
    });
});
