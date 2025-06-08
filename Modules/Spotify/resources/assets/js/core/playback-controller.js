import { setupPlaybackInteractions } from './interactions/playback-interactions.js';
import { setupLikeInteractions } from './interactions/like-interactions.js';
import { setupPlaylistInteractions } from './interactions/playlist-interactions.js';
import { updatePlayerState } from './services/player-service.js';
import { startPlayback, pausePlayback, control, startPeriodicUpdates } from '../ui/interactions/playback-controls.js';
import { updatePlayerUI } from '../ui/player-renderer.js';
import { setVolume } from '../ui/interactions/volume.js';
import { updateState } from './state.js';
import { formatTime } from '../utils/index.js';

export default class PlaybackController {
    constructor(elements, state) {
        this.elements = elements;
        this.state = state;

        // Setup modular interactions
        Object.assign(this, setupPlaybackInteractions(this));
        this.likeInteractions = setupLikeInteractions(this);
        this.playlistInteractions = setupPlaylistInteractions(this);
    }

    applyPlaybackState = (playbackState) => {
        this.state = updatePlayerUI(this.state, this.elements, playbackState, updateState, formatTime);
        return this.state;
    }

    updatePlayerState = (onStateUpdate) => {
        return updatePlayerState(this, onStateUpdate);
    }

    startPlayback = (uri = null) => {
        return startPlayback(this.elements, this.updatePlayerState, uri);
    }

    pausePlayback = () => {
        return pausePlayback(this.elements, this.updatePlayerState);
    }

    control = (action) => {
        return control(this.elements, this.updatePlayerState, action);
    }

    toggleLike = () => {
        return this.likeInteractions.toggleLike();
    }

    setVolume = (volume) => {
        return setVolume(this.elements, this.updatePlayerState, volume);
    }

    start = () => {
        this.state = startPeriodicUpdates(this.state, this.updatePlayerState, updateState);
        this.playlistInteractions.loadUserPlaylists();
    }
}
