<?php

namespace Modules\Entertainment\View\ViewModels;

use Modules\Entertainment\Http\Resources\ConcertResource;
use Modules\Entertainment\Http\Resources\FilmRecommendationResource;
use Modules\Entertainment\Http\Resources\MusicReleaseResource;
use Illuminate\Support\Facades\Cache;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\FilmRecommendation;
use Modules\Entertainment\Models\MusicRelease;

class EntertainmentViewModel
{
    public function state(): array
    {
        return [
            'films' => FilmRecommendationResource::collection(FilmRecommendation::query()->where('dismissed', false)->orderByDesc('score')->orderBy('title')->get())->resolve(),
            'concerts' => $this->concerts(),
            'music' => MusicReleaseResource::collection(MusicRelease::query()->orderByDesc('release_date')->get())->resolve(),
            // Real Spotify link status (token present), independent of whether any
            // releases have been fetched yet — the music list is empty until a
            // refresh runs, which must not read as "not linked".
            'spotifyConnected' => Cache::has('spotify_refresh_token'),
        ];
    }

    public function concerts(): array
    {
        return ConcertResource::collection(Concert::query()->orderBy('date')->get())->resolve();
    }
}
