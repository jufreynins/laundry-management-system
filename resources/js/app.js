import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('order-items-container');
    if (!container) return;

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
});
