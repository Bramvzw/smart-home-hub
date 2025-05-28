document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const previousBtn = document.getElementById('previous-btn');
    const nextBtn = document.getElementById('next-btn');
    const volumeSlider = document.getElementById('volume-slider');
    let isPlaying = window.SPOTIFY_STATE?.is_playing ?? false;

    // Initialize player UI with the initial state if available
    if (window.SPOTIFY_STATE) {
        updatePlayerUI(window.SPOTIFY_STATE);
    }

    // Update player state periodically
    setInterval(updatePlayerState, 1000);

    playPauseBtn?.addEventListener('click', () => {
        isPlaying ? pausePlayback() : startPlayback();
    });

    previousBtn?.addEventListener('click', () => control('previous'));
    nextBtn?.addEventListener('click', () => control('next'));

    let volumeTimeout;
    volumeSlider?.addEventListener('input', function () {
        clearTimeout(volumeTimeout);
        volumeTimeout = setTimeout(() => setVolume(this.value), 300);
    });

    function startPlayback() {
        fetch('/spotify/play', postOptions()).then(r => null);
    }

    function pausePlayback() {
        fetch('/spotify/pause', postOptions()).then(handleResponse);
    }

    function control(action) {
        fetch(`/spotify/${action}`, postOptions()).then(handleResponse);
    }

    function setVolume(volume) {
        fetch('/spotify/volume', {
            ...postOptions(),
            body: JSON.stringify({ volume })
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success && data.code === 'volume_control_not_supported') {
                    showErrorMessage('This device does not support volume control.');
                    setTimeout(updatePlayerState, 500);
                }
            });
    }

    function updatePlayerState() {
        fetch('/spotify/playback-state')
            .then(res => res.json())
            .then(data => {
                if (data.success) updatePlayerUI(data);
            });
    }

    function updatePlayerUI(state) {
        isPlaying = state.is_playing;
        if (playPauseBtn) {
            playPauseBtn.innerHTML = isPlaying
                ? '<i class="fas fa-pause text-xl"></i>'
                : '<i class="fas fa-play text-xl"></i>';
        }
        document.getElementById('track-image').src = state.item?.album.images[0].url ?? '';
        document.getElementById('current-time').textContent = formatTime(state.progress_ms);
        document.getElementById('track-name').textContent = state.item?.name ?? '';
        document.getElementById('artist-name').textContent = state.item?.artists.map(a => a.name).join(', ') ?? '';
        document.getElementById('album-name').textContent = state.item?.album.name ?? '';
        document.getElementById('duration').textContent = formatTime(state.item?.duration_ms ?? 0);
        document.getElementById('progress-bar').style.width = `${(state.progress_ms / state.item?.duration_ms) * 100}%`;
    }

    function postOptions() {
        return {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        };
    }

    function formatTime(ms) {
        const m = Math.floor(ms / 60000);
        const s = Math.floor((ms % 60000) / 1000);
        return `${m}:${s < 10 ? '0' : ''}${s}`;
    }

    function showErrorMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-md';
        alert.innerHTML = `
            <p>${message}</p>
            <button class="absolute top-2 right-2 text-red-700" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    function handleResponse(response) {
        return response.json()
            .then(data => {
                if (data.success) {
                    updatePlayerState();
                    return data;
                } else if (data.error) {
                    showErrorMessage(data.error);
                    throw new Error(data.error);
                }
                return data;
            })
            .catch(error => {
                console.error('Spotify API error:', error);
                showErrorMessage('An error occurred with the Spotify API');
            });
    }
});
