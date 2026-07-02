<?php

namespace Modules\Entertainment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Entertainment\Models\MusicRelease;
use Modules\Entertainment\Services\Music\SpotifyReleasesService;
use Throwable;

class RefreshMusicReleases
{
    public function __construct(private readonly SpotifyReleasesService $spotify) {}

    public function __invoke(?CarbonImmutable $since = null): int
    {
        $since ??= CarbonImmutable::now()->subDays((int) config('entertainment.music.since_days', 14));
        $stored = 0;

        $releases = collect($this->spotify->recentReleasesFor($this->spotify->followedArtists(), $since))
            ->filter(fn (array $release): bool => in_array($release['type'], (array) config('entertainment.music.include', ['album', 'single', 'ep']), true))
            ->values();

        $saved = $this->savedStatusFor($releases->pluck('spotify_id')->all());

        foreach ($releases as $release) {
            if (array_key_exists($release['spotify_id'], $saved)) {
                $release['saved_on_spotify'] = $saved[$release['spotify_id']];
            }

            MusicRelease::query()->updateOrCreate(['spotify_id' => $release['spotify_id']], $release);
            $stored++;
        }

        return $stored;
    }

    /** Best-effort: Spotify being unlinked/unreachable must not fail the refresh; saved_on_spotify stays null instead. */
    private function savedStatusFor(array $spotifyIds): array
    {
        if ($spotifyIds === []) {
            return [];
        }

        try {
            return $this->spotify->checkSaved($spotifyIds);
        } catch (Throwable $exception) {
            Log::warning('Spotify saved-status check failed; leaving saved_on_spotify unset.', ['message' => $exception->getMessage()]);

            return [];
        }
    }
}
