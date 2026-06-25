<?php

namespace Modules\Entertainment\View\ViewModels;

use Modules\Entertainment\Http\Resources\ConcertResource;
use Modules\Entertainment\Http\Resources\FilmRecommendationResource;
use Modules\Entertainment\Http\Resources\MusicReleaseResource;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\FilmRecommendation;
use Modules\Entertainment\Models\MusicRelease;

class EntertainmentViewModel
{
    public function state(): array
    {
        return [
            'films' => FilmRecommendationResource::collection(FilmRecommendation::query()->where('dismissed', false)->orderByDesc('score')->orderBy('title')->get())->resolve(),
            'concerts' => ConcertResource::collection(Concert::query()->orderBy('date')->get())->resolve(),
            'music' => MusicReleaseResource::collection(MusicRelease::query()->orderByDesc('release_date')->get())->resolve(),
        ];
    }
}
