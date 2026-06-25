<?php

namespace Modules\Recipes\Services\Concerns;

use Carbon\CarbonImmutable;
use Modules\Recipes\Data\OfferData;

trait ParsesOfferPayloads
{
    /**
     * @return list<OfferData>
     */
    private function parseOffers(string $store, array $payload): array
    {
        $offers = [];

        foreach ($this->candidateItems($payload) as $item) {
            $name = $this->stringValue(
                data_get($item, 'product_name')
                ?? data_get($item, 'productName')
                ?? data_get($item, 'displayName')
                ?? data_get($item, 'fullTitle')
                ?? data_get($item, 'title')
                ?? data_get($item, 'name')
                ?? data_get($item, 'description')
                ?? data_get($item, 'product.title')
                ?? data_get($item, 'product.name')
            );

            if ($name === null) {
                continue;
            }

            $offers[] = new OfferData(
                store: $store,
                externalId: $this->stringValue(
                    data_get($item, 'external_id')
                    ?? data_get($item, 'externalId')
                    ?? data_get($item, 'id')
                    ?? data_get($item, 'productId')
                    ?? data_get($item, 'webshopId')
                    ?? data_get($item, 'sku')
                    ?? data_get($item, 'code')
                    ?? data_get($item, 'gtin')
                    ?? data_get($item, 'product.id')
                ),
                productName: $name,
                category: $this->stringValue(
                    data_get($item, 'category')
                    ?? data_get($item, 'categoryName')
                    ?? data_get($item, 'taxonomy.category')
                    ?? data_get($item, 'product.category')
                ),
                normalPrice: $this->price(
                    data_get($item, 'normal_price')
                    ?? data_get($item, 'normalPrice')
                    ?? data_get($item, 'wasPrice')
                    ?? data_get($item, 'priceBeforeBonus')
                    ?? data_get($item, 'priceInfo.oldPrice')
                    ?? data_get($item, 'oldPrice')
                    ?? data_get($item, 'product.priceBeforeBonus')
                ),
                offerPrice: $this->price(
                    data_get($item, 'offer_price')
                    ?? data_get($item, 'offerPrice')
                    ?? data_get($item, 'priceAfterBonus')
                    ?? data_get($item, 'currentPrice')
                    ?? data_get($item, 'price')
                    ?? data_get($item, 'priceInfo.price')
                    ?? data_get($item, 'product.priceAfterBonus')
                    ?? data_get($item, 'product.price')
                ),
                discountLabel: $this->stringValue(
                    data_get($item, 'discount_label')
                    ?? data_get($item, 'discountLabel')
                    ?? data_get($item, 'bonusLabel')
                    ?? data_get($item, 'bonusMechanism')
                    ?? data_get($item, 'offerLabel')
                    ?? data_get($item, 'promotion.label')
                    ?? data_get($item, 'priceInfo.discount')
                    ?? data_get($item, 'badge')
                    ?? data_get($item, 'subtitle')
                ),
                unit: $this->stringValue(
                    data_get($item, 'unit')
                    ?? data_get($item, 'salesUnitSize')
                    ?? data_get($item, 'unitInfo')
                    ?? data_get($item, 'product.salesUnitSize')
                ),
                imageUrl: $this->stringValue(
                    data_get($item, 'image_url')
                    ?? data_get($item, 'imageUrl')
                    ?? data_get($item, 'image')
                    ?? data_get($item, 'image.url')
                    ?? data_get($item, 'images.0.url')
                    ?? data_get($item, 'product.images.0.url')
                ),
                validFrom: $this->date(data_get($item, 'valid_from') ?? data_get($item, 'validFrom') ?? data_get($item, 'from')),
                validTo: $this->date(data_get($item, 'valid_to') ?? data_get($item, 'validTo') ?? data_get($item, 'to')),
            );
        }

        return array_values($offers);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function candidateItems(array $payload): array
    {
        $items = [];
        $this->collectCandidateItems($payload, $items);

        return $items;
    }

    private function collectCandidateItems(mixed $value, array &$items): void
    {
        if (! is_array($value)) {
            return;
        }

        if ($this->looksLikeOffer($value)) {
            $items[] = $value;

            return;
        }

        foreach ($value as $child) {
            $this->collectCandidateItems($child, $items);
        }
    }

    private function looksLikeOffer(array $value): bool
    {
        $name = data_get($value, 'product_name')
            ?? data_get($value, 'productName')
            ?? data_get($value, 'displayName')
            ?? data_get($value, 'fullTitle')
            ?? data_get($value, 'title')
            ?? data_get($value, 'name')
            ?? data_get($value, 'description')
            ?? data_get($value, 'product.title')
            ?? data_get($value, 'product.name');

        if (! is_string($name) || trim($name) === '') {
            return false;
        }

        return data_get($value, 'price') !== null
            || data_get($value, 'currentPrice') !== null
            || data_get($value, 'priceAfterBonus') !== null
            || data_get($value, 'priceInfo.price') !== null
            || data_get($value, 'bonusMechanism') !== null
            || data_get($value, 'discountLabel') !== null
            || data_get($value, 'promotion.label') !== null
            || data_get($value, 'product.price') !== null;
    }

    private function price(mixed $value): ?float
    {
        if (is_array($value)) {
            $value = $value['amount']
                ?? $value['value']
                ?? $value['price']
                ?? $value['centAmount']
                ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) && $value > 100) {
            return round($value / 100, 2);
        }

        if (is_string($value)) {
            $value = str_replace(['€', ' '], '', $value);
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['label'] ?? $value['name'] ?? $value['url'] ?? null;
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
