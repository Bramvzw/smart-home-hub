<?php

namespace Modules\Entertainment\Tests\Feature;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Modules\Entertainment\Actions\NotifyEntertainment;
use Modules\Entertainment\Actions\RefreshConcerts;
use Modules\Entertainment\Actions\RefreshFilms;
use Modules\Entertainment\Actions\RefreshMusicReleases;
use Modules\Entertainment\Contracts\ConcertProvider;
use Modules\Entertainment\Contracts\EntertainmentCurator;
use Modules\Entertainment\Data\ConcertData;
use Modules\Entertainment\Data\FilmPick;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\FilmRecommendation;
use Modules\Entertainment\Models\MusicRelease;
use Modules\Entertainment\Models\TasteProfile;
use Modules\Entertainment\Services\Music\SpotifyReleasesService;
use Modules\Entertainment\Services\Tmdb\TmdbClient;
use Tests\TestCase;

class EntertainmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_html_with_real_data(): void
    {
        $this->withoutVite();

        FilmRecommendation::query()->create([
            'tmdb_id' => 438631,
            'title' => 'Dune: Part Three',
            'overview' => 'Sci-fi epic.',
            'availability' => ['cinema', 'netflix'],
            'poster_url' => null,
            'why' => 'Omdat je sci-fi met sterke visuals waardeert.',
            'score' => 96,
            'dismissed' => false,
            'refreshed_at' => CarbonImmutable::parse('2026-06-25 07:00:00'),
        ]);
        Concert::query()->create([
            'source' => 'hedon',
            'external_id' => 'hedon-1',
            'artist' => 'Sigrid',
            'venue' => 'Hedon',
            'city' => 'Zwolle',
            'date' => '2026-09-14 20:00:00',
            'url' => 'https://example.com',
            'relevance' => 'followed',
        ]);
        MusicRelease::query()->create([
            'spotify_id' => 'release-1',
            'artist' => 'Fred again..',
            'title' => 'places to be',
            'type' => 'single',
            'release_date' => '2026-06-25',
        ]);

        $this->get(route('entertainment.index'))
            ->assertOk()
            ->assertSee('Films')
            ->assertSee('Concerten')
            ->assertSee('Dune: Part Three')
            ->assertSee('Sigrid')
            ->assertSee('Fred again..');
    }

    public function test_refresh_music_releases_stores_new_releases_without_duplicates(): void
    {
        $this->app->instance(SpotifyReleasesService::class, new FakeSpotifyReleases);

        app(RefreshMusicReleases::class)(CarbonImmutable::parse('2026-06-01'));
        app(RefreshMusicReleases::class)(CarbonImmutable::parse('2026-06-01'));

        $this->assertDatabaseCount('music_releases', 1);
        $this->assertDatabaseHas('music_releases', ['spotify_id' => 'release-1', 'artist' => 'The National']);
    }

    public function test_refresh_concerts_is_resilient_and_sets_relevance(): void
    {
        $action = new RefreshConcerts(
            [new FakeConcertProvider, new FailingConcertProvider],
            new FakeEntertainmentCurator,
            new FakeSpotifyReleases,
        );

        $stored = $action();

        $this->assertSame(1, $stored);
        $this->assertDatabaseHas('concerts', [
            'source' => 'hedon',
            'artist' => 'The National',
            'relevance' => 'followed',
        ]);
    }

    public function test_notify_entertainment_bundles_music_and_notifies_relevant_concerts_once(): void
    {
        MusicRelease::query()->create([
            'spotify_id' => 'release-1',
            'artist' => 'The National',
            'title' => 'New Song',
            'type' => 'single',
            'release_date' => '2026-06-25',
        ]);
        Concert::query()->create([
            'source' => 'hedon',
            'external_id' => 'hedon-1',
            'artist' => 'The National',
            'venue' => 'Hedon',
            'city' => 'Zwolle',
            'date' => '2026-07-01 20:00:00',
            'url' => 'https://example.com',
            'relevance' => 'hedon',
        ]);
        $notifier = new FakeEntertainmentNotifier;
        $this->app->instance(HubNotifier::class, $notifier);

        app(NotifyEntertainment::class)();
        app(NotifyEntertainment::class)();

        $this->assertCount(2, $notifier->sent);
        $this->assertTrue(MusicRelease::query()->first()->notified);
        $this->assertTrue(Concert::query()->first()->notified);
    }

    public function test_refresh_films_with_curator_stores_why_and_contract_endpoints(): void
    {
        $this->app->instance(TmdbClient::class, new FakeTmdbClient);
        $this->app->instance(EntertainmentCurator::class, new FakeEntertainmentCurator);

        app(RefreshFilms::class)();
        $film = FilmRecommendation::query()->firstOrFail();

        $this->assertSame('Omdat dit bij je smaak past.', $film->why);

        $this->postJson(route('entertainment.films.feedback', $film), ['sentiment' => 'up'])->assertOk();
        $this->postJson(route('entertainment.films.dismiss', $film))->assertOk();
        $this->putJson(route('entertainment.taste.update'), [
            'favorite_titles' => ['Dune'],
            'genres' => ['sci-fi'],
            'notes' => 'Rustige sciencefiction.',
        ])->assertOk()->assertJsonPath('genres', ['sci-fi']);

        $this->getJson(route('entertainment.index'))
            ->assertOk()
            ->assertJsonStructure(['films', 'concerts', 'music']);
        $this->getJson(route('entertainment.taste.show'))->assertJsonPath('favorite_titles', ['Dune']);
    }
}

