// Lighting module: kiosk tile grid, control sheet and scene strip, wired to
// the JSON endpoints. Lamp state lives in each tile's data-* attributes; the
// tile, the sheet and the header are repainted from it.
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

const wait = (ms) => new Promise((resolve) => {
    setTimeout(resolve, ms);
});

// A busy server lock (409) is usually gone within moments: retry once.
const requestWithRetry = async (url, method, payload = null) => {
    const attempt = async () => {
        try {
            return await request(url, method, payload);
        } catch {
            return null;
        }
    };

    let response = await attempt();
    if (response?.status === 409) {
        await wait(500);
        response = await attempt();
    }

    return response;
};

const nullableNumber = (value) => (value === undefined || value === null || value === '' ? null : Number(value));

const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

const debounce = (fn, ms) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
};

// --- Colour helpers -------------------------------------------------------

// HSL (0-360, 0-100, 0-100) -> [r, g, b]. Used for on-screen colours.
const hslRgb = (h, s, l) => {
    s /= 100;
    l /= 100;
    const k = (n) => (n + h / 30) % 12;
    const a = s * Math.min(l, 1 - l);
    const f = (n) => Math.round(255 * (l - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1))));

    return [f(0), f(8), f(4)];
};

// HSV (0-360, 0-100, 0-100) -> #rrggbb. The colour sent to the lamps: with
// v = 100 the saturation maps 1:1 onto Tuya's colour_data_v2 `s`, and the
// providers report colours back the same way.
const hsvToHex = (h, s, v = 100) => {
    const sf = s / 100;
    const vf = v / 100;
    const f = (n) => {
        const k = (n + h / 60) % 6;
        const value = vf - vf * sf * Math.max(0, Math.min(k, 4 - k, 1));
        return Math.round(value * 255).toString(16).padStart(2, '0');
    };

    return `#${f(5)}${f(3)}${f(1)}`;
};

// #rrggbb -> { h: 0-360, s: 0-100, v: 0-100 }.
const hexToHsv = (hex) => {
    const value = String(hex ?? '').replace('#', '');
    if (value.length < 6) {
        return { h: 30, s: 80, v: 100 };
    }

    const r = parseInt(value.slice(0, 2), 16) / 255;
    const g = parseInt(value.slice(2, 4), 16) / 255;
    const b = parseInt(value.slice(4, 6), 16) / 255;
    const max = Math.max(r, g, b);
    const delta = max - Math.min(r, g, b);

    let hue = 0;
    if (delta !== 0) {
        if (max === r) {
            hue = ((g - b) / delta) % 6;
        } else if (max === g) {
            hue = (b - r) / delta + 2;
        } else {
            hue = (r - g) / delta + 4;
        }
    }

    return {
        h: (Math.round(hue * 60) + 360) % 360,
        s: max === 0 ? 0 : Math.round((delta / max) * 100),
        v: Math.round(max * 100),
    };
};

// Colour temperature (0 warm - 100 cool) -> [r, g, b] for the on-screen glow.
const tempRgb = (t) => {
    const warm = [255, 168, 82];
    const mid = [255, 236, 210];
    const cool = [214, 228, 255];
    const lerp = (a, b, x) => a.map((v, i) => Math.round(v + (b[i] - v) * x));

    return t <= 50 ? lerp(warm, mid, t / 50) : lerp(mid, cool, (t - 50) / 50);
};

const rgbCss = (rgb, alpha = null) => (alpha === null ? `rgb(${rgb.join(' ')})` : `rgb(${rgb.join(' ')} / ${alpha})`);

const tempName = (t) => {
    if (t === null || t === undefined) {
        return 'White';
    }

    return t < 34 ? 'Warm white' : (t < 67 ? 'Neutral white' : 'Cool white');
};

const kelvin = (t) => `${(Math.round((2700 + t * 38) / 100) * 100).toLocaleString('en-US')} K`;

// --- Lamp state -----------------------------------------------------------

const lightStateFromCard = (card) => ({
    power: card.dataset.on === 'true',
    brightness: Number(card.dataset.brightness ?? 0),
    color: card.dataset.color || null,
    reachable: card.dataset.reachable === 'true',
    supportsColor: card.dataset.supportsColor === 'true',
    mode: card.dataset.mode === 'colour' ? 'colour' : 'white',
    colorTemp: nullableNumber(card.dataset.colorTemp),
    supportsColorTemp: card.dataset.supportsColorTemp === 'true',
});

// What the lamp looks like: white temperature, or the hue/saturation of its colour.
const displayRgb = (state) => {
    if (state.mode === 'colour' && state.color) {
        const { h, s } = hexToHsv(state.color);
        return hslRgb(h, s, 62);
    }

    return tempRgb(state.colorTemp ?? 30);
};

const stateLabel = (state) => {
    if (! state.reachable) {
        return 'Offline';
    }

    if (! state.power) {
        return 'Off';
    }

    return `${state.brightness}% · ${state.mode === 'colour' ? 'Colour' : tempName(state.colorTemp)}`;
};

