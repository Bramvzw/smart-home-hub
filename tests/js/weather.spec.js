import { initWeather } from '../../Modules/Weather/resources/assets/js/weather.js';

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
}

describe('weather live refresh', () => {
    it('refreshes only the weather content region on the configured interval', async () => {
        jest.useFakeTimers();
        window.history.pushState({}, '', '/weather');
        document.body.innerHTML = `
            <div data-weather data-weather-refresh-seconds="15">
                <div data-weather-content><span id="current">Old forecast</span></div>
            </div>
        `;

        fetch.mockResolvedValueOnce({
            ok: true,
            text: async () => `
                <html><body>
                    <div data-weather data-weather-refresh-seconds="15">
                        <div data-weather-content><span id="current">New forecast</span></div>
                    </div>
                </body></html>
            `,
        });

        initWeather();
        jest.advanceTimersByTime(15000);
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith('http://localhost/weather', {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        expect(document.querySelector('#current').textContent).toBe('New forecast');
        expect(document.querySelector('[data-weather]').dataset.weatherReady).toBe('true');
    });
});
