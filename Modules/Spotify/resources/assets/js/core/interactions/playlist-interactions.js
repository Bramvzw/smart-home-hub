import { setupPlaylistEventListeners, shufflePlayPlaylist } from '../../ui/interactions/playlists.js';

export function setupPlaylistInteractions(controller) {
    return {
        loadUserPlaylists: () => {
            setupPlaylistEventListeners(controller.elements, controller.updatePlayerState);
        },

        shufflePlayPlaylist: uri => {
            return shufflePlayPlaylist(controller.elements, controller.updatePlayerState, uri);
        }
    };
}
