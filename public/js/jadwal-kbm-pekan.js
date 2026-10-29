/**
 * JavaScript Modular — Pemberlakuan Jadwal per Pekan (lapis 2).
 * Sistem Aplikasi Edukasi (SAE)
 *
 * Tanggung jawab: sinkron pekan dari Kalender Pendidikan, ubah rentang
 * tanggal/status satu pekan, serta filter/pencarian sisi klien.
 * Logika bisnis tetap di JadwalPekanService (server).
 */
(function () {
    "use strict";

    const $ = (sel, root) => (root || document).querySelector(sel);
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

    const csrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute("content");
        const input = document.querySelector('input[name="_token"]');
        return input ? input.value : "";
    };

    const notify = (icon, title, text) => {
        if (icon === "success") return window.SAE.alert(text, title, "success", 1800);
        return window.SAE.alert(text, title, icon === "error" ? "error" : "info");
    };

    const requestJson = async (url, method, payload) => {
        const res = await fetch(url, {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify(payload || {}),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || "Permintaan gagal diproses.");
        return data;
    };

    const setBusy = (btn, busy, labelBusy) => {
        if (!btn) return;
        if (busy) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
        }
    };

    // ---------------------------------------------------------------- generate
    const handleGenerate = (btn) => {
        const s = swal();
        const run = async () => {
            setBusy(btn, true);
            try {
                const data = await requestJson(btn.dataset.url, "POST", { perbarui_draft: true });
                notify("success", "Pekan Disinkronkan", data.message || "Pekan berhasil disinkronkan.");
                setTimeout(() => window.location.reload(), 1400);
            } catch (err) {
                setBusy(btn, false);
                notify("error", "Gagal Sinkron Pekan", err.message);
            }
        };

        window.SAE.confirm(
            "Pekan dibentuk otomatis dari rentang semester (Senin–Sabtu).<br><strong>Pekan baru selalu berstatus Draft.</strong> Tanggal pekan yang masih draft akan diperbarui, pekan yang sudah diberlakukan tidak diubah.",
            "Sinkron Pekan dengan Kalender?",
            "info",
            "Ya, Sinkronkan"
        ).then((ok) => { if (ok) run(); });
    };

    // ------------------------------------------------------------- ubah status
    const handleToggle = (btn) => {
        const status = btn.dataset.status;
        const label = btn.dataset.label || "pekan ini";
        const isAktif = status === "aktif";

        const title = isAktif ? "Berlakukan " + label + "?" : "Tarik " + label + " ke Draft?";
        const text = isAktif
            ? "Setelah diberlakukan, jadwal pada pekan ini akan dipakai sebagai dasar cetak bulanan dan tampil untuk guru/siswa."
            : "Saat draft, jadwal pada pekan ini tidak dipakai sebagai dasar cetak bulanan dan disembunyikan dari guru/siswa.";

        const run = async () => {
            setBusy(btn, true);
            try {
                const data = await requestJson(btn.dataset.url, "POST", { status });
                notify("success", "Status Pekan Diperbarui", data.message || "Status pekan berhasil diubah.");
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                setBusy(btn, false);
                notify("error", "Gagal Mengubah Status", err.message);
            }
        };

        window.SAE.confirm(
            text,
            title,
            isAktif ? "info" : "warning",
            isAktif ? "Ya, Berlakukan" : "Ya, Jadikan Draft"
        ).then((ok) => { if (ok) run(); });
    };

    // ------------------------------------------------------------ modal edit
    const openModal = (btn) => {
        const modal = $("#modalPekanJadwal");
        if (!modal) return;

        $("#pekanId").value = btn.dataset.id || "";
        $("#pekanUrl").value = btn.dataset.url || "";
        $("#pekanNomorLabel").textContent = "Pekan ke-" + (btn.dataset.pekanKe || "");
        $("#pekanTanggalMulai").value = btn.dataset.tanggalMulai || "";
        $("#pekanTanggalSelesai").value = btn.dataset.tanggalSelesai || "";
        $("#pekanSumber").value = btn.dataset.sumber || "";
        $("#pekanStatus").value = btn.dataset.status || "draft";
        $("#pekanCatatan").value = btn.dataset.catatan || "";
        $("#pekanFormError").style.display = "none";

        modal.style.display = "flex";
    };

    const closeModal = () => {
        const modal = $("#modalPekanJadwal");
        if (modal) modal.style.display = "none";
    };

    const submitModal = async (e) => {
        e.preventDefault();
        const form = e.currentTarget;
        const btn = $("#btnSimpanPekan");
        const errBox = $("#pekanFormError");

        const payload = {
            tanggal_mulai: $("#pekanTanggalMulai").value,
            tanggal_selesai: $("#pekanTanggalSelesai").value,
            sumber: $("#pekanSumber").value,
            status: $("#pekanStatus").value,
            catatan: $("#pekanCatatan").value,
        };

        setBusy(btn, true);
        errBox.style.display = "none";

        try {
            const data = await requestJson($("#pekanUrl").value, "PUT", payload);
            notify("success", "Pekan Diperbarui", data.message || "Pekan berhasil diperbarui.");
            setTimeout(() => window.location.reload(), 1200);
        } catch (err) {
            setBusy(btn, false);
            errBox.textContent = err.message;
            errBox.style.display = "block";
        }

        return false;
    };

    // ------------------------------------------------- filter & pencarian lokal
    const applyFilter = () => {
        const body = $("#pekanTableBody");
        if (!body) return;

        const keyword = ($("#pekanLiveSearch")?.value || "").trim().toLowerCase();
        const status = $("#pekanFilterStatus")?.value || "";
        const perPage = parseInt($("#pekanPerPageSelect")?.value || "10", 10);

        const rows = $$("tr[data-pekan-id]", body);
        let shown = 0;

        rows.forEach((row) => {
            const matchKeyword = keyword === "" || (row.dataset.search || "").includes(keyword);
            const matchStatus = status === "" || row.dataset.status === status;
            const visible = matchKeyword && matchStatus;

            if (visible && shown < perPage) {
                row.style.display = "";
                shown++;
            } else {
                row.style.display = "none";
            }
        });

        const info = $("#pekanPageInfo");
        if (info) {
            info.textContent = shown === 0 ? "Tidak ada pekan yang cocok" : `Menampilkan ${shown} dari ${rows.length} pekan`;
        }

        const total = $("#pekanTotalInfo");
        if (total) total.textContent = String(rows.length);
    };

    // ------------------------------------------------------------- bootstrap
    document.addEventListener("DOMContentLoaded", () => {
        applyFilter();

        $("#btnGeneratePekan")?.addEventListener("click", (e) => handleGenerate(e.currentTarget));

        document.addEventListener("click", (e) => {
            const toggleBtn = e.target.closest(".btn-toggle-pekan");
            if (toggleBtn) {
                e.preventDefault();
                handleToggle(toggleBtn);
                return;
            }

            const editBtn = e.target.closest(".btn-edit-pekan");
            if (editBtn) {
                e.preventDefault();
                openModal(editBtn);
            }
        });

        $("#btnTutupModalPekan")?.addEventListener("click", closeModal);
        $("#btnBatalPekan")?.addEventListener("click", closeModal);
        $("#formPekanJadwal")?.addEventListener("submit", submitModal);

        $("#modalPekanJadwal")?.addEventListener("click", (e) => {
            if (e.target.id === "modalPekanJadwal") closeModal();
        });

        $("#pekanLiveSearch")?.addEventListener("input", applyFilter);
        $("#pekanFilterStatus")?.addEventListener("change", applyFilter);
        $("#pekanPerPageSelect")?.addEventListener("change", applyFilter);
    });
})();
