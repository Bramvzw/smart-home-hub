<?php

namespace Modules\Printer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Printer\Models\FilamentSpool;
use Modules\Printer\Models\PrinterPart;
use Tests\TestCase;

class PrinterIndexContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_empty_inventories(): void
    {
        $this->getJson(route('printer.index'))
            ->assertOk()
            ->assertExactJson(['filament' => [], 'parts' => []]);
    }

    public function test_index_renders_the_html_page(): void
    {
        $this->withoutVite();

        FilamentSpool::query()->create([
            'material' => 'PLA',
            'color_name' => 'Galaxy Black',
            'total_weight_g' => 1000,
            'remaining_g' => 320,
        ]);

        PrinterPart::query()->create([
            'category' => 'spare',
            'name' => '0.4mm nozzle',
            'quantity' => 3,
            'unit' => 'stuks',
        ]);

        $this->get(route('printer.index'))
            ->assertOk()
            ->assertSee('Filament')
            ->assertSee('3D-printer voorraad')
            ->assertSee('Galaxy Black')
            ->assertSee('0.4mm nozzle');
    }

    public function test_index_returns_the_documented_contract(): void
    {
        config()->set('printer.low_filament_pct', 15);

        FilamentSpool::query()->create([
            'material' => 'PLA',
            'color_name' => 'Galaxy Black',
            'color_hex' => '#1b1b22',
            'brand' => 'Bambu Lab',
            'diameter_mm' => 1.75,
            'total_weight_g' => 1000,
            'remaining_g' => 320,
            'purchase_price' => 24.99,
            'purchase_store' => 'bol.com',
            'purchased_at' => '2026-05-02',
        ]);

        PrinterPart::query()->create([
            'category' => 'spare',
            'name' => '0.4mm nozzle',
            'quantity' => 3,
            'unit' => 'stuks',
        ]);

        PrinterPart::query()->create([
            'category' => 'consumable',
            'name' => 'Isopropanol',
            'quantity' => 500,
            'unit' => 'ml',
        ]);

        $response = $this->getJson(route('printer.index'))->assertOk();

        $response->assertJsonPath('filament.0.material', 'PLA')
            ->assertJsonPath('filament.0.color_name', 'Galaxy Black')
            ->assertJsonPath('filament.0.color_hex', '#1b1b22')
            ->assertJsonPath('filament.0.brand', 'Bambu Lab')
            ->assertJsonPath('filament.0.diameter_mm', 1.75)
            ->assertJsonPath('filament.0.total_weight_g', 1000)
            ->assertJsonPath('filament.0.remaining_g', 320)
            ->assertJsonPath('filament.0.remaining_pct', 32)
            ->assertJsonPath('filament.0.is_low', false)
            ->assertJsonPath('filament.0.purchase.price', 24.99)
            ->assertJsonPath('filament.0.purchase.store', 'bol.com')
            ->assertJsonPath('filament.0.purchase.purchased_at', '2026-05-02');

        // ViewModel sorts parts by category, so "consumable" precedes "spare".
        $response->assertJsonPath('parts.0.category', 'consumable')
            ->assertJsonPath('parts.0.name', 'Isopropanol')
            ->assertJsonPath('parts.0.quantity', 500)
            ->assertJsonPath('parts.0.unit', 'ml')
            ->assertJsonPath('parts.1.category', 'spare')
            ->assertJsonPath('parts.1.name', '0.4mm nozzle')
            ->assertJsonPath('parts.1.quantity', 3)
            ->assertJsonPath('parts.1.unit', 'stuks');

        $response->assertJsonStructure([
            'filament' => [
                ['id', 'material', 'color_name', 'color_hex', 'brand', 'diameter_mm', 'total_weight_g', 'remaining_g', 'remaining_pct', 'is_low', 'purchase' => ['price', 'store', 'purchased_at']],
            ],
            'parts' => [
                ['id', 'category', 'name', 'quantity', 'unit'],
            ],
        ]);
    }
}
