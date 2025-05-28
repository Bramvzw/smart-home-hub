<?php

namespace App\Http\Controllers;

use App\Services\SpotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        return view('spotify.controls', [
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
            return redirect()->route('spotify.controls')
                ->with('error', 'Authorization failed: No code provided');
        }

        $response = $this->spotifyService->getAccessToken($code);

        if (isset($response['error'])) {
            return redirect()->route('spotify.controls')
                ->with('error', 'Authorization failed: ' . $response['error']);
        }

        return redirect()->route('spotify.controls')
            ->with('success', 'Successfully connected to Spotify');
    }

    /**
     * Play music
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function play()
    {
        $response = $this->spotifyService->play();

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
}
