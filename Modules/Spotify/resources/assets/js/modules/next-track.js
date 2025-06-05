/**
 * Next Track module
 * Contains functions for handling the next track functionality
 */

import { displayMessage } from './utils.js';

/**
 * Load the next track from the API
 * @param {Object} elements - DOM elements object
 * @param {Function} renderNextTrackFn - Function to render next track
 */
export function loadNextTrack(elements, renderNextTrackFn) {
    return fetch('/spotify/next-track')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.next_track) {
                // renderNextTrackFn expects (elements, startPlayback, track)
                // but we're passing it from spotify.js as (elements, track) => renderNextTrack(elements, startPlaybackFn, track)
                // so we need to pass just elements and track here
                renderNextTrackFn(elements, data.next_track);
            } else {
                displayNextTrackMessage(elements, 'No upcoming tracks');
            }
        })
        .catch(() => {
            displayNextTrackMessage(elements, 'Error loading next track');
        });
}

/**
 * Display a message in the next track container
 * @param {Object} elements - DOM elements object
 * @param {string} message - The message to display
 */
export function displayNextTrackMessage(elements, message) {
    displayMessage(
        elements.nextTrackContainer,
        elements.messageTemplate,
        message
    );
}

/**
 * Render the next track in the UI
 * @param {Object} elements - DOM elements object
 * @param {Function} startPlayback - Function to start playback
 * @param {Object} track - The track object to render
 */
export function renderNextTrack(elements, startPlayback, track) {
    if (!elements.nextTrackContainer) return;

    if (!track) {
        displayNextTrackMessage(elements, 'No upcoming tracks');
        return;
    }

    try {
        // Safely access nested properties
        const imageUrl = track.album?.images?.[0]?.url || '';
        const artistNames = track.artists?.map(a => a.name || 'Unknown').join(', ') || 'Unknown Artist';

        elements.nextTrackContainer.innerHTML = '';
        if (!elements.nextTrackTemplate) return;

        const element = elements.nextTrackTemplate.content.firstElementChild.cloneNode(true);

        // Set image
        const img = element.querySelector('img.next-track-image');
        if (img) {
            img.src = imageUrl;
        }

        // Set track name
        const nameEl = element.querySelector('.next-track-name');
        if (nameEl) {
            nameEl.textContent = track.name || 'Unknown Track';
        }

        // Set artist names
        const artistsEl = element.querySelector('.next-track-artists');
        if (artistsEl) {
            artistsEl.textContent = artistNames;
        }

        elements.nextTrackContainer.appendChild(element);

        // Add event listener for the play button
        const playButton = elements.nextTrackContainer.querySelector('.play-next-track-btn');
        if (playButton && track.uri) {
            playButton.addEventListener('click', function() {
                startPlayback(elements, null, track.uri);
            });
        }
    } catch (error) {
        displayNextTrackMessage(elements, 'Error displaying next track');
    }
}