const lightKey = (card) => card.dataset.lightKey;

const cardsForKey = (root, key) => [...root.querySelectorAll('[data-light]')]
    .filter((card) => card.dataset.lightKey === key);

const allCards = (root) => [...root.querySelectorAll('[data-light]')];

const rootOf = (card) => card.closest('[data-lighting]');

let toastTimer = null;

const showToast = (root, message) => {
    const toast = root.querySelector('[data-lighting-toast]');
    if (! toast) {
        return;
    }

    const text = toast.querySelector('[data-lighting-toast-text]') ?? toast;
    text.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.hidden = true;
    }, 6000);
};

const setFailed = (element, failed) => {
    if (failed) {
        element.dataset.failed = 'true';
    } else {
        delete element.dataset.failed;
    }
};

// Paint a slider primitive (custom track/fill/thumb over a native range).
const paintSlider = (slider, value, { fill = null, ring = null, track = null } = {}) => {
    if (! slider) {
        return;
    }

    const input = slider.querySelector('input[type="range"]');
    const min = Number(input?.min ?? 0);
    const max = Number(input?.max ?? 100);
    if (input && document.activeElement !== input) {
        input.value = String(value);
    }

    const pct = max === min ? 0 : clamp(((value - min) / (max - min)) * 100, 0, 100);
    slider.style.setProperty('--slider-pct', String(pct));
    if (fill) {
        slider.style.setProperty('--slider-fill', fill);
    }
    if (ring) {
        slider.style.setProperty('--slider-ring', ring);
    }
    if (track) {
        slider.style.setProperty('--slider-track', track);
    }
};

const isSelecting = (root) => root?.dataset.mode === 'select';

const selection = new Set();

const updateCardVisual = (card) => {
    const root = rootOf(card);
    const state = lightStateFromCard(card);
    const rgb = displayRgb(state);
    const label = stateLabel(state);
    const selected = selection.has(lightKey(card));

    card.style.setProperty('--tile-rgb', rgb.join(' '));
    card.style.setProperty('--tile-alpha', String(0.2 + (0.42 * state.brightness) / 100));
    card.style.setProperty('--tile-flat-alpha', String(0.1 + (0.22 * state.brightness) / 100));
    card.dataset.selected = String(isSelecting(root) && selected);

    card.querySelectorAll('[data-light-state]').forEach((element) => {
        element.textContent = label;
    });

    const toggle = card.querySelector('[data-tile-toggle]');
    if (toggle) {
        toggle.disabled = ! state.reachable;
        toggle.setAttribute('aria-pressed', String(isSelecting(root) ? selected : state.power));
        const name = card.dataset.lightName ?? '';
        toggle.setAttribute('aria-label', `${name}, ${label}${isSelecting(root) ? (selected ? ', selected' : ', not selected') : ''}`);
    }

    const bar = card.querySelector('[data-action="brightness"]')?.closest('[data-slider]');
    paintSlider(bar, Math.max(1, state.brightness));
};

const updateSummary = (root) => {
    const cards = allCards(root);
    if (cards.length === 0 || isSelecting(root)) {
        return;
    }

    const onCount = cards.filter((card) => card.dataset.reachable === 'true' && card.dataset.on === 'true').length;
    root.querySelectorAll('[data-lighting-summary]').forEach((element) => {
        element.textContent = `${onCount} of ${cards.length} on`;
    });
};

// Write a state patch onto a lamp's tile(s) and repaint everything that shows it.
const syncLight = (root, sourceCard, patch = {}) => {
    const key = lightKey(sourceCard);
    const targets = key ? cardsForKey(root, key) : [sourceCard];

    targets.forEach((card) => {
        if ('power' in patch && patch.power !== undefined) {
            card.dataset.on = String(Boolean(patch.power));
        }
        if (patch.brightness !== null && patch.brightness !== undefined) {
            card.dataset.brightness = String(patch.brightness);
        }
        if (patch.color) {
            card.dataset.color = patch.color;
        }
        if (patch.mode) {
            card.dataset.mode = patch.mode;
        }
        if (patch.colorTemp !== null && patch.colorTemp !== undefined) {
            card.dataset.colorTemp = String(patch.colorTemp);
        }

        updateCardVisual(card);
    });

    updateSummary(root);
    renderSheet(root);
};

// --- Per-light command queues --------------------------------------------

// Keyed by control URL. Each lamp has at most one request in flight; changes
// made meanwhile coalesce into one queued payload (latest value per field
// wins). `confirmed` is the last state the server accepted (rollback target).
const lightQueues = new Map();

const presetQueue = { inFlight: false, next: null };

const groupQueue = { inFlight: false, next: null, cards: [] };

const queueFor = (url) => {
    if (! lightQueues.has(url)) {
        lightQueues.set(url, { inFlight: false, next: null, confirmed: null, touchedAt: 0 });
    }

    return lightQueues.get(url);
};

const isLightPending = (url) => {
    const queue = lightQueues.get(url);

    return Boolean(queue && (queue.inFlight || queue.next));
};

