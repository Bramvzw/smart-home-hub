import { formatTime, getImageUrl, escapeHtml } from '../../utils/index.js';

export function loadRecentlyPlayed(elements, startPlayback) {
    const container = elements.recentTracksList;
    if (!container) return;

    fetch('/spotify/recently-played')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.items || data.items.length === 0) {
                container.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">No recently played tracks</div>';
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

                return `<button class="track-row spotify-list-row" data-uri="${escapeHtml(uri)}">
                    <span class="spotify-row-thumb"><img src="${image}" alt=""></span>
                    <span class="spotify-row-meta">
                        <span class="spotify-row-title">${escapeHtml(name)}</span>
                        <span class="spotify-row-subtitle">${escapeHtml(artists)}</span>
                    </span>
                    <span class="spotify-row-time">${duration}</span>
                    <span class="spotify-row-action" aria-hidden="true">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                </button>`;
            }).join('');

            container.querySelectorAll('[data-uri]').forEach(row => {
                row.addEventListener('click', () => startPlayback(row.dataset.uri));
            });
        })
        .catch(() => {
            container.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Failed to load recently played</div>';
        });
}
