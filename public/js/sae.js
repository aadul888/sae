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

    // NISN Check Live API Handler
    const nisnForm = document.getElementById("nisnCheckForm");
    const nisnInput = document.getElementById("nisnInput");
    const nisnResult = document.getElementById("nisnResult");

    if (nisnForm && nisnInput && nisnResult) {
        nisnForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const nisn = nisnInput.value.trim();
            if (!nisn) return;

            nisnResult.style.display = "block";
            nisnResult.style.background = "rgba(59, 130, 246, 0.1)";
            nisnResult.style.borderColor = "rgba(59, 130, 246, 0.3)";
            nisnResult.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin me-2"></i> Mencari data...';

            try {
                const csrfToken =
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "";
                const response = await fetch("/api/check-nisn", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ nisn }),
                });

                const data = await response.json();

                if (data.status === "success") {
                    nisnResult.style.background = "rgba(16, 185, 129, 0.1)";
                    nisnResult.style.borderColor = "rgba(16, 185, 129, 0.3)";
                    nisnResult.innerHTML = `
                        <div style="font-weight: 700; color: #10b981; margin-bottom: 4px;">
                            <i class="fa-solid fa-circle-check me-1"></i> ${data.message}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-main);"><strong>Nama:</strong> ${data.data.nama}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><strong>Kelas:</strong> ${data.data.kelas}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><strong>Status:</strong> <span style="color:#10b981;">${data.data.status}</span></div>
                    `;
                } else {
                    nisnResult.style.background = "rgba(239, 68, 68, 0.1)";
                    nisnResult.style.borderColor = "rgba(239, 68, 68, 0.3)";
                    nisnResult.innerHTML = `
                        <div style="font-weight: 700; color: #ef4444; margin-bottom: 4px;">
                            <i class="fa-solid fa-circle-xmark me-1"></i> ${data.message}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Pastikan nomor NISN yang dimasukkan sudah benar.</div>
                    `;
                }
            } catch (err) {
                nisnResult.style.background = "rgba(239, 68, 68, 0.1)";
                nisnResult.style.borderColor = "rgba(239, 68, 68, 0.3)";
                nisnResult.innerHTML =
                    '<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Terjadi kesalahan koneksi.</span>';
            }
        });
    }
});

// Global UI Alert, Confirm & Toast Helper
window.SAE = {
    toast(message, type = "info", duration = 3000) {
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
            warning: "fa-triangle-exclamation text-warning",
            info: "fa-circle-info text-primary",
        };

        const toast = document.createElement("div");
        toast.className = "sae-toast";
        toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> <span>${message}</span>`;
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
                        <button class="btn btn-primary sae-dialog-ok" style="padding: 8px 20px; font-size: 0.88rem;">OK</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add("active"));

            const okBtn = overlay.querySelector(".sae-dialog-ok");
            okBtn.focus();
            okBtn.onclick = () => {
                overlay.classList.remove("active");
                setTimeout(() => {
                    overlay.remove();
                    resolve(true);
                }, 200);
            };
        });
    },

    confirm(message, title = "Konfirmasi Tindakan", type = "warning") {
        return new Promise((resolve) => {
            const icons = {
                success: "fa-circle-check",
                danger: "fa-triangle-exclamation",
                warning: "fa-triangle-exclamation",
                info: "fa-circle-info",
            };

            const overlay = document.createElement("div");
            overlay.className = "sae-dialog-overlay";
            overlay.innerHTML = `
                <div class="sae-dialog-box">
                    <div class="sae-dialog-icon ${type}">
                        <i class="fa-solid ${icons[type] || icons.warning}"></i>
                    </div>
                    <div class="sae-dialog-title">${title}</div>
                    <div class="sae-dialog-message">${message}</div>
                    <div class="sae-dialog-actions">
                        <button class="btn btn-outline sae-dialog-cancel" style="padding: 8px 16px; font-size: 0.88rem;">Batal</button>
                        <button class="btn btn-primary sae-dialog-ok" style="padding: 8px 18px; font-size: 0.88rem;">Lanjutkan</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add("active"));

            const cancelBtn = overlay.querySelector(".sae-dialog-cancel");
            const okBtn = overlay.querySelector(".sae-dialog-ok");
            okBtn.focus();

            const close = (res) => {
                overlay.classList.remove("active");
                setTimeout(() => {
                    overlay.remove();
                    resolve(res);
                }, 200);
            };

            cancelBtn.onclick = () => close(false);
            okBtn.onclick = () => close(true);
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
        if (!confirmMsg || form.dataset.confirmed === "true") return;

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
