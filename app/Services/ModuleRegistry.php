<?php

namespace App\Services;

use App\Contracts\ModuleContract;
use App\Contracts\ReportsHealth;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Collection;

class ModuleRegistry
{
    protected array $modules = [];

    public function register(ModuleContract $module): void
    {
        $this->modules[$module->getModuleSlug()] = $module;
    }

    public function getModules(): Collection
    {
        return collect($this->modules);
    }

    public function getNavigation(): array
    {
        $nav = [];
        foreach ($this->modules as $module) {
            foreach ($module->getNavigation() as $item) {
                $nav[] = $item;
            }
        }
        return $nav;
    }

    /**
     * Readiness of a single module by slug, or null when the module is unknown
     * or does not report health.
     */
    public function health(string $slug): ?ModuleHealth
    {
        $module = $this->modules[$slug] ?? null;

        return $module instanceof ReportsHealth ? $module->health() : null;
    }
}
