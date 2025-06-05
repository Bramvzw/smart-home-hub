/**
 * Progress Bar module
 * Contains functions for handling the progress bar and seeking functionality
 */

import { postOptions, updateElementContent, handleResponse } from './utils.js';

/**
 * Start dragging the progress bar
 * @param {Object} state - The player state object
 * @param {Function} updateState - Function to update state object
 * @param {Function} drag - Function to handle dragging
 * @param {MouseEvent} e - Mouse event
 * @returns {Object} Updated state object
 */
export function startDrag(state, updateState, drag, e) {
    const newState = updateState(state, { isDragging: true });
    drag(newState, e);
    return newState;
}

/**
 * Handle dragging of the progress bar
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Function} formatTime - Function to format time
 * @param {MouseEvent} e - Mouse event
 */
export function drag(state, elements, formatTime, e) {
    if (!state.isDragging || !elements.progressContainer) return;

    const rect = elements.progressContainer.getBoundingClientRect();
    const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const positionMs = Math.floor(position * state.currentDuration);

    // Update UI
    if (elements.progressBar) {
        elements.progressBar.style.width = `${position * 100}%`;
    }
    updateElementContent('current-time', formatTime(positionMs));
}

/**
 * End dragging and seek to position
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Function} updateState - Function to update state object
 * @param {Function} seekToPosition - Function to seek to position
 * @param {MouseEvent} e - Mouse event
 * @returns {Object} Updated state object
 */
export function endDrag(state, elements, updateState, seekToPosition, e) {
    if (!state.isDragging) return state;

    const rect = elements.progressContainer.getBoundingClientRect();
    const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const positionMs = Math.floor(position * state.currentDuration);

    // Seek to position
    seekToPosition(elements, positionMs);

    return updateState(state, { isDragging: false });
}

/**
 * Handle click on progress bar
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Function} seekToPosition - Function to seek to position
 * @param {MouseEvent} e - Mouse event
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
 * @param {Object} elements - DOM elements object
 * @param {Function} updatePlayerState - Function to update player state
 * @param {number} positionMs - Position in milliseconds
 */
export function seekToPosition(elements, updatePlayerState, positionMs) {
    return fetch('/spotify/seek', {
        ...postOptions(elements.csrfToken),
        body: JSON.stringify({ position_ms: positionMs })
    }).then(response => handleResponse(response, updatePlayerState, elements));
}
