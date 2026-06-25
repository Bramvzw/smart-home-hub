<?php

namespace Modules\Briefing\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Briefing\CalendarBriefingSource;
use Modules\News\Briefing\NewsBriefingSource;
use Modules\News\Models\NewsItem;
use Modules\Tasks\Briefing\TasksBriefingSource;
use Modules\Tasks\Models\TaskBoard;
use Modules\Weather\Briefing\WeatherBriefingSource;
use Tests\TestCase;

class BriefingSourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 08:00:00', 'Europe/Amsterdam'));
        config([
            'app.timezone' => 'Europe/Amsterdam',
            'briefing.timezone' => 'Europe/Amsterdam',
            'calendar.feeds' => [],
            'weather.location.label' => 'Wijhe',
            'weather.location.latitude' => null,
            'weather.location.longitude' => null,
            'weather.location.timezone' => 'Europe/Amsterdam',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_weather_source_returns_section_for_known_forecast_and_null_when_unconfigured(): void
    {
        $this->assertNull(app(WeatherBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        config([
            'weather.location.latitude' => 52.4263,
            'weather.location.longitude' => 6.1322,
        ]);
        Http::fake([
            '*api.open-meteo.com*' => Http::response($this->forecastPayload()),
        ]);

        $section = app(WeatherBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('weather', $section->key);
        $this->assertStringContainsString('geen regenalarm', $section->summary);
        $this->assertSame('Wijhe', $section->data['location']);
    }

    public function test_calendar_source_returns_todays_events_and_null_without_feeds(): void
    {
        $this->assertNull(app(CalendarBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        config([
            'calendar.feeds' => [
                ['label' => 'Privé', 'url' => 'https://example.com/calendar.ics', 'color' => '#fff'],
            ],
        ]);
        Http::fake([
            'https://example.com/calendar.ics' => Http::response($this->icsFixture()),
        ]);

        $section = app(CalendarBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('calendar', $section->key);
        $this->assertStringContainsString('10:00 Standup', $section->summary);
        $this->assertSame('Standup', $section->data['events'][0]['title']);
    }

    public function test_tasks_source_returns_top_open_tasks_and_null_when_empty(): void
    {
        $this->assertNull(app(TasksBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        $board = TaskBoard::query()->create(['name' => 'Tasks']);
        $column = $board->columns()->create(['name' => 'Todo', 'position' => 0]);
        $board->tasks()->create([
            'column_id' => $column->id,
            'title' => 'Belangrijke taak',
            'priority' => 'high',
            'due_date' => '2026-06-25',
            'position' => 0,
        ]);

        $section = app(TasksBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('tasks', $section->key);
        $this->assertStringContainsString('Belangrijke taak', $section->summary);
        $this->assertSame('high', $section->data['tasks'][0]['priority']);
    }

    public function test_news_source_returns_unread_items_by_topic_and_null_when_empty(): void
    {
        config([
            'news.topics' => ['dev' => 'Dev & werk'],
            'news.feeds' => [
                ['key' => 'laravel-news', 'topic' => 'dev', 'label' => 'Laravel News', 'url' => 'https://example.com/feed'],
            ],
        ]);

        $this->assertNull(app(NewsBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        NewsItem::query()->create([
            'feed_key' => 'laravel-news',
            'topic' => 'dev',
            'guid' => 'one',
            'title' => 'Laravel update',
            'url' => 'https://example.com/one',
            'summary' => 'Framework news',
            'published_at' => CarbonImmutable::parse('2026-06-25 07:00:00', 'UTC'),
            'matched_keywords' => ['Laravel'],
        ]);

        $section = app(NewsBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('news', $section->key);
        $this->assertStringContainsString('1 ongelezen nieuwsitem', $section->summary);
        $this->assertSame('Laravel News', $section->data['groups'][0]['items'][0]['source']);
    }

    private function forecastPayload(): array
    {
        return [
            'current' => [
                'temperature_2m' => 18.4,
                'precipitation' => 0,
                'weather_code' => 3,
                'wind_speed_10m' => 18,
                'wind_gusts_10m' => 28,
            ],
            'hourly' => [
                'time' => ['2026-06-25T08:00', '2026-06-25T09:00', '2026-06-25T10:00'],
                'temperature_2m' => [18.2, 19.1, 20.4],
                'precipitation' => [0, 0, 0],
                'precipitation_probability' => [10, 20, 10],
                'weather_code' => [3, 3, 2],
                'wind_speed_10m' => [18, 19, 20],
                'wind_gusts_10m' => [28, 30, 31],
            ],
            'daily' => [
                'time' => ['2026-06-25', '2026-06-26'],
                'weather_code' => [3, 2],
                'temperature_2m_max' => [24.2, 23.1],
                'temperature_2m_min' => [14.8, 15.2],
                'precipitation_sum' => [0, 0.1],
                'precipitation_probability_max' => [20, 30],
                'wind_speed_10m_max' => [20, 18],
                'wind_gusts_10m_max' => [31, 26],
            ],
        ];
    }

    private function icsFixture(): string
    {
        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'BEGIN:VEVENT',
            'UID:standup',
            'DTSTART;TZID=Europe/Amsterdam:20260625T100000',
            'DTEND;TZID=Europe/Amsterdam:20260625T103000',
            'SUMMARY:Standup',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }
}
