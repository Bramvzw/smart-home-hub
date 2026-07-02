<?php

namespace Modules\Calendar\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Models\GoogleCalendarToken;
use Modules\Calendar\Services\CalendarService;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/*';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'Europe/Amsterdam',
            'calendar.window_days' => 7,
            'calendar.cache_ttl' => 900,
            'calendar.request_timeout' => 5,
            'calendar.google.calendar_id' => 'primary',
        ]);
    }

    private function connect(): void
    {
        GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);
    }

    private function service(): CalendarService
    {
        return app(CalendarService::class);
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-06-08 00:00:00', 'Europe/Amsterdam');
    }

    /** @param  list<array<string, mixed>>  $items */
    private function fakeEvents(array $items): void
    {
        Http::fake([self::EVENTS_URL => Http::response(['items' => $items], 200)]);
    }

    public function test_maps_google_events_and_respects_timezone(): void
    {
        $this->connect();
        $this->fakeEvents([
            [
                'id' => 'dentist-1',
                'summary' => 'Tandarts',
                'location' => 'Kliniek',
                'start' => ['dateTime' => '2026-06-09T14:00:00+02:00'],
                'end' => ['dateTime' => '2026-06-09T14:30:00+02:00'],
            ],
        ]);

        $feed = $this->service()->feed(7, $this->now());

        $this->assertCount(1, $feed->events);
        $this->assertFalse($feed->stale);

        $event = $feed->events[0];
        $this->assertSame('Tandarts', $event->summary);
        $this->assertSame('Kliniek', $event->location);
        $this->assertFalse($event->allDay);
        $this->assertSame('14:00+02:00', $event->start->format('H:iP'));
        $this->assertSame('Google Calendar', $event->calendarLabel);
    }

    public function test_marks_all_day_events(): void
    {
        $this->connect();
        $this->fakeEvents([
            [
                'id' => 'allday-1',
                'summary' => 'Verjaardag',
                'start' => ['date' => '2026-06-10'],
                'end' => ['date' => '2026-06-11'],
            ],
        ]);

        $feed = $this->service()->feed(7, $this->now());

        $this->assertCount(1, $feed->events);
        $this->assertTrue($feed->events[0]->allDay);
        $this->assertSame('2026-06-10', $feed->events[0]->start->toDateString());
    }

    public function test_sorts_events_chronologically(): void
    {
        $this->connect();
        $this->fakeEvents([
            ['id' => 'b', 'summary' => 'Werkoverleg', 'start' => ['dateTime' => '2026-06-09T10:00:00+02:00'], 'end' => ['dateTime' => '2026-06-09T11:00:00+02:00']],
            ['id' => 'a', 'summary' => 'Tandarts', 'start' => ['dateTime' => '2026-06-09T08:00:00+02:00'], 'end' => ['dateTime' => '2026-06-09T08:30:00+02:00']],
        ]);

        $feed = $this->service()->feed(7, $this->now());

        $this->assertSame('Tandarts', $feed->events[0]->summary);
        $this->assertSame('Werkoverleg', $feed->events[1]->summary);
    }

    public function test_skips_cancelled_events(): void
    {
        $this->connect();
        $this->fakeEvents([
            ['id' => 'x', 'summary' => 'Afgezegd', 'status' => 'cancelled', 'start' => ['dateTime' => '2026-06-09T09:00:00+02:00'], 'end' => ['dateTime' => '2026-06-09T10:00:00+02:00']],
            ['id' => 'y', 'summary' => 'Blijft', 'start' => ['dateTime' => '2026-06-09T11:00:00+02:00'], 'end' => ['dateTime' => '2026-06-09T12:00:00+02:00']],
        ]);

        $feed = $this->service()->feed(7, $this->now());

        $this->assertCount(1, $feed->events);
        $this->assertSame('Blijft', $feed->events[0]->summary);
    }

    public function test_returns_empty_and_makes_no_request_when_not_connected(): void
    {
        Http::fake();

        $feed = $this->service()->feed(7, $this->now());

        $this->assertSame([], $feed->events);
        $this->assertFalse($feed->stale);
        Http::assertNothingSent();
    }

    public function test_caches_events_within_ttl(): void
    {
        $this->connect();
        $this->fakeEvents([
            ['id' => 'a', 'summary' => 'Tandarts', 'start' => ['dateTime' => '2026-06-09T08:00:00+02:00'], 'end' => ['dateTime' => '2026-06-09T08:30:00+02:00']],
        ]);

        $service = $this->service();

        $service->feed(7, $this->now());
        Http::assertSentCount(1);

        // A second call inside the TTL is served from cache — no extra request.
        $service->feed(7, $this->now());
        Http::assertSentCount(1);
    }

    public function test_failed_fetch_falls_back_to_stale_cache(): void
    {
        $this->connect();
        Http::fake([
            self::EVENTS_URL => Http::sequence()
                ->push(['items' => [
                    ['id' => 'a', 'summary' => 'Tandarts', 'start' => ['dateTime' => '2026-06-09T08:00:00+02:00'], 'end' => ['dateTime' => '2026-06-09T08:30:00+02:00']],
                ]], 200)
                ->push('upstream down', 500),
        ]);

        $service = $this->service();

        $fresh = $service->feed(7, $this->now());
        $this->assertFalse($fresh->stale);
        $this->assertFalse($fresh->failed);

        // Expire the TTL entry so the next call refreshes and hits the failure.
        Cache::forget('calendar:events:'.$this->now()->startOfDay()->toDateString().':7');

        $stale = $service->feed(7, $this->now());

        $this->assertTrue($stale->failed);
        $this->assertTrue($stale->stale);
        $this->assertSame(['Google Calendar'], $stale->staleFeeds);
        // The page still shows the last known-good events.
        $this->assertCount(1, $stale->events);
    }
}
