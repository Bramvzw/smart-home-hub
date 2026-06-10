<?php

namespace Modules\Calendar\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    private const FEED_URL = 'https://example.test/secret/basic.ics';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.timezone' => 'Europe/Amsterdam']);
    }

    private function ics(): string
    {
        return <<<'ICS'
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test//Calendar//EN
        BEGIN:VEVENT
        UID:dentist-1
        SUMMARY:Tandarts afspraak
        DTSTART;TZID=Europe/Amsterdam:20260609T140000
        DTEND;TZID=Europe/Amsterdam:20260609T143000
        END:VEVENT
        END:VCALENDAR
        ICS;
    }

    public function test_index_page_lists_events_from_the_feed(): void
    {
        config([
            'calendar.ics_urls' => [self::FEED_URL],
            'calendar.window_days' => 30,
        ]);
        Http::fake([self::FEED_URL => Http::response($this->ics(), 200)]);

        // Pin "now" before the fixture appointment so it falls inside the window.
        $this->travelTo(CarbonImmutable::parse('2026-06-08 08:00:00', 'Europe/Amsterdam'));

        $response = $this->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Agenda');
        $response->assertSee('Tandarts afspraak');
    }

    public function test_index_page_shows_empty_state_when_no_feed_is_configured(): void
    {
        config(['calendar.ics_urls' => []]);

        $response = $this->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Geen agenda gekoppeld');
    }
}
