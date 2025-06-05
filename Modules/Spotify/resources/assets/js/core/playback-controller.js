import { updateState } from './state.js';
import { formatTime } from '../utils/index.js';
import { startPlayback, pausePlayback, control, setVolume, startPeriodicUpdates, updatePlayerState } from '../ui/interactions/playback-controls.js'
import { startDrag, drag, endDrag, seekOnClick, seekToPosition } from '../ui/interactions/track-progress.js';
import { checkIfTrackIsLiked, toggleLike, updateLikeButton } from '../ui/interactions/like.js';
import { loadUserPlaylists, renderUserPlaylists, shufflePlayPlaylist } from '../ui/interactions/playlists.js';
import { loadUpcomingTrack, renderNextTrack } from '../ui/interactions/upcoming-track.js';
import { updatePlayerUI } from '../ui/player-renderer.js';

/**
 * ToDo refactor to clear up
 */
export default class PlaybackController {
    constructor(elements, state) {
        this.elements = elements;
        this.state = state;

        this.updatePlayerState = this.updatePlayerState.bind(this);
        this.startPlayback = this.startPlayback.bind(this);
        this.pausePlayback = this.pausePlayback.bind(this);
        this.control = this.control.bind(this);
        this.setVolume = this.setVolume.bind(this);
        this.toggleLike = this.toggleLike.bind(this);
        this.shufflePlayPlaylist = this.shufflePlayPlaylist.bind(this);
        this.drag = this.drag.bind(this);
        this.startDrag = this.startDrag.bind(this);
        this.endDrag = this.endDrag.bind(this);
        this.seekOnClick = this.seekOnClick.bind(this);
        this.applyPlaybackState = this.applyPlaybackState.bind(this);
    }

    applyPlaybackState(playbackState) {
        this.state = updatePlayerUI(this.state, this.elements, playbackState, updateState, formatTime);
        return this.state;
    }

    updatePlayerState() {
        return updatePlayerState(
            this.state,
            this.elements,
            data => this.applyPlaybackState(data),
            updateState,
            trackId =>
                checkIfTrackIsLiked(this.state, this.elements, updateState, updateLikeButton, trackId).then(newState => {
                    this.state = newState;
                    return newState;
                }),
            () => loadUpcomingTrack(this.elements, (els, track) => renderNextTrack(els, this.startPlayback, track))
        ).then(newState => {
            this.state = newState;
            return this.state;
        });
    }

    startPlayback(uri = null) {
        return startPlayback(this.elements, this.updatePlayerState, uri);
    }

    pausePlayback() {
        return pausePlayback(this.elements, this.updatePlayerState);
    }

    control(action) {
        return control(this.elements, this.updatePlayerState, action);
    }

    setVolume(volume) {
        return setVolume(this.elements, this.updatePlayerState, volume);
    }

    toggleLike() {
        return toggleLike(this.state, this.elements, updateState, updateLikeButton).then(newState => {
            this.state = newState || this.state;
        });
    }

    shufflePlayPlaylist(uri) {
        return shufflePlayPlaylist(this.elements, this.updatePlayerState, uri);
    }

    drag(e) {
        drag(this.state, this.elements, formatTime, e);
    }

    startDrag(e) {
        this.state = startDrag(this.state, updateState, this.drag, e);
    }

    endDrag(e) {
        this.state = endDrag(
            this.state,
            this.elements,
            updateState,
            (els, pos) => seekToPosition(els, this.updatePlayerState, pos),
            e
        );
    }

    seekOnClick(e) {
        seekOnClick(this.state, this.elements, (els, pos) => seekToPosition(els, this.updatePlayerState, pos), e);
    }

    start() {
        this.state = startPeriodicUpdates(this.state, this.updatePlayerState, updateState);
        loadUserPlaylists(this.elements, (els, playlists) => renderUserPlaylists(els, playlists, this.updatePlayerState));
        loadUpcomingTrack(this.elements, (els, track) => renderNextTrack(els, this.startPlayback, track));
    }
}
