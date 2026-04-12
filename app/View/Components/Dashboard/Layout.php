<?php

namespace App\View\Components\Dashboard;

use App\Services\ModuleRegistry;
use Illuminate\View\Component;

class Layout extends Component
{
    public array $navigation;
    public string $title;

    public function __construct(string $title = 'Smart Home Hub')
    {
        $this->title = $title;
        $this->navigation = app(ModuleRegistry::class)->getNavigation();
    }

    public function render()
    {
        return view('components.dashboard.layout');
    }
}
