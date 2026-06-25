<?php

namespace Modules\Printer\View\ViewModels;

use Illuminate\Database\Eloquent\Collection;
use Modules\Printer\Models\FilamentSpool;
use Modules\Printer\Models\PrinterPart;

class PrintingViewModel
{
    /**
     * @return Collection<int, FilamentSpool>
     */
    public function filament(): Collection
    {
        return FilamentSpool::query()->orderBy('material')->orderBy('color_name')->get();
    }

    /**
     * @return Collection<int, PrinterPart>
     */
    public function parts(): Collection
    {
        return PrinterPart::query()->orderBy('category')->orderBy('name')->get();
    }
}
