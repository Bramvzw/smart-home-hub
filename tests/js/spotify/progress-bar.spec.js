import {
  startDrag,
  drag,
  endDrag,
  seekOnClick,
  seekToPosition
} from '../../../Modules/Spotify/resources/assets/js/modules/progress-bar.js';

// Mock the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/modules/utils.js', () => ({
  postOptions: jest.fn().mockReturnValue({
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': 'test-token', 'Content-Type': 'application/json' }
  }),
  updateElementContent: jest.fn(),
  handleResponse: jest.fn()
}));

describe('progress-bar.js', () => {
  let mockState;
  let mockElements;
  let mockUpdateState;
  let mockDrag;
  let mockSeekToPosition;
  let mockFormatTime;
  let mockUpdatePlayerState;
  let mockEvent;

  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Mock fetch globally
    global.fetch = jest.fn().mockResolvedValue({
      json: jest.fn().mockResolvedValue({ success: true })
    });

    // Create mock objects
    mockState = {
      isDragging: false,
      currentDuration: 300000 // 5 minutes in milliseconds
    };

    mockElements = {
      csrfToken: 'test-token',
      progressContainer: {
        getBoundingClientRect: jest.fn().mockReturnValue({
          left: 0,
          width: 100
        })
      },
      progressBar: {
        style: {
          width: '0%'
        }
      }
    };

    mockUpdateState = jest.fn().mockImplementation((state, newValues) => {
      return { ...state, ...newValues };
    });

    mockDrag = jest.fn();
    mockSeekToPosition = jest.fn();
    mockFormatTime = jest.fn().mockReturnValue('2:30');
    mockUpdatePlayerState = jest.fn();

    mockEvent = {
      clientX: 50 // Middle of the progress bar (50% position)
    };
  });

  describe('startDrag', () => {
    it('should update state with isDragging=true', () => {
      const result = startDrag(mockState, mockUpdateState, mockDrag, mockEvent);

      expect(mockUpdateState).toHaveBeenCalledWith(mockState, { isDragging: true });
      expect(result.isDragging).toBe(true);
    });

    it('should call drag function with updated state', () => {
      startDrag(mockState, mockUpdateState, mockDrag, mockEvent);

      expect(mockDrag).toHaveBeenCalledWith(
        expect.objectContaining({ isDragging: true }),
        mockEvent
      );
    });
  });

  describe('drag', () => {
    it('should do nothing if not dragging', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      drag(mockState, mockElements, mockFormatTime, mockEvent);

      expect(mockElements.progressContainer.getBoundingClientRect).not.toHaveBeenCalled();
      expect(updateElementContent).not.toHaveBeenCalled();
      expect(mockElements.progressBar.style.width).toBe('0%');
    });

    it('should do nothing if progressContainer is missing', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      const draggingState = { ...mockState, isDragging: true };
      const elementsWithoutContainer = { ...mockElements, progressContainer: null };

      drag(draggingState, elementsWithoutContainer, mockFormatTime, mockEvent);

      expect(updateElementContent).not.toHaveBeenCalled();
    });

    it('should update progress bar width and current time when dragging', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      const draggingState = { ...mockState, isDragging: true };

      drag(draggingState, mockElements, mockFormatTime, mockEvent);

      // Position should be 50% (clientX = 50, width = 100)
      expect(mockElements.progressBar.style.width).toBe('50%');

      // Position in ms should be 150000 (50% of 300000)
      expect(mockFormatTime).toHaveBeenCalledWith(150000);
      expect(updateElementContent).toHaveBeenCalledWith('current-time', '2:30');
    });

    it('should clamp position between 0 and 1', () => {
      const { updateElementContent } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      const draggingState = { ...mockState, isDragging: true };

      // Test with position < 0
      const eventBefore = { clientX: -10 };
      drag(draggingState, mockElements, mockFormatTime, eventBefore);
      expect(mockElements.progressBar.style.width).toBe('0%');

      // Test with position > 1
      const eventAfter = { clientX: 150 };
      drag(draggingState, mockElements, mockFormatTime, eventAfter);
      expect(mockElements.progressBar.style.width).toBe('100%');
    });
  });

  describe('endDrag', () => {
    it('should return original state if not dragging', () => {
      const result = endDrag(mockState, mockElements, mockUpdateState, mockSeekToPosition, mockEvent);

      expect(result).toBe(mockState);
      expect(mockUpdateState).not.toHaveBeenCalled();
      expect(mockSeekToPosition).not.toHaveBeenCalled();
    });

    it('should seek to position and update state when dragging ends', () => {
      const draggingState = { ...mockState, isDragging: true };

      const result = endDrag(draggingState, mockElements, mockUpdateState, mockSeekToPosition, mockEvent);

      // Position in ms should be 150000 (50% of 300000)
      expect(mockSeekToPosition).toHaveBeenCalledWith(mockElements, 150000);
      expect(mockUpdateState).toHaveBeenCalledWith(draggingState, { isDragging: false });
      expect(result.isDragging).toBe(false);
    });
  });

  describe('seekOnClick', () => {
    it('should calculate position and call seekToPosition', () => {
      seekOnClick(mockState, mockElements, mockSeekToPosition, mockEvent);

      // Position in ms should be 150000 (50% of 300000)
      expect(mockSeekToPosition).toHaveBeenCalledWith(mockElements, 150000);
    });
  });

  describe('seekToPosition', () => {
    it('should call fetch with the correct URL and position data', () => {
      const { handleResponse } = require('../../../Modules/Spotify/resources/assets/js/modules/utils.js');

      seekToPosition(mockElements, mockUpdatePlayerState, 150000);

      expect(fetch).toHaveBeenCalledWith('/spotify/seek', expect.objectContaining({
        body: JSON.stringify({ position_ms: 150000 })
      }));

      // Wait for the promise to resolve
      return new Promise(process.nextTick).then(() => {
        expect(handleResponse).toHaveBeenCalled();
      });
    });
  });
});
