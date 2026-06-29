<?php

namespace Tests\Unit;

use App\Enums\ModuleHealthStatus;
use App\Support\Health\ModuleHealth;
use PHPUnit\Framework\TestCase;

class ModuleHealthTest extends TestCase
{
    public function test_require_is_ok_when_every_value_is_present(): void
    {
        $health = ModuleHealth::require(['FOO' => 'bar', 'BAZ' => 'qux']);

        $this->assertTrue($health->isOk());
        $this->assertSame(ModuleHealthStatus::Ok, $health->status);
        $this->assertSame([], $health->issues);
    }

    public function test_require_reports_blank_and_null_values_as_missing(): void
    {
        $health = ModuleHealth::require(['FOO' => '', 'BAR' => null, 'KEEP' => 'x']);

        $this->assertSame(ModuleHealthStatus::NeedsSetup, $health->status);
        $this->assertSame([
            'Ontbrekende .env: FOO',
            'Ontbrekende .env: BAR',
        ], $health->issues);
    }

    public function test_whitespace_only_value_counts_as_missing(): void
    {
        $this->assertSame(
            ModuleHealthStatus::NeedsSetup,
            ModuleHealth::require(['FOO' => '   '])->status,
        );
    }

    public function test_require_appends_extra_coupling_issues(): void
    {
        $health = ModuleHealth::require(['KEEP' => 'x'], ['Niet gekoppeld']);

        $this->assertSame(ModuleHealthStatus::NeedsSetup, $health->status);
        $this->assertSame(['Niet gekoppeld'], $health->issues);
    }

    public function test_factories_set_the_expected_status(): void
    {
        $this->assertTrue(ModuleHealth::ok()->isOk());
        $this->assertSame(ModuleHealthStatus::NeedsSetup, ModuleHealth::needsSetup(['x'])->status);
        $this->assertSame(ModuleHealthStatus::Degraded, ModuleHealth::degraded(['x'])->status);
        $this->assertFalse(ModuleHealth::degraded(['x'])->isOk());
    }
}
