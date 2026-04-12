/**
 * Progress Bar module — drag, click-to-seek, and API seek
 */

import { postOptions, updateElementContent, handleResponse } from '../../utils/index.js';

export function startDrag(state, updateState, drag, e) {
    const newState = updateState(state, { isDragging: true });
    drag(newState, e);
    return newState;
}

export function drag(state, elements, formatTime, e) {
    if (!state.isDragging || !elements.progressContainer) return;
    const rect     = elements.progressContainer.getBoundingClientRect();
    const ratio    = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const posMs    = Math.floor(ratio * state.durationMs);
    if (elements.progressBar) elements.progressBar.style.width = `${ratio * 100}%`;
    updateElementContent('current-time', formatTime(posMs));
}

export function endDrag(state, elements, updateState, seekToPosition, e) {
    if (!state.isDragging) return state;
    const rect  = elements.progressContainer.getBoundingClientRect();
    const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const posMs = Math.floor(ratio * state.durationMs);
    seekToPosition(elements, posMs);
    // Update anchor immediately so the ticker reflects the new position right away
    return updateState(state, {
        isDragging:  false,
        progressMs:  posMs,
        progressAt:  state.isPlaying ? Date.now() : null,
    });
}

export function seekOnClick(state, elements, updateState, seekToPosition, e) {
    const rect  = elements.progressContainer.getBoundingClientRect();
    const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    const posMs = Math.floor(ratio * state.durationMs);
    seekToPosition(elements, posMs);
    return updateState(state, {
        progressMs: posMs,
        progressAt: state.isPlaying ? Date.now() : null,
    });
}

export function seekToPosition(elements, updatePlayerState, positionMs) {
    return fetch('/spotify/seek', {
        ...postOptions(elements.csrfToken),
        body: JSON.stringify({ position_ms: positionMs }),
    }).then(r => handleResponse(r, updatePlayerState, elements));
}
