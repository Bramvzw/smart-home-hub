import { postOptions, showErrorMessage } from '../../utils/index.js';

// Timestamp of the last shuffle toggle — used by player-renderer to skip
// poll-driven shuffle sync for 2 seconds after a manual toggle.
export let lastShuffleToggleAt = 0;

export function setupShuffle(elements, onSuccess) {
    const btn = elements.shuffleBtn;
    if (!btn) return;

    btn.addEventListener('click', () => {
        if (btn.dataset.shuffleDisallowed === 'true') return;
        const isActive = btn.dataset.shuffleState === 'true';
        const newState = !isActive;

        // Immediate optimistic update
        lastShuffleToggleAt = Date.now();
        updateShuffleUI(elements, newState);

        const options = postOptions(elements.csrfToken);
        options.body = JSON.stringify({ state: newState });

        fetch('/spotify/shuffle', options)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    lastShuffleToggleAt = 0;
                    updateShuffleUI(elements, isActive);
                    showErrorMessage(elements, data.message || 'Failed to toggle shuffle');
                } else if (onSuccess) {
                    onSuccess(newState);
                }
            })
            .catch(() => {
                lastShuffleToggleAt = 0;
                updateShuffleUI(elements, isActive);
                showErrorMessage(elements, 'Failed to toggle shuffle');
            });
    });
}

export function updateShuffleUI(elements, active, disallowed = false) {
    const btn = elements.shuffleBtn;
    if (!btn) return;

    btn.dataset.shuffleState = active ? 'true' : 'false';
    btn.dataset.shuffleDisallowed = disallowed ? 'true' : 'false';
    btn.style.opacity = disallowed ? '0.3' : '';
    btn.style.cursor  = disallowed ? 'not-allowed' : '';

    if (active) {
        btn.classList.remove('text-gray-600');
        btn.classList.add('text-green-400');
    } else {
        btn.classList.remove('text-green-400');
        btn.classList.add('text-gray-600');
    }
}
