<?php

namespace App\Http\Controllers;

use App\Contracts\ProvidesSettings;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\ModuleRegistry;
use Illuminate\Http\RedirectResponse;

class SettingsController
{
    public function index(ModuleRegistry $registry): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $panes = $registry->getModules()
            ->filter(fn ($module): bool => $module instanceof ProvidesSettings)
            ->map(fn ($module): array => [
                'slug' => $module->getModuleSlug(),
                'name' => $module->getModuleName(),
                'fields' => $module->settingsSchema(),
            ])
            ->values();

        return view('settings.index', [
            'panes' => $panes,
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $module): RedirectResponse
    {
        $target = $request->module();

        abort_if($target === null, 404);

        $target->saveSettings($request->validated());

        return back()->with('settings_status', 'Settings saved.');
    }
}
