import { updatePlayerUI } from '../../ui/player-renderer.js';
import { updateState } from '../state.js';
import { formatTime } from '../../utils/index.js';
import { loadUpcomingTrack, renderNextTrack } from '../../ui/interactions/upcoming-track.js';

// Sequential lock: only one poll in-flight at a time.
// This eliminates all race conditions between concurrent polls — no sequence
// counters or other machinery needed.
let polling = false;

export function updatePlayerState(controller) {
    if (polling || controller.state.isDragging) {
        return Promise.resolve(controller.state);
    }

    polling = true;

    return fetch('/spotify/playback-state')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            const prevTrackId = controller.state.currentTrackId;
            controller.state  = updatePlayerUI(
                controller.state, controller.elements, data, updateState, formatTime
            );

            // Side-effects when the track changed
            if (controller.state.currentTrackId !== prevTrackId && controller.state.currentTrackId) {
                controller.likeInteractions.checkIfTrackIsLiked(controller.state.currentTrackId);
                loadUpcomingTrack(
                    controller.elements,
                    (els, track) => renderNextTrack(els, controller.startPlayback, track)
                );
            }

            // Spotify hasn't processed the skip yet — retry soon
            if (controller.state.skipPending) {
                setTimeout(() => controller.forcePoll?.(), 500);
            }
        })
        .catch(() => {})
        .finally(() => { polling = false; });
}
