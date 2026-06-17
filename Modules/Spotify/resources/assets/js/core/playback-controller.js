import { setupPlaybackInteractions } from './interactions/playback-interactions.js';
import { setupLikeInteractions } from './interactions/like-interactions.js';
import { setupPlaylistInteractions } from './interactions/playlist-interactions.js';
import { updatePlayerState } from './services/player-service.js';
import { startPlayback, pausePlayback, control, startPeriodicUpdates, startProgressTicker } from '../ui/interactions/playback-controls.js';
import { updatePlayerUI, setPlayPauseIcon } from '../ui/player-renderer.js';
import { setVolume } from '../ui/interactions/volume.js';
import { updateState } from './state.js';
import { formatTime } from '../utils/index.js';

export default class PlaybackController {
    constructor(elements, state) {
        this.elements = elements;
        this.state    = state;

        Object.assign(this, setupPlaybackInteractions(this));
        this.likeInteractions    = setupLikeInteractions(this);
        this.playlistInteractions = setupPlaylistInteractions(this);
    }

    applyPlaybackState = (playbackState) => {
        this.state = updatePlayerUI(this.state, this.elements, playbackState, updateState, formatTime);
        return this.state;
    }

    updatePlayerState = () => updatePlayerState(this);

    startPlayback = (uri = null) => {
        this._setPlayingState(true);
        return startPlayback(this.elements, this.updatePlayerState, uri);
    }

    pausePlayback = () => {
        this._setPlayingState(false);
        return pausePlayback(this.elements, this.updatePlayerState);
    }

    // Optimistically update play/pause state so the icon responds immediately,
    // without waiting for the next poll to confirm.
    _setPlayingState = (isPlaying) => {
        this.state = updateState(this.state, {
            isPlaying,
            progressAt: isPlaying ? Date.now() : null,
        });
        setPlayPauseIcon(this.elements.playPauseIcon, isPlaying);
    }

    control = (action) => {
        // Reset progress bar immediately and flag that a skip is in-flight.
        // The polling lock in player-service.js prevents any stale in-flight poll
        // from overwriting this. The 500 ms retry in player-service.js keeps
        // polling until Spotify confirms the new track.
        this.state = updateState(this.state, {
            progressMs:  0,
            progressAt:  null,
            durationMs:  0,
            skipPending: true,
        });

        // After the HTTP response, use forcePoll (cancels the periodic timer)
        // so there is only one poll in-flight at a time.
        return control(this.elements, () => this.forcePoll?.(), action);
    }

    toggleLike = () => this.likeInteractions.toggleLike();

    setVolume = (volume) => setVolume(this.elements, this.updatePlayerState, volume);

    start = () => {
        const { state, forcePoll, stop } = startPeriodicUpdates(
            this.state, this.updatePlayerState, updateState, () => this.state
        );
        this.state    = state;
        this.forcePoll = forcePoll;
        this.stopPolling = stop;
        startProgressTicker(() => this.state, this.elements, formatTime, forcePoll);
        this.playlistInteractions.loadUserPlaylists();

        if (this.state.currentTrackId) {
            this.likeInteractions.checkIfTrackIsLiked(this.state.currentTrackId);
        }
    }

    // Stop the poll loop. Used on wire:navigate teardown so the always-on
    // kiosk never accumulates orphaned polling timers.
    teardown = () => {
        this.stopPolling?.();
        this.stopPolling = null;
        this.forcePoll = null;
    }
}
