import { initLighting } from '../../Modules/Lighting/resources/assets/js/lighting.js';

async function flushPromises() {
    for (let i = 0; i < 6; i++) {
        await Promise.resolve();
    }
}

function deferred() {
    let resolve;
    const promise = new Promise((done) => {
        resolve = done;
    });

    return { promise, resolve };
}

const okResponse = (body = {}) => ({ ok: true, status: 200, json: async () => body });

// One lamp tile, mirroring index.blade.php.
const tile = ({
    provider = 'tuya',
    id = '1',
    name = `Lamp ${id}`,
    providerLabel = 'Calex',
    on = true,
    brightness = 10,
    color = '#ff0000',
    mode = 'white',
    colorTemp = 50,
    reachable = true,
    supportsColor = true,
    supportsColorTemp = true,
} = {}) => `
    <article class="lighting-tile" data-light
        data-light-key="${provider}::${id}" data-provider="${provider}" data-light-id="${id}"
        data-light-name="${name}" data-provider-label="${providerLabel}"
        data-url="/lighting/lights/${provider}/${id}"
        data-on="${on}" data-brightness="${brightness}" data-color="${color}" data-mode="${mode}"
        data-color-temp="${colorTemp ?? ''}" data-reachable="${reachable}"
        data-supports-color="${supportsColor}" data-supports-color-temp="${supportsColorTemp}" data-selected="false">
        <button type="button" data-tile-toggle aria-pressed="${on}"><span data-light-state></span></button>
        <button type="button" data-tile-more></button>
        <div data-slider><input type="range" min="1" max="100" value="${Math.max(1, brightness)}" data-action="brightness"></div>
    </article>
`;

const sheetMarkup = `
    <div data-sheet-overlay hidden>
        <section data-sheet>
            <h2 data-sheet-title></h2><p data-sheet-sub></p>
            <button type="button" data-sheet-power aria-pressed="false"></button>
            <button type="button" data-sheet-close></button>
            <span data-sheet-brightness-label></span><span data-sheet-mixed hidden></span>
            <div data-slider data-sheet-brightness-slider><input type="range" min="1" max="100" value="50" data-sheet-brightness></div>
            <div data-sheet-tabs>
                <button type="button" data-sheet-tab="white"></button>
                <button type="button" data-sheet-tab="colour"></button>
            </div>
            <div data-sheet-panel="white">
                <div data-slider data-sheet-temp-slider><input type="range" min="0" max="100" value="50" data-sheet-temp></div>
                <button type="button" data-sheet-chip="0"><span data-chip-dot></span></button>
                <button type="button" data-sheet-chip="50"><span data-chip-dot></span></button>
                <button type="button" data-sheet-chip="100"><span data-chip-dot></span></button>
            </div>
            <div data-sheet-panel="colour" hidden>
                <div data-slider data-sheet-sat-slider><input type="range" min="0" max="100" value="100" data-sheet-sat></div>
            </div>
            <div class="lighting-sheet__preview">
                <button type="button" data-sheet-ring hidden></button>
                <span data-sheet-disc></span>
                <span data-sheet-preview-label></span>
            </div>
        </section>
    </div>
`;

const page = (tiles, { presets = '', attrs = '' } = {}) => `
    <div data-lighting data-mode="normal"
        data-preset-url-template="/lighting/presets/__PRESET__"
        data-group-url="/lighting/lights" ${attrs}>
        <h1 data-lighting-title>Lighting</h1>
        <p data-lighting-summary></p>
        <div data-normal-only><button type="button" data-select-start>Select</button></div>
        <div data-select-only hidden>
            <button type="button" data-select-all>Select all</button>
            <button type="button" data-select-cancel>Cancel</button>
        </div>
        ${presets}
        ${tiles}
        <div data-selbar hidden>
            <span data-selbar-label></span>
            <button type="button" data-selbar-adjust disabled>Adjust</button>
        </div>
        <div data-lighting-toast hidden><span data-lighting-toast-text></span></div>
        ${sheetMarkup}
    </div>
`;

const slide = (input, value) => {
    input.value = String(value);
    input.dispatchEvent(new Event('input'));
};

