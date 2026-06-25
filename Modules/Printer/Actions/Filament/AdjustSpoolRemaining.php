<?php

namespace Modules\Printer\Actions\Filament;

use Modules\Printer\Models\FilamentSpool;

class AdjustSpoolRemaining
{
    public function __invoke(FilamentSpool $spool, int $deltaG): FilamentSpool
    {
        $total = (int) $spool->total_weight_g;
        $remaining = (int) $spool->remaining_g + $deltaG;

        $spool->remaining_g = max(0, min($remaining, $total));
        $spool->save();

        return $spool->refresh();
    }
}
