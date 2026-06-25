<?php

namespace Modules\Entertainment\Data;

use Carbon\CarbonImmutable;

final readonly class ConcertData
{
    public function __construct(
        public string $source,
        public ?string $externalId,
        public string $artist,
        public ?string $title,
        public ?string $venue,
        public ?string $city,
        public CarbonImmutable $date,
        public ?string $url,
    ) {
    }
}
