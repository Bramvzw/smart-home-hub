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
 * Toggle like status for the current track
 */
export function toggleLike(state, elements, updateState, updateLikeButton) {
    const trackId = state.currentTrackId || window.SPOTIFY_STATE?.item?.id;

    if (!trackId) {
        showErrorMessage(elements, 'Cannot like/unlike: No track is playing');
        return Promise.resolve(state);
    }

    // Optimistic update — flip immediately, revert on failure
    const optimisticState = updateState(state, { isTrackLiked: !state.isTrackLiked });
    updateLikeButton(optimisticState, elements);

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
                updateLikeButton(state, elements); // revert optimistic update
                showErrorMessage(elements, 'Failed to update like status');
            }
        })
        .catch(() => {
            updateLikeButton(state, elements); // revert optimistic update
            showErrorMessage(elements, 'Error updating like status');
        });
}

/**
 * Update the like button UI based on current like status
 */
export function updateLikeButton(state, elements) {
    if (elements.likeIcon && elements.likeBtn) {
        if (state.isTrackLiked) {
            elements.likeIcon.setAttribute('fill', 'currentColor');
            elements.likeIcon.setAttribute('stroke', 'currentColor');
            elements.likeBtn.classList.remove('text-gray-600');
            elements.likeBtn.classList.add('text-green-400');
        } else {
            elements.likeIcon.setAttribute('fill', 'none');
            elements.likeIcon.setAttribute('stroke', 'currentColor');
            elements.likeBtn.classList.remove('text-green-400');
            elements.likeBtn.classList.add('text-gray-600');
        }
    }
}
