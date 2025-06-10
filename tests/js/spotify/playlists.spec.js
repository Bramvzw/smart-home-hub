import {
  setupPlaylistEventListeners,
  shufflePlayPlaylist
} from '../../../Modules/Spotify/resources/assets/js/ui/interactions/playlists.js';

// Mock the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/utils/index.js', () => ({
  postOptions: jest.fn().mockReturnValue({
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': 'test-token', 'Content-Type': 'application/json' }
  }),
  showErrorMessage: jest.fn(),
  showSuccessMessage: jest.fn(),
  displayMessage: jest.fn()
}));

describe('playlists.js', () => {
  let mockElements;
  let mockUpdatePlayerState;

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
      recentlyPlayedContainer: {
        innerHTML: '',
        appendChild: jest.fn(),
        querySelectorAll: jest.fn().mockReturnValue([
          {
            getAttribute: jest.fn().mockImplementation(attr => {
              if (attr === 'data-id') return 'liked-songs';
              return null;
            }),
            addEventListener: jest.fn()
          },
          {
            getAttribute: jest.fn().mockImplementation(attr => {
              if (attr === 'data-uri') return 'spotify:playlist:1';
              return null;
            }),
            addEventListener: jest.fn()
          }
        ])
      },
      messageTemplate: {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue({})
          }
        }
      }
    };

    mockUpdatePlayerState = jest.fn();
  });

  describe('setupPlaylistEventListeners', () => {
    it('should add click event listeners to playlist items', () => {
      setupPlaylistEventListeners(mockElements, mockUpdatePlayerState);

      // Get the playlist items from the mock
      const playlistItems = mockElements.recentlyPlayedContainer.querySelectorAll();

      // Verify that event listeners were added to each item
      expect(playlistItems[0].addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
      expect(playlistItems[1].addEventListener).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('should show success message when clicking on Liked Songs playlist', () => {
      const { showSuccessMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      setupPlaylistEventListeners(mockElements, mockUpdatePlayerState);

      // Get the playlist items from the mock
      const playlistItems = mockElements.recentlyPlayedContainer.querySelectorAll();

      // Get the click handler for the Liked Songs playlist
      const clickHandler = playlistItems[0].addEventListener.mock.calls[0][1];

      // Call the click handler
      clickHandler();

      // Verify that showSuccessMessage was called
      expect(showSuccessMessage).toHaveBeenCalledWith(
        mockElements,
        'Playing Liked Songs feature coming soon!'
      );
    });

    it('should call shufflePlayPlaylist when clicking on a regular playlist', () => {
      // Mock the shufflePlayPlaylist function
      const originalShufflePlayPlaylist = shufflePlayPlaylist;
      global.shufflePlayPlaylist = jest.fn();

      setupPlaylistEventListeners(mockElements, mockUpdatePlayerState);

      // Get the playlist items from the mock
      const playlistItems = mockElements.recentlyPlayedContainer.querySelectorAll();

      // Get the click handler for the regular playlist
      const clickHandler = playlistItems[1].addEventListener.mock.calls[0][1];

      // Call the click handler
      clickHandler();

      // Verify that shufflePlayPlaylist was called with the correct URI
      expect(global.shufflePlayPlaylist).toHaveBeenCalledWith(
        mockElements,
        mockUpdatePlayerState,
        'spotify:playlist:1'
      );

      // Restore the original function
      global.shufflePlayPlaylist = originalShufflePlayPlaylist;
    });
  });


  describe('shufflePlayPlaylist', () => {
    it('should call fetch with the correct URL and playlist URI', () => {
      const uri = 'spotify:playlist:1';

      shufflePlayPlaylist(mockElements, mockUpdatePlayerState, uri);

      expect(fetch).toHaveBeenCalledWith('/spotify/shuffle-play-playlist', expect.objectContaining({
        body: JSON.stringify({ uri })
      }));
    });

    it('should show success message and update player state if successful', async () => {
      const { showSuccessMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: true
        })
      });

      await shufflePlayPlaylist(mockElements, mockUpdatePlayerState, 'spotify:playlist:1');

      expect(showSuccessMessage).toHaveBeenCalledWith(mockElements, 'Playing shuffled playlist');
      expect(mockUpdatePlayerState).toHaveBeenCalled();
    });

    it('should show error message if API call fails', async () => {
      const { showErrorMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      global.fetch = jest.fn().mockRejectedValue(new Error('API error'));

      await shufflePlayPlaylist(mockElements, mockUpdatePlayerState, 'spotify:playlist:1');

      expect(showErrorMessage).toHaveBeenCalledWith(mockElements, 'Error playing playlist');
    });

    it('should show error message if API returns success=false', async () => {
      const { showErrorMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: false
        })
      });

      await shufflePlayPlaylist(mockElements, mockUpdatePlayerState, 'spotify:playlist:1');

      expect(showErrorMessage).toHaveBeenCalledWith(mockElements, 'Failed to play playlist');
    });
  });
});
