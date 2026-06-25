<?php

namespace Modules\Entertainment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MusicReleaseResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'spotify_id' => $this->spotify_id,
            'artist' => $this->artist,
            'title' => $this->title,
            'type' => $this->type,
            'release_date' => $this->release_date?->toDateString(),
            'url' => $this->url,
            'image_url' => $this->image_url,
            'notified' => $this->notified,
        ];
    }
}
