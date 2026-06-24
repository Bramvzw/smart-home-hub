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

    it('ignores preset clicks while a lighting action is already running', async () => {
        const pending = deferred();
        document.body.innerHTML = `
            <div data-lighting data-preset-url-template="/lighting/presets/__PRESET__">
                <button type="button" data-preset="cozy" data-preset-power="true">Cozy</button>
                <button type="button" data-preset="movie" data-preset-power="true">Movie</button>
                <article data-light data-reachable="true" data-supports-color="false">
                    <button type="button" data-action="power" aria-checked="false"></button>
                    <input type="range" data-action="brightness" value="10">
                </article>
            </div>
        `;

        fetch.mockReturnValueOnce(pending.promise);

        initLighting();
        document.querySelector('[data-preset="cozy"]').click();
        document.querySelector('[data-preset="movie"]').click();

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch.mock.calls[0][0]).toBe('/lighting/presets/cozy');
        expect(document.querySelector('[data-preset="movie"]').disabled).toBe(true);
        expect(document.querySelector('[data-action="brightness"]').disabled).toBe(true);

        pending.resolve({
            ok: true,
            json: async () => ({
                data: {
                    preset: {
                        key: 'cozy',
                        power: true,
                        brightness: null,
                        color: null,
                    },
                    failed_lights: [],
                },
            }),
        });
        await flushPromises();

        expect(document.querySelector('[data-preset="movie"]').disabled).toBe(false);
        expect(document.querySelector('[data-action="brightness"]').disabled).toBe(false);
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
});
