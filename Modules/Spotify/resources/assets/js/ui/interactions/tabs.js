export function setupTabs(onTabSwitch) {
    const tabBar = document.getElementById('tab-bar');
    if (!tabBar) return;

    const activateTab = (targetId) => {
        tabBar.querySelectorAll('.spotify-tab').forEach(tab => {
            const active = tab.dataset.tab === targetId;
            tab.classList.toggle('is-active', active);
            tab.classList.toggle('tab-active', active);
            tab.classList.toggle('tab-inactive', !active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        document.querySelectorAll('.spotify-panel').forEach(panel => {
            panel.classList.toggle('hidden', panel.id !== targetId);
        });

        if (onTabSwitch) {
            onTabSwitch(targetId);
        }
    };

    tabBar.querySelectorAll('.spotify-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            activateTab(tab.dataset.tab);
        });
    });

    document.querySelectorAll('[data-tab-jump]').forEach(trigger => {
        trigger.addEventListener('click', () => activateTab(trigger.dataset.tabJump));
    });
}
