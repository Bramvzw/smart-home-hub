<?php

namespace Modules\Entertainment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConcertResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'external_id' => $this->external_id,
            'artist' => $this->artist,
            'title' => $this->title,
            'venue' => $this->venue,
            'city' => $this->city,
            'date' => $this->date?->toIso8601String(),
            'url' => $this->url,
            'relevance' => $this->relevance,
            'notified' => $this->notified,
        ];
    }
}
