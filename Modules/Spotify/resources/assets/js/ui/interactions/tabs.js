export function setupTabs(onTabSwitch) {
    const tabBar = document.getElementById('tab-bar');
    if (!tabBar) return;

    tabBar.querySelectorAll('.spotify-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tab;

            // Update tab styles
            tabBar.querySelectorAll('.spotify-tab').forEach(t => {
                t.className = t === tab
                    ? 'spotify-tab tab-active text-sm font-semibold px-3 min-h-[34px]'
                    : 'spotify-tab tab-inactive text-sm font-semibold px-3 min-h-[34px]';
            });

            // Show/hide panels
            document.querySelectorAll('.spotify-panel').forEach(panel => {
                panel.classList.toggle('hidden', panel.id !== targetId);
            });

            if (onTabSwitch) {
                onTabSwitch(targetId);
            }
        });
    });
}
