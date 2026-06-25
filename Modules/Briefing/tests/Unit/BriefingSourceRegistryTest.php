<?php

namespace Modules\Briefing\Tests\Unit;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Briefing\Services\BriefingSourceRegistry;
use Tests\TestCase;

class BriefingSourceRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'weather.location.latitude' => null,
            'weather.location.longitude' => null,
            'calendar.feeds' => [],
        ]);
    }

    public function test_it_collects_tagged_sources_drops_nulls_and_sorts_by_priority(): void
    {
        $this->app->instance(HighPriorityBriefingSource::class, new HighPriorityBriefingSource);
        $this->app->instance(LowPriorityBriefingSource::class, new LowPriorityBriefingSource);
        $this->app->instance(EmptyBriefingSource::class, new EmptyBriefingSource);
        $this->app->tag([
            LowPriorityBriefingSource::class,
            EmptyBriefingSource::class,
            HighPriorityBriefingSource::class,
        ], 'briefing.source');

        $sections = app(BriefingSourceRegistry::class)->sections(CarbonImmutable::parse('2026-06-25'));

        $this->assertSame(['high', 'low'], array_map(fn (BriefingSection $section): string => $section->key, $sections));
    }
}

class HighPriorityBriefingSource implements BriefingSource
{
    public function key(): string { return 'high'; }
    public function label(): string { return 'High'; }
    public function priority(): int { return 10; }
    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        return new BriefingSection('high', 'High', 10, 'High summary');
    }
}

class LowPriorityBriefingSource implements BriefingSource
{
    public function key(): string { return 'low'; }
    public function label(): string { return 'Low'; }
    public function priority(): int { return 30; }
    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        return new BriefingSection('low', 'Low', 30, 'Low summary');
    }
}

class EmptyBriefingSource implements BriefingSource
{
    public function key(): string { return 'empty'; }
    public function label(): string { return 'Empty'; }
    public function priority(): int { return 20; }
    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        return null;
    }
}
