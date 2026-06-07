import { createInitialState, updateState } from '../../../Modules/Spotify/resources/assets/js/core/state.js';
import { getElements } from '../../../Modules/Spotify/resources/assets/js/ui/elements.js';
import { updateLikeButton } from '../../../Modules/Spotify/resources/assets/js/ui/interactions/like.js';
import { drag, endDrag, seekOnClick, startDrag } from '../../../Modules/Spotify/resources/assets/js/ui/interactions/track-progress.js';
import { setPlayPauseIcon } from '../../../Modules/Spotify/resources/assets/js/ui/player-renderer.js';
import { escapeHtml, formatTime, showAlert } from '../../../Modules/Spotify/resources/assets/js/utils/index.js';

describe('Spotify current frontend modules', () => {
    it('creates state from the current Spotify bootstrap payload', () => {
        const state = createInitialState();

        expect(state).toMatchObject({
            isPlaying: false,
            currentTrackId: 'test-track-id',
            isTrackLiked: false,
            isDragging: false,
            progressMs: 150000,
            durationMs: 300000,
            skipPending: false,
            updateInterval: null,
        });
        expect(updateState(state, { isPlaying: true })).toMatchObject({ isPlaying: true });
    });

    it('collects the DOM references used by the current player UI', () => {
        document.head.innerHTML = '<meta name="csrf-token" content="token">';
        document.body.innerHTML = `
            <button id="play-pause-btn"></button>
            <svg id="play-pause-icon"></svg>
            <button id="previous-btn"></button>
            <button id="next-btn"></button>
            <button id="like-btn"></button>
            <svg id="like-icon"></svg>
            <input id="volume-slider">
            <div id="progress-container"></div>
            <div id="progress-bar"></div>
            <div id="next-track"></div>
            <template id="message-template"></template>
            <aside id="sidebar"></aside>
            <button id="sidebar-resize-btn"></button>
            <button id="shuffle-btn"></button>
            <button id="repeat-btn"></button>
            <svg id="repeat-icon"></svg>
            <span id="repeat-dot"></span>
            <button id="device-btn"></button>
            <div id="device-list"></div>
            <div id="device-list-items"></div>
            <span id="device-name"></span>
            <div id="queue-tracks-list"></div>
            <input id="search-input">
            <div id="search-results"></div>
            <div id="recent-tracks-list"></div>
        `;

        const elements = getElements();

        expect(elements.csrfToken).toBe('token');
        expect(elements.recentTracksList).toBe(document.getElementById('recent-tracks-list'));
        expect(elements.queueTracksList).toBe(document.getElementById('queue-tracks-list'));
    });

    it('updates icon rendering for play/pause and liked state', () => {
        document.body.innerHTML = `
            <button id="like-btn" class="text-[var(--hub-dim)]"></button>
            <svg id="like-icon"><path></path></svg>
            <svg id="play-pause-icon" class="ml-0.5"><path></path></svg>
        `;

        updateLikeButton({ isTrackLiked: true }, {
            likeBtn: document.getElementById('like-btn'),
            likeIcon: document.getElementById('like-icon'),
        });
        setPlayPauseIcon(document.getElementById('play-pause-icon'), true);

        expect(document.getElementById('like-icon').getAttribute('fill')).toBe('currentColor');
        expect(document.getElementById('like-btn').classList.contains('text-[#95e2d3]')).toBe(true);
        expect(document.querySelector('#play-pause-icon path').getAttribute('d')).toContain('h4v16');
    });

    it('formats, escapes and displays alerts with real jsdom elements', () => {
        jest.useFakeTimers();

        expect(formatTime(150000)).toBe('2:30');
        expect(escapeHtml('<script>x</script>')).toBe('&lt;script&gt;x&lt;/script&gt;');

        showAlert({}, 'Saved', 'success');

        expect(document.body.textContent).toContain('Saved');
        jest.runOnlyPendingTimers();
        expect(document.body.textContent).not.toContain('Saved');
    });

    it('calculates drag and seek positions from durationMs', () => {
        document.body.innerHTML = '<div id="current-time"></div>';
        const state = { isDragging: false, durationMs: 300000, isPlaying: false };
        const elements = {
            csrfToken: 'token',
            progressBar: document.createElement('div'),
            progressContainer: {
                getBoundingClientRect: () => ({ left: 0, width: 300 }),
            },
        };
        const update = (current, patch) => ({ ...current, ...patch });
        const seek = jest.fn();

        const dragging = startDrag(state, update, () => {}, { clientX: 120 });
        drag(dragging, elements, formatTime, { clientX: 150 });
        const ended = endDrag(dragging, elements, update, seek, { clientX: 150 });
        const clicked = seekOnClick(state, elements, update, seek, { clientX: 75 });

        expect(document.getElementById('current-time').textContent).toBe('2:30');
        expect(elements.progressBar.style.width).toBe('50%');
        expect(seek).toHaveBeenCalledWith(elements, 150000);
        expect(ended).toMatchObject({ isDragging: false, progressMs: 150000 });
        expect(clicked).toMatchObject({ progressMs: 75000 });
    });
});
