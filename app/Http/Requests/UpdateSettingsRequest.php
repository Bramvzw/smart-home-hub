<?php

namespace App\Http\Requests;

use App\Contracts\ProvidesSettings;
use App\Services\ModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Build validation rules from the target module's settings schema.
     *
     * @return array<string, array<int|string, string>|string>
     */
    public function rules(): array
    {
        $module = $this->module();

        if ($module === null) {
            return [];
        }

        $rules = [];

        foreach ($module->settingsSchema() as $field) {
            $rules[$field->key] = $field->rules;
        }

        return $rules;
    }

    /**
     * The settings-providing module targeted by the route, or null when unknown.
     */
    public function module(): ?ProvidesSettings
    {
        $slug = (string) $this->route('module');

        $module = app(ModuleRegistry::class)->getModules()->get($slug);

        return $module instanceof ProvidesSettings ? $module : null;
    }
}
