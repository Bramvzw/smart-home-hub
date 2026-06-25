<?php

namespace Modules\Printer\Actions\Parts;

use Modules\Printer\Models\PrinterPart;

class AdjustPartQuantity
{
    public function __invoke(PrinterPart $part, float $delta): PrinterPart
    {
        $part->quantity = max(0, (float) $part->quantity + $delta);
        $part->save();

        return $part->refresh();
    }
}
