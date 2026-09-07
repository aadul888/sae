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

    // Sidebar Submenu Accordion Handler
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
});
