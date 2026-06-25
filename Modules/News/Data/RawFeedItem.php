<?php

namespace Modules\News\Data;

use Carbon\CarbonImmutable;

final readonly class RawFeedItem
{
    public function __construct(
        public string $guid,
        public string $title,
        public string $url,
        public string $summary,
        public ?string $author,
        public ?string $imageUrl,
        public CarbonImmutable $publishedAt,
    ) {}
}
