import {
    loadUpcomingTrack,
    renderNextTrack,
} from '../../../Modules/Spotify/resources/assets/js/ui/interactions/upcoming-track.js';

// Mock the imported modules
jest.mock('../../../Modules/Spotify/resources/assets/js/utils/index.js', () => ({
    displayMessage: jest.fn(),
    updateElementContent: jest.fn()
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
            json: jest.fn().mockResolvedValue({success: true})
        });

        // Create mock objects
        mockElements = {
            nextTrackContainer: {
                innerHTML: '',
                querySelector: jest.fn().mockReturnValue({
                    addEventListener: jest.fn(),
                    cloneNode: jest.fn().mockReturnValue({
                        addEventListener: jest.fn()
                    }),
                    parentNode: {
                        replaceChild: jest.fn()
                    }
                })
            },
            messageTemplate: {
                content: {
                    firstElementChild: {
                        cloneNode: jest.fn().mockReturnValue({})
                    }
                }
            }
        };

        mockTrack = {
            name: 'Test Track',
            uri: 'spotify:track:123',
            artists: [{name: 'Test Artist'}],
            album: {
                images: [{url: 'test-image-url'}]
            }
        };

        mockRenderNextTrackFn = jest.fn();
        mockStartPlayback = jest.fn();
    });

    describe('loadNextTrack', () => {
        it('should fetch next track from the API', () => {
            loadUpcomingTrack(mockElements, mockRenderNextTrackFn);

            expect(fetch).toHaveBeenCalledWith('/spotify/next-track');
        });

        it('should call renderNextTrackFn with track if successful', async () => {
            global.fetch = jest.fn().mockResolvedValue({
                json: jest.fn().mockResolvedValue({
                    success: true,
                    next_track: mockTrack
                })
            });

            await loadUpcomingTrack(mockElements, mockRenderNextTrackFn);

            expect(mockRenderNextTrackFn).toHaveBeenCalledWith(mockElements, mockTrack);
        });

        it('should display message if no next track found', async () => {
            global.fetch = jest.fn().mockResolvedValue({
                json: jest.fn().mockResolvedValue({
                    success: true,
                    next_track: null
                })
            });

            const {displayMessage} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            await loadUpcomingTrack(mockElements, mockRenderNextTrackFn);

            expect(displayMessage).toHaveBeenCalledWith(
                mockElements.nextTrackContainer,
                mockElements.messageTemplate,
                'No upcoming tracks'
            );
        });

        it('should display error message if API call fails', async () => {
            global.fetch = jest.fn().mockRejectedValue(new Error('API error'));

            const {displayMessage} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');
            await loadUpcomingTrack(mockElements, mockRenderNextTrackFn);

            expect(displayMessage).toHaveBeenCalledWith(
                mockElements.nextTrackContainer,
                mockElements.messageTemplate,
                'Error loading next track'
            );
        });
    });

    describe('displayNextTrackMessage', () => {
        it('should call displayMessage with correct parameters when track is null', () => {
            const {displayMessage} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            renderNextTrack(mockElements, mockStartPlayback, null);

            expect(displayMessage).toHaveBeenCalledWith(
                mockElements.nextTrackContainer,
                mockElements.messageTemplate,
                'No upcoming tracks'
            );
        });
    });

    describe('renderNextTrack', () => {
        it('should return early if nextTrackContainer is missing', () => {
            const elementsWithoutContainer = {...mockElements, nextTrackContainer: null};
            const {updateElementContent} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            renderNextTrack(elementsWithoutContainer, mockStartPlayback, mockTrack);

            expect(updateElementContent).not.toHaveBeenCalled();
        });

        it('should display message if track is null', () => {
            const {displayMessage} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            renderNextTrack(mockElements, mockStartPlayback, null);

            expect(displayMessage).toHaveBeenCalledWith(
                mockElements.nextTrackContainer,
                mockElements.messageTemplate,
                'No upcoming tracks'
            );
        });

        it('should update track details correctly', async () => {
            const {updateElementContent} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            // Mock the dynamic import
            jest.spyOn(Promise, 'resolve').mockImplementation(value => value);

            renderNextTrack(mockElements, mockStartPlayback, mockTrack);

            // Wait for the Promise.resolve to be called
            await Promise.resolve();

            // Should update image src
            expect(updateElementContent).toHaveBeenCalledWith('next-track-image', 'test-image-url', 'src');

            // Should update track name
            expect(updateElementContent).toHaveBeenCalledWith('next-track-name', 'Test Track');

            // Should update artist names
            expect(updateElementContent).toHaveBeenCalledWith('next-track-artists', 'Test Artist');
        });

        it('should add click event listener to play button', () => {
            const playButton = mockElements.nextTrackContainer.querySelector();

            renderNextTrack(mockElements, mockStartPlayback, mockTrack);

            // Should add click event listener
            expect(playButton.parentNode.replaceChild).toHaveBeenCalled();

            // Get the new button that was created
            const newButton = playButton.cloneNode();

            // Call the click handler that would be attached to the new button
            const clickHandler = newButton.addEventListener.mock.calls[0][1];
            if (clickHandler) clickHandler();

            // Should call startPlayback with track URI
            expect(mockStartPlayback).toHaveBeenCalledWith(mockElements, null, 'spotify:track:123');
        });

        it('should handle missing track properties gracefully', async () => {
            const incompleteTrack = {
                name: 'Test Track',
                uri: 'spotify:track:123'
                // Missing artists and album
            };

            const {updateElementContent} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            // Mock the dynamic import
            jest.spyOn(Promise, 'resolve').mockImplementation(value => value);

            renderNextTrack(mockElements, mockStartPlayback, incompleteTrack);

            // Wait for the Promise.resolve to be called
            await Promise.resolve();

            // Should set default values for missing properties
            expect(updateElementContent).toHaveBeenCalledWith('next-track-image', '', 'src');
            expect(updateElementContent).toHaveBeenCalledWith('next-track-name', 'Test Track');
            expect(updateElementContent).toHaveBeenCalledWith('next-track-artists', 'Unknown Artist');
        });

        it('should display error message if rendering fails', () => {
            const {displayMessage, updateElementContent} = require('../../../Modules/Spotify/resources/assets/js/utils/index.js');

            // Force an error by making updateElementContent throw
            updateElementContent.mockImplementation(() => {
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
