<?php

namespace Modules\Lighting\Tests\Unit;

use Modules\Lighting\Data\Light;
use Modules\Lighting\Data\LightingSnapshot;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Domain\SceneCapture;
use Modules\Lighting\Enums\LightMode;
use Modules\Lighting\Exceptions\UnknownLight;
use PHPUnit\Framework\TestCase;

class SceneCaptureTest extends TestCase
{
    private function snapshot(): LightingSnapshot
    {
        return new LightingSnapshot([
            new Light('tuya', 'white', 'White spot', true, 70, '#ff0000', true, true, LightMode::White, 15, true),
            new Light('tuya', 'colour', 'Colour spot', true, 40, '#7F96FF', true, true, LightMode::Colour, 50, true),
            new Light('tuya', 'off', 'Off spot', false, 55, null, true, true, LightMode::White, 30, true),
            new Light('tuya', 'offline', 'Hallway', false, 0, null, false, true, LightMode::White, null, true),
            new Light('govee', 'plain', 'Plain bulb', true, 80, null, true, false, LightMode::White, 60, false),
        ]);
    }

    public function test_it_captures_each_lamp_as_it_is_now(): void
    {
        $states = (new SceneCapture)->fromSnapshot($this->snapshot(), [
            ['provider' => 'tuya', 'id' => 'white'],
            ['provider' => 'tuya', 'id' => 'colour'],
            ['provider' => 'tuya', 'id' => 'off'],
            ['provider' => 'govee', 'id' => 'plain'],
        ]);

        $this->assertEquals([
            new SceneLightState('tuya', 'white', true, 70, LightMode::White, null, 15),
            new SceneLightState('tuya', 'colour', true, 40, LightMode::Colour, '#7f96ff'),
            new SceneLightState('tuya', 'off', false, null, LightMode::White),
            // No tunable white: only power and brightness are stored.
            new SceneLightState('govee', 'plain', true, 80, LightMode::White),
        ], $states);
    }

    public function test_unreachable_and_duplicate_lamps_are_skipped(): void
    {
        $states = (new SceneCapture)->fromSnapshot($this->snapshot(), [
            ['provider' => 'tuya', 'id' => 'offline'],
            ['provider' => 'tuya', 'id' => 'white'],
            ['provider' => 'tuya', 'id' => 'white'],
        ]);

        $this->assertCount(1, $states);
        $this->assertSame('white', $states[0]->id);
    }

    public function test_an_unknown_lamp_rejects_the_capture(): void
    {
        $this->expectException(UnknownLight::class);

        (new SceneCapture)->fromSnapshot($this->snapshot(), [['provider' => 'govee', 'id' => 'missing']]);
    }

    public function test_captured_states_turn_into_change_sets_and_match_their_lamp(): void
    {
        $snapshot = $this->snapshot();
        $states = (new SceneCapture)->fromSnapshot($snapshot, [
            ['provider' => 'tuya', 'id' => 'white'],
            ['provider' => 'tuya', 'id' => 'colour'],
            ['provider' => 'tuya', 'id' => 'off'],
        ]);

        $this->assertSame(['power' => true, 'brightness' => 70, 'color_temp' => 15], $states[0]->changes());
        $this->assertSame(['power' => true, 'brightness' => 40, 'color' => '#7f96ff'], $states[1]->changes());
        $this->assertSame(['power' => false], $states[2]->changes());

        foreach ($states as $index => $state) {
            $this->assertTrue($state->matches($snapshot->lights[$index]));
        }
        $this->assertFalse($states[0]->matches($snapshot->lights[1]));
        $this->assertFalse($states[2]->matches($snapshot->lights[0]));
    }
}
