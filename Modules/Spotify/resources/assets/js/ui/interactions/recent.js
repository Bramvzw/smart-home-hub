import { formatTime, getImageUrl, escapeHtml } from '../../utils/index.js';

export function loadRecentlyPlayed(elements, startPlayback) {
    const container = elements.recentTracksList;
    if (!container) return;

    fetch('/spotify/recently-played')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.items || data.items.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">No recently played tracks</div>';
                return;
            }

            container.innerHTML = data.items.map(item => {
                const track = item.track;
                if (!track) return '';

                const image = getImageUrl(track, 'album');
                const name = track.name || 'Unknown';
                const artists = (track.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                const duration = formatTime(track.duration_ms || 0);
                const uri = track.uri;

                return `<button class="track-row w-full flex items-center space-x-3 px-3 py-2 rounded-lg text-left" data-uri="${escapeHtml(uri)}">
                    <img src="${image}" alt="" class="w-9 h-9 rounded-md object-cover shrink-0 bg-white/5">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm text-white truncate">${escapeHtml(name)}</div>
                        <div class="text-xs text-gray-500 truncate">${escapeHtml(artists)}</div>
                    </div>
                    <div class="text-xs text-gray-600 tabular-nums shrink-0">${duration}</div>
                </button>`;
            }).join('');

            container.querySelectorAll('[data-uri]').forEach(row => {
                row.addEventListener('click', () => startPlayback(row.dataset.uri));
            });
        })
        .catch(() => {
            container.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">Failed to load</div>';
        });
}
