document.addEventListener("DOMContentLoaded", () => {
    // Theme Switcher (Hanya elemen ID themeToggleBtn)
    const themeToggleBtns = document.querySelectorAll("#themeToggleBtn");
    const logos = document.querySelectorAll(
        "#navLogo, #dashLogo, #loginLogo, #installLogo",
    );
    const htmlElement = document.documentElement;

    const getLogoSrc = (mode, el) => {
        if (window.__SAE_LOGOS__ && window.__SAE_LOGOS__[mode]) {
            return window.__SAE_LOGOS__[mode];
        }
        return mode === "light" ? el.dataset.light : el.dataset.dark;
    };

    const applyTheme = (theme) => {
        if (theme === "light") {
            htmlElement.setAttribute("data-theme", "light");
            themeToggleBtns.forEach((btn) => {
                btn.innerHTML =
                    '<i class="fa-solid fa-sun" style="color: #f59e0b;"></i>';
            });
            logos.forEach((logo) => {
                const src = getLogoSrc("light", logo);
                if (src) logo.src = src;
            });
        } else {
            htmlElement.removeAttribute("data-theme");
            themeToggleBtns.forEach((btn) => {
                btn.innerHTML = '<i class="fa-solid fa-moon"></i>';
            });
            logos.forEach((logo) => {
                const src = getLogoSrc("dark", logo);
                if (src) logo.src = src;
            });
        }
        localStorage.setItem("sae_theme", theme);
    };

    // Init theme from storage or system preference
    const savedTheme =
        localStorage.getItem("sae_theme") ||
        (window.matchMedia("(prefers-color-scheme: light)").matches
            ? "light"
            : "dark");
    applyTheme(savedTheme);

    themeToggleBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            const currentTheme =
                htmlElement.getAttribute("data-theme") === "light"
                    ? "light"
                    : "dark";
            const nextTheme = currentTheme === "light" ? "dark" : "light";
            applyTheme(nextTheme);
        });
    });

    // Public Mobile Navigation Toggle
    const navToggle = document.getElementById("navToggle");
    const navLinks = document.getElementById("navLinks");

    if (navToggle && navLinks) {
        navToggle.addEventListener("click", () => {
            navLinks.classList.toggle("active");
        });
    }

    // NISN Check Live API Handler -> Validasi Ketat: Hanya Angka, Tepat 10 Digit
    const nisnForm = document.getElementById("nisnCheckForm");
    const nisnInput = document.getElementById("nisnInput");
    const nisnBadge = document.getElementById("nisnCounterBadge");
    const nisnStatusIcon = document.getElementById("nisnStatusIcon");
    const nisnErrorMsg = document.getElementById("nisnErrorMsg");

    if (nisnForm && nisnInput) {
        const updateNisnValidationUI = (isSubmitAttempt = false) => {
            const rawVal = nisnInput.value;
            const cleanVal = rawVal.replace(/\D/g, "").slice(0, 10);
            if (rawVal !== cleanVal) {
                nisnInput.value = cleanVal;
            }

            const len = cleanVal.length;
            if (nisnBadge) {
                nisnBadge.textContent = `${len} / 10 digit`;
                if (len === 10) {
                    nisnBadge.style.color = "#10b981";
                } else if (len > 0) {
                    nisnBadge.style.color = "var(--primary, #3b82f6)";
                } else {
                    nisnBadge.style.color = "var(--text-muted, #94a3b8)";
                }
            }

            if (len === 10) {
                nisnInput.style.borderColor = "#10b981";
                nisnInput.style.boxShadow = "0 0 0 3px rgba(16, 185, 129, 0.15)";
                if (nisnStatusIcon) {
                    nisnStatusIcon.style.display = "block";
                    nisnStatusIcon.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i>';
                }
                if (nisnErrorMsg) nisnErrorMsg.style.display = "none";
            } else {
                if (nisnStatusIcon) nisnStatusIcon.style.display = "none";
                if (isSubmitAttempt) {
                    nisnInput.style.borderColor = "#ef4444";
                    nisnInput.style.boxShadow = "0 0 0 3px rgba(239, 68, 68, 0.15)";
                    if (nisnErrorMsg) {
                        nisnErrorMsg.style.display = "flex";
                        nisnErrorMsg.querySelector("span").textContent =
                            len === 0
                                ? "NISN wajib diisi (tepat 10 digit angka)."
                                : `NISN kurang ${10 - len} digit (harus pas 10 digit angka).`;
                    }
                } else {
                    nisnInput.style.borderColor = "";
                    nisnInput.style.boxShadow = "";
                    if (nisnErrorMsg) nisnErrorMsg.style.display = "none";
                }
            }
            return len === 10;
        };

        // Cegah input selain angka pada keypress
        nisnInput.addEventListener("keydown", (e) => {
            const allowedKeys = [
                "Backspace", "Delete", "Tab", "Escape", "Enter",
                "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown",
                "Home", "End"
            ];
            // Allow ctrl/cmd + A, C, V, X
            if (e.ctrlKey || e.metaKey || allowedKeys.includes(e.key)) {
                return;
            }
            // Jika bukan angka 0-9, blokir tombol
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault();
                if (nisnErrorMsg) {
                    nisnErrorMsg.style.display = "flex";
                    nisnErrorMsg.querySelector("span").textContent = "Hanya karakter angka (0-9) yang diizinkan.";
                }
            }
        });

        // Filter paste dan sanitasi input
        nisnInput.addEventListener("input", () => {
            updateNisnValidationUI(false);
        });

        nisnInput.addEventListener("paste", (e) => {
            setTimeout(() => {
                updateNisnValidationUI(false);
            }, 0);
        });

        // Validasi ketat saat form di-submit
        nisnForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const isValid = updateNisnValidationUI(true);
            if (!isValid) {
                nisnInput.focus();
                return;
            }
            const cleanNisn = nisnInput.value.replace(/\D/g, "").slice(0, 10);
            window.location.href = `/v/${encodeURIComponent(cleanNisn)}`;
        });
    }
});

