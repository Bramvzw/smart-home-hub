/**
 * Player UI module
 * Updates state and DOM from a Spotify API playback response.
 *
 * Progress anchor: sets progressMs + progressAt so the ticker can interpolate.
 * The ticker owns all DOM writes for the progress bar.
 */
import { updateElementContent } from '../utils/index.js';
import { updateShuffleUI, lastShuffleToggleAt } from './interactions/shuffle.js';
import { updateRepeatUI } from './interactions/repeat.js';

const PAUSE_PATH = 'M6 4h4v16H6V4zm8 0h4v16h-4V4z';
const PLAY_PATH  = 'M8 5v14l11-7z';

export function setPlayPauseIcon(svgEl, isPlaying) {
    if (!svgEl) return;
    const path = svgEl.querySelector('path');
    if (path) path.setAttribute('d', isPlaying ? PAUSE_PATH : PLAY_PATH);
    svgEl.classList.toggle('ml-0.5', !isPlaying);
}

export function updatePlayerUI(state, elements, data, updateState, formatTime) {
    const apiProgressMs = data.progress_ms ?? 0;
    const durationMs    = data.item?.duration_ms ?? 0;
    const isPlaying     = data.is_playing ?? false;
    const newTrackId    = data.item?.id ?? null;
    const trackChanged  = state.currentTrackId !== newTrackId;

    // Skip is pending and Spotify still returns the old track — wait for the
    // next poll (scheduled by the 500 ms retry in player-service.js).
    if (state.skipPending && !trackChanged) {
        return state;
    }

    state = updateState(state, {
        isPlaying,
        currentTrackId: newTrackId,
        durationMs,
        progressMs: apiProgressMs,
        progressAt: isPlaying ? Date.now() : null,
        skipPending: false,
    });

    // ── Shuffle / repeat ─────────────────────────────────────────────────
    // Only sync on track change (another device may have changed it).
    // On same-track polls, don't touch shuffle/repeat — the user may have
    // just toggled it and the Spotify API hasn't propagated the change yet,
    // which would revert the optimistic update in setupShuffle/setupRepeat.
    const shuffleDisallowed = data.actions?.disallows?.toggling_shuffle === true;
    // Skip shuffle sync for 2 s after a manual toggle so the optimistic update
    // isn't reverted before Spotify propagates the change.
    const shuffleLocked = (Date.now() - lastShuffleToggleAt) < 2000;
    if (trackChanged) {
        if (data.shuffle_state !== undefined) updateShuffleUI(elements, data.shuffle_state, shuffleDisallowed);
        if (data.repeat_state  !== undefined) updateRepeatUI(elements, data.repeat_state);
    } else if (!shuffleLocked && data.shuffle_state !== undefined) {
        updateShuffleUI(elements, data.shuffle_state, shuffleDisallowed);
    } else {
        // Lock active: only update the disallowed visual, keep optimistic state.
        const currentActive = elements.shuffleBtn?.dataset.shuffleState === 'true';
        updateShuffleUI(elements, currentActive, shuffleDisallowed);
    }

    // ── Play / pause icon ────────────────────────────────────────────────
    setPlayPauseIcon(elements.playPauseIcon, isPlaying);

    // ── Track metadata ───────────────────────────────────────────────────
    if (data.item) {
        try {
            const imageUrl   = data.item.album?.images?.[0]?.url;
            const trackName  = data.item.name;
            const artists    = data.item.artists?.map(a => a.name).join(', ');
            const albumName  = data.item.album?.name || 'Unknown Album';
            const duration   = formatTime(durationMs);

            if (trackChanged) {
                updateElementContent('track-image', imageUrl, 'src');
                setTimeout(() => {
                    updateElementContent('track-name',  trackName);
                    updateElementContent('artist-name', artists);
                    updateElementContent('album-name',  albumName);
                    updateElementContent('duration',    duration);
                }, 50);
            } else {
                updateElementContent('track-image',  imageUrl, 'src');
                updateElementContent('track-name',   trackName);
                updateElementContent('artist-name',  artists);
                updateElementContent('album-name',   albumName);
                updateElementContent('duration',     duration);
            }
        } catch (e) {
            console.error('Error updating player UI:', e);
        }
    }

    return state;
}
