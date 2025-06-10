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
            const imageUrl = playbackState.item.album?.images?.[0]?.url;
            const trackName = playbackState.item.name;
            const artistNames = playbackState.item.artists?.map(a => a.name).join(', ');
            const albumName = playbackState.item.album?.name || 'Unknown Album';
            const durationFormatted = formatTime(playbackState.item.duration_ms || 0);

            // Check if track has changed to apply a coordinated update
            const currentTrackId = state.currentTrackId;
            const newTrackId = playbackState.item?.id;
            const hasTrackChanged = currentTrackId !== newTrackId;

            // If track has changed, add a small delay before updating UI
            // This allows CSS transitions to work properly in a coordinated way
            if (hasTrackChanged) {
                // First update the image (which has its own preloading mechanism)
                updateElementContent('track-image', imageUrl, 'src');

                // Then update all text elements together
                setTimeout(() => {
                    updateElementContent('track-name', trackName);
                    updateElementContent('artist-name', artistNames);
                    updateElementContent('album-name', albumName);
                    updateElementContent('duration', durationFormatted);
                }, 50); // Small delay for smoother transition
            } else {
                // If track hasn't changed, update normally
                updateElementContent('track-image', imageUrl, 'src');
                updateElementContent('track-name', trackName);
                updateElementContent('artist-name', artistNames);
                updateElementContent('album-name', albumName);
                updateElementContent('duration', durationFormatted);
            }

            if (!state.isDragging) {
                const progressMs = playbackState.progress_ms || 0;
                const durationMs = playbackState.item.duration_ms || 1;
                const progressPercentage = (progressMs / durationMs) * 100;

                updateElementContent('current-time', formatTime(progressMs));

                if (elements.progressBar) {
                    elements.progressBar.style.width = `${progressPercentage}%`;
                }
            }
        } catch (error) {
            console.error('Error updating player UI:', error);
        }
    }

    return state;
}
