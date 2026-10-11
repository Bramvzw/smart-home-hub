import { initLighting } from '../../Modules/Lighting/resources/assets/js/lighting.js';

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
}

function deferred() {
    let resolve;
    const promise = new Promise((done) => {
        resolve = done;
    });

    return { promise, resolve };
}

describe('lighting controls', () => {
    beforeEach(() => {
        document.head.innerHTML = '<meta name="csrf-token" content="csrf-test-token">';
    });

    it('posts presets and updates reachable light cards optimistically', async () => {
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button
                    type="button"
                    data-preset="cozy"
                    data-preset-label="Cozy"
                    data-preset-power="true"
                    data-preset-brightness="45"
                    data-preset-color="#ff9f4a"
                >Cozy</button>

                <article data-light data-reachable="true" data-supports-color="true">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="10">
                    <input type="color" data-action="color" value="#ffffff">
                </article>

                <article data-light data-reachable="false" data-supports-color="true">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="10">
                    <input type="color" data-action="color" value="#ffffff">
                </article>
            </div>
        `;

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: {
                    preset: {
                        key: 'cozy',
                        power: true,
                        brightness: 45,
                        color: '#ff9f4a',
                    },
                    failed_lights: [],
                },
            }),
        });

        initLighting();
        document.querySelector('[data-preset="cozy"]').click();
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith('/lighting/presets/cozy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': 'csrf-test-token',
            },
            body: null,
        });

        const [reachable, unreachable] = document.querySelectorAll('[data-light]');
        expect(reachable.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('true');
        expect(reachable.querySelector('[data-action="brightness"]').value).toBe('45');
        expect(reachable.querySelector('[data-action="color"]').value).toBe('#ff9f4a');

        expect(unreachable.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('false');
        expect(unreachable.querySelector('[data-action="brightness"]').value).toBe('10');
        expect(unreachable.querySelector('[data-action="color"]').value).toBe('#ffffff');
        expect(document.querySelector('[data-preset="cozy"]').dataset.active).toBe('true');
    });

    it('turns reachable lights off without changing brightness', async () => {
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button type="button" data-preset="off" data-preset-power="false">All off</button>
                <article data-light data-reachable="true" data-supports-color="false">
                    <button type="button" data-action="power" aria-checked="true"></button>
                    <input type="range" data-action="brightness" value="80">
                </article>
            </div>
        `;

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: {
                    preset: {
                        key: 'off',
                        power: false,
                        brightness: null,
                        color: null,
                    },
                    failed_lights: [],
                },
            }),
        });

        initLighting();
        document.querySelector('[data-preset="off"]').click();
        await flushPromises();

        const card = document.querySelector('[data-light]');
        expect(card.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('false');
        expect(card.querySelector('[data-action="brightness"]').value).toBe('80');
    });

    it('applies targeted presets only to matching light cards', async () => {
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button
                    type="button"
                    data-preset="night_light"
                    data-preset-label="Night light"
                    data-preset-power="true"
                    data-preset-brightness="1"
                    data-preset-color="#ff8559"
                    data-preset-target-name-contains="strip"
                >Night light</button>

                <article data-light data-light-row data-light-name="LED Strip" data-reachable="true" data-supports-color="true">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="45">
                    <input type="color" data-action="color" value="#ffffff">
                </article>

                <article data-light data-light-row data-light-name="Desk lamp" data-reachable="true" data-supports-color="true">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="45">
                    <input type="color" data-action="color" value="#ffffff">
                </article>
            </div>
        `;

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: {
                    preset: {
                        key: 'night_light',
                        label: 'Night light',
                        power: true,
                        brightness: 1,
                        color: '#ff8559',
                        target_name_contains: ['strip'],
                    },
                    failed_lights: [],
                },
            }),
        });

        initLighting();
        document.querySelector('[data-preset="night_light"]').click();
        await flushPromises();

        const [strip, lamp] = document.querySelectorAll('[data-light]');
        expect(strip.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('true');
        expect(strip.querySelector('[data-action="brightness"]').value).toBe('1');
        expect(strip.querySelector('[data-action="color"]').value).toBe('#ff8559');

        expect(lamp.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('false');
        expect(lamp.querySelector('[data-action="brightness"]').value).toBe('45');
        expect(lamp.querySelector('[data-action="color"]').value).toBe('#ffffff');
        expect(document.querySelector('[data-preset="night_light"]').dataset.active).toBe('true');
    });

    it('keeps controls usable and runs the latest preset after the running one', async () => {
        const pending = deferred();
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button type="button" data-preset="cozy" data-preset-power="true">Cozy</button>
                <button type="button" data-preset="movie" data-preset-power="true">Movie</button>
                <button type="button" data-preset="night" data-preset-power="true">Night</button>
                <article data-light data-reachable="true" data-supports-color="false">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="10">
                </article>
            </div>
        `;

        const ok = { ok: true, json: async () => ({ data: { preset: { power: true, brightness: null, color: null }, failed_lights: [] } }) };
        fetch.mockReturnValueOnce(pending.promise).mockResolvedValue(ok);

        initLighting();
        document.querySelector('[data-preset="cozy"]').click();
        document.querySelector('[data-preset="movie"]').click();
        document.querySelector('[data-preset="night"]').click();

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch.mock.calls[0][0]).toBe('/lighting/presets/cozy');
        expect(document.querySelector('[data-preset="movie"]').disabled).toBe(false);
        expect(document.querySelector('[data-action="brightness"]').disabled).toBe(false);

        pending.resolve(ok);
        await flushPromises();
        await flushPromises();

        // Movie was superseded by Night while Cozy was running.
        expect(fetch).toHaveBeenCalledTimes(2);
        expect(fetch.mock.calls[1][0]).toBe('/lighting/presets/night');
    });

    it('switches the focused light detail panel from the lamp list', () => {
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <span data-selected-provider>Calex</span>
                <span data-selected-name>Spot 1</span>

                <article data-light data-light-row data-light-key="tuya::1" data-light-name="Spot 1" data-provider-label="Calex" data-on="true" data-brightness="40" data-color="#ffc26b" data-reachable="true" data-supports-color="true">
                    <button type="button" data-light-select data-light-key="tuya::1" aria-selected="true"></button>
                    <button type="button" data-action="power" aria-checked="true"></button>
                </article>
                <article data-light data-light-row data-light-key="govee::1" data-light-name="LED Strip" data-provider-label="Govee" data-on="true" data-brightness="72" data-color="#7f96ff" data-reachable="true" data-supports-color="true">
                    <button type="button" data-light-select data-light-key="govee::1" aria-selected="false"></button>
                    <button type="button" data-action="power" aria-checked="true"></button>
                </article>

                <section data-light data-light-panel data-light-key="tuya::1" data-on="true" data-brightness="40" data-color="#ffc26b" data-reachable="true" data-supports-color="true"></section>
                <section data-light data-light-panel data-light-key="govee::1" data-on="true" data-brightness="72" data-color="#7f96ff" data-reachable="true" data-supports-color="true" hidden></section>
            </div>
        `;

        initLighting();
        document.querySelector('[data-light-key="govee::1"][data-light-select]').click();

        expect(document.querySelector('[data-selected-provider]').textContent).toBe('Govee');
        expect(document.querySelector('[data-selected-name]').textContent).toBe('LED Strip');
        expect(document.querySelector('[data-light-row][data-light-key="tuya::1"]').dataset.selected).toBe('false');
        expect(document.querySelector('[data-light-row][data-light-key="govee::1"]').dataset.selected).toBe('true');
        expect(document.querySelector('[data-light-panel][data-light-key="tuya::1"]').hidden).toBe(true);
        expect(document.querySelector('[data-light-panel][data-light-key="govee::1"]').hidden).toBe(false);
    });

    it('detects active presets and clears them after a custom change', () => {
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button
                    type="button"
                    data-preset="cozy"
                    data-preset-label="Cozy"
                    data-preset-power="true"
                    data-preset-brightness="45"
                    data-preset-color="#ff9f4a"
                >Cozy</button>

                <article data-light data-light-row data-light-key="tuya::1" data-reachable="true" data-supports-color="true">
                    <button type="button" data-action="power" aria-checked="true"></button>
                    <input type="range" data-action="brightness" value="45">
                    <input type="color" data-action="color" value="#ff9f4a">
                </article>
            </div>
        `;

        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({}),
        });

        initLighting();

        expect(document.querySelector('[data-preset="cozy"]').dataset.active).toBe('true');

        document.querySelector('[data-action="power"]').click();

        expect(document.querySelector('[data-preset="cozy"]').dataset.active).toBe('false');
    });

    it('applies colour-temperature presets as white mode on lamps that support it', async () => {
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button type="button" data-preset="bright" data-preset-label="Bright" data-preset-power="true" data-preset-brightness="100" data-preset-color-temp="100">Bright</button>
                <article data-light data-light-row data-light-key="tuya::1" data-reachable="true" data-supports-color="true" data-supports-color-temp="true" data-mode="colour" data-color="#ff0000">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="10">
                    <span data-light-state></span>
                </article>
            </div>
        `;

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ data: { preset: { key: 'bright', power: true, brightness: 100, color: null, color_temp: 100 }, failed_lights: [] } }),
        });

        initLighting();
        document.querySelector('[data-preset="bright"]').click();
        await flushPromises();

        const card = document.querySelector('[data-light]');
        expect(card.dataset.mode).toBe('white');
        expect(card.dataset.colorTemp).toBe('100');
        expect(card.dataset.color).toBe('#ff0000');
        expect(card.querySelector('[data-light-state]').textContent).toBe('100% · white');
        expect(document.querySelector('[data-preset="bright"]').dataset.active).toBe('true');
    });

    describe('per-light command pipeline', () => {
        const lampMarkup = (key, url, extra = '') => `
            <article data-light data-light-row data-light-key="${key}" data-light-name="${key}" data-provider-label="Calex" data-url="${url}" data-reachable="true" data-supports-color="true" data-on="true" data-brightness="10" ${extra}>
                <button type="button" data-action="power" aria-checked="true"></button>
                <input type="range" data-action="brightness" value="10">
            </article>
        `;

        const slide = (input, value) => {
            input.value = String(value);
            input.dispatchEvent(new Event('input'));
        };

        const okResponse = () => ({ ok: true, status: 200, json: async () => ({}) });

        afterEach(() => {
            delete document.visibilityState;
        });

        it('coalesces slider moves while a command is in flight (latest wins)', async () => {
            jest.useFakeTimers();
            const first = deferred();
            document.body.innerHTML = `
                <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                    <div data-lighting-toast hidden></div>
                    ${lampMarkup('tuya::1', '/lighting/lights/tuya/1')}
                    ${lampMarkup('tuya::2', '/lighting/lights/tuya/2')}
                </div>
            `;
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
            other.querySelector('[data-action="power"]').click();
            expect(fetch).toHaveBeenCalledTimes(2);
            expect(fetch.mock.calls[1][0]).toBe('/lighting/lights/tuya/2');

            first.resolve(okResponse());
            await flushPromises();
            await flushPromises();

            const lampCalls = fetch.mock.calls.filter(([url]) => url === '/lighting/lights/tuya/1');
            expect(lampCalls).toHaveLength(2);
            expect(JSON.parse(lampCalls[0][1].body)).toEqual({ power: true, brightness: 20 });
            expect(JSON.parse(lampCalls[1][1].body)).toEqual({ power: true, brightness: 40 });
            expect(lamp.dataset.pending).toBeUndefined();
        });

        it('rolls back to the last confirmed state and flags the lamp on failure', async () => {
            document.body.innerHTML = `
                <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                    <div data-lighting-toast hidden></div>
                    ${lampMarkup('tuya::1', '/lighting/lights/tuya/1')}
                </div>
            `;
            fetch.mockResolvedValueOnce({ ok: false, status: 502, json: async () => ({}) });

            initLighting();
            const lamp = document.querySelector('[data-light]');
            const power = lamp.querySelector('[data-action="power"]');

            power.click();
            expect(power.getAttribute('aria-checked')).toBe('false');

            await flushPromises();
            await flushPromises();

            expect(power.getAttribute('aria-checked')).toBe('true');
            expect(lamp.dataset.on).toBe('true');
            expect(lamp.classList.contains('ring-[var(--hub-danger)]')).toBe(true);
            const toast = document.querySelector('[data-lighting-toast]');
            expect(toast.hidden).toBe(false);
            expect(toast.textContent).toBe("Couldn't reach Calex");
            expect(lamp.dataset.pending).toBeUndefined();
        });

        it('retries a 409 busy response once after 500 ms', async () => {
            jest.useFakeTimers();
            document.body.innerHTML = `
                <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                    ${lampMarkup('tuya::1', '/lighting/lights/tuya/1')}
                </div>
            `;
            fetch
                .mockResolvedValueOnce({ ok: false, status: 409, json: async () => ({}) })
                .mockResolvedValueOnce(okResponse());

            initLighting();
            const power = document.querySelector('[data-action="power"]');
            power.click();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(1);
            jest.advanceTimersByTime(500);
            await flushPromises();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(2);
            expect(power.getAttribute('aria-checked')).toBe('false');
            expect(document.querySelector('[data-light]').classList.contains('ring-[var(--hub-danger)]')).toBe(false);
        });

        it('polls state only while visible and skips lamps with a pending command', async () => {
            jest.useFakeTimers();
            let visibility = 'hidden';
            Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => visibility });

            document.body.innerHTML = `
                <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__" data-state-url="/lighting/state" data-poll-seconds="120">
                    ${lampMarkup('tuya::1', '/lighting/lights/tuya/1')}
                    ${lampMarkup('tuya::2', '/lighting/lights/tuya/2')}
                </div>
            `;

            initLighting();
            jest.advanceTimersByTime(120000);
            expect(fetch).not.toHaveBeenCalled();

            // Lamp 1 gets a command that stays in flight.
            const pendingCommand = deferred();
            fetch.mockReturnValueOnce(pendingCommand.promise);
            document.querySelectorAll('[data-light]')[0].querySelector('[data-action="power"]').click();
            expect(fetch).toHaveBeenCalledTimes(1);

            fetch.mockResolvedValueOnce({
                ok: true,
                status: 200,
                json: async () => ({
                    data: [
                        { provider: 'tuya', id: '1', on: true, brightness: 90, color: null, reachable: true, mode: 'white', color_temp: 50 },
                        { provider: 'tuya', id: '2', on: false, brightness: 55, color: null, reachable: true, mode: 'white', color_temp: 0 },
                    ],
                    unreachable_providers: [],
                }),
            });

            jest.setSystemTime(Date.now() + 1000);
            visibility = 'visible';
            jest.advanceTimersByTime(120000);
            await flushPromises();
            await flushPromises();

            expect(fetch).toHaveBeenCalledTimes(2);
            expect(fetch.mock.calls[1][0]).toBe('/lighting/state');
            expect(fetch.mock.calls[1][1].method).toBe('GET');

            const [pendingLamp, idleLamp] = document.querySelectorAll('[data-light]');
            expect(pendingLamp.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('false');
            expect(pendingLamp.querySelector('[data-action="brightness"]').value).toBe('10');
            expect(idleLamp.querySelector('[data-action="power"]').getAttribute('aria-checked')).toBe('false');
            expect(idleLamp.querySelector('[data-action="brightness"]').value).toBe('55');
            expect(idleLamp.dataset.colorTemp).toBe('0');
        });

        it('refreshes once when the page becomes visible again', async () => {
            let visibility = 'hidden';
            Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => visibility });
            document.body.innerHTML = `
                <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__" data-state-url="/lighting/state" data-poll-seconds="120">
                    ${lampMarkup('tuya::1', '/lighting/lights/tuya/1')}
                </div>
            `;
            fetch.mockResolvedValue({ ok: true, status: 200, json: async () => ({ data: [], unreachable_providers: [] }) });

            initLighting();
            visibility = 'visible';
            document.dispatchEvent(new Event('visibilitychange'));

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(fetch.mock.calls[0][0]).toBe('/lighting/state');
        });
    });
});
