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
        url.searchParams.delete("alumni_page");
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
        const map = { perPageSelect: "perPage", filterRombel: "rombel_id", filterStatusVerif: "status_verifikasi", filterTahap: "tahap", filterJenisSk: "jenis_sk", filterStatusKeluar: "status_keluar", filterStatusPip: "status_pip" };
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

    window.openTambahUsulanSiswa = function (pdId, nama, rombel, nisn) {
        window.openTambahUsulan();
        const idEl = document.getElementById("pipPdId");
        const infoEl = document.getElementById("pipPilihInfo");
        const submitBtn = document.getElementById("pipSubmitUsulan");
        if (idEl) idEl.value = pdId;
        if (infoEl) {
            infoEl.innerHTML = `Terpilih: <strong>${esc(nama)}</strong> (${esc(rombel || "-")}) &bull; NISN: ${esc(nisn || "-")}`;
        }
        if (submitBtn) submitBtn.disabled = false;
    };

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
        const { value: formValues } = await Swal.fire({
            title: "Hitung Kuota Proporsional",
            html: `
                <div style="text-align: left; margin-bottom: 14px;">
                    <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 6px;">
                        Pilih Sasaran Tingkat Kelas:
                    </label>
                    <select id="swalTargetKategori" class="form-control" style="font-size: 0.88rem; font-weight: 600; padding: 8px 10px; width: 100%;">
                        <option value="semua">Semua Tingkat (Kelas X, XI, XII)</option>
                        <option value="kelas_awal">Kelas Awal (Khusus Kelas X Saja)</option>
                        <option value="kelas_berjalan">Kelas Berjalan (Khusus Kelas XI & XII)</option>
                    </select>
                </div>
                <div style="text-align: left;">
                    <label style="font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 6px;">
                        Jumlah Pagu Kuota (Siswa):
                    </label>
                    <input type="number" id="swalTotalPagu" class="form-control" value="80" min="1" max="10000" style="font-size: 1rem; font-weight: 800; padding: 8px 10px; width: 100%;">
                    <div style="font-size: 0.74rem; color: #64748b; margin-top: 6px; line-height: 1.4;">
                        * Kuota dibagikan adil & presisi berdasarkan persentase jumlah peserta didik aktif pada kategori kelas yang dipilih.
                    </div>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-calculator me-1"></i> Bagikan Proporsional',
            cancelButtonText: "Batal",
            confirmButtonColor: "#4f46e5",
            cancelButtonColor: "#64748b",
            preConfirm: () => {
                const elKat = document.getElementById("swalTargetKategori");
                const elTot = document.getElementById("swalTotalPagu");
                const kategori = elKat ? elKat.value : "semua";
                const total = parseInt(elTot ? elTot.value : "0", 10);
                if (!total || total <= 0) {
                    Swal.showValidationMessage("Harap masukkan total pagu kuota yang valid.");
                    return false;
                }
                return { kategori, total };
            },
        });

        if (!formValues) return;

        try {
            const { ok, data } = await post(CFG.routes.kuota, {
                mode: "distribute_proportional",
                kategori_tingkat: formValues.kategori,
                total_kuota: formValues.total,
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

    // ---------------------------------------------------------------------
    // Export Usulan PIP ke Excel (.xlsx)
    // ---------------------------------------------------------------------
    window.pipExportUsulanExcel = function () {
        const curUrl = new URL(window.location.href);
        const exportBase = (CFG.routes && CFG.routes.exportUsulan) ? CFG.routes.exportUsulan : "/dashboard/pip/usulan/export";
        const exportUrl = new URL(exportBase, window.location.origin);
        ["tahun", "rombel_id", "status_verifikasi", "q"].forEach(k => {
            const val = curUrl.searchParams.get(k);
            if (val) exportUrl.searchParams.set(k, val);
        });
        const searchInp = document.getElementById("searchPip");
        if (searchInp && searchInp.value.trim()) {
            exportUrl.searchParams.set("q", searchInp.value.trim());
        }
        toast("Mengunduh data usulan PIP lengkap...", "info");
        window.location.href = exportUrl.toString();
    };

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") ["modalTambahUsulan", "modalImportRiwayat", "modalTambahRiwayat"].forEach(closeModal);
    });

    // ---------------------------------------------------------------------
    // Interactive Dashboard Analytics Charts (Chart.js)
    // ---------------------------------------------------------------------
    window._pipChartStatus = null;
    window._pipChartAlasan = null;
    window._pipChartTingkat = null;
    window._pipChartRiwayatTahun = null;

    function initPipDashboardCharts() {
        const payloadEl = document.getElementById("pipChartPayload");
        if (!payloadEl || typeof Chart === "undefined") return;

        let payload = {};
        try {
            payload = JSON.parse(payloadEl.textContent || "{}");
        } catch (e) {
            console.error("Gagal parse pipChartPayload:", e);
            return;
        }

        const computedStyle = getComputedStyle(document.documentElement);
        const isLight = document.documentElement.getAttribute("data-theme") === "light";
        const textColor = computedStyle.getPropertyValue("--text-color").trim() || (isLight ? "#0f172a" : "#f8fafc");
        const textMuted = computedStyle.getPropertyValue("--text-muted").trim() || (isLight ? "#64748b" : "#94a3b8");
        const borderColor = computedStyle.getPropertyValue("--border-color").trim() || "rgba(255,255,255,0.08)";

        // Destroy previous instances
        if (window._pipChartStatus) { window._pipChartStatus.destroy(); window._pipChartStatus = null; }
        if (window._pipChartAlasan) { window._pipChartAlasan.destroy(); window._pipChartAlasan = null; }
        if (window._pipChartTingkat) { window._pipChartTingkat.destroy(); window._pipChartTingkat = null; }
        if (window._pipChartRiwayatTahun) { window._pipChartRiwayatTahun.destroy(); window._pipChartRiwayatTahun = null; }

        // 1. Doughnut Status Usulan & Kuota
        const ctxStatus = document.getElementById("chartPipStatus")?.getContext("2d");
        if (ctxStatus && payload.status && payload.status.data) {
            const hasData = payload.status.data.some(v => v > 0);
            window._pipChartStatus = new Chart(ctxStatus, {
                type: "doughnut",
                data: {
                    labels: hasData ? payload.status.labels : ["Belum ada data"],
                    datasets: [{
                        data: hasData ? payload.status.data : [1],
                        backgroundColor: hasData ? payload.status.colors : ["#cbd5e1"],
                        borderWidth: 2,
                        borderColor: isLight ? "#ffffff" : "#1e293b",
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "70%",
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (item) => ` ${item.label}: ${item.raw} siswa`,
                            },
                        },
                    },
                },
            });
        }

        // 2. Horizontal Bar: 8 Kriteria Alasan Usulan
        const ctxAlasan = document.getElementById("chartPipAlasan")?.getContext("2d");
        if (ctxAlasan && payload.alasan && payload.alasan.labels) {
            const gradAlasan = ctxAlasan.createLinearGradient(0, 0, 300, 0);
            gradAlasan.addColorStop(0, "#6366f1");
            gradAlasan.addColorStop(1, "#818cf8");

            window._pipChartAlasan = new Chart(ctxAlasan, {
                type: "bar",
                data: {
                    labels: payload.alasan.labels.length ? payload.alasan.labels : ["Belum ada usulan"],
                    datasets: [{
                        label: "Jumlah Siswa Diusulkan",
                        data: payload.alasan.data.length ? payload.alasan.data : [0],
                        backgroundColor: gradAlasan,
                        borderRadius: 6,
                        maxBarThickness: 20,
                    }],
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (item) => ` ${item.raw} siswa diusulkan`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        y: {
                            ticks: {
                                color: textColor,
                                font: { size: 11, weight: 600 },
                                callback: function (val, idx) {
                                    const text = this.getLabelForValue(idx) || "";
                                    return text.length > 25 ? text.substring(0, 23) + "..." : text;
                                },
                            },
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        // 3. Bar: Usulan per Tingkat Kelas (Admin Only)
        const ctxTingkat = document.getElementById("chartPipTingkat")?.getContext("2d");
        if (ctxTingkat && payload.tingkat && payload.tingkat.labels) {
            window._pipChartTingkat = new Chart(ctxTingkat, {
                type: "bar",
                data: {
                    labels: payload.tingkat.labels,
                    datasets: [
                        {
                            label: "Total Peserta Didik",
                            data: payload.tingkat.siswa,
                            backgroundColor: "rgba(148, 163, 184, 0.75)",
                            borderRadius: 6,
                            maxBarThickness: 28,
                        },
                        {
                            label: "Diusulkan PIP",
                            data: payload.tingkat.usulan,
                            backgroundColor: "#6366f1",
                            borderRadius: 6,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "bottom",
                            labels: { color: textMuted, font: { size: 11, weight: 600 } },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: textMuted },
                            grid: { color: borderColor },
                        },
                        x: {
                            ticks: { color: textColor, font: { weight: 700 } },
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        // 4. Bar / Line: Tren Riwayat Penerimaan PIP per Tahun (Admin Only)
        const ctxRiwayatTahun = document.getElementById("chartPipRiwayatTahun")?.getContext("2d");
        if (ctxRiwayatTahun && payload.riwayat_tahun && payload.riwayat_tahun.labels) {
            const hasRiwayat = payload.riwayat_tahun.labels.length > 0;
            window._pipChartRiwayatTahun = new Chart(ctxRiwayatTahun, {
                type: "bar",
                data: {
                    labels: hasRiwayat ? payload.riwayat_tahun.labels : ["Belum ada riwayat"],
                    datasets: [{
                        label: "Penerima PIP (Siswa)",
                        data: hasRiwayat ? payload.riwayat_tahun.counts : [0],
                        backgroundColor: "rgba(16, 185, 129, 0.85)",
                        borderRadius: 6,
                        maxBarThickness: 32,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (item) => {
                                    const count = item.raw || 0;
                                    const nominal = payload.riwayat_tahun.nominals ? (payload.riwayat_tahun.nominals[item.dataIndex] || 0) : 0;
                                    const nomStr = new Intl.NumberFormat("id-ID").format(nominal);
                                    return ` ${count} siswa (Rp ${nomStr})`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        x: {
                            ticks: { color: textColor, font: { weight: 700 } },
                            grid: { display: false },
                        },
                    },
                },
            });
        }
    }

    // ---------------------------------------------------------------------
    // Filter & Live Search Rekap Rombel di Tab Dashboard
    // ---------------------------------------------------------------------
    function initPipRekapRombelFilter() {
        const filterBtns = document.querySelectorAll(".btn-rekap-filter");
        const searchInp = document.getElementById("filterRekapRombel");
        if (!filterBtns.length && !searchInp) return;

        let currentStatus = "all";
        let searchQuery = "";

        function applyRekapFilter() {
            const rows = document.querySelectorAll(".rekap-rombel-row");
            rows.forEach((row) => {
                const status = row.getAttribute("data-status");
                const name = row.getAttribute("data-name") || "";
                const matchStatus = currentStatus === "all" || status === currentStatus;
                const matchSearch = searchQuery === "" || name.includes(searchQuery);
                row.style.display = matchStatus && matchSearch ? "" : "none";
            });
        }

        filterBtns.forEach((btn) => {
            btn.addEventListener("click", function () {
                filterBtns.forEach((b) => {
                    b.classList.remove("active");
                    b.style.background = "transparent";
                    b.style.color = "var(--text-muted)";
                });
                this.classList.add("active");
                this.style.background = "var(--primary)";
                this.style.color = "#fff";
                currentStatus = this.getAttribute("data-status") || "all";
                applyRekapFilter();
            });
        });

        if (searchInp) {
            searchInp.addEventListener("input", function () {
                searchQuery = this.value.trim().toLowerCase();
                applyRekapFilter();
            });
        }
    }

    // Panggil saat DOM ready dan saat live table di-refresh
    initPipDashboardCharts();
    initPipRekapRombelFilter();
    window.addEventListener("sae:tableRefreshed", function () {
        initPipDashboardCharts();
        initPipRekapRombelFilter();
    });
});
