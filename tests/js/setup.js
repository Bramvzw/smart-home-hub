// Setup file for Jest tests

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

// Mock fetch API
global.fetch = jest.fn();

// Mock document methods
document.getElementById = jest.fn();
document.querySelector = jest.fn();
document.querySelectorAll = jest.fn();
document.addEventListener = jest.fn();
document.createElement = jest.fn();

// Mock document.body methods
if (document.body) {
  document.body.appendChild = jest.fn();
  document.body.removeChild = jest.fn();
} else {
  // Create a mock body if it doesn't exist
  Object.defineProperty(document, 'body', {
    value: {
      appendChild: jest.fn(),
      removeChild: jest.fn()
    },
    writable: true
  });
}

// Create a mock element class
class MockElement {
    constructor() {
        this.addEventListener = jest.fn();
        this.appendChild = jest.fn();
        this.remove = jest.fn();
        this.querySelector = jest.fn();
        this.querySelectorAll = jest.fn();
        this.classList = {
            add: jest.fn(),
            remove: jest.fn(),
            toggle: jest.fn()
        };
    }
}

// Use the mock element for document.createElement
document.createElement = jest.fn().mockReturnValue(new MockElement());

// Use Jest's modern fake timers globally
jest.useFakeTimers({ legacyFakeTimers: true });

// Cleanup after each test to avoid hanging processes
afterEach(() => {
    jest.clearAllTimers();
    jest.restoreAllMocks(); // Reset mocks to avoid leaking between tests
});
