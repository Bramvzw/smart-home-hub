/**
 * Playlists module
 * Contains functions for handling the playlist functionality
 */

import { postOptions, showSuccessMessage } from '../../utils/index.js';

/**
 * Set up event listeners for playlist items
 */
export function setupPlaylistEventListeners(elements, updatePlayerState) {
    document.querySelectorAll('.playlist-item').forEach(item => {
        item.removeEventListener('click', handlePlaylistClick);
        item.addEventListener('click', handlePlaylistClick);
    });

    function handlePlaylistClick() {
        if (this.getAttribute('data-id') === 'liked-songs') {
            showSuccessMessage(elements, 'Playing Liked Songs feature coming soon!');
        } else {
            const uri = this.getAttribute('data-uri');
            if (uri) {
                (globalThis.shufflePlayPlaylist || shufflePlayPlaylist)(
                    elements,
                    updatePlayerState,
                    uri
                );
            }
        }
    }
}

/**
 * Shuffle and play a playlist
 */
export function shufflePlayPlaylist(elements, updatePlayerState, uri) {
    return fetch('/spotify/shuffle-play-playlist', {
        ...postOptions(elements.csrfToken),
        body: JSON.stringify({ uri })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updatePlayerState();
            } else {
                console.error('Failed to play playlist:', data);
            }
        })
        .catch(() => {
            console.error(elements, 'Error playing playlist');
        });
}
