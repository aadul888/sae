/**
 * SAE - Dashboard Script (Sidebar Mobile Toggle)
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
});
