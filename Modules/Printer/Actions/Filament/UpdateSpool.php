<?php

namespace Modules\Printer\Actions\Filament;

use Modules\Printer\Models\FilamentSpool;

class UpdateSpool
{
    public function __invoke(FilamentSpool $spool, array $attributes): FilamentSpool
    {
        $spool->fill($attributes);

        $total = (int) $spool->total_weight_g;
        $spool->total_weight_g = $total;
        $spool->remaining_g = max(0, min((int) $spool->remaining_g, $total));

        $spool->save();

        return $spool->refresh();
    }
}
