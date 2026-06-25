<?php

namespace Modules\News\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\News\Data\NewsRefreshResult;
use Modules\News\Exceptions\FeedUnavailable;
use Modules\News\Models\NewsItem;

class NewsService
{
    public function __construct(
        private readonly FeedClient $client,
    ) {}

    public function refresh(): NewsRefreshResult
    {
        $fetched = 0;
        $stored = 0;
        $skippedFeeds = [];
        $failedFeeds = [];
        $topics = array_keys((array) config('news.topics', []));

        foreach ((array) config('news.feeds', []) as $feed) {
            $key = (string) ($feed['key'] ?? '');
            $topic = (string) ($feed['topic'] ?? '');
            $url = (string) ($feed['url'] ?? '');

            if ($key === '' || $topic === '' || $url === '' || ! in_array($topic, $topics, true)) {
                $skippedFeeds[] = $key !== '' ? $key : $url;

                continue;
            }

            try {
                $items = $this->client->fetch($url);
            } catch (FeedUnavailable $exception) {
                Log::warning('News feed refresh failed', [
                    'feed_key' => $key,
                    'url' => $url,
                    'message' => $exception->getMessage(),
                ]);

                $failedFeeds[$key] = $exception->getMessage();

                continue;
            }

            $fetched += count($items);

            foreach ($items as $item) {
                $matches = $this->matchKeywords($item->title, $item->summary);
                $newsItem = NewsItem::query()->firstOrNew([
                    'feed_key' => $key,
                    'guid' => $item->guid,
                ]);

                $newsItem->fill([
                    'feed_key' => $key,
                    'topic' => $topic,
                    'guid' => $item->guid,
                    'title' => $item->title,
                    'url' => $item->url,
                    'summary' => $item->summary,
                    'author' => $item->author,
                    'image_url' => $item->imageUrl,
                    'published_at' => $item->publishedAt,
                    'matched_keywords' => $matches === [] ? null : $matches,
                ]);

                $newsItem->save();
                $stored++;
            }
        }

        $this->pruneExpired();

        return new NewsRefreshResult(
            fetched: $fetched,
            stored: $stored,
            skippedFeeds: $skippedFeeds,
            failedFeeds: $failedFeeds,
        );
    }

    /**
     * @return list<string>
     */
    public function keywordMatches(NewsItem $item): array
    {
        return $this->matchKeywords($item->title, $item->summary);
    }

    /**
     * @return list<string>
     */
    private function matchKeywords(string $title, string $summary): array
    {
        $haystack = Str::lower("{$title} {$summary}");
        $matches = [];

        foreach ((array) config('news.keywords', []) as $keyword) {
            $keyword = trim((string) $keyword);

            if ($keyword === '') {
                continue;
            }

            if (str_contains($haystack, Str::lower($keyword))) {
                $matches[] = $keyword;
            }
        }

        return array_values(array_unique($matches));
    }

    private function pruneExpired(): void
    {
        $retentionDays = max(1, (int) config('news.retention_days', 7));

        NewsItem::query()
            ->where('published_at', '<', CarbonImmutable::now('UTC')->subDays($retentionDays))
            ->delete();
    }
}