const confirmedStateFromCard = (card) => {
    const { power, brightness, color, mode, colorTemp } = lightStateFromCard(card);

    return { power, brightness, color, mode, colorTemp };
};

// Translate a sent payload into the state fields the server has now confirmed.
const confirmedPatch = (payload) => {
    const patch = {};
    if ('power' in payload) {
        patch.power = payload.power;
    }
    if ('brightness' in payload) {
        patch.brightness = payload.brightness;
    }
    if ('color' in payload) {
        patch.color = payload.color;
        patch.mode = 'colour';
    }
    if ('color_temp' in payload) {
        patch.colorTemp = payload.color_temp;
        patch.mode = 'white';
    }

    return patch;
};

// Merge a newer payload onto a queued one. Colour and colour temperature are
// different LED channels: the newer of the two wins.
const mergePayload = (queued, payload) => {
    const merged = { ...(queued ?? {}) };
    if ('color' in payload) {
        delete merged.color_temp;
    }
    if ('color_temp' in payload) {
        delete merged.color;
    }

    return { ...merged, ...payload };
};

const setPending = (root, card, pending) => {
    cardsForKey(root, lightKey(card) ?? '').concat(lightKey(card) ? [] : [card]).forEach((element) => {
        if (pending) {
            element.dataset.pending = 'true';
        } else {
            delete element.dataset.pending;
        }
    });
};

const failureMessage = (card) => {
    const provider = card.dataset.providerLabel || 'the light';
    const name = card.dataset.lightName;

    return name
        ? `Couldn't reach ${provider} — ${name} was set back to its last known state.`
        : `Couldn't reach ${provider}.`;
};

const send = async (card, payload) => {
    const url = card.dataset.url;
    const root = rootOf(card);
    if (! url || ! root) {
        return;
    }

    const queue = queueFor(url);
    queue.touchedAt = Date.now();
    queue.confirmed ??= confirmedStateFromCard(card);

    if (queue.inFlight) {
        queue.next = mergePayload(queue.next, payload);
        return;
    }

    queue.inFlight = true;
    setPending(root, card, true);

    try {
        let current = payload;
        while (current) {
            const response = await requestWithRetry(url, 'PUT', current);

            if (! response?.ok) {
                // Drop anything queued on top of the failed state and restore
                // what the lamp last confirmed.
                queue.next = null;
                syncLight(root, card, queue.confirmed);
                updateActivePreset(root);
                setFailed(card, true);
                showToast(root, failureMessage(card));
                return;
            }

            queue.confirmed = { ...queue.confirmed, ...confirmedPatch(current) };
            setFailed(card, false);

            current = queue.next;
            queue.next = null;
        }
    } finally {
        queue.inFlight = false;
        queue.touchedAt = Date.now();
        setPending(root, card, false);
    }
};

// One change set for several lamps (multi-select): PUT /lighting/lights.
// Latest wins like the per-light queue; a lamp named in `failed` rolls back.
const sendGroup = async (root, cards, payload) => {
    const url = root.dataset.groupUrl;
    if (! url || cards.length === 0) {
        return;
    }

    cards.forEach((card) => {
        const queue = queueFor(card.dataset.url ?? lightKey(card));
        queue.touchedAt = Date.now();
        queue.confirmed ??= confirmedStateFromCard(card);
    });

    if (groupQueue.inFlight) {
        groupQueue.next = mergePayload(groupQueue.next, payload);
        groupQueue.cards = cards;
        return;
    }

    groupQueue.inFlight = true;
    let current = payload;
    let targets = cards;

    try {
        while (current) {
            targets.forEach((card) => setPending(root, card, true));
            const response = await requestWithRetry(url, 'PUT', {
                lights: targets.map((card) => ({ provider: card.dataset.provider, id: card.dataset.lightId })),
                changes: current,
            });
            const body = response ? await readJson(response) : {};
            const failedNames = response?.ok && Array.isArray(body.data?.failed) ? body.data.failed : null;
            const failedCards = failedNames === null
                ? targets
                : targets.filter((card) => failedNames.includes(card.dataset.lightName));

            targets.forEach((card) => {
                const queue = queueFor(card.dataset.url ?? lightKey(card));
                if (failedCards.includes(card)) {
                    syncLight(root, card, queue.confirmed);
                    setFailed(card, true);
                } else {
                    queue.confirmed = { ...queue.confirmed, ...confirmedPatch(current) };
                    setFailed(card, false);
                }
                setPending(root, card, false);
            });

            if (failedCards.length > 0) {
                groupQueue.next = null;
                updateActivePreset(root);
                showToast(root, `Couldn't reach ${failedCards.map((card) => card.dataset.lightName).join(', ')} — set back to the last known state.`);
                return;
            }

            current = groupQueue.next;
            targets = groupQueue.cards.length ? groupQueue.cards : targets;
            groupQueue.next = null;
        }
    } finally {
        groupQueue.inFlight = false;
        groupQueue.cards = [];
        targets.forEach((card) => {
            setPending(root, card, false);
            queueFor(card.dataset.url ?? lightKey(card)).touchedAt = Date.now();
        });
    }
};

