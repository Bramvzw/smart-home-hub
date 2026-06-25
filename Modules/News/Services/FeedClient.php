<?php

namespace Modules\News\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Reader;
use Modules\News\Data\RawFeedItem;
use Modules\News\Exceptions\FeedUnavailable;
use Throwable;

class FeedClient
{
    /**
     * @return list<RawFeedItem>
     */
    public function fetch(string $url): array
    {
        try {
            // Feed URLs are trusted, operator-configured values (news.feeds in config)
            // and must NEVER originate from user input. As a lightweight SSRF guard we
            // cap redirects so a compromised feed cannot bounce us across hosts unbounded.
            $response = Http::timeout((int) config('news.request_timeout', 10))
                ->maxRedirects(3)
                ->accept('application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.8')
                ->get($url);
        } catch (Throwable $exception) {
            throw new FeedUnavailable("Feed request failed for {$url}", previous: $exception);
        }

        if (! $response->successful()) {
            throw new FeedUnavailable("Feed request returned HTTP {$response->status()} for {$url}");
        }

        try {
            $feed = Reader::importString($response->body());
        } catch (Throwable $exception) {
            throw new FeedUnavailable("Feed could not be parsed for {$url}", previous: $exception);
        }

        $items = [];

        foreach ($feed as $entry) {
            try {
                $item = $this->mapEntry($entry);
            } catch (Throwable) {
                continue;
            }

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function mapEntry(EntryInterface $entry): ?RawFeedItem
    {
        $url = $this->truncate($entry->getLink() ?: $entry->getPermalink(), 2048);

        if ($url === '') {
            return null;
        }

        $title = $this->truncate($this->plainText($entry->getTitle() ?: 'Untitled'), 250);
        $summary = Str::limit($this->plainText($entry->getDescription() ?: $entry->getContent()), 280, '...');
        $date = $entry->getDateModified() ?? $entry->getDateCreated();
        $publishedAt = $date instanceof DateTimeInterface
            ? CarbonImmutable::instance($date)->utc()
            : CarbonImmutable::now('UTC');

        return new RawFeedItem(
            guid: $this->guid($entry->getId() ?: $url),
            title: $title,
            url: $url,
            summary: $summary,
            author: $this->truncate($this->author($entry), 250) ?: null,
            imageUrl: $this->truncate($this->imageUrl($entry), 2048) ?: null,
            publishedAt: $publishedAt,
        );
    }

    private function plainText(?string $value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function author(EntryInterface $entry): string
    {
        $authors = $entry->getAuthors();

        if ($authors === null) {
            return '';
        }

        foreach ($authors as $author) {
            if (is_array($author)) {
                return (string) ($author['name'] ?? $author['email'] ?? '');
            }
        }

        return '';
    }

    private function imageUrl(EntryInterface $entry): string
    {
        $enclosure = $entry->getEnclosure();

        if (! is_object($enclosure)) {
            return '';
        }

        return (string) ($enclosure->url ?? $enclosure->href ?? '');
    }

    private function guid(string $guid): string
    {
        $guid = trim($guid);

        if (mb_strlen($guid) <= 255) {
            return $guid;
        }

        return hash('sha256', $guid);
    }

    private function truncate(?string $value, int $limit): string
    {
        return Str::limit(trim((string) $value), $limit, '');
    }
}
