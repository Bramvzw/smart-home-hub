export function initializeEventListeners(elements, getState, callbacks) {
    const {
        startPlayback,
        pausePlayback,
        control,
        toggleLike,
        setVolume,
        startDrag,
        drag,
        endDrag,
        seekOnClick
    } = callbacks;

    // Playback controls
    elements.playPauseBtn?.addEventListener('click', () => {
        getState().isPlaying ? pausePlayback() : startPlayback();
    });

    elements.previousBtn?.addEventListener('click', () => control('previous'));
    elements.nextBtn?.addEventListener('click', () => control('next'));
    elements.likeBtn?.addEventListener('click', toggleLike);

    // Volume control
    let volumeTimeout;
    elements.volumeSlider?.addEventListener('input', function () {
        clearTimeout(volumeTimeout);
        volumeTimeout = setTimeout(() => setVolume(this.value), 300);
    });

    // Progress bar drag functionality
    if (elements.progressContainer) {
        elements.progressContainer.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);
        elements.progressContainer.addEventListener('click', seekOnClick);
    }
}
