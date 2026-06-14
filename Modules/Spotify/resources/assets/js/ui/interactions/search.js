import { formatTime, getImageUrl, escapeHtml } from '../../utils/index.js';

export function setupSearch(elements, startPlayback, updatePlayerStateFn) {
    const input = elements.searchInput;
    const results = elements.searchResults;
    if (!input || !results) return;

    let debounceTimer;

    document.querySelectorAll('[data-search-chip]').forEach(chip => {
        chip.addEventListener('click', () => {
            input.value = chip.dataset.searchChip || chip.textContent.trim();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        });
    });

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length < 2) {
            results.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Zoek naar nummers, albums of afspeellijsten</div>';
            return;
        }

        debounceTimer = setTimeout(() => {
            results.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Zoeken...</div>';

            fetch(`/spotify/search?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        results.innerHTML = `<div class="text-center text-[var(--spotify-dim)] text-sm py-8">${data.message || 'Geen resultaten gevonden'}</div>`;
                        return;
                    }

                    const tracks = (data.tracks || []).filter(t => t != null);
                    const albums = (data.albums || []).filter(a => a != null);
                    const playlists = (data.playlists || []).filter(p => p != null);

                    if (tracks.length === 0 && albums.length === 0 && playlists.length === 0) {
                        results.innerHTML = '<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Geen resultaten gevonden</div>';
                        return;
                    }

                    let cols = [];

                    // Tracks
                    if (tracks.length > 0) {
                        cols.push(renderSection('Nummers', tracks.map(track => {
                            const image = getImageUrl(track, 'album');
                            const name = track.name || 'Unknown';
                            const artists = (track.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                            const duration = formatTime(track.duration_ms || 0);
                            return `<button class="track-row spotify-list-row" data-uri="${escapeHtml(track.uri)}" data-type="track">
                                <span class="spotify-row-thumb"><img src="${image}" alt=""></span>
                                <span class="spotify-row-meta">
                                    <span class="spotify-row-title">${escapeHtml(name)}</span>
                                    <span class="spotify-row-subtitle">${escapeHtml(artists)}</span>
                                </span>
                                <span class="spotify-row-time">${duration}</span>
                            </button>`;
                        })));
                    }

                    // Albums
                    if (albums.length > 0) {
                        cols.push(renderSection('Albums', albums.map(album => {
                            const image = getImageUrl(album);
                            const name = album.name || 'Unknown';
                            const artists = (album.artists || []).filter(a => a != null).map(a => a.name || '').join(', ');
                            return `<button class="track-row spotify-list-row" data-uri="${escapeHtml(album.uri)}" data-type="context">
                                <span class="spotify-row-thumb"><img src="${image}" alt=""></span>
                                <span class="spotify-row-meta">
                                    <span class="spotify-row-title">${escapeHtml(name)}</span>
                                    <span class="spotify-row-subtitle">${escapeHtml(artists)}</span>
                                </span>
                            </button>`;
                        })));
                    }

                    // Playlists
                    if (playlists.length > 0) {
                        cols.push(renderSection('Afspeellijsten', playlists.map(playlist => {
                            const image = getImageUrl(playlist);
                            const name = playlist.name || 'Unknown';
                            const owner = playlist.owner?.display_name || '';
                            return `<button class="track-row spotify-list-row" data-uri="${escapeHtml(playlist.uri)}" data-type="context">
                                <span class="spotify-row-thumb"><img src="${image}" alt=""></span>
                                <span class="spotify-row-meta">
                                    <span class="spotify-row-title">${escapeHtml(name)}</span>
                                    <span class="spotify-row-subtitle">van ${escapeHtml(owner)}</span>
                                </span>
                            </button>`;
                        })));
                    }

                    results.innerHTML = `<div class="grid gap-4 h-full" style="grid-template-columns: repeat(${cols.length}, minmax(0, 1fr))">${cols.map(c => `<div class="overflow-y-auto min-w-0">${c}</div>`).join('')}</div>`;

                    results.querySelectorAll('[data-type="track"]').forEach(row => {
                        row.addEventListener('click', () => {
                            playNow(startPlayback, updatePlayerStateFn, row.dataset.uri);
                        });
                    });

                    results.querySelectorAll('[data-type="context"]').forEach(row => {
                        row.addEventListener('click', () => {
                            playNow(startPlayback, updatePlayerStateFn, row.dataset.uri);
                        });
                    });
                })
                .catch(err => {
                    console.error('Search error:', err);
                    results.innerHTML = `<div class="text-center text-[var(--spotify-dim)] text-sm py-8">Zoeken mislukt: ${err.message || 'Onbekende fout'}</div>`;
                });
        }, 400);
    });
}

function playNow(startPlayback, updatePlayerStateFn, uri) {
    startPlayback(uri)
        .then(data => {
            if (data?.success === false) return;

            setTimeout(() => {
                updatePlayerStateFn();
                document.querySelector('[data-tab="panel-playing"]')?.click();
            }, 650);
        })
        .catch(err => console.error('Playback start failed:', err));
}

function renderSection(title, items) {
    return `<div class="mb-3">
        <h3 class="spotify-section-kicker px-3 mb-2">${title}</h3>
        <div class="space-y-1">${items.join('')}</div>
    </div>`;
}
