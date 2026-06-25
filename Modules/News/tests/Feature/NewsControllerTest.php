<?php

namespace Modules\News\Tests\Feature;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\News\Actions\CheckNewsKeywords;
use Modules\News\Actions\RefreshFeeds;
use Modules\News\Models\NewsItem;
use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 10:00:00', 'UTC'));
        config([
            'news.timezone' => 'Europe/Amsterdam',
            'news.items_per_topic' => 6,
            'news.retention_days' => 7,
            'news.keywords' => ['Bambu', 'Laravel', 'PHP 8'],
            'news.topics' => [
                '3d-printing' => '3D-printen & making',
                'dev' => 'Dev & werk',
            ],
            'news.feeds' => [
                ['key' => 'rss', 'topic' => '3d-printing', 'label' => 'RSS Feed', 'url' => 'https://example.com/rss'],
                ['key' => 'atom', 'topic' => 'dev', 'label' => 'Atom Feed', 'url' => 'https://example.com/atom'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_refresh_feeds_stores_items_grouped_by_topic(): void
    {
        Http::fake([
            'https://example.com/rss' => Http::response($this->fixture('rss.xml')),
            'https://example.com/atom' => Http::response($this->fixture('atom.xml')),
        ]);

        $result = app(RefreshFeeds::class)();

        $this->assertSame(3, $result->stored);
        $this->assertDatabaseHas('news_items', [
            'feed_key' => 'rss',
            'topic' => '3d-printing',
            'title' => 'Bambu Firmware Released',
        ]);
        $this->assertDatabaseHas('news_items', [
            'feed_key' => 'atom',
            'topic' => 'dev',
            'title' => 'Laravel Atom Entry',
        ]);
    }

    public function test_refresh_skips_a_failing_feed_while_storing_other_feeds(): void
    {
        Http::fake([
            'https://example.com/rss' => Http::response($this->fixture('rss.xml')),
            'https://example.com/atom' => Http::response('Nope', 500),
        ]);

        $result = app(RefreshFeeds::class)();

        $this->assertSame(2, $result->stored);
        $this->assertArrayHasKey('atom', $result->failedFeeds);
        $this->assertDatabaseHas('news_items', ['feed_key' => 'rss']);
    }

    public function test_keyword_notifications_are_sent_once_per_matching_item(): void
    {
        $item = NewsItem::query()->create([
            'feed_key' => 'rss',
            'topic' => '3d-printing',
            'guid' => 'match',
            'title' => 'Bambu firmware',
            'url' => 'https://example.com/match',
            'summary' => 'Printer update',
            'published_at' => CarbonImmutable::parse('2026-06-24 10:00:00', 'UTC'),
            'matched_keywords' => ['Bambu'],
        ]);
        $notifier = new FakeHubNotifier;
        $this->app->instance(HubNotifier::class, $notifier);

        $first = app(CheckNewsKeywords::class)();
        $second = app(CheckNewsKeywords::class)();

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        $this->assertCount(1, $notifier->sent);
        $this->assertTrue($item->fresh()->notified);
    }

    public function test_items_endpoint_returns_the_documented_contract(): void
    {
        $read = $this->item([
            'topic' => 'dev',
            'feed_key' => 'atom',
            'guid' => 'read',
            'title' => 'Read item',
            'is_read' => true,
        ]);
        $unread = $this->item([
            'topic' => 'dev',
            'feed_key' => 'atom',
            'guid' => 'unread',
            'title' => 'Unread item',
            'published_at' => CarbonImmutable::parse('2026-06-24 11:00:00', 'UTC'),
            'matched_keywords' => ['Laravel'],
        ]);

        $response = $this->getJson(route('news.items.index'));

        $response->assertOk()
            ->assertJsonPath('total_unread', 1)
            ->assertJsonPath('topics.1.key', 'dev')
            ->assertJsonPath('topics.1.label', 'Dev & werk')
            ->assertJsonPath('topics.1.unread', 1)
            ->assertJsonPath('topics.1.items.0.id', $unread->id)
            ->assertJsonPath('topics.1.items.0.source', 'Atom Feed')
            ->assertJsonPath('topics.1.items.0.matched_keywords', ['Laravel'])
            ->assertJsonStructure([
                'topics',
                'total_unread',
                'last_refreshed_at',
            ]);
        $response->assertJsonStructure([
            'topics' => [
                1 => [
                    'key',
                    'label',
                    'unread',
                    'items' => [
                        [
                            'id',
                            'title',
                            'url',
                            'summary',
                            'source',
                            'topic',
                            'published_at',
                            'is_read',
                            'image_url',
                            'matched_keywords',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($read->fresh()->is_read);
    }

    public function test_mark_item_read_and_mark_all_read_update_counts(): void
    {
        $first = $this->item(['topic' => 'dev', 'feed_key' => 'atom', 'guid' => 'first']);
        $second = $this->item(['topic' => '3d-printing', 'feed_key' => 'rss', 'guid' => 'second']);

        $this->postJson(route('news.items.read', $first))->assertOk()->assertJson(['is_read' => true]);
        $this->assertTrue($first->fresh()->is_read);

        $this->postJson(route('news.read-all'), ['topic' => '3d-printing'])
            ->assertOk()
            ->assertJson(['marked' => 1]);

        $this->assertTrue($second->fresh()->is_read);
        $this->getJson(route('news.items.index'))->assertJsonPath('total_unread', 0);
    }

    public function test_index_renders_the_html_page(): void
    {
        $this->withoutVite();
        $this->item([
            'topic' => '3d-printing',
            'feed_key' => 'rss',
            'guid' => 'render',
            'title' => 'Bambu Firmware Released',
            'matched_keywords' => ['Bambu'],
        ]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Nieuws')
            ->assertSee('Bambu Firmware Released')
            ->assertSee('Markeer alles gelezen');
    }

    private function item(array $overrides = []): NewsItem
    {
        return NewsItem::query()->create(array_merge([
            'feed_key' => 'rss',
            'topic' => '3d-printing',
            'guid' => 'guid-'.uniqid(),
            'title' => 'News item',
            'url' => 'https://example.com/item',
            'summary' => 'Summary',
            'published_at' => CarbonImmutable::parse('2026-06-24 10:00:00', 'UTC'),
            'is_read' => false,
            'matched_keywords' => null,
        ], $overrides));
    }

    private function fixture(string $file): string
    {
        return file_get_contents(__DIR__."/../Fixtures/{$file}");
    }
}

class FakeHubNotifier extends HubNotifier
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
