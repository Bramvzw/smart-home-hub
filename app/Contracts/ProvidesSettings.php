<?php

namespace App\Contracts;

use App\Data\SettingField;

/**
 * Implemented by module service providers that expose UI-editable settings on
 * the central /settings page. Extends ModuleContract so the pane title/slug
 * come from getModuleName()/getModuleSlug() — enforced, not assumed.
 */
interface ProvidesSettings extends ModuleContract
{
    /**
     * The fields shown for this module, each carrying its current value,
     * default and validation rules.
     *
     * @return list<SettingField>
     */
    public function settingsSchema(): array;

    /**
     * Persist validated field values (keyed by SettingField::$key) to the store.
     *
     * @param  array<string, mixed>  $values
     */
    public function saveSettings(array $values): void;
}
