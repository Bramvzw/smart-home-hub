<?php

namespace Modules\Calendar\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Services\CalendarService;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    private const FEED_URL = 'https://example.test/secret/basic.ics';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'Europe/Amsterdam',
            'calendar.ics_urls' => [self::FEED_URL],
            'calendar.window_days' => 7,
            'calendar.cache_ttl' => 900,
            'calendar.request_timeout' => 5,
        ]);
    }

    private function service(): CalendarService
    {
        return app(CalendarService::class);
    }

    /** A daily-recurring meeting (5 occurrences) plus one single appointment. */
    private function ics(): string
    {
        return <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//Calendar//EN
        BEGIN:VEVENT
        UID:standup-1
        SUMMARY:Daily standup
        DTSTART;TZID=Europe/Amsterdam:20260608T090000
        DTEND;TZID=Europe/Amsterdam:20260608T091500
        RRULE:FREQ=DAILY;COUNT=5
        END:VEVENT
        BEGIN:VEVENT
        UID:dentist-1
        SUMMARY:Tandarts
        LOCATION:Kliniek
        DTSTART;TZID=Europe/Amsterdam:20260609T140000
        DTEND;TZID=Europe/Amsterdam:20260609T143000
        END:VEVENT
        END:VCALENDAR
        ICS;
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-06-08 00:00:00', 'Europe/Amsterdam');
    }

    public function test_parses_recurring_events_and_respects_timezone(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->ics(), 200)]);

        $feed = $this->service()->feed(7, $this->now());

        // 5 daily occurrences (08–12 June) + 1 single appointment.
        $this->assertCount(6, $feed->events);
        $this->assertFalse($feed->stale);

        $standups = array_filter($feed->events, fn ($event) => $event->summary === 'Daily standup');
        $this->assertCount(5, $standups);

        $first = $feed->events[0];
        $this->assertSame('Daily standup', $first->summary);
        // Raw wall-clock + offset: June is CEST, so +02:00 — assert without re-normalising.
        $this->assertSame('09:00+02:00', $first->start->format('H:iP'));

        $dentist = array_values(array_filter($feed->events, fn ($event) => $event->summary === 'Tandarts'))[0];
        $this->assertSame('Kliniek', $dentist->location);
        $this->assertFalse($dentist->allDay);
    }

    public function test_marks_all_day_events(): void
    {
        $ics = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//Calendar//EN
        BEGIN:VEVENT
        UID:allday-1
        SUMMARY:Verjaardag
        DTSTART;VALUE=DATE:20260610
        DTEND;VALUE=DATE:20260611
        END:VEVENT
        END:VCALENDAR
        ICS;

        Http::fake([self::FEED_URL => Http::response($ics, 200)]);

        $feed = $this->service()->feed(7, $this->now());

        $this->assertCount(1, $feed->events);
        $event = $feed->events[0];
        $this->assertSame('Verjaardag', $event->summary);
        $this->assertTrue($event->allDay);
        $this->assertSame('2026-06-10', $event->start->toDateString());
    }

    public function test_keeps_correct_offset_across_the_october_dst_boundary(): void
    {
        // Dutch DST ends on Sunday 2026-10-25: 03:00 CEST falls back to 02:00 CET.
        $ics = <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//Calendar//EN
        BEGIN:VEVENT
        UID:dst-before
        SUMMARY:Voor DST
        DTSTART;TZID=Europe/Amsterdam:20261024T090000
        DTEND;TZID=Europe/Amsterdam:20261024T100000
        END:VEVENT
        BEGIN:VEVENT
        UID:dst-after
        SUMMARY:Na DST
        DTSTART;TZID=Europe/Amsterdam:20261026T090000
        DTEND;TZID=Europe/Amsterdam:20261026T100000
        END:VEVENT
        END:VCALENDAR
        ICS;

        Http::fake([self::FEED_URL => Http::response($ics, 200)]);

        $feed = $this->service()->feed(3, CarbonImmutable::parse('2026-10-24 00:00:00', 'Europe/Amsterdam'));

        $before = array_values(array_filter($feed->events, fn ($e) => $e->summary === 'Voor DST'))[0];
        $after = array_values(array_filter($feed->events, fn ($e) => $e->summary === 'Na DST'))[0];

        // Same wall-clock (09:00) but different UTC offset across the boundary.
        $this->assertSame('09:00+02:00', $before->start->format('H:iP'));
        $this->assertSame('09:00+01:00', $after->start->format('H:iP'));
    }

    public function test_caches_feed_within_ttl(): void
    {
        Http::fake([self::FEED_URL => Http::response($this->ics(), 200)]);

        $service = $this->service();

        $first = $service->feed(7, $this->now());
        $this->assertFalse($first->stale);
        Http::assertSentCount(1);

        // A second call inside the TTL is served from cache — no extra request.
        $service->feed(7, $this->now());
        Http::assertSentCount(1);
    }

    public function test_failed_fetch_falls_back_to_stale_cache(): void
    {
        // First fetch succeeds and primes the last-good cache; the refresh fails.
        Http::fake([
            self::FEED_URL => Http::sequence()
                ->push($this->ics(), 200)
                ->push('upstream down', 500),
        ]);

        $service = $this->service();

        $fresh = $service->feed(7, $this->now());
        $this->assertFalse($fresh->stale);
        $this->assertFalse($fresh->failed);

        // Expire the TTL entry so the next call refreshes and hits the failure.
        Cache::forget('calendar:feed:7');

        $stale = $service->feed(7, $this->now());

        $this->assertTrue($stale->failed);
        $this->assertTrue($stale->stale);
        // The page still shows the last known-good events.
        $this->assertCount(6, $stale->events);
    }
}
