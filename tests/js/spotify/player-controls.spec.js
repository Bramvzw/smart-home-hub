import {
  startPlayback,
  pausePlayback,
  control,
  setVolume,
  startPeriodicUpdates,
  stopPeriodicUpdates,
  updatePlayerState,
  updatePlayerUI
} from '../../../Modules/Spotify/resources/assets/js/modules/player-controls.js';

// Mock the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/modules/utils.js', () => ({
  postOptions: jest.fn().mockReturnValue({
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': 'test-token', 'Content-Type': 'application/json' }
  }),
  showErrorMessage: jest.fn(),
  updateElementContent: jest.fn(),
  handleResponse: jest.fn()
}));

describe('player-controls.js', () => {
  let mockElements;
  let mockState;
  let mockUpdatePlayerStateFn;
  let mockUpdateState;
  let mockUpdatePlayerUI;
  let mockCheckIfTrackIsLiked;
  let mockLoadNextTrack;
  let mockFormatTime;

  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Mock fetch globally
    global.fetch = jest.fn().mockResolvedValue({
      json: jest.fn().mockResolvedValue({ success: true })
    });

    // Create mock objects
    mockElements = {
      csrfToken: 'test-token',
      playPauseIcon: {
        classList: {
          toggle: jest.fn()
        }
      },
      progressBar: {
        style: {
          width: '0%'
        }
      }
    };

    mockState = {
      isPlaying: false,
      currentTrackId: 'test-track-id',
      isDragging: false,
      updateInterval: null
    };

    mockUpdatePlayerStateFn = jest.fn();
    mockUpdateState = jest.fn().mockImplementation((state, newValues) => {
      return { ...state, ...newValues };
    });
    mockUpdatePlayerUI = jest.fn();
    mockCheckIfTrackIsLiked = jest.fn();
    mockLoadNextTrack = jest.fn();
    mockFormatTime = jest.fn().mockReturnValue('0:00');
  });

  describe('startPlayback', () => {
    it('should call fetch with the correct URL and options', () => {
      startPlayback(mockElements, mockUpdatePlayerStateFn);

      expect(fetch).toHaveBeenCalledWith('/spotify/play', expect.any(Object));
    });

    it('should include URI in request body if provided', () => {
      const uri = 'spotify:track:123';
      startPlayback(mockElements, mockUpdatePlayerStateFn, uri);

      expect(fetch).toHaveBeenCalledWith('/spotify/play', expect.objectContaining({
        body: JSON.stringify({ uri })
      }));
    });
  });

  describe('pausePlayback', () => {
    it('should call fetch with the correct URL and options', () => {
      pausePlayback(mockElements, mockUpdatePlayerStateFn);

      expect(fetch).toHaveBeenCalledWith('/spotify/pause', expect.any(Object));
    });
  });

  describe('control', () => {
    it('should call fetch with the correct URL for next action', () => {
      control(mockElements, mockUpdatePlayerStateFn, 'next');

      expect(fetch).toHaveBeenCalledWith('/spotify/next', expect.any(Object));
    });

    it('should call fetch with the correct URL for previous action', () => {
      control(mockElements, mockUpdatePlayerStateFn, 'previous');

      expect(fetch).toHaveBeenCalledWith('/spotify/previous', expect.any(Object));
    });
  });

  describe('setVolume', () => {
    it('should call fetch with the correct URL and volume data', () => {
      const volume = 50;
      setVolume(mockElements, mockUpdatePlayerStateFn, volume);

      expect(fetch).toHaveBeenCalledWith('/spotify/volume', expect.objectContaining({
        body: JSON.stringify({ volume })
      }));
    });

    it('should show error message if volume control is not supported', async () => {
      // Mock fetch to return volume control not supported error
      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: false,
          code: 'volume_control_not_supported'
        })
      });

      const { showErrorMessage } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      await setVolume(mockElements, mockUpdatePlayerStateFn, 50);

      expect(showErrorMessage).toHaveBeenCalledWith(
        mockElements,
        'This device does not support volume control.'
      );
      expect(setTimeout).toHaveBeenCalledWith(mockUpdatePlayerStateFn, 500);
    });
  });

  describe('startPeriodicUpdates', () => {
    it('should clear existing interval if present', () => {
      const stateWithInterval = {
        ...mockState,
        updateInterval: 123
      };

      startPeriodicUpdates(stateWithInterval, mockUpdatePlayerStateFn, mockUpdateState);

      expect(clearInterval).toHaveBeenCalledWith(123);
    });

    it('should call updatePlayerStateFn immediately', () => {
      startPeriodicUpdates(mockState, mockUpdatePlayerStateFn, mockUpdateState);

      expect(mockUpdatePlayerStateFn).toHaveBeenCalled();
    });

    it('should set up interval for future updates', () => {
      global.setInterval = jest.fn().mockReturnValue(456);

      startPeriodicUpdates(mockState, mockUpdatePlayerStateFn, mockUpdateState);

      expect(setInterval).toHaveBeenCalledWith(mockUpdatePlayerStateFn, 1000);
    });

    it('should update state with new interval', () => {
      global.setInterval = jest.fn().mockReturnValue(456);

      startPeriodicUpdates(mockState, mockUpdatePlayerStateFn, mockUpdateState);

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { updateInterval: 456 });
    });
  });

  describe('stopPeriodicUpdates', () => {
    it('should clear interval if present', () => {
      const stateWithInterval = {
        ...mockState,
        updateInterval: 123
      };

      stopPeriodicUpdates(stateWithInterval, mockUpdateState);

      expect(clearInterval).toHaveBeenCalledWith(123);
    });

    it('should update state with null interval', () => {
      const stateWithInterval = {
        ...mockState,
        updateInterval: 123
      };

      stopPeriodicUpdates(stateWithInterval, mockUpdateState);

      expect(mockUpdateState).toHaveBeenCalledWith(stateWithInterval, { updateInterval: null });
    });
  });

  describe('updatePlayerState', () => {
    it('should not fetch if isDragging is true', () => {
      const draggingState = {
        ...mockState,
        isDragging: true
      };

      updatePlayerState(
        draggingState,
        mockElements,
        mockUpdatePlayerUI,
        mockUpdateState,
        mockCheckIfTrackIsLiked,
        mockLoadNextTrack
      );

      expect(fetch).not.toHaveBeenCalled();
    });

    it('should fetch playback state and update UI if successful', async () => {
      const mockPlaybackData = {
        success: true,
        is_playing: true,
        item: {
          id: 'test-track-id',
          name: 'Test Track'
        }
      };

      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue(mockPlaybackData)
      });

      await updatePlayerState(
        mockState,
        mockElements,
        mockUpdatePlayerUI,
        mockUpdateState,
        mockCheckIfTrackIsLiked,
        mockLoadNextTrack
      );

      expect(fetch).toHaveBeenCalledWith('/spotify/playback-state');
      expect(mockUpdatePlayerUI).toHaveBeenCalledWith(mockPlaybackData);
    });

    it('should update track ID, check if liked, and load next track if track changed', async () => {
      const mockPlaybackData = {
        success: true,
        is_playing: true,
        item: {
          id: 'new-track-id',
          name: 'New Track'
        }
      };

      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue(mockPlaybackData)
      });

      await updatePlayerState(
        mockState,
        mockElements,
        mockUpdatePlayerUI,
        mockUpdateState,
        mockCheckIfTrackIsLiked,
        mockLoadNextTrack
      );

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { currentTrackId: 'new-track-id' });
      expect(mockCheckIfTrackIsLiked).toHaveBeenCalledWith('new-track-id');
      expect(mockLoadNextTrack).toHaveBeenCalled();
    });
  });

  describe('updatePlayerUI', () => {
    it('should update state with playback information', () => {
      const playbackState = {
        is_playing: true,
        item: {
          id: 'test-track-id',
          name: 'Test Track',
          duration_ms: 300000,
          artists: [{ name: 'Test Artist' }],
          album: {
            name: 'Test Album',
            images: [{ url: 'test-image-url' }]
          }
        },
        progress_ms: 150000
      };

      updatePlayerUI(mockState, mockElements, playbackState, mockUpdateState, mockFormatTime);

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, {
        isPlaying: true,
        currentDuration: 300000
      });
    });

    it('should toggle play/pause icon classes based on isPlaying', () => {
      const playbackState = {
        is_playing: true,
        item: null
      };

      updatePlayerUI(mockState, mockElements, playbackState, mockUpdateState, mockFormatTime);

      expect(mockElements.playPauseIcon.classList.toggle).toHaveBeenCalledWith('fa-pause', true);
      expect(mockElements.playPauseIcon.classList.toggle).toHaveBeenCalledWith('fa-play', false);
    });

    it('should update track details if item is available', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      const playbackState = {
        is_playing: true,
        item: {
          id: 'test-track-id',
          name: 'Test Track',
          duration_ms: 300000,
          artists: [{ name: 'Test Artist' }],
          album: {
            name: 'Test Album',
            images: [{ url: 'test-image-url' }]
          }
        },
        progress_ms: 150000
      };

      updatePlayerUI(mockState, mockElements, playbackState, mockUpdateState, mockFormatTime);

      expect(updateElementContent).toHaveBeenCalledWith('track-image', 'test-image-url', 'src');
      expect(updateElementContent).toHaveBeenCalledWith('track-name', 'Test Track');
      expect(updateElementContent).toHaveBeenCalledWith('artist-name', 'Test Artist');
      expect(updateElementContent).toHaveBeenCalledWith('album-name', 'Test Album');
      expect(updateElementContent).toHaveBeenCalledWith('duration', '0:00');
    });

    it('should update progress if not dragging', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      const playbackState = {
        is_playing: true,
        item: {
          id: 'test-track-id',
          name: 'Test Track',
          duration_ms: 300000,
          artists: [{ name: 'Test Artist' }],
          album: {
            name: 'Test Album',
            images: [{ url: 'test-image-url' }]
          }
        },
        progress_ms: 150000
      };

      updatePlayerUI(mockState, mockElements, playbackState, mockUpdateState, mockFormatTime);

      expect(updateElementContent).toHaveBeenCalledWith('current-time', '0:00');
      expect(mockElements.progressBar.style.width).toBe('50%');
    });

    it('should not update progress if dragging', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      const draggingState = {
        ...mockState,
        isDragging: true
      };

      const playbackState = {
        is_playing: true,
        item: {
          id: 'test-track-id',
          name: 'Test Track',
          duration_ms: 300000,
          artists: [{ name: 'Test Artist' }],
          album: {
            name: 'Test Album',
            images: [{ url: 'test-image-url' }]
          }
        },
        progress_ms: 150000
      };

      updatePlayerUI(draggingState, mockElements, playbackState, mockUpdateState, mockFormatTime);

      // These should still be called
      expect(updateElementContent).toHaveBeenCalledWith('track-image', 'test-image-url', 'src');
      expect(updateElementContent).toHaveBeenCalledWith('track-name', 'Test Track');

      // These should not be called for progress
      expect(updateElementContent).not.toHaveBeenCalledWith('current-time', '0:00');
      expect(mockElements.progressBar.style.width).not.toBe('50%');
    });
  });
});
