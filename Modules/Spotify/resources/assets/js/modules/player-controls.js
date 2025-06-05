/**
 * Player Controls module
 * Contains functions for controlling playback and updating player state
 */

import { postOptions, showErrorMessage, updateElementContent, handleResponse } from './utils.js';

/**
 * Start playback, optionally with a specific URI
 * @param {Object} elements - DOM elements object
 * @param {Function} updatePlayerStateFn - Function to update player state
 * @param {string|null} uri - Optional URI to play
 */
export function startPlayback(elements, updatePlayerStateFn, uri = null) {
    const options = postOptions(elements.csrfToken);
    if (uri) {
        options.body = JSON.stringify({ uri });
    }
    fetch('/spotify/play', options).then(response =>
        handleResponse(response, updatePlayerStateFn, elements)
    );
}

/**
 * Pause the current playback
 * @param {Object} elements - DOM elements object
 * @param {Function} updatePlayerStateFn - Function to update player state
 */
export function pausePlayback(elements, updatePlayerStateFn) {
    fetch('/spotify/pause', postOptions(elements.csrfToken)).then(response =>
        handleResponse(response, updatePlayerStateFn, elements)
    );
}

/**
 * Control playback (previous/next)
 * @param {Object} elements - DOM elements object
 * @param {Function} updatePlayerStateFn - Function to update player state
 * @param {string} action - The action to perform (previous/next)
 */
export function control(elements, updatePlayerStateFn, action) {
    fetch(`/spotify/${action}`, postOptions(elements.csrfToken)).then(response =>
        handleResponse(response, updatePlayerStateFn, elements)
    );
}

/**
 * Set the volume level
 * @param {Object} elements - DOM elements object
 * @param {Function} updatePlayerStateFn - Function to update player state
 * @param {number} volume - Volume level (0-100)
 */
export function setVolume(elements, updatePlayerStateFn, volume) {
    fetch('/spotify/volume', {
        ...postOptions(elements.csrfToken),
        body: JSON.stringify({ volume })
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success && data.code === 'volume_control_not_supported') {
                showErrorMessage(elements, 'This device does not support volume control.');
                setTimeout(updatePlayerStateFn, 500);
            }
        })
        .catch(error => {
            showErrorMessage(elements, 'Error setting volume');
        });
}

/**
 * Start periodic updates of player state
 * @param {Object} state - The player state object
 * @param {Function} updatePlayerStateFn - Function to update player state
 * @param {Function} updateState - Function to update state object
 * @returns {Object} Updated state object
 */
export function startPeriodicUpdates(state, updatePlayerStateFn, updateState) {
    // Clear any existing interval
    if (state.updateInterval) {
        clearInterval(state.updateInterval);
    }

    // Update immediately
    updatePlayerStateFn();

    // Then set up interval for future updates
    const updateInterval = setInterval(updatePlayerStateFn, 1000);

    // Update state with new interval
    return updateState(state, { updateInterval });
}

/**
 * Stop periodic updates of player state
 * @param {Object} state - The player state object
 * @param {Function} updateState - Function to update state object
 * @returns {Object} Updated state object
 */
export function stopPeriodicUpdates(state, updateState) {
    if (state.updateInterval) {
        clearInterval(state.updateInterval);
    }
    return updateState(state, { updateInterval: null });
}

/**
 * Fetch and update the current player state
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Function} updatePlayerUI - Function to update player UI
 * @param {Function} updateState - Function to update state object
 * @param {Function} checkIfTrackIsLiked - Function to check if track is liked
 * @param {Function} loadNextTrack - Function to load next track
 */
export function updatePlayerState(state, elements, updatePlayerUI, updateState, checkIfTrackIsLiked, loadNextTrack) {
    // Don't update while dragging
    if (state.isDragging) return;

    fetch('/spotify/playback-state')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updatePlayerUI(data);

                // If track changed, update like status and next track
                if (state.currentTrackId !== data.item?.id) {
                    const newTrackId = data.item?.id;
                    updateState(state, { currentTrackId: newTrackId });

                    if (newTrackId) {
                        checkIfTrackIsLiked(newTrackId);
                        loadNextTrack();
                    }
                }
            } else {
                // Silent fail - no need to show errors for routine updates
                // This happens normally when playback is inactive
            }
        })
        .catch(error => {
            // Silent fail for routine updates
        });
}

/**
 * Update the player UI with the current playback state
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Object} playbackState - The current playback state from Spotify API
 * @param {Function} updateState - Function to update state object
 * @param {Function} formatTime - Function to format time
 */
export function updatePlayerUI(state, elements, playbackState, updateState, formatTime) {
    // Update state
    updateState(state, {
        isPlaying: playbackState.is_playing,
        currentDuration: playbackState.item?.duration_ms || 0
    });

    // Update play/pause button icon
    if (elements.playPauseIcon) {
        elements.playPauseIcon.classList.toggle('fa-pause', state.isPlaying);
        elements.playPauseIcon.classList.toggle('fa-play', !state.isPlaying);
    }

    // Update track information if available
    if (playbackState.item) {
        try {
            // Safely get image URL
            const imageUrl = playbackState.item.album?.images?.[0]?.url || '';

            // Update track details
            updateElementContent('track-image', imageUrl, 'src');
            updateElementContent('track-name', playbackState.item.name || 'Unknown Track');
            updateElementContent('artist-name',
                playbackState.item.artists?.map(a => a.name).join(', ') || 'Unknown Artist');
            updateElementContent('album-name', playbackState.item.album?.name || 'Unknown Album');
            updateElementContent('duration', formatTime(playbackState.item.duration_ms || 0));

            // Only update progress if not dragging
            if (!state.isDragging) {
                const progressMs = playbackState.progress_ms || 0;
                const durationMs = playbackState.item.duration_ms || 1;
                const progressPercentage = (progressMs / durationMs) * 100;

                updateElementContent('current-time', formatTime(progressMs));

                if (elements.progressBar) {
                    elements.progressBar.style.width = `${progressPercentage}%`;
                }
            }
        } catch (error) {
            // Silent fail - no need to show errors for UI updates
        }
    }
}
