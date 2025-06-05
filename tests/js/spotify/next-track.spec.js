import {
  loadNextTrack,
  displayNextTrackMessage,
  renderNextTrack
} from '../../../Modules/Spotify/resources/assets/js/modules/next-track.js';

// Mock the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/modules/utils.js', () => ({
  displayMessage: jest.fn()
}));

describe('next-track.js', () => {
  let mockElements;
  let mockTrack;
  let mockRenderNextTrackFn;
  let mockStartPlayback;

  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Mock fetch globally
    global.fetch = jest.fn().mockResolvedValue({
      json: jest.fn().mockResolvedValue({ success: true })
    });

    // Create mock objects
    mockElements = {
      nextTrackContainer: {
        innerHTML: '',
        appendChild: jest.fn(),
        querySelector: jest.fn().mockReturnValue({
          addEventListener: jest.fn()
        })
      },
      messageTemplate: {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue({})
          }
        }
      },
      nextTrackTemplate: {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue({
              querySelector: jest.fn().mockImplementation((selector) => {
                if (selector === 'img.next-track-image') return { src: '' };
                if (selector === '.next-track-name') return { textContent: '' };
                if (selector === '.next-track-artists') return { textContent: '' };
                return null;
              })
            })
          }
        }
      }
    };

    mockTrack = {
      name: 'Test Track',
      uri: 'spotify:track:123',
      artists: [{ name: 'Test Artist' }],
      album: {
        images: [{ url: 'test-image-url' }]
      }
    };

    mockRenderNextTrackFn = jest.fn();
    mockStartPlayback = jest.fn();
  });

  describe('loadNextTrack', () => {
    it('should fetch next track from the API', () => {
      loadNextTrack(mockElements, mockRenderNextTrackFn);

      expect(fetch).toHaveBeenCalledWith('/spotify/next-track');
    });

    it('should call renderNextTrackFn with track if successful', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: true,
          next_track: mockTrack
        })
      });

      await loadNextTrack(mockElements, mockRenderNextTrackFn);

      expect(mockRenderNextTrackFn).toHaveBeenCalledWith(mockElements, mockTrack);
    });

    it('should display message if no next track found', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: true,
          next_track: null
        })
      });

      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      await loadNextTrack(mockElements, mockRenderNextTrackFn);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.nextTrackContainer,
        mockElements.messageTemplate,
        'No upcoming tracks'
      );
    });

    it('should display error message if API call fails', async () => {
      global.fetch = jest.fn().mockRejectedValue(new Error('API error'));

      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      await loadNextTrack(mockElements, mockRenderNextTrackFn);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.nextTrackContainer,
        mockElements.messageTemplate,
        'Error loading next track'
      );
    });
  });

  describe('displayNextTrackMessage', () => {
    it('should call displayMessage with correct parameters', () => {
      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      displayNextTrackMessage(mockElements, 'Test message');

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.nextTrackContainer,
        mockElements.messageTemplate,
        'Test message'
      );
    });
  });

  describe('renderNextTrack', () => {
    it('should return early if nextTrackContainer is missing', () => {
      const elementsWithoutContainer = { ...mockElements, nextTrackContainer: null };

      renderNextTrack(elementsWithoutContainer, mockStartPlayback, mockTrack);

      expect(mockElements.nextTrackTemplate.content.firstElementChild.cloneNode).not.toHaveBeenCalled();
    });

    it('should display message if track is null', () => {
      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      renderNextTrack(mockElements, mockStartPlayback, null);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.nextTrackContainer,
        mockElements.messageTemplate,
        'No upcoming tracks'
      );
    });

    it('should clear container and render track', () => {
      const mockElement = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === 'img.next-track-image') return { src: '' };
          if (selector === '.next-track-name') return { textContent: '' };
          if (selector === '.next-track-artists') return { textContent: '' };
          return null;
        })
      };

      mockElements.nextTrackTemplate.content.firstElementChild.cloneNode.mockReturnValue(mockElement);

      renderNextTrack(mockElements, mockStartPlayback, mockTrack);

      // Should clear the container
      expect(mockElements.nextTrackContainer.innerHTML).toBe('');

      // Should create element for track
      expect(mockElements.nextTrackTemplate.content.firstElementChild.cloneNode).toHaveBeenCalled();

      // Should append element to container
      expect(mockElements.nextTrackContainer.appendChild).toHaveBeenCalledWith(mockElement);
    });

    it('should set track details correctly', () => {
      const mockImg = { src: '' };
      const mockNameEl = { textContent: '' };
      const mockArtistsEl = { textContent: '' };

      const mockElement = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === 'img.next-track-image') return mockImg;
          if (selector === '.next-track-name') return mockNameEl;
          if (selector === '.next-track-artists') return mockArtistsEl;
          return null;
        })
      };

      mockElements.nextTrackTemplate.content.firstElementChild.cloneNode.mockReturnValue(mockElement);

      renderNextTrack(mockElements, mockStartPlayback, mockTrack);

      // Should set image src
      expect(mockImg.src).toBe('test-image-url');

      // Should set track name
      expect(mockNameEl.textContent).toBe('Test Track');

      // Should set artist names
      expect(mockArtistsEl.textContent).toBe('Test Artist');
    });

    it('should add click event listener to play button', () => {
      const playButton = { addEventListener: jest.fn() };
      mockElements.nextTrackContainer.querySelector.mockReturnValue(playButton);

      renderNextTrack(mockElements, mockStartPlayback, mockTrack);

      // Should add click event listener
      expect(playButton.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));

      // Call the click handler
      const clickHandler = playButton.addEventListener.mock.calls[0][1];
      clickHandler();

      // Should call startPlayback with track URI
      expect(mockStartPlayback).toHaveBeenCalledWith(mockElements, null, 'spotify:track:123');
    });

    it('should handle missing track properties gracefully', () => {
      const incompleteTrack = {
        name: 'Test Track',
        uri: 'spotify:track:123'
        // Missing artists and album
      };

      const mockImg = { src: '' };
      const mockNameEl = { textContent: '' };
      const mockArtistsEl = { textContent: '' };

      const mockElement = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === 'img.next-track-image') return mockImg;
          if (selector === '.next-track-name') return mockNameEl;
          if (selector === '.next-track-artists') return mockArtistsEl;
          return null;
        })
      };

      mockElements.nextTrackTemplate.content.firstElementChild.cloneNode.mockReturnValue(mockElement);

      renderNextTrack(mockElements, mockStartPlayback, incompleteTrack);

      // Should set default values for missing properties
      expect(mockImg.src).toBe('');
      expect(mockNameEl.textContent).toBe('Test Track');
      expect(mockArtistsEl.textContent).toBe('Unknown Artist');
    });

    it('should display error message if rendering fails', () => {
      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      // Force an error by making cloneNode throw
      mockElements.nextTrackTemplate.content.firstElementChild.cloneNode.mockImplementation(() => {
        throw new Error('Rendering error');
      });

      renderNextTrack(mockElements, mockStartPlayback, mockTrack);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.nextTrackContainer,
        mockElements.messageTemplate,
        'Error displaying next track'
      );
    });
  });
});
