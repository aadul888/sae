/**
 * SAE - Pengumuman, Broadcast & Berita Module JS
 */

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    /* ==========================================================================
       1. MODAL FORM CREATE / EDIT PENGUMUMAN
       ========================================================================== */
    const modalPengumuman = document.getElementById("pengumumanModal");
    const formPengumuman = document.getElementById("pengumumanForm");
    const modalPengumumanTitle = document.getElementById("modalTitle");
    const methodFieldPengumuman = document.getElementById("methodField");
    const inputJudul = document.getElementById("inputJudul");
    const inputPenulisNama = document.getElementById("inputPenulisNama");
    const inputTargetPeran = document.getElementById("inputTargetPeran");
    const inputIsi = document.getElementById("inputIsi");
    const inputIsActive = document.getElementById("inputIsActive");
    const btnOpenCreate = document.getElementById("btnOpenCreateModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const btnCancelModal = document.getElementById("btnCancelModal");

    const openPengumumanModal = (isEdit = false, item = null) => {
        if (!modalPengumuman) return;
        if (isEdit && item) {
            modalPengumumanTitle.innerHTML = '<i class="fas fa-pen-to-square text-primary me-2"></i> Edit Pengumuman';
            formPengumuman.action = `/dashboard/pengumuman/${item.id}`;
            methodFieldPengumuman.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            if (inputJudul) inputJudul.value = item.judul || "";
            if (inputPenulisNama) inputPenulisNama.value = item.penulis_nama || "";
            if (inputTargetPeran) inputTargetPeran.value = item.target_peran || "semua";
            if (inputIsi) inputIsi.value = item.isi || "";
            if (inputIsActive) inputIsActive.checked = !!item.is_active;
        } else {
            modalPengumumanTitle.innerHTML = '<i class="fas fa-bullhorn text-primary me-2"></i> Buat Pengumuman Baru';
            formPengumuman.action = "/dashboard/pengumuman";
            methodFieldPengumuman.innerHTML = "";
            formPengumuman.reset();
            if (inputTargetPeran) inputTargetPeran.value = "semua";
            if (inputIsActive) inputIsActive.checked = true;
        }
        modalPengumuman.style.display = "flex";
    };

    const closePengumumanModal = () => {
        if (modalPengumuman) modalPengumuman.style.display = "none";
    };

    if (btnOpenCreate) btnOpenCreate.addEventListener("click", () => openPengumumanModal(false));
    if (btnCloseModal) btnCloseModal.addEventListener("click", closePengumumanModal);
    if (btnCancelModal) btnCancelModal.addEventListener("click", closePengumumanModal);

    document.querySelectorAll(".btn-edit-pengumuman").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                openPengumumanModal(true, item);
            } catch (err) {
                console.error("Gagal parse data pengumuman", err);
            }
        });
    });

    /* ==========================================================================
       2. MODAL FORM CREATE / EDIT BROADCAST
       ========================================================================== */
    const modalBroadcast = document.getElementById("broadcastModal");
    const formBroadcast = document.getElementById("broadcastForm");
    const broadcastModalTitle = document.getElementById("broadcastModalTitle");
    const broadcastMethodField = document.getElementById("broadcastMethodField");
    const inputBroadcastJudul = document.getElementById("inputBroadcastJudul");
    const inputBroadcastPenulis = document.getElementById("inputBroadcastPenulis");
    const inputBroadcastIsi = document.getElementById("inputBroadcastIsi");
    const inputBroadcastIsActive = document.getElementById("inputBroadcastIsActive");
    const btnOpenBroadcast = document.getElementById("btnOpenBroadcastModal");
    const btnCloseBroadcastModal = document.getElementById("btnCloseBroadcastModal");
    const btnCancelBroadcastModal = document.getElementById("btnCancelBroadcastModal");

    const openBroadcastModal = (isEdit = false, item = null) => {
        if (!modalBroadcast) return;
        if (isEdit && item) {
            broadcastModalTitle.innerHTML = '<i class="fas fa-pen-to-square text-accent me-2"></i> Edit Info Broadcast';
            formBroadcast.action = `/dashboard/pengumuman/${item.id}`;
            broadcastMethodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            if (inputBroadcastJudul) inputBroadcastJudul.value = item.judul || "";
            if (inputBroadcastPenulis) inputBroadcastPenulis.value = item.penulis_nama || "";
            if (inputBroadcastIsi) inputBroadcastIsi.value = item.isi || "";
            if (inputBroadcastIsActive) inputBroadcastIsActive.checked = !!item.is_active;
        } else {
            broadcastModalTitle.innerHTML = '<i class="fas fa-tower-broadcast text-accent me-2"></i> Buat Info Broadcast Baru';
            formBroadcast.action = "/dashboard/pengumuman";
            broadcastMethodField.innerHTML = "";
            formBroadcast.reset();
            if (inputBroadcastIsActive) inputBroadcastIsActive.checked = true;
        }
        modalBroadcast.style.display = "flex";
    };

    const closeBroadcastModal = () => {
        if (modalBroadcast) modalBroadcast.style.display = "none";
    };

    if (btnOpenBroadcast) btnOpenBroadcast.addEventListener("click", () => openBroadcastModal(false));
    if (btnCloseBroadcastModal) btnCloseBroadcastModal.addEventListener("click", closeBroadcastModal);
    if (btnCancelBroadcastModal) btnCancelBroadcastModal.addEventListener("click", closeBroadcastModal);

    document.querySelectorAll(".btn-edit-broadcast").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                openBroadcastModal(true, item);
            } catch (err) {
                console.error("Gagal parse data broadcast", err);
            }
        });
    });

    /* ==========================================================================
       3. MODAL FORM CREATE / EDIT BERITA & RICH TEXT EDITOR
       ========================================================================== */
    const modalBerita = document.getElementById("beritaModal");
    const formBerita = document.getElementById("beritaForm");
    const beritaModalTitle = document.getElementById("beritaModalTitle");
    const beritaMethodField = document.getElementById("beritaMethodField");
    const inputBeritaJudul = document.getElementById("inputBeritaJudul");
    const inputBeritaKategori = document.getElementById("inputBeritaKategori");
    const inputBeritaPenulis = document.getElementById("inputBeritaPenulis");
    const inputBeritaRingkasan = document.getElementById("inputBeritaRingkasan");
    const inputBeritaIsi = document.getElementById("inputBeritaIsi");
    const beritaEditorContent = document.getElementById("beritaEditorContent");
    const inputBeritaIsActive = document.getElementById("inputBeritaIsActive");
    const inputBeritaGambar = document.getElementById("inputBeritaGambar");
    const beritaDropZone = document.getElementById("beritaDropZone");
    const beritaNewGambarPreview = document.getElementById("beritaNewGambarPreview");
    const beritaNewGambarImg = document.getElementById("beritaNewGambarImg");
    const beritaNewGambarName = document.getElementById("beritaNewGambarName");
    const btnHapusNewGambar = document.getElementById("btnHapusNewGambar");
    const beritaCurrentGambarWrap = document.getElementById("beritaCurrentGambarWrap");
    const beritaCurrentGambar = document.getElementById("beritaCurrentGambar");
    const cbHapusGambarSaatIni = document.getElementById("cbHapusGambarSaatIni");
    const btnOpenBerita = document.getElementById("btnOpenBeritaModal");
    const btnCloseBeritaModal = document.getElementById("btnCloseBeritaModal");
    const btnCancelBeritaModal = document.getElementById("btnCancelBeritaModal");
    const btnEditorInsertLink = document.getElementById("btnEditorInsertLink");

    // Drag and Drop foto sampul
    const handleFilePreview = (file) => {
        if (!file || !file.type.startsWith("image/")) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            if (beritaNewGambarImg) beritaNewGambarImg.src = e.target.result;
            if (beritaNewGambarName) beritaNewGambarName.textContent = file.name + " (" + Math.round(file.size / 1024) + " KB)";
            if (beritaNewGambarPreview) beritaNewGambarPreview.style.display = "flex";
        };
        reader.readAsDataURL(file);
    };

    if (inputBeritaGambar) {
        inputBeritaGambar.addEventListener("change", function () {
            if (this.files && this.files[0]) {
                handleFilePreview(this.files[0]);
            }
        });
    }

    if (beritaDropZone) {
        ["dragenter", "dragover"].forEach((eventName) => {
            beritaDropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                beritaDropZone.classList.add("dragover");
            });
        });
        ["dragleave", "drop"].forEach((eventName) => {
            beritaDropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                beritaDropZone.classList.remove("dragover");
            });
        });
        beritaDropZone.addEventListener("drop", (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files[0]) {
                const file = dt.files[0];
                if (file.type.startsWith("image/")) {
                    inputBeritaGambar.files = dt.files;
                    handleFilePreview(file);
                }
            }
        });
    }

    if (btnHapusNewGambar) {
        btnHapusNewGambar.addEventListener("click", () => {
            if (inputBeritaGambar) inputBeritaGambar.value = "";
            if (beritaNewGambarPreview) beritaNewGambarPreview.style.display = "none";
        });
    }

    // Toolbar Word/Office actions
    document.querySelectorAll("#beritaEditorToolbar .tb-btn[data-command]").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const cmd = btn.dataset.command;
            const val = btn.dataset.val || null;
            document.execCommand(cmd, false, val);
            if (beritaEditorContent) beritaEditorContent.focus();
        });
    });

    if (btnEditorInsertLink) {
        btnEditorInsertLink.addEventListener("click", (e) => {
            e.preventDefault();
            const url = prompt("Masukkan tautan URL (contoh: https://sekolah.sch.id):");
            if (url) {
                document.execCommand("createLink", false, url);
            }
            if (beritaEditorContent) beritaEditorContent.focus();
        });
    }

    // Sync isi contenteditable ke hidden textarea saat form submit
    if (formBerita) {
        formBerita.addEventListener("submit", function () {
            if (beritaEditorContent && inputBeritaIsi) {
                inputBeritaIsi.value = beritaEditorContent.innerHTML.trim();
            }
        });
    }

    const openBeritaModal = (isEdit = false, item = null) => {
        if (!modalBerita) return;
        if (beritaNewGambarPreview) beritaNewGambarPreview.style.display = "none";
        if (inputBeritaGambar) inputBeritaGambar.value = "";
        if (cbHapusGambarSaatIni) cbHapusGambarSaatIni.checked = false;

        if (isEdit && item) {
            beritaModalTitle.innerHTML = '<i class="fas fa-pen-to-square text-warning me-2"></i> Edit Berita';
            formBerita.action = `/dashboard/pengumuman/berita/${item.id}`;
            beritaMethodField.innerHTML = "";
            if (inputBeritaJudul) inputBeritaJudul.value = item.judul || "";
            if (inputBeritaKategori) inputBeritaKategori.value = item.kategori || "Umum";
            if (inputBeritaPenulis) inputBeritaPenulis.value = item.penulis_nama || "";
            if (inputBeritaRingkasan) inputBeritaRingkasan.value = item.ringkasan || "";
            if (beritaEditorContent) {
                beritaEditorContent.innerHTML = item.isi || "";
            }
            if (inputBeritaIsi) inputBeritaIsi.value = item.isi || "";
            if (inputBeritaIsActive) inputBeritaIsActive.checked = !!item.is_active;

            if (item.gambar && beritaCurrentGambarWrap && beritaCurrentGambar) {
                beritaCurrentGambar.src = '/' + item.gambar.replace(/^\//, '');
                beritaCurrentGambarWrap.style.display = "flex";
            } else if (beritaCurrentGambarWrap) {
                beritaCurrentGambarWrap.style.display = "none";
            }
        } else {
            beritaModalTitle.innerHTML = '<i class="fas fa-newspaper text-warning me-2"></i> Tulis Berita Baru';
            formBerita.action = "/dashboard/pengumuman/berita";
            beritaMethodField.innerHTML = "";
            formBerita.reset();
            if (beritaEditorContent) beritaEditorContent.innerHTML = "";
            if (inputBeritaIsi) inputBeritaIsi.value = "";
            if (inputBeritaKategori) inputBeritaKategori.value = "Akademik";
            // Default saat buat baru: DRAFT (tidak dicentang)
            if (inputBeritaIsActive) inputBeritaIsActive.checked = false;
            if (beritaCurrentGambarWrap) beritaCurrentGambarWrap.style.display = "none";
        }
        modalBerita.style.display = "flex";
        if (beritaEditorContent) {
            setTimeout(() => beritaEditorContent.focus(), 150);
        }
    };

    const closeBeritaModal = () => {
        if (modalBerita) modalBerita.style.display = "none";
    };

    if (btnOpenBerita) btnOpenBerita.addEventListener("click", () => openBeritaModal(false));
    if (btnCloseBeritaModal) btnCloseBeritaModal.addEventListener("click", closeBeritaModal);
    if (btnCancelBeritaModal) btnCancelBeritaModal.addEventListener("click", closeBeritaModal);

    document.querySelectorAll(".btn-edit-berita").forEach((btn) => {
        btn.addEventListener("click", () => {
            try {
                const item = JSON.parse(btn.dataset.item);
                openBeritaModal(true, item);
            } catch (err) {
                console.error("Gagal parse data berita", err);
            }
        });
    });

    /* ==========================================================================
       4. MODAL PREVIEW DETAIL PENGUMUMAN / BROADCAST
       ========================================================================== */
    const previewModal = document.getElementById("previewPengumumanModal");
    const previewJudul = document.getElementById("previewJudul");
    const previewPenulis = document.getElementById("previewPenulis");
    const previewTanggal = document.getElementById("previewTanggal");
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
                if (previewIsi) previewIsi.textContent = item.isi || "-";

                if (previewTargetBadge) {
                    if (item.tipe === "broadcast" || item.target === "publik") {
                        previewTargetBadge.className = "badge badge-accent";
                        previewTargetBadge.innerHTML = '<i class="fas fa-tower-broadcast me-1"></i> Running Text Publik';
                    } else {
                        previewTargetBadge.className = "badge badge-primary";
                        previewTargetBadge.innerHTML = '<i class="fas fa-bullhorn me-1"></i> Notifikasi Pengumuman';
                    }
                }

                if (previewPeranBadge) {
                    if (item.tipe === "broadcast" || item.target === "publik") {
                        previewPeranBadge.textContent = "Portal Utama";
                    } else {
                        const roleLabel = item.target_peran === "semua" ? "Semua Pengguna" : (item.target_peran || "").replace("_", " ");
                        previewPeranBadge.textContent = "Sasaran: " + roleLabel;
                    }
                }

                if (previewModal) previewModal.style.display = "flex";
            } catch (err) {
                console.error("Gagal membuka pratinjau", err);
            }
        });
    });

    /* ==========================================================================
       5. AJAX TOGGLE STATUS PENGUMUMAN / BROADCAST
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
                        window.SAE.toast(data.message || "Status berhasil diubah.", "success");
                    }
                } else {
                    throw new Error(data.message || "Gagal mengubah status");
                }
            } catch (err) {
                this.checked = originalChecked;
                if (slider) slider.style.backgroundColor = originalChecked ? "#10b981" : "#64748b";
                if (window.SAE && typeof window.SAE.toast === "function") {
                    window.SAE.toast(err.message || "Terjadi kesalahan koneksi.", "danger");
                }
            }
        });
    });

    /* ==========================================================================
       6. AJAX TOGGLE & TOMBOL AKSI PUBLIKASI BERITA
       ========================================================================== */
    const handleToggleBerita = async (id, buttonEl = null) => {
        try {
            const res = await fetch(`/dashboard/pengumuman/berita/${id}/toggle`, {
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
                    window.SAE.toast(data.message || "Status berita berhasil diubah.", "success");
                }
                // Reload halaman tab berita agar badge, aksi, dan tabel ter-update presisi
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                throw new Error(data.message || "Gagal mengubah status berita");
            }
        } catch (err) {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast(err.message || "Terjadi kesalahan koneksi.", "danger");
            } else {
                alert(err.message || "Terjadi kesalahan.");
            }
        }
    };

    document.querySelectorAll(".btn-toggle-publish-berita").forEach((btn) => {
        btn.addEventListener("click", function () {
            const id = this.dataset.id;
            const isCurrentlyActive = this.dataset.status === "1";
            const confirmMsg = isCurrentlyActive
                ? "Tarik berita ini kembali menjadi Draft? Berita tidak akan tampil di portal publik."
                : "Terbitkan berita ini sekarang ke beranda utama dan portal publik?";

            if (confirm(confirmMsg)) {
                handleToggleBerita(id, this);
            }
        });
    });

    document.querySelectorAll(".toggle-berita-status").forEach((toggle) => {
        toggle.addEventListener("change", function () {
            handleToggleBerita(this.dataset.id);
        });
    });

    /* ==========================================================================
       7. KONFIRMASI HAPUS
       ========================================================================== */
    document.querySelectorAll('form[data-confirm="delete"]').forEach((deleteForm) => {
        deleteForm.addEventListener("submit", async function (e) {
            if (this.dataset.confirmed === "true") return;

            e.preventDefault();
            const name = this.dataset.name || "item ini";

            let confirmed = false;
            if (window.SAE && typeof window.SAE.confirm === "function") {
                confirmed = await window.SAE.confirm(
                    `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`,
                    "Konfirmasi Hapus",
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

    /* ==========================================================================
       8. SORT HEADER DATATABLE
       ========================================================================== */
    document.querySelectorAll(".sortable-th").forEach(function (th) {
        th.style.cursor = "pointer";
        th.addEventListener("click", function () {
            const sortField = this.getAttribute("data-sort");
            if (!sortField) return;

            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort") || "created_at";
            const currentDir = url.searchParams.get("sort_dir") || "desc";

            let newDir = "asc";
            if (currentSort === sortField && currentDir === "asc") {
                newDir = "desc";
            }

            url.searchParams.set("sort", sortField);
            url.searchParams.set("sort_dir", newDir);
            url.searchParams.delete("page");
            window.location.href = url.toString();
        });
    });
});
