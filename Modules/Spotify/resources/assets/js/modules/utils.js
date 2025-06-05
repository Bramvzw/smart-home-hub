/**
 * Utilities module
 * Contains utility functions used throughout the Spotify player
 */

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
 * Helper function to update element content
 * @param {string} elementId - The ID of the element to update
 * @param {string} content - The content to set
 * @param {string} property - The property to set (default: 'textContent')
 */
export function updateElementContent(elementId, content, property = 'textContent') {
    const element = document.getElementById(elementId);
    if (element) {
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
    if (!elements.alertTemplate || !elements.alertTemplate.content || !elements.alertTemplate.content.firstElementChild) {
        return;
    }

    const alert = elements.alertTemplate.content.firstElementChild.cloneNode(true);
    const messageEl = alert.querySelector('.message');
    const closeBtn = alert.querySelector('.close-btn');

    if (messageEl) {
        messageEl.textContent = message;
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => alert.remove());
    }

    if (type === 'error') {
        alert.classList.add('bg-red-100', 'border-l-4', 'border-red-500', 'text-red-700');
        setTimeout(() => alert.remove(), 5000);
    } else {
        alert.classList.add('bg-green-100', 'border-l-4', 'border-green-500', 'text-green-700');
        setTimeout(() => alert.remove(), 3000);
    }

    document.body.appendChild(alert);
}

/**
 * Show an error message to the user
 * @param {Object} elements - DOM elements object
 * @param {string} message - The error message to display
 */
// Self-import to allow Jest spies on exported functions
import * as self from './utils.js';

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
                updatePlayerState();
                return data;
            } else if (data.error) {
                handledError = true;
                self.showErrorMessage(elements, data.error);
                throw new Error(data.error);
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
 * Display a message in a container
 * @param {HTMLElement} container - The container element
 * @param {HTMLTemplateElement} messageTemplate - The message template
 * @param {string} message - The message to display
 */
export function displayMessage(container, messageTemplate, message) {
    if (!container || !messageTemplate) return;

    container.innerHTML = '';
    const msg = messageTemplate.content.firstElementChild.cloneNode(true);
    msg.textContent = message;
    container.appendChild(msg);
}
