import { toggleLike, updateLikeButton, checkIfTrackIsLiked } from '../../ui/interactions/like.js';
import { updateState } from '../state.js';

export function setupLikeInteractions(controller) {
    return {
        toggleLike: () => {
            return toggleLike(controller.state, controller.elements, updateState, updateLikeButton).then(newState => {
                controller.state = newState || controller.state;
            });
        },

        checkIfTrackIsLiked: trackId => {
            return checkIfTrackIsLiked(controller.state, controller.elements, updateState, updateLikeButton, trackId).then(newState => {
                controller.state = newState;
                return newState;
            });
        }
    };
}
