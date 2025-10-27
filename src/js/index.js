import "./modules/raffle-frontend/index.js";

document.addEventListener("DOMContentLoaded", function () {
    // Toggle menú móvil
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton && mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('active');
    });

    // Toggle submenús móviles
    const mobileSubmenuToggles = document.querySelectorAll('.mobile-submenu-toggle');

    mobileSubmenuToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const submenu = toggle.nextElementSibling;
            const icon = toggle.querySelector('svg');

            submenu.classList.toggle('active');
            icon.classList.toggle('rotate-180');
        });
    });
});