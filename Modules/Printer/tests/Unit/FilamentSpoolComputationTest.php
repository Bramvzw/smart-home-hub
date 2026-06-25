<?php

namespace Modules\Printer\Tests\Unit;

use Modules\Printer\Models\FilamentSpool;
use Tests\TestCase;

class FilamentSpoolComputationTest extends TestCase
{
    private function spool(int $total, int $remaining): FilamentSpool
    {
        $spool = new FilamentSpool();
        $spool->total_weight_g = $total;
        $spool->remaining_g = $remaining;

        return $spool;
    }

    public function test_remaining_pct_is_zero_when_empty(): void
    {
        $this->assertSame(0, $this->spool(1000, 0)->remaining_pct);
    }

    public function test_remaining_pct_is_hundred_when_full(): void
    {
        $this->assertSame(100, $this->spool(1000, 1000)->remaining_pct);
    }

    public function test_remaining_pct_rounds_to_nearest_integer(): void
    {
        // 327 / 1000 = 32.7 -> 33
        $this->assertSame(33, $this->spool(1000, 327)->remaining_pct);
        // 324 / 1000 = 32.4 -> 32
        $this->assertSame(32, $this->spool(1000, 324)->remaining_pct);
    }

    public function test_remaining_pct_is_zero_when_total_is_zero(): void
    {
        $this->assertSame(0, $this->spool(0, 0)->remaining_pct);
    }

    public function test_is_low_uses_configured_threshold(): void
    {
        config()->set('printer.low_filament_pct', 15);

        $this->assertTrue($this->spool(1000, 150)->is_low); // 15% == threshold -> low
        $this->assertTrue($this->spool(1000, 100)->is_low); // 10% -> low
        $this->assertFalse($this->spool(1000, 200)->is_low); // 20% -> not low
    }
}
