<?php

namespace Modules\Briefing\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Briefing\CalendarBriefingSource;
use Modules\Deals\Briefing\DealsBriefingSource;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Models\WatchedProduct;
use Modules\Entertainment\Briefing\EntertainmentBriefingSource;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\MusicRelease;
use Modules\News\Briefing\NewsBriefingSource;
use Modules\News\Models\NewsItem;
use Modules\Printer\Briefing\PrinterBriefingSource;
use Modules\Printer\Models\FilamentSpool;
use Modules\Printer\Models\PrinterPart;
use Modules\Recipes\Briefing\RecipesBriefingSource;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Services\OfferAggregator;
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

    public function test_calendar_source_returns_todays_events_and_null_without_connection(): void
    {
        $this->assertNull(app(CalendarBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        \Modules\Calendar\Models\GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addDay(),
        ]);
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['items' => [
                [
                    'id' => 'standup',
                    'summary' => 'Standup',
                    'start' => ['dateTime' => '2026-06-25T10:00:00+02:00'],
                    'end' => ['dateTime' => '2026-06-25T10:30:00+02:00'],
                ],
            ]], 200),
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

    public function test_printer_source_returns_low_stock_and_null_when_sufficient(): void
    {
        FilamentSpool::query()->create([
            'material' => 'PLA',
            'color_name' => 'Wit',
            'total_weight_g' => 1000,
            'remaining_g' => 900,
        ]);
        PrinterPart::query()->create([
            'category' => 'spare',
            'name' => 'M4 bout',
            'quantity' => 50,
            'unit' => 'stuks',
            'low_threshold' => 5,
        ]);

        $this->assertNull(app(PrinterBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        FilamentSpool::query()->create([
            'material' => 'PLA',
            'color_name' => 'Zwart',
            'total_weight_g' => 1000,
            'remaining_g' => 120,
        ]);
        PrinterPart::query()->create([
            'category' => 'spare',
            'name' => 'M3 moer',
            'quantity' => 2,
            'unit' => 'stuks',
            'low_threshold' => 5,
        ]);

        $section = app(PrinterBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('printer', $section->key);
        $this->assertStringContainsString('PLA Zwart (12%)', $section->summary);
        $this->assertStringContainsString('M3 moer (2 stuks)', $section->summary);
        $this->assertSame('PLA', $section->data['low_spools'][0]['material']);
        $this->assertSame('M3 moer', $section->data['low_parts'][0]['name']);
    }

    public function test_deals_source_returns_price_drops_and_null_when_none(): void
    {
        $product = WatchedProduct::query()->create(['name' => 'Koffiezetapparaat', 'query' => 'koffiezetapparaat']);
        $listing = ProductListing::query()->create([
            'watched_product_id' => $product->id,
            'retailer' => 'bol',
            'title' => 'Koffiezetapparaat X',
            'url' => 'https://example.com/product',
            'current_price' => 89.00,
            'lowest_price' => 89.00,
            'confirmed' => true,
            'active' => true,
            'last_checked_at' => CarbonImmutable::now('Europe/Amsterdam'),
        ]);
        $listing->pricePoints()->create(['price' => 99.00, 'observed_at' => CarbonImmutable::now()->subDay()]);
        $listing->pricePoints()->create(['price' => 89.00, 'observed_at' => CarbonImmutable::now()]);

        $this->assertSame('deals', app(DealsBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'))->key);

        $listing->pricePoints()->delete();
        $listing->pricePoints()->create(['price' => 89.00, 'observed_at' => CarbonImmutable::now()]);

        $this->assertNull(app(DealsBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        $listing->pricePoints()->create(['price' => 99.00, 'observed_at' => CarbonImmutable::now()->subDay()]);

        $section = app(DealsBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('deals', $section->key);
        $this->assertStringContainsString('1 prijsdaling vandaag', $section->summary);
        $this->assertStringContainsString('Koffiezetapparaat', $section->summary);
        $this->assertSame(89.0, $section->data['drops'][0]['current_price']);
        $this->assertSame(99.0, $section->data['drops'][0]['previous_price']);
    }

    public function test_recipes_source_returns_current_week_menu_and_null_when_absent(): void
    {
        $this->assertNull(app(RecipesBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        $weekKey = app(OfferAggregator::class)->weekKey(CarbonImmutable::now('Europe/Amsterdam'));
        Recipe::query()->create([
            'week_key' => $weekKey,
            'title' => 'Pasta pesto',
            'servings' => 2,
            'time_minutes' => 25,
            'estimated_cost' => 8.5,
            'ingredients' => [],
            'steps' => [],
            'shopping_list' => [],
        ]);

        $section = app(RecipesBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('recipes', $section->key);
        $this->assertStringContainsString('Weekmenu klaar: 1 recept', $section->summary);
        $this->assertStringContainsString('Pasta pesto', $section->summary);
        $this->assertSame($weekKey, $section->data['week_key']);
    }

    public function test_entertainment_source_returns_upcoming_items_and_null_when_none(): void
    {
        $this->assertNull(app(EntertainmentBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam')));

        Concert::query()->create([
            'source' => 'ticketmaster',
            'external_id' => 'evt-1',
            'artist' => 'Coldplay',
            'title' => 'World Tour',
            'venue' => 'Ziggo Dome',
            'city' => 'Amsterdam',
            'date' => CarbonImmutable::now('Europe/Amsterdam')->addDays(3),
            'url' => 'https://example.com/tickets',
            'relevance' => 'followed',
        ]);
        Concert::query()->create([
            'source' => 'ticketmaster',
            'external_id' => 'evt-2',
            'artist' => 'Onbekend',
            'title' => 'Show',
            'venue' => 'Cafe',
            'city' => 'Zwolle',
            'date' => CarbonImmutable::now('Europe/Amsterdam')->addDays(3),
            'url' => 'https://example.com/other',
            'relevance' => 'none',
        ]);
        MusicRelease::query()->create([
            'spotify_id' => 'abc123',
            'artist' => 'Coldplay',
            'title' => 'New Album',
            'type' => 'album',
            'release_date' => CarbonImmutable::now('Europe/Amsterdam')->subDay(),
            'url' => 'https://example.com/album',
            'notified' => false,
        ]);

        $section = app(EntertainmentBriefingSource::class)->contribute(CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertSame('entertainment', $section->key);
        $this->assertStringContainsString('1 concert deze/volgende week', $section->summary);
        $this->assertStringContainsString('Coldplay @ Ziggo Dome', $section->summary);
        $this->assertStringContainsString('1 nieuwe release', $section->summary);
        $this->assertCount(1, $section->data['concerts']);
        $this->assertSame('New Album', $section->data['releases'][0]['title']);
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
}
