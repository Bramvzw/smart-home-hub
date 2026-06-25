<?php

namespace Modules\Tasks\Providers;

use App\Providers\ModuleServiceProvider;
use Modules\Tasks\Briefing\TasksBriefingSource;

class TasksServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tasks';
    protected string $nameLower = 'tasks';

    public function register(): void
    {
        parent::register();

        $this->app->tag([TasksBriefingSource::class], 'briefing.source');
    }

    public function getModuleName(): string
    {
        return 'Tasks';
    }

    public function getModuleSlug(): string
    {
        return 'tasks';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Tasks', 'route' => 'tasks.index', 'icon' => 'clipboard-list'],
            ['label' => 'Gewoontes', 'route' => 'tasks.habits.index', 'icon' => 'habits'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
