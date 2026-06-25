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

    /**
     * Normalize an arbitrary retailer price payload into a positive float, or null.
     *
     * Garbage, missing or non-positive values resolve to null (never 0 or a
     * wrong number) so they can never register as a false price drop downstream.
     */
    private function price(mixed $value): ?float
    {
        // Minor-unit integer amounts (cents) are explicitly keyed; convert those.
        if (is_array($value)) {
            if (($cents = $value['centAmount'] ?? $value['amountInCents'] ?? null) !== null && is_numeric($cents)) {
                return $this->positivePrice((float) $cents / 100);
            }

            $value = $value['amount'] ?? $value['value'] ?? $value['price'] ?? null;
        }

        if (is_int($value) || is_float($value)) {
            return $this->positivePrice((float) $value);
        }

        if (! is_string($value)) {
            return null;
        }

        return $this->positivePrice($this->parseLocalizedNumber($value));
    }

    /**
     * Parse a human/locale-formatted price string ("€ 1.299,00", "1,299.00",
     * "319", "EUR 49.99") into a float, or null if it has no usable number.
     */
    private function parseLocalizedNumber(string $value): ?float
    {
        // Strip everything except digits, separators and a leading sign.
        $value = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';

        if ($value === '' || ! preg_match('/\d/', $value)) {
            return null;
        }

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            // The right-most separator is the decimal mark; the other groups thousands.
            $decimal = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $thousands = $decimal === ',' ? '.' : ',';
            $value = str_replace($thousands, '', $value);
            $value = str_replace($decimal, '.', $value);
        } elseif ($hasComma) {
            // Only a comma present: treat as decimal mark (European "49,99").
            $value = str_replace(',', '.', $value);
        }

        // Reject anything left with more than one decimal point (garbage like "1.2.3").
        if (substr_count($value, '.') > 1) {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function positivePrice(?float $value): ?float
    {
        if ($value === null || ! is_finite($value) || $value <= 0.0) {
            return null;
        }

        return round($value, 2);
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
