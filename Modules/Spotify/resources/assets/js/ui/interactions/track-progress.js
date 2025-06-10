/**
 * Progress Bar module
 * Contains functions for handling the progress bar and seeking functionality
 */

import { postOptions, updateElementContent, handleResponse } from '../../utils/index.js';

/**
 * Start dragging the progress bar
 */
export function startDrag(state, updateState, drag, e) {
    const newState = updateState(state, { isDragging: true });
    drag(newState, e);
    return newState;
}

/**
 * Handle dragging of the progress bar
 */
export function drag(state, elements, formatTime, e) {
    if (!state.isDragging || !elements.progressContainer) return;

    const rect = elements.progressContainer.getBoundingClientRect();
    const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const positionMs = Math.floor(position * state.currentDuration);

    if (elements.progressBar) {
        elements.progressBar.style.width = `${position * 100}%`;
    }
    updateElementContent('current-time', formatTime(positionMs));
}

/**
 * End dragging and seek to position
 */
export function endDrag(state, elements, updateState, seekToPosition, e) {
    if (!state.isDragging) return state;

    const rect = elements.progressContainer.getBoundingClientRect();
    const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const positionMs = Math.floor(position * state.currentDuration);

    seekToPosition(elements, positionMs);

    return updateState(state, { isDragging: false });
}

/**
 * Handle click on progress bar
 */
export function seekOnClick(state, elements, seekToPosition, e) {
    const rect = elements.progressContainer.getBoundingClientRect();
    const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const positionMs = Math.floor(position * state.currentDuration);

    // Seek to position
    seekToPosition(elements, positionMs);
}

/**
 * Seek to a specific position in the track
 */
export function seekToPosition(elements, updatePlayerState, positionMs) {
    return fetch('/spotify/seek', {
        ...postOptions(elements.csrfToken),
        body: JSON.stringify({ position_ms: positionMs })
    }).then(response => handleResponse(response, updatePlayerState, elements));
}