// Global UI Alert, Confirm & Toast Helper
window.SAE = {
    toast(message, type = "info", duration = 3200) {
        let container = document.getElementById("saeToastContainer");
        if (!container) {
            container = document.createElement("div");
            container.id = "saeToastContainer";
            container.className = "sae-toast-container";
            document.body.appendChild(container);
        }

        const icons = {
            success: "fa-circle-check text-success",
            danger: "fa-circle-xmark text-danger",
            error: "fa-circle-xmark text-danger",
            warning: "fa-triangle-exclamation text-warning",
            info: "fa-circle-info text-primary",
        };

        const toast = document.createElement("div");
        toast.className = "sae-toast";
        toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}" style="font-size: 1.1rem; flex-shrink: 0;"></i> <span style="line-height: 1.4;">${message}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add("show"));

        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    alert(message, title = "Informasi", type = "info") {
        return new Promise((resolve) => {
            const icons = {
                success: "fa-circle-check",
                danger: "fa-triangle-exclamation",
                error: "fa-triangle-exclamation",
                warning: "fa-triangle-exclamation",
                info: "fa-circle-info",
            };

            const overlay = document.createElement("div");
            overlay.className = "sae-dialog-overlay";
            overlay.innerHTML = `
                <div class="sae-dialog-box">
                    <div class="sae-dialog-icon ${type}">
                        <i class="fa-solid ${icons[type] || icons.info}"></i>
                    </div>
                    <div class="sae-dialog-title">${title}</div>
                    <div class="sae-dialog-message">${message}</div>
                    <div class="sae-dialog-actions">
                        <button class="btn btn-primary sae-dialog-ok" style="padding: 8px 22px; font-size: 0.88rem;">OK</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add("active"));

            const okBtn = overlay.querySelector(".sae-dialog-ok");
            if (okBtn) okBtn.focus();
            const close = () => {
                overlay.classList.remove("active");
                setTimeout(() => {
                    overlay.remove();
                    resolve(true);
                }, 200);
            };
            if (okBtn) okBtn.onclick = close;
            overlay.onclick = (e) => {
                if (e.target === overlay) close();
            };
        });
    },

    confirm(message, title = "Konfirmasi Tindakan", type = "warning", confirmText = "Lanjutkan", cancelText = "Batal") {
        return new Promise((resolve) => {
            const icons = {
                success: "fa-circle-check",
                danger: "fa-triangle-exclamation",
                error: "fa-triangle-exclamation",
                warning: "fa-triangle-exclamation",
                info: "fa-circle-info",
            };

            const isDanger = type === "danger" || type === "error";
            const overlay = document.createElement("div");
            overlay.className = "sae-dialog-overlay";
            overlay.innerHTML = `
                <div class="sae-dialog-box">
                    <div class="sae-dialog-icon ${isDanger ? 'danger' : type}">
                        <i class="fa-solid ${icons[type] || icons.warning}"></i>
                    </div>
                    <div class="sae-dialog-title">${title}</div>
                    <div class="sae-dialog-message">${message}</div>
                    <div class="sae-dialog-actions">
                        <button class="btn btn-outline sae-dialog-cancel" style="padding: 8px 16px; font-size: 0.88rem;">${cancelText}</button>
                        <button class="btn ${isDanger ? 'btn-danger' : 'btn-primary'} sae-dialog-ok" style="padding: 8px 18px; font-size: 0.88rem; ${isDanger ? 'background: #ef4444; border-color: #ef4444; color: #fff;' : ''}">${confirmText}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add("active"));

            const cancelBtn = overlay.querySelector(".sae-dialog-cancel");
            const okBtn = overlay.querySelector(".sae-dialog-ok");
            if (okBtn) okBtn.focus();

            const close = (res) => {
                overlay.classList.remove("active");
                setTimeout(() => {
                    overlay.remove();
                    resolve(res);
                }, 200);
            };

            if (cancelBtn) cancelBtn.onclick = () => close(false);
            if (okBtn) okBtn.onclick = () => close(true);
            overlay.onclick = (e) => {
                if (e.target === overlay) close(false);
            };
        });
    },

    async copy(text, label = "Teks") {
        if (!text) return false;
        let success = false;

        // 1. Coba Modern Clipboard API jika secure context
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                success = true;
            } catch (err) {
                success = false;
            }
        }

        // 2. Fallback textarea + execCommand untuk HTTP / non-HTTPS domain
        if (!success) {
            try {
                const tempTextArea = document.createElement("textarea");
                tempTextArea.value = text;
                tempTextArea.style.position = "fixed";
                tempTextArea.style.left = "-9999px";
                tempTextArea.style.top = "0";
                tempTextArea.setAttribute("readonly", "");
                document.body.appendChild(tempTextArea);
                tempTextArea.focus();
                tempTextArea.select();
                tempTextArea.setSelectionRange(0, 99999);
                success = document.execCommand("copy");
                document.body.removeChild(tempTextArea);
            } catch (err) {
                success = false;
            }
        }

        if (success) {
            this.toast(`${label} berhasil disalin ke clipboard!`, "success");
        } else {
            this.toast("Gagal menyalin teks ke clipboard.", "danger");
        }

        return success;
    },
};

