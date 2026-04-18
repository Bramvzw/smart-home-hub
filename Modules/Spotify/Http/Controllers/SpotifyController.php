<?php

namespace Modules\Spotify\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use Modules\Spotify\Http\Requests\CheckSavedTracksRequest;
use Modules\Spotify\Http\Requests\PlayRequest;
use Modules\Spotify\Http\Requests\SearchRequest;
use Modules\Spotify\Http\Requests\SeekRequest;
use Modules\Spotify\Http\Requests\SetRepeatRequest;
use Modules\Spotify\Http\Requests\SetShuffleRequest;
use Modules\Spotify\Http\Requests\SetVolumeRequest;
use Modules\Spotify\Http\Requests\ToggleSaveTrackRequest;
use Modules\Spotify\Http\Requests\TransferPlaybackRequest;
use Modules\Spotify\Http\Requests\UriRequest;
use Modules\Spotify\Services\SpotifyService;

class SpotifyController extends Controller
{
    public function __construct(protected SpotifyService $spotifyService) {}

    /**
     * Return a JSON response from a service call, handling errors consistently.
     */
    private function serviceResponse(array $response, array $extra = []): JsonResponse
    {
        if (isset($response['error'])) {
            $errorCode = $response['code'] ?? null;
            $payload = ['success' => false, 'message' => $response['error']];

            if ($errorCode) {
                $payload['code'] = $errorCode;
            }

            $status = match ($errorCode) {
                'auth_required' => 401,
                'volume_control_not_supported' => 422,
                default => isset($response['error']) ? 502 : 200,
            };

            return response()->json($payload, $status);
        }

        return response()->json(array_merge(['success' => true], $extra));
    }

    /**
     * Return a JSON response for read operations, extracting specific data keys.
     */
    private function readResponse(array $response, array $dataKeys = []): JsonResponse
    {
        if (isset($response['error'])) {
            $status = match ($response['code'] ?? null) {
                'auth_required' => 401,
                default => 502,
            };
            return response()->json(['success' => false, 'message' => $response['error']], $status);
        }

        $data = ['success' => true];
        foreach ($dataKeys as $key) {
            $data[$key] = $response[$key] ?? null;
        }

        return response()->json($data);
    }

    /**
     * Display the Spotify interface.
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
     * Handle the callback from Spotify authorization.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $state = $request->query('state');
        $expectedState = session()->pull('spotify_oauth_state');

        if (!$state || $state !== $expectedState) {
            return redirect()->route('spotify.index')
                ->with('error', 'Authorization failed: Invalid state parameter');
        }

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

    // ── Playback Controls ─────────────────────────────────────────────

    /**
     * Start or resume playback, optionally with a URI.
     */
    public function play(PlayRequest $request): JsonResponse
    {
        return $this->serviceResponse(
            $this->spotifyService->play($request->input('uri'))
        );
    }

    /**
     * Pause playback.
     */
    public function pause(): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->pause());
    }

    /**
     * Skip to next track.
     */
    public function next(): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->next());
    }

    /**
     * Skip to previous track.
     */
    public function previous(): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->previous());
    }

    /**
     * Set playback volume (0–100).
     */
    public function setVolume(SetVolumeRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->setVolume($request->integer('volume')));
    }

    /**
     * Seek to a position in the currently playing track.
     */
    public function seekToPosition(SeekRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->seekToPosition($request->integer('position_ms')));
    }

    /**
     * Toggle shuffle mode on/off.
     */
    public function setShuffle(SetShuffleRequest $request): JsonResponse
    {
        $state = (bool) $request->input('state');

        return $this->serviceResponse(
            $this->spotifyService->setShuffle($state),
            ['state' => $state]
        );
    }

    /**
     * Cycle repeat mode (off → context → track → off).
     */
    public function setRepeatMode(SetRepeatRequest $request): JsonResponse
    {
        $state = $request->input('state');

        return $this->serviceResponse(
            $this->spotifyService->setRepeatMode($state),
            ['state' => $state]
        );
    }

    // ── Playback State & Queue ────────────────────────────────────────

    /**
     * Get the current playback state.
     */
    public function getPlaybackState(): JsonResponse
    {
        $response = $this->spotifyService->getCurrentPlayback();

        return $this->readResponse($response, ['item', 'is_playing', 'progress_ms', 'shuffle_state', 'repeat_state', 'device', 'actions']);
    }

    /**
     * Get the next track in the queue.
     */
    public function getNextTrack(): JsonResponse
    {
        $response = $this->spotifyService->getNextTrack();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']]);
        }

        return response()->json(array_merge(['success' => true], $response));
    }

    /**
     * Get the current playback queue.
     */
    public function getQueue(): JsonResponse
    {
        $response = $this->spotifyService->getQueue();

        return $this->readResponse($response, ['queue']);
    }

    /**
     * Add a track to the playback queue.
     */
    public function addToQueue(UriRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->addToQueue($request->input('uri')));
    }

    /**
     * Get recently played tracks.
     */
    public function getRecentlyPlayed(): JsonResponse
    {
        $response = $this->spotifyService->getRecentlyPlayed(20);

        return $this->readResponse($response, ['items']);
    }

    /**
     * Get available playback devices.
     */
    public function getDevices(): JsonResponse
    {
        $response = $this->spotifyService->getAvailableDevices();

        return $this->readResponse($response, ['devices']);
    }

    /**
     * Transfer playback to a different device.
     */
    public function transferPlayback(TransferPlaybackRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->transferPlayback($request->input('device_id')));
    }

    /**
     * Search for tracks, albums, and playlists.
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $query = $request->input('q');
        $type = $request->input('type', 'track,album,playlist');
        $response = $this->spotifyService->search($query, $type, 10);

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']], 502);
        }

        return response()->json([
            'success' => true,
            'tracks' => $response['tracks']['items'] ?? [],
            'albums' => $response['albums']['items'] ?? [],
            'playlists' => $response['playlists']['items'] ?? [],
        ]);
    }

    // ── Playlists ─────────────────────────────────────────────────────

    /**
     * Get the user's playlists.
     */
    public function getUserPlaylists(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $includeLikedSongs = $request->input('include_liked_songs', true);

        try {
            $response = $this->spotifyService->getUserPlaylists($limit, $includeLikedSongs);

            if (isset($response['error'])) {
                return response()->json(['success' => false, 'message' => $response['error']], 502);
            }

            return response()->json(array_merge(['success' => true], $response));
        } catch (\Exception $e) {
            Log::error('Error in getUserPlaylists: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }

    /**
     * Shuffle-play a playlist.
     */
    public function shufflePlayPlaylist(UriRequest $request): JsonResponse
    {
        return $this->serviceResponse($this->spotifyService->shufflePlayPlaylist($request->input('uri')));
    }

    // ── Library ───────────────────────────────────────────────────────

    /**
     * Check if tracks are saved in the user's library.
     */
    public function checkSavedTracks(CheckSavedTracksRequest $request): JsonResponse
    {
        $ids = $request->input('ids');
        $response = $this->spotifyService->checkSavedTracks($ids);

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']], 502);
        }

        return response()->json(['success' => true, 'results' => $response]);
    }

    /**
     * Save or remove a track from the user's library.
     */
    public function toggleSaveTrack(ToggleSaveTrackRequest $request): JsonResponse
    {
        $id = $request->input('id');
        $saved = $request->boolean('saved');

        $response = $saved
            ? $this->spotifyService->saveTracks([$id])
            : $this->spotifyService->removeTracks([$id]);

        return $this->serviceResponse($response, ['saved' => $saved]);
    }
}
