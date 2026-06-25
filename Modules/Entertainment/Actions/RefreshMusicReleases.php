<?php

namespace Modules\Entertainment\Actions;

use Carbon\CarbonImmutable;
use Modules\Entertainment\Models\MusicRelease;
use Modules\Entertainment\Services\Music\SpotifyReleasesService;

class RefreshMusicReleases
{
    public function __construct(private readonly SpotifyReleasesService $spotify) {}

    public function __invoke(?CarbonImmutable $since = null): int
    {
        $since ??= CarbonImmutable::now()->subDays((int) config('entertainment.music.since_days', 14));
        $stored = 0;

        foreach ($this->spotify->recentReleasesFor($this->spotify->followedArtists(), $since) as $release) {
            if (! in_array($release['type'], (array) config('entertainment.music.include', ['album', 'single', 'ep']), true)) {
                continue;
            }

            MusicRelease::query()->updateOrCreate(['spotify_id' => $release['spotify_id']], $release);
            $stored++;
        }

        return $stored;
    }
}
