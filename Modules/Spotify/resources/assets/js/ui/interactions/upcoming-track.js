/**
 * Next Track module
 * Contains functions for handling the next track functionality
 */

import { getImageUrl, updateElementContent } from '../../utils/index.js';
/*
* ToDo refactor can be one function?
 */
/**
 * Load the upcoming track from the queue
 */
export function loadUpcomingTrack(elements, renderNextTrackFn) {
    // If repeat track is on, next up is the current track
    const repeatBtn = document.getElementById('repeat-btn');
    if (repeatBtn && repeatBtn.dataset.repeatState === 'track') {
        return fetch('/spotify/playback-state')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.item) {
                    renderNextTrackFn(elements, data.item);
                } else {
                    renderNextTrackFn(elements, null);
                }
            })
            .catch(() => {
                renderNextTrackFn(elements, null);
            });
    }

    return fetch('/spotify/next-track')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.next_track) {
                renderNextTrackFn(elements, data.next_track);
            } else {
                renderNextTrackFn(elements, null);
            }
        })
        .catch(() => {
            renderNextTrackFn(elements, null);
        });
}

function resolveNextTrackImage(track) {
    return getImageUrl(track, 'album')
        || getImageUrl(track)
        || getImageUrl(track, 'show')
        || getImageUrl(track?.track, 'album')
        || getImageUrl(track?.track)
        || '';
}

function setNextTrackEmptyState(isEmpty) {
    const dock = document.querySelector('.spotify-next-dock');
    const playButton = document.querySelector('.play-next-track-btn');

    dock?.classList.toggle('is-empty', isEmpty);

    if (playButton) {
        playButton.disabled = isEmpty;
    }
}

function updateNextTrackImage(imageUrl) {
    const image = document.getElementById('next-track-image');
    const thumb = image?.closest('.spotify-next-thumb');

    if (!image || !thumb) return;

    if (!imageUrl) {
        thumb.classList.add('is-empty');
        image.hidden = true;
        return;
    }

    image.hidden = false;
    thumb.classList.remove('is-empty');
    updateElementContent('next-track-image', imageUrl, 'src');
}

function renderEmptyNextTrack() {
    updateNextTrackImage('');
    updateElementContent('next-track-name', 'Geen volgend nummer');
    updateElementContent('next-track-artists', '');
    setNextTrackEmptyState(true);
}

function resolveNextTrackArtists(track) {
    const artists = track.artists || track.album?.artists || [];
    const artistNames = artists.map(artist => artist.name).filter(Boolean);

    if (artistNames.length > 0) {
        return artistNames.join(', ');
    }

    return track.show?.publisher || '';
}

/**
 * Render the next track in the UI
 */
export function renderNextTrack(elements, startPlayback, track) {
    if (!elements.nextTrackContainer) return;

    if (!track) {
        renderEmptyNextTrack();
        return;
    }

    try {
        // Safely access nested properties
        const unwrappedTrack = track.track || track;
        const imageUrl = resolveNextTrackImage(unwrappedTrack);
        const artistNames = resolveNextTrackArtists(unwrappedTrack);
        const trackName = unwrappedTrack.name || 'Unknown Track';

        setNextTrackEmptyState(false);
        updateNextTrackImage(imageUrl);

        setTimeout(() => {
            updateElementContent('next-track-name', trackName);
            updateElementContent('next-track-artists', artistNames);
        }, 50);

        // Add event listener for the play button if it exists
        const playButton = elements.nextTrackContainer.querySelector('.play-next-track-btn');
        if (playButton && unwrappedTrack.uri) {
            // Remove existing listeners to prevent duplicates
            const newPlayButton = playButton.cloneNode(true);
            playButton.parentNode.replaceChild(newPlayButton, playButton);

            newPlayButton.addEventListener('click', function() {
                startPlayback(unwrappedTrack.uri);
            });
        }
    } catch (error) {
        renderEmptyNextTrack();
    }
}
