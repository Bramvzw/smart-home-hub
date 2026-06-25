<?php

namespace Modules\Entertainment\Actions;

use Illuminate\Support\Facades\Log;
use Modules\Entertainment\Contracts\ConcertProvider;
use Modules\Entertainment\Contracts\EntertainmentCurator;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\TasteProfile;
use Modules\Entertainment\Services\Music\SpotifyReleasesService;
use Throwable;

class RefreshConcerts
{
    public function __construct(
        private readonly iterable $providers,
        private readonly EntertainmentCurator $curator,
        private readonly SpotifyReleasesService $spotify,
    ) {}

    public function __invoke(): int
    {
        $followed = collect($this->spotify->followedArtists())->pluck('name')->filter()->values()->all();
        $profile = TasteProfile::query()->firstOrCreate([], ['favorite_titles' => [], 'genres' => []]);
        $stored = 0;

        foreach ($this->providers as $provider) {
            /** @var ConcertProvider $provider */
            if (! in_array($provider->source(), (array) config('entertainment.concerts.sources', []), true)) {
                continue;
            }

            try {
                foreach ($provider->fetch() as $concertData) {
                    $concert = Concert::query()->updateOrCreate(
                        ['source' => $concertData->source, 'external_id' => $concertData->externalId ?: sha1($concertData->source.$concertData->artist.$concertData->date->toIso8601String())],
                        [
                            'artist' => $concertData->artist,
                            'title' => $concertData->title,
                            'venue' => $concertData->venue,
                            'city' => $concertData->city,
                            'date' => $concertData->date,
                            'url' => $concertData->url,
                        ]
                    );
                    $concert->update(['relevance' => $this->curator->concertRelevance($concert->fresh(), $followed, $profile)]);
                    $stored++;
                }
            } catch (Throwable $exception) {
                Log::warning('Entertainment concert source failed.', ['source' => $provider->source(), 'message' => $exception->getMessage()]);
            }
        }

        return $stored;
    }
}
