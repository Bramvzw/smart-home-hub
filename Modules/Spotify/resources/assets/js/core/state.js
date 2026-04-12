/**
 * State module
 */

export function createInitialState() {
    const isPlaying = window.SPOTIFY_STATE?.is_playing ?? false;
    return {
        isPlaying,
        currentTrackId: window.SPOTIFY_STATE?.item?.id ?? null,
        isTrackLiked: false,
        isDragging: false,
        // Progress anchor: ticker renders progressMs + (Date.now() - progressAt)
        progressMs: window.SPOTIFY_STATE?.progress_ms ?? 0,
        progressAt: isPlaying ? Date.now() : null,
        durationMs: window.SPOTIFY_STATE?.item?.duration_ms ?? 0,
        // True after a skip until Spotify returns the new track
        skipPending: false,
        updateInterval: null,
    };
}

export function updateState(state, newValues) {
    return { ...state, ...newValues };
}
