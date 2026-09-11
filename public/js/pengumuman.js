/**
 * SAE - Pengumuman & Broadcast Module JS
 */

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";

    /* ==========================================================================
       1. MODAL FORM CREATE / EDIT PENGUMUMAN (ADMIN)
       ========================================================================== */
    const modal = document.getElementById("pengumumanModal");
    const form = document.getElementById("pengumumanForm");
    const modalTitle = document.getElementById("modalTitle");
    const methodField = document.getElementById("methodField");
    const inputJudul = document.getElementById("inputJudul");
    const inputPenulisNama = document.getElementById("inputPenulisNama");
    const inputIsi = document.getElementById("inputIsi");
    const inputTarget = document.getElementById("inputTarget");
    const inputTargetPeran = document.getElementById("inputTargetPeran");
    const inputIsActive = document.getElementById("inputIsActive");
    const btnOpenCreate = document.getElementById("btnOpenCreateModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const btnCancelModal = document.getElementById("btnCancelModal");

    const openModal = (isEdit = false, item = null) => {
        if (!modal) return;
        if (isEdit && item) {
            modalTitle.innerHTML =
                '<i class="fas fa-pen-to-square text-primary me-2"></i> Edit Pengumuman';
            form.action = `/dashboard/pengumuman/${item.id}`;
            methodField.innerHTML =
                '<input type="hidden" name="_method" value="PUT">';
            if (inputJudul) inputJudul.value = item.judul || "";
            if (inputPenulisNama) inputPenulisNama.value = item.penulis_nama || "";
            if (inputIsi) inputIsi.value = item.isi || "";
            if (inputTarget) inputTarget.value = item.target || "semua";
            if (inputTargetPeran) inputTargetPeran.value = item.target_peran || "semua";
            if (inputIsActive) inputIsActive.checked = !!item.is_active;
        } else {
            modalTitle.innerHTML =
                '<i class="fas fa-bullhorn text-primary me-2"></i> Buat Pengumuman Baru';
            form.action = "/dashboard/pengumuman";
            methodField.innerHTML = "";
            form.reset();
            if (inputTarget) inputTarget.value = "semua";
            if (inputTargetPeran) inputTargetPeran.value = "semua";
            if (inputIsActive) inputIsActive.checked = true;
        }
        modal.style.display = "flex";
    };

    const closeModal = () => {
        if (modal) modal.style.display = "none";
    };

    if (btnOpenCreate) {
        btnOpenCreate.addEventListener("click", () => openModal(false));
    }
    if (btnCloseModal) {
        btnCloseModal.addEventListener("click", closeModal);
    }
    if (btnCancelModal) {
        btnCancelModal.addEventListener("click", closeModal);
    }

    document.querySelectorAll(".btn-edit-pengumuman").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                openModal(true, item);
            } catch (err) {
                console.error("Gagal menguraikan data pengumuman", err);
            }
        });
    });

    /* ==========================================================================
       2. MODAL PREVIEW DETAIL PENGUMUMAN (ADMIN)
       ========================================================================== */
    const previewModal = document.getElementById("previewPengumumanModal");
    const previewJudul = document.getElementById("previewJudul");
    const previewPenulis = document.getElementById("previewPenulis");
    const previewTanggal = document.getElementById("previewTanggal");
    const previewPembaca = document.getElementById("previewPembaca");
    const previewIsi = document.getElementById("previewIsi");
    const previewTargetBadge = document.getElementById("previewTargetBadge");
    const previewPeranBadge = document.getElementById("previewPeranBadge");
    const btnClosePreviewModal = document.getElementById("btnClosePreviewModal");
    const btnClosePreviewBtn = document.getElementById("btnClosePreviewBtn");

    const closePreviewModal = () => {
        if (previewModal) previewModal.style.display = "none";
    };

    if (btnClosePreviewModal) btnClosePreviewModal.addEventListener("click", closePreviewModal);
    if (btnClosePreviewBtn) btnClosePreviewBtn.addEventListener("click", closePreviewModal);

    document.querySelectorAll(".btn-preview-pengumuman").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                if (previewJudul) previewJudul.textContent = item.judul || "-";
                if (previewPenulis) previewPenulis.textContent = item.penulis_nama || "Administrator";
                if (previewTanggal) {
                    const d = item.created_at ? new Date(item.created_at) : new Date();
                    previewTanggal.textContent = d.toLocaleDateString("id-ID", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                    });
                }
                if (previewPembaca) previewPembaca.textContent = (item.dibaca_pengguna || []).length;
                if (previewIsi) previewIsi.textContent = item.isi || "-";

                if (previewTargetBadge) {
                    if (item.target === "publik") {
                        previewTargetBadge.className = "badge badge-accent";
                        previewTargetBadge.innerHTML = '<i class="fas fa-globe me-1"></i> Teks Publik';
                    } else if (item.target === "pengguna") {
                        previewTargetBadge.className = "badge badge-warning";
                        previewTargetBadge.innerHTML = '<i class="fas fa-bell me-1"></i> Lonceng Pengguna';
                    } else {
                        previewTargetBadge.className = "badge badge-primary";
                        previewTargetBadge.innerHTML = '<i class="fas fa-bullhorn me-1"></i> Publik &amp; Lonceng';
                    }
                }

                if (previewPeranBadge) {
                    const roleLabel = item.target_peran === "semua" ? "Semua Pengguna" : item.target_peran.replace("_", " ");
                    previewPeranBadge.textContent = "Sasaran: " + roleLabel;
                }

                if (previewModal) previewModal.style.display = "flex";
            } catch (err) {
                console.error("Gagal membuka pratinjau", err);
            }
        });
    });

    /* ==========================================================================
       3. MODAL READER PENGUMUMAN (PENGGUNA)
       ========================================================================== */
    const readerModal = document.getElementById("userReaderModal");
    const readerJudul = document.getElementById("readerJudul");
    const readerPenulis = document.getElementById("readerPenulis");
    const readerTanggal = document.getElementById("readerTanggal");
    const readerIsi = document.getElementById("readerIsi");
    const readerPenulisBadge = document.getElementById("readerPenulisBadge");
    const readerSasaranBadge = document.getElementById("readerSasaranBadge");
    const btnCloseReaderModal = document.getElementById("btnCloseReaderModal");
    const btnCloseReaderBtn = document.getElementById("btnCloseReaderBtn");

    const closeReaderModal = () => {
        if (readerModal) readerModal.style.display = "none";
    };

    if (btnCloseReaderModal) btnCloseReaderModal.addEventListener("click", closeReaderModal);
    if (btnCloseReaderBtn) btnCloseReaderBtn.addEventListener("click", closeReaderModal);

    const openReader = async (item) => {
        if (!readerModal || !item) return;

        if (readerJudul) readerJudul.textContent = item.judul || "-";
        if (readerPenulis) readerPenulis.textContent = item.penulis_nama || "Administrator";
        if (readerTanggal) {
            const d = item.created_at ? new Date(item.created_at) : new Date();
            readerTanggal.textContent = d.toLocaleDateString("id-ID", {
                day: "numeric",
                month: "long",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            }) + " WIB";
        }
        if (readerIsi) readerIsi.textContent = item.isi || "-";

        if (readerPenulisBadge) {
            const isSys = (item.penulis_nama || "").toLowerCase().includes("sistem");
            readerPenulisBadge.className = `badge ${isSys ? "badge-accent" : "badge-primary"}`;
            readerPenulisBadge.innerHTML = `<i class="fas ${isSys ? "fa-robot" : "fa-user-pen"} me-1"></i> ${item.penulis_nama || "Administrator"}`;
        }

        if (readerSasaranBadge) {
            const roleLabel = item.target_peran === "semua" ? "Semua Pengguna" : item.target_peran.replace("_", " ");
            readerSasaranBadge.textContent = "Sasaran: " + roleLabel;
        }

        readerModal.style.display = "flex";

        // Tandai sebagai dibaca secara background jika belum dibaca
        const card = document.getElementById(`item-pengumuman-${item.id}`);
        if (card && card.classList.contains("unread-item")) {
            markItemAsReadUi(item.id);
            try {
                await fetch(`/dashboard/informasi/${item.id}/mark-read`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });
            } catch (e) {
                // Abaikan jika offline
            }
        }
    };

    document.querySelectorAll(".btn-open-reader").forEach((el) => {
        el.addEventListener("click", () => {
            try {
                const item = JSON.parse(el.dataset.item);
                openReader(item);
            } catch (err) {
                console.error("Gagal membuka pembaca", err);
            }
        });
    });

    // Auto-open reader if URL has highlight parameter
    if (window.PAGE_HIGHLIGHT_ID) {
        const highlightCard = document.getElementById(`item-pengumuman-${window.PAGE_HIGHLIGHT_ID}`);
        if (highlightCard) {
            highlightCard.scrollIntoView({ behavior: "smooth", block: "center" });
            highlightCard.style.outline = "2px solid var(--primary)";
            highlightCard.style.outlineOffset = "4px";
            setTimeout(() => {
                highlightCard.style.outline = "none";
            }, 3000);

            const openBtn = highlightCard.querySelector(".btn-open-reader");
            if (openBtn) {
                try {
                    const item = JSON.parse(openBtn.dataset.item);
                    openReader(item);
                } catch (e) {}
            }
        }
    }

    /* ==========================================================================
       4. TANDAI SUDAH DIBACA (SATUAN & MASSAL DI FEED PENGGUNA)
       ========================================================================== */
    const markItemAsReadUi = (id) => {
        const card = document.getElementById(`item-pengumuman-${id}`);
        if (!card) return;

        card.classList.remove("unread-item");
        card.style.borderLeft = "4px solid var(--primary)";

        const badgeUnread = card.querySelector(".badge-unread-status");
        if (badgeUnread) {
            badgeUnread.className = "badge badge-outline";
            badgeUnread.style.opacity = "0.7";
            badgeUnread.innerHTML = '<i class="fas fa-check me-1 text-success"></i> Sudah Dibaca';
        }

        const btnMark = card.querySelector(".btn-mark-single-read");
        if (btnMark) btnMark.remove();

        // Kurangi counter unread di stats & header
        const statUnread = document.getElementById("statUnreadCount");
        if (statUnread) {
            const current = parseInt(statUnread.textContent, 10) || 0;
            if (current > 0) statUnread.textContent = current - 1;
        }

        const headerBadge = document.getElementById("headerNotifBadge");
        if (headerBadge) {
            const current = parseInt(headerBadge.textContent, 10) || 0;
            if (current <= 1) {
                headerBadge.remove();
                const bellDot = document.getElementById("bellNotifDot");
                if (bellDot) bellDot.remove();
            } else {
                headerBadge.textContent = `${current - 1} Baru`;
            }
        }
    };

    document.querySelectorAll(".btn-mark-single-read").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.stopPropagation();
            const id = btn.dataset.id;
            if (!id) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res = await fetch(`/dashboard/informasi/${id}/mark-read`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });

                const data = await res.json();
                if (data.status === "success") {
                    markItemAsReadUi(id);
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast("Pengumuman ditandai sudah dibaca.", "success");
                    }
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Tandai Dibaca';
            }
        });
    });

    // Form Mark All Read (AJAX)
    const formMarkAll = document.getElementById("formMarkAllRead");
    if (formMarkAll) {
        formMarkAll.addEventListener("submit", async (e) => {
            e.preventDefault();
            const btn = document.getElementById("btnMarkAllRead");
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
            }

            try {
                const res = await fetch("/dashboard/informasi/mark-all-read", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });

                const data = await res.json();
                if (data.status === "success") {
                    window.updateFeedAllRead();
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message || "Semua pengumuman telah dibaca.", "success");
                    }
                }
            } catch (err) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca';
                }
            }
        });
    }

    // Global updater bila semua pengumuman ditandai dibaca
    window.updateFeedAllRead = () => {
        document.querySelectorAll(".card-informasi-item.unread-item").forEach((card) => {
            card.classList.remove("unread-item");
            card.style.borderLeft = "4px solid var(--primary)";

            const badge = card.querySelector(".badge-unread-status");
            if (badge) {
                badge.className = "badge badge-outline";
                badge.style.opacity = "0.7";
                badge.innerHTML = '<i class="fas fa-check me-1 text-success"></i> Sudah Dibaca';
            }

            const btn = card.querySelector(".btn-mark-single-read");
            if (btn) btn.remove();
        });

        const statUnread = document.getElementById("statUnreadCount");
        if (statUnread) statUnread.textContent = "0";

        const btnMarkAll = document.getElementById("btnMarkAllRead");
        if (btnMarkAll) {
            btnMarkAll.disabled = true;
            btnMarkAll.innerHTML = '<i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca';
        }

        const bellDot = document.getElementById("bellNotifDot");
        if (bellDot) bellDot.remove();

        const headerBadge = document.getElementById("headerNotifBadge");
        if (headerBadge) headerBadge.remove();

        const quickMarkBtn = document.getElementById("btnQuickMarkAllRead");
        if (quickMarkBtn) quickMarkBtn.remove();
    };

    /* ==========================================================================
       5. AJAX TOGGLE STATUS (ADMIN)
       ========================================================================== */
    document.querySelectorAll(".toggle-pengumuman-status").forEach((toggle) => {
        toggle.addEventListener("change", async function () {
            const id = this.dataset.id;
            const originalChecked = !this.checked;
            const slider = this.nextElementSibling;

            if (slider) {
                slider.style.backgroundColor = this.checked ? "#10b981" : "#64748b";
            }

            try {
                const res = await fetch(`/dashboard/pengumuman/${id}/toggle`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });

                const data = await res.json();
                if (data.status === "success") {
                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(data.message || "Status pengumuman berhasil diubah.", "success");
                    }
                } else {
                    throw new Error(data.message || "Gagal mengubah status");
                }
            } catch (err) {
                this.checked = originalChecked;
                if (slider) {
                    slider.style.backgroundColor = originalChecked ? "#10b981" : "#64748b";
                }
                if (window.SAE && typeof window.SAE.toast === "function") {
                    window.SAE.toast(err.message || "Terjadi kesalahan koneksi.", "danger");
                }
            }
        });
    });

    /* ==========================================================================
       6. KONFIRMASI HAPUS PENGUMUMAN
       ========================================================================== */
    document.querySelectorAll('form[data-confirm="delete"]').forEach((deleteForm) => {
        deleteForm.addEventListener("submit", async function (e) {
            if (this.dataset.confirmed === "true") return;

            e.preventDefault();
            const name = this.dataset.name || "pengumuman ini";

            let confirmed = false;
            if (window.SAE && typeof window.SAE.confirm === "function") {
                confirmed = await window.SAE.confirm(
                    `Apakah Anda yakin ingin menghapus pengumuman "${name}"? Tindakan ini tidak dapat dibatalkan.`,
                    "Hapus Pengumuman",
                    "danger"
                );
            } else {
                confirmed = confirm(`Apakah Anda yakin ingin menghapus "${name}"?`);
            }

            if (confirmed) {
                this.dataset.confirmed = "true";
                this.submit();
            }
        });
    });
});

