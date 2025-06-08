import { getElements } from '../ui/elements.js';
import { createInitialState } from './state.js';
import { initializeEventListeners } from '../ui/event-listeners.js';
import PlaybackController from '../core/playback-controller.js';

/**
 * Main class that coordinates the Spotify Player's UI and logic.
 */
class SpotifyPlayer {
    constructor() {
        this.elements = getElements();
        this.controller = new PlaybackController(this.elements, createInitialState());

        if (window.SPOTIFY_STATE) {
            this.controller.applyPlaybackState(window.SPOTIFY_STATE);
        }
    }

    /**
     * Start the player logic and attach UI event listeners.
     */
    start() {
        this.controller.start();

        initializeEventListeners(this.elements, () => this.controller.state, {
            startPlayback: this.controller.startPlayback,
            pausePlayback: this.controller.pausePlayback,
            control: this.controller.control,
            toggleLike: this.controller.toggleLike,
            setVolume: this.controller.setVolume,
            startDrag: this.controller.startDrag,
            drag: this.controller.drag,
            endDrag: this.controller.endDrag,
            seekOnClick: this.controller.seekOnClick
        });
    }
}

/**
 * Entry point to create and start the Spotify player.
 * Called on initial page load or whenever window.SPOTIFY_STATE changes.
 */
export function initSpotifyPlayer() {
    new SpotifyPlayer().start();
}

document.addEventListener('DOMContentLoaded', initSpotifyPlayer);

/**
 * Keep track of the global playback state (SPOTIFY_STATE).
 * Whenever it changes, re-initialize the player to reflect the new state.
 * This is useful for dynamic updates (e.g., automated tests, other app logic).
 */
let _spotifyState = window.SPOTIFY_STATE;
Object.defineProperty(window, 'SPOTIFY_STATE', {
    get() { return _spotifyState; },
    set(value) {
        _spotifyState = value;
        initSpotifyPlayer();
    }
});
