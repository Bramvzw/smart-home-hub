/**
 * State module
 * Contains the player state and functions to manage it
 */

/**
 * Create the initial player state
 * @returns {Object} Object containing the player state
 */
export function createInitialState() {
    return {
        isPlaying: window.SPOTIFY_STATE?.is_playing ?? false,
        currentTrackId: null,
        isTrackLiked: false,
        isDragging: false,
        currentDuration: 0,
        updateInterval: null
    };
}

/**
 * Update the player state with new values
 * @param {Object} state - The current state object
 * @param {Object} newValues - Object containing new values to update
 * @returns {Object} The updated state object
 */
export function updateState(state, newValues) {
    return { ...state, ...newValues };
}
