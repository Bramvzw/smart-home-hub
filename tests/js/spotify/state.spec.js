import { createInitialState, updateState } from '../../../Modules/Spotify/resources/assets/js/modules/state.js';

describe('state.js', () => {
  describe('createInitialState', () => {
    it('should create an initial state object with all required properties', () => {
      const state = createInitialState();

      // Verify that the state object contains all expected properties
      expect(state).toHaveProperty('isPlaying');
      expect(state).toHaveProperty('currentTrackId');
      expect(state).toHaveProperty('isTrackLiked');
      expect(state).toHaveProperty('isDragging');
      expect(state).toHaveProperty('currentDuration');
      expect(state).toHaveProperty('updateInterval');
    });

    it('should initialize isPlaying from window.SPOTIFY_STATE if available', () => {
      // window.SPOTIFY_STATE is mocked in setup.js with is_playing: false
      const state = createInitialState();
      expect(state.isPlaying).toBe(false);

      // Change the mock value and test again
      window.SPOTIFY_STATE.is_playing = true;
      const newState = createInitialState();
      expect(newState.isPlaying).toBe(true);
    });

    it('should initialize default values for other properties', () => {
      const state = createInitialState();

      expect(state.currentTrackId).toBeNull();
      expect(state.isTrackLiked).toBe(false);
      expect(state.isDragging).toBe(false);
      expect(state.currentDuration).toBe(0);
      expect(state.updateInterval).toBeNull();
    });
  });

  describe('updateState', () => {
    it('should update the state with new values', () => {
      const initialState = {
        isPlaying: false,
        currentTrackId: null,
        isTrackLiked: false,
        isDragging: false,
        currentDuration: 0,
        updateInterval: null
      };

      const newValues = {
        isPlaying: true,
        currentTrackId: 'new-track-id'
      };

      const updatedState = updateState(initialState, newValues);

      // Verify that the updated state contains the new values
      expect(updatedState.isPlaying).toBe(true);
      expect(updatedState.currentTrackId).toBe('new-track-id');

      // Verify that other properties remain unchanged
      expect(updatedState.isTrackLiked).toBe(false);
      expect(updatedState.isDragging).toBe(false);
      expect(updatedState.currentDuration).toBe(0);
      expect(updatedState.updateInterval).toBeNull();
    });

    it('should not modify the original state object', () => {
      const initialState = {
        isPlaying: false,
        currentTrackId: null
      };

      const newValues = {
        isPlaying: true
      };

      const updatedState = updateState(initialState, newValues);

      // Verify that the updated state is a new object
      expect(updatedState).not.toBe(initialState);

      // Verify that the original state is unchanged
      expect(initialState.isPlaying).toBe(false);
      expect(initialState.currentTrackId).toBeNull();
    });
  });
});
