import {
  checkIfTrackIsLiked,
  toggleLike,
  updateLikeButton
} from '../../../Modules/Spotify/resources/assets/js/ui/interactions/like.js';

// Mock the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/utils/index.js', () => ({
  showErrorMessage: jest.fn()
}));

describe('like.js', () => {
  let mockState;
  let mockElements;
  let mockUpdateState;
  let mockUpdateLikeButton;

  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Mock fetch globally
    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: jest.fn().mockResolvedValue({ success: true })
    });

    // Create mock objects
    mockState = {
      currentTrackId: 'test-track-id',
      isTrackLiked: false
    };

    mockElements = {
      csrfToken: 'test-token',
      likeBtn: {
        classList: {
          toggle: jest.fn()
        }
      },
      likeIcon: {
        classList: {
          toggle: jest.fn()
        }
      }
    };

    mockUpdateState = jest.fn().mockImplementation((state, newValues) => {
      return { ...state, ...newValues };
    });

    mockUpdateLikeButton = jest.fn();
  });

  describe('checkIfTrackIsLiked', () => {
    it('should fetch track like status with correct URL and parameters', () => {
      const trackId = 'test-track-id';

      checkIfTrackIsLiked(mockState, mockElements, mockUpdateState, mockUpdateLikeButton, trackId);

      expect(fetch).toHaveBeenCalledWith(expect.stringContaining('/spotify/tracks/check?ids%5B%5D=test-track-id'));
    });

    it('should update state with isTrackLiked=true if track is liked', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: jest.fn().mockResolvedValue({
          success: true,
          results: [true]
        })
      });

      const newState = await checkIfTrackIsLiked(mockState, mockElements, mockUpdateState, mockUpdateLikeButton, 'test-track-id');

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { isTrackLiked: true });
      expect(mockUpdateLikeButton).toHaveBeenCalledWith(newState, mockElements);
    });

    it('should update state with isTrackLiked=false if track is not liked', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: jest.fn().mockResolvedValue({
          success: true,
          results: [false]
        })
      });

      const newState = await checkIfTrackIsLiked(mockState, mockElements, mockUpdateState, mockUpdateLikeButton, 'test-track-id');

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { isTrackLiked: false });
      expect(mockUpdateLikeButton).toHaveBeenCalledWith(newState, mockElements);
    });

    it('should update state with isTrackLiked=false if API call fails', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        ok: false,
        status: 500
      });

      const newState = await checkIfTrackIsLiked(mockState, mockElements, mockUpdateState, mockUpdateLikeButton, 'test-track-id');

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { isTrackLiked: false });
      expect(mockUpdateLikeButton).toHaveBeenCalledWith(newState, mockElements);
    });
  });

  describe('toggleLike', () => {
    it('should send POST request with correct data to toggle like status', () => {
      toggleLike(mockState, mockElements, mockUpdateState, mockUpdateLikeButton);

      expect(fetch).toHaveBeenCalledWith('/spotify/tracks/toggle', expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'test-token'
        }),
        body: JSON.stringify({
          id: 'test-track-id',
          saved: true // Toggling from false to true
        })
      }));
    });

    it('should update state with new like status if successful', async () => {
      global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: jest.fn().mockResolvedValue({
          success: true,
          saved: true
        })
      });

      const newState = await toggleLike(mockState, mockElements, mockUpdateState, mockUpdateLikeButton);

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { isTrackLiked: true });
      expect(mockUpdateLikeButton).toHaveBeenCalledWith(newState, mockElements);
    });

    it('should show error message if API call fails', async () => {
      const { showErrorMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      global.fetch = jest.fn().mockResolvedValue({
        ok: false,
        status: 500
      });

      await toggleLike(mockState, mockElements, mockUpdateState, mockUpdateLikeButton);

      expect(showErrorMessage).toHaveBeenCalledWith(mockElements, 'Error updating like status');
      expect(mockUpdateState).not.toHaveBeenCalled();
    });

    it('should show error message if API returns success=false', async () => {
      const { showErrorMessage } = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

      global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: jest.fn().mockResolvedValue({
          success: false
        })
      });

      await toggleLike(mockState, mockElements, mockUpdateState, mockUpdateLikeButton);

      expect(showErrorMessage).toHaveBeenCalledWith(mockElements, 'Failed to update like status');
      expect(mockUpdateState).not.toHaveBeenCalled();
    });
  });

  describe('updateLikeButton', () => {
    it('should toggle like button classes based on isTrackLiked', () => {
      // Test with isTrackLiked = false
      updateLikeButton(mockState, mockElements);

      expect(mockElements.likeIcon.classList.toggle).toHaveBeenCalledWith('fas', false);
      expect(mockElements.likeIcon.classList.toggle).toHaveBeenCalledWith('far', true);
      expect(mockElements.likeBtn.classList.toggle).toHaveBeenCalledWith('active', false);

      // Test with isTrackLiked = true
      const likedState = { ...mockState, isTrackLiked: true };
      updateLikeButton(likedState, mockElements);

      expect(mockElements.likeIcon.classList.toggle).toHaveBeenCalledWith('fas', true);
      expect(mockElements.likeIcon.classList.toggle).toHaveBeenCalledWith('far', false);
      expect(mockElements.likeBtn.classList.toggle).toHaveBeenCalledWith('active', true);
    });

    it('should do nothing if like button elements are missing', () => {
      const elementsWithoutLikeBtn = { ...mockElements, likeBtn: null, likeIcon: null };

      updateLikeButton(mockState, elementsWithoutLikeBtn);

      // No errors should be thrown
      expect(true).toBe(true);
    });
  });
});
