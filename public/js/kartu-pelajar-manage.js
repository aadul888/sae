/**
 * SAE - Kartu Pelajar & Pengelolaan Pembayaran Frontend Logic
 * Mengelola catatan pembayaran, status cetak fisik RFID, serta pengiriman bukti otomatis via WhatsApp ber-QR Code.
 */

document.addEventListener("DOMContentLoaded", function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    // =========================================================================
    // 1. Live Search & Filter URL Handlers
    // =========================================================================
    let searchTimer = null;
    const searchInput = document.getElementById("searchKartuPelajar");
    const clearSearchBtn = document.getElementById("clearSearchKartuPelajar");

    function updateSearch(val) {
        const url = new URL(window.location.href);
        if (val) {
            url.searchParams.set("q", val);
        } else {
            url.searchParams.delete("q");
        }
        if (filterRombel && filterRombel.value) url.searchParams.set("rombel", filterRombel.value);
        if (filterStatusBayar && filterStatusBayar.value) url.searchParams.set("status_pembayaran", filterStatusBayar.value);
        if (filterStatusCetak && filterStatusCetak.value) url.searchParams.set("status_cetak", filterStatusCetak.value);
        if (filterReqStatus && filterReqStatus.value) url.searchParams.set("req_status", filterReqStatus.value);
        if (perPageSelect && perPageSelect.value) url.searchParams.set("perPage", perPageSelect.value);
        url.searchParams.delete("page");
        url.searchParams.delete("request_page");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle("visible", this.value.trim().length > 0);
            }
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                updateSearch(this.value.trim());
            }, 350);
        });

        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(searchTimer);
                updateSearch(this.value.trim());
            }
        });
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener("click", function () {
            searchInput.value = "";
            clearSearchBtn.classList.remove("visible");
            updateSearch("");
        });
    }

    // Filter Selects
    const filterRombel = document.getElementById("filterRombel");
    const filterStatusBayar = document.getElementById("filterStatusBayar");
    const filterStatusCetak = document.getElementById("filterStatusCetak");
    const filterReqStatus = document.getElementById("filterReqStatus");
    const perPageSelect = document.getElementById("perPageSelect");

    function applyFilterChange(paramName, value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(paramName, value);
        } else {
            url.searchParams.delete(paramName);
        }
        // Sinkronkan nilai pencarian aktif dari input jika ada
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }
        url.searchParams.delete("page");
        url.searchParams.delete("request_page");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (filterRombel) {
        filterRombel.addEventListener("change", function (e) {
            e.stopImmediatePropagation();
            applyFilterChange("rombel", this.value);
        });
    }

    if (filterStatusBayar) {
        filterStatusBayar.addEventListener("change", function (e) {
            e.stopImmediatePropagation();
            applyFilterChange("status_pembayaran", this.value);
        });
    }

    if (filterStatusCetak) {
        filterStatusCetak.addEventListener("change", function (e) {
            e.stopImmediatePropagation();
            applyFilterChange("status_cetak", this.value);
        });
    }

    if (filterReqStatus) {
        filterReqStatus.addEventListener("change", function (e) {
            e.stopImmediatePropagation();
            applyFilterChange("req_status", this.value);
        });
    }

    if (perPageSelect) {
        perPageSelect.addEventListener("change", function (e) {
            e.stopImmediatePropagation();
            applyFilterChange("perPage", this.value);
        });
    }

    // Delegated Sort Header Handler (AJAX Live Sort for Kartu Pelajar)
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th) return;

        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;

        e.preventDefault();
        e.stopPropagation();

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "";
        const currentDir = url.searchParams.get("sort_dir") || "asc";

        let newDir = "asc";
        if (currentSort === sortField && currentDir === "asc") {
            newDir = "desc";
        }

        url.searchParams.set("sort", sortField);
        url.searchParams.set("sort_dir", newDir);
        url.searchParams.delete("page");
        url.searchParams.delete("request_page");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }, true);

    // =========================================================================
    // 2. Multi-Select & Bulk Actions
    // =========================================================================
    function getSelectedIds() {
        return Array.from(document.querySelectorAll(".check-student-row:checked")).map(c => c.value);
    }

    function updateBulkBar() {
        const ids = getSelectedIds();
        const bulkBar = document.getElementById("bulkActionBar");
        const bulkCountEl = document.getElementById("bulkSelectedCount");
        const checkAll = document.getElementById("checkAllStudents");

        if (bulkBar && bulkCountEl) {
            if (ids.length > 0) {
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

    // Delegated Change Handler pada document agar selalu aktif walau tabel di-swap oleh AJAX
    document.addEventListener("change", function (e) {
        if (e.target && e.target.id === "checkAllStudents") {
            const isChecked = e.target.checked;
            document.querySelectorAll(".check-student-row").forEach(c => {
                c.checked = isChecked;
            });
            updateBulkBar();
        } else if (e.target && e.target.classList.contains("check-student-row")) {
            updateBulkBar();
        }
    });

    // Sinkronkan kembali bulk bar saat tabel di-refresh via AJAX
    window.addEventListener("sae:tableRefreshed", function () {
        updateBulkBar();
    });

    window.executeBulkAction = async function (action) {
        const ids = getSelectedIds();
        if (!ids.length) {
            Swal.fire("Peringatan", "Pilih minimal satu peserta didik.", "warning");
            return;
        }

        const actionLabels = {
            set_lunas: "menandai LUNAS",
            set_belum_bayar: "menandai BELUM BAYAR",
            set_sudah_cetak: "menandai SUDAH DICETAK (Garansi Aktif)",
            set_belum_cetak: "menandai BELUM DICETAK",
        };

        const confirmed = await Swal.fire({
            title: "Konfirmasi Aksi Massal",
            text: `Yakin ingin ${actionLabels[action]} untuk ${ids.length} peserta didik terpilih?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Lanjutkan",
            cancelButtonText: "Batal",
            confirmButtonColor: "#4f46e5",
        });

        if (!confirmed.isConfirmed) return;

        Swal.fire({
            title: "Memproses...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const res = await fetch("/dashboard/kartu-pelajar/bulk-action", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({ action: action, ids: ids }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: data.message,
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
                Swal.fire("Gagal", data.message || "Terjadi kesalahan sistem.", "error");
            }
        } catch (err) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
    };

    // =========================================================================
    // 2B. Modal Penyesuaian Data Pembayaran & Cetak Masal (Bulk Action Modal)
    // =========================================================================
    const modalBulkBayar = document.getElementById("modalBulkPembayaran");
    const formBulkBayar = document.getElementById("formBulkPembayaran");

    window.openModalBulkPembayaran = function (presetAction) {
        const ids = getSelectedIds();
        if (!ids.length) {
            Swal.fire("Peringatan", "Pilih minimal satu peserta didik terlebih dahulu.", "warning");
            return;
        }

        if (!modalBulkBayar) return;

        const countEl = document.getElementById("bulkModalSelectedCount");
        if (countEl) countEl.textContent = ids.length;

        const actionInput = document.getElementById("bulkModalAction");
        if (actionInput) actionInput.value = presetAction || "custom_bulk";

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
        } else if (presetAction === "set_sudah_cetak") {
            radioBayar.forEach(r => r.checked = (r.value === "keep"));
            if (detailWrap) detailWrap.style.display = "none";
            if (selectCetak) selectCetak.value = "sudah_cetak";
            if (tglCetakWrap) tglCetakWrap.style.display = "block";
        } else if (presetAction === "set_belum_cetak") {
            radioBayar.forEach(r => r.checked = (r.value === "keep"));
            if (detailWrap) detailWrap.style.display = "none";
            if (selectCetak) selectCetak.value = "belum_cetak";
            if (tglCetakWrap) tglCetakWrap.style.display = "none";
        } else {
            // custom_bulk
            radioBayar.forEach(r => r.checked = (r.value === "keep"));
            if (detailWrap) detailWrap.style.display = "none";
            if (selectCetak) selectCetak.value = "keep";
            if (tglCetakWrap) tglCetakWrap.style.display = "none";
        }

        modalBulkBayar.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    window.closeModalBulkPembayaran = function () {
        if (!modalBulkBayar) return;
        modalBulkBayar.style.display = "none";
        document.body.style.overflow = "";
    };

    document.querySelectorAll('input[name="bulk_status_pembayaran"]').forEach(r => {
        r.addEventListener("change", function () {
            const wrap = document.getElementById("bulkBayarDetailWrap");
            if (wrap) wrap.style.display = (this.value === "lunas") ? "block" : "none";
        });
    });

    const bulkSelectCetak = document.getElementById("bulkStatusCetak");
    if (bulkSelectCetak) {
        bulkSelectCetak.addEventListener("change", function () {
            const wrap = document.getElementById("bulkTanggalCetakWrap");
            if (wrap) wrap.style.display = (this.value === "sudah_cetak") ? "block" : "none";
        });
    }

    if (formBulkBayar) {
        formBulkBayar.addEventListener("submit", async function (e) {
            e.preventDefault();
            const ids = getSelectedIds();
            if (!ids.length) {
                Swal.fire("Peringatan", "Pilih minimal satu peserta didik.", "warning");
                return;
            }

            const btnSubmit = document.getElementById("btnSubmitBulkPembayaran");
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menerapkan...';

            const formData = new FormData(formBulkBayar);
            const payload = Object.fromEntries(formData.entries());
            payload.ids = ids;

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
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;

                if (res.ok && data.success) {
                    closeModalBulkPembayaran();
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
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        });
    }

    // =========================================================================
    // 3. Modal Edit / Catat Pembayaran
    // =========================================================================
    const modalEditBayar = document.getElementById("modalEditPembayaran");
    const formEditBayar = document.getElementById("formEditPembayaran");

    window.openEditPembayaran = function (studentJson) {
        if (!modalEditBayar) return;
        const s = typeof studentJson === "string" ? JSON.parse(studentJson) : studentJson;

        document.getElementById("bayarPdId").value = s.peserta_didik_id;
        document.getElementById("bayarPdNama").textContent = s.nama;
        document.getElementById("bayarPdNisn").textContent = s.nisn || "-";
        document.getElementById("bayarPdRombel").textContent = s.nama_rombel || "-";

        // Status bayar
        const isLunas = s.status_pembayaran === "lunas";
        document.querySelector(`input[name="status_pembayaran"][value="${isLunas ? 'lunas' : 'belum_bayar'}"]`).checked = true;

        document.getElementById("bayarNominal").value = s.nominal || 25000;
        document.getElementById("bayarMetode").value = s.metode_pembayaran || "tunai";
        document.getElementById("bayarTanggal").value = s.tanggal_bayar ? s.tanggal_bayar.substring(0, 10) : new Date().toISOString().substring(0, 10);
        document.getElementById("bayarStatusCetak").value = s.status_cetak || "belum_cetak";
        document.getElementById("bayarNomorWa").value = s.nomor_wa || s.nomor_telepon_seluler || s.nomor_telepon_rumah || "";
        document.getElementById("bayarCatatan").value = s.catatan || "";

        toggleBayarDetailFields(isLunas);

        modalEditBayar.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    window.closeEditPembayaran = function () {
        if (!modalEditBayar) return;
        modalEditBayar.style.display = "none";
        document.body.style.overflow = "";
    };

    function toggleBayarDetailFields(isLunas) {
        const wrap = document.getElementById("bayarDetailFieldsWrap");
        if (wrap) {
            wrap.style.display = isLunas ? "block" : "none";
        }
    }

    document.querySelectorAll('input[name="status_pembayaran"]').forEach(r => {
        r.addEventListener("change", function () {
            toggleBayarDetailFields(this.value === "lunas");
        });
    });

    if (formEditBayar) {
        formEditBayar.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("btnSubmitPembayaran");
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

            const formData = new FormData(formEditBayar);
            const payload = Object.fromEntries(formData.entries());
            const autoKirimWa = document.getElementById("chkKirimWaAuto")?.checked;

            try {
                const res = await fetch("/dashboard/kartu-pelajar/pembayaran", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;

                if (res.ok && data.success) {
                    closeEditPembayaran();

                    if (autoKirimWa && data.wa_url) {
                        Swal.fire({
                            icon: "success",
                            title: "Data Disimpan!",
                            text: "Catatan pembayaran berhasil disimpan. Membuka WhatsApp untuk mengirimkan rincian & QR Code...",
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => {
                            window.open(data.wa_url, "_blank");
                            if (typeof window.refreshLiveTable === "function") {
                                window.refreshLiveTable(window.location.href);
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil Disimpan!",
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            if (typeof window.refreshLiveTable === "function") {
                                window.refreshLiveTable(window.location.href);
                            } else {
                                window.location.reload();
                            }
                        });
                    }
                } else {
                    Swal.fire("Gagal", data.message || "Terjadi kesalahan.", "error");
                }
            } catch (err) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        });
    }

    // =========================================================================
    // 4. Quick Toggle Status Cetak
    // =========================================================================
    window.toggleStatusCetak = async function (pdId, btnEl) {
        if (!pdId) return;

        try {
            const res = await fetch("/dashboard/kartu-pelajar/toggle-cetak", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({ peserta_didik_id: pdId }),
            });
            const data = await res.json();

            if (res.ok && data.success) {
                if (window.SAE && typeof window.SAE.toast === "function") {
                    window.SAE.toast(data.message, "success");
                }
                if (typeof window.refreshLiveTable === "function") {
                    window.refreshLiveTable(window.location.href);
                } else {
                    window.location.reload();
                }
            } else {
                Swal.fire("Error", data.message || "Gagal mengubah status.", "error");
            }
        } catch (err) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
    };

    // =========================================================================
    // 5. Modal Kirim WhatsApp & QR Code
    // =========================================================================
    const modalWa = document.getElementById("modalKirimWa");
    let currentWaNisn = null;
    let currentWaPdId = null;

    window.openModalWa = async function (nisn) {
        if (!modalWa) return;
        currentWaNisn = nisn;

        const loading = document.getElementById("waModalLoading");
        const body = document.getElementById("waModalBody");
        if (loading) loading.style.display = "block";
        if (body) body.style.display = "none";

        modalWa.style.display = "flex";
        document.body.style.overflow = "hidden";

        try {
            const res = await fetch(`/dashboard/kartu-pelajar/wa-data/${encodeURIComponent(nisn)}`, {
                headers: { Accept: "application/json" },
            });
            const data = await res.json();

            if (loading) loading.style.display = "none";
            if (body) body.style.display = "block";

            if (!res.ok || !data.success) {
                Swal.fire("Gagal", data.message || "Data siswa tidak ditemukan.", "error");
                closeModalWa();
                return;
            }

            currentWaPdId = data.siswa.peserta_didik_id;
            document.getElementById("waTargetNama").textContent = data.siswa.nama;
            document.getElementById("waTargetNisn").textContent = data.siswa.nisn;
            document.getElementById("waTargetRombel").textContent = data.siswa.rombel || "-";

            const inputPhone = document.getElementById("waInputPhone");
            if (inputPhone) {
                inputPhone.value = data.siswa.phone_clean || data.siswa.phone_raw || "";
            }

            const txtPesan = document.getElementById("waTextPesan");
            if (txtPesan) {
                txtPesan.value = data.message;
            }

            const qrContainer = document.getElementById("waQrSvgContainer");
            if (qrContainer && data.qr_svg) {
                qrContainer.innerHTML = data.qr_svg;
            }

            const linkVerify = document.getElementById("waVerifyLinkText");
            if (linkVerify) {
                linkVerify.textContent = data.verify_url;
                linkVerify.href = data.verify_url;
            }
        } catch (err) {
            if (loading) loading.style.display = "none";
            Swal.fire("Error", "Gagal memuat data WhatsApp siswa.", "error");
            closeModalWa();
        }
    };

    window.closeModalWa = function () {
        if (!modalWa) return;
        modalWa.style.display = "none";
        document.body.style.overflow = "";
    };

    window.kirimPesanWaSekarang = async function () {
        const inputPhone = document.getElementById("waInputPhone");
        const txtPesan = document.getElementById("waTextPesan");

        const rawPhone = inputPhone ? inputPhone.value.trim() : "";
        const message = txtPesan ? txtPesan.value.trim() : "";

        if (!rawPhone) {
            Swal.fire("Nomor WhatsApp Kosong", "Silakan masukkan nomor WhatsApp tujuan.", "warning");
            if (inputPhone) inputPhone.focus();
            return;
        }

        // Format clean phone
        let cleanPhone = rawPhone.replace(/[^0-9]/g, "");
        if (cleanPhone.startsWith("0")) {
            cleanPhone = "62" + cleanPhone.substring(1);
        } else if (cleanPhone.startsWith("8")) {
            cleanPhone = "62" + cleanPhone;
        }

        // Catat pengiriman log ke backend via AJAX
        if (currentWaPdId) {
            fetch("/dashboard/kartu-pelajar/log-wa", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    peserta_didik_id: currentWaPdId,
                    nomor_wa: cleanPhone,
                }),
            }).catch(() => {});
        }

        const waUrl = "https://wa.me/" + cleanPhone + "?text=" + encodeURIComponent(message);
        window.open(waUrl, "_blank");
        closeModalWa();

        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Membuka WhatsApp untuk mengirim pesan...", "success");
        }
    };

    window.salinTeksPesanWa = function () {
        const txtPesan = document.getElementById("waTextPesan");
        if (!txtPesan) return;
        navigator.clipboard.writeText(txtPesan.value).then(() => {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast("Teks pesan WhatsApp berhasil disalin!", "success");
            } else if (window.Swal) {
                Swal.fire({
                    icon: "success",
                    title: "Tersalin!",
                    text: "Teks pesan WhatsApp berhasil disalin ke clipboard.",
                    timer: 1500,
                    showConfirmButton: false,
                });
            }
        });
    };

    window.confirmResetWaTemplate = function (btn) {
        const doReset = () => {
            const form = btn ? btn.closest("form") : document.getElementById("formTemplateWa");
            if (!form) return;
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "reset_template";
            input.value = "1";
            form.appendChild(input);
            form.submit();
        };

        if (window.Swal) {
            Swal.fire({
                title: "Reset Template WhatsApp?",
                text: "Format pesan WhatsApp akan dikembalikan ke template bawaan sistem.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#f59e0b",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Reset ke Default",
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    doReset();
                }
            });
        } else {
            doReset();
        }
    };

    window.unduhQrCodeSvg = function () {
        if (!currentWaNisn) return;
        window.location.href = `/dashboard/kartu-pelajar/qrcode-svg/${encodeURIComponent(currentWaNisn)}`;
    };

    // =========================================================================
    // 6. Modal Klaim Garansi Kartu Pelajar (1x Ganti Kartu Full, 3x Stiker)
    // =========================================================================
    const modalGaransi = document.getElementById("modalKlaimGaransi");
    const formGaransi = document.getElementById("formKlaimGaransi");

    window.openKlaimGaransi = function (pdId, nama, nisn, rombel, klaimKartu, klaimStiker, kuotaKartu, kuotaStiker, isCetak) {
        if (!modalGaransi) return;

        document.getElementById("garansiPdId").value = pdId;
        document.getElementById("garansiPdNama").textContent = nama;
        document.getElementById("garansiPdNisn").textContent = nisn || "-";
        document.getElementById("garansiPdRombel").textContent = rombel || "-";

        const isCetakBool = Boolean(parseInt(isCetak));
        const sisaKartu = Math.max(0, kuotaKartu - klaimKartu);
        const sisaStiker = Math.max(0, kuotaStiker - klaimStiker);

        const bannerActive = document.getElementById("garansiActiveNotice");
        const bannerNotActive = document.getElementById("garansiNotActiveNotice");
        const btnSubmit = document.getElementById("btnSubmitGaransi");

        const badgeKartu = document.getElementById("garansiSisaKartu");
        const badgeStiker = document.getElementById("garansiSisaStiker");
        const optKartu = document.getElementById("optGaransiKartu");
        const optStiker = document.getElementById("optGaransiStiker");
        const alasanInput = document.getElementById("garansiAlasan");

        if (isCetakBool) {
            if (bannerActive) bannerActive.style.display = "flex";
            if (bannerNotActive) bannerNotActive.style.display = "none";

            if (badgeKartu) badgeKartu.textContent = `${sisaKartu} dari ${kuotaKartu} kali`;
            if (badgeStiker) badgeStiker.textContent = `${sisaStiker} dari ${kuotaStiker} kali`;

            if (optKartu) {
                optKartu.disabled = sisaKartu <= 0;
                if (sisaKartu > 0) optKartu.checked = true;
                else optKartu.checked = false;
            }
            if (optStiker) {
                optStiker.disabled = sisaStiker <= 0;
                if (sisaKartu <= 0 && sisaStiker > 0) optStiker.checked = true;
                else if (sisaStiker <= 0) optStiker.checked = false;
            }

            if (alasanInput) {
                alasanInput.disabled = false;
                alasanInput.value = "";
            }

            if (btnSubmit) {
                if (sisaKartu <= 0 && sisaStiker <= 0) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fas fa-ban me-1"></i> Kuota Garansi Habis';
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fas fa-check me-1"></i> Simpan Klaim Garansi';
                }
            }
        } else {
            // Belum Dicetak: Garansi belum aktif
            if (bannerActive) bannerActive.style.display = "none";
            if (bannerNotActive) bannerNotActive.style.display = "flex";

            if (badgeKartu) badgeKartu.textContent = `0 dari ${kuotaKartu} kali (Belum Aktif)`;
            if (badgeStiker) badgeStiker.textContent = `0 dari ${kuotaStiker} kali (Belum Aktif)`;

            if (optKartu) {
                optKartu.disabled = true;
                optKartu.checked = false;
            }
            if (optStiker) {
                optStiker.disabled = true;
                optStiker.checked = false;
            }

            if (alasanInput) {
                alasanInput.disabled = true;
                alasanInput.value = "";
            }

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-lock me-1"></i> Garansi Belum Berlaku';
            }
        }

        modalGaransi.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    window.closeKlaimGaransi = function () {
        if (!modalGaransi) return;
        modalGaransi.style.display = "none";
        document.body.style.overflow = "";
    };

    if (formGaransi) {
        formGaransi.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("btnSubmitGaransi");
            const originalText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

            const formData = new FormData(formGaransi);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch("/dashboard/kartu-pelajar/klaim-garansi", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;

                if (res.ok && data.success) {
                    closeKlaimGaransi();
                    Swal.fire({
                        icon: "success",
                        title: "Klaim Berhasil Dicatat!",
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false,
                    }).then(() => {
                        if (typeof window.refreshLiveTable === "function") {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire("Klaim Ditolak", data.message || "Gagal mencatat klaim garansi.", "error");
                }
            } catch (err) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalText;
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        });
    }

    // =========================================================================
    // 7. Tab 3 WhatsApp Template Live Preview & Tag Chips
    // =========================================================================
    const templateWaTextarea = document.getElementById("templateWaTextarea");
    const waLivePreviewContent = document.getElementById("waLivePreviewContent");

    function updateTemplateWaPreview() {
        if (!templateWaTextarea || !waLivePreviewContent) return;
        let val = templateWaTextarea.value;
        val = val
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\*(.*?)\*/g, "<strong>$1</strong>")
            .replace(/_(.*?)_/g, "<em>$1</em>")
            .replace(/~(.*?)~/g, "<del>$1</del>")
            .replace(/\n/g, "<br>");
        waLivePreviewContent.innerHTML = val;
    }

    if (templateWaTextarea && waLivePreviewContent) {
        templateWaTextarea.addEventListener("input", updateTemplateWaPreview);
        updateTemplateWaPreview();
    }

    document.querySelectorAll(".btn-wa-tag-chip").forEach(chip => {
        chip.addEventListener("click", function () {
            const tag = this.getAttribute("data-tag");
            if (!templateWaTextarea || !tag) return;
            const start = templateWaTextarea.selectionStart;
            const end = templateWaTextarea.selectionEnd;
            const text = templateWaTextarea.value;
            templateWaTextarea.value = text.substring(0, start) + tag + text.substring(end);
            templateWaTextarea.focus();
            templateWaTextarea.selectionStart = templateWaTextarea.selectionEnd = start + tag.length;
            updateTemplateWaPreview();
        });
    });

    // =========================================================================
    // 8. Modal Cetak Pengambilan & Tambah Pengeluaran
    // =========================================================================
    window.openModalCetakPengambilan = function () {
        const m = document.getElementById("modalCetakPengambilan");
        if (m) {
            m.style.display = "flex";
            document.body.style.overflow = "hidden";
        }
    };

    window.closeModalCetakPengambilan = function () {
        const m = document.getElementById("modalCetakPengambilan");
        if (m) {
            m.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    window.openTambahPengeluaran = function () {
        const m = document.getElementById("modalTambahPengeluaran");
        if (m) {
            m.style.display = "flex";
            document.body.style.overflow = "hidden";
            const form = document.getElementById("formTambahPengeluaran");
            if (form) form.reset();
            const jml = document.getElementById("pgJumlah");
            if (jml) jml.value = "1";
            const preview = document.getElementById("pgNominalPreview");
            if (preview) {
                preview.innerHTML = `<span>Total Pengeluaran:</span> <strong style="font-size: 0.92rem; color: var(--text-muted);">Rp 0</strong>`;
            }
            document.getElementById("pgJudul")?.focus();
        }
    };

    window.closeTambahPengeluaran = function () {
        const m = document.getElementById("modalTambahPengeluaran");
        if (m) {
            m.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    window.openEditPengeluaran = function (data) {
        const m = document.getElementById("modalEditPengeluaran");
        const form = document.getElementById("formEditPengeluaran");
        if (!m || !form || !data) return;

        form.reset();
        document.getElementById("editPgId").value = data.id || "";
        form.action = `/dashboard/kartu-pelajar/pengeluaran/${data.id}`;

        const judulInput = document.getElementById("editPgJudul");
        if (judulInput) judulInput.value = data.judul || "";

        const katInput = document.getElementById("editPgKategori");
        if (katInput) katInput.value = data.kategori || "lainnya";

        const jumlah = parseInt(data.jumlah, 10) || 1;
        const jmlInput = document.getElementById("editPgJumlah");
        if (jmlInput) jmlInput.value = jumlah;

        const hargaSatuan = data.harga_satuan ? parseInt(data.harga_satuan, 10) : (jumlah > 0 && data.nominal ? Math.round(data.nominal / jumlah) : data.nominal);
        const nomInput = document.getElementById("editPgNominal");
        if (nomInput) nomInput.value = new Intl.NumberFormat("id-ID").format(hargaSatuan || 0);

        const tglInput = document.getElementById("editPgTanggal");
        if (tglInput) {
            if (data.tanggal) {
                tglInput.value = String(data.tanggal).substring(0, 10);
            } else {
                tglInput.value = new Date().toISOString().split("T")[0];
            }
        }

        const ketInput = document.getElementById("editPgKeterangan");
        if (ketInput) ketInput.value = data.keterangan || "";

        const hintNota = document.getElementById("editPgNotaHint");
        if (hintNota) {
            if (data.bukti_nota_path) {
                hintNota.innerHTML = `Nota saat ini: <a href="/storage/${data.bukti_nota_path.replace(/^\/+/, '')}" target="_blank" style="color: #4f46e5; text-decoration: underline;"><i class="fas fa-file-invoice me-1"></i>Lihat Nota</a>. Pilih berkas baru hanya jika ingin menggantinya.`;
            } else {
                hintNota.textContent = "JPG, PNG, WebP, PDF. Maks. 5 MB. Kosongkan jika tidak ada nota.";
            }
        }

        if (typeof updateEditPengeluaranCalc === "function") {
            updateEditPengeluaranCalc();
        }

        m.style.display = "flex";
        document.body.style.overflow = "hidden";
        if (judulInput) judulInput.focus();
    };

    window.closeEditPengeluaran = function () {
        const m = document.getElementById("modalEditPengeluaran");
        if (m) {
            m.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    // Modal Request Cetak + Autocomplete Peserta Didik
    window.openTambahRequest = function () {
        const m = document.getElementById("modalTambahRequest");
        if (m) {
            m.style.display = "flex";
            document.body.style.overflow = "hidden";
            document.getElementById("formTambahRequest")?.reset();
            document.getElementById("rqPdId") && (document.getElementById("rqPdId").value = "");
            document.getElementById("rqStatusPembayaran") && (document.getElementById("rqStatusPembayaran").value = "");
            document.getElementById("rqMetodePembayaran") && (document.getElementById("rqMetodePembayaran").value = "");
            document.getElementById("rqPilihInfo") && (document.getElementById("rqPilihInfo").textContent = "Belum ada peserta didik dipilih.");
            document.getElementById("rqSubmit") && (document.getElementById("rqSubmit").disabled = true);
            document.getElementById("rqCari")?.focus();
        }
    };

    window.closeTambahRequest = function () {
        const m = document.getElementById("modalTambahRequest");
        if (m) {
            m.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    (function initRequestAutocomplete() {
        const input = document.getElementById("rqCari");
        const list = document.getElementById("rqAcList");
        const hidden = document.getElementById("rqPdId");
        const info = document.getElementById("rqPilihInfo");
        const submit = document.getElementById("rqSubmit");
        if (!input || !list || !hidden) return;

        let timer = null;
        const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        function reset() {
            hidden.value = "";
            if (submit) submit.disabled = true;
            if (info) info.textContent = "Belum ada peserta didik dipilih.";
        }

        input.addEventListener("input", function () {
            reset();
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) {
                list.style.display = "none";
                return;
            }
            timer = setTimeout(async () => {
                try {
                    const res = await fetch(`/dashboard/kartu-pelajar/cari-siswa?q=${encodeURIComponent(q)}`, {
                        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                    });
                    const rows = await res.json();
                    list.innerHTML = rows.length
                        ? rows.map((r) => {
                            const stText = r.status_pembayaran === 'lunas' ? 'Lunas' : 'Belum Bayar';
                            const stColor = r.status_pembayaran === 'lunas' ? '#10b981' : '#f59e0b';
                            const mtText = r.metode_pembayaran ? ' &bull; ' + esc(r.metode_pembayaran.toUpperCase()) : '';
                            return `<button type="button" class="kp-ac-item" data-id="${esc(r.peserta_didik_id)}" data-label="${esc(r.nama)} (${esc(r.nama_rombel || "-")})" data-status="${esc(r.status_pembayaran || 'belum_bayar')}" data-metode="${esc(r.metode_pembayaran || '')}">
                                ${esc(r.nama)}
                                <small>${esc(r.nama_rombel || "-")} &bull; NISN: ${esc(r.nisn || "-")} &bull; <span style="color:${stColor}; font-weight:700;">${stText}</span>${mtText}</small>
                            </button>`;
                        }).join("")
                        : '<div class="kp-ac-item" style="cursor:default;">Tidak ditemukan.</div>';
                    list.style.display = "block";
                } catch (e) {
                    list.style.display = "none";
                }
            }, 300);
        });

        list.addEventListener("click", function (e) {
            const btn = e.target.closest(".kp-ac-item[data-id]");
            if (!btn) return;
            hidden.value = btn.dataset.id;
            input.value = btn.dataset.label;
            const curStatus = btn.dataset.status || "belum_bayar";
            const curMetode = btn.dataset.metode || "";
            if (document.getElementById("rqStatusPembayaran")) {
                document.getElementById("rqStatusPembayaran").value = curStatus;
            }
            if (document.getElementById("rqMetodePembayaran")) {
                document.getElementById("rqMetodePembayaran").value = curMetode;
            }
            if (info) {
                const badgeClass = curStatus === 'lunas' ? 'badge-success' : 'badge-warning';
                const stText = curStatus === 'lunas' ? 'LUNAS' : 'BELUM BAYAR';
                const mtText = curMetode ? ` (${curMetode.toUpperCase()})` : '';
                info.innerHTML = `Dipilih: <strong>${esc(btn.dataset.label)}</strong> &bull; Status saat ini: <span class="badge ${badgeClass}" style="font-size:0.7rem; padding:1px 6px;">${stText}${mtText}</span>`;
            }
            if (submit) submit.disabled = false;
            list.style.display = "none";
        });

        document.addEventListener("click", function (e) {
            if (!e.target.closest(".kp-ac-wrap")) list.style.display = "none";
        });
    })();

    // =========================================================================
    // 9. Inisialisasi Chart Keuangan Kartu Pelajar (Bar & Doughnut)
    // =========================================================================
    window._kpChartBarInstance = null;
    window._kpChartDoughnutInstance = null;

    window.initKeuanganCharts = function () {
        const payloadEl = document.getElementById("kartuPelajarChartPayload");
        if (!payloadEl || typeof Chart === "undefined") return;

        let chartData = {};
        try {
            chartData = JSON.parse(payloadEl.getAttribute("data-chart") || "{}");
        } catch (e) {
            console.error("Gagal parse kartuPelajarChartPayload", e);
            return;
        }

        // 1. Chart Bar Arus Kas (Pemasukan vs Pengeluaran)
        const ctxBar = document.getElementById("chartArusKasBar");
        if (ctxBar && chartData.labels) {
            if (window._kpChartBarInstance) {
                window._kpChartBarInstance.destroy();
            }
            window._kpChartBarInstance = new Chart(ctxBar, {
                type: "bar",
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: "Pemasukan (Siswa)",
                            data: chartData.pemasukan || [],
                            backgroundColor: "rgba(16, 185, 129, 0.75)",
                            borderColor: "#10b981",
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                        {
                            label: "Pengeluaran (Bahan/Alat)",
                            data: chartData.pengeluaran || [],
                            backgroundColor: "rgba(239, 68, 68, 0.75)",
                            borderColor: "#ef4444",
                            borderWidth: 1.5,
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "top",
                            labels: {
                                font: {
                                    family: "Plus Jakarta Sans",
                                    size: 11,
                                },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return (
                                        ctx.dataset.label +
                                        ": Rp " +
                                        new Intl.NumberFormat("id-ID").format(ctx.raw)
                                    );
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) {
                                    return (
                                        "Rp " +
                                        (v >= 1000000
                                            ? v / 1000000 + "jt"
                                            : v >= 1000
                                              ? v / 1000 + "rb"
                                              : v)
                                    );
                                },
                                font: {
                                    size: 10,
                                },
                            },
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10,
                                },
                            },
                        },
                    },
                },
            });
        }

        // 2. Chart Doughnut Kategori Pengeluaran
        const ctxDoughnut = document.getElementById("chartKategoriDoughnut");
        if (ctxDoughnut && chartData.kategori_labels) {
            if (window._kpChartDoughnutInstance) {
                window._kpChartDoughnutInstance.destroy();
            }
            const colors = ["#6366f1", "#f59e0b", "#ef4444", "#06b6d4", "#8b5cf6", "#64748b"];
            const katVals = chartData.kategori_values || [];
            const hasData = katVals.length > 0 && katVals.some(v => v > 0);

            window._kpChartDoughnutInstance = new Chart(ctxDoughnut, {
                type: "doughnut",
                data: {
                    labels: hasData ? chartData.kategori_labels : ["Belum Ada Pengeluaran"],
                    datasets: [
                        {
                            data: hasData ? katVals : [1],
                            backgroundColor: hasData ? colors.slice(0, katVals.length) : ["#e2e8f0"],
                            borderWidth: 2,
                            borderColor: "#ffffff",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                font: {
                                    family: "Plus Jakarta Sans",
                                    size: 10,
                                },
                                boxWidth: 12,
                            },
                        },
                        tooltip: {
                            enabled: hasData,
                            callbacks: {
                                label: function (ctx) {
                                    return (
                                        ctx.label +
                                        ": Rp " +
                                        new Intl.NumberFormat("id-ID").format(ctx.raw)
                                    );
                                },
                            },
                        },
                    },
                    cutout: "62%",
                },
            });
        }
    };

    window.initKeuanganCharts();
    window.addEventListener("sae:tableRefreshed", window.initKeuanganCharts);

    // =========================================================================
    // 10. AJAX: Request Cetak Kartu Pelajar (store, status, delete) - refresh hanya tabel
    // =========================================================================
    const formTambahRequest = document.getElementById("formTambahRequest");
    if (formTambahRequest) {
        formTambahRequest.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("rqSubmit");
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengirim...';
            }

            const formData = new FormData(formTambahRequest);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(formTambahRequest.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
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
                    closeTambahRequest();
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    }
                } else {
                    const errorMsg = data.errors
                        ? Object.values(data.errors).flat().join(" ")
                        : (data.message || "Terjadi kesalahan sistem.");
                    Swal.fire("Gagal", errorMsg, "error");
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

    // Delegated handler: status select change -> AJAX submit
    document.addEventListener("change", async function (e) {
        const sel = e.target;
        if (sel && sel.name === "status" && sel.closest(".form-update-request-status, form[action*='/request/']")) {
            const form = sel.closest("form");
            if (!form) return;
            e.preventDefault();
            const originalVal = sel.dataset.current || sel.value;
            const newStatus = sel.value;

            // Jangan disable elemen sebelum mengambil nilai
            const payload = {
                status: newStatus,
                _token: csrfToken,
            };

            sel.disabled = true;

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                sel.disabled = false;

                if (res.ok && data.success) {
                    sel.dataset.current = newStatus;
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    }
                } else {
                    sel.value = originalVal;
                    const errorMsg = data.errors && data.errors.status
                        ? data.errors.status.join(" ")
                        : (data.message || "Gagal memperbarui status.");
                    Swal.fire("Gagal", errorMsg, "error");
                }
            } catch (err) {
                sel.disabled = false;
                sel.value = originalVal;
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        }
    });

    // Delegated handler: delete request -> AJAX
    document.addEventListener("submit", async function (e) {
        const form = e.target;
        if (form && form.matches(".delete-request-form, .form-destroy-request, form[action*='/delete']")) {
            e.preventDefault();
            const result = await Swal.fire({
                title: "Hapus Request?",
                text: "Data antrean cetak kartu ini akan dihapus permanen.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
            });
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ _token: csrfToken }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    }
                } else {
                    Swal.fire("Gagal", data.message || "Gagal menghapus request.", "error");
                }
            } catch (err) {
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        }
    });

    // =========================================================================
    // 11. Modal & Form Tambah Pengeluaran (AJAX + Auto-format Rupiah & Perkalian Total)
    // =========================================================================
    const pgNominalInput = document.getElementById("pgNominal");
    const pgJumlahInput = document.getElementById("pgJumlah");
    const pgNominalPreview = document.getElementById("pgNominalPreview");

    function updatePengeluaranCalc() {
        if (!pgNominalInput) return;
        const rawDigits = String(pgNominalInput.value || "").replace(/[^0-9]/g, "");
        const unitPrice = rawDigits ? parseInt(rawDigits, 10) : 0;
        const formattedPrice = new Intl.NumberFormat("id-ID").format(unitPrice);
        pgNominalInput.value = rawDigits ? formattedPrice : "";

        const qty = pgJumlahInput ? (parseInt(pgJumlahInput.value, 10) || 1) : 1;
        const total = qty * unitPrice;
        const formattedTotal = new Intl.NumberFormat("id-ID").format(total);

        if (pgNominalPreview) {
            if (unitPrice > 0) {
                pgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <span style="display: inline-flex; align-items: baseline; gap: 6px;"><strong style="color: #ef4444; font-size: 0.95rem;">Rp ${formattedTotal}</strong> <span style="font-weight: 500; font-size: 0.74rem; color: var(--text-muted);">(${qty} item &times; Rp ${formattedPrice})</span></span>`;
            } else {
                pgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <strong style="color: var(--text-muted); font-size: 0.92rem;">Rp 0</strong>`;
            }
        }
    }

    if (pgNominalInput) {
        pgNominalInput.addEventListener("input", updatePengeluaranCalc);
    }
    if (pgJumlahInput) {
        pgJumlahInput.addEventListener("input", updatePengeluaranCalc);
        pgJumlahInput.addEventListener("change", updatePengeluaranCalc);
    }

    const formTambahPengeluaran = document.getElementById("formTambahPengeluaran");
    if (formTambahPengeluaran) {
        formTambahPengeluaran.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("pgSubmit") || formTambahPengeluaran.querySelector('button[type="submit"]');
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const formData = new FormData(formTambahPengeluaran);
            // Pastikan nominal yang dikirim adalah angka murni
            const rawNom = (formData.get("nominal") || "").toString().replace(/[^0-9]/g, "");
            formData.set("nominal", rawNom);

            try {
                const res = await fetch(formTambahPengeluaran.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: formData,
                });
                const data = await res.json();
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }

                if (res.ok && data.success) {
                    closeTambahPengeluaran();
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
                    const errorMsg = data.errors
                        ? Object.values(data.errors).flat().join("<br>")
                        : (data.message || "Gagal menyimpan pengeluaran.");
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Menyimpan",
                        html: errorMsg,
                    });
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
    // 11B. Modal & Form Edit Pengeluaran (AJAX + Auto-format Rupiah & Perkalian Total)
    // =========================================================================
    const editPgNominalInput = document.getElementById("editPgNominal");
    const editPgJumlahInput = document.getElementById("editPgJumlah");
    const editPgNominalPreview = document.getElementById("editPgNominalPreview");

    function updateEditPengeluaranCalc() {
        if (!editPgNominalInput) return;
        const rawDigits = String(editPgNominalInput.value || "").replace(/[^0-9]/g, "");
        const unitPrice = rawDigits ? parseInt(rawDigits, 10) : 0;
        const formattedPrice = new Intl.NumberFormat("id-ID").format(unitPrice);
        editPgNominalInput.value = rawDigits ? formattedPrice : "";

        const qty = editPgJumlahInput ? (parseInt(editPgJumlahInput.value, 10) || 1) : 1;
        const total = qty * unitPrice;
        const formattedTotal = new Intl.NumberFormat("id-ID").format(total);

        if (editPgNominalPreview) {
            if (unitPrice > 0) {
                editPgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <span style="display: inline-flex; align-items: baseline; gap: 6px;"><strong style="color: #ef4444; font-size: 0.95rem;">Rp ${formattedTotal}</strong> <span style="font-weight: 500; font-size: 0.74rem; color: var(--text-muted);">(${qty} item &times; Rp ${formattedPrice})</span></span>`;
            } else {
                editPgNominalPreview.innerHTML = `<span>Total Pengeluaran:</span> <strong style="color: var(--text-muted); font-size: 0.92rem;">Rp 0</strong>`;
            }
        }
    }

    if (editPgNominalInput) {
        editPgNominalInput.addEventListener("input", updateEditPengeluaranCalc);
    }
    if (editPgJumlahInput) {
        editPgJumlahInput.addEventListener("input", updateEditPengeluaranCalc);
        editPgJumlahInput.addEventListener("change", updateEditPengeluaranCalc);
    }

    const formEditPengeluaran = document.getElementById("formEditPengeluaran");
    if (formEditPengeluaran) {
        formEditPengeluaran.addEventListener("submit", async function (e) {
            e.preventDefault();
            const btnSubmit = document.getElementById("editPgSubmit") || formEditPengeluaran.querySelector('button[type="submit"]');
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memperbarui...';
            }

            const formData = new FormData(formEditPengeluaran);
            const rawNom = (formData.get("nominal") || "").toString().replace(/[^0-9]/g, "");
            formData.set("nominal", rawNom);

            try {
                const res = await fetch(formEditPengeluaran.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: formData,
                });
                const data = await res.json();
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }

                if (res.ok && data.success) {
                    closeEditPengeluaran();
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
                    const errorMsg = data.errors
                        ? Object.values(data.errors).flat().join("<br>")
                        : (data.message || "Gagal memperbarui pengeluaran.");
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Memperbarui",
                        html: errorMsg,
                    });
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

    // Delegated handler: delete pengeluaran -> AJAX dengan SweetAlert
    document.addEventListener("submit", async function (e) {
        const form = e.target;
        if (form && (form.matches('form.delete-pengeluaran-form') || form.matches('form[action*="destroy-pengeluaran"]'))) {
            e.preventDefault();
            const result = await Swal.fire({
                title: "Hapus Pengeluaran?",
                text: "Catatan belanja/pengeluaran ini akan dihapus permanen.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
            });
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ _token: csrfToken, _method: "DELETE" }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message, "success");
                    }
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    } else {
                        window.location.reload();
                    }
                } else {
                    Swal.fire("Gagal", data.message || "Gagal menghapus pengeluaran.", "error");
                }
            } catch (err) {
                Swal.fire("Error", "Gagal menghubungi server.", "error");
            }
        }
    });

    // =========================================================================
    // 11C. Datatable Catatan Belanja: state mandiri (prefix belanja_) via refreshLiveTable
    // =========================================================================
    function refreshBelanja(mutate) {
        const url = new URL(window.location.href);
        mutate(url.searchParams);
        url.searchParams.delete("belanja_page");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    let belanjaTimer = null;
    document.addEventListener("input", function (e) {
        if (e.target.id !== "belanjaSearch") return;
        const val = e.target.value.trim();
        e.target.closest(".live-search-wrap")?.querySelector(".clear-search")?.classList.toggle("visible", val.length > 0);
        clearTimeout(belanjaTimer);
        belanjaTimer = setTimeout(() => {
            refreshBelanja((p) => (val ? p.set("belanja_q", val) : p.delete("belanja_q")));
        }, 400);
    });

    document.addEventListener("keydown", function (e) {
        if (e.key !== "Enter" || e.target.id !== "belanjaSearch") return;
        e.preventDefault();
        clearTimeout(belanjaTimer);
        const val = e.target.value.trim();
        refreshBelanja((p) => (val ? p.set("belanja_q", val) : p.delete("belanja_q")));
    });

    document.addEventListener("change", function (e) {
        if (e.target.id !== "belanjaPerPage") return;
        refreshBelanja((p) => p.set("belanja_per_page", e.target.value));
    });

    // Capture phase agar handler global (.clear-search / .sortable-th) tidak ikut menimpa state tabel lain
    document.addEventListener("click", function (e) {
        const clearBtn = e.target.closest(".clear-search");
        const belanjaInput = clearBtn?.closest(".live-search-wrap")?.querySelector("#belanjaSearch");
        if (belanjaInput) {
            e.preventDefault();
            e.stopImmediatePropagation();
            belanjaInput.value = "";
            clearBtn.classList.remove("visible");
            refreshBelanja((p) => p.delete("belanja_q"));
            return;
        }

        const th = e.target.closest("th[data-belanja-sort]");
        if (th) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const field = th.dataset.belanjaSort;
            refreshBelanja((p) => {
                const asc = p.get("belanja_sort") === field && p.get("belanja_dir") === "asc";
                p.set("belanja_sort", field);
                p.set("belanja_dir", asc ? "desc" : "asc");
            });
        }
    }, true);

    // Close modals on escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeEditPembayaran();
            closeModalWa();
            closeKlaimGaransi();
            closeModalBulkPembayaran();
            closeModalCetakPengambilan();
            closeTambahPengeluaran();
            closeEditPengeluaran();
            closeTambahRequest();
        }
    });
});
