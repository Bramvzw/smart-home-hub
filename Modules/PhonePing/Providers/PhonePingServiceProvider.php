<?php

namespace Modules\PhonePing\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Modules\PhonePing\Services\NtfyClient;

class PhonePingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'PhonePing';
    protected string $nameLower = 'phoneping';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NtfyClient::class, fn () => new NtfyClient(
            url: rtrim((string) config('phoneping.ntfy.url', 'https://ntfy.sh'), '/'),
            topic: (string) config('phoneping.ntfy.topic', ''),
            token: (string) config('phoneping.ntfy.token', ''),
        ));
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
            'PHONE_PING_NTFY_TOPIC' => config('phoneping.ntfy.topic'),
        ]);
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