// --- Presets (built-in, config-driven) -----------------------------------

const listFromValue = (value) => {
    if (Array.isArray(value)) {
        return value.map((entry) => String(entry).trim().toLowerCase()).filter(Boolean);
    }

    if (typeof value !== 'string' || value.trim() === '') {
        return [];
    }

    return value.split(',').map((entry) => entry.trim().toLowerCase()).filter(Boolean);
};

const normaliseColor = (value) => {
    if (! value) {
        return null;
    }

    const color = String(value).trim().toLowerCase();

    return color.startsWith('#') ? color : `#${color}`;
};

const normalisePreset = (preset) => ({
    key: preset.key,
    label: preset.label ?? '',
    power: preset.power === true || preset.power === 'true',
    brightness: nullableNumber(preset.brightness),
    color: preset.color || null,
    colorTemp: nullableNumber(preset.colorTemp ?? preset.color_temp),
    targetNameContains: listFromValue(preset.targetNameContains ?? preset.target_name_contains),
});

const presetFromButton = (button) => normalisePreset({
    key: button.dataset.preset,
    label: button.dataset.presetLabel ?? button.textContent.trim(),
    power: button.dataset.presetPower === 'true',
    brightness: button.dataset.presetBrightness,
    color: button.dataset.presetColor || null,
    colorTemp: button.dataset.presetColorTemp,
    targetNameContains: button.dataset.presetTargetNameContains,
});

const presetTargetsCard = (card, preset) => {
    if (! preset.targetNameContains?.length) {
        return true;
    }

    const name = (card.dataset.lightName ?? '').toLowerCase();

    return preset.targetNameContains.some((target) => target !== '' && name.includes(target));
};

const presetMatchesCard = (card, preset) => {
    const state = lightStateFromCard(card);

    if (! preset.power) {
        return ! state.power;
    }

    if (! state.power) {
        return false;
    }

    if (preset.brightness !== null && state.brightness !== preset.brightness) {
        return false;
    }

    if (preset.colorTemp !== null && state.supportsColorTemp) {
        return state.mode === 'white' && state.colorTemp === preset.colorTemp;
    }

    if (preset.color && state.supportsColor && normaliseColor(state.color) !== normaliseColor(preset.color)) {
        return false;
    }

    return true;
};

const presetButtons = (root) => [...root.querySelectorAll('[data-preset]')];

// Every activatable scene button with a matcher: built-in presets first.
const sceneEntries = (root) => presetButtons(root).map((button) => {
    const preset = presetFromButton(button);
    const reachable = allCards(root).filter((card) => card.dataset.reachable === 'true');

    return {
        key: preset.key,
        label: preset.label,
        button,
        matches: () => {
            const targeted = reachable.filter((card) => presetTargetsCard(card, preset));

            return targeted.length > 0 && targeted.every((card) => presetMatchesCard(card, preset));
        },
    };
});

const updateActivePreset = (root, activeKey = null) => {
    const entries = sceneEntries(root);
    const active = activeKey
        ? entries.find((entry) => entry.key === activeKey)
        : entries.find((entry) => entry.matches());

    root.dataset.activePreset = active?.key ?? 'manual';

    entries.forEach((entry) => {
        const isActive = active?.key === entry.key;
        entry.button.dataset.active = String(isActive);
        entry.button.setAttribute('aria-pressed', String(isActive));
    });
};

const applyPresetToCard = (card, preset) => {
    if (card.dataset.reachable !== 'true' || ! presetTargetsCard(card, preset)) {
        return;
    }

    card.dataset.on = String(preset.power);

    if (preset.power) {
        if (preset.brightness !== null) {
            card.dataset.brightness = String(preset.brightness);
        }

        // A colour temperature wins over a colour, like on the backend.
        if (preset.colorTemp !== null && card.dataset.supportsColorTemp === 'true') {
            card.dataset.mode = 'white';
            card.dataset.colorTemp = String(preset.colorTemp);
        } else if (preset.color && card.dataset.supportsColor === 'true') {
            card.dataset.color = preset.color;
            card.dataset.mode = 'colour';
        }
    }

    updateCardVisual(card);
};

// How a scene-strip button is applied: its endpoint and the optimistic update.
const bulkActionFor = (root, button) => {
    const template = root.dataset.presetUrlTemplate;
    const preset = presetFromButton(button);
    if (! template || ! preset.key) {
        return null;
    }

    return {
        key: preset.key,
        label: preset.label,
        url: template.replace('__PRESET__', encodeURIComponent(preset.key)),
        apply: (body) => {
            const applied = normalisePreset({ ...preset, ...(body.data?.preset ?? {}) });
            allCards(root).forEach((card) => applyPresetToCard(card, applied));

            return { activeKey: applied.key, failed: body.data?.failed_lights ?? [] };
        },
    };
};

