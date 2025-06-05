/**
 * Like module
 * Contains functions for handling the like functionality
 */

import { showErrorMessage } from './utils.js';

/**
 * Check if the current track is liked by the user
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Function} updateState - Function to update state object
 * @param {Function} updateLikeButton - Function to update like button UI
 * @param {string} trackId - The ID of the track to check
 */
export function checkIfTrackIsLiked(state, elements, updateState, updateLikeButton, trackId) {
    const params = new URLSearchParams();
    params.append('ids[]', trackId);

    return fetch(`/spotify/tracks/check?${params.toString()}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success && data.results && data.results.length > 0) {
                state = updateState(state, { isTrackLiked: data.results[0] });
            } else {
                state = updateState(state, { isTrackLiked: false });
            }
            updateLikeButton(state, elements);
            return state;
        })
        .catch(() => {
            state = updateState(state, { isTrackLiked: false });
            updateLikeButton(state, elements);
            return state;
        });
}

/**
 * Toggle like status for the current track
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 * @param {Function} updateState - Function to update state object
 * @param {Function} updateLikeButton - Function to update like button UI
 */
export function toggleLike(state, elements, updateState, updateLikeButton) {
    if (!state.currentTrackId) {
        showErrorMessage(elements, 'Cannot like/unlike: No track is playing');
        return;
    }

    return fetch('/spotify/tracks/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': elements.csrfToken
        },
        body: JSON.stringify({
            id: state.currentTrackId,
            saved: !state.isTrackLiked
        })
    })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                state = updateState(state, { isTrackLiked: data.saved });
                updateLikeButton(state, elements);
                return state;
            } else {
                showErrorMessage(elements, 'Failed to update like status');
            }
        })
        .catch(() => {
            showErrorMessage(elements, 'Error updating like status');
        });
}

/**
 * Update the like button UI based on current like status
 * @param {Object} state - The player state object
 * @param {Object} elements - DOM elements object
 */
export function updateLikeButton(state, elements) {
    if (elements.likeIcon && elements.likeBtn) {
        elements.likeIcon.classList.toggle('fas', state.isTrackLiked);
        elements.likeIcon.classList.toggle('far', !state.isTrackLiked);
        elements.likeBtn.classList.toggle('active', state.isTrackLiked);
    }
}
