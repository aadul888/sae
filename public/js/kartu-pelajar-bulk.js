/**
 * SAE - Kartu Pelajar Bulk Actions & Batch Selection Module
 * Mengelola seleksi per baris / seluruh data terfilter serta eksekusi massal pembayaran & request cetak.
 */

document.addEventListener("DOMContentLoaded", function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    // =========================================================================
    // 1. Bulk Selection untuk Tab Berhasil Cetak
    // =========================================================================
    function getBerhasilSelectedIds() {
        return Array.from(document.querySelectorAll(".check-student-row:checked")).map(c => c.value);
    }

    function isBerhasilApplyAll() {
        const chk = document.getElementById("checkPilihSemuaFilterBerhasil");
        return chk ? chk.checked : false;
    }

    function updateBerhasilBulkBar() {
        const ids = getBerhasilSelectedIds();
        const bulkBar = document.getElementById("bulkActionBar");
        const bulkCountEl = document.getElementById("bulkSelectedCount");
        const checkAll = document.getElementById("checkAllStudents");
        const checkApplyAll = document.getElementById("checkPilihSemuaFilterBerhasil");

        if (bulkBar && bulkCountEl) {
            if (ids.length > 0 || (checkApplyAll && checkApplyAll.checked)) {
                bulkBar.style.display = "flex";
                bulkCountEl.textContent = ids.length;
            } else {
                bulkBar.style.display = "none";
            }
        }
        if (checkAll) {
            const allBoxes = document.querySelectorAll(".check-student-row");
            checkAll.checked = allBoxes.length > 0 && ids.length === allBoxes.length;
            checkAll.indeterminate = ids.length > 0 && ids.length < allBoxes.length;
        }
    }

    // =========================================================================
    // 2. Bulk Selection untuk Tab Request Cetak
    // =========================================================================
    function getRequestSelectedIds() {
        return Array.from(document.querySelectorAll(".check-request-row:checked")).map(c => c.value);
    }

    function isRequestApplyAll() {
        const chk = document.getElementById("checkPilihSemuaFilterRequest");
        return chk ? chk.checked : false;
    }

    function updateRequestBulkBar() {
        const ids = getRequestSelectedIds();
        const bulkBar = document.getElementById("bulkActionRequestBar");
        const bulkCountEl = document.getElementById("bulkRequestSelectedCount");
        const checkAll = document.getElementById("checkAllRequests");
        const checkApplyAll = document.getElementById("checkPilihSemuaFilterRequest");

        if (bulkBar && bulkCountEl) {
            if (ids.length > 0 || (checkApplyAll && checkApplyAll.checked)) {
                bulkBar.style.display = "flex";
                bulkCountEl.textContent = ids.length;
            } else {
                bulkBar.style.display = "none";
            }
        }
        if (checkAll) {
            const allBoxes = document.querySelectorAll(".check-request-row");
            checkAll.checked = allBoxes.length > 0 && ids.length === allBoxes.length;
            checkAll.indeterminate = ids.length > 0 && ids.length < allBoxes.length;
        }
    }

    // Delegated Change Handler untuk checkbox
    document.addEventListener("change", function (e) {
        if (!e.target) return;

        // Check All Berhasil Cetak
        if (e.target.id === "checkAllStudents") {
            const isChecked = e.target.checked;
            document.querySelectorAll(".check-student-row").forEach(c => c.checked = isChecked);
            updateBerhasilBulkBar();
        } else if (e.target.classList.contains("check-student-row")) {
            updateBerhasilBulkBar();
        } else if (e.target.id === "checkPilihSemuaFilterBerhasil") {
            if (e.target.checked) {
                document.querySelectorAll(".check-student-row").forEach(c => c.checked = true);
            }
            updateBerhasilBulkBar();
        }

        // Check All Request Cetak
        if (e.target.id === "checkAllRequests") {
            const isChecked = e.target.checked;
            document.querySelectorAll(".check-request-row").forEach(c => c.checked = isChecked);
            updateRequestBulkBar();
        } else if (e.target.classList.contains("check-request-row")) {
            updateRequestBulkBar();
        } else if (e.target.id === "checkPilihSemuaFilterRequest") {
            if (e.target.checked) {
                document.querySelectorAll(".check-request-row").forEach(c => c.checked = true);
            }
            updateRequestBulkBar();
        }
    });

    // Sinkronkan kembali saat refreshLiveTable selesai
    window.addEventListener("sae:tableRefreshed", function () {
        updateBerhasilBulkBar();
        updateRequestBulkBar();
    });

    // =========================================================================
    // 3. Eksekusi Bulk Action Berhasil Cetak (Pembayaran & Fisik)
    // =========================================================================
    window.openModalBulkPembayaran = function (presetAction) {
        const ids = getBerhasilSelectedIds();
        const applyAll = isBerhasilApplyAll();

        if (!ids.length && !applyAll) {
            Swal.fire("Peringatan", "Pilih minimal satu peserta didik atau centang 'Pilih Semua Sesuai Filter'.", "warning");
            return;
        }

        const modalBulkBayar = document.getElementById("modalBulkPembayaran");
        if (!modalBulkBayar) return;

        const countEl = document.getElementById("bulkModalSelectedCount");
        if (countEl) countEl.textContent = applyAll ? "seluruh data sesuai filter" : ids.length;

        const actionInput = document.getElementById("bulkModalAction");
        if (actionInput) actionInput.value = presetAction || "custom_bulk";

        const applyAllInput = document.getElementById("bulkModalApplyAll");
        if (applyAllInput) applyAllInput.value = applyAll ? "1" : "0";

        // Isi filter saat ini
        const urlParams = new URLSearchParams(window.location.search);
        const searchInput = document.getElementById("searchKartuPelajar");
        const filterRombel = document.getElementById("filterRombel");
        const filterStatusBayar = document.getElementById("filterStatusBayar");

        document.getElementById("bulkModalFilterQ").value = (searchInput ? searchInput.value.trim() : "") || urlParams.get("q") || "";
        document.getElementById("bulkModalFilterRombel").value = (filterRombel ? filterRombel.value : "") || urlParams.get("rombel") || "";
        document.getElementById("bulkModalFilterStatusBayar").value = (filterStatusBayar ? filterStatusBayar.value : "") || urlParams.get("status_pembayaran") || "";

        const todayStr = new Date().toISOString().substring(0, 10);
        const radioBayar = document.querySelectorAll('input[name="bulk_status_pembayaran"]');
        const detailWrap = document.getElementById("bulkBayarDetailWrap");
        const selectCetak = document.getElementById("bulkStatusCetak");
        const tglBayarInput = document.getElementById("bulkTanggalBayar");
        const tglCetakInput = document.getElementById("bulkTanggalCetak");
        const tglCetakWrap = document.getElementById("bulkTanggalCetakWrap");

        if (tglBayarInput) tglBayarInput.value = todayStr;
        if (tglCetakInput) tglCetakInput.value = todayStr;

        if (presetAction === "set_lunas") {
            radioBayar.forEach(r => r.checked = (r.value === "lunas"));
            if (detailWrap) detailWrap.style.display = "block";
            if (selectCetak) selectCetak.value = "keep";
            if (tglCetakWrap) tglCetakWrap.style.display = "none";
        } else if (presetAction === "set_belum_bayar") {
            radioBayar.forEach(r => r.checked = (r.value === "belum_bayar"));
            if (detailWrap) detailWrap.style.display = "none";
            if (selectCetak) selectCetak.value = "keep";
            if (tglCetakWrap) tglCetakWrap.style.display = "none";
        } else {
            radioBayar.forEach(r => r.checked = (r.value === "keep"));
            if (detailWrap) detailWrap.style.display = "none";
            if (selectCetak) selectCetak.value = "keep";
            if (tglCetakWrap) tglCetakWrap.style.display = "none";
        }

        modalBulkBayar.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    window.closeModalBulkPembayaran = function () {
        const modal = document.getElementById("modalBulkPembayaran");
        if (modal) {
            modal.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    const formBulkBayar = document.getElementById("formBulkPembayaran");
    if (formBulkBayar) {
        formBulkBayar.addEventListener("submit", async function (e) {
            e.preventDefault();
            const ids = getBerhasilSelectedIds();
            const applyAll = isBerhasilApplyAll();

            if (!ids.length && !applyAll) {
                Swal.fire("Peringatan", "Pilih minimal satu peserta didik atau centang pilih semua sesuai filter.", "warning");
                return;
            }

            const btnSubmit = document.getElementById("btnSubmitBulkPembayaran");
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menerapkan...';
            }

            const formData = new FormData(formBulkBayar);
            const payload = Object.fromEntries(formData.entries());
            payload.ids = ids;
            payload.apply_all_filtered = applyAll ? 1 : 0;

            try {
                const res = await fetch("/dashboard/kartu-pelajar/bulk-action", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }

                if (res.ok && data.success) {
                    window.closeModalBulkPembayaran();
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: data.message,
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => {
                        if (typeof window.refreshLiveTable === "function") {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire("Gagal", data.message || "Terjadi kesalahan sistem.", "error");
                }
            } catch (err) {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        });
    }

    // =========================================================================
    // 4. Eksekusi Bulk Action Tab Request Cetak
    // =========================================================================
    window.executeBulkRequestAction = async function (action) {
        const ids = getRequestSelectedIds();
        const applyAll = isRequestApplyAll();

        if (!ids.length && !applyAll) {
            Swal.fire("Peringatan", "Pilih minimal satu antrean atau centang 'Pilih Semua Sesuai Filter'.", "warning");
            return;
        }

        const labels = {
            set_diproses: "menandai status DIPROSES",
            set_selesai: "menandai status SELESAI (Kartu Fisik Dicetak)",
            set_ditolak: "menandai status DITOLAK",
            delete: "MENGHAPUS PERMANEN",
        };

        const targetCountText = applyAll ? "seluruh antrean sesuai filter saat ini" : `${ids.length} antrean terpilih`;

        const confirmed = await Swal.fire({
            title: action === "delete" ? "Hapus Antrean Massal?" : "Konfirmasi Aksi Massal",
            text: `Yakin ingin ${labels[action] || action} untuk ${targetCountText}?`,
            icon: action === "delete" ? "warning" : "question",
            showCancelButton: true,
            confirmButtonText: action === "delete" ? "Ya, Hapus!" : "Ya, Lanjutkan",
            cancelButtonText: "Batal",
            confirmButtonColor: action === "delete" ? "#ef4444" : "#4f46e5",
            cancelButtonColor: "#64748b",
        });

        if (!confirmed.isConfirmed) return;

        Swal.fire({
            title: "Memproses...",
            text: "Mohon tunggu sebentar...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const urlParams = new URLSearchParams(window.location.search);
        const searchInput = document.getElementById("searchKartuPelajar");
        const filterRombel = document.getElementById("filterRombel");
        const filterStatusBayar = document.getElementById("filterStatusBayar");
        const filterReqStatus = document.getElementById("filterReqStatus");

        const payload = {
            action: action,
            ids: ids,
            apply_all_filtered: applyAll ? 1 : 0,
            filter_q: (searchInput ? searchInput.value.trim() : "") || urlParams.get("q") || "",
            filter_rombel: (filterRombel ? filterRombel.value : "") || urlParams.get("rombel") || "",
            filter_status_bayar: (filterStatusBayar ? filterStatusBayar.value : "") || urlParams.get("status_pembayaran") || "",
            filter_req_status: (filterReqStatus ? filterReqStatus.value : "") || urlParams.get("req_status") || "",
        };

        try {
            const res = await fetch("/dashboard/kartu-pelajar/request/bulk-action", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: data.message,
                    timer: 1600,
                    showConfirmButton: false,
                }).then(() => {
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire("Gagal", data.message || "Terjadi kesalahan.", "error");
            }
        } catch (err) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
    };

    // =========================================================================
    // 5. Cetak Masal Khusus Antrean Terpilih / Terfilter
    // =========================================================================
    window.executeCetakSelectedRequests = function () {
        const ids = getRequestSelectedIds();
        const applyAll = isRequestApplyAll();

        if (!ids.length && !applyAll) {
            Swal.fire("Peringatan", "Pilih minimal satu antrean atau centang 'Pilih Semua Sesuai Filter'.", "warning");
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const searchInput = document.getElementById("searchKartuPelajar");
        const filterRombel = document.getElementById("filterRombel");
        const filterStatusBayar = document.getElementById("filterStatusBayar");
        const filterReqStatus = document.getElementById("filterReqStatus");

        const params = new URLSearchParams();
        if (applyAll) {
            const q = (searchInput ? searchInput.value.trim() : "") || urlParams.get("q") || "";
            const rombel = (filterRombel ? filterRombel.value : "") || urlParams.get("rombel") || "";
            const bayar = (filterStatusBayar ? filterStatusBayar.value : "") || urlParams.get("status_pembayaran") || "";
            const reqSt = (filterReqStatus ? filterReqStatus.value : "") || urlParams.get("req_status") || "";

            if (q) params.set("filter_q", q);
            if (rombel) params.set("filter_rombel", rombel);
            if (bayar) params.set("filter_status_bayar", bayar);
            if (reqSt) params.set("filter_req_status", reqSt);
        } else {
            params.set("ids", ids.join(","));
        }
        params.set("hanya_ada_foto", "1");

        const printUrl = "/dashboard/kartu-pelajar/cetak-antrean?" + params.toString();
        window.open(printUrl, "_blank");
    };
});
