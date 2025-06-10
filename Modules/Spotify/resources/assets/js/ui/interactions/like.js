/**
 * Like module
 * Contains functions for handling the like functionality
 */

import { showErrorMessage  } from '../../utils/index.js'

/**
 * Check if the current track is liked by the user
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
 *  ToDo: when track is paused be able to like/unlike it
 */

/**
 * Toggle like status for the current track
 */
export function toggleLike(state, elements, updateState, updateLikeButton) {
    const trackId = state.currentTrackId || window.SPOTIFY_STATE?.item?.id;

    if (!trackId) {
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
            id: trackId,
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
 */
export function updateLikeButton(state, elements) {
    if (elements.likeIcon && elements.likeBtn) {
        elements.likeIcon.classList.toggle('fas', state.isTrackLiked);
        elements.likeIcon.classList.toggle('far', !state.isTrackLiked);
        elements.likeBtn.classList.toggle('active', state.isTrackLiked);
    }
}
