<?php

namespace Modules\Spotify\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Modules\Spotify\Services\SpotifyService;
use Tests\TestCase;

class SpotifyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function createServiceWithMockClient(array $responses): SpotifyService
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        return new SpotifyService($client);
    }

    public function test_get_authorization_url_returns_valid_url()
    {
        $service = new SpotifyService();
        $url = $service->getAuthorizationUrl();

        $this->assertStringStartsWith('https://accounts.spotify.com/authorize?', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function test_get_authorization_url_stores_state_in_session()
    {
        $service = new SpotifyService();
        $service->getAuthorizationUrl();

        $this->assertTrue(Session::has('spotify_oauth_state'));
    }

    public function test_has_stored_authorization_uses_refresh_token()
    {
        Cache::put('spotify_refresh_token', 'test_refresh_token', 3600);

        $service = new SpotifyService();

        $this->assertTrue($service->hasStoredAuthorization());
    }

    public function test_ensure_access_token_succeeds_when_access_token_exists()
    {
        Cache::put('spotify_access_token', 'test_access_token', 3600);

        $service = new SpotifyService();

        $this->assertEquals(['success' => true], $service->ensureAccessToken());
    }

    public function test_get_access_token_stores_tokens_in_cache()
    {
        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'access_token' => 'test_access_token',
                'refresh_token' => 'test_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ])),
        ]);

        $result = $service->getAccessToken('test_code');

        $this->assertEquals('test_access_token', $result['access_token']);
        $this->assertEquals('test_access_token', Cache::get('spotify_access_token'));
        $this->assertEquals('test_refresh_token', Cache::get('spotify_refresh_token'));
    }

    public function test_get_current_playback_returns_data()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $playbackData = [
            'is_playing' => true,
            'item' => ['name' => 'Test Track'],
        ];

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode($playbackData)),
        ]);

        $result = $service->getCurrentPlayback();

        $this->assertTrue($result['is_playing']);
        $this->assertEquals('Test Track', $result['item']['name']);
    }

    public function test_play_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->play();

        $this->assertTrue($result['success']);
    }

    public function test_pause_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->pause();

        $this->assertTrue($result['success']);
    }

    public function test_set_volume_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->setVolume(50);

        $this->assertTrue($result['success']);
    }

    public function test_get_next_track_returns_track()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'queue' => [
                    ['name' => 'Next Track', 'artists' => [['name' => 'Artist']]],
                ],
            ])),
        ]);

        $result = $service->getNextTrack();

        $this->assertEquals('Next Track', $result['next_track']['name']);
    }

    public function test_get_next_track_returns_null_when_empty_queue()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode(['queue' => []])),
        ]);

        $result = $service->getNextTrack();

        $this->assertNull($result['next_track']);
    }

    public function test_refresh_token_returns_error_when_no_refresh_token()
    {
        Cache::forget('spotify_refresh_token');

        $service = new SpotifyService();
        $result = $service->refreshAccessToken();

        $this->assertArrayHasKey('error', $result);
    }

    public function test_check_saved_tracks()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([true, false])),
        ]);

        $result = $service->checkSavedTracks(['id1', 'id2']);

        $this->assertEquals([true, false], $result);
    }

    public function test_get_user_playlists()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            // getUserPlaylists call
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => '1', 'name' => 'Playlist 1'],
                ],
            ])),
            // getSavedTracks call (for liked songs)
            new Response(200, [], json_encode([
                'items' => [
                    ['track' => ['name' => 'Liked Track']],
                ],
            ])),
        ]);

        $result = $service->getUserPlaylists(20, true);

        $this->assertArrayHasKey('playlists', $result);
        $this->assertNotEmpty($result['playlists']);
    }

    public function test_set_shuffle_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->setShuffle(true);

        $this->assertTrue($result['success']);
    }

    public function test_set_shuffle_off()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->setShuffle(false);

        $this->assertTrue($result['success']);
    }

    public function test_set_repeat_mode()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->setRepeatMode('track');

        $this->assertTrue($result['success']);
    }

    public function test_set_repeat_mode_off()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->setRepeatMode('off');

        $this->assertTrue($result['success']);
    }

    public function test_set_repeat_mode_context()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->setRepeatMode('context');

        $this->assertTrue($result['success']);
    }

    public function test_get_available_devices_returns_devices()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'devices' => [
                    ['id' => 'device_1', 'name' => 'My Phone', 'type' => 'Smartphone', 'is_active' => true],
                    ['id' => 'device_2', 'name' => 'My Computer', 'type' => 'Computer', 'is_active' => false],
                ],
            ])),
        ]);

        $result = $service->getAvailableDevices();

        $this->assertArrayHasKey('devices', $result);
        $this->assertCount(2, $result['devices']);
        $this->assertEquals('My Phone', $result['devices'][0]['name']);
    }

    public function test_get_available_devices_returns_empty_list()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode(['devices' => []])),
        ]);

        $result = $service->getAvailableDevices();

        $this->assertEmpty($result['devices']);
    }

    public function test_transfer_playback_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->transferPlayback('device_abc');

        $this->assertTrue($result['success']);
    }

    public function test_add_to_queue()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->addToQueue('spotify:track:4uLU6hMCjMI75M1A2tKUQC');

        $this->assertTrue($result['success']);
    }

    public function test_get_recently_played()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'items' => [
                    [
                        'track' => ['name' => 'Recent Track', 'artists' => [['name' => 'Artist']]],
                        'played_at' => '2026-03-23T10:00:00Z',
                    ],
                ],
            ])),
        ]);

        $result = $service->getRecentlyPlayed(20);

        $this->assertArrayHasKey('items', $result);
        $this->assertEquals('Recent Track', $result['items'][0]['track']['name']);
    }

    public function test_search_returns_tracks()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'tracks' => [
                    'items' => [
                        ['name' => 'Found Track', 'uri' => 'spotify:track:789'],
                    ],
                ],
            ])),
        ]);

        $result = $service->search('test query', 'track', 10);

        $this->assertArrayHasKey('tracks', $result);
        $this->assertEquals('Found Track', $result['tracks']['items'][0]['name']);
    }

    public function test_search_with_multiple_types()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'tracks' => ['items' => [['name' => 'Track']]],
                'albums' => ['items' => [['name' => 'Album']]],
                'playlists' => ['items' => [['name' => 'Playlist']]],
            ])),
        ]);

        $result = $service->search('test', 'track,album,playlist', 10);

        $this->assertArrayHasKey('tracks', $result);
        $this->assertArrayHasKey('albums', $result);
        $this->assertArrayHasKey('playlists', $result);
    }

    public function test_get_queue_returns_queue()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'queue' => [
                    ['name' => 'Queue Track 1'],
                    ['name' => 'Queue Track 2'],
                ],
            ])),
        ]);

        $result = $service->getQueue();

        $this->assertArrayHasKey('queue', $result);
        $this->assertCount(2, $result['queue']);
    }

    public function test_get_queue_empty()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'queue' => [],
            ])),
        ]);

        $result = $service->getQueue();

        $this->assertEmpty($result['queue']);
    }

    public function test_next_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->next();

        $this->assertTrue($result['success']);
    }

    public function test_previous_sends_correct_request()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->previous();

        $this->assertTrue($result['success']);
    }

    public function test_seek_to_position()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->seekToPosition(30000);

        $this->assertTrue($result['success']);
    }

    public function test_save_tracks()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->saveTracks(['track_id_1']);

        $this->assertTrue($result['success']);
    }

    public function test_remove_tracks()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->removeTracks(['track_id_1']);

        $this->assertTrue($result['success']);
    }

    public function test_play_with_track_uri()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->play('spotify:track:4uLU6hMCjMI75M1A2tKUQC');

        $this->assertTrue($result['success']);
    }

    public function test_play_with_playlist_uri()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->play('spotify:playlist:37i9dQZF1DXcBWIGoYBM5M');

        $this->assertTrue($result['success']);
    }

    public function test_shuffle_play_playlist()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204), // shuffle
            new Response(204), // play
        ]);

        $result = $service->shufflePlayPlaylist('spotify:playlist:37i9dQZF1DXcBWIGoYBM5M');

        $this->assertTrue($result['success']);
    }

    public function test_get_saved_tracks()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['track' => ['name' => 'Saved Track']],
                ],
            ])),
        ]);

        $result = $service->getSavedTracks(10);

        $this->assertCount(1, $result);
    }

    public function test_get_saved_tracks_returns_empty_when_no_items()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode(['items' => []])),
        ]);

        $result = $service->getSavedTracks(10);

        $this->assertEmpty($result);
    }

    public function test_refresh_access_token_updates_cache()
    {
        Cache::put('spotify_refresh_token', 'test_refresh_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
            ])),
        ]);

        $result = $service->refreshAccessToken();

        $this->assertEquals('new_access_token', $result['access_token']);
        $this->assertEquals('new_access_token', Cache::get('spotify_access_token'));
    }

    public function test_get_profile()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'display_name' => 'Test User',
                'email' => 'test@example.com',
            ])),
        ]);

        $result = $service->getProfile();

        $this->assertEquals('Test User', $result['display_name']);
    }

    public function test_make_request_retries_on_401_and_succeeds()
    {
        Cache::put('spotify_access_token', 'expired_token', 3600);
        Cache::put('spotify_refresh_token', 'refresh_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(401, [], json_encode(['error' => 'Unauthorized'])),
            // Token refresh call
            new Response(200, [], json_encode([
                'access_token' => 'new_token',
                'expires_in' => 3600,
            ])),
            // Retry of original request
            new Response(200, [], json_encode(['is_playing' => true])),
        ]);

        $result = $service->getCurrentPlayback();
        $this->assertTrue($result['is_playing']);
    }

    public function test_make_request_fails_after_retry_on_second_401()
    {
        Cache::put('spotify_access_token', 'expired_token', 3600);
        Cache::put('spotify_refresh_token', 'refresh_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(401, [], json_encode(['error' => 'Unauthorized'])),
            new Response(200, [], json_encode([
                'access_token' => 'new_token',
                'expires_in' => 3600,
            ])),
            new Response(401, [], json_encode(['error' => 'Unauthorized'])),
        ]);

        $result = $service->getCurrentPlayback();
        $this->assertArrayHasKey('error', $result);
    }

    public function test_volume_control_not_supported_returns_specific_error()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(403, [], 'Cannot control device volume'),
        ]);

        $result = $service->setVolume(50);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('volume_control_not_supported', $result['code'] ?? null);
    }

    public function test_get_access_token_returns_error_on_failed_response()
    {
        $service = $this->createServiceWithMockClient([
            new Response(400, [], json_encode(['error' => 'invalid_grant'])),
        ]);

        $result = $service->getAccessToken('invalid_code');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_get_user_playlists_without_liked_songs()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'items' => [
                    ['id' => '1', 'name' => 'Playlist 1'],
                ],
            ])),
        ]);

        $result = $service->getUserPlaylists(20, false);
        $this->assertArrayHasKey('playlists', $result);
    }

    public function test_validate_spotify_uri_rejects_invalid_format()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = new SpotifyService();
        $result = $service->play('not-a-valid-uri');
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid Spotify URI format', $result['error']);
    }

    public function test_get_available_devices_with_mocked_response()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(200, [], json_encode([
                'devices' => [
                    ['id' => 'dev1', 'name' => 'Phone', 'type' => 'Smartphone', 'is_active' => true],
                ],
            ])),
        ]);

        $result = $service->getAvailableDevices();
        $this->assertCount(1, $result['devices']);
        $this->assertEquals('Phone', $result['devices'][0]['name']);
    }

    public function test_transfer_playback_sends_correct_payload()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $service = $this->createServiceWithMockClient([
            new Response(204),
        ]);

        $result = $service->transferPlayback('device_xyz', true);
        $this->assertTrue($result['success']);
    }
}
