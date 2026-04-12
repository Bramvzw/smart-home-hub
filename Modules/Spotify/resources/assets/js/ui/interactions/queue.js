import { formatTime, postOptions, getImageUrl, escapeHtml } from '../../utils/index.js';

export function loadQueue(elements, startPlayback) {
    const container = elements.queueTracksList;
    if (!container) return;

    container.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">Loading queue...</div>';

    fetch('/spotify/queue')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.queue || data.queue.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">Queue is empty</div>';
                return;
            }

            container.innerHTML = data.queue.map((track, index) => {
                if (!track) return '';

                const image = getImageUrl(track, 'album');
                const name = track.name || 'Unknown';
                const artists = (track.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                const duration = formatTime(track.duration_ms || 0);

                return `<div class="track-row flex items-center space-x-3 px-3 py-2 rounded-lg" data-queue-index="${index}">
                    <span class="text-xs text-gray-600 w-5 text-right shrink-0 tabular-nums">${index + 1}</span>
                    <img src="${image}" alt="" class="w-9 h-9 rounded-md object-cover shrink-0 bg-white/5">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm text-white truncate">${escapeHtml(name)}</div>
                        <div class="text-xs text-gray-500 truncate">${escapeHtml(artists)}</div>
                    </div>
                    <div class="text-xs text-gray-600 tabular-nums shrink-0">${duration}</div>
                    <button class="skip-to-btn text-gray-500 hover:text-green-400 transition-colors p-1.5 rounded-lg hover:bg-white/5 shrink-0" title="Skip to this track" data-skip-count="${index + 1}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
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
            container.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">Failed to load queue</div>';
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
