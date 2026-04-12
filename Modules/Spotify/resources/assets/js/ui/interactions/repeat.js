import { postOptions, showErrorMessage } from '../../utils/index.js';

const REPEAT_CYCLE = ['off', 'context', 'track'];

export function setupRepeat(elements, onRepeatChanged) {
    const btn = elements.repeatBtn;
    if (!btn) return;

    btn.addEventListener('click', () => {
        const current = btn.dataset.repeatState || 'off';
        const nextIndex = (REPEAT_CYCLE.indexOf(current) + 1) % REPEAT_CYCLE.length;
        const next = REPEAT_CYCLE[nextIndex];

        // Optimistic UI update
        updateRepeatUI(elements, next);

        const options = postOptions(elements.csrfToken);
        options.body = JSON.stringify({ state: next });

        fetch('/spotify/repeat', options)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (onRepeatChanged) {
                        onRepeatChanged(next);
                    }
                } else {
                    updateRepeatUI(elements, current);
                    showErrorMessage(elements, data.message || 'Failed to set repeat mode');
                }
            })
            .catch(() => {
                updateRepeatUI(elements, current);
                showErrorMessage(elements, 'Failed to set repeat mode');
            });
    });
}

export function updateRepeatUI(elements, state) {
    const btn = elements.repeatBtn;
    const dot = elements.repeatDot;
    const icon = elements.repeatIcon;
    if (!btn) return;

    btn.dataset.repeatState = state;

    if (state === 'off') {
        btn.classList.remove('text-green-400');
        btn.classList.add('text-gray-600');
        if (dot) dot.classList.add('hidden');
    } else {
        btn.classList.remove('text-gray-600');
        btn.classList.add('text-green-400');
        if (dot) dot.classList.remove('hidden');
    }

    // For track repeat, show a "1" indicator
    if (icon) {
        if (state === 'track') {
            icon.innerHTML = '<path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/><text x="12" y="14.5" text-anchor="middle" font-size="7" font-weight="bold" fill="currentColor">1</text>';
        } else {
            icon.innerHTML = '<path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/>';
        }
    }
}
