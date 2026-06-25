<?php

namespace Modules\Printer\Actions\Parts;

use Modules\Printer\Models\PrinterPart;

class CreatePart
{
    public function __invoke(array $attributes): PrinterPart
    {
        $attributes['quantity'] = max(0, (float) ($attributes['quantity'] ?? 0));

        return PrinterPart::query()->create($attributes);
    }
}
