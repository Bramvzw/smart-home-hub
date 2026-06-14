// Lighting module: wire the controls to the JSON endpoints.
export const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const request = (url, method, payload = null) => fetch(url, {
    method,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf(),
    },
    body: payload === null ? null : JSON.stringify(payload),
});

const readJson = async (response) => {
    try {
        return await response.json();
    } catch {
        return {};
    }
};

const markDanger = (element, failed) => {
    element.classList.toggle('ring-[var(--hub-danger)]', failed);
};

const cardsForKey = (root, key) => [...root.querySelectorAll('[data-light]')]
    .filter((card) => card.dataset.lightKey === key);

const lightStateFromCard = (card) => ({
    power: card.querySelector('[data-action="power"]')?.getAttribute('aria-checked') === 'true',
    brightness: Number(card.querySelector('[data-action="brightness"]')?.value ?? card.dataset.brightness ?? 0),
    color: card.querySelector('[data-action="color"]')?.value ?? card.dataset.color ?? '#ffc26b',
    reachable: card.dataset.reachable === 'true',
    supportsColor: card.dataset.supportsColor === 'true',
});

const stateLabel = (state) => {
    if (! state.reachable) {
        return 'niet bereikbaar';
    }

    if (! state.power) {
        return 'uit';
    }

    return `${state.brightness}% · ${state.supportsColor ? 'kleur' : 'wit'}`;
};

const updateCardVisual = (card) => {
    const state = lightStateFromCard(card);

    card.dataset.on = String(state.power);
    card.dataset.brightness = String(state.brightness);
    card.dataset.color = state.color;
    card.style.setProperty('--light-color', state.color);
    card.style.setProperty('--light-brightness', `${state.brightness}%`);

    card.querySelectorAll('[data-light-state]').forEach((element) => {
        element.textContent = stateLabel(state);
    });
    card.querySelectorAll('[data-light-percent]').forEach((element) => {
        element.textContent = state.power ? `${state.brightness}%` : 'uit';
    });
    card.querySelectorAll('[data-light-brightness-value]').forEach((element) => {
        element.textContent = `${state.brightness}%`;
    });
};

const updateSummary = (root) => {
    const rows = [...root.querySelectorAll('[data-light-row]')];
    if (rows.length === 0) {
        return;
    }

    const onCount = rows.filter((row) => row.dataset.on === 'true').length;
    root.querySelectorAll('[data-lighting-summary]').forEach((element) => {
        element.textContent = `${onCount} van ${rows.length} aan`;
    });

    const master = root.querySelector('[data-master-toggle]');
    if (! master) {
        return;
    }

    const nextPreset = onCount > 0 ? 'off' : 'bright';
    const source = root.querySelector(`[data-preset="${nextPreset}"]:not([data-master-toggle])`);

    master.setAttribute('aria-checked', String(onCount > 0));
    master.querySelector('.lighting-console__switch')?.setAttribute('aria-checked', String(onCount > 0));

    if (source) {
        master.dataset.preset = source.dataset.preset;
        master.dataset.presetPower = source.dataset.presetPower;
        master.dataset.presetBrightness = source.dataset.presetBrightness ?? '';
        master.dataset.presetColor = source.dataset.presetColor ?? '';
    }
};

const syncLight = (root, sourceCard, patch = {}) => {
    const key = sourceCard.dataset.lightKey;
    const source = { ...lightStateFromCard(sourceCard), ...patch };

    if (! key) {
        updateCardVisual(sourceCard);
        updateSummary(root);
        return;
    }

    cardsForKey(root, key).forEach((card) => {
        card.querySelectorAll('[data-action="power"]').forEach((power) => {
            power.setAttribute('aria-checked', String(source.power));
        });
        card.querySelectorAll('[data-action="brightness"]').forEach((brightness) => {
            if (source.brightness !== null && source.brightness !== undefined) {
                brightness.value = String(source.brightness);
            }
        });
        card.querySelectorAll('[data-action="color"]').forEach((color) => {
            if (source.color) {
                color.value = source.color;
            }
        });

        updateCardVisual(card);
    });

    updateSummary(root);
};

