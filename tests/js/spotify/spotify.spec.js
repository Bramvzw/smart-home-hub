// Integration test for spotify.js

// Mock all the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/modules/elements.js', () => ({
  getElements: jest.fn().mockReturnValue({
    csrfToken: 'test-token',
    playPauseBtn: { addEventListener: jest.fn() },
    playPauseIcon: { classList: { toggle: jest.fn() } },
    previousBtn: { addEventListener: jest.fn() },
    nextBtn: { addEventListener: jest.fn() },
    likeBtn: { addEventListener: jest.fn() },
    likeIcon: { classList: { toggle: jest.fn() } },
    volumeSlider: { addEventListener: jest.fn() },
    progressContainer: { addEventListener: jest.fn() },
    progressBar: { style: { width: '0%' } },
    recentlyPlayedContainer: { innerHTML: '', appendChild: jest.fn() },
    nextTrackContainer: { innerHTML: '', appendChild: jest.fn() },
    playlistTemplate: { content: { firstElementChild: { cloneNode: jest.fn() } } },
    nextTrackTemplate: { content: { firstElementChild: { cloneNode: jest.fn() } } },
    alertTemplate: { content: { firstElementChild: { cloneNode: jest.fn() } } },
    messageTemplate: { content: { firstElementChild: { cloneNode: jest.fn() } } }
  })
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/state.js', () => ({
  createInitialState: jest.fn().mockReturnValue({
    isPlaying: false,
    currentTrackId: null,
    isTrackLiked: false,
    isDragging: false,
    currentDuration: 0,
    updateInterval: null
  }),
  updateState: jest.fn().mockImplementation((state, newValues) => ({ ...state, ...newValues }))
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/utils.js', () => ({
  formatTime: jest.fn().mockReturnValue('0:00'),
  postOptions: jest.fn(),
  updateElementContent: jest.fn(),
  showAlert: jest.fn(),
  showErrorMessage: jest.fn(),
  showSuccessMessage: jest.fn(),
  handleResponse: jest.fn(),
  displayMessage: jest.fn()
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/player-controls.js', () => ({
  startPlayback: jest.fn(),
  pausePlayback: jest.fn(),
  control: jest.fn(),
  setVolume: jest.fn(),
  startPeriodicUpdates: jest.fn().mockReturnValue({ updateInterval: 123 }),
  stopPeriodicUpdates: jest.fn(),
  updatePlayerState: jest.fn(),
  updatePlayerUI: jest.fn().mockReturnValue({ isPlaying: false })
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/progress-bar.js', () => ({
  startDrag: jest.fn().mockReturnValue({ isDragging: true }),
  drag: jest.fn(),
  endDrag: jest.fn().mockReturnValue({ isDragging: false }),
  seekOnClick: jest.fn(),
  seekToPosition: jest.fn()
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/like.js', () => ({
  checkIfTrackIsLiked: jest.fn(),
  toggleLike: jest.fn(),
  updateLikeButton: jest.fn()
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/playlists.js', () => ({
  loadUserPlaylists: jest.fn(),
  displayPlaylistMessage: jest.fn(),
  renderUserPlaylists: jest.fn(),
  shufflePlayPlaylist: jest.fn()
}));

jest.mock('../../../Modules/Spotify/resources/assets/js/modules/next-track.js', () => ({
  loadNextTrack: jest.fn(),
  displayNextTrackMessage: jest.fn(),
  renderNextTrack: jest.fn()
}));

