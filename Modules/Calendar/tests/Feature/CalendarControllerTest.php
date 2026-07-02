<?php

namespace Modules\Calendar\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Models\GoogleCalendarToken;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.timezone' => 'Europe/Amsterdam', 'calendar.google.calendar_id' => 'primary']);
    }

    private function connect(): void
    {
        GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);
    }

    public function test_index_shows_connect_prompt_when_google_is_not_connected(): void
    {
        $response = $this->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Connect your Google Calendar');
    }

    public function test_index_lists_events_from_google_calendar(): void
    {
        $this->connect();
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['items' => [
                [
                    'id' => 'dentist-1',
                    'summary' => 'Dentist appointment',
                    'start' => ['dateTime' => '2026-06-09T14:00:00+02:00'],
                    'end' => ['dateTime' => '2026-06-09T14:30:00+02:00'],
                ],
            ]], 200),
        ]);

        // Pin "now" before the fixture appointment so it falls inside the window.
        $this->travelTo(CarbonImmutable::parse('2026-06-08 08:00:00', 'Europe/Amsterdam'));

        $response = $this->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Dentist appointment');
    }
}
