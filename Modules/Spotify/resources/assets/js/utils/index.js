/**
 * Utilities module
 * Contains utility functions used throughout the Spotify player
 */

/**
 * Fetch a URL and return parsed JSON, throwing on non-OK responses.
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<any>}
 */
export async function fetchJson(url, options = {}) {
    const res = await fetch(url, options);
    if (res.status === 401) {
        throw Object.assign(new Error('auth_required'), { status: 401 });
    }
    if (!res.ok) {
        throw Object.assign(new Error(`HTTP error ${res.status}`), { status: res.status });
    }
    return res.json();
}

/**
 * POST JSON data with CSRF token.
 * @param {string} url
 * @param {object} body
 * @param {string} csrfToken
 * @returns {Promise<any>}
 */
export function postJson(url, body, csrfToken) {
    return fetchJson(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(body),
    });
}

/**
 * Handle auth errors by showing a reconnect prompt.
 * @param {Error} err
 * @param {object} elements
 */
export function handleAuthError(err, elements) {
    if (err.status === 401) {
        // Show a visible reconnect prompt
        const msg = document.createElement('div');
        msg.id = 'spotify-reconnect-banner';
        msg.style.cssText = 'position:fixed;top:0;left:0;right:0;background:#ef4444;color:#fff;text-align:center;padding:12px;z-index:9999;font-size:14px;';
        msg.innerHTML = 'Spotify session expired. <a href="/spotify" style="color:#fff;text-decoration:underline;font-weight:600;">Reconnect</a>';
        if (!document.getElementById('spotify-reconnect-banner')) {
            document.body.appendChild(msg);
        }
    }
}

/**
 * Format milliseconds to mm:ss format
 * @param {number} ms - Time in milliseconds
 * @returns {string} Formatted time string
 */
export function formatTime(ms) {
    const m = Math.floor(ms / 60000);
    const s = Math.floor((ms % 60000) / 1000);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
}

/**
 * Create options for POST requests with CSRF token
 * @param {string} csrfToken - The CSRF token
 * @returns {Object} Request options
 */
export function postOptions(csrfToken) {
    return {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    };
}

/**
 * Helper function to update element content with a smooth transition
 * @param {string} elementId - The ID of the element to update
 * @param {string} content - The content to set
 * @param {string} property - The property to set (default: 'textContent')
 */
export function updateElementContent(elementId, content, property = 'textContent') {
    const element = document.getElementById(elementId);
    if (!element) return;

    // For image elements, handle differently to ensure smooth transitions
    if (property === 'src' && element.tagName === 'IMG') {
        // Only update if the content is different
        if (element.src !== content) {
            // Create a new image to preload
            const newImage = new Image();
            newImage.onload = function() {
                // Once preloaded, update the src
                element.src = content;
            };
            newImage.src = content;
        }
    } else {
        // For text content, just update directly
        // The CSS transitions will handle the smooth effect
        element[property] = content;
    }
}

/**
 * Show an alert message to the user
 * @param {Object} elements - DOM elements object
 * @param {string} message - The message to display
 * @param {string} type - The type of alert ('success' or 'error')
 */
export function showAlert(elements, message, type) {
    const alert = document.createElement('div');
    alert.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;padding:10px 20px;border-radius:8px;font-size:14px;font-family:inherit;max-width:90%;text-align:center;transition:opacity 0.3s;';

    if (type === 'error') {
        alert.style.background = 'rgba(239,68,68,0.95)';
        alert.style.color = '#fff';
        setTimeout(() => alert.remove(), 5000);
    } else {
        alert.style.background = 'rgba(34,197,94,0.95)';
        alert.style.color = '#fff';
        setTimeout(() => alert.remove(), 3000);
    }

    alert.textContent = message;
    alert.addEventListener('click', () => alert.remove());
    document.body.appendChild(alert);
}

/**
 * Show an error message to the user
 * @param {Object} elements - DOM elements object
 * @param {string} message - The error message to display
 */
// Self-import to allow Jest spies on exported functions
import * as self from './index.js';

export function showErrorMessage(elements, message) {
    self.showAlert(elements, message, 'error');
}

/**
 * Show a success message to the user
 * @param {Object} elements - DOM elements object
 * @param {string} message - The success message to display
 */
export function showSuccessMessage(elements, message) {
    self.showAlert(elements, message, 'success');
}

/**
 * Handle API response and update player state
 * @param {Response} response - The fetch API response
 * @param {Function} updatePlayerState - Function to update player state
 * @param {Object} elements - DOM elements object
 * @returns {Promise} Promise with the response data
 */
export function handleResponse(response, updatePlayerState, elements) {
    let handledError = false;
    return response
        .json()
        .then(data => {
            if (data.success) {
                updatePlayerState(data);
                return data;
            } else if (data.message || data.error) {
                handledError = true;
                const msg = data.message || data.error;
                self.showErrorMessage(elements, msg);
                throw new Error(msg);
            }
            return data;
        })
        .catch(error => {
            if (handledError) {
                throw error;
            }
            self.showErrorMessage(elements, 'An error occurred with the Spotify API');
            return { success: false, error: error.message };
        });
}

/**
 * Safely get an image URL from a Spotify API object.
 * Handles null images, null parent objects, and empty arrays.
 *
 * @param {Object} obj - The Spotify object (track, album, playlist)
 * @param {string} [key] - Optional nested key to access images from (e.g. 'album')
 * @returns {string} Image URL or empty string
 */
export function getImageUrl(obj, key) {
    try {
        const images = key ? obj?.[key]?.images : obj?.images;
        if (images && Array.isArray(images) && images.length > 0) {
            return images[images.length - 1]?.url || '';
        }
    } catch (e) { /* ignore */ }
    return '';
}

/**
 * Escape HTML entities to prevent XSS in dynamic content.
 *
 * @param {string} text - The text to escape
 * @returns {string} Escaped HTML string
 */
export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Display a message in a container
 * @param {HTMLElement} container - The container element
 * @param {HTMLTemplateElement} messageTemplate - The message template
 * @param {string} message - The message to display
 */
export function displayMessage(container, messageTemplate, message) {
    if (!container || !messageTemplate) return;

    // Create a clone of the template
    const msg = messageTemplate.content.firstElementChild.cloneNode(true);
    msg.textContent = message;

    // Clear the container carefully
    while (container.firstChild) {
        container.removeChild(container.firstChild);
    }

    // Append the message
    container.appendChild(msg);
}
