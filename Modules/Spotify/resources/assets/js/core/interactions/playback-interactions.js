import { drag, startDrag, endDrag, seekOnClick, seekToPosition } from '../../ui/interactions/track-progress.js';
import { formatTime } from '../../utils/index.js';
import { updateState } from '../state.js';

export function setupPlaybackInteractions(controller) {
    return {
        drag: e => drag(controller.state, controller.elements, formatTime, e),

        startDrag: e => {
            controller.state = startDrag(controller.state, updateState, controller.drag, e);
        },

        endDrag: e => {
            controller.state = endDrag(
                controller.state,
                controller.elements,
                updateState,
                (els, pos) => seekToPosition(els, controller.updatePlayerState, pos),
                e
            );
        },

        seekOnClick: e => {
            seekOnClick(controller.state, controller.elements, (els, pos) => seekToPosition(els, controller.updatePlayerState, pos), e);
        }
    };
}
