document.addEventListener("DOMContentLoaded", function () {
    const sidebarToggleBtn = document.querySelector(".toggle-sidebar-btn");
    const body = document.body;

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener("click", function () {
            body.classList.toggle("toggled");
        });
    }

    // Dropdown menu functionality
    const dropdowns = document.querySelectorAll(".sidebar-dropdown > a");
    dropdowns.forEach((dropdown) => {
        dropdown.addEventListener("click", function (e) {
            e.preventDefault();
            const parent = this.parentElement;

            // Close other open dropdowns (accordion style) - Optional
            // document.querySelectorAll('.sidebar-dropdown').forEach(item => {
            //     if (item !== parent) item.classList.remove('active');
            // });

            parent.classList.toggle("active");
        });
    });
});
