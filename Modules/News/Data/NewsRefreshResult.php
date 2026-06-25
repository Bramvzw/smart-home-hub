<?php

namespace Modules\News\Data;

final readonly class NewsRefreshResult
{
    /**
     * @param  list<string>  $skippedFeeds
     * @param  array<string, string>  $failedFeeds
     */
    public function __construct(
        public int $fetched,
        public int $stored,
        public array $skippedFeeds,
        public array $failedFeeds,
    ) {}

    public function toArray(): array
    {
        return [
            'fetched' => $this->fetched,
            'stored' => $this->stored,
            'skipped_feeds' => $this->skippedFeeds,
            'failed_feeds' => $this->failedFeeds,
        ];
    }
}
