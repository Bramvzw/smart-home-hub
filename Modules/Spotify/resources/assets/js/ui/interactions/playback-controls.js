/**
 * Player Controls module
 */

import { postOptions, handleResponse } from '../../utils/index.js';
import { updateMirroredTime, updateProgressFills } from './track-progress.js';

/**
 * Start playback, optionally with a specific URI
 */
export function startPlayback(elements, updatePlayerStateFn, uri = null) {
    const options = postOptions(elements.csrfToken);
    if (uri) options.body = JSON.stringify({ uri });
    return fetch('/spotify/play', options).then(r => handleResponse(r, updatePlayerStateFn, elements));
}

/**
 * Pause the current playback
 */
export function pausePlayback(elements, updatePlayerStateFn) {
    return fetch('/spotify/pause', postOptions(elements.csrfToken))
        .then(r => handleResponse(r, updatePlayerStateFn, elements));
}

/**
 * Control playback (next / previous)
 */
export function control(elements, updatePlayerStateFn, action) {
    return fetch(`/spotify/${action}`, postOptions(elements.csrfToken))
        .then(r => handleResponse(r, updatePlayerStateFn, elements));
}

/**
 * Start periodic polling (3 s playing / 15 s paused).
 * Returns forcePoll() — call it to cancel the pending timer and poll immediately.
 */
export function startPeriodicUpdates(state, updatePlayerStateFn, updateState, getState) {
    let consecutiveErrors = 0;
    let timeoutId = null;
    let stopped = false;

    function getInterval() {
        if (consecutiveErrors > 0) return Math.min(5000 * Math.pow(2, consecutiveErrors - 1), 60000);
        return getState?.().isPlaying ? 3000 : 15000;
    }

    function scheduleNext() {
        if (stopped) return;
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            if (document.hidden) { scheduleNext(); return; }
            updatePlayerStateFn()
                .then(() => { consecutiveErrors = 0; scheduleNext(); })
                .catch(() => { consecutiveErrors++;  scheduleNext(); });
        }, getInterval());
    }

    function forcePoll() {
        if (stopped) return;
        clearTimeout(timeoutId);
        updatePlayerStateFn()
            .then(() => { consecutiveErrors = 0; scheduleNext(); })
            .catch(() => { consecutiveErrors++;  scheduleNext(); });
    }

    function onVisibilityChange() { if (!document.hidden) forcePoll(); }

    function stop() {
        stopped = true;
        clearTimeout(timeoutId);
        timeoutId = null;
        document.removeEventListener('visibilitychange', onVisibilityChange);
    }

    document.addEventListener('visibilitychange', onVisibilityChange);

    // Initial poll
    updatePlayerStateFn()
        .then(() => { consecutiveErrors = 0; scheduleNext(); })
        .catch(() => { consecutiveErrors++;  scheduleNext(); });

    return { state: updateState(state, { updateInterval: timeoutId }), forcePoll, stop };
}

/**
 * Progress ticker — owns progressBar and current-time DOM writes.
 *
 * Reads the (progressMs, progressAt) anchor from state and renders:
 *   displayed = progressMs + (Date.now() - progressAt)   [while playing]
 * The ticker never mutates state. DOM writes are throttled to 250 ms.
 */
export function startProgressTicker(getState, elements, formatTimeFn, onTrackEnd) {
    let lastRender      = 0;
    let trackEndFired   = false;
    let lastSeenTrackId = null;

    function tick(timestamp) {
        const state = getState();

        // Reset end-of-track guard on track change
        if (state.currentTrackId !== lastSeenTrackId) {
            lastSeenTrackId = state.currentTrackId;
            trackEndFired   = false;
        }

        if (state.isDragging || state.durationMs <= 0) {
            requestAnimationFrame(tick);
            return;
        }

        // Interpolate position from anchor — read-only, no state mutation
        const displayed = state.isPlaying && state.progressAt
            ? Math.min(state.progressMs + (Date.now() - state.progressAt), state.durationMs)
            : state.progressMs;

        // Throttle DOM writes to 250 ms
        if (timestamp - lastRender >= 250) {
            lastRender = timestamp;

            updateProgressFills(displayed / state.durationMs);
            updateMirroredTime(formatTimeFn(displayed));
        }

        // Trigger immediate poll when track is estimated to have ended
        if (state.isPlaying && displayed >= state.durationMs && !trackEndFired) {
            trackEndFired = true;
            onTrackEnd?.();
        }

        requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
}
