<?php

namespace Modules\Printer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Printer\Models\FilamentSpool;
use Tests\TestCase;

class FilamentSpoolControllerTest extends TestCase
{
    use RefreshDatabase;

    private function spool(array $overrides = []): FilamentSpool
    {
        return FilamentSpool::query()->create(array_merge([
            'material' => 'PLA',
            'color_name' => 'Galaxy Black',
            'color_hex' => '#1b1b22',
            'brand' => 'Bambu Lab',
            'diameter_mm' => 1.75,
            'total_weight_g' => 1000,
            'remaining_g' => 320,
        ], $overrides));
    }

    public function test_can_create_a_spool(): void
    {
        $response = $this->postJson(route('printer.filament.store'), [
            'material' => 'PETG',
            'color_name' => 'Jade White',
            'total_weight_g' => 1000,
            'remaining_g' => 1000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('material', 'PETG')
            ->assertJsonPath('remaining_pct', 100)
            ->assertJsonPath('is_low', false);

        $this->assertDatabaseHas('filament_spools', ['material' => 'PETG', 'color_name' => 'Jade White']);
    }

    public function test_create_clamps_remaining_to_total(): void
    {
        $response = $this->postJson(route('printer.filament.store'), [
            'material' => 'PLA',
            'color_name' => 'Black',
            'total_weight_g' => 1000,
            'remaining_g' => 5000,
        ]);

        $response->assertCreated()->assertJsonPath('remaining_g', 1000);
    }

    public function test_create_rejects_invalid_payload(): void
    {
        $this->postJson(route('printer.filament.store'), [
            'color_name' => 'Black',
            'total_weight_g' => -10,
        ])->assertStatus(422)->assertJsonValidationErrors(['material', 'total_weight_g']);
    }

    public function test_can_update_a_spool(): void
    {
        $spool = $this->spool();

        $this->patchJson(route('printer.filament.update', $spool), [
            'color_name' => 'Cosmic Grey',
            'remaining_g' => 500,
        ])->assertOk()
            ->assertJsonPath('color_name', 'Cosmic Grey')
            ->assertJsonPath('remaining_g', 500)
            ->assertJsonPath('remaining_pct', 50);
    }

    public function test_can_delete_a_spool(): void
    {
        $spool = $this->spool();

        $this->deleteJson(route('printer.filament.destroy', $spool))->assertNoContent();

        $this->assertDatabaseMissing('filament_spools', ['id' => $spool->id]);
    }

    public function test_adjust_consumes_filament(): void
    {
        $spool = $this->spool(['remaining_g' => 500]);

        $this->postJson(route('printer.filament.adjust', $spool), ['delta_g' => -150])
            ->assertOk()
            ->assertJsonPath('remaining_g', 350);
    }

    public function test_adjust_clamps_to_zero_when_consuming_below_zero(): void
    {
        $spool = $this->spool(['remaining_g' => 100]);

        $this->postJson(route('printer.filament.adjust', $spool), ['delta_g' => -500])
            ->assertOk()
            ->assertJsonPath('remaining_g', 0)
            ->assertJsonPath('remaining_pct', 0);
    }

    public function test_adjust_clamps_to_total_when_refilling_above_total(): void
    {
        $spool = $this->spool(['total_weight_g' => 1000, 'remaining_g' => 800]);

        $this->postJson(route('printer.filament.adjust', $spool), ['delta_g' => 5000])
            ->assertOk()
            ->assertJsonPath('remaining_g', 1000)
            ->assertJsonPath('remaining_pct', 100);
    }

    public function test_adjust_rejects_non_integer_delta(): void
    {
        $spool = $this->spool();

        $this->postJson(route('printer.filament.adjust', $spool), ['delta_g' => 'lots'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delta_g']);
    }

    public function test_is_low_reflects_threshold(): void
    {
        config()->set('printer.low_filament_pct', 15);
        $spool = $this->spool(['total_weight_g' => 1000, 'remaining_g' => 100]);

        $this->patchJson(route('printer.filament.update', $spool), ['remaining_g' => 100])
            ->assertOk()
            ->assertJsonPath('is_low', true);
    }
}
