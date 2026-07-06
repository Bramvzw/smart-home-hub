import { updatePlayerUI } from '../../../Modules/Spotify/resources/assets/js/ui/player-renderer.js';

const mergeState = (state, patch) => ({ ...state, ...patch });
const formatTime = () => '0:00';

const payload = (progressMs, overrides = {}) => ({
    progress_ms: progressMs,
    is_playing: true,
    item: { id: 't1', duration_ms: 300000, name: 'Track', artists: [], album: { images: [] } },
    ...overrides,
});

describe('Spotify progress anchor smoothing', () => {
    const NOW = 1_750_000_000_000;

    beforeEach(() => {
        document.body.innerHTML = '';
        jest.useFakeTimers({ now: NOW });
    });

    afterEach(() => jest.useRealTimers());

    const playingState = () => ({
        isPlaying: true,
        currentTrackId: 't1',
        durationMs: 300000,
        progressMs: 60000,
        progressAt: NOW - 1000, // local interpolation now shows 61000ms
        skipPending: false,
    });

    it('keeps the local anchor when the API wobbles a few hundred ms on the same track', () => {
        // API reports 60400 while local shows 61000: drift 600ms — ignore it.
        const next = updatePlayerUI(playingState(), {}, payload(60400), mergeState, formatTime);

        expect(next.progressMs).toBe(60000);
        expect(next.progressAt).toBe(NOW - 1000);
    });

    it('re-anchors on real drift, e.g. after a seek on another device', () => {
        const next = updatePlayerUI(playingState(), {}, payload(90000), mergeState, formatTime);

        expect(next.progressMs).toBe(90000);
        expect(next.progressAt).toBe(NOW);
    });

    it('re-anchors when the track changes', () => {
        const data = payload(500);
        data.item.id = 't2';

        const next = updatePlayerUI(playingState(), {}, data, mergeState, formatTime);

        expect(next.progressMs).toBe(500);
        expect(next.progressAt).toBe(NOW);
    });

    it('drops the anchor when playback pauses', () => {
        const next = updatePlayerUI(playingState(), {}, payload(61000, { is_playing: false }), mergeState, formatTime);

        expect(next.progressMs).toBe(61000);
        expect(next.progressAt).toBeNull();
    });
});