const applyPreset = async (root, button) => {
    const action = bulkActionFor(root, button);
    if (! action) {
        return;
    }

    // Latest wins: a scene tapped while another is running replaces any
    // queued one and runs right after it.
    if (presetQueue.inFlight) {
        presetQueue.next = button;
        return;
    }

    presetQueue.inFlight = true;
    root.dataset.busyPreset = action.key;

    try {
        const response = await requestWithRetry(action.url, 'POST');
        const body = response ? await readJson(response) : {};

        if (! response?.ok) {
            setFailed(button, true);
            showToast(root, `Couldn't apply ${action.label || 'the scene'}.`);
            return;
        }

        const { activeKey, failed } = action.apply(body);
        allCards(root).forEach((card) => {
            if (card.dataset.url && card.dataset.reachable === 'true') {
                queueFor(card.dataset.url).confirmed = confirmedStateFromCard(card);
            }
        });
        updateSummary(root);
        renderSheet(root);
        updateActivePreset(root, activeKey);
        const failedList = Array.isArray(failed) ? failed : [];
        setFailed(button, failedList.length > 0);
        if (failedList.length > 0) {
            showToast(root, `Couldn't reach ${failedList.join(', ')}.`);
        }
    } finally {
        delete root.dataset.busyPreset;
        presetQueue.inFlight = false;

        const next = presetQueue.next;
        presetQueue.next = null;
        if (next) {
            applyPreset(root, next);
        }
    }
};

// --- Live state polling ---------------------------------------------------

const setReachable = (card, reachable) => {
    card.dataset.reachable = String(reachable);
};

// Apply one light from GET /lighting/state, unless the lamp has a command
// pending or was touched after the refresh started (its local state is newer).
const applyServerLight = (root, light, startedAt) => {
    const cards = cardsForKey(root, `${light.provider}::${light.id}`);
    if (cards.length === 0) {
        return;
    }

    const url = cards[0].dataset.url;
    if (cards[0].dataset.pending === 'true'
        || (url && (isLightPending(url) || (lightQueues.get(url)?.touchedAt ?? 0) >= startedAt))) {
        return;
    }

    const state = {
        power: Boolean(light.on),
        brightness: Number(light.brightness ?? 0),
        color: light.color ?? null,
        mode: light.mode === 'colour' ? 'colour' : 'white',
        colorTemp: nullableNumber(light.color_temp),
    };

    cards.forEach((card) => setReachable(card, Boolean(light.reachable)));
    syncLight(root, cards[0], state);

    if (url) {
        queueFor(url).confirmed = { ...queueFor(url).confirmed, ...state, color: state.color ?? cards[0].dataset.color };
    }
};

const refreshState = async (root) => {
    const url = root.dataset.stateUrl;
    if (! url || presetQueue.inFlight) {
        return;
    }

    const startedAt = Date.now();
    let response;
    try {
        response = await request(url, 'GET');
    } catch {
        return;
    }

    if (! response.ok || presetQueue.inFlight) {
        return;
    }

    const body = await readJson(response);
    (Array.isArray(body.data) ? body.data : []).forEach((light) => applyServerLight(root, light, startedAt));
    updateSummary(root);
    updateActivePreset(root);
};

let pollTimer = null;
let visibilityHandler = null;

// Re-read state every data-poll-seconds while the page is visible, plus once
// when it becomes visible again. Hidden kiosk tabs cost no provider calls.
const startPolling = (root) => {
    clearInterval(pollTimer);
    if (visibilityHandler) {
        document.removeEventListener('visibilitychange', visibilityHandler);
        visibilityHandler = null;
    }

    const seconds = Number(root.dataset.pollSeconds ?? 0);
    if (! root.dataset.stateUrl || ! (seconds > 0)) {
        return;
    }

    pollTimer = setInterval(() => {
        if (! root.isConnected) {
            clearInterval(pollTimer);
            return;
        }
        if (document.visibilityState === 'visible') {
            refreshState(root);
        }
    }, seconds * 1000);

    visibilityHandler = () => {
        if (document.visibilityState === 'visible' && root.isConnected) {
            refreshState(root);
        }
    };
    document.addEventListener('visibilitychange', visibilityHandler);
};

// --- Select mode ----------------------------------------------------------

const renderSelection = (root) => {
    const selecting = isSelecting(root);
    root.querySelectorAll('[data-normal-only]').forEach((element) => {
        element.hidden = selecting;
    });
    root.querySelectorAll('[data-select-only]').forEach((element) => {
        element.hidden = ! selecting;
    });

    const title = root.querySelector('[data-lighting-title]');
    if (title) {
        title.textContent = selecting ? 'Select lamps' : 'Lighting';
    }
    if (selecting) {
        root.querySelectorAll('[data-lighting-summary]').forEach((element) => {
            element.textContent = `${selection.size} selected`;
        });
    }

    const bar = root.querySelector('[data-selbar]');
    if (bar) {
        bar.hidden = ! selecting || sheet.open || isDialogOpen(root);
        const label = bar.querySelector('[data-selbar-label]');
        if (label) {
            label.textContent = selection.size === 1 ? '1 lamp selected' : `${selection.size} lamps selected`;
        }
        const adjust = bar.querySelector('[data-selbar-adjust]');
        if (adjust) {
            adjust.disabled = selection.size === 0;
        }
    }

    allCards(root).forEach(updateCardVisual);
    updateSummary(root);
};

