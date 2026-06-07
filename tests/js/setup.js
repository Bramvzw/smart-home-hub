window.SPOTIFY_STATE = {
    is_playing: false,
    item: {
        id: 'test-track-id',
        name: 'Test Track',
        artists: [{ name: 'Test Artist' }],
        album: {
            name: 'Test Album',
            images: [{ url: 'test-image-url' }],
        },
        duration_ms: 300000,
    },
    progress_ms: 150000,
};

global.fetch = jest.fn();

afterEach(() => {
    document.body.innerHTML = '';
    jest.clearAllMocks();
    jest.clearAllTimers();
    jest.useRealTimers();
    window.SPOTIFY_STATE = {
        is_playing: false,
        item: {
            id: 'test-track-id',
            name: 'Test Track',
            artists: [{ name: 'Test Artist' }],
            album: {
                name: 'Test Album',
                images: [{ url: 'test-image-url' }],
            },
            duration_ms: 300000,
        },
        progress_ms: 150000,
    };
});
