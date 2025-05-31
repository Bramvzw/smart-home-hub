<?php

namespace Modules\Spotify\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller;
use Modules\Spotify\Services\SpotifyService;

class SpotifyController extends Controller
{
    protected $spotifyService;

    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
    }

    /**
     * Display the Spotify interface
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $isConnected = Cache::has('spotify_access_token');
        $playbackState = null;

        if ($isConnected) {
            $playbackState = $this->spotifyService->getCurrentPlayback();
        }

        return view('spotify::index', [
            'isConnected' => $isConnected,
            'playbackState' => $playbackState,
            'authUrl' => $this->spotifyService->getAuthorizationUrl(),
        ]);
    }

    /**
     * Handle the callback from Spotify authorization
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('spotify.index')
                ->with('error', 'Authorization failed: No code provided');
        }

        $response = $this->spotifyService->getAccessToken($code);

        if (isset($response['error'])) {
            return redirect()->route('spotify.index')
                ->with('error', 'Authorization failed: ' . $response['error']);
        }

        return redirect()->route('spotify.index')
            ->with('success', 'Successfully connected to Spotify');
    }

    /**
     * Play music
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function play(Request $request)
    {
        $uri = $request->input('uri');
        $response = $this->spotifyService->play($uri);

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Pause music
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pause()
    {
        $response = $this->spotifyService->pause();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Skip to next track
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function next()
    {
        $response = $this->spotifyService->next();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Skip to previous track
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function previous()
    {
        $response = $this->spotifyService->previous();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Set volume
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setVolume(Request $request)
    {
        $volume = $request->input('volume');

        if ($volume === null || $volume < 0 || $volume > 100) {
            return response()->json(['success' => false, 'message' => 'Invalid volume value']);
        }

        $response = $this->spotifyService->setVolume($volume);

        if (isset($response['error'])) {
            $errorMessage = $response['error'];
            $errorCode = $response['code'] ?? null;

            if ($errorCode === 'volume_control_not_supported') {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'code' => $errorCode
                ], 422);
            }

            return response()->json(['success' => false, 'message' => $errorMessage]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get current playback state
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlaybackState()
    {
        $response = $this->spotifyService->getCurrentPlayback();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        // Return the full playback state with success flag
        return response()->json(array_merge(
            ['success' => true],
            $response
        ));
    }

    /**
     * Seek to position in currently playing track
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function seekToPosition(Request $request)
    {
        $positionMs = $request->input('position_ms');

        if ($positionMs === null || $positionMs < 0) {
            return response()->json(['success' => false, 'message' => 'Invalid position value']);
        }

        $response = $this->spotifyService->seekToPosition($positionMs);

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get the next track in the queue
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextTrack()
    {
        $response = $this->spotifyService->getNextTrack();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(array_merge(
            ['success' => true],
            $response
        ));
    }

    /**
     * Get the user's library playlists
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPlaylists(Request $request)
    {
        $limit = $request->input('limit', 20);
        $includeLikedSongs = $request->input('include_liked_songs', true);

        try {
            $response = $this->spotifyService->getUserPlaylists($limit, $includeLikedSongs);

            if (isset($response['error'])) {
                return response()->json(['success' => false, 'message' => $response['error']]);
            }

            return response()->json(array_merge(
                ['success' => true],
                $response
            ));
        } catch (\Exception $e) {
            \Log::error('Error in getUserPlaylists: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the user's recently played playlists/albums
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    /**
     * Start playback with shuffle mode enabled for a playlist
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function shufflePlayPlaylist(Request $request)
    {
        $uri = $request->input('uri');

        if (!$uri) {
            return response()->json(['success' => false, 'message' => 'Playlist URI is required']);
        }

        $response = $this->spotifyService->shufflePlayPlaylist($uri);

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Check if tracks are saved in the user's library
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSavedTracks(Request $request)
    {
        $ids = $request->input('ids');

        if (!$ids || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'Track IDs are required']);
        }

        $response = $this->spotifyService->checkSavedTracks($ids);

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true, 'results' => $response]);
    }

    /**
     * Save or remove a track from the user's library
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSaveTrack(Request $request)
    {
        $id = $request->input('id');
        $saved = $request->input('saved');

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Track ID is required']);
        }

        if ($saved) {
            $response = $this->spotifyService->saveTracks([$id]);
        } else {
            $response = $this->spotifyService->removeTracks([$id]);
        }

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(['success' => true, 'saved' => $saved]);
    }
}
