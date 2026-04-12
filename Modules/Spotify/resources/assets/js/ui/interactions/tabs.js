export function setupTabs(onTabSwitch) {
    const tabBar = document.getElementById('tab-bar');
    if (!tabBar) return;

    tabBar.querySelectorAll('.spotify-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tab;

            // Update tab styles
            tabBar.querySelectorAll('.spotify-tab').forEach(t => {
                t.className = t === tab
                    ? 'spotify-tab tab-active text-sm font-medium pb-2 px-1 min-h-[36px]'
                    : 'spotify-tab tab-inactive text-sm font-medium pb-2 px-1 min-h-[36px]';
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
