const refreshWeather = async (root) => {
    const target = root.querySelector('[data-weather-content]');
    if (! target || document.hidden) {
        return;
    }

    const response = await fetch(window.location.href, {
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (! response.ok) {
        return;
    }

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const next = doc.querySelector('[data-weather-content]');

    if (next) {
        target.innerHTML = next.innerHTML;
    }
};

export const initWeather = () => {
    const root = document.querySelector('[data-weather]');
    if (! root || root.dataset.weatherReady === 'true') {
        return;
    }

    root.dataset.weatherReady = 'true';
    const seconds = Number(root.dataset.weatherRefreshSeconds ?? 0);
    if (! Number.isFinite(seconds) || seconds <= 0) {
        return;
    }

    window.setInterval(() => {
        refreshWeather(root).catch(() => {});
    }, seconds * 1000);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWeather, { once: true });
} else {
    initWeather();
}
document.addEventListener('livewire:navigated', initWeather);
