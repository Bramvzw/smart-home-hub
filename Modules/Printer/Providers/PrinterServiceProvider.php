<?php

namespace Modules\Printer\Providers;

use App\Contracts\ProvidesSettings;
use App\Data\SettingField;
use App\Providers\ModuleServiceProvider;
use App\Services\Settings\SettingsStore;
use Modules\Printer\Briefing\PrinterBriefingSource;

class PrinterServiceProvider extends ModuleServiceProvider implements ProvidesSettings
{
    protected string $name = 'Printer';

    protected string $nameLower = 'printer';

    public function register(): void
    {
        parent::register();

        $this->app->tag([PrinterBriefingSource::class], 'briefing.source');
    }

    public function getModuleName(): string
    {
        return '3D printer';
    }

    public function getModuleSlug(): string
    {
        return 'printer';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => '3D printer', 'route' => 'printer.index', 'icon' => 'printer'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return 'Filament & parts inventory.';
    }

    public function settingsSchema(): array
    {
        $default = (int) config('printer.low_filament_pct', 20);

        return [
            new SettingField(
                key: 'low_filament_pct',
                label: 'Threshold "running low" (%)',
                type: SettingField::TYPE_NUMBER,
                rules: ['required', 'integer', 'min:5', 'max:90'],
                value: (int) $this->settings()->get('printer.low_filament_pct', $default),
                default: $default,
                help: 'Spools below this remaining percentage get a "running low" badge.',
            ),
        ];
    }

    public function saveSettings(array $values): void
    {
        if (array_key_exists('low_filament_pct', $values)) {
            $this->settings()->set('printer.low_filament_pct', (int) $values['low_filament_pct']);
        }
    }

    private function settings(): SettingsStore
    {
        return $this->app->make(SettingsStore::class);
    }
}
