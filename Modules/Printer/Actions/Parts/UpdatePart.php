<?php

namespace Modules\Printer\Actions\Parts;

use Modules\Printer\Models\PrinterPart;

class UpdatePart
{
    public function __invoke(PrinterPart $part, array $attributes): PrinterPart
    {
        $part->fill($attributes);
        $part->quantity = max(0, (float) $part->quantity);
        $part->save();

        return $part->refresh();
    }
}
