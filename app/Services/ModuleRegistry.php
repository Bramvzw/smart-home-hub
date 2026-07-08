<?php

namespace App\Services;

use App\Contracts\ModuleContract;
use App\Contracts\ReportsHealth;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Collection;

class ModuleRegistry
{
    protected array $modules = [];

    public function __construct(private readonly ModuleState $state) {}

    public function register(ModuleContract $module): void
    {
        $this->modules[$module->getModuleSlug()] = $module;
    }

    /**
     * Enabled modules in the configured order. Everything user-facing
     * (navigation, dashboard, settings panes) derives from this.
     */
    public function getModules(): Collection
    {
        return $this->allModules()
            ->filter(fn (ModuleContract $module): bool => $this->state->isEnabled($module->getModuleSlug()));
    }

    /**
     * Every registered module in the configured order, disabled ones included.
     * Only the module-management UI should need this.
     */
    public function allModules(): Collection
    {
        $registrationOrder = array_flip(array_keys($this->modules));

        return collect($this->modules)->sortBy(
            fn (ModuleContract $module, string $slug): int => $this->state->order($slug, $registrationOrder[$slug])
        );
    }

    public function getNavigation(): array
    {
        $nav = [];
        foreach ($this->getModules() as $module) {
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