const bodyOf = (call) => JSON.parse(call[1].body);

describe('lighting kiosk', () => {
    beforeEach(() => {
        document.head.innerHTML = '<meta name="csrf-token" content="csrf-test-token">';
    });

    describe('tiles', () => {
        it('renders the state line and the header summary', () => {
            document.body.innerHTML = page(
                tile({ id: '1', on: true, brightness: 70, colorTemp: 10 })
                + tile({ id: '2', on: true, brightness: 40, mode: 'colour' })
                + tile({ id: '3', on: false })
                + tile({ id: '4', reachable: false }),
            );

            initLighting();

            const states = [...document.querySelectorAll('[data-light-state]')].map((el) => el.textContent);
            expect(states).toEqual(['70% · Warm white', '40% · Colour', 'Off', 'Offline']);
            expect(document.querySelector('[data-lighting-summary]').textContent).toBe('2 of 4 on');
            expect(document.querySelectorAll('[data-tile-toggle]')[3].disabled).toBe(true);
        });

        it('toggles a lamp with one tap', async () => {
            document.body.innerHTML = page(tile({ id: '1', on: false, brightness: 0 }));
            fetch.mockResolvedValue(okResponse());

            initLighting();
            const card = document.querySelector('[data-light]');
            card.querySelector('[data-tile-toggle]').click();

            expect(card.dataset.on).toBe('true');
            expect(card.querySelector('[data-tile-toggle]').getAttribute('aria-pressed')).toBe('true');
            expect(fetch).toHaveBeenCalledTimes(1);
            expect(fetch.mock.calls[0][0]).toBe('/lighting/lights/tuya/1');
            expect(fetch.mock.calls[0][1].method).toBe('PUT');
            // A lamp stored at 0 % comes on at 60 %.
            expect(bodyOf(fetch.mock.calls[0])).toEqual({ power: true, brightness: 60 });
            await flushPromises();
        });

        it('sets brightness by dragging the tile bar', () => {
            jest.useFakeTimers();
            document.body.innerHTML = page(tile({ id: '1', on: false, brightness: 30 }));
            fetch.mockResolvedValue(okResponse());

            initLighting();
            const card = document.querySelector('[data-light]');
            const bar = card.querySelector('[data-action="brightness"]');
            slide(bar, 55);
            slide(bar, 80);

            expect(card.dataset.brightness).toBe('80');
            expect(card.dataset.on).toBe('true');
            expect(card.querySelector('[data-slider]').style.getPropertyValue('--slider-pct')).not.toBe('');
            expect(fetch).not.toHaveBeenCalled();

            jest.advanceTimersByTime(250);
            expect(fetch).toHaveBeenCalledTimes(1);
            expect(bodyOf(fetch.mock.calls[0])).toEqual({ power: true, brightness: 80 });
        });

        it('opens the control sheet from the more button', () => {
            document.body.innerHTML = page(tile({ id: '1', name: 'Desk spot', brightness: 70, colorTemp: 95 }));

            initLighting();
            document.querySelector('[data-tile-more]').click();

            expect(document.querySelector('[data-sheet-overlay]').hidden).toBe(false);
            expect(document.querySelector('[data-sheet-title]').textContent).toBe('Desk spot');
            expect(document.querySelector('[data-sheet-sub]').textContent).toBe('Calex · 70% · Cool white');
            expect(document.querySelector('[data-sheet-preview-label]').textContent).toBe('≈ 6,300 K · Cool white');
        });
    });

    describe('select mode', () => {
        it('selects tiles instead of toggling them and sends one group request', () => {
            jest.useFakeTimers();
            document.body.innerHTML = page(
                tile({ id: '1', name: 'Spot 1' })
                + tile({ provider: 'govee', id: 'DEV1', name: 'Strip', providerLabel: 'Govee' })
                + tile({ id: '3', name: 'Spot 3' }),
            );
            fetch.mockResolvedValue(okResponse({ data: { applied: 2, failed: [] } }));

            initLighting();
            document.querySelector('[data-select-start]').click();
            const root = document.querySelector('[data-lighting]');
            expect(root.dataset.mode).toBe('select');
            expect(document.querySelector('[data-lighting-title]').textContent).toBe('Select lamps');

            const [one, strip] = document.querySelectorAll('[data-light]');
            one.querySelector('[data-tile-toggle]').click();
            strip.querySelector('[data-tile-toggle]').click();

            expect(fetch).not.toHaveBeenCalled();
            expect(one.dataset.selected).toBe('true');
            expect(document.querySelector('[data-selbar]').hidden).toBe(false);
            expect(document.querySelector('[data-selbar-label]').textContent).toBe('2 lamps selected');

            document.querySelector('[data-selbar-adjust]').click();
            expect(document.querySelector('[data-sheet-title]').textContent).toBe('2 lamps');

            slide(document.querySelector('[data-sheet-brightness]'), 35);
            document.querySelector('[data-sheet-chip="0"]').click();

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(fetch.mock.calls[0][0]).toBe('/lighting/lights');
            expect(bodyOf(fetch.mock.calls[0])).toEqual({
                lights: [{ provider: 'tuya', id: '1' }, { provider: 'govee', id: 'DEV1' }],
                changes: { power: true, brightness: 35, color_temp: 0 },
            });
        });

        it('rolls back lamps the group request names as failed', async () => {
            document.body.innerHTML = page(tile({ id: '1', name: 'Spot 1', on: true }) + tile({ id: '2', name: 'Spot 2', on: true }));
            fetch.mockResolvedValue(okResponse({ data: { applied: 1, failed: ['Spot 2'] } }));

            initLighting();
            document.querySelector('[data-select-start]').click();
            document.querySelector('[data-select-all]').click();
            document.querySelector('[data-selbar-adjust]').click();
            document.querySelector('[data-sheet-power]').click();
            await flushPromises();
            await flushPromises();

            const [one, two] = document.querySelectorAll('[data-light]');
            expect(one.dataset.on).toBe('false');
            expect(two.dataset.on).toBe('true');
            expect(two.dataset.failed).toBe('true');
            expect(document.querySelector('[data-lighting-toast]').hidden).toBe(false);
        });
    });

    describe('control sheet', () => {
        it('hides controls a lamp does not support', () => {
            document.body.innerHTML = page(tile({ id: '1', supportsColor: true, supportsColorTemp: false, mode: 'colour', color: '#00ff00' }));

            initLighting();
            document.querySelector('[data-tile-more]').click();

            expect(document.querySelector('[data-sheet-tabs]').hidden).toBe(true);
            expect(document.querySelector('[data-sheet-panel="white"]').hidden).toBe(true);
            expect(document.querySelector('[data-sheet-panel="colour"]').hidden).toBe(false);
            expect(document.querySelector('[data-sheet-ring]').hidden).toBe(false);
        });

        it('hides both colour panels for a dimmable-only lamp', () => {
            document.body.innerHTML = page(tile({ id: '1', supportsColor: false, supportsColorTemp: false, colorTemp: null }));

            initLighting();
            document.querySelector('[data-tile-more]').click();

            expect(document.querySelector('[data-sheet-tabs]').hidden).toBe(true);
            expect(document.querySelector('[data-sheet-panel="white"]').hidden).toBe(true);
            expect(document.querySelector('[data-sheet-panel="colour"]').hidden).toBe(true);
        });

        it('starts a mixed selection at the average brightness and the majority mode', () => {
            document.body.innerHTML = page(
                tile({ id: '1', brightness: 70, mode: 'white', colorTemp: 10 })
                + tile({ id: '2', brightness: 40, mode: 'white', colorTemp: 30 })
                + tile({ id: '3', brightness: 100, mode: 'colour', color: '#0000ff' })
                + tile({ id: '4', on: false, brightness: 50 }),
            );

            initLighting();
            document.querySelector('[data-select-start]').click();
            document.querySelector('[data-select-all]').click();
            document.querySelector('[data-selbar-adjust]').click();

            // (70 + 40 + 100 + 0) / 4 = 52.5 -> 53; three white lamps vs one colour.
            expect(document.querySelector('[data-sheet-brightness-label]').textContent).toBe('53%');
            expect(document.querySelector('[data-sheet-brightness]').value).toBe('53');
            expect(document.querySelector('[data-sheet-mixed]').hidden).toBe(false);
            expect(document.querySelector('[data-sheet-tab="white"]').getAttribute('aria-pressed')).toBe('true');
            // Colour temperature averages over the lamps that report one: (10 + 30 + 50 + 50) / 4 = 35.
            expect(document.querySelector('[data-sheet-temp]').value).toBe('35');
            expect(document.querySelector('[data-sheet-power]').getAttribute('aria-pressed')).toBe('true');
        });

        it('sends colour as a hex built from hue and saturation', () => {
            jest.useFakeTimers();
            document.body.innerHTML = page(tile({ id: '1', mode: 'colour', color: '#ff0000' }));
            fetch.mockResolvedValue(okResponse());

            initLighting();
            document.querySelector('[data-tile-more]').click();
            slide(document.querySelector('[data-sheet-sat]'), 50);
            jest.advanceTimersByTime(250);

            expect(bodyOf(fetch.mock.calls[0])).toEqual({ power: true, color: '#ff8080' });
            expect(document.querySelector('[data-light]').dataset.mode).toBe('colour');
        });
    });

    describe('presets', () => {
        const preset = (key, attrs) => `<button type="button" data-preset="${key}" data-preset-label="${key}" ${attrs}>${key}</button>`;

        it('posts presets and updates reachable tiles optimistically', async () => {
            document.body.innerHTML = page(
                tile({ id: '1', on: false, brightness: 10, mode: 'white' })
                + tile({ id: '2', on: false, brightness: 10, reachable: false }),
                { presets: preset('movie', 'data-preset-power="true" data-preset-brightness="42" data-preset-color="#7f96ff"') },
            );
            fetch.mockResolvedValueOnce(okResponse({ data: { preset: { key: 'movie', power: true, brightness: 42, color: '#7f96ff' }, failed_lights: [] } }));

            initLighting();
            document.querySelector('[data-preset="movie"]').click();
            await flushPromises();

            expect(fetch).toHaveBeenCalledWith('/lighting/presets/movie', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': 'csrf-test-token',
                },
                body: null,
            });

            const [reachable, unreachable] = document.querySelectorAll('[data-light]');
            expect(reachable.dataset.on).toBe('true');
            expect(reachable.dataset.brightness).toBe('42');
            expect(reachable.dataset.color).toBe('#7f96ff');
            expect(reachable.dataset.mode).toBe('colour');
            expect(unreachable.dataset.on).toBe('false');
            expect(document.querySelector('[data-preset="movie"]').getAttribute('aria-pressed')).toBe('true');
        });

        it('turns lamps off without changing brightness', async () => {
            document.body.innerHTML = page(tile({ id: '1', on: true, brightness: 80 }), { presets: preset('off', 'data-preset-power="false"') });
            fetch.mockResolvedValueOnce(okResponse({ data: { preset: { key: 'off', power: false }, failed_lights: [] } }));

            initLighting();
            document.querySelector('[data-preset="off"]').click();
            await flushPromises();

            const card = document.querySelector('[data-light]');
            expect(card.dataset.on).toBe('false');
            expect(card.dataset.brightness).toBe('80');
            expect(document.querySelector('[data-lighting-summary]').textContent).toBe('0 of 1 on');
        });

        it('applies targeted presets only to matching lamps', async () => {
            document.body.innerHTML = page(
                tile({ id: '1', name: 'LED Strip', on: false }) + tile({ id: '2', name: 'Desk lamp', on: false }),
                { presets: preset('night_light', 'data-preset-power="true" data-preset-brightness="1" data-preset-color="#ff8559" data-preset-target-name-contains="strip"') },
            );
            fetch.mockResolvedValueOnce(okResponse({ data: { preset: { key: 'night_light', power: true, brightness: 1, color: '#ff8559', target_name_contains: ['strip'] }, failed_lights: [] } }));

            initLighting();
            document.querySelector('[data-preset="night_light"]').click();
            await flushPromises();

            const [strip, lamp] = document.querySelectorAll('[data-light]');
            expect(strip.dataset.on).toBe('true');
            expect(strip.dataset.color).toBe('#ff8559');
            expect(lamp.dataset.on).toBe('false');
            expect(document.querySelector('[data-preset="night_light"]').dataset.active).toBe('true');
        });

        it('applies colour-temperature presets as white mode', async () => {
            document.body.innerHTML = page(
                tile({ id: '1', on: false, mode: 'colour', color: '#ff0000' }),
                { presets: preset('bright', 'data-preset-power="true" data-preset-brightness="100" data-preset-color-temp="100"') },
            );
            fetch.mockResolvedValueOnce(okResponse({ data: { preset: { key: 'bright', power: true, brightness: 100, color: null, color_temp: 100 }, failed_lights: [] } }));

            initLighting();
            document.querySelector('[data-preset="bright"]').click();
            await flushPromises();

            const card = document.querySelector('[data-light]');
            expect(card.dataset.mode).toBe('white');
            expect(card.dataset.colorTemp).toBe('100');
            expect(card.dataset.color).toBe('#ff0000');
            expect(card.querySelector('[data-light-state]').textContent).toBe('100% · Cool white');
        });

        it('keeps controls usable and runs the latest preset after the running one', async () => {
            const pending = deferred();
            document.body.innerHTML = page(tile({ id: '1' }), {
                presets: preset('cozy', 'data-preset-power="true"') + preset('movie', 'data-preset-power="true"') + preset('night', 'data-preset-power="true"'),
            });
            const ok = okResponse({ data: { preset: { power: true }, failed_lights: [] } });
            fetch.mockReturnValueOnce(pending.promise).mockResolvedValue(ok);

            initLighting();
            document.querySelector('[data-preset="cozy"]').click();
            document.querySelector('[data-preset="movie"]').click();
            document.querySelector('[data-preset="night"]').click();

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(document.querySelector('[data-tile-toggle]').disabled).toBe(false);

            pending.resolve(ok);
            await flushPromises();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(2);
            expect(fetch.mock.calls[1][0]).toBe('/lighting/presets/night');
        });

        it('detects the active preset and clears it after a manual change', () => {
            document.body.innerHTML = page(
                tile({ id: '1', on: true, brightness: 70, mode: 'white', colorTemp: 0 }),
                { presets: preset('cozy', 'data-preset-power="true" data-preset-brightness="70" data-preset-color-temp="0"') },
            );
            fetch.mockResolvedValue(okResponse());

            initLighting();
            expect(document.querySelector('[data-preset="cozy"]').dataset.active).toBe('true');

            document.querySelector('[data-tile-toggle]').click();
            expect(document.querySelector('[data-preset="cozy"]').dataset.active).toBe('false');
        });
    });

    describe('per-light command pipeline', () => {
        afterEach(() => {
            delete document.visibilityState;
        });

        it('coalesces slider moves while a command is in flight (latest wins)', async () => {
            jest.useFakeTimers();
            const first = deferred();
            document.body.innerHTML = page(tile({ id: '1' }) + tile({ id: '2' }));
            fetch.mockReturnValueOnce(first.promise).mockResolvedValue(okResponse());

            initLighting();
            const [lamp, other] = document.querySelectorAll('[data-light]');
            const slider = lamp.querySelector('[data-action="brightness"]');

            slide(slider, 20);
            jest.advanceTimersByTime(250);
            slide(slider, 30);
            jest.advanceTimersByTime(250);
            slide(slider, 40);
            jest.advanceTimersByTime(250);

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(lamp.dataset.pending).toBe('true');
            expect(slider.disabled).toBe(false);

            // Another lamp is not blocked by the pending one.
            other.querySelector('[data-tile-toggle]').click();
            expect(fetch).toHaveBeenCalledTimes(2);
            expect(fetch.mock.calls[1][0]).toBe('/lighting/lights/tuya/2');

            first.resolve(okResponse());
            await flushPromises();
            await flushPromises();

            const lampCalls = fetch.mock.calls.filter(([url]) => url === '/lighting/lights/tuya/1');
            expect(lampCalls).toHaveLength(2);
            expect(bodyOf(lampCalls[0])).toEqual({ power: true, brightness: 20 });
            expect(bodyOf(lampCalls[1])).toEqual({ power: true, brightness: 40 });
            expect(lamp.dataset.pending).toBeUndefined();
        });

        it('rolls back to the last confirmed state and flags the lamp on failure', async () => {
            document.body.innerHTML = page(tile({ id: '1', name: 'Spot 1', on: true }));
            fetch.mockResolvedValueOnce({ ok: false, status: 502, json: async () => ({}) });

            initLighting();
            const lamp = document.querySelector('[data-light]');
            lamp.querySelector('[data-tile-toggle]').click();
            expect(lamp.dataset.on).toBe('false');

            await flushPromises();
            await flushPromises();

            expect(lamp.dataset.on).toBe('true');
            expect(lamp.dataset.failed).toBe('true');
            const toast = document.querySelector('[data-lighting-toast]');
            expect(toast.hidden).toBe(false);
            expect(toast.textContent).toBe("Couldn't reach Calex — Spot 1 was set back to its last known state.");
            expect(lamp.dataset.pending).toBeUndefined();
        });

        it('retries a 409 busy response once after 500 ms', async () => {
            jest.useFakeTimers();
            document.body.innerHTML = page(tile({ id: '1', on: true }));
            fetch
                .mockResolvedValueOnce({ ok: false, status: 409, json: async () => ({}) })
                .mockResolvedValueOnce(okResponse());

            initLighting();
            document.querySelector('[data-tile-toggle]').click();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(1);
            jest.advanceTimersByTime(500);
            await flushPromises();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(2);
            const lamp = document.querySelector('[data-light]');
            expect(lamp.dataset.on).toBe('false');
            expect(lamp.dataset.failed).toBeUndefined();
        });

        it('polls state only while visible and skips lamps with a pending command', async () => {
            jest.useFakeTimers();
            let visibility = 'hidden';
            Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => visibility });
            document.body.innerHTML = page(tile({ id: '1', on: true }) + tile({ id: '2', on: true }), {
                attrs: 'data-state-url="/lighting/state" data-poll-seconds="120"',
            });

            initLighting();
            jest.advanceTimersByTime(120000);
            expect(fetch).not.toHaveBeenCalled();

            const pendingCommand = deferred();
            fetch.mockReturnValueOnce(pendingCommand.promise);
            document.querySelector('[data-tile-toggle]').click();
            expect(fetch).toHaveBeenCalledTimes(1);

            fetch.mockResolvedValueOnce(okResponse({
                data: [
                    { provider: 'tuya', id: '1', on: true, brightness: 90, color: null, reachable: true, mode: 'white', color_temp: 50 },
                    { provider: 'tuya', id: '2', on: false, brightness: 55, color: null, reachable: false, mode: 'white', color_temp: 0 },
                ],
                unreachable_providers: [],
            }));

            jest.setSystemTime(Date.now() + 1000);
            visibility = 'visible';
            jest.advanceTimersByTime(120000);
            await flushPromises();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(2);
            expect(fetch.mock.calls[1][0]).toBe('/lighting/state');
            expect(fetch.mock.calls[1][1].method).toBe('GET');

            const [pendingLamp, idleLamp] = document.querySelectorAll('[data-light]');
            expect(pendingLamp.dataset.on).toBe('false');
            expect(pendingLamp.dataset.brightness).toBe('10');
            expect(idleLamp.dataset.on).toBe('false');
            expect(idleLamp.dataset.brightness).toBe('55');
            expect(idleLamp.dataset.colorTemp).toBe('0');
            expect(idleLamp.dataset.reachable).toBe('false');
            expect(idleLamp.querySelector('[data-light-state]').textContent).toBe('Offline');
        });

        it('refreshes once when the page becomes visible again', () => {
            let visibility = 'hidden';
            Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => visibility });
            document.body.innerHTML = page(tile({ id: '1' }), { attrs: 'data-state-url="/lighting/state" data-poll-seconds="120"' });
            fetch.mockResolvedValue(okResponse({ data: [], unreachable_providers: [] }));

            initLighting();
            visibility = 'visible';
            document.dispatchEvent(new Event('visibilitychange'));

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(fetch.mock.calls[0][0]).toBe('/lighting/state');
        });
    });
});
