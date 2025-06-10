/**
 * DOM Elements module
 * Contains all DOM element references used in the Spotify player
 */

/**
 * Get all DOM elements needed for the Spotify player
 * @returns {Object} Object containing all DOM element references
 */
export function getElements() {
    return {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        playPauseBtn: document.getElementById('play-pause-btn'),
        playPauseIcon: document.getElementById('play-pause-icon'),
        previousBtn: document.getElementById('previous-btn'),
        nextBtn: document.getElementById('next-btn'),
        likeBtn: document.getElementById('like-btn'),
        likeIcon: document.getElementById('like-icon'),
        volumeSlider: document.getElementById('volume-slider'),
        progressContainer: document.getElementById('progress-container'),
        progressBar: document.getElementById('progress-bar'),
        nextTrackContainer: document.getElementById('next-track'),
        messageTemplate: document.getElementById('message-template'),
        sidebar: document.getElementById('sidebar'),
        handle: document.getElementById('sidebar-resize-btn'),
    };
}