const selectLight = (root, key) => {
    const selectedRow = [...root.querySelectorAll('[data-light-row]')]
        .find((row) => row.dataset.lightKey === key);

    root.querySelectorAll('[data-light-row]').forEach((row) => {
        const selected = row.dataset.lightKey === key;
        row.dataset.selected = String(selected);
        row.querySelector('[data-light-select]')?.setAttribute('aria-selected', String(selected));
    });

    root.querySelectorAll('[data-light-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.lightKey !== key;
    });

    if (selectedRow) {
        root.querySelectorAll('[data-selected-provider]').forEach((element) => {
            element.textContent = selectedRow.dataset.providerLabel ?? '';
        });
        root.querySelectorAll('[data-selected-name]').forEach((element) => {
            element.textContent = selectedRow.dataset.lightName ?? '';
        });
    }
};

const isCommandBusy = (root) => root?.dataset.commandBusy === 'true';

const setControlsBusy = (root, busy) => {
    if (! root) {
        return;
    }

    if (busy) {
        root.dataset.commandBusy = 'true';
    } else {
        delete root.dataset.commandBusy;
    }

    root.querySelectorAll('[data-preset]').forEach((button) => {
        button.disabled = busy;
    });

    root.querySelectorAll('[data-light]').forEach((card) => {
        const disabled = busy || card.dataset.reachable !== 'true';
        card.querySelectorAll('[data-action]').forEach((control) => {
            control.disabled = disabled;
        });
    });
};

const send = async (card, payload) => {
    const url = card.dataset.url;
    const root = card.closest('[data-lighting]');
    if (! url || isCommandBusy(root)) {
        return;
    }

    card.dataset.busy = 'true';
    setControlsBusy(root, true);
    try {
        const response = await request(url, 'PUT', payload);

        if (! response.ok) {
            markDanger(card, true);
            return;
        }

        markDanger(card, false);
    } finally {
        delete card.dataset.busy;
        setControlsBusy(root, false);
    }
};

const debounce = (fn, wait) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };
};

const nullableNumber = (value) => (value === undefined || value === '' ? null : Number(value));

const normaliseColor = (value) => {
    if (! value) {
        return null;
    }

    const color = String(value).trim().toLowerCase();

    return color.startsWith('#') ? color : `#${color}`;
};

const presetFromButton = (button) => ({
    key: button.dataset.preset,
    label: button.dataset.presetLabel ?? button.textContent.trim(),
    power: button.dataset.presetPower === 'true',
    brightness: nullableNumber(button.dataset.presetBrightness),
    color: button.dataset.presetColor || null,
});

const presetMatchesCard = (card, preset) => {
    const state = lightStateFromCard(card);

    if (! state.reachable) {
        return true;
    }

    if (! preset.power) {
        return ! state.power;
    }

    if (! state.power) {
        return false;
    }

    if (preset.brightness !== null && state.brightness !== preset.brightness) {
        return false;
    }

    if (preset.color && state.supportsColor && normaliseColor(state.color) !== normaliseColor(preset.color)) {
        return false;
    }

    return true;
};

const detectActivePreset = (root) => {
    const reachableLights = [...root.querySelectorAll('[data-light-row]')]
        .filter((card) => card.dataset.reachable === 'true');

    if (reachableLights.length === 0) {
        return null;
    }

    return [...root.querySelectorAll('[data-preset]:not([data-master-toggle])')]
        .map((button) => ({ button, preset: presetFromButton(button) }))
        .find(({ preset }) => reachableLights.every((card) => presetMatchesCard(card, preset))) ?? null;
};

