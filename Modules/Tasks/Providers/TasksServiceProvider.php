<?php

namespace Modules\Tasks\Providers;

use App\Providers\ModuleServiceProvider;

class TasksServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tasks';
    protected string $nameLower = 'tasks';

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
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
