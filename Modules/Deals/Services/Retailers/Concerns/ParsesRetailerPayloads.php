<?php

namespace Modules\Deals\Services\Retailers\Concerns;

use Modules\Deals\Data\ListingCandidate;

trait ParsesRetailerPayloads
{
    /**
     * @return list<ListingCandidate>
     */
    private function parseCandidates(string $retailer, array $payload): array
    {
        $candidates = [];

        foreach ($this->candidateItems($payload) as $item) {
            $title = $this->stringValue(data_get($item, 'title') ?? data_get($item, 'name') ?? data_get($item, 'productName'));
            $url = $this->stringValue(data_get($item, 'url') ?? data_get($item, 'link') ?? data_get($item, 'offerUrl'));

            if (! $title || ! $url) {
                continue;
            }

            $candidates[] = new ListingCandidate(
                retailer: $retailer,
                externalId: $this->stringValue(data_get($item, 'external_id') ?? data_get($item, 'externalId') ?? data_get($item, 'id') ?? data_get($item, 'ean') ?? data_get($item, 'offerId')),
                title: $title,
                url: $url,
                price: $this->price(data_get($item, 'price') ?? data_get($item, 'current_price') ?? data_get($item, 'currentPrice') ?? data_get($item, 'bestOffer.price')),
                imageUrl: $this->stringValue(data_get($item, 'image_url') ?? data_get($item, 'imageUrl') ?? data_get($item, 'image') ?? data_get($item, 'images.0.url')),
            );
        }

        return $candidates;
    }

    private function parsePrice(array $payload): ?float
    {
        return $this->price(
            data_get($payload, 'price')
            ?? data_get($payload, 'current_price')
            ?? data_get($payload, 'currentPrice')
            ?? data_get($payload, 'bestOffer.price')
            ?? data_get($payload, 'offers.0.price')
            ?? data_get($payload, 'price_points.0.price')
        );
    }

    private function candidateItems(array $payload): array
    {
        $items = [];
        $this->collect($payload, $items);

        return $items;
    }

    private function collect(mixed $value, array &$items): void
    {
        if (! is_array($value)) {
            return;
        }

        if ($this->looksLikeListing($value)) {
            $items[] = $value;

            return;
        }

        foreach ($value as $child) {
            $this->collect($child, $items);
        }
    }

    private function looksLikeListing(array $value): bool
    {
        $title = data_get($value, 'title') ?? data_get($value, 'name') ?? data_get($value, 'productName');
        $url = data_get($value, 'url') ?? data_get($value, 'link') ?? data_get($value, 'offerUrl');

        return is_string($title) && trim($title) !== '' && is_string($url) && trim($url) !== '';
    }

    private function price(mixed $value): ?float
    {
        if (is_array($value)) {
            $value = $value['amount'] ?? $value['value'] ?? $value['price'] ?? $value['centAmount'] ?? null;
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
            $value = $value['url'] ?? $value['name'] ?? $value['label'] ?? null;
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
