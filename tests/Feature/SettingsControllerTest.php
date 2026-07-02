<?php

namespace Tests\Feature;

use App\Services\Settings\SettingsStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PhonePing\Services\NtfyClient;
use Modules\Printer\Models\FilamentSpool;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_module_panes(): void
    {
        $this->get(route('settings.index'))
            ->assertStatus(200)
            ->assertSee('Settings')
            ->assertSee('3D printer')
            ->assertSee('Phone Ping')
            ->assertSee('ntfy-topic');
    }

    public function test_printer_update_persists_and_is_used_by_the_module(): void
    {
        // A spool at 15% remaining is "low" under the default (20%) threshold.
        $spool = FilamentSpool::query()->create([
            'material' => 'PLA',
            'color_name' => 'Galaxy Black',
            'color_hex' => '#1b1b22',
            'brand' => 'Bambu Lab',
            'diameter_mm' => 1.75,
            'total_weight_g' => 1000,
            'remaining_g' => 150,
        ]);
        $this->assertTrue($spool->fresh()->is_low);

        $this->from(route('settings.index'))
            ->put(route('settings.update', 'printer'), ['low_filament_pct' => 10])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('settings_status');

        $this->assertDatabaseHas('settings', ['key' => 'printer.low_filament_pct']);
        $this->assertSame(10, (int) app(SettingsStore::class)->get('printer.low_filament_pct'));

        // 15% > 10% threshold -> no longer low, proving the stored value is used.
        $this->assertFalse($spool->fresh()->is_low);
    }

    public function test_phoneping_update_persists_and_configures_the_client(): void
    {
        $this->put(route('settings.update', 'phoneping'), ['ntfy_topic' => 'my-hub-topic'])
            ->assertRedirect();

        $this->assertSame('my-hub-topic', app(SettingsStore::class)->get('phoneping.ntfy.topic'));
        $this->assertTrue(app(NtfyClient::class)->isConfigured());
    }

    public function test_printer_update_rejects_out_of_range_value(): void
    {
        $this->from(route('settings.index'))
            ->put(route('settings.update', 'printer'), ['low_filament_pct' => 999])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('low_filament_pct');

        $this->assertDatabaseMissing('settings', ['key' => 'printer.low_filament_pct']);
    }
}
