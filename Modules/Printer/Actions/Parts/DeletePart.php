<?php

namespace Modules\Printer\Actions\Parts;

use Modules\Printer\Models\PrinterPart;

class DeletePart
{
    public function __invoke(PrinterPart $part): void
    {
        $part->delete();
    }
}
