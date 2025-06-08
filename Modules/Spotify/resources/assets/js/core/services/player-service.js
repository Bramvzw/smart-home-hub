import { updatePlayerState as updatePlayerStateFn, updatePlayerUI } from '../../ui/interactions/playback-controls.js';
import { updateState } from '../state.js';
import { loadUpcomingTrack, renderNextTrack } from '../../ui/interactions/upcoming-track.js';

export function updatePlayerState(controller) {

    return updatePlayerStateFn(
        controller.state,
        controller.elements,
        updatePlayerUI,
        updateState,
        trackId => controller.likeInteractions.checkIfTrackIsLiked(trackId),
        () => loadUpcomingTrack(controller.elements, (els, track) => renderNextTrack(els, controller.startPlayback, track))
    ).then(newState => {
        controller.state = newState;
        return newState;
    });
}
