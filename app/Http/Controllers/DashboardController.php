<?php

namespace App\Http\Controllers;

use App\Services\ModuleRegistry;
use Modules\Briefing\View\ViewModels\BriefingViewModel;

class DashboardController
{
    public function index(ModuleRegistry $registry)
    {
        return view('dashboard', [
            'modules' => $registry->getModules(),
            'briefing' => $this->briefing($registry),
        ]);
    }

    /**
     * Resolve today's briefing for the dashboard hero, when the Briefing module
     * is enabled. Returns null otherwise so the dashboard degrades gracefully.
     */
    private function briefing(ModuleRegistry $registry): ?array
    {
        if (! $registry->getModules()->has('briefing') || ! class_exists(BriefingViewModel::class)) {
            return null;
        }

        try {
            return app(BriefingViewModel::class)->today();
        } catch (\Throwable) {
            // Briefing data is optional on the dashboard — never let it 500 the page.
            return null;
        }
    }
}
