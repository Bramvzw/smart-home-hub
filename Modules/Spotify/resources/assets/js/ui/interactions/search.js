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
            results.innerHTML = '<div class="text-center text-[var(--hub-dim)] text-sm py-8">Search for songs, albums or playlists</div>';
            return;
        }

        debounceTimer = setTimeout(() => {
            results.innerHTML = '<div class="text-center text-[var(--hub-dim)] text-sm py-8">Searching...</div>';

            fetch(`/spotify/search?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        results.innerHTML = `<div class="text-center text-[var(--hub-dim)] text-sm py-8">${data.message || 'No results found'}</div>`;
                        return;
                    }

                    const tracks = (data.tracks || []).filter(t => t != null);
                    const albums = (data.albums || []).filter(a => a != null);
                    const playlists = (data.playlists || []).filter(p => p != null);

                    if (tracks.length === 0 && albums.length === 0 && playlists.length === 0) {
                        results.innerHTML = '<div class="text-center text-[var(--hub-dim)] text-sm py-8">No results found</div>';
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
                            return `<button class="track-row group w-full flex items-center space-x-2 px-2 py-1.5 rounded-[7px] text-left" data-uri="${escapeHtml(track.uri)}" data-type="track">
                                <div class="relative w-8 h-8 shrink-0">
                                    <img src="${image}" alt="" class="w-8 h-8 rounded-[6px] object-cover bg-[var(--hub-card)]">
                                    <div class="absolute inset-0 flex items-center justify-center bg-[#0d0e12]/70 rounded-[6px] opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4 text-[var(--hub-text)]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-[var(--hub-text)] truncate">${escapeHtml(name)}</div>
                                    <div class="text-xs text-[var(--hub-dim)] truncate">${escapeHtml(artists)}</div>
                                </div>
                                <div class="text-xs text-[var(--hub-dim)] tabular-nums shrink-0">${duration}</div>
                            </button>`;
                        })));
                    }

                    // Albums
                    if (albums.length > 0) {
                        cols.push(renderSection('Albums', albums.map(album => {
                            const image = getImageUrl(album);
                            const name = album.name || 'Unknown';
                            const artists = (album.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                            return `<button class="track-row group w-full flex items-center space-x-2 px-2 py-1.5 rounded-[7px] text-left" data-uri="${escapeHtml(album.uri)}" data-type="context">
                                <div class="relative w-8 h-8 shrink-0">
                                    <img src="${image}" alt="" class="w-8 h-8 rounded-[6px] object-cover bg-[var(--hub-card)]">
                                    <div class="absolute inset-0 flex items-center justify-center bg-[#0d0e12]/70 rounded-[6px] opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4 text-[var(--hub-text)]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-[var(--hub-text)] truncate">${escapeHtml(name)}</div>
                                    <div class="text-xs text-[var(--hub-dim)] truncate">${escapeHtml(artists)}</div>
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
                            return `<button class="track-row group w-full flex items-center space-x-2 px-2 py-1.5 rounded-[7px] text-left" data-uri="${escapeHtml(playlist.uri)}" data-type="context">
                                <div class="relative w-8 h-8 shrink-0">
                                    <img src="${image}" alt="" class="w-8 h-8 rounded-[6px] object-cover bg-[var(--hub-card)]">
                                    <div class="absolute inset-0 flex items-center justify-center bg-[#0d0e12]/70 rounded-[6px] opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4 text-[var(--hub-text)]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-[var(--hub-text)] truncate">${escapeHtml(name)}</div>
                                    <div class="text-xs text-[var(--hub-dim)] truncate">by ${escapeHtml(owner)}</div>
                                </div>
                            </button>`;
                        })));
                    }

                    results.innerHTML = `<div class="grid gap-3 h-full" style="grid-template-columns: repeat(${cols.length}, minmax(0, 1fr))">${cols.map(c => `<div class="overflow-y-auto min-w-0">${c}</div>`).join('')}</div>`;

                    results.querySelectorAll('[data-type="track"]').forEach(row => {
                        row.addEventListener('click', () => {
                            queueAndPlay(elements, row.dataset.uri, updatePlayerStateFn);
                        });
                    });

                    results.querySelectorAll('[data-type="context"]').forEach(row => {
                        row.addEventListener('click', () => {
                            startPlayback(row.dataset.uri);
                        });
                    });
                })
                .catch(err => {
                    console.error('Search error:', err);
                    results.innerHTML = `<div class="text-center text-[var(--hub-dim)] text-sm py-8">Search failed: ${err.message || 'Unknown error'}</div>`;
                });
        }, 400);
    });
}

function queueAndPlay(elements, uri, updatePlayerStateFn) {
    const options = postOptions(elements.csrfToken);
    options.body = JSON.stringify({ uri });

    fetch('/spotify/add-to-queue', options)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            return fetch('/spotify/next', postOptions(elements.csrfToken))
                .then(res => res.json())
                .then(() => setTimeout(() => updatePlayerStateFn(), 500));
        })
        .catch(err => console.error('Queue and play failed:', err));
}

function renderSection(title, items) {
    return `<div class="mb-3">
        <h3 class="text-xs font-bold text-[var(--hub-dim)] uppercase tracking-wider px-3 mb-1">${title}</h3>
        <div class="space-y-0.5">${items.join('')}</div>
    </div>`;
}
