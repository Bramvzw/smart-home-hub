import { bootSidebar } from '../../resources/js/sidebar.js';

function render(state) {
    document.body.innerHTML = `
        <button id="sidebar-toggle"></button>
        <button id="sidebar-hide"></button>
        <button id="sidebar-fab"></button>
    `;
    document.body.dataset.sidebar = state;
}

function cookieState() {
    const match = document.cookie.match(/sidebar_state=([^;]+)/);

    return match ? match[1] : null;
}

describe('dashboard sidebar', () => {
    beforeEach(() => {
        document.cookie = 'sidebar_state=;max-age=0;path=/';
    });

    it('toggles between expanded and rail and persists the state in a cookie', () => {
        render('expanded');
        bootSidebar();

        document.getElementById('sidebar-toggle').click();
        expect(document.body.dataset.sidebar).toBe('rail');
        expect(cookieState()).toBe('rail');

        document.getElementById('sidebar-toggle').click();
        expect(document.body.dataset.sidebar).toBe('expanded');
        expect(cookieState()).toBe('expanded');
    });

    it('hides the sidebar and restores it from the floating button', () => {
        render('rail');
        bootSidebar();

        document.getElementById('sidebar-hide').click();
        expect(document.body.dataset.sidebar).toBe('hidden');

        document.getElementById('sidebar-fab').click();
        expect(document.body.dataset.sidebar).toBe('expanded');
    });

    it('wires each control only once across repeated boots', () => {
        render('expanded');
        bootSidebar();
        bootSidebar(); // e.g. a livewire:navigated re-fire

        // A double-wired toggle would run two transitions per click (expanded -> rail -> expanded).
        document.getElementById('sidebar-toggle').click();
        expect(document.body.dataset.sidebar).toBe('rail');
    });
});
