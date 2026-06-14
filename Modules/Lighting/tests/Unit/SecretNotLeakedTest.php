<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Lighting\Data\Light;
use Modules\Lighting\Services\Providers\TuyaProvider;
use Tests\TestCase;
use Throwable;

class SecretNotLeakedTest extends TestCase
{
    public function test_provider_errors_never_contain_credentials(): void
    {
        config(['lighting.tuya' => ['client_id' => 'CLIENT-ID-PUBLIC', 'client_secret' => 'SUPER-SECRET-VALUE', 'region' => 'eu', 'uid' => 'u1']]);

        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => false, 'code' => 1010, 'msg' => 'token invalid']),
        ]);

        try {
            app(TuyaProvider::class)->lights();
            $this->fail('Expected a provider failure.');
        } catch (Throwable $e) {
            $this->assertStringNotContainsString('SUPER-SECRET-VALUE', $e->getMessage());
            $this->assertStringNotContainsString('SUPER-SECRET-VALUE', (string) $e);
        }
    }

    public function test_light_dto_exposes_no_credentials(): void
    {
        $light = new Light('govee', 'AA:BB', 'Strip', true, 80, '#ff0000', true, true);

        $this->assertSame(
            ['provider', 'id', 'name', 'on', 'brightness', 'color', 'reachable', 'supports_color'],
            array_keys($light->toArray()),
        );
    }
}
