<?php

namespace Modules\Entertainment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TasteProfileResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'favorite_titles' => $this->favorite_titles ?? [],
            'genres' => $this->genres ?? [],
            'notes' => $this->notes,
        ];
    }
}
