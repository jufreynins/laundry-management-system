import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('order-items-container');
    if (container) {
        const template = document.getElementById('order-item-template');
        const addButton = document.getElementById('add-order-item');

        addButton?.addEventListener('click', () => {
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
        });

        container.addEventListener('click', (event) => {
            if (event.target.matches('.remove-order-item')) {
                event.target.closest('.order-item-row').remove();
            }
        });
    }

    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggle && sidebar && sidebarBackdrop) {
        const openSidebar = () => {
            sidebar.classList.add('open');
            sidebarBackdrop.hidden = false;
            document.body.classList.add('sidebar-open');
            sidebarToggle.setAttribute('aria-expanded', 'true');
            sidebarToggle.setAttribute('aria-label', 'Close menu');
        };

        const closeSidebar = () => {
            sidebar.classList.remove('open');
            sidebarBackdrop.hidden = true;
            document.body.classList.remove('sidebar-open');
            sidebarToggle.setAttribute('aria-expanded', 'false');
            sidebarToggle.setAttribute('aria-label', 'Open menu');
        };

        sidebarToggle.addEventListener('click', () => {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        sidebarBackdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
    }
});
