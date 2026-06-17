import { formatTime, postOptions, getImageUrl, escapeHtml } from '../../utils/index.js';

export function loadQueue(elements, startPlayback) {
    const container = elements.queueTracksList;
    if (!container) return;

    container.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Loading queue...</div>';

    fetch('/spotify/queue')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.queue || data.queue.length === 0) {
                container.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">The queue is empty</div>';
                return;
            }

            container.innerHTML = data.queue.map((track, index) => {
                if (!track) return '';

                const image = getImageUrl(track, 'album');
                const name = track.name || 'Unknown';
                const artists = (track.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                const duration = formatTime(track.duration_ms || 0);

                return `<div class="track-row spotify-list-row" data-queue-index="${index}">
                    <span class="spotify-row-thumb"><img src="${image}" alt=""></span>
                    <span class="spotify-row-meta">
                        <span class="spotify-row-title">${escapeHtml(name)}</span>
                        <span class="spotify-row-subtitle">${escapeHtml(artists)}</span>
                    </span>
                    <span class="spotify-row-time">${duration}</span>
                    <button class="skip-to-btn spotify-row-action" title="Skip to this track" data-skip-count="${index + 1}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="m6 18 8.5-6L6 6v12ZM16 6v12h2V6h-2Z"/></svg>
                    </button>
                </div>`;
            }).join('');

            container.querySelectorAll('.skip-to-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const skipCount = parseInt(btn.dataset.skipCount);
                    skipToPosition(elements, skipCount, () => {
                        loadQueue(elements, startPlayback);
                    });
                });
            });
        })
        .catch(() => {
            container.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Failed to load queue</div>';
        });
}

function skipToPosition(elements, count, onComplete) {
    if (count <= 0) {
        if (onComplete) setTimeout(onComplete, 300);
        return;
    }

    fetch('/spotify/next', postOptions(elements.csrfToken))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => skipToPosition(elements, count - 1, onComplete), 200);
            }
        })
        .catch(() => { if (onComplete) onComplete(); });
}
