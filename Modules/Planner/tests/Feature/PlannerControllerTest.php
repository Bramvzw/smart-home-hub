<?php

namespace Modules\Planner\Tests\Feature;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Planner\Actions\GenerateWeeklyPlan;
use Modules\Planner\Contracts\PlanComposer;
use Modules\Planner\Data\BusyTime;
use Modules\Planner\Data\ComposedPlan;
use Modules\Planner\Data\PlanItemData;
use Modules\Planner\Models\GoogleCalendarToken;
use Modules\Planner\Models\PlannerIntention;
use Modules\Planner\Models\PlannerPlanItem;
use Modules\Planner\Services\Google\GoogleCalendarClient;
use Tests\TestCase;

class PlannerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 12:00:00'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_generate_weekly_plan_validates_invalid_ai_slots_and_falls_back(): void
    {
        $this->seedIntentions();
        $this->app->instance(GoogleCalendarClient::class, new FakePlannerCalendar([
            new BusyTime(CarbonImmutable::parse('2026-06-29 18:00'), CarbonImmutable::parse('2026-06-29 19:30')),
        ]));
        $this->app->instance(PlanComposer::class, new InvalidPlanComposer);
        $this->app->instance(HubNotifier::class, new FakePlannerNotifier);

        $plan = app(GenerateWeeklyPlan::class)(CarbonImmutable::parse('2026-06-29'), push: true);

        $this->assertTrue($plan->is_fallback);
        $this->assertGreaterThan(0, $plan->items()->where('status', 'proposed')->count());
        $this->assertDatabaseMissing('planner_plan_items', [
            'start_at' => '2026-06-29 10:00:00',
        ]);
    }

    public function test_accept_item_and_accept_all_insert_google_events(): void
    {
        $this->app->instance(GoogleCalendarClient::class, new FakePlannerCalendar);
        $plan = app(GenerateWeeklyPlan::class)(CarbonImmutable::parse('2026-06-29'), push: false);
        $item = $plan->items()->where('status', 'proposed')->firstOrFail();

        $this->postJson(route('planner.items.accept', $item))
            ->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('google_event_id', 'google-event-1');

        $this->postJson(route('planner.accept-all'))->assertOk();
        $this->assertSame(0, PlannerPlanItem::query()->where('status', 'proposed')->count());
    }

    public function test_google_token_service_refreshes_expired_token(): void
    {
        config(['planner.google.client_id' => 'id', 'planner.google.client_secret' => 'secret']);
        GoogleCalendarToken::query()->create([
            'access_token' => 'old',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'new', 'expires_in' => 3600])]);

        $this->assertSame('new', app(\Modules\Planner\Services\Google\GoogleCalendarTokenService::class)->accessToken());
    }

    public function test_index_renders_html_weekplan_with_real_data(): void
    {
        $this->withoutVite();

        GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);

        $intention = PlannerIntention::query()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 3,
            'target_max' => 3,
            'duration_minutes' => 90,
            'active' => true,
        ]);

        $plan = \Modules\Planner\Models\PlannerPlan::query()->create([
            'week_key' => '2026-W27',
            'summary' => '3× sporten gepland deze week.',
            'status' => 'proposed',
            'is_fallback' => false,
            'generated_at' => CarbonImmutable::now(),
        ]);

        $plan->items()->create([
            'intention_id' => $intention->id,
            'title' => 'Sporten',
            'start_at' => CarbonImmutable::parse('2026-06-29 18:00'),
            'end_at' => CarbonImmutable::parse('2026-06-29 19:30'),
            'status' => 'proposed',
        ]);

        $this->get(route('planner.index'))
            ->assertOk()
            ->assertSee('Agenda-planner')
            ->assertSee('Sporten');
    }

    public function test_planner_contract_and_intention_crud(): void
    {
        $this->postJson(route('planner.intentions.store'), [
            'title' => 'Lezen',
            'category' => 'custom',
            'frequency_type' => 'weekly',
            'target_min' => 1,
            'target_max' => 1,
            'duration_minutes' => 60,
        ])->assertCreated()->assertJsonPath('intention.title', 'Lezen');

        $intention = PlannerIntention::query()->firstOrFail();
        $this->patchJson(route('planner.intentions.update', $intention), ['active' => false])->assertOk()->assertJsonPath('intention.active', false);
        $this->getJson(route('planner.intentions.index'))->assertOk()->assertJsonPath('intentions.0.title', 'Lezen');

        $this->app->instance(GoogleCalendarClient::class, new FakePlannerCalendar);
        $this->postJson(route('planner.generate'), ['week_start' => '2026-06-29'])
            ->assertOk()
            ->assertJsonStructure(['id', 'week_key', 'status', 'summary', 'is_fallback', 'items']);
        $this->getJson(route('planner.index'))->assertOk()->assertJsonStructure(['connected', 'plan', 'intentions']);

        $this->deleteJson(route('planner.intentions.destroy', $intention))->assertNoContent();
    }

    private function seedIntentions(): void
    {
        PlannerIntention::query()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 1,
            'target_max' => 1,
            'duration_minutes' => 90,
            'active' => true,
        ]);
    }
}

class FakePlannerCalendar extends GoogleCalendarClient
{
    public function __construct(private readonly array $busy = []) {}

    public function busyTimes(CarbonPeriod $period): array
    {
        return $this->busy;
    }

    public function insertEvent(PlannerPlanItem $item): string
    {
        return 'google-event-1';
    }
}

class InvalidPlanComposer implements PlanComposer
{
    public function compose(array $items, array $busy): ComposedPlan
    {
        return new ComposedPlan('Invalid AI plan', [
            new PlanItemData($items[0]->intentionId, $items[0]->title, $items[0]->category, CarbonImmutable::parse('2026-06-29 10:00'), CarbonImmutable::parse('2026-06-29 11:30')),
        ]);
    }
}

class FakePlannerNotifier extends HubNotifier
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
