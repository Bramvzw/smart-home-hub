// Calendar module: toggle between the list and week view.
const init = () => {
    const root = document.querySelector('[data-calendar]');
    if (! root) {
        return;
    }

    // Guard against re-initialisation after wire:navigate (listeners persist
    // across body swaps; the same root may be booted again).
    if (root.dataset.calendarBooted === 'true') {
        return;
    }
    root.dataset.calendarBooted = 'true';

    const toggles = root.querySelectorAll('[data-view-toggle]');
    const list = root.querySelector('[data-calendar-list]');
    const week = root.querySelector('[data-calendar-week]');

    if (! toggles.length || ! list || ! week) {
        return;
    }

    const select = (view) => {
        const showWeek = view === 'week';
        week.hidden = ! showWeek;
        list.hidden = showWeek;
        toggles.forEach((button) => {
            button.setAttribute('aria-selected', String(button.dataset.viewToggle === view));
        });
    };

    toggles.forEach((button) => {
        button.addEventListener('click', () => select(button.dataset.viewToggle));
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
    init();
}
document.addEventListener('livewire:navigated', init);
