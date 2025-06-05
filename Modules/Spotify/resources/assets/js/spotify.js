// Import modules
import { getElements } from './modules/elements.js';
import { createInitialState, updateState } from './modules/state.js';
import {
    formatTime,
    postOptions,
    updateElementContent,
    showAlert,
    showErrorMessage,
    showSuccessMessage,
    handleResponse,
    displayMessage
} from './modules/utils.js';
import {
    startPlayback,
    pausePlayback,
    control,
    setVolume,
    startPeriodicUpdates,
    stopPeriodicUpdates,
    updatePlayerState,
    updatePlayerUI
} from './modules/player-controls.js';
import {
    startDrag,
    drag,
    endDrag,
    seekOnClick,
    seekToPosition
} from './modules/progress-bar.js';
import {
    checkIfTrackIsLiked,
    toggleLike,
    updateLikeButton
} from './modules/like.js';
import {
    loadUserPlaylists,
    displayPlaylistMessage,
    renderUserPlaylists,
    shufflePlayPlaylist
} from './modules/playlists.js';
import {
    loadNextTrack,
    displayNextTrackMessage,
    renderNextTrack
} from './modules/next-track.js';

document.addEventListener('DOMContentLoaded', function () {
    // Get DOM Elements
    const elements = getElements();

    // Create initial player state
    let state = createInitialState();

    // Initialize player UI with the initial state if available
    if (window.SPOTIFY_STATE) {
        state = updatePlayerUI(state, elements, window.SPOTIFY_STATE, updateState, formatTime);
    }

    // Create wrapper functions that bind the necessary parameters
    const updatePlayerStateFn = () => updatePlayerState(
        state,
        elements,
        (data) => updatePlayerUI(state, elements, data, updateState, formatTime),
        updateState,
        (trackId) => checkIfTrackIsLiked(state, elements, updateState, updateLikeButton, trackId),
        () => loadNextTrack(elements, (elements, track) => renderNextTrack(elements, startPlaybackFn, track))
    );

    const startPlaybackFn = (uri = null) => startPlayback(elements, updatePlayerStateFn, uri);
    const pausePlaybackFn = () => pausePlayback(elements, updatePlayerStateFn);
    const controlFn = (action) => control(elements, updatePlayerStateFn, action);
    const setVolumeFn = (volume) => setVolume(elements, updatePlayerStateFn, volume);
    const toggleLikeFn = () => toggleLike(state, elements, updateState, updateLikeButton);
    const shufflePlayPlaylistFn = (uri) => shufflePlayPlaylist(elements, updatePlayerStateFn, uri);

    const dragFn = (e) => drag(state, elements, formatTime, e);
    const startDragFn = (e) => {
        state = startDrag(state, updateState, dragFn, e);
    };
    const endDragFn = (e) => {
        state = endDrag(
            state,
            elements,
            updateState,
            (elements, positionMs) => seekToPosition(elements, updatePlayerStateFn, positionMs),
            e
        );
    };
    const seekOnClickFn = (e) => seekOnClick(
        state,
        elements,
        (elements, positionMs) => seekToPosition(elements, updatePlayerStateFn, positionMs),
        e
    );

    // Start periodic updates
    state = startPeriodicUpdates(state, updatePlayerStateFn, updateState);

    // Set up event listeners
    initializeEventListeners();

    // Load user playlists and next track on startup
    loadUserPlaylists(
        elements,
        (elements, playlists) => renderUserPlaylists(elements, playlists, updatePlayerStateFn)
    );

    loadNextTrack(
        elements,
        (elements, track) => renderNextTrack(elements, startPlaybackFn, track)
    );

    /**
     * Initialize all event listeners for player controls
     */
    function initializeEventListeners() {
        // Playback controls
        elements.playPauseBtn?.addEventListener('click', () => {
            state.isPlaying ? pausePlaybackFn() : startPlaybackFn();
        });

        elements.previousBtn?.addEventListener('click', () => controlFn('previous'));
        elements.nextBtn?.addEventListener('click', () => controlFn('next'));
        elements.likeBtn?.addEventListener('click', toggleLikeFn);

        // Volume control
        let volumeTimeout;
        elements.volumeSlider?.addEventListener('input', function () {
            clearTimeout(volumeTimeout);
            volumeTimeout = setTimeout(() => setVolumeFn(this.value), 300);
        });

        // Progress bar drag functionality
        if (elements.progressContainer) {
            elements.progressContainer.addEventListener('mousedown', startDragFn);
            document.addEventListener('mousemove', dragFn);
            document.addEventListener('mouseup', endDragFn);
            elements.progressContainer.addEventListener('click', seekOnClickFn);
        }
    }

    // All functions have been moved to their respective modules

});