// Universal SweetAlert2 Shim: Mengarahkan seluruh panggilan Swal.fire ke style tunggal SAE secara konsisten
window.Swal = {
    fire: function (titleOrOpts, message, icon) {
        let opts = {};
        if (typeof titleOrOpts === "string") {
            opts = { title: titleOrOpts, text: message, icon: icon };
        } else if (typeof titleOrOpts === "object" && titleOrOpts !== null) {
            opts = titleOrOpts;
        }

        const isConfirm = Boolean(opts.showCancelButton);
        const rawMsg = opts.html || opts.text || opts.title || "";
        const title = opts.title || "Pemberitahuan";
        const iconType = (opts.icon === "error" ? "danger" : opts.icon) || "info";

        if (isConfirm) {
            const confirmText = opts.confirmButtonText ? String(opts.confirmButtonText).replace(/<[^>]*>?/gm, "").trim() : "Lanjutkan";
            const cancelText = opts.cancelButtonText ? String(opts.cancelButtonText).replace(/<[^>]*>?/gm, "").trim() : "Batal";
            return window.SAE.confirm(rawMsg, title, iconType, confirmText, cancelText).then((isConfirmed) => ({
                isConfirmed: isConfirmed,
                isDismissed: !isConfirmed,
            }));
        } else if (opts.toast || iconType === "success" || iconType === "info" || iconType === "warning" || iconType === "danger") {
            // Seluruh alert status (sukses/info/peringatan) menggunakan SAE Toast modern di pojok atas
            const toastMsg = opts.title && opts.text ? `${opts.title} - ${opts.text}` : (opts.text || opts.title || rawMsg);
            const cleanToastMsg = typeof toastMsg === "string" ? toastMsg.replace(/<[^>]*>?/gm, "").trim() : toastMsg;
            window.SAE.toast(cleanToastMsg, iconType);
            return Promise.resolve({ isConfirmed: true });
        } else {
            return window.SAE.alert(rawMsg, title, iconType).then(() => ({ isConfirmed: true }));
        }
    },
    close: function () {},
    showLoading: function () {},
};

