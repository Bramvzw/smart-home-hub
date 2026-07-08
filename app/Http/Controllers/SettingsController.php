<?php

namespace App\Http\Controllers;

use App\Contracts\ProvidesSettings;
use App\Http\Requests\UpdateModuleStatesRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\ModuleRegistry;
use App\Services\ModuleState;
use Illuminate\Http\RedirectResponse;

class SettingsController
{
    public function index(ModuleRegistry $registry, ModuleState $state): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $panes = $registry->getModules()
            ->filter(fn ($module): bool => $module instanceof ProvidesSettings)
            ->map(fn ($module): array => [
                'slug' => $module->getModuleSlug(),
                'name' => $module->getModuleName(),
                'fields' => $module->settingsSchema(),
            ])
            ->values();

        $moduleStates = $registry->allModules()
            ->map(fn ($module): array => [
                'slug' => $module->getModuleSlug(),
                'name' => $module->getModuleName(),
                'enabled' => $state->isEnabled($module->getModuleSlug()),
            ])
            ->values();

        return view('settings.index', [
            'panes' => $panes,
            'moduleStates' => $moduleStates,
        ]);
    }

    public function updateModules(UpdateModuleStatesRequest $request, ModuleRegistry $registry, ModuleState $state): RedirectResponse
    {
        $known = $registry->allModules()->keys()->all();

        foreach ($request->validated('modules') as $slug => $values) {
            if (! in_array($slug, $known, true)) {
                continue;
            }

            $state->update($slug, (bool) $values['enabled'], (int) $values['order']);
        }

        return back()->with('settings_status', 'Module settings saved.');
    }

    public function update(UpdateSettingsRequest $request, string $module): RedirectResponse
    {
        $target = $request->module();

        abort_if($target === null, 404);

        $target->saveSettings($request->validated());

        return back()->with('settings_status', 'Settings saved.');
    }
}
