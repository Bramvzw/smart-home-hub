<?php

namespace Modules\Spotify\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Modules\Spotify\Services\SpotifyService;
use Tests\TestCase;

class SpotifyControllerTest extends TestCase
{
    protected function mockSpotifyService(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(SpotifyService::class);
        $this->app->instance(SpotifyService::class, $mock);
        return $mock;
    }

    public function test_index_page_loads()
    {
        $response = $this->get(route('spotify.index'));

        $response->assertStatus(200);
    }

    public function test_callback_rejects_missing_state()
    {
        $response = $this->get(route('spotify.callback', ['code' => 'test_code']));

        $response->assertRedirect(route('spotify.index'));
        $response->assertSessionHas('error');
    }

    public function test_callback_rejects_invalid_state()
    {
        session(['spotify_oauth_state' => 'valid_state']);

        $response = $this->get(route('spotify.callback', [
            'code' => 'test_code',
            'state' => 'invalid_state',
        ]));

        $response->assertRedirect(route('spotify.index'));
        $response->assertSessionHas('error');
    }

    public function test_callback_rejects_missing_code()
    {
        session(['spotify_oauth_state' => 'valid_state']);

        $response = $this->get(route('spotify.callback', [
            'state' => 'valid_state',
        ]));

        $response->assertRedirect(route('spotify.index'));
        $response->assertSessionHas('error');
    }

    public function test_play_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        // This will fail against real API but tests the route exists
        $response = $this->postJson(route('spotify.play'));

