/**
 * SAE - Master Data: Kalender Pendidikan Module JS
 */

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";

    /* ==========================================================================
       1. MODAL FORM CREATE / EDIT AGENDA
       ========================================================================== */
    const modal = document.getElementById("agendaModal");
    const form = document.getElementById("agendaForm");
    const modalTitle = document.getElementById("modalTitle");
    const methodField = document.getElementById("methodField");
    const inputNama = document.getElementById("inputNamaKegiatan");
    const inputTipe = document.getElementById("inputTipe");
    const inputWarna = document.getElementById("inputWarna");
    const inputMulai = document.getElementById("inputTanggalMulai");
    const inputSelesai = document.getElementById("inputTanggalSelesai");
    const inputLiburPd = document.getElementById("inputLiburPd");
    const inputLiburGuru = document.getElementById("inputLiburGuru");
    const inputLiburTendik = document.getElementById("inputLiburTendik");
    const inputKeterangan = document.getElementById("inputKeterangan");

    const btnTambah = document.getElementById("btnTambahAgenda");
    const btnEmptyTambah = document.getElementById("btnEmptyTambahAgenda");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const btnCancelModal = document.getElementById("btnCancelModal");

    const defaultColors = {
        libur_nasional: "#ef4444",
        libur_semester: "#f59e0b",
        libur_khusus: "#ec4899",
        kegiatan_sekolah: "#6366f1",
        ujian_asesmen: "#10b981",
        hari_efektif: "#06b6d4",
    };

    const openModal = (isEdit = false, item = null) => {
        if (!modal) return;
        if (isEdit && item) {
            modalTitle.innerHTML =
                '<i class="fas fa-pen-to-square text-primary me-2"></i> Edit Agenda Kalender';
            form.action = `/dashboard/master-data/kalender-pendidikan/${item.id}`;
            methodField.innerHTML =
                '<input type="hidden" name="_method" value="PUT">';

            if (inputNama) inputNama.value = item.nama_kegiatan || "";
            if (inputTipe) inputTipe.value = item.tipe || "kegiatan_sekolah";
            if (inputWarna) inputWarna.value = item.warna || "#3b82f6";
            if (inputMulai)
                inputMulai.value = item.tanggal_mulai
                    ? item.tanggal_mulai.substring(0, 10)
                    : "";
            if (inputSelesai)
                inputSelesai.value = item.tanggal_selesai
                    ? item.tanggal_selesai.substring(0, 10)
                    : "";
            if (inputLiburPd) inputLiburPd.checked = !!item.libur_pd;
            if (inputLiburGuru) inputLiburGuru.checked = !!item.libur_guru;
            if (inputLiburTendik)
                inputLiburTendik.checked = !!item.libur_tendik;
            if (inputKeterangan) inputKeterangan.value = item.keterangan || "";
        } else {
            modalTitle.innerHTML =
                '<i class="fas fa-calendar-plus text-primary me-2"></i> Tambah Agenda Kalender';
            form.action = "/dashboard/master-data/kalender-pendidikan";
            methodField.innerHTML = "";
            form.reset();

            const today = new Date().toISOString().substring(0, 10);
            if (inputMulai) inputMulai.value = today;
            if (inputSelesai) inputSelesai.value = today;
            if (inputWarna) inputWarna.value = defaultColors.kegiatan_sekolah;
        }
        modal.style.display = "flex";
    };

    const closeModal = () => {
        if (modal) modal.style.display = "none";
    };

    if (btnTambah) btnTambah.addEventListener("click", () => openModal(false));
    if (btnEmptyTambah)
        btnEmptyTambah.addEventListener("click", () => openModal(false));
    if (btnCloseModal) btnCloseModal.addEventListener("click", closeModal);
    if (btnCancelModal) btnCancelModal.addEventListener("click", closeModal);

    // Otomatis sesuaikan warna default dan checkbox libur saat kategori dipilih
    if (inputTipe) {
        inputTipe.addEventListener("change", () => {
            const val = inputTipe.value;
            if (inputWarna && defaultColors[val]) {
                inputWarna.value = defaultColors[val];
            }
            if (val.startsWith("libur_")) {
                if (inputLiburPd) inputLiburPd.checked = true;
                if (val === "libur_nasional" || val === "libur_semester") {
                    if (inputLiburGuru) inputLiburGuru.checked = true;
                    if (inputLiburTendik)
                        inputLiburTendik.checked = val === "libur_nasional";
                }
            } else if (val === "hari_efektif") {
                if (inputLiburPd) inputLiburPd.checked = false;
                if (inputLiburGuru) inputLiburGuru.checked = false;
                if (inputLiburTendik) inputLiburTendik.checked = false;
            }
        });
    }

    // Pastikan tanggal selesai tidak lebih kecil dari tanggal mulai
    if (inputMulai && inputSelesai) {
        inputMulai.addEventListener("change", () => {
            if (!inputSelesai.value || inputSelesai.value < inputMulai.value) {
                inputSelesai.value = inputMulai.value;
            }
            inputSelesai.min = inputMulai.value;
        });
    }

    document.querySelectorAll(".btn-edit-agenda").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                openModal(true, item);
            } catch (err) {
                console.error("Gagal mengurai data agenda", err);
            }
        });
    });

    /* ==========================================================================
       2. MODAL VIEW DETAIL AGENDA
       ========================================================================== */
    const viewModal = document.getElementById("viewModal");
    const viewJudul = document.getElementById("viewJudul");
    const viewTipeBadge = document.getElementById("viewTipeBadge");
    const viewTanggal = document.getElementById("viewTanggal");
    const viewDampakBadges = document.getElementById("viewDampakBadges");
    const viewKeterangan = document.getElementById("viewKeterangan");
    const viewPembuat = document.getElementById("viewPembuat");
    const viewWaktu = document.getElementById("viewWaktu");
    const btnCloseViewModal = document.getElementById("btnCloseViewModal");
    const btnTutupView = document.getElementById("btnTutupView");

    const closeViewModal = () => {
        if (viewModal) viewModal.style.display = "none";
    };

    if (btnCloseViewModal)
        btnCloseViewModal.addEventListener("click", closeViewModal);
    if (btnTutupView) btnTutupView.addEventListener("click", closeViewModal);

    document.querySelectorAll(".btn-view-agenda").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                if (viewJudul)
                    viewJudul.textContent = item.nama_kegiatan || "-";
                if (viewTipeBadge) {
                    viewTipeBadge.textContent =
                        item.tipe_label || item.tipe || "Agenda";
                    viewTipeBadge.style.backgroundColor =
                        item.warna || "#3b82f6";
                    viewTipeBadge.style.color = "#fff";
                }

                const tglMulai = item.tanggal_mulai
                    ? item.tanggal_mulai.substring(0, 10)
                    : "";
                const tglSelesai = item.tanggal_selesai
                    ? item.tanggal_selesai.substring(0, 10)
                    : "";
                if (viewTanggal) {
                    viewTanggal.textContent =
                        tglMulai === tglSelesai
                            ? tglMulai
                            : `${tglMulai} s.d. ${tglSelesai}`;
                }

                if (viewDampakBadges) {
                    let badgesHtml = "";
                    badgesHtml += item.libur_pd
                        ? '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px;"><i class="fas fa-ban me-1"></i> Libur Siswa</span>'
                        : '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px;"><i class="fas fa-check me-1"></i> Siswa Masuk</span>';

                    badgesHtml += item.libur_guru
                        ? '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px;"><i class="fas fa-ban me-1"></i> Libur Guru</span>'
                        : '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px;"><i class="fas fa-check me-1"></i> Guru Mengajar</span>';

                    badgesHtml += item.libur_tendik
                        ? '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px;"><i class="fas fa-ban me-1"></i> Libur Tendik</span>'
                        : '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px;"><i class="fas fa-check me-1"></i> Tendik Hadir</span>';

                    viewDampakBadges.innerHTML = badgesHtml;
                }

                if (viewKeterangan) {
                    viewKeterangan.textContent =
                        item.keterangan || "Tidak ada keterangan tambahan.";
                }
                if (viewPembuat)
                    viewPembuat.textContent = item.created_by || "Sistem";
                if (viewWaktu)
                    viewWaktu.textContent = item.created_at
                        ? item.created_at.substring(0, 16)
                        : "-";

                if (viewModal) viewModal.style.display = "flex";
            } catch (err) {
                console.error("Gagal membuka pratinjau agenda", err);
            }
        });
    });

    // Menutup modal jika klik di luar box (backdrop)
    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
        if (e.target === viewModal) closeViewModal();
    });

    /* ==========================================================================
       3. FILTER & LIVE SEARCH
       ========================================================================== */
    const applyFilters = () => {
        const url = new URL(window.location.href);
        const perPage = document.getElementById("perPageSelect")?.value;
        const tipe = document.getElementById("filterTipe")?.value;
        const dampak = document.getElementById("filterDampak")?.value;
        const bulan = document.getElementById("filterBulan")?.value;
        const q = document.getElementById("liveSearch")?.value.trim();

        if (perPage) url.searchParams.set("perPage", perPage);
        else url.searchParams.delete("perPage");

        if (tipe) url.searchParams.set("tipe", tipe);
        else url.searchParams.delete("tipe");

        if (dampak) url.searchParams.set("dampak", dampak);
        else url.searchParams.delete("dampak");

        if (bulan) url.searchParams.set("bulan", bulan);
        else url.searchParams.delete("bulan");

        if (q) url.searchParams.set("q", q);
        else url.searchParams.delete("q");

        url.searchParams.set("page", "1");
        window.location.href = url.toString();
    };

    document
        .getElementById("perPageSelect")
        ?.addEventListener("change", applyFilters);
    document
        .getElementById("filterTipe")
        ?.addEventListener("change", applyFilters);
    document
        .getElementById("filterDampak")
        ?.addEventListener("change", applyFilters);
    document
        .getElementById("filterBulan")
        ?.addEventListener("change", applyFilters);

    const liveSearch = document.getElementById("liveSearch");
    const clearSearch = document.getElementById("clearSearch");
    let debounceTimer = null;

    if (liveSearch) {
        liveSearch.addEventListener("input", () => {
            if (clearSearch) {
                if (liveSearch.value) clearSearch.classList.add("visible");
                else clearSearch.classList.remove("visible");
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                applyFilters();
            }, 850);
        });

        liveSearch.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                clearTimeout(debounceTimer);
                applyFilters();
            }
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener("click", () => {
            if (liveSearch) {
                liveSearch.value = "";
                clearSearch.classList.remove("visible");
                applyFilters();
            }
        });
    }

    // Sortable Table Headers
    document.querySelectorAll(".sortable-th[data-sort]").forEach((th) => {
        th.addEventListener("click", () => {
            const sortKey = th.dataset.sort;
            if (!sortKey) return;
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort");
            const currentDir = url.searchParams.get("sort_dir") || "asc";

            if (currentSort === sortKey) {
                url.searchParams.set(
                    "sort_dir",
                    currentDir === "asc" ? "desc" : "asc",
                );
            } else {
                url.searchParams.set("sort", sortKey);
                url.searchParams.set("sort_dir", "asc");
            }

            window.location.href = url.toString();
        });
    });
});
