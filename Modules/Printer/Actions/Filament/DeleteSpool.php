<?php

namespace Modules\Printer\Actions\Filament;

use Modules\Printer\Models\FilamentSpool;

class DeleteSpool
{
    public function __invoke(FilamentSpool $spool): void
    {
        $spool->delete();
    }
}
