<?php

namespace Modules\Deals\Data;

final readonly class PriceCheckResult
{
    /**
     * @param  list<array<string, mixed>>  $drops
     * @param  list<string>  $failedRetailers
     */
    public function __construct(
        public int $checked,
        public array $drops,
        public array $failedRetailers,
    ) {
    }

    public function toArray(): array
    {
        return [
            'checked' => $this->checked,
            'drops' => $this->drops,
            'failed_retailers' => $this->failedRetailers,
        ];
    }
}
