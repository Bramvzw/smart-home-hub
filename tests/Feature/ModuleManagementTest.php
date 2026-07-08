<?php

namespace Tests\Feature;

use App\Services\ModuleRegistry;
use App\Services\ModuleState;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_lists_every_module_in_the_modules_pane(): void
    {
        $response = $this->get(route('settings.index'))
            ->assertStatus(200)
            ->assertSee('Modules');

        foreach (app(ModuleRegistry::class)->allModules() as $module) {
            $response->assertSee($module->getModuleName());
        }
    }

    public function test_update_persists_state_and_hides_the_module(): void
    {
        $payload = $this->payloadForAllModules();
        $payload['weather']['enabled'] = '0';

        $this->from(route('settings.index'))
            ->put(route('settings.modules.update'), ['modules' => $payload])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('settings_status');

        $this->assertDatabaseHas('settings', ['key' => 'modules.weather.enabled']);
        $this->assertFalse(app(ModuleState::class)->isEnabled('weather'));

        $registry = app(ModuleRegistry::class);
        $this->assertFalse($registry->getModules()->has('weather'));
        $this->assertTrue($registry->allModules()->has('weather'));

        $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertDontSee('Weather');
    }

    public function test_order_state_reorders_the_navigation(): void
    {
        app(ModuleState::class)->update('weather', true, 99);

        $labels = array_column(app(ModuleRegistry::class)->getNavigation(), 'label');

        $this->assertSame('Briefing', $labels[0]);
        $this->assertSame('Weather', end($labels));
    }

    public function test_disabled_module_scheduled_jobs_are_skipped(): void
    {
        $rainChecks = collect(app(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'weather:check-rain'));

        $this->assertNotEmpty($rainChecks);
        foreach ($rainChecks as $event) {
            $this->assertTrue($event->filtersPass($this->app));
        }

        app(ModuleState::class)->update('weather', false, 1);

        foreach ($rainChecks as $event) {
            $this->assertFalse($event->filtersPass($this->app));
        }
    }

    public function test_update_rejects_out_of_range_order(): void
    {
        $payload = $this->payloadForAllModules();
        $payload['weather']['order'] = 999;

        $this->from(route('settings.index'))
            ->put(route('settings.modules.update'), ['modules' => $payload])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('modules.weather.order');

        $this->assertDatabaseMissing('settings', ['key' => 'modules.weather.order']);
    }

    public function test_update_ignores_unknown_modules(): void
    {
        $payload = $this->payloadForAllModules();
        $payload['bogus'] = ['enabled' => '1', 'order' => 0];

        $this->put(route('settings.modules.update'), ['modules' => $payload])
            ->assertRedirect();

        $this->assertDatabaseMissing('settings', ['key' => 'modules.bogus.enabled']);
    }

    /**
     * @return array<string, array{enabled: string, order: int}>
     */
    private function payloadForAllModules(): array
    {
        return app(ModuleRegistry::class)->allModules()->keys()
            ->mapWithKeys(fn (string $slug, int $index): array => [$slug => ['enabled' => '1', 'order' => $index]])
            ->all();
    }
}
