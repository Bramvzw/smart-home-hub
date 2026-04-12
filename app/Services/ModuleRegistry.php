<?php

namespace App\Services;

use App\Contracts\ModuleContract;
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
}
