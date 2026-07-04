<?php

namespace Modules\PhonePing\Providers;

use App\Contracts\ProvidesSettings;
use App\Data\SettingField;
use App\Providers\ModuleServiceProvider;
use App\Services\Ntfy\HubNotifier;
use App\Services\Settings\SettingsStore;
use App\Support\Health\ModuleHealth;
use Modules\PhonePing\Services\NtfyClient;

class PhonePingServiceProvider extends ModuleServiceProvider implements ProvidesSettings
{
    protected string $name = 'PhonePing';

    protected string $nameLower = 'phoneping';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NtfyClient::class, fn (): \Modules\PhonePing\Services\NtfyClient => new NtfyClient(new HubNotifier(
            url: rtrim((string) config('phoneping.ntfy.url', 'https://ntfy.sh'), '/'),
            topic: $this->topic(),
            token: (string) config('phoneping.ntfy.token', ''),
            timeout: (int) config('ntfy.timeout', 10),
        )));
    }

    public function getModuleName(): string
    {
        return 'Phone Ping';
    }

    public function getModuleSlug(): string
    {
        return 'phoneping';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Phone', 'route' => 'phoneping.index', 'icon' => 'phone'],
        ];
    }

    public function health(): ModuleHealth
    {
        return ModuleHealth::require([
            'PHONE_PING_NTFY_TOPIC' => $this->topic(),
        ]);
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }

    public function settingsSchema(): array
    {
        $default = (string) config('phoneping.ntfy.topic', '');

        return [
            new SettingField(
                key: 'ntfy_topic',
                label: 'ntfy-topic',
                type: SettingField::TYPE_STRING,
                rules: ['nullable', 'string', 'max:255'],
                value: (string) $this->settings()->get('phoneping.ntfy.topic', $default),
                default: $default,
                help: 'The ntfy topic the "where are you?" notification is sent to.',
            ),
        ];
    }

    public function saveSettings(array $values): void
    {
        if (array_key_exists('ntfy_topic', $values)) {
            $this->settings()->set('phoneping.ntfy.topic', (string) ($values['ntfy_topic'] ?? ''));
        }
    }

    /**
     * Effective ntfy topic: the UI-stored value overrides the env/config default.
     */
    private function topic(): string
    {
        return (string) $this->settings()->get(
            'phoneping.ntfy.topic',
            config('phoneping.ntfy.topic', ''),
        );
    }

    private function settings(): SettingsStore
    {
        return $this->app->make(SettingsStore::class);
    }
}
