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
        const map = { perPageSelect: "perPage", filterRombel: "rombel_id", filterStatusVerif: "status_verifikasi", filterFase: "fase", filterTahap: "tahap", filterJenisSk: "jenis_sk", filterStatusKeluar: "status_keluar", filterStatusPip: "status_pip" };
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
    // Edit Usulan (Wali Kelas & Admin)
    // ---------------------------------------------------------------------
    window.pipBukaEditUsulan = function (id, nama, alasanList, keterangan) {
        const f = document.getElementById("formEditUsulan");
        if (!f) return;
        document.getElementById("editUsulanId").value = id;
        document.getElementById("editUsulanNamaPd").textContent = nama || "Peserta Didik";
        document.getElementById("editPipKeterangan").value = keterangan || "";

        const alasanArr = Array.isArray(alasanList) ? alasanList : [];
        document.querySelectorAll(".edit-alasan-cb").forEach((cb) => {
            cb.checked = alasanArr.includes(cb.value);
        });

        openModal("modalEditUsulan");
    };
    window.closeEditUsulan = () => closeModal("modalEditUsulan");

    document.getElementById("formEditUsulan")?.addEventListener("submit", async function (e) {
        e.preventDefault();
        const id = document.getElementById("editUsulanId")?.value;
        if (!id) return;

        const btn = document.getElementById("btnSubmitEditUsulan");
        const oldHtml = btn ? btn.innerHTML : "";
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        }

        const fd = new FormData(this);
        const payload = {
            keterangan: fd.get("keterangan"),
            alasan_usulan: fd.getAll("alasan_usulan[]"),
        };

        if (!payload.alasan_usulan.length) {
            Swal.fire("Belum Lengkap", "Pilih minimal satu kriteria / alasan usulan.", "warning");
            if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
            return;
        }

        try {
            const url = tpl(CFG.urlTemplates.updateUsulan, id);
            const { ok, data } = await post(url, payload);
            if (ok) {
                closeEditUsulan();
                toast(data.message);
                refresh();
            } else {
                Swal.fire("Gagal", data.message || "Terjadi kesalahan.", "error");
            }
        } catch (err) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
        }
    });

    // ---------------------------------------------------------------------
    // Riwayat Perubahan Usulan
    // ---------------------------------------------------------------------
    window.pipLihatRiwayatUsulan = async function (id) {
        const modal = document.getElementById("modalRiwayatUsulan");
        if (!modal) return;

        const listContainer = document.getElementById("riwayatPerubahanList");
        const namaEl = document.getElementById("riwayatPdNama");
        const metaEl = document.getElementById("riwayatPdMeta");

        if (listContainer) {
            listContainer.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 24px;"><i class="fas fa-spinner fa-spin me-1"></i> Memuat riwayat...</div>';
        }
        if (namaEl) namaEl.textContent = "Memuat data...";
        if (metaEl) metaEl.textContent = "";

        openModal("modalRiwayatUsulan");

        try {
            const url = tpl(CFG.urlTemplates.riwayatUsulan, id);
            const res = await fetch(url, {
                headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                if (listContainer) listContainer.innerHTML = `<div class="text-danger p-3">${esc(data.message || "Gagal memuat riwayat.")}</div>`;
                return;
            }

            const u = data.usulan || {};
            const logs = data.riwayat || [];

            if (namaEl) namaEl.textContent = u.nama_pd || "Peserta Didik";
            if (metaEl) {
                metaEl.innerHTML = `Kelas: <strong>${esc(u.rombel || "-")}</strong> &bull; NISN: <strong>${esc(u.nisn || "-")}</strong> &bull; Didaftarkan oleh: <strong>${esc(u.diusulkan_oleh || "-")}</strong> (${esc(u.created_at || "-")})`;
            }

            if (!logs.length) {
                if (listContainer) {
                    listContainer.innerHTML = `
                        <div style="text-align: center; color: var(--text-muted); padding: 30px;">
                            <i class="fas fa-history" style="font-size: 2rem; opacity: 0.35; margin-bottom: 8px; display: block;"></i>
                            Data usulan ini belum pernah mengalami perubahan sejak awal didaftarkan oleh Wali Kelas.
                        </div>`;
                }
                return;
            }

            let html = "";
            logs.forEach((log) => {
                const detailsHtml = Array.isArray(log.detail) && log.detail.length
                    ? `<ul style="margin: 6px 0 0 18px; padding: 0; font-size: 0.78rem; color: var(--text-muted);">
                        ${log.detail.map(d => `<li style="margin-bottom: 2px;">${esc(d)}</li>`).join("")}
                       </ul>`
                    : "";

                html += `
                    <div style="padding: 12px 14px; border-radius: 8px; background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-left: 3px solid #f59e0b;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; flex-wrap: wrap; gap: 6px;">
                            <span style="font-weight: 700; font-size: 0.84rem; color: var(--text-color);">
                                <i class="fas fa-pen-to-square text-warning me-1"></i> ${esc(log.ringkasan || "Perubahan data")}
                            </span>
                            <span class="badge badge-outline" style="font-size: 0.68rem; padding: 1px 6px;">
                                ${esc(log.waktu || "-")}
                            </span>
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">
                            Diperbarui oleh: <strong>${esc(log.editor || "-")}</strong>
                        </div>
                        ${detailsHtml}
                    </div>
                `;
            });

            if (listContainer) listContainer.innerHTML = html;

        } catch (e) {
            if (listContainer) listContainer.innerHTML = `<div class="text-danger p-3">Gagal mengambil data dari server.</div>`;
        }
    };
    window.closeRiwayatUsulan = () => closeModal("modalRiwayatUsulan");

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
    window.pipHapusUsulan = async function (id, nama, status) {
        if (status === "sudah_diajukan" || status === "ditolak") {
            const label = status === "sudah_diajukan" ? "Sudah Diusulkan" : "Ditolak";
            Swal.fire({
                icon: "warning",
                title: "Tidak Dapat Dihapus",
                text: `Usulan PIP untuk ${nama} berstatus "${label}" sehingga tidak dapat dihapus.`,
            });
            return;
        }

        const r = await Swal.fire({
            title: "Hapus Usulan?",
            text: `Usulan PIP untuk ${nama} akan dihapus dan urutan prioritas disusun ulang.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        });
        if (!r.isConfirmed) return;
        try {
            const { ok, data } = await post(tpl(CFG.urlTemplates.destroyUsulan, id), {});
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

    // ---------------------------------------------------------------------
    // Verifikasi admin (single + bulk)
    // ---------------------------------------------------------------------
    window.pipUbahStatusVerifikasi = async function (el) {
        if (!el || el.dataset.busy === "1") return;
        const prev = el.dataset.current;
        const newVal = el.value;
        const id = el.dataset.id;
        if (!id) return;

        el.dataset.busy = "1";
        el.disabled = true;

        try {
            const url = tpl(CFG.urlTemplates.verifikasi, id);
            const { ok, data } = await post(url, { status_verifikasi: newVal });
            el.disabled = false;
            el.dataset.busy = "0";

            if (ok) {
                el.dataset.current = newVal;
                toast(data.message || "Status usulan berhasil diperbarui.");
                refresh();
            } else {
                el.value = prev;
                Swal.fire("Gagal", data.message || "Gagal mengubah status verifikasi.", "error");
            }
        } catch (err) {
            el.disabled = false;
            el.dataset.busy = "0";
            el.value = prev;
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
    };

    document.addEventListener("change", function (e) {
        const el = e.target;
        if (!el || !el.classList?.contains("pip-verif-select")) return;
        if (typeof window.pipUbahStatusVerifikasi === "function") {
            window.pipUbahStatusVerifikasi(el);
        }
    });

    function selectedIds() {
        return Array.from(document.querySelectorAll(".check-pip-row:checked"))
            .map((c) => parseInt(c.value, 10))
            .filter((n) => !isNaN(n));
    }

    function updateBulkBar() {
        const ids = selectedIds();
        const bar = document.getElementById("bulkActionBar");
        const cnt = document.getElementById("bulkSelectedCount");
        if (bar && cnt) {
            bar.style.display = ids.length ? "flex" : "none";
            cnt.textContent = ids.length;
        }
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
        } else if (e.target.classList?.contains("check-pip-row")) {
            updateBulkBar();
        }
    });
    window.addEventListener("sae:tableRefreshed", updateBulkBar);

    window.pipBulkVerifikasi = async function (action) {
        const ids = selectedIds();
        if (!ids.length) {
            toast("Pilih minimal satu usulan siswa terlebih dahulu.", "warning");
            return;
        }

        const actionLabels = {
            set_sudah_diajukan: "Sudah Diusulkan",
            set_belum_diajukan: "Belum Diusulkan",
            set_ditolak: "Ditolak",
        };
        const targetLabel = actionLabels[action] || action;

        const confirmResult = await Swal.fire({
            title: `Tandai ${ids.length} Usulan?`,
            text: `Ubah status verifikasi ${ids.length} usulan terpilih menjadi '${targetLabel}'.`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: action === "set_ditolak" ? "#ef4444" : "#10b981",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Lanjutkan",
            cancelButtonText: "Batal",
        });
        if (!confirmResult.isConfirmed) return;

        try {
            const { ok, data } = await post(CFG.routes.bulkVerifikasi, { ids, action });
            if (ok) {
                toast(data.message);
                refresh();
                setTimeout(updateBulkBar, 600);
            } else {
                Swal.fire("Gagal", data.message || "Gagal memproses verifikasi massal.", "error");
            }
        } catch (e) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
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
        ["tahun", "fase", "rombel_id", "status_verifikasi", "q"].forEach(k => {
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

    // ---------------------------------------------------------------------
    // Pengaturan Kunci & Batas Waktu PIP (Admin Only)
    // ---------------------------------------------------------------------
    window.pipSetPresetDeadline = function (days) {
        const inp = document.getElementById("pipBatasWaktu");
        if (!inp) return;
        const d = new Date();
        d.setDate(d.getDate() + days);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const dd = String(d.getDate()).padStart(2, "0");
        inp.value = `${yyyy}-${mm}-${dd}T23:59`;
    };

    window.pipSetPresetDeadlineEOM = function () {
        const inp = document.getElementById("pipBatasWaktu");
        if (!inp) return;
        const now = new Date();
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        const yyyy = lastDay.getFullYear();
        const mm = String(lastDay.getMonth() + 1).padStart(2, "0");
        const dd = String(lastDay.getDate()).padStart(2, "0");
        inp.value = `${yyyy}-${mm}-${dd}T23:59`;
    };

    window.pipClearDeadline = function () {
        const inp = document.getElementById("pipBatasWaktu");
        if (inp) inp.value = "";
    };

    window.pipOnFaseSelect = function (fase, label) {
        const judulInp = document.getElementById("pipJudulUsulan");
        if (judulInp && (!judulInp.value || judulInp.value.includes("Usulan PIP"))) {
            const thn = CFG.tahun || new Date().getFullYear();
            judulInp.value = `Usulan PIP Tahun ${thn} — ${label}`;
        }
    };

    document.getElementById("formPengaturanPip")?.addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = document.getElementById("btnSimpanPengaturanPip");
        const oldHtml = btn ? btn.innerHTML : "";
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
        }

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());
        payload.kunci_otomatis = this.querySelector('input[name="kunci_otomatis"]')?.checked ? 1 : 0;

        try {
            const url = (CFG.routes && CFG.routes.updatePengaturan) ? CFG.routes.updatePengaturan : "/dashboard/pip/pengaturan";
            const { ok, data } = await post(url, payload);
            if (ok) {
                toast(data.message);
                refresh();
            } else {
                Swal.fire("Gagal", data.message || "Terjadi kesalahan.", "error");
            }
        } catch (err) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
        }
    });

    // ---------------------------------------------------------------------
    // Realtime Countdown Timer
    // ---------------------------------------------------------------------
    window._pipCdInterval = null;

    function initPipCountdown() {
        const wrap = document.getElementById("pipCountdownWrap");
        if (!wrap) return;

        if (window._pipCdInterval) {
            clearInterval(window._pipCdInterval);
            window._pipCdInterval = null;
        }

        const deadlineIso = wrap.getAttribute("data-deadline");
        const isLocked = wrap.getAttribute("data-locked") === "1";

        const daysEl = document.getElementById("pipCdDays");
        const hoursEl = document.getElementById("pipCdHours");
        const minsEl = document.getElementById("pipCdMinutes");
        const secsEl = document.getElementById("pipCdSeconds");
        const badgeEl = document.getElementById("pipCdBadge");

        if (isLocked) {
            return;
        }

        if (!deadlineIso) {
            return;
        }

        const targetTime = new Date(deadlineIso).getTime();

        function tick() {
            const now = new Date().getTime();
            const diff = targetTime - now;

            if (diff <= 0) {
                if (daysEl) daysEl.textContent = "00";
                if (hoursEl) hoursEl.textContent = "00";
                if (minsEl) minsEl.textContent = "00";
                if (secsEl) secsEl.textContent = "00";
                if (badgeEl) {
                    badgeEl.className = "badge badge-danger";
                    badgeEl.innerHTML = '<i class="fas fa-clock-rotate-left me-1"></i> Batas Waktu Berakhir';
                }
                return;
            }

            const totalSec = Math.floor(diff / 1000);
            const d = Math.floor(totalSec / 86400);
            const h = Math.floor((totalSec % 86400) / 3600);
            const m = Math.floor((totalSec % 3600) / 60);
            const s = totalSec % 60;

            if (daysEl) daysEl.textContent = String(d).padStart(2, "0");
            if (hoursEl) hoursEl.textContent = String(h).padStart(2, "0");
            if (minsEl) minsEl.textContent = String(m).padStart(2, "0");
            if (secsEl) secsEl.textContent = String(s).padStart(2, "0");
        }

        tick();
        window._pipCdInterval = setInterval(tick, 1000);
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") ["modalTambahUsulan", "modalEditUsulan", "modalRiwayatUsulan", "modalImportRiwayat", "modalTambahRiwayat"].forEach(closeModal);
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

        // Custom Plugin 1: Angka langsung di segmen donat & angka total di tengah donat
        const pipDoughnutDataLabelsPlugin = {
            id: "pipDoughnutDataLabels",
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || !meta.data.length) return;

                const dataset = data.datasets[0];
                const total = dataset.data.reduce((a, b) => a + Number(b || 0), 0);
                if (total <= 0) return;

                ctx.save();
                meta.data.forEach((element, index) => {
                    const val = dataset.data[index];
                    if (!val || val <= 0) return;

                    const pos = element.tooltipPosition();
                    const pct = Math.round((val / total) * 100);

                    ctx.fillStyle = "#ffffff";
                    ctx.font = "bold 12px 'Plus Jakarta Sans', sans-serif";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.shadowColor = "rgba(0, 0, 0, 0.45)";
                    ctx.shadowBlur = 4;

                    if (pct >= 8) {
                        ctx.fillText(Number(val).toLocaleString("id-ID"), pos.x, pos.y - 5);
                        ctx.font = "600 9px 'Plus Jakarta Sans', sans-serif";
                        ctx.fillStyle = "rgba(255, 255, 255, 0.95)";
                        ctx.fillText(pct + "%", pos.x, pos.y + 7);
                    } else {
                        ctx.fillText(Number(val).toLocaleString("id-ID"), pos.x, pos.y);
                    }
                });

                // Teks & Angka Total di Tengah Donat
                if (meta.data[0]) {
                    const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                    const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                    ctx.shadowBlur = 0;
                    ctx.fillStyle = textMuted;
                    ctx.font = "700 9px 'Plus Jakarta Sans', sans-serif";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    const labelCenter = payload.status?.is_admin ? "TOTAL USULAN" : "USULAN KELAS";
                    ctx.fillText(labelCenter, centerX, centerY - 10);

                    ctx.fillStyle = textColor;
                    ctx.font = "800 18px 'Plus Jakarta Sans', sans-serif";
                    const centerVal = payload.status?.total_usulan !== undefined
                        ? payload.status.total_usulan
                        : ((dataset.data[0] || 0) + (dataset.data[1] || 0) + (dataset.data[2] || 0));
                    ctx.fillText(Number(centerVal).toLocaleString("id-ID"), centerX, centerY + 8);
                }
                ctx.restore();
            },
        };

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
                plugins: hasData ? [pipDoughnutDataLabelsPlugin] : [],
            });
        }

        // Custom Plugin 2: Angka langsung di samping kanan batang diagram alasan
        const pipHorizontalBarLabelsPlugin = {
            id: "pipHorizontalBarLabels",
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data) return;

                ctx.save();
                meta.data.forEach((bar, index) => {
                    const val = data.datasets[0].data[index];
                    if (val !== undefined && val !== null && val > 0) {
                        const text = Number(val).toLocaleString("id-ID") + " Siswa";
                        ctx.fillStyle = textColor;
                        ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
                        ctx.textAlign = "left";
                        ctx.textBaseline = "middle";
                        ctx.fillText(text, bar.x + 8, bar.y);
                    }
                });
                ctx.restore();
            },
        };

        // 2. Horizontal Bar: 8 Kriteria Alasan Usulan
        const ctxAlasan = document.getElementById("chartPipAlasan")?.getContext("2d");
        if (ctxAlasan && payload.alasan && payload.alasan.labels) {
            const gradAlasan = ctxAlasan.createLinearGradient(0, 0, 300, 0);
            gradAlasan.addColorStop(0, "#6366f1");
            gradAlasan.addColorStop(1, "#818cf8");

            const hasAlasanData = payload.alasan.data && payload.alasan.data.some(v => v > 0);

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
                    layout: {
                        padding: { right: 65, top: 4, bottom: 4 },
                    },
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
                            grace: "15%",
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
                plugins: hasAlasanData ? [pipHorizontalBarLabelsPlugin] : [],
            });
        }

        // Custom Plugin 3: Angka langsung di atas batang diagram tingkat kelas
        const pipGroupedBarLabelsPlugin = {
            id: "pipGroupedBarLabels",
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                ctx.save();
                data.datasets.forEach((dataset, dIdx) => {
                    const meta = chart.getDatasetMeta(dIdx);
                    if (!meta || meta.hidden) return;

                    meta.data.forEach((bar, index) => {
                        const val = dataset.data[index];
                        if (val !== undefined && val !== null && val > 0) {
                            ctx.fillStyle = dIdx === 1 ? "#6366f1" : (isLight ? "#334155" : "#e2e8f0");
                            ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
                            ctx.textAlign = "center";
                            ctx.textBaseline = "bottom";
                            ctx.fillText(Number(val).toLocaleString("id-ID"), bar.x, bar.y - 3);
                        }
                    });
                });
                ctx.restore();
            },
        };

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
                    layout: {
                        padding: { top: 18 },
                    },
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
                            grace: "15%",
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        x: {
                            ticks: { color: textColor, font: { weight: 700 } },
                            grid: { display: false },
                        },
                    },
                },
                plugins: [pipGroupedBarLabelsPlugin],
            });
        }

        // Custom Plugin 4: Angka langsung di atas batang tren riwayat tahun
        const pipRiwayatBarLabelsPlugin = {
            id: "pipRiwayatBarLabels",
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data) return;

                ctx.save();
                meta.data.forEach((bar, index) => {
                    const val = data.datasets[0].data[index];
                    if (val !== undefined && val !== null && val > 0) {
                        const text = Number(val).toLocaleString("id-ID") + " Siswa";
                        ctx.fillStyle = isLight ? "#059669" : "#34d399";
                        ctx.font = "bold 11px 'Plus Jakarta Sans', sans-serif";
                        ctx.textAlign = "center";
                        ctx.textBaseline = "bottom";
                        ctx.fillText(text, bar.x, bar.y - 4);
                    }
                });
                ctx.restore();
            },
        };

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
                    layout: {
                        padding: { top: 18 },
                    },
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
                            grace: "15%",
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        x: {
                            ticks: { color: textColor, font: { weight: 700 } },
                            grid: { display: false },
                        },
                    },
                },
                plugins: hasRiwayat ? [pipRiwayatBarLabelsPlugin] : [],
            });
        }
    }

    // ---------------------------------------------------------------------
    // Filter & Live Search Rekap Rombel di Tab Dashboard (Status, Tingkat, & Search)
    // ---------------------------------------------------------------------
    function initPipRekapRombelFilter() {
        const filterStatusBtns = document.querySelectorAll(".btn-rekap-filter");
        const filterTingkatBtns = document.querySelectorAll(".btn-rekap-tingkat-filter");
        const searchInp = document.getElementById("filterRekapRombel");
        if (!filterStatusBtns.length && !filterTingkatBtns.length && !searchInp) return;

        let currentStatus = "all";
        let currentTingkat = "all";
        let searchQuery = "";

        function applyRekapFilter() {
            const rows = document.querySelectorAll(".rekap-rombel-row");
            let visIndex = 1;
            rows.forEach((row) => {
                const status = row.getAttribute("data-status");
                const tingkat = row.getAttribute("data-tingkat");
                const name = row.getAttribute("data-name") || "";
                const matchStatus = currentStatus === "all" || status === currentStatus;
                const matchTingkat = currentTingkat === "all" || tingkat === currentTingkat;
                const matchSearch = searchQuery === "" || name.includes(searchQuery);
                const isVisible = matchStatus && matchTingkat && matchSearch;
                row.style.display = isVisible ? "" : "none";
                if (isVisible) {
                    const noCell = row.querySelector(".rekap-row-no");
                    if (noCell) noCell.textContent = visIndex++;
                }
            });
        }

        filterStatusBtns.forEach((btn) => {
            btn.addEventListener("click", function () {
                filterStatusBtns.forEach((b) => {
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

        filterTingkatBtns.forEach((btn) => {
            btn.addEventListener("click", function () {
                filterTingkatBtns.forEach((b) => {
                    b.classList.remove("active");
                    b.style.background = "transparent";
                    b.style.color = "var(--text-muted)";
                });
                this.classList.add("active");
                this.style.background = "var(--primary)";
                this.style.color = "#fff";
                currentTingkat = this.getAttribute("data-tingkat") || "all";
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
    initPipCountdown();
    window.addEventListener("sae:tableRefreshed", function () {
        initPipDashboardCharts();
        initPipRekapRombelFilter();
        initPipCountdown();
    });
});
