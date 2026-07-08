import Sortable from 'sortablejs';

/**
 * Write each row's position back into its hidden order input, so the form
 * submits the visual order. Kept pure and exported for testing.
 */
export function syncOrderInputs(container) {
    container.querySelectorAll('[data-module-row]').forEach((row, index) => {
        const input = row.querySelector('input[data-order-input]');
        if (input) {
            input.value = String(index);
        }
    });
}

function bind(container) {
    syncOrderInputs(container);

    Sortable.create(container, {
        animation: 160,
        draggable: '[data-module-row]',
        handle: '.module-grip',
        ghostClass: 'drag-ghost',
        onEnd: () => syncOrderInputs(container),
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-module-order]').forEach(bind);
});
