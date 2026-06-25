<?php

namespace Modules\Recipes\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class OfferData
{
    public function __construct(
        public string $store,
        public ?string $externalId,
        public string $productName,
        public ?string $category = null,
        public ?float $normalPrice = null,
        public ?float $offerPrice = null,
        public ?string $discountLabel = null,
        public ?string $unit = null,
        public ?string $imageUrl = null,
        public ?CarbonImmutable $validFrom = null,
        public ?CarbonImmutable $validTo = null,
    ) {
    }

    public function toAttributes(string $weekKey, CarbonInterface $fetchedAt): array
    {
        return [
            'store' => $this->store,
            'external_id' => $this->externalId,
            'product_name' => $this->productName,
            'category' => $this->category,
            'normal_price' => $this->normalPrice,
            'offer_price' => $this->offerPrice,
            'discount_label' => $this->discountLabel,
            'unit' => $this->unit,
            'image_url' => $this->imageUrl,
            'valid_from' => $this->validFrom?->toDateString(),
            'valid_to' => $this->validTo?->toDateString(),
            'week_key' => $weekKey,
            'fetched_at' => $fetchedAt,
        ];
    }
}