        $response->assertJsonStructure(['success']);
    }

    public function test_pause_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.pause'));

        $response->assertJsonStructure(['success']);
    }

    public function test_volume_rejects_invalid_value()
    {
        $response = $this->postJson(route('spotify.volume'), ['volume' => 150]);

        $response->assertStatus(422);
    }

    public function test_volume_rejects_negative_value()
    {
        $response = $this->postJson(route('spotify.volume'), ['volume' => -1]);

        $response->assertStatus(422);
    }

    public function test_seek_rejects_invalid_position()
    {
        $response = $this->postJson(route('spotify.seek'), ['position_ms' => -1]);

        $response->assertStatus(422);
    }

    public function test_check_saved_tracks_rejects_missing_ids()
    {
        $response = $this->getJson(route('spotify.check-saved-tracks'));

        $response->assertStatus(422);
    }

    public function test_toggle_save_track_rejects_missing_id()
    {
        $response = $this->postJson(route('spotify.toggle-save-track'));

        $response->assertStatus(422);
    }

    public function test_shuffle_play_rejects_missing_uri()
    {
        $response = $this->postJson(route('spotify.shuffle-play-playlist'));

        $response->assertStatus(422);
    }

    // Shuffle

    public function test_shuffle_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.shuffle'), ['state' => true]);

        $response->assertJsonStructure(['success']);
    }

    // Repeat

    public function test_repeat_accepts_valid_states()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        foreach (['off', 'context', 'track'] as $state) {
            $response = $this->postJson(route('spotify.repeat'), ['state' => $state]);
            $response->assertJsonStructure(['success']);
        }
    }

    public function test_repeat_rejects_invalid_state()
    {
        $response = $this->postJson(route('spotify.repeat'), ['state' => 'invalid']);

        $response->assertStatus(422);
    }

    public function test_repeat_rejects_missing_state()
    {
        $response = $this->postJson(route('spotify.repeat'));

        $response->assertStatus(422);
    }

    // Devices

    public function test_devices_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.devices'));

        $response->assertJsonStructure(['success']);
    }

    public function test_transfer_playback_rejects_missing_device_id()
    {
        $response = $this->postJson(route('spotify.transfer-playback'));

        $response->assertStatus(422);
    }

    public function test_transfer_playback_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.transfer-playback'), ['device_id' => 'abc123']);

        $response->assertJsonStructure(['success']);
    }

    // Recently played

    public function test_recently_played_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.recently-played'));

        $response->assertJsonStructure(['success']);
    }

    // Search

    public function test_search_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.search', ['q' => 'test']));

        $response->assertJsonStructure(['success']);
    }

    public function test_search_rejects_empty_query()
    {
        $response = $this->getJson(route('spotify.search'));

        $response->assertStatus(422);
    }

    public function test_search_rejects_blank_query()
    {
        $response = $this->getJson(route('spotify.search', ['q' => '   ']));

        $response->assertStatus(422);
    }

    // Queue

    public function test_queue_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.queue'));

        $response->assertJsonStructure(['success']);
    }

    // Add to queue

    public function test_add_to_queue_rejects_missing_uri()
    {
        $response = $this->postJson(route('spotify.add-to-queue'));

        $response->assertStatus(422);
    }

    public function test_add_to_queue_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.add-to-queue'), ['uri' => 'spotify:track:4uLU6hMCjMI75M1A2tKUQC']);

        $response->assertJsonStructure(['success']);
    }

    // Next / Previous

    public function test_next_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.next'));

        $response->assertJsonStructure(['success']);
    }

    public function test_previous_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.previous'));

        $response->assertJsonStructure(['success']);
    }

    // Playback state

    public function test_playback_state_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.playback-state'));

        $response->assertJsonStructure(['success']);
    }

    // Next track

    public function test_next_track_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.next-track'));

        $response->assertJsonStructure(['success']);
    }

    // User playlists

    public function test_user_playlists_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->getJson(route('spotify.user-playlists'));

        $response->assertJsonStructure(['success']);
    }

    // Toggle save track

    public function test_toggle_save_track_returns_json()
    {
        Cache::put('spotify_access_token', 'fake_token', 3600);

        $response = $this->postJson(route('spotify.toggle-save-track'), [
            'id' => 'track_123',
            'saved' => true,
        ]);

        $response->assertJsonStructure(['success']);
    }

    // Mocked service tests

    public function test_play_success_with_mocked_service()
    {
        $mock = $this->mockSpotifyService();
        $mock->shouldReceive('play')->once()->with(null)->andReturn(['success' => true]);
        Cache::put('spotify_access_token', 'fake', 3600);
        $response = $this->postJson(route('spotify.play'));
        $response->assertJson(['success' => true]);
    }

    public function test_play_with_uri_validates_format()
    {
        $response = $this->postJson(route('spotify.play'), ['uri' => 'not-a-valid-uri']);
        $response->assertStatus(422);
    }

    public function test_volume_returns_422_for_invalid_value()
    {
        $response = $this->postJson(route('spotify.volume'), ['volume' => 150]);
        $response->assertStatus(422);
    }

    public function test_seek_returns_422_for_negative_position()
    {
        $response = $this->postJson(route('spotify.seek'), ['position_ms' => -1]);
        $response->assertStatus(422);
    }

    public function test_seek_returns_422_for_missing_position()
    {
        $response = $this->postJson(route('spotify.seek'));
        $response->assertStatus(422);
    }

    public function test_repeat_returns_422_for_invalid_state()
    {
        $response = $this->postJson(route('spotify.repeat'), ['state' => 'invalid']);
        $response->assertStatus(422);
    }

    public function test_repeat_returns_422_for_missing_state()
    {
        $response = $this->postJson(route('spotify.repeat'));
        $response->assertStatus(422);
    }

    public function test_search_returns_422_for_missing_query()
    {
        $response = $this->getJson(route('spotify.search'));
        $response->assertStatus(422);
    }

    public function test_search_returns_422_for_blank_query()
    {
        $response = $this->getJson(route('spotify.search', ['q' => '   ']));
        $response->assertStatus(422);
    }

    public function test_search_returns_422_for_too_long_query()
    {
        $response = $this->getJson(route('spotify.search', ['q' => str_repeat('a', 201)]));
        $response->assertStatus(422);
    }

    public function test_transfer_playback_returns_422_for_missing_device_id()
    {
        $response = $this->postJson(route('spotify.transfer-playback'));
        $response->assertStatus(422);
    }

    public function test_check_saved_tracks_returns_422_for_missing_ids()
    {
        $response = $this->getJson(route('spotify.check-saved-tracks'));
        $response->assertStatus(422);
    }

    public function test_check_saved_tracks_returns_422_for_non_array_ids()
    {
        $response = $this->getJson(route('spotify.check-saved-tracks', ['ids' => 'not-an-array']));
        $response->assertStatus(422);
    }

    public function test_toggle_save_track_returns_422_for_missing_fields()
    {
        $response = $this->postJson(route('spotify.toggle-save-track'));
        $response->assertStatus(422);
    }

    public function test_toggle_save_track_returns_422_for_missing_saved_flag()
    {
        $response = $this->postJson(route('spotify.toggle-save-track'), ['id' => 'track123']);
        $response->assertStatus(422);
    }

    public function test_add_to_queue_returns_422_for_invalid_uri()
    {
        $response = $this->postJson(route('spotify.add-to-queue'), ['uri' => 'not-a-spotify-uri']);
        $response->assertStatus(422);
    }

    public function test_getPlaybackState_returns_502_when_service_errors()
    {
        $mock = $this->mockSpotifyService();
        $mock->shouldReceive('getCurrentPlayback')->once()->andReturn(['error' => 'Spotify API request failed']);
        Cache::put('spotify_access_token', 'fake', 3600);
        $response = $this->getJson(route('spotify.playback-state'));
        $response->assertStatus(502);
        $response->assertJson(['success' => false]);
    }

    public function test_getDevices_returns_devices_on_success()
    {
        $mock = $this->mockSpotifyService();
        $mock->shouldReceive('getAvailableDevices')->once()->andReturn([
            'devices' => [['id' => 'abc', 'name' => 'My Phone', 'type' => 'Smartphone', 'is_active' => true]]
        ]);
        Cache::put('spotify_access_token', 'fake', 3600);
        $response = $this->getJson(route('spotify.devices'));
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['devices']);
    }
}
