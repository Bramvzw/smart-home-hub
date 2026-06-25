<?php

namespace Modules\Printer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilamentSpoolResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material' => $this->material,
            'color_name' => $this->color_name,
            'color_hex' => $this->color_hex,
            'brand' => $this->brand,
            'diameter_mm' => (float) $this->diameter_mm,
            'total_weight_g' => (int) $this->total_weight_g,
            'remaining_g' => (int) $this->remaining_g,
            'remaining_pct' => $this->remaining_pct,
            'is_low' => $this->is_low,
            'purchase' => [
                'price' => $this->purchase_price !== null ? (float) $this->purchase_price : null,
                'store' => $this->purchase_store,
                'purchased_at' => $this->purchased_at?->toDateString(),
            ],
        ];
    }
}
