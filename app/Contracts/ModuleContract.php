<?php

namespace App\Contracts;

interface ModuleContract
{
    public function getModuleName(): string;

    public function getModuleSlug(): string;

    public function getNavigation(): array;

    public function getDashboardWidget(): ?string;
}