// Global Automatic Listeners (Auto Flash Messages, Auto Confirm, Auto Copy)
document.addEventListener("DOMContentLoaded", () => {
    // 1. Auto Flash Messages dari Laravel Session
    const flashEl = document.getElementById("saeFlashMessages");
    if (flashEl) {
        try {
            const flashes = JSON.parse(flashEl.dataset.messages || "[]");
            flashes.forEach((item) => {
                window.SAE.toast(item.message, item.type || "info");
            });
        } catch (e) {}
    }

    // 2. Global Event Delegation untuk data-copy
    document.addEventListener("click", (e) => {
        const copyBtn = e.target.closest("[data-copy]");
        if (!copyBtn) return;
        e.preventDefault();

        const targetSelector = copyBtn.getAttribute("data-copy");
        const label = copyBtn.getAttribute("data-copy-label") || "Teks";
        const targetEl = document.querySelector(targetSelector);
        if (targetEl) {
            const val = targetEl.value || targetEl.innerText;
            window.SAE.copy(val, label);
        }
    });

    // 3. Global Event Delegation untuk data-confirm pada form submit / link click
    document.addEventListener("submit", async (e) => {
        const form = e.target;
        const confirmMsg = form.getAttribute("data-confirm");
        // Lewatkan jika ditangani handler khusus (misal pengguna.js: delete/reset)
        if (
            !confirmMsg ||
            form.dataset.confirmed === "true" ||
            confirmMsg === "delete" ||
            confirmMsg === "reset"
        )
            return;

        e.preventDefault();
        const title =
            form.getAttribute("data-confirm-title") || "Konfirmasi Tindakan";
        const confirmed = await window.SAE.confirm(
            confirmMsg,
            title,
            "warning",
        );
        if (confirmed) {
            form.dataset.confirmed = "true";
            form.submit();
        }
    });

    document.addEventListener("click", async (e) => {
        const link = e.target.closest("a[data-confirm]");
        if (!link || link.dataset.confirmed === "true") return;

        e.preventDefault();
        const msg = link.getAttribute("data-confirm");
        const title =
            link.getAttribute("data-confirm-title") || "Konfirmasi Tindakan";
        const confirmed = await window.SAE.confirm(msg, title, "warning");
        if (confirmed) {
            link.dataset.confirmed = "true";
            window.location.href = link.href;
        }
    });
});
