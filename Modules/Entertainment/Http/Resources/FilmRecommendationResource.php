<?php

namespace Modules\Entertainment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilmRecommendationResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'overview' => $this->overview,
            'availability' => $this->availability ?? [],
            'poster_url' => $this->poster_url,
            'why' => $this->why,
            'score' => $this->score,
            'dismissed' => $this->dismissed,
            'refreshed_at' => $this->refreshed_at?->toIso8601String(),
        ];
    }
}
