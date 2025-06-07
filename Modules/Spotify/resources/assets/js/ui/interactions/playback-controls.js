/**
 * Player Controls module
 * Contains functions for controlling playback and updating player state
 */

import { postOptions, handleResponse } from '../../utils/index.js';

/**
 * ToDo refactor seperate interactions from upatePlayerState
 */

/**
 * Start playback, optionally with a specific URI
 */
export function startPlayback(elements, updatePlayerStateFn, uri = null) {
    const options = postOptions(elements.csrfToken);
    if (uri) {
        options.body = JSON.stringify({ uri });
    }
    return fetch('/spotify/play', options).then(response =>
        handleResponse(response, updatePlayerStateFn, elements)
    );
}

/**
 * Pause the current playback
 */
export function pausePlayback(elements, updatePlayerStateFn) {
    return fetch('/spotify/pause', postOptions(elements.csrfToken)).then(response =>
        handleResponse(response, updatePlayerStateFn, elements)
    );
}

/**
 * Control playback (previous/next)
 */
export function control(elements, updatePlayerStateFn, action) {
    return fetch(`/spotify/${action}`, postOptions(elements.csrfToken)).then(response =>
        handleResponse(response, updatePlayerStateFn, elements)
    );
}

/**
 * Start periodic updates of player state
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
 * Fetch and update the current player state
 */
export function updatePlayerState(state, elements, updatePlayerUI, updateState, checkIfTrackIsLiked, loadNextTrack) {
    // Don't update while dragging
    if (state.isDragging) return Promise.resolve(state);

    return fetch('/spotify/playback-state')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                state = updatePlayerUI(data);

                // If track changed, update like status and next track
                if (state.currentTrackId !== data.item?.id) {
                    const newTrackId = data.item?.id;
                    state = updateState(state, { currentTrackId: newTrackId });

                    if (newTrackId) {
                        checkIfTrackIsLiked(newTrackId);
                        loadNextTrack();
                    }
                }
            } else {
                // Silent fail - no need to show errors for routine updates
                // This happens normally when playback is inactive
            }
            return state;
        })
        .catch(() => state);
}

export { updatePlayerUI } from '../player-renderer.js'
