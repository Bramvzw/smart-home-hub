import { updatePlayerUI } from '../../ui/player-renderer.js';
import { updateState } from '../state.js';
import { formatTime, handleAuthError } from '../../utils/index.js';
import { loadUpcomingTrack, renderNextTrack } from '../../ui/interactions/upcoming-track.js';

let polling = false;
let consecutiveErrors = 0;

export function updatePlayerState(controller) {
    if (polling || controller.state.isDragging) {
        return Promise.resolve(controller.state);
    }

    polling = true;

    return fetch('/spotify/playback-state')
        .then(res => {
            if (res.status === 401) {
                throw Object.assign(new Error('auth_required'), { status: 401 });
            }
            return res.json();
        })
        .then(data => {
            if (!data.success) return;

            consecutiveErrors = 0;

            const prevTrackId = controller.state.currentTrackId;
            controller.state  = updatePlayerUI(
                controller.state, controller.elements, data, updateState, formatTime
            );

            if (controller.state.currentTrackId !== prevTrackId && controller.state.currentTrackId) {
                controller.likeInteractions.checkIfTrackIsLiked(controller.state.currentTrackId);
                loadUpcomingTrack(
                    controller.elements,
                    (els, track) => renderNextTrack(els, controller.startPlayback, track)
                );
            }

            if (controller.state.skipPending) {
                setTimeout(() => controller.forcePoll?.(), 500);
            }
        })
        .catch((err) => {
            consecutiveErrors++;
            handleAuthError(err, controller.elements);
        })
        .finally(() => { polling = false; });
}
