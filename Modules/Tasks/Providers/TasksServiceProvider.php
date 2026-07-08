<?php

namespace Modules\Tasks\Providers;

use App\Contracts\SchedulableGoals;
use App\Providers\ModuleServiceProvider;
use Modules\Tasks\Briefing\TasksBriefingSource;
use Modules\Tasks\Services\RecurrenceGoals;

class TasksServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tasks';

    protected string $nameLower = 'tasks';

    public function register(): void
    {
        parent::register();

        $this->app->tag([TasksBriefingSource::class], 'briefing.source');
        $this->app->bind(SchedulableGoals::class, RecurrenceGoals::class);
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
            ['label' => 'Maintenance', 'route' => 'tasks.maintenance.index', 'icon' => 'habits'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return 'Boards, labels and todos.';
    }
}
