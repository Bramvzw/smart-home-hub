<?php

namespace Modules\Entertainment\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Entertainment\Services\Tmdb\TmdbClient;
use Tests\TestCase;

class TmdbClientTest extends TestCase
{
    public function test_tmdb_client_parses_now_playing_and_watch_providers(): void
    {
        config(['entertainment.tmdb.api_key' => 'token']);
        Http::fake([
            'https://api.themoviedb.org/3/movie/now_playing*' => Http::response(['results' => [['id' => 1, 'title' => 'Dune']]]),
            'https://api.themoviedb.org/3/movie/1/watch/providers*' => Http::response(['results' => ['NL' => ['flatrate' => [['provider_name' => 'Netflix']]]]]),
        ]);

        $client = app(TmdbClient::class);

        $this->assertSame('Dune', $client->nowPlayingNl()[0]['title']);
        $this->assertSame('Netflix', $client->watchProviders(1)['flatrate'][0]['provider_name']);
    }
}
