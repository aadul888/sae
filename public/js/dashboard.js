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
});
