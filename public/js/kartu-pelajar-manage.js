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
        url.searchParams.delete("page");
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
    const perPageSelect = document.getElementById("perPageSelect");

    function applyFilterChange(paramName, value) {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(paramName, value);
        } else {
            url.searchParams.delete(paramName);
        }
        url.searchParams.delete("page");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (filterRombel) {
        filterRombel.addEventListener("change", function () {
            applyFilterChange("rombel", this.value);
        });
    }

    if (filterStatusBayar) {
        filterStatusBayar.addEventListener("change", function () {
            applyFilterChange("status_pembayaran", this.value);
        });
    }

    if (filterStatusCetak) {
        filterStatusCetak.addEventListener("change", function () {
            applyFilterChange("status_cetak", this.value);
        });
    }

    if (perPageSelect) {
        perPageSelect.addEventListener("change", function () {
            applyFilterChange("perPage", this.value);
        });
    }

    // =========================================================================
    // 2. Multi-Select & Bulk Actions
    // =========================================================================
    const checkAll = document.getElementById("checkAllStudents");
    const bulkBar = document.getElementById("bulkActionBar");
    const bulkCountEl = document.getElementById("bulkSelectedCount");

    function getSelectedIds() {
        return Array.from(document.querySelectorAll(".check-student-row:checked")).map(c => c.value);
    }

    function updateBulkBar() {
        const ids = getSelectedIds();
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
        }
    }

    if (checkAll) {
        checkAll.addEventListener("change", function () {
            document.querySelectorAll(".check-student-row").forEach(c => {
                c.checked = checkAll.checked;
            });
            updateBulkBar();
        });
    }

    document.addEventListener("change", function (e) {
        if (e.target.classList.contains("check-student-row")) {
            updateBulkBar();
        }
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
            set_sudah_cetak: "menandai SUDAH DICETAK",
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
                    window.location.reload();
                });
            } else {
                Swal.fire("Gagal", data.message || "Terjadi kesalahan sistem.", "error");
            }
        } catch (err) {
            Swal.fire("Error", "Gagal menghubungi server.", "error");
        }
    };

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
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil Disimpan!",
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            window.location.reload();
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

                // Update badge & icon
                const badgeEl = document.getElementById("badgeCetak_" + pdId);
                if (badgeEl) {
                    if (data.status_cetak === "sudah_cetak") {
                        badgeEl.className = "badge badge-primary";
                        badgeEl.innerHTML = '<i class="fas fa-print me-1"></i> Sudah Dicetak';
                    } else {
                        badgeEl.className = "badge badge-outline";
                        badgeEl.innerHTML = '<i class="fas fa-clock me-1"></i> Belum Dicetak';
                    }
                }
                if (btnEl) {
                    btnEl.title = data.status_cetak === "sudah_cetak" ? "Tandai Belum Dicetak" : "Tandai Sudah Dicetak";
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
            } else {
                alert("Teks pesan berhasil disalin!");
            }
        });
    };

    window.unduhQrCodeSvg = function () {
        if (!currentWaNisn) return;
        window.location.href = `/dashboard/kartu-pelajar/qrcode-svg/${encodeURIComponent(currentWaNisn)}`;
    };

    // Close modals on escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeEditPembayaran();
            closeModalWa();
        }
    });
});