const updateActivePreset = (root, activeKey = null) => {
    const detected = activeKey
        ? [...root.querySelectorAll('[data-preset]:not([data-master-toggle])')]
            .map((button) => ({ button, preset: presetFromButton(button) }))
            .find(({ preset }) => preset.key === activeKey)
        : detectActivePreset(root);
    const activePreset = detected?.preset ?? null;

    root.dataset.activePreset = activePreset?.key ?? 'manual';

    root.querySelectorAll('[data-preset]:not([data-master-toggle])').forEach((button) => {
        const active = activePreset?.key === button.dataset.preset;
        button.dataset.active = String(active);
        button.setAttribute('aria-pressed', String(active));
    });

    root.querySelectorAll('[data-active-preset-label]').forEach((element) => {
        const label = activePreset?.label ?? element.dataset.manualLabel ?? 'Handmatig';
        const labelTarget = element.querySelector('.lighting-console__active-preset-name') ?? element;
        labelTarget.textContent = label;
    });
};

const applyPresetToCard = (card, preset) => {
    if (card.dataset.reachable !== 'true') {
        return;
    }

    const powerControls = card.querySelectorAll('[data-action="power"]');
    const brightnessControls = card.querySelectorAll('[data-action="brightness"]');
    const colorControls = card.querySelectorAll('[data-action="color"]');

    powerControls.forEach((power) => power.setAttribute('aria-checked', String(preset.power)));

    if (! preset.power) {
        updateCardVisual(card);
        return;
    }

    if (preset.brightness !== null) {
        brightnessControls.forEach((brightness) => {
            brightness.value = String(preset.brightness);
        });
    }

    if (preset.color && card.dataset.supportsColor === 'true') {
        colorControls.forEach((color) => {
            color.value = preset.color;
        });
    }

    updateCardVisual(card);
};

const applyPreset = async (root, button) => {
    const template = root.dataset.presetUrlTemplate;
    const preset = presetFromButton(button);
    if (! template || ! preset.key || isCommandBusy(root)) {
        return;
    }

    root.dataset.busyPreset = preset.key;
    setControlsBusy(root, true);

    try {
        const response = await request(
            template.replace('__PRESET__', encodeURIComponent(preset.key)),
            'POST',
        );
        const body = await readJson(response);

        if (! response.ok) {
            markDanger(button, true);
            return;
        }

        const appliedPreset = body.data?.preset ?? preset;
        root.querySelectorAll('[data-light]').forEach((card) => applyPresetToCard(card, appliedPreset));
        updateSummary(root);
        updateActivePreset(root, appliedPreset.key);
        markDanger(button, Array.isArray(body.data?.failed_lights) && body.data.failed_lights.length > 0);
    } finally {
        delete root.dataset.busyPreset;
        setControlsBusy(root, false);
    }
};

export const initLighting = () => {
    const root = document.querySelector('[data-lighting]');
    if (! root || root.dataset.lightingReady === 'true') {
        return;
    }
    root.dataset.lightingReady = 'true';

    root.querySelectorAll('[data-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            applyPreset(root, button);
        });
    });

    root.querySelectorAll('[data-light-select]').forEach((button) => {
        button.addEventListener('click', () => {
            selectLight(root, button.dataset.lightKey);
        });
    });

    root.querySelectorAll('[data-light]').forEach((card) => {
        card.querySelectorAll('[data-action="power"]').forEach((power) => power.addEventListener('click', () => {
            if (isCommandBusy(root)) {
                return;
            }

            const next = power.getAttribute('aria-checked') !== 'true';
            syncLight(root, card, { power: next });
            updateActivePreset(root);
            send(card, { power: next });
        }));

        card.querySelectorAll('[data-action="brightness"]').forEach((brightness) => brightness.addEventListener('input', debounce((event) => {
            const value = Number(event.target.value);
            syncLight(root, card, { brightness: value, power: true });
            updateActivePreset(root);
            send(card, { power: true, brightness: value });
        }, 250)));

        // 'input' fires live while dragging the picker; debounce so we don't flood the API.
        card.querySelectorAll('[data-action="color"]').forEach((color) => color.addEventListener('input', debounce((event) => {
            syncLight(root, card, { color: event.target.value, power: true });
            updateActivePreset(root);
            send(card, { power: true, color: event.target.value });
        }, 250)));

        updateCardVisual(card);
    });

    updateSummary(root);
    updateActivePreset(root);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLighting);
} else {
    initLighting();
}