class FakeSpotifyReleases extends SpotifyReleasesService
{
    public function __construct() {}

    public function followedArtists(): array
    {
        return [['id' => 'artist-1', 'name' => 'The National']];
    }

    public function recentReleasesFor(array $artists, CarbonImmutable $since): array
    {
        return [[
            'spotify_id' => 'release-1',
            'artist' => 'The National',
            'title' => 'New Song',
            'type' => 'single',
            'release_date' => '2026-06-25',
            'url' => 'https://spotify.example/release',
            'image_url' => null,
        ]];
    }
}

class FakeConcertProvider implements ConcertProvider
{
    public function source(): string
    {
        return 'hedon';
    }

    public function fetch(): array
    {
        return [new ConcertData('hedon', 'hedon-1', 'The National', 'The National', 'Hedon', 'Zwolle', CarbonImmutable::parse('2026-07-01 20:00:00'), 'https://example.com')];
    }
}

class FailingConcertProvider implements ConcertProvider
{
    public function source(): string
    {
        return 'ticketmaster';
    }

    public function fetch(): array
    {
        throw new \RuntimeException('broken');
    }
}

class FakeEntertainmentCurator implements EntertainmentCurator
{
    public function curateFilms(array $candidates, TasteProfile $profile, Collection $feedback): array
    {
        return [new FilmPick((int) $candidates[0]['tmdb_id'], 'Omdat dit bij je smaak past.', 95)];
    }

    public function concertRelevance(Concert $concert, array $followedArtists, TasteProfile $profile): string
    {
        return in_array($concert->artist, $followedArtists, true) ? 'followed' : ($concert->source === 'hedon' ? 'hedon' : 'none');
    }
}

class FakeTmdbClient extends TmdbClient
{
    public function nowPlayingNl(): array
    {
        return [['id' => 1, 'title' => 'Dune', 'overview' => 'Desert movie', 'poster_path' => '/dune.jpg']];
    }

    public function watchProviders(int $tmdbId): array
    {
        return ['flatrate' => [['provider_name' => 'Netflix']]];
    }
}

class FakeEntertainmentNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '', 10);
    }

    public function send(string $title, string $message): void
    {
        $this->sent[] = compact('title', 'message');
    }
}
