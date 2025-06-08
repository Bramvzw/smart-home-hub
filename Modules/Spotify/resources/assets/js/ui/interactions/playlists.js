/**
 * Playlists module
 * Contains functions for handling the playlist functionality
 */

import { postOptions, showErrorMessage, showSuccessMessage, displayMessage } from '../../utils/index.js';

/**
 * Load user playlists from the API
 */
export function loadUserPlaylists(elements, renderUserPlaylists) {
    return fetch('/spotify/user-playlists')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.playlists) {
                renderUserPlaylists(elements, data.playlists);
            } else {
                displayMessage( elements.recentlyPlayedContainer, elements.messageTemplate, 'No playlists found in your library');
            }
        })
        .catch(() => {
            displayMessage( elements.recentlyPlayedContainer, elements.messageTemplate,'Error loading playlists');
        });
}

/**
 * ToDo: must be refactored
 */

/**
 * Display a message in the playlists container
 */
export function displayPlaylistMessage(elements, message) {
    displayMessage(elements.recentlyPlayedContainer, elements.messageTemplate, message);
}

/**
 * Render user playlists in the UI
 */
export function renderUserPlaylists(elements, playlists, updatePlayerState) {
    if (!elements.recentlyPlayedContainer) return;

    if (!playlists || !playlists.length) {
        displayPlaylistMessage(elements, 'No playlists found in your library');
        return;
    }

    elements.recentlyPlayedContainer.innerHTML = '';

    playlists.forEach(playlist => {
        try {
            // Check if playlist has all required properties
            if (!playlist.images || !playlist.images.length) {
                return;
            }

            if (!elements.playlistTemplate) return;

            const playlistElement = elements.playlistTemplate.content.firstElementChild.cloneNode(true);

            // Special handling for Liked Songs playlist
            if (playlist.id === 'liked-songs') {
                playlistElement.setAttribute('data-id', 'liked-songs');
                // We don't set a URI for liked songs as it's handled differently
            } else {
                playlistElement.setAttribute('data-uri', playlist.uri);
            }

            // Get the best image (prefer larger images)
            const image = playlist.images.sort((a, b) => (b.width || 0) - (a.width || 0))[0];
            const img = playlistElement.querySelector('img.playlist-image');
            if (img) {
                img.src = image.url;
                img.alt = playlist.name;
            }

            // Add event listener for clicking on the playlist
            playlistElement.addEventListener('click', function() {
                // Special handling for Liked Songs playlist
                if (playlist.id === 'liked-songs') {
                    // For now, just show a message that this feature is coming soon
                    showSuccessMessage(elements, 'Playing Liked Songs feature coming soon!');
                } else {
                    (globalThis.shufflePlayPlaylist || shufflePlayPlaylist)(
                        elements,
                        updatePlayerState,
                        playlist.uri
                    );
                }
            });

            elements.recentlyPlayedContainer.appendChild(playlistElement);
        } catch (error) {
            // Silent fail for individual playlist rendering issues
        }
    });
}

/**
 * ToDo console.log errors instead of displaying them
 */

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
                showSuccessMessage(elements, 'Playing shuffled playlist');
                updatePlayerState();
            } else {
                showErrorMessage(elements, 'Failed to play playlist');
            }
        })
        .catch(() => {
            showErrorMessage(elements, 'Error playing playlist');
        });
}
