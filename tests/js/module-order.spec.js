import { syncOrderInputs } from '../../resources/js/module-order.js';

const row = (slug, order) => `
    <div data-module-row data-slug="${slug}">
        <input type="hidden" data-order-input name="modules[${slug}][order]" value="${order}" />
    </div>`;

const orderOf = (container, slug) =>
    container.querySelector(`[data-slug="${slug}"] input[data-order-input]`).value;

describe('module order syncing', () => {
    beforeEach(() => {
        document.body.innerHTML = `<div data-module-order>${row('briefing', 5)}${row('weather', 2)}${row('calendar', 9)}</div>`;
    });

    test('rewrites order inputs to match the current DOM position', () => {
        const container = document.querySelector('[data-module-order]');

        syncOrderInputs(container);

        expect(orderOf(container, 'briefing')).toBe('0');
        expect(orderOf(container, 'weather')).toBe('1');
        expect(orderOf(container, 'calendar')).toBe('2');
    });

    test('reflects a reorder after rows are moved', () => {
        const container = document.querySelector('[data-module-order]');
        // Simulate a drag: move weather to the front.
        container.prepend(container.querySelector('[data-slug="weather"]'));

        syncOrderInputs(container);

        expect(orderOf(container, 'weather')).toBe('0');
        expect(orderOf(container, 'briefing')).toBe('1');
        expect(orderOf(container, 'calendar')).toBe('2');
    });
});
