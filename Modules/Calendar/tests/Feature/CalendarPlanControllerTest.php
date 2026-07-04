<?php

namespace Modules\Calendar\Tests\Feature;

use App\Contracts\SchedulableGoals;
use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Actions\GenerateWeeklyPlan;
use Modules\Calendar\Contracts\PlanComposer;
use Modules\Calendar\Data\BusyTime;
use Modules\Calendar\Data\ComposedPlan;
use Modules\Calendar\Data\PlanItemData;
use Modules\Calendar\Models\CalendarPlanItem;
use Modules\Calendar\Models\GoogleCalendarToken;
use Modules\Calendar\Services\Google\GoogleCalendarClient;
use Tests\TestCase;

class CalendarPlanControllerTest extends TestCase
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

    private function seedGoal(array $overrides = []): void
    {
        app(SchedulableGoals::class)->create(array_merge([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 1,
            'target_max' => 1,
            'duration_minutes' => 90,
            'plannable' => true,
            'active' => true,
        ], $overrides));
    }

    public function test_generate_weekly_plan_validates_invalid_ai_slots_and_falls_back(): void
    {
        $this->seedGoal();
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
        $this->seedGoal(['target_min' => 3, 'target_max' => 3]);
        $this->app->instance(GoogleCalendarClient::class, new FakePlannerCalendar);
        $plan = app(GenerateWeeklyPlan::class)(CarbonImmutable::parse('2026-06-29'), push: false);
        $item = $plan->items()->where('status', 'proposed')->firstOrFail();

        $this->postJson(route('calendar.items.accept', $item))
            ->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('google_event_id', 'google-event-1');

        $this->postJson(route('calendar.accept-all'))->assertOk();
        $this->assertSame(0, CalendarPlanItem::query()->where('status', 'proposed')->count());
    }

    public function test_google_token_service_refreshes_expired_token(): void
    {
        config(['calendar.google.client_id' => 'id', 'calendar.google.client_secret' => 'secret']);
        GoogleCalendarToken::query()->create([
            'access_token' => 'old',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'new', 'expires_in' => 3600])]);

        $this->assertSame('new', app(\Modules\Calendar\Services\Google\GoogleCalendarTokenService::class)->accessToken());
    }

    public function test_index_renders_html_weekplan_with_real_data(): void
    {
        $this->withoutVite();
        $this->app->instance(GoogleCalendarClient::class, new FakePlannerCalendar);

        GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);

        $this->seedGoal(['target_min' => 3, 'target_max' => 3]);

        $plan = \Modules\Calendar\Models\CalendarPlan::query()->create([
            'week_key' => '2026-W27',
            'summary' => '3× sporten gepland deze week.',
            'status' => 'proposed',
            'is_fallback' => false,
            'generated_at' => CarbonImmutable::now(),
        ]);

        $plan->items()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'start_at' => CarbonImmutable::parse('2026-06-29 18:00'),
            'end_at' => CarbonImmutable::parse('2026-06-29 19:30'),
            'status' => 'proposed',
        ]);

        $this->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Week plan')
            ->assertSee('Sporten');
    }

    public function test_goal_crud_and_generate(): void
    {
        $this->postJson(route('calendar.goals.store'), [
            'title' => 'Lezen',
            'category' => 'custom',
            'frequency_type' => 'weekly',
            'target_min' => 1,
            'target_max' => 1,
            'duration_minutes' => 60,
        ])->assertCreated()->assertJsonPath('goal.title', 'Lezen');

        $goal = app(SchedulableGoals::class)->all()[0];
        $this->patchJson(route('calendar.goals.update', $goal->id), ['active' => false])->assertOk()->assertJsonPath('goal.active', false);
        $this->getJson(route('calendar.goals.index'))->assertOk()->assertJsonPath('goals.0.title', 'Lezen');

        $this->app->instance(GoogleCalendarClient::class, new FakePlannerCalendar);
        $this->postJson(route('calendar.generate'), ['week_start' => '2026-06-29'])
            ->assertOk()
            ->assertJsonStructure(['id', 'week_key', 'status', 'summary', 'is_fallback', 'items']);
        $this->getJson(route('calendar.index'))->assertOk()->assertJsonStructure(['connected', 'plan', 'habits']);

        $this->deleteJson(route('calendar.goals.destroy', $goal->id))->assertNoContent();
    }

    public function test_habit_complete_and_undo_via_calendar(): void
    {
        $goal = app(SchedulableGoals::class)->create([
            'title' => 'Hardlopen', 'category' => 'sport', 'frequency_type' => 'times_per_week',
            'target_min' => 3, 'target_max' => 3, 'plannable' => true,
        ]);

        $this->postJson(route('calendar.habits.complete', $goal->id), ['date' => '2026-06-25'])
            ->assertOk()->assertJsonPath('habit.completed_today', true);
        $this->assertDatabaseCount('task_recurrence_completions', 1);

        $this->deleteJson(route('calendar.habits.complete.destroy', $goal->id), ['date' => '2026-06-25'])
            ->assertOk()->assertJsonPath('habit.completed_today', false);
        $this->assertDatabaseCount('task_recurrence_completions', 0);
    }

    public function test_google_callback_rejects_when_provider_returns_error(): void
    {
        $this->withSession(['google_calendar_oauth_state' => 'expected-state']);

        $this->get(route('calendar.google.callback', ['error' => 'access_denied', 'state' => 'expected-state']))
            ->assertRedirect(route('calendar.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('google_calendar_tokens', 0);
        $this->assertNull(session()->get('google_calendar_oauth_state'));
    }

    public function test_google_callback_rejects_empty_code(): void
    {
        $this->withSession(['google_calendar_oauth_state' => 'expected-state']);

        $this->get(route('calendar.google.callback', ['state' => 'expected-state']))
            ->assertRedirect(route('calendar.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('google_calendar_tokens', 0);
        $this->assertNull(session()->get('google_calendar_oauth_state'));
    }

    public function test_google_callback_rejects_state_mismatch(): void
    {
        $this->withSession(['google_calendar_oauth_state' => 'expected-state']);

        $this->get(route('calendar.google.callback', ['state' => 'tampered', 'code' => 'auth-code']))
            ->assertRedirect(route('calendar.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('google_calendar_tokens', 0);
        $this->assertNull(session()->get('google_calendar_oauth_state'));
    }

    public function test_google_callback_exchanges_code_on_valid_state(): void
    {
        config(['calendar.google.client_id' => 'id', 'calendar.google.client_secret' => 'secret', 'calendar.google.redirect' => 'https://hub.test/calendar/google/callback']);
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fresh', 'refresh_token' => 'refresh', 'expires_in' => 3600])]);

        $this->withSession(['google_calendar_oauth_state' => 'expected-state']);

        $this->get(route('calendar.google.callback', ['state' => 'expected-state', 'code' => 'auth-code']))
            ->assertRedirect(route('calendar.index'))
            ->assertSessionHas('success');

        $this->assertSame('fresh', GoogleCalendarToken::query()->firstOrFail()->access_token);
        $this->assertNull(session()->get('google_calendar_oauth_state'));
    }

    public function test_token_columns_are_encrypted_at_rest(): void
    {
        GoogleCalendarToken::query()->create([
            'access_token' => 'plain-access',
            'refresh_token' => 'plain-refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);

        $raw = \Illuminate\Support\Facades\DB::table('google_calendar_tokens')->first();

        $this->assertNotSame('plain-access', $raw->access_token);
        $this->assertNotSame('plain-refresh', $raw->refresh_token);
        $this->assertSame('plain-access', GoogleCalendarToken::query()->firstOrFail()->access_token);
    }

    public function test_deterministic_composer_marks_plan_as_fallback(): void
    {
        config(['ai.anthropic.api_key' => 'sk-present']);

        $composed = app(\Modules\Calendar\Services\DeterministicPlanComposer::class)->compose([
            new PlanItemData(1, 'Sporten', 'sport', CarbonImmutable::parse('2026-06-29 18:00'), CarbonImmutable::parse('2026-06-29 19:30')),
        ], []);

        // The composer does no real AI arrangement, so is_fallback must be honest (true) even with an API key set.
        $this->assertTrue($composed->isFallback);
    }

    public function test_google_client_applies_timeout_and_surfaces_http_errors(): void
    {
        config(['calendar.request_timeout' => 3]);

        GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);

        Http::fake([
            'https://www.googleapis.com/calendar/v3/*' => Http::response('upstream unavailable', 503),
        ]);

        $client = app(GoogleCalendarClient::class);
        $period = CarbonPeriod::create(CarbonImmutable::parse('2026-06-29'), CarbonImmutable::parse('2026-07-06'));

        // The GET fetch retries idempotently, then surfaces the upstream failure instead of hanging.
        try {
            $client->events($period);
            $this->fail('Expected a RequestException when Google Calendar returns an error.');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->assertSame(503, $e->response->status());
        }

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains($request->url(), 'googleapis.com/calendar/v3/'));
    }
}

class FakePlannerCalendar extends GoogleCalendarClient
{
    public function __construct(private readonly array $busy = []) {}

    public function events(CarbonPeriod $period): array
    {
        return [];
    }

    public function busyTimes(CarbonPeriod $period): array
    {
        return $this->busy;
    }

    public function insertEvent(CalendarPlanItem $item): string
    {
        return 'google-event-1';
    }
}

class InvalidPlanComposer implements PlanComposer
{
    public function compose(array $items, array $busy): ComposedPlan
    {
        return new ComposedPlan('Invalid AI plan', [
            new PlanItemData($items[0]->goalId, $items[0]->title, $items[0]->category, CarbonImmutable::parse('2026-06-29 10:00'), CarbonImmutable::parse('2026-06-29 11:30')),
        ]);
    }
}

class FakePlannerNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '');
    }

    public function send(string $title, string $message): void
    {
        $this->sent[] = compact('title', 'message');
    }
}
