<?php

namespace Modules\Briefing\Tests\Feature;

use App\Contracts\BriefingSource;
use App\Services\Ntfy\HubNotifier;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Briefing\Actions\GenerateBriefing;
use Modules\Briefing\Contracts\BriefingTextGenerator;
use Modules\Briefing\Models\Briefing;
use Tests\TestCase;

class BriefingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 08:00:00', 'Europe/Amsterdam'));
        config([
            'briefing.timezone' => 'Europe/Amsterdam',
            'briefing.retention_days' => 14,
            'weather.location.latitude' => null,
            'weather.location.longitude' => null,
            'calendar.feeds' => [],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_generate_briefing_composes_stores_and_pushes_once(): void
    {
        $this->fakeSource(new FixedBriefingSource('weather', 'Weer', 10, 'Droog en 24 graden'));
        $generator = new FakeBriefingTextGenerator('AI briefing body');
        $notifier = new FakeBriefingNotifier;
        $this->app->instance(BriefingTextGenerator::class, $generator);
        $this->app->instance(HubNotifier::class, $notifier);

        $briefing = app(GenerateBriefing::class)();

        $this->assertSame('AI briefing body', $briefing->body);
        $this->assertFalse($briefing->is_fallback);
        $this->assertSame(config('briefing.ai.model'), $briefing->model);
        $this->assertDatabaseCount('briefings', 1);
        $this->assertCount(1, $notifier->sent);
        $this->assertStringContainsString('Droog en 24 graden', $generator->prompt);
    }

    public function test_fallback_is_stored_and_still_pushed_when_ai_is_unavailable(): void
    {
        $this->fakeSource(new FixedBriefingSource('tasks', 'Taken', 30, 'Top 1 open taak'));
        $notifier = new FakeBriefingNotifier;
        $this->app->instance(BriefingTextGenerator::class, new ThrowingBriefingTextGenerator);
        $this->app->instance(HubNotifier::class, $notifier);

        $briefing = app(GenerateBriefing::class)();

        $this->assertTrue($briefing->is_fallback);
        $this->assertNull($briefing->model);
        $this->assertStringContainsString('Taken: Top 1 open taak', $briefing->body);
        $this->assertCount(1, $notifier->sent);
    }

    public function test_generation_degrades_to_available_sources_only_and_upserts_per_date(): void
    {
        $this->fakeSource(new FixedBriefingSource('weather', 'Weer', 10, 'Zonnig'));
        $this->fakeSource(new NullBriefingSource);
        $this->app->instance(BriefingTextGenerator::class, new FakeBriefingTextGenerator('Eerste body'));
        $this->app->instance(HubNotifier::class, new FakeBriefingNotifier);

        $first = app(GenerateBriefing::class)();
        $this->app->instance(BriefingTextGenerator::class, new FakeBriefingTextGenerator('Tweede body'));
        $second = app(GenerateBriefing::class)(push: false);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('Tweede body', $second->body);
        $this->assertDatabaseCount('briefings', 1);
        $this->assertCount(1, $second->sections);
        $this->assertSame('weather', $second->sections[0]['key']);
    }

    public function test_items_endpoint_and_regenerate_return_contract(): void
    {
        $this->fakeSource(new FixedBriefingSource('weather', 'Weer', 10, 'Droog'));
        $this->app->instance(BriefingTextGenerator::class, new FakeBriefingTextGenerator('Nieuwe briefing'));
        $this->app->instance(HubNotifier::class, new FakeBriefingNotifier);

        $this->getJson(route('briefing.index'))->assertNotFound();

        $this->postJson(route('briefing.regenerate'))
            ->assertOk()
            ->assertJsonPath('date', '2026-06-25')
            ->assertJsonPath('body', 'Nieuwe briefing')
            ->assertJsonPath('sections.0.key', 'weather')
            ->assertJsonPath('is_fallback', false)
            ->assertJsonPath('model', config('briefing.ai.model'))
            ->assertJsonStructure([
                'date',
                'body',
                'sections' => [
                    ['key', 'label', 'summary'],
                ],
                'generated_at',
                'is_fallback',
                'model',
            ]);

        $this->getJson(route('briefing.index'))
            ->assertOk()
            ->assertJsonPath('body', 'Nieuwe briefing');
    }

    public function test_old_briefings_are_pruned_on_generate(): void
    {
        Briefing::query()->create([
            'date' => '2026-06-01',
            'body' => 'Old',
            'sections' => [],
            'generated_at' => CarbonImmutable::parse('2026-06-01 08:00:00', 'UTC'),
            'is_fallback' => true,
        ]);
        $this->fakeSource(new FixedBriefingSource('weather', 'Weer', 10, 'Droog'));
        $this->app->instance(BriefingTextGenerator::class, new FakeBriefingTextGenerator('Vandaag'));
        $this->app->instance(HubNotifier::class, new FakeBriefingNotifier);

        app(GenerateBriefing::class)();

        $this->assertDatabaseMissing('briefings', ['date' => '2026-06-01']);
    }

    private function fakeSource(BriefingSource $source): void
    {
        $class = $source::class;
        $this->app->instance($class, $source);
        $this->app->tag([$class], 'briefing.source');
    }
}

class FakeBriefingTextGenerator implements BriefingTextGenerator
{
    public string $prompt = '';

    public function __construct(private readonly string $body) {}

    public function generate(array $sections, string $systemPrompt, string $prompt): string
    {
        $this->prompt = $prompt;

        return $this->body;
    }
}

class ThrowingBriefingTextGenerator implements BriefingTextGenerator
{
    public function generate(array $sections, string $systemPrompt, string $prompt): string
    {
        throw new \RuntimeException('AI down');
    }
}

class FakeBriefingNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '', 10);
    }

    public function send(string $title, string $message): void
    {
        $this->sent[] = compact('title', 'message');
    }
}

class FixedBriefingSource implements BriefingSource
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly int $priority,
        private readonly string $summary,
    ) {}

    public function key(): string { return $this->key; }
    public function label(): string { return $this->label; }
    public function priority(): int { return $this->priority; }
    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        return new BriefingSection($this->key, $this->label, $this->priority, $this->summary, ['date' => $date->toDateString()]);
    }
}

class NullBriefingSource implements BriefingSource
{
    public function key(): string { return 'null'; }
    public function label(): string { return 'Null'; }
    public function priority(): int { return 20; }
    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        return null;
    }
}
