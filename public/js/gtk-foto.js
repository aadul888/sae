/**
 * Pasfoto GTK (Guru / Tendik / Non-ASN) — Manajemen Data.
 * Endpoint & URL diambil dari atribut data-* pada modal, jadi satu skrip
 * dipakai untuk ketiga menu.
 */
(function () {
    let selectedPtkId = "";
    let currentFotoUrl = "";

    function toast(msg, type) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(msg, type || "info");
        }
    }

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) return "0 B";
        const k = 1024;
        const sizes = ["B", "KB", "MB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
    }

    function modal() {
        return document.getElementById("gtkFotoUploadModal");
    }

    window.openGtkFotoModal = function (ptkId, nama, ident, fotoUrl, fotoSize) {
        const m = modal();
        if (!m) return;

        selectedPtkId = ptkId;
        currentFotoUrl = fotoUrl || "";

        const previewImg = document.getElementById("gtkFotoPreviewImg");
        const placeholder = document.getElementById("gtkFotoPreviewPlaceholder");
        const specs = document.getElementById("gtkFotoFileSpecs");
        const btnDelete = document.getElementById("btnGtkDeleteFoto");
        const fileInput = document.getElementById("gtkFotoFileInput");

        if (fileInput) fileInput.value = "";
        const hidden = document.getElementById("gtkFotoUploadPtkId");
        if (hidden) hidden.value = ptkId;
        const title = document.getElementById("gtkFotoModalTitle");
        if (title) title.textContent = "Pasfoto: " + (nama || "GTK");
        const subtitle = document.getElementById("gtkFotoModalSubtitle");
        if (subtitle) subtitle.textContent = (ident ? "NUPTK/NIP: " + ident + " • " : "") + "ID: " + ptkId;

        if (currentFotoUrl) {
            if (previewImg) {
                previewImg.src = currentFotoUrl + "?v=" + Date.now();
                previewImg.style.display = "block";
            }
            if (placeholder) placeholder.style.display = "none";
            if (specs) {
                specs.style.display = "block";
                specs.innerHTML = '<i class="fas fa-circle-check me-1"></i> Format: PNG • Ukuran: ' + (fotoSize || "Tersimpan");
            }
            if (btnDelete) btnDelete.style.display = "inline-flex";
        } else {
            if (previewImg) {
                previewImg.src = "";
                previewImg.style.display = "none";
            }
            if (placeholder) placeholder.style.display = "block";
            if (specs) {
                specs.style.display = "none";
                specs.textContent = "";
            }
            if (btnDelete) btnDelete.style.display = "none";
        }

        m.style.display = "flex";
    };

    window.closeGtkFotoModal = function () {
        const m = modal();
        if (m) m.style.display = "none";
        const fileInput = document.getElementById("gtkFotoFileInput");
        if (fileInput) fileInput.value = "";
    };

    function previewFile(file) {
        if (!file) return;

        const ext = file.name ? file.name.split(".").pop().toLowerCase() : "";
        if (
            ext !== "png" ||
            (file.type &&
                !file.type.includes("png") &&
                file.type !== "application/octet-stream")
        ) {
            toast("Pasfoto GTK wajib berformat PNG (.png).", "warning");
            const input = document.getElementById("gtkFotoFileInput");
            if (input) input.value = "";
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            toast("Ukuran file pasfoto maksimal 5 MB.", "danger");
            const input = document.getElementById("gtkFotoFileInput");
            if (input) input.value = "";
            return;
        }

        const previewImg = document.getElementById("gtkFotoPreviewImg");
        const placeholder = document.getElementById("gtkFotoPreviewPlaceholder");
        const specsEl = document.getElementById("gtkFotoFileSpecs");

        const reader = new FileReader();
        reader.onload = function (e) {
            const dataUrl = e.target.result;
            const tempImg = new Image();
            tempImg.onload = function () {
                if (previewImg) {
                    previewImg.src = dataUrl;
                    previewImg.style.display = "block";
                }
                if (placeholder) placeholder.style.display = "none";
                if (specsEl) {
                    specsEl.style.display = "block";
                    specsEl.innerHTML =
                        '<span style="color:#10b981;"><i class="fas fa-file-circle-check me-1"></i> PNG Siap: ' +
                        formatBytes(file.size) + " (" + tempImg.width + " × " + tempImg.height + " px)</span>";
                }
            };
            tempImg.src = dataUrl;
        };
        reader.readAsDataURL(file);
    }

    window.handleGtkSubmitFoto = async function () {
        const fileInput = document.getElementById("gtkFotoFileInput");
        const btnSubmit = document.getElementById("btnGtkSubmitFoto");
        const m = modal();

        if (!selectedPtkId) {
            toast("Data GTK tidak valid.", "danger");
            return;
        }
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            toast("Silakan pilih berkas foto PNG terlebih dahulu.", "warning");
            return;
        }

        const formData = new FormData();
        formData.append("ptk_id", selectedPtkId);
        formData.append("foto", fileInput.files[0]);

        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengompresi & Menyimpan...';
        }

        try {
            const res = await fetch(m.dataset.uploadUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    Accept: "application/json",
                },
                body: formData,
            });
            const json = await res.json();

            if (json.status === "success") {
                const savings = json.data.savings ? " (Hemat " + json.data.savings + "%)" : "";
                toast("Foto " + (json.data.nama || "GTK") + " berhasil disimpan! Ukuran: " + json.data.foto_size + savings, "success");
                updateRowFotoUI(selectedPtkId, json.data.foto_url, json.data.foto_size);
                window.closeGtkFotoModal();
            } else {
                throw new Error(json.message || "Gagal mengunggah foto.");
            }
        } catch (err) {
            toast(err.message || "Terjadi kesalahan saat mengunggah foto.", "danger");
        } finally {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Simpan Pasfoto';
            }
        }
    };

    window.handleGtkDeleteFoto = async function () {
        if (!selectedPtkId) return;

        const ok = window.SAE && typeof window.SAE.confirm === "function"
            ? await window.SAE.confirm("Pasfoto GTK ini akan dihapus permanen.", "Hapus Pasfoto GTK?", "danger", "Ya, Hapus!", "Batal")
            : window.confirm("Hapus pasfoto GTK ini?");
        if (!ok) return;

        const btnDelete = document.getElementById("btnGtkDeleteFoto");
        if (btnDelete) {
            btnDelete.disabled = true;
            btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
        }

        try {
            const res = await fetch(modal().dataset.deleteUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ ptk_id: selectedPtkId }),
            });
            const json = await res.json();

            if (json.status === "success") {
                toast("Pasfoto GTK berhasil dihapus.", "success");
                updateRowFotoUI(selectedPtkId, "", "");
                window.closeGtkFotoModal();
            } else {
                throw new Error(json.message || "Gagal menghapus foto.");
            }
        } catch (err) {
            toast(err.message || "Terjadi kesalahan saat menghapus foto.", "danger");
        } finally {
            if (btnDelete) {
                btnDelete.disabled = false;
                btnDelete.innerHTML = '<i class="fas fa-trash-can me-1"></i> Hapus Pasfoto';
            }
        }
    };

    function updateRowFotoUI(ptkId, fotoUrl, fotoSize) {
        const thumb = document.getElementById("gtkFotoThumb_" + ptkId);
        if (!thumb) return;

        if (fotoUrl) {
            thumb.className = "pd-foto-thumb";
            thumb.style.background = "repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px";
            thumb.style.border = "1.5px solid var(--border-color)";
            thumb.style.color = "";
            thumb.title = "Klik untuk melihat / mengubah pasfoto GTK" + (fotoSize ? " (" + fotoSize + ")" : "");
            thumb.innerHTML = '<img src="' + fotoUrl + "?v=" + Date.now() + '" alt="Foto GTK" style="width:100%;height:100%;object-fit:cover;">';
        } else {
            thumb.className = "pd-foto-thumb empty";
            thumb.style.background = "rgba(99,102,241,0.08)";
            thumb.style.border = "1.5px dashed rgba(99,102,241,0.4)";
            thumb.style.color = "var(--primary)";
            thumb.title = "Klik untuk mengunggah pasfoto GTK (PNG)";
            thumb.innerHTML = '<i class="fas fa-camera" style="font-size:0.7rem;"></i>';
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const m = modal();
        if (!m) return;

        m.addEventListener("click", function (e) {
            if (e.target === m) window.closeGtkFotoModal();
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && m.style.display === "flex") window.closeGtkFotoModal();
        });

        const fileInput = document.getElementById("gtkFotoFileInput");
        if (fileInput) {
            fileInput.addEventListener("change", function (e) {
                previewFile(e.target.files[0]);
            });
        }

        const dropZone = document.getElementById("gtkFotoDropZone");
        if (dropZone) {
            ["dragenter", "dragover"].forEach(function (ev) {
                dropZone.addEventListener(ev, function (e) {
                    e.preventDefault();
                    dropZone.style.borderColor = "var(--primary)";
                    dropZone.style.background = "rgba(99,102,241,0.08)";
                });
            });
            ["dragleave", "drop"].forEach(function (ev) {
                dropZone.addEventListener(ev, function (e) {
                    e.preventDefault();
                    dropZone.style.borderColor = "rgba(99,102,241,0.4)";
                    dropZone.style.background = "rgba(255,255,255,0.01)";
                });
            });
            dropZone.addEventListener("drop", function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = "rgba(99,102,241,0.4)";
                dropZone.style.background = "rgba(255,255,255,0.01)";

                const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                if (file && fileInput) {
                    try {
                        fileInput.files = e.dataTransfer.files;
                    } catch (err) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        fileInput.files = dt.files;
                    }
                    previewFile(file);
                }
            });
        }

        const previewContainer = document.getElementById("gtkFotoPreviewContainer");
        if (previewContainer) {
            ["dragenter", "dragover"].forEach(function (ev) {
                previewContainer.addEventListener(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    previewContainer.style.borderColor = "#6366f1";
                    previewContainer.style.boxShadow = "0 0 16px rgba(99,102,241,0.45)";
                });
            });

            ["dragleave", "drop"].forEach(function (ev) {
                previewContainer.addEventListener(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    previewContainer.style.borderColor = "var(--border-color)";
                    previewContainer.style.boxShadow = "0 4px 16px rgba(0,0,0,0.35)";
                });
            });

            previewContainer.addEventListener("drop", function (e) {
                e.preventDefault();
                e.stopPropagation();
                previewContainer.style.borderColor = "var(--border-color)";
                previewContainer.style.boxShadow = "0 4px 16px rgba(0,0,0,0.35)";

                const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                if (file && fileInput) {
                    try {
                        fileInput.files = e.dataTransfer.files;
                    } catch (err) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        fileInput.files = dt.files;
                    }
                    previewFile(file);
                }
            });
        }
    });
})();
