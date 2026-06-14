// Calendar module: toggle between the list and week view.
const init = () => {
    const root = document.querySelector('[data-calendar]');
    if (! root) {
        return;
    }

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
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
