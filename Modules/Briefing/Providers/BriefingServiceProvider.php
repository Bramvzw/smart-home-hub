<?php

namespace Modules\Briefing\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Schema;
use Modules\Briefing\Contracts\BriefingTextGenerator;
use Modules\Briefing\Models\Briefing;
use Modules\Briefing\Services\PrismBriefingTextGenerator;

class BriefingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Briefing';

    protected string $nameLower = 'briefing';

    public function register(): void
    {
        parent::register();

        $this->app->bind(BriefingTextGenerator::class, PrismBriefingTextGenerator::class);
    }

    public function getModuleName(): string
    {
        return 'Briefing';
    }

    public function getModuleSlug(): string
    {
        return 'briefing';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Briefing', 'route' => 'briefing.index', 'icon' => 'briefing'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('briefings')) {
            return null;
        }

        $today = now((string) config('briefing.timezone', 'Europe/Amsterdam'))->toDateString();
        $briefing = Briefing::query()->where('date', $today)->first();

        if ($briefing === null) {
            return 'No briefing today';
        }

        return $briefing->is_fallback ? 'Fallback briefing ready' : 'Today briefing ready';
    }
}