describe('spotify.js', () => {
  // Store original document.addEventListener
  const originalAddEventListener = document.addEventListener;

  // Store DOMContentLoaded callback
  let domContentLoadedCallback;

  beforeAll(() => {
    // Mock document.addEventListener to capture DOMContentLoaded callback
    document.addEventListener = jest.fn((event, callback) => {
      if (event === 'DOMContentLoaded') {
        domContentLoadedCallback = callback;
      }
    });

    // Import spotify.js to trigger the event listener setup
    require('../../../Modules/Spotify/resources/assets/js/spotify.js');
  });

  afterAll(() => {
    // Restore original document.addEventListener
    document.addEventListener = originalAddEventListener;
  });

  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Mock window.SPOTIFY_STATE
    window.SPOTIFY_STATE = {
      is_playing: false,
      item: {
        id: 'test-track-id',
        name: 'Test Track',
        artists: [{ name: 'Test Artist' }],
        album: {
          name: 'Test Album',
          images: [{ url: 'test-image-url' }]
        },
        duration_ms: 300000
      },
      progress_ms: 150000
    };
  });

  it('should set up document.addEventListener for DOMContentLoaded', () => {
    expect(document.addEventListener).toHaveBeenCalledWith('DOMContentLoaded', expect.any(Function));
    expect(domContentLoadedCallback).toBeDefined();
  });

  describe('DOMContentLoaded callback', () => {
    it('should initialize the player when DOM is loaded', () => {
      // Call the DOMContentLoaded callback
      domContentLoadedCallback();

      // Verify that the player is initialized
      const { getElements } = require('../../../Modules/Spotify/resources/assets/js/modules/elements.js');
      const { createInitialState } = require('../../../Modules/Spotify/resources/assets/js/modules/state.js');
      const { updatePlayerUI } = require('../../../Modules/Spotify/resources/assets/js/modules/player-controls.js');

      expect(getElements).toHaveBeenCalled();
      expect(createInitialState).toHaveBeenCalled();
      expect(updatePlayerUI).toHaveBeenCalled();
    });

    it('should set up event listeners for player controls', () => {
      // Call the DOMContentLoaded callback
      domContentLoadedCallback();

      // Get mock elements
      const { getElements } = require('../../../Modules/Spotify/resources/assets/js/modules/elements.js');
      const elements = getElements();

      // Verify that event listeners are set up
      expect(elements.playPauseBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
      expect(elements.previousBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
      expect(elements.nextBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
      expect(elements.likeBtn.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
      expect(elements.volumeSlider.addEventListener).toHaveBeenCalledWith('input', expect.any(Function));
      expect(elements.progressContainer.addEventListener).toHaveBeenCalledWith('mousedown', expect.any(Function));
      expect(elements.progressContainer.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('should start periodic updates', () => {
      // Call the DOMContentLoaded callback
      domContentLoadedCallback();

      // Verify that periodic updates are started
      const { startPeriodicUpdates } = require('../../../Modules/Spotify/resources/assets/js/modules/player-controls.js');
      expect(startPeriodicUpdates).toHaveBeenCalled();
    });

    it('should load user playlists and next track', () => {
      // Call the DOMContentLoaded callback
      domContentLoadedCallback();

      // Verify that playlists and next track are loaded
      const { loadUserPlaylists } = require('../../../Modules/Spotify/resources/assets/js/modules/playlists.js');
      const { loadNextTrack } = require('../../../Modules/Spotify/resources/assets/js/modules/next-track.js');

      expect(loadUserPlaylists).toHaveBeenCalled();
      expect(loadNextTrack).toHaveBeenCalled();
    });

    it('should create wrapper functions for all player controls', () => {
      // Call the DOMContentLoaded callback
      domContentLoadedCallback();

      // Trigger play/pause button click
      const { getElements } = require('../../../Modules/Spotify/resources/assets/js/modules/elements.js');
      const elements = getElements();

      // Get the click handler
      const clickHandler = elements.playPauseBtn.addEventListener.mock.calls[0][1];

      // Call the click handler
      clickHandler();

      // Verify that startPlayback is called (since isPlaying is false)
      const { startPlayback } = require('../../../Modules/Spotify/resources/assets/js/modules/player-controls.js');
      expect(startPlayback).toHaveBeenCalled();
    });
  });
});
