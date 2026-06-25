<?php

namespace Modules\Printer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Printer\Models\PrinterPart;
use Tests\TestCase;

class PrinterPartControllerTest extends TestCase
{
    use RefreshDatabase;

    private function part(array $overrides = []): PrinterPart
    {
        return PrinterPart::query()->create(array_merge([
            'category' => 'spare',
            'name' => '0.4mm nozzle',
            'quantity' => 3,
            'unit' => 'stuks',
        ], $overrides));
    }

    public function test_can_create_a_spare_part(): void
    {
        $this->postJson(route('printer.parts.store'), [
            'category' => 'spare',
            'name' => '0.4mm nozzle',
            'quantity' => 3,
            'unit' => 'stuks',
        ])->assertCreated()
            ->assertJsonPath('category', 'spare')
            ->assertJsonPath('quantity', 3)
            ->assertJsonPath('unit', 'stuks');

        $this->assertDatabaseHas('printer_parts', ['name' => '0.4mm nozzle']);
    }

    public function test_create_rejects_unknown_category(): void
    {
        $this->postJson(route('printer.parts.store'), [
            'category' => 'gadget',
            'name' => 'Mystery',
            'quantity' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['category']);
    }

    public function test_create_rejects_negative_quantity(): void
    {
        $this->postJson(route('printer.parts.store'), [
            'category' => 'consumable',
            'name' => 'IPA',
            'quantity' => -5,
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    public function test_can_update_a_part(): void
    {
        $part = $this->part();

        $this->patchJson(route('printer.parts.update', $part), ['name' => '0.6mm nozzle', 'quantity' => 5])
            ->assertOk()
            ->assertJsonPath('name', '0.6mm nozzle')
            ->assertJsonPath('quantity', 5);
    }

    public function test_can_delete_a_part(): void
    {
        $part = $this->part();

        $this->deleteJson(route('printer.parts.destroy', $part))->assertNoContent();

        $this->assertDatabaseMissing('printer_parts', ['id' => $part->id]);
    }

    public function test_adjust_changes_quantity(): void
    {
        $part = $this->part(['quantity' => 3]);

        $this->postJson(route('printer.parts.adjust', $part), ['delta' => -1])
            ->assertOk()
            ->assertJsonPath('quantity', 2);
    }

    public function test_adjust_clamps_quantity_to_zero(): void
    {
        $part = $this->part(['quantity' => 1]);

        $this->postJson(route('printer.parts.adjust', $part), ['delta' => -5])
            ->assertOk()
            ->assertJsonPath('quantity', 0);
    }

    public function test_adjust_supports_fractional_consumable(): void
    {
        $part = $this->part(['category' => 'consumable', 'name' => 'Isopropanol', 'quantity' => 500, 'unit' => 'ml']);

        $this->postJson(route('printer.parts.adjust', $part), ['delta' => -50.5])
            ->assertOk()
            ->assertJsonPath('quantity', 449.5);
    }

    public function test_can_store_a_part_with_low_threshold(): void
    {
        $this->postJson(route('printer.parts.store'), [
            'category' => 'consumable',
            'name' => 'Isopropanol',
            'quantity' => 100,
            'unit' => 'ml',
            'low_threshold' => 150,
        ])->assertCreated()
            ->assertJsonPath('low_threshold', 150)
            ->assertJsonPath('is_low', true);

        $this->assertDatabaseHas('printer_parts', ['name' => 'Isopropanol', 'low_threshold' => 150]);
    }

    public function test_can_update_the_low_threshold(): void
    {
        $part = $this->part(['quantity' => 5, 'low_threshold' => null]);

        $this->patchJson(route('printer.parts.update', $part), ['low_threshold' => 3])
            ->assertOk()
            ->assertJsonPath('low_threshold', 3)
            ->assertJsonPath('is_low', false);
    }

    public function test_is_low_is_true_when_quantity_at_or_below_threshold(): void
    {
        $part = $this->part(['quantity' => 2, 'low_threshold' => 2]);

        $this->assertTrue($part->fresh()->is_low);
    }

    public function test_is_low_is_false_without_a_threshold(): void
    {
        $part = $this->part(['quantity' => 0, 'low_threshold' => null]);

        $this->assertFalse($part->fresh()->is_low);
    }
}
