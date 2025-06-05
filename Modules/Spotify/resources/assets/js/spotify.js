document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const playPauseIcon = document.getElementById('play-pause-icon');
    const previousBtn = document.getElementById('previous-btn');
    const nextBtn = document.getElementById('next-btn');
    const likeBtn = document.getElementById('like-btn');
    const likeIcon = document.getElementById('like-icon');
    const volumeSlider = document.getElementById('volume-slider');
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const recentlyPlayedContainer = document.getElementById('recently-played-container');
    const nextTrackContainer = document.getElementById('next-track');
    const playlistTemplate = document.getElementById('playlist-item-template');
    const nextTrackTemplate = document.getElementById('next-track-template');
    const alertTemplate = document.getElementById('alert-template');
    const messageTemplate = document.getElementById('message-template');

    let isPlaying = window.SPOTIFY_STATE?.is_playing ?? false;
    let currentTrackId = null;
    let isTrackLiked = false;
    let isDragging = false;
    let currentDuration = 0;
    let updateInterval;

    // Initialize player UI with the initial state if available
    if (window.SPOTIFY_STATE) {
        updatePlayerUI(window.SPOTIFY_STATE);
    }

    // Start periodic updates
    startPeriodicUpdates();

    // Event listeners for player controls
    playPauseBtn?.addEventListener('click', () => {
        isPlaying ? pausePlayback() : startPlayback();
    });

    previousBtn?.addEventListener('click', () => control('previous'));
    nextBtn?.addEventListener('click', () => control('next'));

    likeBtn?.addEventListener('click', toggleLike);

    let volumeTimeout;
    volumeSlider?.addEventListener('input', function () {
        clearTimeout(volumeTimeout);
        volumeTimeout = setTimeout(() => setVolume(this.value), 300);
    });

    // Progress bar drag functionality
    if (progressContainer) {
        progressContainer.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);
        progressContainer.addEventListener('click', seekOnClick);
    }

    // Load user playlists and next track on startup
    loadUserPlaylists();
    loadNextTrack();

    function startPlayback(uri = null) {
        const options = postOptions();
        if (uri) {
            options.body = JSON.stringify({ uri });
        }
        fetch('/spotify/play', options).then(handleResponse);
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

    function startPeriodicUpdates() {
        // Clear any existing interval
        if (updateInterval) {
            clearInterval(updateInterval);
        }

        // Update immediately
        updatePlayerState();

        // Then set up interval for future updates
        updateInterval = setInterval(updatePlayerState, 1000);
    }

    function stopPeriodicUpdates() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }

    function updatePlayerState() {
        // Don't update while dragging
        if (isDragging) return;

        fetch('/spotify/playback-state')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updatePlayerUI(data);

                    // If track changed, update like status and next track
                    if (currentTrackId !== data.item?.id) {
                        currentTrackId = data.item?.id;
                        if (currentTrackId) {
                            checkIfTrackIsLiked(currentTrackId);
                            loadNextTrack();
                        }
                    }
                } else {
                    console.error('Failed to get playback state:', data);
                }
            })
            .catch(error => {
                console.error('Error fetching playback state:', error);
            });
    }

    function updatePlayerUI(state) {
        isPlaying = state.is_playing;
        currentDuration = state.item?.duration_ms || 0;

        if (playPauseIcon) {
            playPauseIcon.classList.toggle('fa-pause', isPlaying);
            playPauseIcon.classList.toggle('fa-play', !isPlaying);
        }

        if (state.item) {
            try {
                // Update track details
                document.getElementById('track-image').src = state.item.album.images[0].url || '';
                document.getElementById('track-name').textContent = state.item.name || 'Unknown Track';
                document.getElementById('artist-name').textContent = state.item.artists.map(a => a.name).join(', ') || 'Unknown Artist';
                document.getElementById('album-name').textContent = state.item.album.name || 'Unknown Album';
                document.getElementById('duration').textContent = formatTime(state.item.duration_ms || 0);

                // Only update progress if not dragging
                if (!isDragging) {
                    // Log the progress values
                    document.getElementById('current-time').textContent = formatTime(state.progress_ms || 0);
                    document.getElementById('progress-bar').style.width = `${((state.progress_ms || 0) / (state.item.duration_ms || 1)) * 100}%`;
                }
            } catch (error) {
                console.error('Error updating player UI:', error);
            }
        } else {
            console.warn('No track item in playback state');
        }
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

    function showAlert(message, type) {
        if (!alertTemplate) return;
        const alert = alertTemplate.content.firstElementChild.cloneNode(true);
        alert.querySelector('.message').textContent = message;
        alert.querySelector('.close-btn').addEventListener('click', () => alert.remove());

        if (type === 'error') {
            alert.classList.add('bg-red-100', 'border-l-4', 'border-red-500', 'text-red-700');
            setTimeout(() => alert.remove(), 5000);
        } else {
            alert.classList.add('bg-green-100', 'border-l-4', 'border-green-500', 'text-green-700');
            setTimeout(() => alert.remove(), 3000);
        }

        document.body.appendChild(alert);
    }

    function showErrorMessage(message) {
        showAlert(message, 'error');
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

    // Progress bar drag functionality
    function startDrag(e) {
        isDragging = true;
        drag(e);
    }

    function drag(e) {
        if (!isDragging || !progressContainer) return;

        const rect = progressContainer.getBoundingClientRect();
        const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        const positionMs = Math.floor(position * currentDuration);

        // Update UI
        progressBar.style.width = `${position * 100}%`;
        document.getElementById('current-time').textContent = formatTime(positionMs);
    }

    function endDrag(e) {
        if (!isDragging) return;

        const rect = progressContainer.getBoundingClientRect();
        const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        const positionMs = Math.floor(position * currentDuration);

        // Seek to position
        seekToPosition(positionMs);

        isDragging = false;
    }

    function seekOnClick(e) {
        const rect = progressContainer.getBoundingClientRect();
        const position = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        const positionMs = Math.floor(position * currentDuration);

        // Seek to position
        seekToPosition(positionMs);
    }

    function seekToPosition(positionMs) {
        fetch('/spotify/seek', {
            ...postOptions(),
            body: JSON.stringify({ position_ms: positionMs })
        }).then(handleResponse);
    }

    // Like functionality
    function checkIfTrackIsLiked(trackId) {
        const params = new URLSearchParams();
        params.append('ids[]', trackId);
        fetch(`/spotify/tracks/check?${params.toString()}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.success && data.results && data.results.length > 0) {
                    isTrackLiked = data.results[0];
                    updateLikeButton();
                } else {
                    console.error('Failed to check if track is liked:', data);
                    isTrackLiked = false;
                    updateLikeButton();
                }
            })
            .catch(error => {
                console.error('Error checking if track is liked:', error);
                isTrackLiked = false;
                updateLikeButton();
            });
    }

    function toggleLike() {
        if (!currentTrackId) {
            console.error('No current track ID available for like/unlike');
            showErrorMessage('Cannot like/unlike: No track is playing');
            return;
        }

        fetch('/spotify/tracks/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken // if needed!
            },
            body: JSON.stringify({ id: currentTrackId, saved: !isTrackLiked })
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('Toggle like response:', data);
                if (data.success) {
                    isTrackLiked = data.saved;
                    updateLikeButton();
                } else {
                    console.error('Failed to toggle like:', data);
                    showErrorMessage('Failed to update like status');
                }
            })
            .catch(error => {
                console.error('Error toggling like:', error);
                showErrorMessage('Error updating like status');
            });
    }

    function updateLikeButton() {
        if (likeIcon) {
            likeIcon.classList.toggle('fas', isTrackLiked);
            likeIcon.classList.toggle('far', !isTrackLiked);
            likeBtn.classList.toggle('active', isTrackLiked);
        } else {
            console.error('Like button element not found');
        }
    }


    // User playlists functionality
    function loadUserPlaylists() {
        fetch('/spotify/user-playlists')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.playlists) {
                    renderUserPlaylists(data.playlists);
                } else {
                    console.error('Failed to get user playlists:', data);
                    if (recentlyPlayedContainer && messageTemplate) {
                        recentlyPlayedContainer.innerHTML = '';
                        const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                        msg.textContent = 'No playlists found in your library';
                        recentlyPlayedContainer.appendChild(msg);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching user playlists:', error);
                if (recentlyPlayedContainer && messageTemplate) {
                    recentlyPlayedContainer.innerHTML = '';
                    const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                    msg.textContent = 'Error loading playlists';
                    recentlyPlayedContainer.appendChild(msg);
                }
            });
    }

    function renderUserPlaylists(playlists) {
        if (!recentlyPlayedContainer) {
            console.error('Playlists container not found');
            return;
        }

        if (!playlists || !playlists.length) {
            if (messageTemplate) {
                recentlyPlayedContainer.innerHTML = '';
                const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                msg.textContent = 'No playlists found in your library';
                recentlyPlayedContainer.appendChild(msg);
            }
            return;
        }

        recentlyPlayedContainer.innerHTML = '';

        playlists.forEach(playlist => {
            try {
                // Check if playlist has all required properties
                if (!playlist.images || !playlist.images.length) {
                    console.warn('Playlist missing required properties:', playlist);
                    return;
                }

                if (!playlistTemplate) return;
                const playlistElement = playlistTemplate.content.firstElementChild.cloneNode(true);
                // Special handling for Liked Songs playlist
                if (playlist.id === 'liked-songs') {
                    playlistElement.setAttribute('data-id', 'liked-songs');
                    // We don't set a URI for liked songs as it's handled differently
                } else {
                    playlistElement.setAttribute('data-uri', playlist.uri);
                }

                // Get the best image (prefer larger images)
                const image = playlist.images.sort((a, b) => (b.width || 0) - (a.width || 0))[0];
                const img = playlistElement.querySelector('img.playlist-image');
                if (img) {
                    img.src = image.url;
                    img.alt = playlist.name;
                }

                // Add event listener for clicking on the playlist
                playlistElement.addEventListener('click', function() {
                    // Special handling for Liked Songs playlist
                    if (playlist.id === 'liked-songs') {
                        // For now, just show a message that this feature is coming soon
                        showSuccessMessage('Playing Liked Songs feature coming soon!');
                    } else {
                        shufflePlayPlaylist(playlist.uri);
                    }
                });

                recentlyPlayedContainer.appendChild(playlistElement);
            } catch (error) {
                console.error('Error rendering playlist:', error, playlist);
            }
        });
    }

    function shufflePlayPlaylist(uri) {
        fetch('/spotify/shuffle-play-playlist', {
            ...postOptions(),
            body: JSON.stringify({ uri })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Playing shuffled playlist');
                    updatePlayerState();
                } else {
                    console.error('Failed to shuffle play playlist:', data);
                    showErrorMessage('Failed to play playlist');
                }
            })
            .catch(error => {
                console.error('Error shuffling playlist:', error);
                showErrorMessage('Error playing playlist');
            });
    }

    // Next track functionality
    function loadNextTrack() {
        fetch('/spotify/next-track')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.next_track) {
                    renderNextTrack(data.next_track);
                } else if (nextTrackContainer && messageTemplate) {
                    nextTrackContainer.innerHTML = '';
                    const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                    msg.textContent = 'No upcoming tracks';
                    nextTrackContainer.appendChild(msg);
                }
            })
            .catch(error => {
                console.error('Error fetching next track:', error);
                if (nextTrackContainer && messageTemplate) {
                    nextTrackContainer.innerHTML = '';
                    const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                    msg.textContent = 'Error loading next track';
                    nextTrackContainer.appendChild(msg);
                }
            });
    }

    function renderNextTrack(track) {
        if (!nextTrackContainer) {
            console.error('Next track container not found');
            return;
        }

        if (!track) {
            if (messageTemplate) {
                nextTrackContainer.innerHTML = '';
                const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                msg.textContent = 'No upcoming tracks';
                nextTrackContainer.appendChild(msg);
            }
            return;
        }

        try {
            // Safely access nested properties
            const imageUrl = track.album && track.album.images && track.album.images.length > 0
                ? track.album.images[0].url
                : '';

            const artistNames = track.artists && track.artists.length > 0
                ? track.artists.map(a => a.name || 'Unknown').join(', ')
                : 'Unknown Artist';

            nextTrackContainer.innerHTML = '';
            if (!nextTrackTemplate) return;
            const element = nextTrackTemplate.content.firstElementChild.cloneNode(true);
            const img = element.querySelector('img.next-track-image');
            if (img) {
                img.src = imageUrl;
            }
            const nameEl = element.querySelector('.next-track-name');
            if (nameEl) {
                nameEl.textContent = track.name || 'Unknown Track';
            }
            const artistsEl = element.querySelector('.next-track-artists');
            if (artistsEl) {
                artistsEl.textContent = artistNames;
            }
            nextTrackContainer.appendChild(element);

            // Add event listener for the play button
            const playButton = nextTrackContainer.querySelector('.play-next-track-btn');
            if (playButton && track.uri) {
                playButton.addEventListener('click', function() {
                    startPlayback(track.uri);
                });
            }
        } catch (error) {
            console.error('Error rendering next track:', error, track);
            if (nextTrackContainer && messageTemplate) {
                nextTrackContainer.innerHTML = '';
                const msg = messageTemplate.content.firstElementChild.cloneNode(true);
                msg.textContent = 'Error displaying next track';
                nextTrackContainer.appendChild(msg);
            }
        }
    }

    function showSuccessMessage(message) {
        showAlert(message, 'success');
    }
});