const setMode = (root, mode) => {
    root.dataset.mode = mode;
    selection.clear();
    renderSelection(root);
};

const toggleSelected = (root, card) => {
    const key = lightKey(card);
    if (selection.has(key)) {
        selection.delete(key);
    } else {
        selection.add(key);
    }
    renderSelection(root);
};

// --- Control sheet ----------------------------------------------------------

const sheet = { open: false, keys: [], tab: 'white' };

// Placeholder until scenes register their dialogs.
let isDialogOpen = () => false;

const sheetCards = (root) => sheet.keys
    .flatMap((key) => cardsForKey(root, key).slice(0, 1))
    .filter((card) => card.dataset.reachable === 'true');

// Initial values for one lamp or a mixed selection: brightness and colour
// temperature average, the mode follows the majority (ties go to white).
const sheetModel = (root) => {
    const cards = sheetCards(root);
    const states = cards.map(lightStateFromCard);
    const n = states.length || 1;
    const brightnesses = states.map((state) => (state.power ? state.brightness : 0));
    const temps = states.map((state) => state.colorTemp).filter((value) => value !== null);
    const colourState = states.find((state) => state.mode === 'colour' && state.color) ?? states.find((state) => state.color);
    const { h: hue, s: sat } = colourState ? hexToHsv(colourState.color) : { h: 30, s: 80 };
    const allTemp = states.length > 0 && states.every((state) => state.supportsColorTemp);
    const allColour = states.length > 0 && states.every((state) => state.supportsColor);
    let tab = null;
    if (allTemp && allColour) {
        tab = sheet.tab;
    } else if (allColour) {
        tab = 'colour';
    } else if (allTemp) {
        tab = 'white';
    }

    return {
        cards,
        states,
        brightness: Math.round(brightnesses.reduce((sum, value) => sum + value, 0) / n),
        mixed: states.length > 1
            && (brightnesses.some((value) => value !== brightnesses[0]) || states.some((state) => state.mode !== states[0].mode)),
        temp: temps.length ? Math.round(temps.reduce((sum, value) => sum + value, 0) / temps.length) : 50,
        hue,
        sat,
        power: states.some((state) => state.power),
        showTabs: allTemp && allColour,
        tab,
    };
};

const renderSheet = (root) => {
    const overlay = root?.querySelector('[data-sheet-overlay]');
    if (! overlay || ! sheet.open) {
        return;
    }

    const model = sheetModel(root);
    if (model.cards.length === 0) {
        closeSheet(root);
        return;
    }

    const isWhite = model.tab !== 'colour';
    const preview = isWhite ? tempRgb(model.temp) : hslRgb(model.hue, model.sat, 62);
    const single = model.cards.length === 1;
    const q = (selector) => overlay.querySelector(selector);

    q('[data-sheet-title]').textContent = single ? model.cards[0].dataset.lightName : `${model.cards.length} lamps`;
    q('[data-sheet-sub]').textContent = single
        ? `${model.cards[0].dataset.providerLabel} · ${stateLabel(model.states[0])}`
        : model.cards.map((card) => card.dataset.lightName).join(', ');
    q('[data-sheet-power]').setAttribute('aria-pressed', String(model.power));
    q('[data-sheet-brightness-label]').textContent = `${model.brightness}%`;
    q('[data-sheet-mixed]').hidden = ! model.mixed;
    paintSlider(q('[data-sheet-brightness-slider]'), Math.max(1, model.brightness), { fill: rgbCss(preview), ring: rgbCss(preview) });

    q('[data-sheet-tabs]').hidden = ! model.showTabs;
    overlay.querySelectorAll('[data-sheet-tab]').forEach((tab) => {
        tab.setAttribute('aria-pressed', String(tab.dataset.sheetTab === model.tab));
    });
    q('[data-sheet-panel="white"]').hidden = model.tab !== 'white';
    q('[data-sheet-panel="colour"]').hidden = model.tab !== 'colour';

    paintSlider(q('[data-sheet-temp-slider]'), model.temp, { ring: rgbCss(tempRgb(model.temp)) });
    overlay.querySelectorAll('[data-sheet-chip]').forEach((chip) => {
        const value = Number(chip.dataset.sheetChip);
        chip.setAttribute('aria-pressed', String(model.tab === 'white' && Math.abs(model.temp - value) <= 8));
        chip.querySelector('[data-chip-dot]')?.style.setProperty('background', rgbCss(tempRgb(value)));
    });

    paintSlider(q('[data-sheet-sat-slider]'), model.sat, {
        ring: rgbCss(hslRgb(model.hue, model.sat, 62)),
        track: `linear-gradient(90deg, ${rgbCss(hslRgb(model.hue, 0, 62))}, ${rgbCss(hslRgb(model.hue, 100, 62))})`,
    });

    const ring = q('[data-sheet-ring]');
    ring.hidden = model.tab !== 'colour';
    const rad = (model.hue * Math.PI) / 180;
    ring.style.setProperty('--handle-x', `${50 + Math.sin(rad) * 43}%`);
    ring.style.setProperty('--handle-y', `${50 - Math.cos(rad) * 43}%`);
    ring.style.setProperty('--handle-color', rgbCss(hslRgb(model.hue, 100, 55)));
    q('[data-sheet-disc]').hidden = model.tab === 'colour';
    q('.lighting-sheet__preview')?.style.setProperty('--preview-rgb', preview.join(' '));
    q('[data-sheet-preview-label]').textContent = isWhite
        ? (model.tab === 'white' ? `≈ ${kelvin(model.temp)} · ${tempName(model.temp)}` : 'White')
        : `Hue ${model.hue}° · ${model.sat}% saturation`;
};

