/**
 * SAE - Master Data: Kalender Pendidikan Module JS
 * Standar Baku SAE:
 * - Real-time Live Search & Filter Asinkron (window.refreshLiveTable)
 * - Event Delegation untuk Edit, Detail, Hapus (Survives AJAX Re-renders)
 * - Sortable Column Header Server-Side
 * - AJAX Create & Update dengan SweetAlert2 Notifikasi
 */

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    /* ==========================================================================
       1. MODAL FORM CREATE / EDIT AGENDA
       ========================================================================== */
    const modal = document.getElementById("agendaModal");
    const form = document.getElementById("agendaForm");
    const modalTitle = document.getElementById("modalTitle");
    const methodField = document.getElementById("methodField");
    const inputNama = document.getElementById("inputNamaKegiatan");
    const inputModePresensi = document.getElementById("inputModePresensi");
    const inputTipe = document.getElementById("inputTipe");
    const inputWarna = document.getElementById("inputWarna");
    const inputMulai = document.getElementById("inputTanggalMulai");
    const inputSelesai = document.getElementById("inputTanggalSelesai");
    const inputLiburPd = document.getElementById("inputLiburPd");
    const inputLiburGuru = document.getElementById("inputLiburGuru");
    const inputLiburTendik = document.getElementById("inputLiburTendik");
    const inputKeterangan = document.getElementById("inputKeterangan");

    const btnTambah = document.getElementById("btnTambahAgenda");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const btnCancelModal = document.getElementById("btnCancelModal");

    const defaultColors = {
        libur_nasional: "#ef4444",
        libur_semester: "#f59e0b",
        libur_khusus: "#ec4899",
        kegiatan_sekolah: "#6366f1",
        ujian_asesmen: "#a855f7",
        hari_efektif: "#10b981",
        pembelajaran_daring: "#3b82f6",
    };

    const openModal = (isEdit = false, item = null) => {
        if (!modal) return;
        if (isEdit && item) {
            modalTitle.innerHTML =
                '<i class="fas fa-pen-to-square text-primary me-2"></i> Edit Agenda Kalender';
            form.action = `/dashboard/master-data/kalender-pendidikan/${item.id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

            if (inputNama) inputNama.value = item.nama_kegiatan || "";
            if (inputTipe) inputTipe.value = item.tipe || "hari_efektif";
            if (inputModePresensi) {
                inputModePresensi.value = item.mode_presensi || (item.libur_pd ? 'libur' : 'luring');
            }
            if (inputWarna) inputWarna.value = item.warna || "#10b981";
            if (inputMulai)
                inputMulai.value = item.tanggal_mulai ? item.tanggal_mulai.substring(0, 10) : "";
            if (inputSelesai)
                inputSelesai.value = item.tanggal_selesai ? item.tanggal_selesai.substring(0, 10) : "";
            if (inputLiburPd) inputLiburPd.checked = !!item.libur_pd;
            if (inputLiburGuru) inputLiburGuru.checked = !!item.libur_guru;
            if (inputLiburTendik) inputLiburTendik.checked = !!item.libur_tendik;
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
            if (inputModePresensi) inputModePresensi.value = "luring";
            if (inputTipe) inputTipe.value = "hari_efektif";
            if (inputWarna) inputWarna.value = defaultColors.hari_efektif;
        }
        modal.style.display = "flex";
    };

    const closeModal = () => {
        if (modal) modal.style.display = "none";
    };

    window.openAddModal = () => openModal(false);
    window.openAgendaModal = openModal;
    window.closeAgendaModal = closeModal;

    if (btnTambah) btnTambah.addEventListener("click", () => openModal(false));
    if (btnCloseModal) btnCloseModal.addEventListener("click", closeModal);
    if (btnCancelModal) btnCancelModal.addEventListener("click", closeModal);

    // Otomatis sesuaikan mode presensi, warna default, dan checkbox libur saat kategori dipilih
    if (inputTipe) {
        inputTipe.addEventListener("change", () => {
            const val = inputTipe.value;
            if (inputWarna && defaultColors[val]) {
                inputWarna.value = defaultColors[val];
            }
            if (val.startsWith("libur_")) {
                if (inputModePresensi) inputModePresensi.value = "libur";
                if (inputLiburPd) inputLiburPd.checked = true;
                if (val === "libur_nasional" || val === "libur_semester") {
                    if (inputLiburGuru) inputLiburGuru.checked = true;
                    if (inputLiburTendik) inputLiburTendik.checked = val === "libur_nasional";
                }
            } else if (val === "pembelajaran_daring") {
                if (inputModePresensi) inputModePresensi.value = "daring";
                if (inputLiburPd) inputLiburPd.checked = false;
            } else if (val === "hari_efektif") {
                if (inputModePresensi) inputModePresensi.value = "luring";
                if (inputLiburPd) inputLiburPd.checked = false;
                if (inputLiburGuru) inputLiburGuru.checked = false;
                if (inputLiburTendik) inputLiburTendik.checked = false;
            }
        });
    }

    if (inputModePresensi) {
        inputModePresensi.addEventListener("change", () => {
            const mode = inputModePresensi.value;
            if (mode === "libur") {
                if (inputLiburPd) inputLiburPd.checked = true;
            } else if (mode === "daring") {
                if (inputLiburPd) inputLiburPd.checked = false;
                if (inputTipe && inputTipe.value !== "pembelajaran_daring") {
                    inputTipe.value = "pembelajaran_daring";
                    if (inputWarna) inputWarna.value = defaultColors.pembelajaran_daring;
                }
            } else if (mode === "luring") {
                if (inputLiburPd) inputLiburPd.checked = false;
            }
        });
    }

    if (inputMulai && inputSelesai) {
        inputMulai.addEventListener("change", () => {
            if (!inputSelesai.value || inputSelesai.value < inputMulai.value) {
                inputSelesai.value = inputMulai.value;
            }
            inputSelesai.min = inputMulai.value;
        });
    }

    // Submit Modal Form via AJAX
    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            try {
                Swal.fire({
                    title: "Menyimpan Agenda...",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: formData,
                });

                const data = await res.json();

                if (res.ok && data.status === "success") {
                    closeModal();
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: data.message || "Agenda berhasil disimpan.",
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        if (typeof window.refreshLiveTable === "function") {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    let errMsg = data.message || "Gagal menyimpan agenda.";
                    if (data.errors) {
                        errMsg = Object.values(data.errors).flat().join("<br>");
                    }
                    Swal.fire({ icon: "error", title: "Gagal", html: errMsg });
                }
            } catch (err) {
                Swal.fire("Kesalahan Sistem", "Tidak dapat menghubungi server.", "error");
            }
        });
    }

    /* ==========================================================================
       2. MODAL VIEW DETAIL AGENDA
       ========================================================================== */
    const viewModal = document.getElementById("viewModal");
    const viewJudul = document.getElementById("viewJudul");
    const viewTipeBadge = document.getElementById("viewTipeBadge");
    const viewModeBadge = document.getElementById("viewModeBadge");
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

    if (btnCloseViewModal) btnCloseViewModal.addEventListener("click", closeViewModal);
    if (btnTutupView) btnTutupView.addEventListener("click", closeViewModal);

    window.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
        if (e.target === viewModal) closeViewModal();
    });

    /* ==========================================================================
       3. EVENT DELEGATION (EDIT, DETAIL, HAPUS - TAHAN REFRESH LIVE TABLE)
       ========================================================================== */
    document.addEventListener("click", (e) => {
        // 1. Edit Agenda
        const btnEdit = e.target.closest(".btn-edit-agenda");
        if (btnEdit) {
            try {
                const item = JSON.parse(btnEdit.dataset.item);
                openModal(true, item);
            } catch (err) {
                console.error("Gagal mengurai data agenda", err);
            }
            return;
        }

        // 2. View Detail Agenda
        const btnView = e.target.closest(".btn-view-agenda");
        if (btnView) {
            try {
                const item = JSON.parse(btnView.dataset.item);
                if (viewJudul) viewJudul.textContent = item.nama_kegiatan || "-";
                if (viewTipeBadge) {
                    viewTipeBadge.textContent = item.tipe_label || item.tipe || "Agenda";
                    viewTipeBadge.style.backgroundColor = item.warna || "#10b981";
                    viewTipeBadge.style.color = "#fff";
                }
                if (viewModeBadge) {
                    const mode = item.mode_presensi || (item.libur_pd ? 'libur' : 'luring');
                    if (mode === 'daring') {
                        viewModeBadge.innerHTML = '<span class="badge badge-primary"><i class="fas fa-laptop-house me-1"></i> Daring</span>';
                    } else if (mode === 'libur') {
                        viewModeBadge.innerHTML = '<span class="badge badge-danger"><i class="fas fa-umbrella-beach me-1"></i> Libur</span>';
                    } else {
                        viewModeBadge.innerHTML = '<span class="badge badge-success"><i class="fas fa-school me-1"></i> Luring</span>';
                    }
                }

                const tglMulai = item.tanggal_mulai ? item.tanggal_mulai.substring(0, 10) : "";
                const tglSelesai = item.tanggal_selesai ? item.tanggal_selesai.substring(0, 10) : "";
                if (viewTanggal) {
                    viewTanggal.textContent =
                        tglMulai === tglSelesai ? tglMulai : `${tglMulai} s.d. ${tglSelesai}`;
                }

                if (viewDampakBadges) {
                    let badgesHtml = "";
                    badgesHtml += item.libur_pd
                        ? '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px;"><i class="fas fa-ban me-1"></i> Libur Siswa</span>'
                        : '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px;"><i class="fas fa-check me-1"></i> Siswa ' + (item.mode_presensi === 'daring' ? 'Daring' : 'Efektif') + '</span>';

                    badgesHtml += item.libur_guru
                        ? '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px;"><i class="fas fa-ban me-1"></i> Libur Guru</span>'
                        : '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px;"><i class="fas fa-check me-1"></i> Guru Efektif</span>';

                    badgesHtml += item.libur_tendik
                        ? '<span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 3px 8px;"><i class="fas fa-ban me-1"></i> Libur Tendik</span>'
                        : '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 3px 8px;"><i class="fas fa-check me-1"></i> Tendik Efektif</span>';

                    viewDampakBadges.innerHTML = badgesHtml;
                }

                if (viewKeterangan) viewKeterangan.textContent = item.keterangan || "Tidak ada catatan tambahan.";
                if (viewPembuat) viewPembuat.textContent = item.created_by || "Sistem";
                if (viewWaktu) {
                    viewWaktu.textContent = item.created_at ? item.created_at.substring(0, 16).replace("T", " ") : "-";
                }

                viewModal.style.display = "flex";
            } catch (err) {
                console.error("Gagal membuka detail agenda", err);
            }
            return;
        }

        // 3. Tambah Agenda via Tombol Empty State
        const btnEmpty = e.target.closest("#btnEmptyTambahAgenda");
        if (btnEmpty) {
            openModal(false);
            return;
        }

        // 4. Sortable Header Click
        const sortTh = e.target.closest(".sortable-th");
        if (sortTh) {
            const sortField = sortTh.getAttribute("data-sort");
            if (!sortField) return;

            const url = new URL(window.location.href);
            const curSort = url.searchParams.get("sort") || "tanggal_mulai";
            const curDir = url.searchParams.get("sort_dir") || "asc";

            let newDir = "asc";
            if (curSort === sortField && curDir === "asc") {
                newDir = "desc";
            }
            applyFilters({ sort: sortField, sort_dir: newDir });
        }
    });

    // Delete Confirmation with SweetAlert2 (Event Delegation)
    document.addEventListener("submit", function (e) {
        const delForm = e.target.closest('form[data-confirm="delete"]');
        if (!delForm) return;

        e.preventDefault();
        const agendaName = delForm.getAttribute("data-name") || "agenda ini";

        Swal.fire({
            title: "Hapus Agenda Kalender?",
            html: `Apakah Anda yakin ingin menghapus agenda <strong>"${agendaName}"</strong>?<br><small class="text-muted">Tindakan ini juga akan memperbarui perhitungan Hari Efektif Belajar secara otomatis.</small>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-trash-can me-1"></i> Ya, Hapus',
            cancelButtonText: "Batal",
            confirmButtonColor: "#ef4444",
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    Swal.fire({
                        title: "Menghapus...",
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    const res = await fetch(delForm.action, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: new FormData(delForm),
                    });

                    const data = await res.json();

                    if (res.ok && data.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil!",
                            text: data.message || "Agenda berhasil dihapus.",
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            if (typeof window.refreshLiveTable === "function") {
                                window.refreshLiveTable(window.location.href);
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({ icon: "error", title: "Gagal", text: data.message || "Gagal menghapus agenda." });
                    }
                } catch (err) {
                    Swal.fire("Kesalahan Sistem", "Tidak dapat menghubungi server.", "error");
                }
            }
        });
    });

    /* ==========================================================================
       4. REAL-TIME FILTER ENGINE (AJAX refreshLiveTable)
       ========================================================================== */
    const filterTahunAjaran = document.getElementById("filterTahunAjaran");
    const filterSemester    = document.getElementById("filterSemester");
    const filterBulan       = document.getElementById("filterBulan");
    const filterModePresensi = document.getElementById("filterModePresensi");
    const filterTipe        = document.getElementById("filterTipe");
    const filterDampak      = document.getElementById("filterDampak");
    const perPageSelect     = document.getElementById("perPageSelect");
    const liveSearch        = document.getElementById("liveSearch");
    const clearSearch       = document.getElementById("clearSearch");
    const btnResetFilter    = document.getElementById("btnResetFilter");

    const applyFilters = (overrideParams = {}) => {
        const url = new URL(window.location.href);

        if (filterTahunAjaran) {
            if (filterTahunAjaran.value) url.searchParams.set("tahun_ajaran", filterTahunAjaran.value);
            else url.searchParams.delete("tahun_ajaran");
        }

        if (filterSemester) {
            if (filterSemester.value) url.searchParams.set("semester", filterSemester.value);
            else url.searchParams.delete("semester");
        }

        if (filterBulan) {
            if (filterBulan.value) url.searchParams.set("bulan", filterBulan.value);
            else url.searchParams.delete("bulan");
        }

        if (filterModePresensi) {
            if (filterModePresensi.value) url.searchParams.set("mode_presensi", filterModePresensi.value);
            else url.searchParams.delete("mode_presensi");
        }

        if (filterTipe) {
            if (filterTipe.value) url.searchParams.set("tipe", filterTipe.value);
            else url.searchParams.delete("tipe");
        }

        if (filterDampak) {
            if (filterDampak.value) url.searchParams.set("dampak", filterDampak.value);
            else url.searchParams.delete("dampak");
        }

        if (perPageSelect) {
            if (perPageSelect.value) url.searchParams.set("perPage", perPageSelect.value);
            else url.searchParams.delete("perPage");
        }

        if (liveSearch) {
            const qVal = liveSearch.value.trim();
            if (qVal) url.searchParams.set("q", qVal);
            else url.searchParams.delete("q");
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        if (!("page" in overrideParams)) {
            url.searchParams.delete("page");
        }

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    };

    if (filterTahunAjaran) filterTahunAjaran.addEventListener("change", () => applyFilters());
    if (filterSemester)    filterSemester.addEventListener("change", () => applyFilters());
    if (filterBulan)       filterBulan.addEventListener("change", () => applyFilters());
    if (filterModePresensi) filterModePresensi.addEventListener("change", () => applyFilters());
    if (filterTipe)        filterTipe.addEventListener("change", () => applyFilters());
    if (filterDampak)      filterDampak.addEventListener("change", () => applyFilters());
    if (perPageSelect)     perPageSelect.addEventListener("change", () => applyFilters());

    let debounceTimer = null;
    if (liveSearch) {
        liveSearch.addEventListener("input", function () {
            if (clearSearch) {
                clearSearch.classList.toggle("visible", this.value.trim().length > 0);
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => applyFilters(), 350);
        });

        liveSearch.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
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
                liveSearch.focus();
            }
            applyFilters({ q: "" });
        });
    }

    if (btnResetFilter) {
        btnResetFilter.addEventListener("click", () => {
            const cleanUrl = new URL(window.location.pathname, window.location.origin);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(cleanUrl.toString());
            } else {
                window.location.href = cleanUrl.toString();
            }
        });
    }
});
