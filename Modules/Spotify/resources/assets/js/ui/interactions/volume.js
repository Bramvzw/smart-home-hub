import { postOptions, showErrorMessage } from '../../utils/index.js';

/**
 * Set the volume level
 */
export function setVolume(elements, updatePlayerStateFn, volume) {
    return fetch('/spotify/volume', {
        ...postOptions(elements.csrfToken),
        body: JSON.stringify({ volume })
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success && data.code === 'volume_control_not_supported') {
                showErrorMessage(elements, 'This device does not support volume control.');
                setTimeout(updatePlayerStateFn, 500);
            }
        })
        .catch(() => {
            showErrorMessage(elements, 'Error setting volume');
        });
}