const openSheet = (root, cards) => {
    const reachable = cards.filter((card) => card.dataset.reachable === 'true');
    if (reachable.length === 0) {
        return;
    }

    const whites = reachable.filter((card) => card.dataset.mode !== 'colour').length;
    sheet.open = true;
    sheet.keys = reachable.map(lightKey);
    sheet.tab = whites >= reachable.length - whites ? 'white' : 'colour';
    root.querySelector('[data-sheet-overlay]').hidden = false;
    renderSheet(root);
    renderSelection(root);
    root.querySelector('[data-sheet] [data-sheet-close]')?.focus();
};

const closeSheet = (root) => {
    sheet.open = false;
    sheet.keys = [];
    const overlay = root.querySelector('[data-sheet-overlay]');
    if (overlay) {
        overlay.hidden = true;
    }
    renderSelection(root);
};

// Sheet changes coalesce for 250 ms (slider drags), then go out as one
// per-light command, or one group request for a multi-lamp selection.
let sheetPayload = null;
let sheetTargets = [];

const flushSheet = (root) => {
    const payload = sheetPayload;
    const cards = sheetTargets;
    sheetPayload = null;
    sheetTargets = [];
    if (! payload || cards.length === 0) {
        return;
    }

    if (cards.length === 1) {
        send(cards[0], payload);
    } else {
        sendGroup(root, cards, payload);
    }
};

const flushSheetDebounced = debounce(flushSheet, 250);

const sheetChange = (root, patch, payload, immediate = false) => {
    const cards = sheetCards(root);
    cards.forEach((card) => syncLight(root, card, patch));
    updateActivePreset(root);
    sheetPayload = mergePayload(sheetPayload, payload);
    sheetTargets = cards;

    if (immediate) {
        flushSheet(root);
    } else {
        flushSheetDebounced(root);
    }
};

const setupSheet = (root) => {
    const overlay = root.querySelector('[data-sheet-overlay]');
    if (! overlay) {
        return;
    }

    overlay.querySelectorAll('[data-sheet-close]').forEach((button) => button.addEventListener('click', () => closeSheet(root)));

    overlay.querySelector('[data-sheet-power]')?.addEventListener('click', () => {
        const next = ! sheetModel(root).power;
        sheetChange(root, { power: next }, { power: next }, true);
    });

    overlay.querySelector('[data-sheet-brightness]')?.addEventListener('input', (event) => {
        const value = Number(event.target.value);
        sheetChange(root, { power: true, brightness: value }, { power: true, brightness: value });
    });

    overlay.querySelectorAll('[data-sheet-tab]').forEach((tab) => tab.addEventListener('click', () => {
        sheet.tab = tab.dataset.sheetTab;
        renderSheet(root);
    }));

    const setTemp = (value, immediate) => sheetChange(
        root,
        { power: true, mode: 'white', colorTemp: value },
        { power: true, color_temp: value },
        immediate,
    );
    overlay.querySelector('[data-sheet-temp]')?.addEventListener('input', (event) => setTemp(Number(event.target.value), false));
    overlay.querySelectorAll('[data-sheet-chip]').forEach((chip) => chip.addEventListener('click', () => setTemp(Number(chip.dataset.sheetChip), true)));

    const setColour = (hue, sat) => {
        const hex = hsvToHex(hue, sat, 100);
        sheetChange(root, { power: true, mode: 'colour', color: hex }, { power: true, color: hex });
    };
    overlay.querySelector('[data-sheet-sat]')?.addEventListener('input', (event) => {
        setColour(sheetModel(root).hue, Number(event.target.value));
    });

    const ring = overlay.querySelector('[data-sheet-ring]');
    let dragging = false;
    const pickHue = (event) => {
        const rect = ring.getBoundingClientRect();
        const dx = event.clientX - (rect.left + rect.width / 2);
        const dy = event.clientY - (rect.top + rect.height / 2);
        // atan2(dx, -dy): 0deg points up, clockwise — matches the conic-gradient.
        const hue = Math.round((Math.atan2(dx, -dy) * 180 / Math.PI + 360) % 360);
        setColour(hue, sheetModel(root).sat);
    };
    ring?.addEventListener('pointerdown', (event) => {
        dragging = true;
        ring.setPointerCapture?.(event.pointerId);
        pickHue(event);
    });
    ring?.addEventListener('pointermove', (event) => {
        if (dragging) {
            pickHue(event);
        }
    });
    const stop = () => {
        dragging = false;
    };
    ring?.addEventListener('pointerup', stop);
    ring?.addEventListener('pointercancel', stop);
};

