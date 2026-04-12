import { getElements } from '../ui/elements.js';
import { createInitialState } from './state.js';
import { initializeEventListeners } from '../ui/event-listeners.js';
import PlaybackController from '../core/playback-controller.js';
import { setupShuffle, updateShuffleUI } from '../ui/interactions/shuffle.js';
import { setupRepeat, updateRepeatUI } from '../ui/interactions/repeat.js';
import { setupTabs } from '../ui/interactions/tabs.js';
import { loadQueue } from '../ui/interactions/queue.js';
import { loadRecentlyPlayed } from '../ui/interactions/recent.js';
import { setupSearch } from '../ui/interactions/search.js';
import { setupDevices } from '../ui/interactions/devices.js';
import { loadUpcomingTrack, renderNextTrack } from '../ui/interactions/upcoming-track.js';

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

        // New features
        const refreshNextUp = (delay = 500) => {
            setTimeout(() => {
                loadUpcomingTrack(this.elements, (els, track) => renderNextTrack(els, this.controller.startPlayback, track));
                // Also refresh queue panel if it's currently visible
                const queuePanel = document.getElementById('panel-queue');
                if (queuePanel && !queuePanel.classList.contains('hidden')) {
                    loadQueue(this.elements, this.controller.startPlayback);
                }
            }, delay);
        };

        const refreshNextUpAfterShuffle = () => {
            // Retry up to 4 times (500ms apart) until Spotify reorders the queue
            const currentName = document.getElementById('next-track-name')?.textContent;
            let attempts = 0;
            const tryRefresh = () => {
                loadUpcomingTrack(this.elements, (els, track) => {
                    renderNextTrack(els, this.controller.startPlayback, track);
                    // Retry if the name didn't change and we have attempts left
                    if (attempts < 4 && track?.name === currentName) {
                        attempts++;
                        setTimeout(tryRefresh, 500);
                    }
                });
            };
            setTimeout(tryRefresh, 500);
        };

        setupShuffle(this.elements, () => refreshNextUpAfterShuffle());
        setupDevices(this.elements);
        setupRepeat(this.elements, () => refreshNextUp());
        setupSearch(this.elements, this.controller.startPlayback, this.controller.updatePlayerState);

        // Initialize UI from initial state
        if (window.SPOTIFY_STATE?.shuffle_state !== undefined) {
            updateShuffleUI(this.elements, window.SPOTIFY_STATE.shuffle_state);
        }
        if (window.SPOTIFY_STATE?.repeat_state) {
            updateRepeatUI(this.elements, window.SPOTIFY_STATE.repeat_state);
        }

        let recentLoaded = false;
        setupTabs((tabId) => {
            if (tabId === 'panel-queue') {
                loadQueue(this.elements, this.controller.startPlayback);
            }
            if (tabId === 'panel-recent' && !recentLoaded) {
                recentLoaded = true;
                loadRecentlyPlayed(this.elements, this.controller.startPlayback);
            }
        });
    }
}

let _spotifyState = window.SPOTIFY_STATE;
let _playerInstance = null;

export function initSpotifyPlayer() {
    _playerInstance = new SpotifyPlayer();
    _playerInstance.start();
}

Object.defineProperty(window, 'SPOTIFY_STATE', {
    get() { return _spotifyState; },
    set(value) {
        _spotifyState = value;
        if (_playerInstance) {
            _playerInstance.controller.applyPlaybackState(value);
        } else {
            initSpotifyPlayer();
        }
    }
});

document.addEventListener('DOMContentLoaded', initSpotifyPlayer);
