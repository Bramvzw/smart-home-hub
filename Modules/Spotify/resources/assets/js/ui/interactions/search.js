import { formatTime, postOptions, getImageUrl, escapeHtml } from '../../utils/index.js';

export function setupSearch(elements, startPlayback, updatePlayerStateFn) {
    const input = elements.searchInput;
    const results = elements.searchResults;
    if (!input || !results) return;

    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length < 2) {
            results.innerHTML = '<div class="text-center text-gray-600 text-sm py-8">Search for songs, albums or playlists</div>';
            return;
        }

        debounceTimer = setTimeout(() => {
            results.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">Searching...</div>';

            fetch(`/spotify/search?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        results.innerHTML = `<div class="text-center text-gray-500 text-sm py-8">${data.message || 'No results found'}</div>`;
                        return;
                    }

                    const tracks = (data.tracks || []).filter(t => t != null);
                    const albums = (data.albums || []).filter(a => a != null);
                    const playlists = (data.playlists || []).filter(p => p != null);

                    if (tracks.length === 0 && albums.length === 0 && playlists.length === 0) {
                        results.innerHTML = '<div class="text-center text-gray-500 text-sm py-8">No results found</div>';
                        return;
                    }

                    let cols = [];

                    // Tracks
                    if (tracks.length > 0) {
                        cols.push(renderSection('Songs', tracks.map(track => {
                            const image = getImageUrl(track, 'album');
                            const name = track.name || 'Unknown';
                            const artists = (track.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                            const duration = formatTime(track.duration_ms || 0);
                            return `<button class="track-row w-full flex items-center space-x-2 px-2 py-1.5 rounded-lg text-left" data-uri="${escapeHtml(track.uri)}" data-type="track">
                                <img src="${image}" alt="" class="w-8 h-8 rounded object-cover shrink-0 bg-white/5">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-white truncate">${escapeHtml(name)}</div>
                                    <div class="text-xs text-gray-500 truncate">${escapeHtml(artists)}</div>
                                </div>
                                <div class="text-xs text-gray-600 tabular-nums shrink-0">${duration}</div>
                            </button>`;
                        })));
                    }

                    // Albums
                    if (albums.length > 0) {
                        cols.push(renderSection('Albums', albums.map(album => {
                            const image = getImageUrl(album);
                            const name = album.name || 'Unknown';
                            const artists = (album.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                            return `<button class="track-row w-full flex items-center space-x-2 px-2 py-1.5 rounded-lg text-left" data-uri="${escapeHtml(album.uri)}" data-type="context">
                                <img src="${image}" alt="" class="w-8 h-8 rounded object-cover shrink-0 bg-white/5">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-white truncate">${escapeHtml(name)}</div>
                                    <div class="text-xs text-gray-500 truncate">${escapeHtml(artists)}</div>
                                </div>
                            </button>`;
                        })));
                    }

                    // Playlists
                    if (playlists.length > 0) {
                        cols.push(renderSection('Playlists', playlists.map(playlist => {
                            const image = getImageUrl(playlist);
                            const name = playlist.name || 'Unknown';
                            const owner = playlist.owner?.display_name || '';
                            return `<button class="track-row w-full flex items-center space-x-2 px-2 py-1.5 rounded-lg text-left" data-uri="${escapeHtml(playlist.uri)}" data-type="context">
                                <img src="${image}" alt="" class="w-8 h-8 rounded object-cover shrink-0 bg-white/5">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-white truncate">${escapeHtml(name)}</div>
                                    <div class="text-xs text-gray-500 truncate">by ${escapeHtml(owner)}</div>
                                </div>
                            </button>`;
                        })));
                    }

                    results.innerHTML = `<div class="grid gap-3 h-full" style="grid-template-columns: repeat(${cols.length}, minmax(0, 1fr))">${cols.map(c => `<div class="overflow-y-auto min-w-0">${c}</div>`).join('')}</div>`;

                    // Tracks: queue and skip
                    results.querySelectorAll('[data-type="track"]').forEach(row => {
                        row.addEventListener('click', () => {
                            queueAndSkip(elements, row.dataset.uri, updatePlayerStateFn);
                        });
                    });

                    // Albums & playlists: start playback with context
                    results.querySelectorAll('[data-type="context"]').forEach(row => {
                        row.addEventListener('click', () => {
                            startPlayback(row.dataset.uri);
                        });
                    });
                })
                .catch(err => {
                    console.error('Search error:', err);
                    results.innerHTML = `<div class="text-center text-gray-500 text-sm py-8">Search failed: ${err.message || 'Unknown error'}</div>`;
                });
        }, 400);
    });
}

function renderSection(title, items) {
    return `<div class="mb-3">
        <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider px-3 mb-1">${title}</h3>
        <div class="space-y-0.5">${items.join('')}</div>
    </div>`;
}

function queueAndSkip(elements, uri, updatePlayerStateFn) {
    const options = postOptions(elements.csrfToken);
    options.body = JSON.stringify({ uri });

    fetch('/spotify/add-to-queue', options)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                return fetch('/spotify/next', postOptions(elements.csrfToken))
                    .then(res => res.json())
                    .then(() => {
                        setTimeout(() => updatePlayerStateFn(), 500);
                    });
            }
        })
        .catch(() => { /* skip failed silently */ });
}

