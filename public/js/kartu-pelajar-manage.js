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
    // 2. Multi-Select & Bulk Actions (Didelegasikan ke kartu-pelajar-bulk.js)
    // =========================================================================

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
                        window.SAE.alert("Catatan pembayaran berhasil disimpan. Membuka WhatsApp untuk mengirimkan rincian & QR Code...", "Data Disimpan!", 'success').then(() => {
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
                window.SAE.alert("Teks pesan WhatsApp berhasil disalin ke clipboard.", "Tersalin!", 'success');
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
            window.SAE.confirm("Format pesan WhatsApp akan dikembalikan ke template bawaan sistem.", "Reset Template WhatsApp?", 'warning', "Ya, Reset ke Default", "Batal").then((result) => {
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

});
