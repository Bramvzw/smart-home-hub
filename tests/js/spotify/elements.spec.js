import { getElements } from '../../../Modules/Spotify/resources/assets/js/ui/elements.js';

describe('elements.js', () => {
  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Mock document.querySelector to return an object with getAttribute method
    document.querySelector.mockReturnValue({
      getAttribute: jest.fn().mockReturnValue('test-csrf-token')
    });

    // Mock document.getElementById to return a mock element
    document.getElementById.mockImplementation((id) => {
      return { id };
    });
  });

  describe('getElements', () => {
    it('should return an object with all DOM elements', () => {
      const elements = getElements();

      // Verify that the elements object contains all expected properties
      expect(elements).toHaveProperty('csrfToken');
      expect(elements).toHaveProperty('playPauseBtn');
      expect(elements).toHaveProperty('playPauseIcon');
      expect(elements).toHaveProperty('previousBtn');
      expect(elements).toHaveProperty('nextBtn');
      expect(elements).toHaveProperty('likeBtn');
      expect(elements).toHaveProperty('likeIcon');
      expect(elements).toHaveProperty('volumeSlider');
      expect(elements).toHaveProperty('progressContainer');
      expect(elements).toHaveProperty('progressBar');
      expect(elements).toHaveProperty('recentlyPlayedContainer');
      expect(elements).toHaveProperty('nextTrackContainer');
      expect(elements).toHaveProperty('playlistTemplate');
      expect(elements).toHaveProperty('nextTrackTemplate');
      expect(elements).toHaveProperty('alertTemplate');
      expect(elements).toHaveProperty('messageTemplate');
    });

    it('should call document.querySelector for CSRF token', () => {
      getElements();

      expect(document.querySelector).toHaveBeenCalledWith('meta[name="csrf-token"]');
    });

    it('should call document.getElementById for each element', () => {
      getElements();

      expect(document.getElementById).toHaveBeenCalledWith('play-pause-btn');
      expect(document.getElementById).toHaveBeenCalledWith('play-pause-icon');
      expect(document.getElementById).toHaveBeenCalledWith('previous-btn');
      expect(document.getElementById).toHaveBeenCalledWith('next-btn');
      expect(document.getElementById).toHaveBeenCalledWith('like-btn');
      expect(document.getElementById).toHaveBeenCalledWith('like-icon');
      expect(document.getElementById).toHaveBeenCalledWith('volume-slider');
      expect(document.getElementById).toHaveBeenCalledWith('progress-container');
      expect(document.getElementById).toHaveBeenCalledWith('progress-bar');
      expect(document.getElementById).toHaveBeenCalledWith('recently-played-container');
      expect(document.getElementById).toHaveBeenCalledWith('next-track');
      expect(document.getElementById).toHaveBeenCalledWith('playlist-item-template');
      expect(document.getElementById).toHaveBeenCalledWith('next-track-template');
      expect(document.getElementById).toHaveBeenCalledWith('alert-template');
      expect(document.getElementById).toHaveBeenCalledWith('message-template');
    });
  });
});
