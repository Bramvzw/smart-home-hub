<?php

namespace Modules\News\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Data\RawFeedItem;
use Modules\News\Models\NewsItem;
use Modules\News\Services\FeedClient;
use Modules\News\Services\NewsService;
use Tests\TestCase;

class NewsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 10:00:00', 'UTC'));
        config([
            'news.retention_days' => 7,
            'news.keywords' => ['Bambu', 'Laravel', 'PHP 8'],
            'news.topics' => ['dev' => 'Dev', '3d-printing' => '3D'],
            'news.feeds' => [
                ['key' => 'dev-feed', 'topic' => 'dev', 'label' => 'Dev Feed', 'url' => 'https://example.com/dev'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_refresh_dedupes_and_preserves_read_and_notified_state(): void
    {
        $client = $this->clientWithItems([
            'https://example.com/dev' => [
                new RawFeedItem(
                    guid: 'same-guid',
                    title: 'Laravel release',
                    url: 'https://example.com/first',
                    summary: 'Initial summary',
                    author: null,
                    imageUrl: null,
                    publishedAt: CarbonImmutable::parse('2026-06-24 10:00:00', 'UTC'),
                ),
            ],
        ]);

        $service = new NewsService($client);
        $service->refresh();

        $item = NewsItem::query()->firstOrFail();
        $item->forceFill(['is_read' => true, 'notified' => true])->save();

        $client->items['https://example.com/dev'] = [
            new RawFeedItem(
                guid: 'same-guid',
                title: 'Laravel release updated',
                url: 'https://example.com/updated',
                summary: 'Updated summary',
                author: null,
                imageUrl: null,
                publishedAt: CarbonImmutable::parse('2026-06-24 11:00:00', 'UTC'),
            ),
        ];

        $service->refresh();

        $this->assertSame(1, NewsItem::query()->count());
        $item = NewsItem::query()->firstOrFail();
        $this->assertTrue($item->is_read);
        $this->assertTrue($item->notified);
        $this->assertSame('Laravel release updated', $item->title);
    }

    public function test_refresh_prunes_items_older_than_retention(): void
    {
        NewsItem::query()->create([
            'feed_key' => 'dev-feed',
            'topic' => 'dev',
            'guid' => 'old',
            'title' => 'Old',
            'url' => 'https://example.com/old',
            'summary' => 'Old item',
            'published_at' => CarbonImmutable::parse('2026-06-10 10:00:00', 'UTC'),
        ]);

        $service = new NewsService($this->clientWithItems(['https://example.com/dev' => []]));

        $service->refresh();

        $this->assertDatabaseMissing('news_items', ['guid' => 'old']);
    }

    public function test_keyword_matches_are_case_insensitive_and_search_title_and_summary(): void
    {
        $service = new NewsService($this->clientWithItems([]));
        $item = NewsItem::query()->make([
            'title' => 'bambu firmware notes',
            'summary' => 'Works with PHP 8 apps',
        ]);

        $this->assertSame(['Bambu', 'PHP 8'], $service->keywordMatches($item));
    }

    private function clientWithItems(array $items): FeedClient
    {
        return new class($items) extends FeedClient
        {
            public function __construct(public array $items) {}

            public function fetch(string $url): array
            {
                return $this->items[$url] ?? [];
            }
        };
    }
}
