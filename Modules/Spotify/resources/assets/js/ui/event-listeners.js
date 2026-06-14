function debounce(fn, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

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
        syncVolumeInputs(this.value);
        volumeTimeout = setTimeout(() => setVolume(this.value), 300);
    });

    // Progress bar drag functionality
    if (elements.progressContainer) {
        elements.progressContainer.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);
        elements.progressContainer.addEventListener('click', debounce(seekOnClick, 300));
    }

    document.querySelectorAll('[data-spotify-control]').forEach(button => {
        button.addEventListener('click', () => {
            const action = button.dataset.spotifyControl;

            if (action === 'toggle-play') {
                getState().isPlaying ? pausePlayback() : startPlayback();
                return;
            }

            if (action === 'previous') {
                control('previous');
                return;
            }

            if (action === 'next') {
                control('next');
                return;
            }

            if (action === 'like') {
                toggleLike();
                return;
            }

            if (action === 'shuffle') {
                elements.shuffleBtn?.click();
                return;
            }

            if (action === 'repeat') {
                elements.repeatBtn?.click();
                return;
            }

            if (action === 'mute') {
                const current = parseInt(elements.volumeSlider?.value || '0', 10);
                const next = current > 0 ? 0 : 70;
                syncVolumeInputs(next);
                setVolume(next);
            }
        });
    });

    document.querySelectorAll('[data-volume-proxy]').forEach(input => {
        input.addEventListener('input', function () {
            clearTimeout(volumeTimeout);
            syncVolumeInputs(this.value);
            volumeTimeout = setTimeout(() => setVolume(this.value), 300);
        });
    });
}

function syncVolumeInputs(value) {
    document.querySelectorAll('#volume-slider, [data-volume-proxy]').forEach(input => {
        input.value = value;
    });
}
