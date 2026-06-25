<?php

namespace Modules\Printer\Actions\Filament;

use Modules\Printer\Models\FilamentSpool;

class CreateSpool
{
    public function __invoke(array $attributes): FilamentSpool
    {
        $total = (int) ($attributes['total_weight_g'] ?? config('printer.default_spool_weight_g', 1000));
        $remaining = (int) ($attributes['remaining_g'] ?? $total);

        $attributes['total_weight_g'] = $total;
        $attributes['remaining_g'] = max(0, min($remaining, $total));

        return FilamentSpool::query()->create($attributes);
    }
}
