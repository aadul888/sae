document.addEventListener("DOMContentLoaded", () => {
    // Theme Switcher (Hanya elemen ID themeToggleBtn)
    const themeToggleBtns = document.querySelectorAll("#themeToggleBtn");
    const logos = document.querySelectorAll("#navLogo, #dashLogo");
    const htmlElement = document.documentElement;

    const applyTheme = (theme) => {
        if (theme === "light") {
            htmlElement.setAttribute("data-theme", "light");
            themeToggleBtns.forEach((btn) => {
                btn.innerHTML =
                    '<i class="fa-solid fa-sun" style="color: #f59e0b;"></i>';
            });
            logos.forEach((logo) => {
                if (logo.dataset.light) logo.src = logo.dataset.light;
            });
        } else {
            htmlElement.removeAttribute("data-theme");
            themeToggleBtns.forEach((btn) => {
                btn.innerHTML = '<i class="fa-solid fa-moon"></i>';
            });
            logos.forEach((logo) => {
                if (logo.dataset.dark) logo.src = logo.dataset.dark;
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