// --- Tiles -------------------------------------------------------------------

const LONG_PRESS_MS = 550;

const setupTile = (root, card) => {
    const toggle = card.querySelector('[data-tile-toggle]');
    let pressTimer = null;
    let suppressClick = false;

    toggle?.addEventListener('click', () => {
        if (suppressClick) {
            suppressClick = false;
            return;
        }
        if (card.dataset.reachable !== 'true') {
            return;
        }
        if (isSelecting(root)) {
            toggleSelected(root, card);
            return;
        }

        // Tap = toggle. Turning on a lamp stored at 0 % brings it to 60 %.
        const state = lightStateFromCard(card);
        const payload = { power: ! state.power };
        if (! state.power && state.brightness < 1) {
            payload.brightness = 60;
        }
        syncLight(root, card, payload);
        updateActivePreset(root);
        send(card, payload);
    });

    // Long-press is a shortcut for the visible ⋯ button.
    const cancelPress = () => clearTimeout(pressTimer);
    toggle?.addEventListener('pointerdown', () => {
        if (isSelecting(root) || card.dataset.reachable !== 'true') {
            return;
        }
        pressTimer = setTimeout(() => {
            suppressClick = true;
            openSheet(root, [card]);
        }, LONG_PRESS_MS);
    });
    ['pointerup', 'pointerleave', 'pointercancel'].forEach((type) => toggle?.addEventListener(type, cancelPress));
    toggle?.addEventListener('contextmenu', (event) => event.preventDefault());

    card.querySelector('[data-tile-more]')?.addEventListener('click', () => openSheet(root, [card]));

    // Dragging the bottom bar sets the brightness directly (and turns the lamp on).
    const sendBrightness = debounce((value) => send(card, { power: true, brightness: value }), 250);
    card.querySelectorAll('[data-action="brightness"]').forEach((input) => input.addEventListener('input', (event) => {
        const value = Number(event.target.value);
        syncLight(root, card, { brightness: value, power: true });
        updateActivePreset(root);
        sendBrightness(value);
    }));

    updateCardVisual(card);
    if (card.dataset.url) {
        queueFor(card.dataset.url).confirmed ??= confirmedStateFromCard(card);
    }
};

const paintPresetDots = (root) => {
    presetButtons(root).forEach((button) => {
        const preset = presetFromButton(button);
        let color = null;
        if (preset.colorTemp !== null) {
            color = rgbCss(tempRgb(preset.colorTemp));
        } else if (preset.color) {
            color = preset.color;
        }
        if (color) {
            button.style.setProperty('--scene-dot', color);
        }
    });
};

const setupChrome = (root) => {
    root.querySelectorAll('[data-preset]').forEach((button) => {
        button.addEventListener('click', () => applyPreset(root, button));
    });
    root.querySelectorAll('[data-select-start]').forEach((button) => button.addEventListener('click', () => setMode(root, 'select')));
    root.querySelectorAll('[data-select-cancel]').forEach((button) => button.addEventListener('click', () => setMode(root, 'normal')));
    root.querySelectorAll('[data-select-all]').forEach((button) => button.addEventListener('click', () => {
        allCards(root).filter((card) => card.dataset.reachable === 'true').forEach((card) => selection.add(lightKey(card)));
        renderSelection(root);
    }));
    root.querySelector('[data-selbar-adjust]')?.addEventListener('click', () => {
        const cards = allCards(root).filter((card) => selection.has(lightKey(card)));
        openSheet(root, cards);
    });
    root.querySelector('[data-toast-close]')?.addEventListener('click', () => {
        root.querySelector('[data-lighting-toast]').hidden = true;
    });

    root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet.open) {
            closeSheet(root);
        }
    });
};

export const initLighting = () => {
    const root = document.querySelector('[data-lighting]');
    if (! root || root.dataset.lightingReady === 'true') {
        return;
    }
    root.dataset.lightingReady = 'true';
    root.dataset.mode ??= 'normal';
    lightQueues.clear();
    selection.clear();
    presetQueue.inFlight = false;
    presetQueue.next = null;
    groupQueue.inFlight = false;
    groupQueue.next = null;
    sheet.open = false;
    sheet.keys = [];
    sheetPayload = null;
    sheetTargets = [];

    setupChrome(root);
    setupSheet(root);
    allCards(root).forEach((card) => setupTile(root, card));
    paintPresetDots(root);

    updateSummary(root);
    updateActivePreset(root);
    startPolling(root);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLighting, { once: true });
} else {
    initLighting();
}
document.addEventListener('livewire:navigated', initLighting);
