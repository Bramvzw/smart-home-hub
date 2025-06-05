// Import the module
import * as utils from '../../../Modules/Spotify/resources/assets/js/modules/utils.js';

// Destructure the functions for easier use in tests
const {
  formatTime,
  postOptions,
  updateElementContent,
  showAlert,
  showErrorMessage,
  showSuccessMessage,
  handleResponse,
  displayMessage
} = utils;

describe('utils.js', () => {
  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Use fake timers
    jest.useFakeTimers();
  });

  describe('formatTime', () => {
    it('should format milliseconds to mm:ss format', () => {
      expect(formatTime(0)).toBe('0:00');
      expect(formatTime(1000)).toBe('0:01');
      expect(formatTime(60000)).toBe('1:00');
      expect(formatTime(61000)).toBe('1:01');
      expect(formatTime(3661000)).toBe('61:01');
    });

    it('should pad seconds with leading zero if less than 10', () => {
      expect(formatTime(9000)).toBe('0:09');
      expect(formatTime(69000)).toBe('1:09');
    });
  });

  describe('postOptions', () => {
    it('should create options object for POST requests', () => {
      const csrfToken = 'test-csrf-token';
      const options = postOptions(csrfToken);

      expect(options.method).toBe('POST');
      expect(options.headers['X-CSRF-TOKEN']).toBe(csrfToken);
      expect(options.headers['Content-Type']).toBe('application/json');
      expect(options.credentials).toBe('same-origin');
    });
  });

  describe('updateElementContent', () => {
    it('should update element content if element exists', () => {
      const mockElement = { textContent: '' };
      document.getElementById.mockReturnValue(mockElement);

      updateElementContent('test-element', 'test-content');

      expect(document.getElementById).toHaveBeenCalledWith('test-element');
      expect(mockElement.textContent).toBe('test-content');
    });

    it('should update specified property if provided', () => {
      const mockElement = { src: '' };
      document.getElementById.mockReturnValue(mockElement);

      updateElementContent('test-element', 'test-src', 'src');

      expect(document.getElementById).toHaveBeenCalledWith('test-element');
      expect(mockElement.src).toBe('test-src');
    });

    it('should do nothing if element does not exist', () => {
      document.getElementById.mockReturnValue(null);

      updateElementContent('non-existent-element', 'test-content');

      expect(document.getElementById).toHaveBeenCalledWith('non-existent-element');
      // No error should be thrown
    });
  });

  describe('showAlert', () => {
    it('should create and append alert element to document body', () => {
      // Create a mock alert element
      const mockAlert = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === '.message') return { textContent: '' };
          if (selector === '.close-btn') return { addEventListener: jest.fn() };
          return null;
        }),
        classList: { add: jest.fn() },
        remove: jest.fn()
      };

      // Create a mock alertTemplate
      const mockAlertTemplate = {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue(mockAlert)
          }
        }
      };

      const elements = { alertTemplate: mockAlertTemplate };

      showAlert(elements, 'Test message', 'success');

      expect(document.body.appendChild).toHaveBeenCalledWith(mockAlert);
      expect(jest.getTimerCount()).toBeGreaterThan(0);
    });

    it('should add error classes for error type', () => {
      const mockAlert = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === '.message') return { textContent: '' };
          if (selector === '.close-btn') return { addEventListener: jest.fn() };
          return null;
        }),
        classList: { add: jest.fn() },
        remove: jest.fn()
      };

      const mockAlertTemplate = {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue(mockAlert)
          }
        }
      };

      const elements = { alertTemplate: mockAlertTemplate };

      showAlert(elements, 'Test error', 'error');

      expect(mockAlert.classList.add).toHaveBeenCalledWith(
        'bg-red-100', 'border-l-4', 'border-red-500', 'text-red-700'
      );
    });

    it('should add success classes for success type', () => {
      const mockAlert = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === '.message') return { textContent: '' };
          if (selector === '.close-btn') return { addEventListener: jest.fn() };
          return null;
        }),
        classList: { add: jest.fn() },
        remove: jest.fn()
      };

      const mockAlertTemplate = {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue(mockAlert)
          }
        }
      };

      const elements = { alertTemplate: mockAlertTemplate };

      showAlert(elements, 'Test success', 'success');

      expect(mockAlert.classList.add).toHaveBeenCalledWith(
        'bg-green-100', 'border-l-4', 'border-green-500', 'text-green-700'
      );
    });
  });

  describe('showErrorMessage', () => {
    it('should call showAlert with error type', () => {
      // Create a spy on showAlert
      const showAlertSpy = jest.spyOn(utils, 'showAlert').mockImplementation(() => {});

      // Create a proper mock for alertTemplate
      const mockAlert = {
        querySelector: jest.fn().mockImplementation((selector) => {
          if (selector === '.message') return { textContent: '' };
          if (selector === '.close-btn') return { addEventListener: jest.fn() };
          return null;
        }),
        classList: { add: jest.fn() },
        remove: jest.fn()
      };

      const mockAlertTemplate = {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue(mockAlert)
          }
        }
      };

      const elements = { alertTemplate: mockAlertTemplate };
      const message = 'Test error message';

      // Call the function under test
      showErrorMessage(elements, message);

      // Verify the spy was called with the right arguments
      expect(showAlertSpy).toHaveBeenCalledWith(elements, message, 'error');

      // Restore the original implementation
      showAlertSpy.mockRestore();
    });
  });

  describe('showSuccessMessage', () => {
    it('should call showAlert with success type', () => {
      // Mock the showAlert function directly
      const originalShowAlert = utils.showAlert;
      utils.showAlert = jest.fn();

      const elements = { alertTemplate: {} };
      const message = 'Test success message';

      // Call the function under test
      showSuccessMessage(elements, message);

      // Verify the mock was called with the right arguments
      expect(utils.showAlert).toHaveBeenCalledWith(elements, message, 'success');

      // Restore the original implementation
      utils.showAlert = originalShowAlert;
    });
  });

  describe('handleResponse', () => {
    it('should call updatePlayerState and return data if response is successful', async () => {
      const mockResponse = {
        json: jest.fn().mockResolvedValue({ success: true, data: 'test-data' })
      };

      const updatePlayerState = jest.fn();
      const elements = {};

      const result = await handleResponse(mockResponse, updatePlayerState, elements);

      expect(mockResponse.json).toHaveBeenCalled();
      expect(updatePlayerState).toHaveBeenCalled();
      expect(result).toEqual({ success: true, data: 'test-data' });
    });

    it('should show error message and throw error if response contains error', async () => {
      // Mock the response
      const mockResponse = {
        json: jest.fn().mockResolvedValue({ success: false, error: 'test-error' })
      };

      const updatePlayerState = jest.fn();

      // Mock the showErrorMessage function directly
      const originalShowErrorMessage = utils.showErrorMessage;
      utils.showErrorMessage = jest.fn();

      const elements = { alertTemplate: {} };

      // The function should throw an error, but we need to catch it
      try {
        await handleResponse(mockResponse, updatePlayerState, elements);
        // If we get here, the function didn't throw an error
        fail('Expected handleResponse to throw an error');
      } catch (error) {
        // Verify the error message
        expect(error.message).toBe('test-error');
      }

      expect(mockResponse.json).toHaveBeenCalled();
      expect(updatePlayerState).not.toHaveBeenCalled();
      expect(utils.showErrorMessage).toHaveBeenCalledWith(elements, 'test-error');

      // Restore the original implementation
      utils.showErrorMessage = originalShowErrorMessage;
    });

    it('should show generic error message if fetch fails', async () => {
      // Mock the response
      const mockResponse = {
        json: jest.fn().mockRejectedValue(new Error('fetch failed'))
      };

      const updatePlayerState = jest.fn();

      // Mock the showErrorMessage function directly
      const originalShowErrorMessage = utils.showErrorMessage;
      utils.showErrorMessage = jest.fn();

      const elements = { alertTemplate: {} };

      // Call the function under test
      const result = await handleResponse(mockResponse, updatePlayerState, elements);

      expect(mockResponse.json).toHaveBeenCalled();
      expect(updatePlayerState).not.toHaveBeenCalled();
      expect(utils.showErrorMessage).toHaveBeenCalledWith(elements, 'An error occurred with the Spotify API');
      expect(result).toEqual({ success: false, error: 'fetch failed' });

      // Restore the original implementation
      utils.showErrorMessage = originalShowErrorMessage;
    });
  });

  describe('displayMessage', () => {
    it('should display message in container', () => {
      const mockMsg = {
        textContent: ''
      };

      const mockMessageTemplate = {
        content: {
          firstElementChild: {
            cloneNode: jest.fn().mockReturnValue(mockMsg)
          }
        }
      };

      const mockContainer = {
        innerHTML: '',
        appendChild: jest.fn()
      };

      displayMessage(mockContainer, mockMessageTemplate, 'Test message');

      expect(mockContainer.innerHTML).toBe('');
      expect(mockMsg.textContent).toBe('Test message');
      expect(mockContainer.appendChild).toHaveBeenCalledWith(mockMsg);
    });

    it('should do nothing if container or messageTemplate is missing', () => {
      const mockContainer = {
        innerHTML: '',
        appendChild: jest.fn()
      };

      // Test with missing messageTemplate
      displayMessage(mockContainer, null, 'Test message');
      expect(mockContainer.innerHTML).toBe('');
      expect(mockContainer.appendChild).not.toHaveBeenCalled();

      // Test with missing container
      displayMessage(null, {}, 'Test message');
      // No error should be thrown
    });
  });
});
