import { loadUserPlaylists, renderUserPlaylists, shufflePlayPlaylist } from '../../ui/interactions/playlists.js';

export function setupPlaylistInteractions(controller) {
    return {
        loadUserPlaylists: () => {
            loadUserPlaylists(controller.elements, (els, playlists) => renderUserPlaylists(els, playlists, controller.updatePlayerState));
        },

        shufflePlayPlaylist: uri => {
            return shufflePlayPlaylist(controller.elements, controller.updatePlayerState, uri);
        }
    };
}
