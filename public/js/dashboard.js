/**
 * SAE - Dashboard Script (Sidebar Mobile Toggle & Accordion Submenus)
 */

document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("dashSidebar");
    const toggleBtn = document.getElementById("dashToggleBtn");
    const backdrop = document.getElementById("dashBackdrop");

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("open");
            backdrop.classList.toggle("open");
        });

        backdrop.addEventListener("click", () => {
            sidebar.classList.remove("open");
            backdrop.classList.remove("open");
        });
    }

    // Sidebar Submenu Accordion Handler (Level 1 & Nested Level 2)
    document.querySelectorAll(".dash-nav-toggle").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            const group = this.closest(".dash-nav-group");
            if (group) {
                const isOpen = group.classList.contains("open");
                group.classList.toggle("open", !isOpen);
            }
        });
    });

    document.querySelectorAll(".dash-nav-nested-toggle").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const nestedGroup = this.closest(".dash-nav-nested-group");
            if (nestedGroup) {
                const isOpen = nestedGroup.classList.contains("open");
                nestedGroup.classList.toggle("open", !isOpen);
            }
        });
    });

    // Auto-scroll Active Segmented Tab into view on Mobile
    const tabContainers = document.querySelectorAll(
        ".dash-tabs-nav, .dash-segmented-tabs, .dash-tabs, [style*='overflow-x: auto']",
    );
    tabContainers.forEach((container) => {
        const activeTab = container.querySelector(
            ".btn-primary, .active, .tab-btn.active, [aria-selected='true']",
        );
        if (activeTab) {
            setTimeout(() => {
                const containerRect = container.getBoundingClientRect();
                const activeRect = activeTab.getBoundingClientRect();
                if (
                    activeRect.left < containerRect.left ||
                    activeRect.right > containerRect.right
                ) {
                    activeTab.scrollIntoView({
                        behavior: "smooth",
                        block: "nearest",
                        inline: "center",
                    });
                }
            }, 100);
        }
    });

    // Custom Styled Dropdown Component Toggle & Outside Click
    document.querySelectorAll(".custom-dropdown-trigger").forEach((trigger) => {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = this.closest(".dash-custom-dropdown");
            if (!dropdown) return;
            const isOpen = dropdown.classList.contains("open");
            document
                .querySelectorAll(".dash-custom-dropdown.open")
                .forEach((d) => {
                    if (d !== dropdown) {
                        d.classList.remove("open");
                        const otherTrig = d.querySelector(
                            ".custom-dropdown-trigger",
                        );
                        if (otherTrig)
                            otherTrig.setAttribute("aria-expanded", "false");
                    }
                });
            dropdown.classList.toggle("open", !isOpen);
            this.setAttribute("aria-expanded", String(!isOpen));
        });
    });

    document.addEventListener("click", (e) => {
        if (!e.target.closest(".dash-custom-dropdown")) {
            document
                .querySelectorAll(".dash-custom-dropdown.open")
                .forEach((d) => {
                    d.classList.remove("open");
                    const trig = d.querySelector(".custom-dropdown-trigger");
                    if (trig) trig.setAttribute("aria-expanded", "false");
                });
        }
    });

    // Global Modal Backdrop Close & ESC Handler
    document.querySelectorAll(".modal-backdrop").forEach((modal) => {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-backdrop").forEach((modal) => {
                modal.style.display = "none";
            });
        }
    });
});

/**
 * Global HTML escaping helper
 */
window.escapeHtml = function (str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
};
