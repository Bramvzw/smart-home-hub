import {
  loadUserPlaylists,
  displayPlaylistMessage,
  renderUserPlaylists,
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
  let mockPlaylists;
  let mockRenderUserPlaylists;
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
        appendChild: jest.fn()
      },
      messageTemplate: {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue({})
          }
        }
      },
      playlistTemplate: {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue({
              setAttribute: jest.fn(),
              addEventListener: jest.fn(),
              querySelector: jest.fn().mockReturnValue({
                src: '',
                alt: ''
              })
            })
          }
        }
      }
    };

    mockPlaylists = [
      {
        id: 'playlist-1',
        name: 'Test Playlist 1',
        uri: 'spotify:playlist:1',
        images: [
          { url: 'image-url-1', width: 300 },
          { url: 'image-url-2', width: 600 }
        ]
      },
      {
        id: 'liked-songs',
        name: 'Liked Songs',
        images: [
          { url: 'liked-songs-image', width: 300 }
        ]
      }
    ];

    mockRenderUserPlaylists = jest.fn();
    mockUpdatePlayerState = jest.fn();
  });

  describe('loadUserPlaylists', () => {
    it('should fetch user playlists from the API', () => {
      loadUserPlaylists(mockElements, mockRenderUserPlaylists);

      expect(fetch).toHaveBeenCalledWith('/spotify/user-playlists');
    });

    it('should call renderUserPlaylists with playlists if successful', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: true,
          playlists: mockPlaylists
        })
      });

      await loadUserPlaylists(mockElements, mockRenderUserPlaylists);

      expect(mockRenderUserPlaylists).toHaveBeenCalledWith(mockElements, mockPlaylists);
    });

    it('should display message if no playlists found', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        json: jest.fn().mockResolvedValue({
          success: true,
          playlists: null
        })
      });

      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      await loadUserPlaylists(mockElements, mockRenderUserPlaylists);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.recentlyPlayedContainer,
        mockElements.messageTemplate,
        'No playlists found in your library'
      );
    });

    it('should display error message if API call fails', async () => {
      global.fetch = jest.fn().mockRejectedValue(new Error('API error'));

      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      await loadUserPlaylists(mockElements, mockRenderUserPlaylists);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.recentlyPlayedContainer,
        mockElements.messageTemplate,
        'Error loading playlists'
      );
    });
  });

  describe('displayPlaylistMessage', () => {
    it('should call displayMessage with correct parameters', () => {
      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      displayPlaylistMessage(mockElements, 'Test message');

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.recentlyPlayedContainer,
        mockElements.messageTemplate,
        'Test message'
      );
    });
  });

  describe('renderUserPlaylists', () => {
    it('should return early if recentlyPlayedContainer is missing', () => {
      const elementsWithoutContainer = { ...mockElements, recentlyPlayedContainer: null };

      renderUserPlaylists(elementsWithoutContainer, mockPlaylists, mockUpdatePlayerState);

      expect(mockElements.playlistTemplate.content.firstElementChild.cloneNode).not.toHaveBeenCalled();
    });

    it('should display message if playlists array is empty', () => {
      const { displayMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      renderUserPlaylists(mockElements, [], mockUpdatePlayerState);

      expect(displayMessage).toHaveBeenCalledWith(
        mockElements.recentlyPlayedContainer,
        mockElements.messageTemplate,
        'No playlists found in your library'
      );
    });

    it('should clear container and render each playlist', () => {
      renderUserPlaylists(mockElements, mockPlaylists, mockUpdatePlayerState);

      // Should clear the container
      expect(mockElements.recentlyPlayedContainer.innerHTML).toBe('');

      // Should create elements for each playlist
      expect(mockElements.playlistTemplate.content.firstElementChild.cloneNode).toHaveBeenCalledTimes(2);

      // Should append elements to container
      expect(mockElements.recentlyPlayedContainer.appendChild).toHaveBeenCalledTimes(2);
    });

    it('should handle Liked Songs playlist specially', () => {
      const mockPlaylistElement = {
        setAttribute: jest.fn(),
        addEventListener: jest.fn(),
        querySelector: jest.fn().mockReturnValue({
          src: '',
          alt: ''
        })
      };

      mockElements.playlistTemplate.content.firstElementChild.cloneNode.mockReturnValue(mockPlaylistElement);

      renderUserPlaylists(mockElements, [mockPlaylists[1]], mockUpdatePlayerState);

      // Should set data-id attribute for Liked Songs
      expect(mockPlaylistElement.setAttribute).toHaveBeenCalledWith('data-id', 'liked-songs');

      // Should add click event listener
      expect(mockPlaylistElement.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));

      // Call the click handler
      const clickHandler = mockPlaylistElement.addEventListener.mock.calls[0][1];
      clickHandler();

      // Should show success message for Liked Songs
      const { showSuccessMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');
      expect(showSuccessMessage).toHaveBeenCalledWith(mockElements, 'Playing Liked Songs feature coming soon!');
    });

    it('should handle regular playlists correctly', () => {
      const mockPlaylistElement = {
        setAttribute: jest.fn(),
        addEventListener: jest.fn(),
        querySelector: jest.fn().mockReturnValue({
          src: '',
          alt: ''
        })
      };

      mockElements.playlistTemplate.content.firstElementChild.cloneNode.mockReturnValue(mockPlaylistElement);

      renderUserPlaylists(mockElements, [mockPlaylists[0]], mockUpdatePlayerState);

      // Should set data-uri attribute for regular playlist
      expect(mockPlaylistElement.setAttribute).toHaveBeenCalledWith('data-uri', 'spotify:playlist:1');

      // Should add click event listener
      expect(mockPlaylistElement.addEventListener).toHaveBeenCalledWith('click', expect.any(Function));

      // Mock shufflePlayPlaylist
      const originalShufflePlayPlaylist = shufflePlayPlaylist;
      global.shufflePlayPlaylist = jest.fn();

      // Call the click handler
      const clickHandler = mockPlaylistElement.addEventListener.mock.calls[0][1];
      clickHandler();

      // Should call shufflePlayPlaylist for regular playlist
      expect(global.shufflePlayPlaylist).toHaveBeenCalledWith(mockElements, mockUpdatePlayerState, 'spotify:playlist:1');

      // Restore original function
      global.shufflePlayPlaylist = originalShufflePlayPlaylist;
    });

    it('should skip playlists without images', () => {
      const playlistWithoutImages = {
        id: 'playlist-no-images',
        name: 'No Images',
        uri: 'spotify:playlist:no-images',
        images: []
      };

      renderUserPlaylists(mockElements, [playlistWithoutImages], mockUpdatePlayerState);

      // Should not create elements for playlists without images
      expect(mockElements.playlistTemplate.content.firstElementChild.cloneNode).not.toHaveBeenCalled();
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
