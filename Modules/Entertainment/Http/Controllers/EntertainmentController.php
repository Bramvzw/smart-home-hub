<?php

namespace Modules\Entertainment\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Actions\DismissFilm;
use Modules\Entertainment\Actions\NotifyEntertainment;
use Modules\Entertainment\Actions\RecordFilmFeedback;
use Modules\Entertainment\Actions\RefreshConcerts;
use Modules\Entertainment\Actions\RefreshFilms;
use Modules\Entertainment\Actions\RefreshMusicReleases;
use Modules\Entertainment\Actions\UpdateTasteProfile;
use Modules\Entertainment\Http\Resources\ConcertResource;
use Modules\Entertainment\Http\Resources\TasteProfileResource;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\FilmRecommendation;
use Modules\Entertainment\Models\TasteProfile;
use Modules\Entertainment\View\ViewModels\EntertainmentViewModel;

class EntertainmentController
{
    public function __construct(private readonly EntertainmentViewModel $viewModel) {}

    public function index(Request $request): View|JsonResponse
    {
        $state = $this->viewModel->state();

        if ($request->expectsJson()) {
            return response()->json($state);
        }

        return view('entertainment::index', $state);
    }

    public function concerts(): JsonResponse
    {
        return response()->json(['concerts' => ConcertResource::collection(Concert::query()->orderBy('date')->get())->resolve()]);
    }

    public function feedback(Request $request, FilmRecommendation $film, RecordFilmFeedback $record): JsonResponse
    {
        $data = $request->validate(['sentiment' => 'required|in:up,down']);

        return response()->json(['feedback' => $record($film, $data['sentiment'])]);
    }

    public function dismiss(FilmRecommendation $film, DismissFilm $dismiss): JsonResponse
    {
        return response()->json(['film' => $dismiss($film)]);
    }

    public function taste(): JsonResponse
    {
        return response()->json(TasteProfileResource::make(TasteProfile::query()->firstOrCreate([], ['favorite_titles' => [], 'genres' => []]))->resolve());
    }

    public function updateTaste(Request $request, UpdateTasteProfile $update): JsonResponse
    {
        $data = $request->validate([
            'favorite_titles' => 'sometimes|array',
            'favorite_titles.*' => 'string|max:160',
            'genres' => 'sometimes|array',
            'genres.*' => 'string|max:80',
            'notes' => 'nullable|string|max:4000',
        ]);

        return response()->json(TasteProfileResource::make($update($data))->resolve());
    }

    public function refresh(
        RefreshFilms $films,
        RefreshConcerts $concerts,
        RefreshMusicReleases $music,
        NotifyEntertainment $notify,
    ): JsonResponse {
        return response()->json([
            'films' => $films(),
            'concerts' => $concerts(),
            'music' => $music(),
            'notified' => $notify(),
            'state' => $this->viewModel->state(),
        ]);
    }
}
