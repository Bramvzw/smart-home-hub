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
    // If repeat track is on, next up is the current track
    const repeatBtn = document.getElementById('repeat-btn');
    if (repeatBtn && repeatBtn.dataset.repeatState === 'track') {
        return fetch('/spotify/playback-state')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.item) {
                    renderNextTrackFn(elements, data.item);
                } else {
                    displayMessage(elements.nextTrackContainer, elements.messageTemplate, 'No upcoming tracks');
                }
            })
            .catch(() => {
                displayMessage(elements.nextTrackContainer, elements.messageTemplate, 'Error loading next track');
            });
    }

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
        displayMessage(elements.nextTrackContainer, elements.messageTemplate, 'No upcoming tracks');
        return;
    }

    try {
        // Safely access nested properties
        const imageUrl = track.album?.images?.[0]?.url || '';
        const artistNames = track.artists?.map(a => a.name || 'Unknown').join(', ') || 'Unknown Artist';
        const trackName = track.name || 'Unknown Track';

        // Update the elements directly using updateElementContent
        import('../../utils/index.js').then(utils => {
            // First update the image (which has its own preloading mechanism)
            utils.updateElementContent('next-track-image', imageUrl, 'src');

            // Then update all text elements together with a small delay
            // This allows CSS transitions to work properly in a coordinated way
            setTimeout(() => {
                // Set track name
                utils.updateElementContent('next-track-name', trackName);

                // Set artist names
                utils.updateElementContent('next-track-artists', artistNames);
            }, 50); // Small delay for smoother transition
        });

        // Add event listener for the play button if it exists
        const playButton = elements.nextTrackContainer.querySelector('.play-next-track-btn');
        if (playButton && track.uri) {
            // Remove existing listeners to prevent duplicates
            const newPlayButton = playButton.cloneNode(true);
            playButton.parentNode.replaceChild(newPlayButton, playButton);

            newPlayButton.addEventListener('click', function() {
                startPlayback(elements, null, track.uri);
            });
        }
    } catch (error) {
        displayMessage(elements.nextTrackContainer, elements.messageTemplate, 'Error displaying next track');
    }
}
