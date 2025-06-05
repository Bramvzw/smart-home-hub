/**
 * Player UI module
 * Handles updating the DOM with the current playback state
 */
import {updateElementContent} from '../utils/index.js';

export function updatePlayerUI(state, elements, playbackState, updateState, formatTime) {
    // Update state
    state = updateState(state, {
        isPlaying: playbackState.is_playing,
        currentDuration: playbackState.item?.duration_ms || 0
    });

    // Update play/pause button icon
    if (elements.playPauseIcon) {
        elements.playPauseIcon.classList.toggle('fa-pause', state.isPlaying);
        elements.playPauseIcon.classList.toggle('fa-play', !state.isPlaying);
    }

    // Update track information if available
    if (playbackState.item) {
        try {
            const imageUrl = playbackState.item.album?.images?.[0]?.url || '';

            updateElementContent('track-image', imageUrl, 'src');
            updateElementContent('track-name', playbackState.item.name || 'Unknown Track');
            updateElementContent(
                'artist-name',
                playbackState.item.artists?.map(a => a.name).join(', ') || 'Unknown Artist'
            );
            updateElementContent('album-name', playbackState.item.album?.name || 'Unknown Album');
            updateElementContent('duration', formatTime(playbackState.item.duration_ms || 0));

            if (!state.isDragging) {
                const progressMs = playbackState.progress_ms || 0;
                const durationMs = playbackState.item.duration_ms || 1;
                const progressPercentage = (progressMs / durationMs) * 100;

                updateElementContent('current-time', formatTime(progressMs));

                if (elements.progressBar) {
                    elements.progressBar.style.width = `${progressPercentage}%`;
                }
            }
        } catch {
        }
    }

    return state;
}
