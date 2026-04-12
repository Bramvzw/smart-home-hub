import { postOptions, escapeHtml } from '../../utils/index.js';

export function setupDevices(elements) {
    const { deviceBtn, deviceList, deviceListItems, deviceName } = elements;
    if (!deviceBtn || !deviceList) return;

    deviceBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = deviceList.classList.contains('hidden');
        if (isHidden) {
            loadDevices(elements);
            deviceList.classList.remove('hidden');
        } else {
            deviceList.classList.add('hidden');
        }
    });

    document.addEventListener('click', () => {
        deviceList.classList.add('hidden');
    });

    deviceList.addEventListener('click', (e) => e.stopPropagation());
}

function loadDevices(elements) {
    const { deviceListItems, deviceName, csrfToken } = elements;
    if (!deviceListItems) return;

    deviceListItems.innerHTML = '<div class="text-center text-gray-600 text-xs py-3">Loading...</div>';

    fetch('/spotify/devices')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.devices || data.devices.length === 0) {
                deviceListItems.innerHTML = '<div class="text-center text-gray-600 text-xs py-3">No devices found</div>';
                return;
            }

            deviceListItems.innerHTML = data.devices.map(device => {
                const isActive = device.is_active;
                const typeIcon = getDeviceIcon(device.type);
                return `<button class="device-item w-full flex items-center space-x-3 px-3 py-2.5 hover:bg-white/5 transition-colors text-left ${isActive ? 'text-green-400' : 'text-gray-300'}"
                    data-device-id="${escapeHtml(device.id)}"
                    data-device-name="${escapeHtml(device.name)}">
                    ${typeIcon}
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-medium truncate">${escapeHtml(device.name)}</div>
                        <div class="text-[10px] text-gray-600 capitalize">${escapeHtml(device.type?.toLowerCase() ?? '')}</div>
                    </div>
                    ${isActive ? '<svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>' : ''}
                </button>`;
            }).join('');

            deviceListItems.querySelectorAll('.device-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.deviceId;
                    const name = btn.dataset.deviceName;
                    transferToDevice(id, name, elements, csrfToken);
                });
            });
        })
        .catch(() => {
            deviceListItems.innerHTML = '<div class="text-center text-gray-600 text-xs py-3">Failed to load devices</div>';
        });
}

function transferToDevice(deviceId, deviceDisplayName, elements, csrfToken) {
    const { deviceList, deviceName } = elements;

    fetch('/spotify/transfer-playback', {
        ...postOptions(csrfToken),
        body: JSON.stringify({ device_id: deviceId }),
    })
        .then(res => res.json())
        .then(data => {
            if (data.success && deviceName) {
                deviceName.textContent = deviceDisplayName;
            }
        })
        .catch((err) => { console.error('Failed to transfer playback', err); })
        .finally(() => {
            deviceList?.classList.add('hidden');
        });
}

function getDeviceIcon(type) {
    const t = (type ?? '').toUpperCase();
    if (t === 'COMPUTER') {
        return `<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M20 18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>`;
    }
    if (t === 'SMARTPHONE') {
        return `<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 18H7V4h10v16z"/></svg>`;
    }
    if (t === 'SPEAKER') {
        return `<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM12 19c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-9H9V6h6v4z"/></svg>`;
    }
    return `<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>`;
}
