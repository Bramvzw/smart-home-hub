/**
 * Next Track module
 * Contains functions for handling the next track functionality
 */

import { displayMessage } from '../../utils/index.js';
/*
* ToDo refactor can be one function?
 */
/**
 * Load the upcoming track from the queue
 */
export function loadUpcomingTrack(elements, renderNextTrackFn) {
    return fetch('/spotify/next-track')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.next_track) {
                renderNextTrackFn(elements, data.next_track);
            } else {
                displayMessage(elements.nextTrackContainer,  elements.messageTemplate, 'No upcoming tracks');
            }
        })
        .catch(() => {
            displayMessage(elements.nextTrackContainer,  elements.messageTemplate, 'Error loading next track');
        });
}

/**
 * Render the next track in the UI
 */
export function renderNextTrack(elements, startPlayback, track) {
    if (!elements.nextTrackContainer) return;

    if (!track) {
        displayMessage(elements.nextTrackContainer,  elements.messageTemplate, 'No upcoming tracks');
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
        displayMessage(elements.nextTrackContainer, elements.messageTemplate, 'Error displaying next track');
    }
}
