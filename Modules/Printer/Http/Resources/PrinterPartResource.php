<?php

namespace Modules\Printer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrinterPartResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'name' => $this->name,
            'quantity' => $this->normalizeQuantity((float) $this->quantity),
            'unit' => $this->unit,
            'low_threshold' => $this->low_threshold !== null ? (int) $this->low_threshold : null,
            'is_low' => $this->is_low,
        ];
    }

    private function normalizeQuantity(float $quantity): int|float
    {
        return floor($quantity) === $quantity ? (int) $quantity : $quantity;
    }
}
