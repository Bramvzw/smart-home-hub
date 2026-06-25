<?php

namespace Modules\Printer\Providers;

use App\Providers\ModuleServiceProvider;

class PrinterServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Printer';

    protected string $nameLower = 'printer';

    public function getModuleName(): string
    {
        return '3D-printer';
    }

    public function getModuleSlug(): string
    {
        return 'printer';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => '3D-printer', 'route' => 'printer.index', 'icon' => 'printer'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return 'Filament & onderdelen voorraad.';
    }
}
